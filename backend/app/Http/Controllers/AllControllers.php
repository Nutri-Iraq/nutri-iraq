<?php
// app/Http/Controllers/SubscriptionController.php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['member:id,first_name,last_name,phone,governorate', 'trainer:id,name'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->member_id, fn($q, $id) => $q->where('member_id', $id));

        if ($request->user()->isTrainer()) {
            $query->where('trainer_id', $request->user()->id);
        }

        return response()->json($query->orderByDesc('created_at')->paginate(20));
    }

    public function store(Request $request, NotificationService $notif)
    {
        $data = $request->validate([
            'member_id'      => 'required|exists:members,id',
            'trainer_id'     => 'nullable|exists:users,id',
            'package'        => 'required|in:1m,3m,6m',
            'price_iqd'      => 'required|integer|min:0',
            'start_date'     => 'required|date',
            'payment_method' => 'required|in:cash,zain_cash,asia,bank',
            'notes'          => 'nullable|string',
        ]);

        // إلغاء الاشتراك النشط القديم إن وُجد
        Subscription::where('member_id', $data['member_id'])
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        $sub = Subscription::create($data);

        $member = Member::find($data['member_id']);
        $notif->create(
            type: 'subscription',
            priority: 'normal',
            title: "اشتراك جديد — {$member->full_name}",
            body: "تم إنشاء اشتراك {$sub->package} للمشترك {$member->full_name}.",
            memberId: $member->id,
        );

        return response()->json([
            'id'       => $sub->id,
            'end_date' => $sub->end_date->format('Y-m-d'),
            'message'  => 'تم إنشاء الاشتراك بنجاح.',
        ], 201);
    }

    public function renew(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'package'        => 'required|in:1m,3m,6m',
            'price_iqd'      => 'required|integer|min:0',
            'payment_method' => 'required|in:cash,zain_cash,asia,bank',
        ]);

        $subscription->update(['status' => 'expired']);

        $newSub = Subscription::create([
            ...$data,
            'member_id'  => $subscription->member_id,
            'trainer_id' => $subscription->trainer_id,
            'start_date' => now()->toDateString(),
        ]);

        return response()->json([
            'id'      => $newSub->id,
            'message' => 'تم تجديد الاشتراك بنجاح.',
        ]);
    }

    public function cancel(Subscription $subscription)
    {
        $subscription->update(['status' => 'cancelled']);
        return response()->json(['message' => 'تم إلغاء الاشتراك.']);
    }

    public function expiring(Request $request)
    {
        $days = $request->integer('days', 3);
        $subs = Subscription::expiringSoon($days)
            ->with('member:id,first_name,last_name,phone,governorate')
            ->get()
            ->map(fn($s) => [
                ...$s->toArray(),
                'days_remaining' => $s->days_remaining,
            ]);

        return response()->json($subs);
    }
}

// ─────────────────────────────────────

// app/Http/Controllers/MeasurementController.php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Measurement;
use App\Models\WeightGoal;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    public function index(Member $member)
    {
        $measurements = $member->measurements()
            ->orderByDesc('measured_at')
            ->get()
            ->append(['bmi_category']);

        return response()->json(['data' => $measurements]);
    }

    public function store(Request $request, Member $member)
    {
        $data = $request->validate([
            'weight_kg'       => 'required|numeric|min:20|max:300',
            'height_cm'       => 'nullable|numeric|min:100|max:250',
            'body_fat_pct'    => 'nullable|numeric|min:0|max:100',
            'waist_cm'        => 'nullable|numeric',
            'muscle_mass_pct' => 'nullable|numeric',
            'measured_at'     => 'required|date',
            'notes'           => 'nullable|string',
        ]);

        // إذا لم يُرسل الطول، نأخذه من آخر قياس
        if (empty($data['height_cm'])) {
            $data['height_cm'] = $member->latestMeasurement?->height_cm;
        }

        $measurement = $member->measurements()->create($data);

        // تحديث نسبة التقدم نحو الهدف
        $goal = $member->weightGoal;
        if ($goal) {
            $lost = $goal->current_weight_kg - $measurement->weight_kg;
            $total = $goal->current_weight_kg - $goal->target_weight_kg;
            $progress = $total > 0 ? round(($lost / $total) * 100, 1) : 0;
            $goal->update(['progress_pct' => max(0, min(100, $progress))]);
        }

        return response()->json([
            'id'           => $measurement->id,
            'bmi'          => $measurement->bmi,
            'bmi_category' => $measurement->bmi_category,
            'message'      => 'تم تسجيل القياس بنجاح.',
        ], 201);
    }

    public function chart(Member $member)
    {
        $measurements = $member->measurements()
            ->orderBy('measured_at')
            ->limit(12)
            ->get(['measured_at', 'weight_kg', 'bmi', 'body_fat_pct', 'waist_cm']);

        return response()->json([
            'labels'        => $measurements->pluck('measured_at')->map->format('d/m'),
            'weight'        => $measurements->pluck('weight_kg'),
            'bmi'           => $measurements->pluck('bmi'),
            'body_fat'      => $measurements->pluck('body_fat_pct'),
            'waist'         => $measurements->pluck('waist_cm'),
        ]);
    }
}

// ─────────────────────────────────────

// app/Http/Controllers/MealPlanController.php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MealPlan;
use App\Services\PdfService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MealPlanController extends Controller
{
    public function index(Member $member)
    {
        return response()->json(
            $member->mealPlans()->with('meals')->orderByDesc('created_at')->get()
        );
    }

    public function show(MealPlan $mealPlan)
    {
        return response()->json($mealPlan->load('meals', 'member:id,first_name,last_name', 'creator:id,name'));
    }

    public function store(Request $request, Member $member)
    {
        $data = $request->validate([
            'total_calories' => 'required|integer|min:500|max:5000',
            'protein_g'      => 'required|numeric',
            'carbs_g'        => 'required|numeric',
            'fat_g'          => 'required|numeric',
            'goal'           => 'required|in:lose,gain,muscle,health',
            'duration_days'  => 'nullable|integer|min:7',
            'meals'          => 'required|array|min:3',
            'meals.*.meal_type'       => 'required|in:breakfast,snack1,lunch,snack2,dinner',
            'meals.*.scheduled_time'  => 'nullable|date_format:H:i',
            'meals.*.calories'        => 'required|integer',
            'meals.*.description'     => 'required|string',
        ]);

        $plan = DB::transaction(function () use ($data, $member, $request) {
            // تعطيل الخطة السابقة
            $member->mealPlans()->where('is_active', true)->update(['is_active' => false]);

            $plan = $member->mealPlans()->create([
                ...$data,
                'created_by'  => $request->user()->id,
                'is_active'   => true,
            ]);

            foreach ($data['meals'] as $mealData) {
                $plan->meals()->create($mealData);
            }

            return $plan;
        });

        return response()->json([
            'id'      => $plan->id,
            'message' => 'تم إنشاء الخطة الغذائية بنجاح.',
        ], 201);
    }

    public function pdf(MealPlan $mealPlan, PdfService $pdf)
    {
        $mealPlan->load(['meals', 'member', 'creator']);
        return $pdf->generateMealPlan($mealPlan);
    }

    public function sendWhatsapp(MealPlan $mealPlan, WhatsAppService $wa)
    {
        $mealPlan->load(['meals', 'member']);
        $result = $wa->sendMealPlan($mealPlan);

        return response()->json([
            'message'            => 'تم الإرسال بنجاح.',
            'whatsapp_message_id' => $result['id'] ?? null,
        ]);
    }
}

// ─────────────────────────────────────

// app/Http/Controllers/DailyTrackingController.php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\DailyTracking;
use Illuminate\Http\Request;

class DailyTrackingController extends Controller
{
    public function index(Member $member, Request $request)
    {
        $query = $member->dailyTracking()->orderByDesc('tracking_date');

        if ($request->date) {
            $query->whereDate('tracking_date', $request->date);
        } elseif ($request->from && $request->to) {
            $query->whereBetween('tracking_date', [$request->from, $request->to]);
        } else {
            $query->limit(30);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request, Member $member)
    {
        $data = $request->validate([
            'tracking_date'  => 'required|date',
            'diet_followed'  => 'required|boolean',
            'water_liters'   => 'nullable|numeric|min:0|max:10',
            'steps_count'    => 'nullable|integer|min:0',
            'exercise_notes' => 'nullable|string',
            'sleep_hours'    => 'nullable|numeric|min:0|max:24',
            'notes'          => 'nullable|string',
        ]);

        $tracking = $member->dailyTracking()->updateOrCreate(
            ['tracking_date' => $data['tracking_date']],
            $data
        );

        return response()->json([
            'id'      => $tracking->id,
            'message' => 'تم تسجيل المتابعة اليومية.',
        ], 201);
    }

    public function complianceReport(Request $request)
    {
        $from = $request->from ?? now()->subDays(30)->toDateString();
        $to   = $request->to   ?? now()->toDateString();

        $query = DailyTracking::whereBetween('tracking_date', [$from, $to])
            ->with('member:id,first_name,last_name');

        if ($request->user()->isTrainer()) {
            $query->whereHas('member', fn($q) =>
                $q->where('assigned_trainer_id', $request->user()->id)
            );
        }

        $data = $query->get()
            ->groupBy('member_id')
            ->map(fn($records, $memberId) => [
                'member'         => $records->first()->member,
                'total_days'     => $records->count(),
                'followed_days'  => $records->where('diet_followed', true)->count(),
                'compliance_pct' => round($records->where('diet_followed', true)->count() / $records->count() * 100, 1),
                'avg_water'      => round($records->avg('water_liters'), 1),
                'avg_steps'      => round($records->avg('steps_count')),
                'avg_sleep'      => round($records->avg('sleep_hours'), 1),
            ])
            ->values();

        return response()->json(['data' => $data, 'from' => $from, 'to' => $to]);
    }
}

// ─────────────────────────────────────

// app/Http/Controllers/ReportController.php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\Measurement;
use App\Models\DailyTracking;
use App\Services\PdfService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dashboard()
    {
        $now = now();

        return response()->json([
            'total_members'          => Member::count(),
            'active_members'         => Member::where('is_active', true)->count(),
            'new_members_this_month' => Member::whereMonth('created_at', $now->month)->count(),
            'active_subscriptions'   => Subscription::where('status', 'active')->count(),
            'expiring_soon'          => Subscription::expiringSoon(3)->count(),
            'revenue_this_month_iqd' => Subscription::whereMonth('created_at', $now->month)->sum('price_iqd'),
            'total_weight_lost_kg'   => $this->calcWeightLost(),
            'compliance_rate'        => $this->calcComplianceRate(),
        ]);
    }

    public function weightLost(Request $request)
    {
        $period = $request->period ?? 'month';

        $data = match($period) {
            'week'    => $this->weightByWeek(1),
            'quarter' => $this->weightByMonth(3),
            'year'    => $this->weightByMonth(12),
            default   => $this->weightByWeek(4),
        };

        return response()->json($data);
    }

    public function byGovernorate()
    {
        $data = Member::selectRaw('governorate, count(*) as total, sum(is_active) as active')
            ->groupBy('governorate')
            ->orderByDesc('total')
            ->get();

        return response()->json($data);
    }

    public function bmiDistribution()
    {
        $measurements = Measurement::whereIn('id', function ($q) {
            $q->selectRaw('MAX(id)')->from('measurements')->groupBy('member_id');
        })->get(['bmi']);

        $dist = [
            'نحيف (<18.5)'       => $measurements->where('bmi', '<', 18.5)->count(),
            'طبيعي (18.5-25)'    => $measurements->whereBetween('bmi', [18.5, 24.9])->count(),
            'زيادة (25-30)'      => $measurements->whereBetween('bmi', [25, 29.9])->count(),
            'سمنة 1 (30-35)'     => $measurements->whereBetween('bmi', [30, 34.9])->count(),
            'سمنة 2 (35-40)'     => $measurements->whereBetween('bmi', [35, 39.9])->count(),
            'سمنة مفرطة (40+)'   => $measurements->where('bmi', '>=', 40)->count(),
        ];

        return response()->json($dist);
    }

    public function export(Request $request, PdfService $pdf)
    {
        $type   = $request->type   ?? 'pdf';
        $period = $request->period ?? 'month';

        if ($type === 'pdf') {
            return $pdf->generateReport($period);
        }

        return response()->json(['message' => 'Excel export قريباً.']);
    }

    private function calcWeightLost(): float
    {
        $now = now();
        return Member::with(['measurements' => fn($q) =>
            $q->whereMonth('measured_at', $now->month)->orderBy('measured_at')
        ])->get()->sum(function ($member) {
            $first = $member->measurements->first();
            $last  = $member->measurements->last();
            return $first && $last ? max(0, $first->weight_kg - $last->weight_kg) : 0;
        });
    }

    private function calcComplianceRate(): float
    {
        $total    = DailyTracking::whereMonth('tracking_date', now()->month)->count();
        $followed = DailyTracking::whereMonth('tracking_date', now()->month)->where('diet_followed', true)->count();
        return $total > 0 ? round($followed / $total, 2) : 0;
    }

    private function weightByWeek(int $weeks): array
    {
        // تجميع الوزن المفقود أسبوعياً
        $labels = [];
        $values = [];
        for ($i = $weeks; $i >= 1; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end   = now()->subWeeks($i - 1)->endOfWeek();
            $labels[] = "أسب{$i}";
            $values[] = DailyTracking::whereBetween('tracking_date', [$start, $end])
                ->where('diet_followed', true)->count() * 0.5; // تقريب
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private function weightByMonth(int $months): array
    {
        $arabicMonths = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
        $labels = [];
        $values = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $arabicMonths[$date->month];
            $values[] = Subscription::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('price_iqd') / 1_000_000;
        }
        return ['labels' => $labels, 'values' => $values];
    }
}
