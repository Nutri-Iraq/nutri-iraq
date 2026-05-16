<?php
// app/Http/Controllers/MemberController.php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\WeightGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Member::with(['trainer:id,name', 'latestMeasurement', 'activeSubscription', 'weightGoal'])
            ->when($request->search, fn($q, $s) =>
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
            )
            ->when($request->governorate, fn($q, $g) => $q->where('governorate', $g))
            ->when($request->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($request->trainer_id, fn($q, $t) => $q->where('assigned_trainer_id', $t));

        // المدرب يرى مشتركيه فقط
        if ($user->isTrainer()) {
            $query->where('assigned_trainer_id', $user->id);
        }

        $members = $query->orderByDesc('created_at')->paginate($request->limit ?? 20);

        return response()->json([
            'data' => $members->items(),
            'meta' => [
                'total'     => $members->total(),
                'page'      => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page'  => $members->perPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'          => 'required|string|max:100',
            'last_name'           => 'required|string|max:100',
            'phone'               => 'required|string|unique:members',
            'email'               => 'nullable|email|unique:members',
            'birth_date'          => 'nullable|date',
            'gender'              => 'required|in:male,female',
            'governorate'         => 'required|string',
            'goal'                => 'required|in:lose,gain,muscle,health',
            'activity_level'      => 'nullable|in:sedentary,light,moderate,active,athlete',
            'health_condition'    => 'nullable|string',
            'allergies'           => 'nullable|string',
            'referral_source'     => 'nullable|string',
            'assigned_trainer_id' => 'nullable|exists:users,id',
            'target_weight_kg'    => 'nullable|numeric',
            'current_weight_kg'   => 'nullable|numeric',
        ]);

        $member = DB::transaction(function () use ($data) {
            $member = Member::create($data);

            if (isset($data['current_weight_kg']) && isset($data['target_weight_kg'])) {
                WeightGoal::create([
                    'member_id'         => $member->id,
                    'current_weight_kg' => $data['current_weight_kg'],
                    'target_weight_kg'  => $data['target_weight_kg'],
                    'progress_pct'      => 0,
                ]);
            }

            return $member;
        });

        return response()->json([
            'id'      => $member->id,
            'message' => 'تم إضافة المشترك بنجاح.',
        ], 201);
    }

    public function show(Member $member)
    {
        $this->authorizeTrainer($member);

        $member->load([
            'trainer:id,name,phone',
            'measurements' => fn($q) => $q->orderByDesc('measured_at')->limit(10),
            'activeSubscription.trainer:id,name',
            'activeMealPlan.meals',
            'weightGoal',
            'photos',
        ]);

        return response()->json($member->append(['full_name', 'age']));
    }

    public function update(Request $request, Member $member)
    {
        $this->authorizeTrainer($member);

        $data = $request->validate([
            'first_name'          => 'sometimes|string|max:100',
            'last_name'           => 'sometimes|string|max:100',
            'phone'               => "sometimes|string|unique:members,phone,{$member->id}",
            'email'               => "sometimes|nullable|email|unique:members,email,{$member->id}",
            'governorate'         => 'sometimes|string',
            'goal'                => 'sometimes|in:lose,gain,muscle,health',
            'health_condition'    => 'sometimes|nullable|string',
            'allergies'           => 'sometimes|nullable|string',
            'activity_level'      => 'sometimes|in:sedentary,light,moderate,active,athlete',
            'assigned_trainer_id' => 'sometimes|nullable|exists:users,id',
            'is_active'           => 'sometimes|boolean',
        ]);

        $member->update($data);

        return response()->json(['message' => 'تم تحديث بيانات المشترك.']);
    }

    public function destroy(Member $member)
    {
        // المدير فقط
        if (!request()->user()->isAdmin()) {
            return response()->json(['message' => 'غير مصرح.'], 403);
        }

        $member->delete();
        return response()->json(['message' => 'تم حذف المشترك.']);
    }

    public function timeline(Member $member)
    {
        $this->authorizeTrainer($member);

        $timeline = collect();

        // آخر 5 قياسات
        $member->measurements()->limit(5)->get()->each(fn($m) =>
            $timeline->push([
                'type' => 'measurement',
                'date' => $m->measured_at,
                'text' => "تسجيل وزن: {$m->weight_kg} كغ",
                'icon' => 'scale',
            ])
        );

        // آخر 5 يوم متابعة
        $member->dailyTracking()->orderByDesc('tracking_date')->limit(5)->get()->each(fn($t) =>
            $timeline->push([
                'type' => 'tracking',
                'date' => $t->tracking_date,
                'text' => $t->diet_followed ? 'التزم بالنظام الغذائي' : 'لم يلتزم بالنظام',
                'icon' => $t->diet_followed ? 'check' : 'x',
            ])
        );

        // الاشتراكات
        $member->subscriptions()->limit(3)->get()->each(fn($s) =>
            $timeline->push([
                'type' => 'subscription',
                'date' => $s->created_at,
                'text' => "اشتراك {$s->package} — {$s->status}",
                'icon' => 'credit-card',
            ])
        );

        return response()->json(
            $timeline->sortByDesc('date')->values()
        );
    }

    private function authorizeTrainer(Member $member): void
    {
        $user = request()->user();
        if ($user->isTrainer() && $member->assigned_trainer_id !== $user->id) {
            abort(403, 'غير مصرح بالوصول لهذا المشترك.');
        }
    }
}
