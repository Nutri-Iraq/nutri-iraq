<?php
// app/Services/WhatsAppService.php
namespace App\Services;

use App\Models\Member;
use App\Models\MealPlan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $token;
    private string $phoneId;

    public function __construct()
    {
        $this->apiUrl  = config('services.whatsapp.url', 'https://graph.facebook.com/v18.0');
        $this->token   = config('services.whatsapp.token', '');
        $this->phoneId = config('services.whatsapp.phone_id', '');
    }

    public function sendMessage(string $memberId, string $message): array
    {
        $member = Member::findOrFail($memberId);
        $phone  = $this->formatPhone($member->phone);

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => ['body' => $message],
                ]);

            Log::info('WhatsApp sent', ['to' => $phone, 'status' => $response->status()]);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function sendMealPlan(MealPlan $mealPlan): array
    {
        $member  = $mealPlan->member;
        $meals   = $mealPlan->meals->map(fn($m) =>
            "• {$m->meal_type_arabic} ({$m->scheduled_time}): {$m->description} — {$m->calories} سعرة"
        )->join("\n");

        $message = "🥗 *خطتك الغذائية — نيوتري عراق*\n\n"
                 . "الإجمالي اليومي: {$mealPlan->total_calories} سعرة حرارية\n"
                 . "بروتين: {$mealPlan->protein_g}غ | كربوهيدرات: {$mealPlan->carbs_g}غ | دهون: {$mealPlan->fat_g}غ\n\n"
                 . "*الوجبات:*\n{$meals}\n\n"
                 . "_للاستفسار تواصل مع المركز_ ✅";

        return $this->sendMessage($member->id, $message);
    }

    private function formatPhone(string $phone): string
    {
        // تحويل 07X إلى 9647X
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '964' . substr($phone, 1);
        }
        return $phone;
    }
}

// ─────────────────────────────────────────────────

// app/Services/PdfService.php
namespace App\Services;

use App\Models\MealPlan;
use Illuminate\Http\Response;

class PdfService
{
    public function generateMealPlan(MealPlan $mealPlan): Response
    {
        // يتطلب barryvdh/laravel-dompdf
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdfs.meal-plan', compact('mealPlan'));
        $pdf->setPaper('A4', 'portrait');

        $filename = "خطة-غذائية-{$mealPlan->member->full_name}.pdf";
        return $pdf->download($filename);
    }

    public function generateReport(string $period): Response
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdfs.report', compact('period'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("تقرير-نيوتري-عراق-{$period}.pdf");
    }
}

// ─────────────────────────────────────────────────

// app/Models/WeightGoal.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WeightGoal extends Model
{
    use HasUuids;

    protected $fillable = [
        'member_id', 'current_weight_kg', 'target_weight_kg', 'progress_pct', 'target_date',
    ];

    protected $casts = ['target_date' => 'date'];

    public function member() { return $this->belongsTo(Member::class); }
}

// ─────────────────────────────────────────────────

// app/Models/BeforeAfterPhoto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BeforeAfterPhoto extends Model
{
    use HasUuids;
    protected $fillable = ['member_id', 'photo_type', 'file_path', 'taken_at'];
    protected $casts    = ['taken_at' => 'date'];
    public function member() { return $this->belongsTo(Member::class); }
}

// ─────────────────────────────────────────────────

// app/Models/AuditLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditLog extends Model
{
    use HasUuids;
    protected $fillable = ['user_id','action','table_name','record_id','old_values','new_values','ip_address'];
    protected $casts    = ['old_values' => 'array', 'new_values' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
}

// ─────────────────────────────────────────────────

// app/Models/CenterSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CenterSetting extends Model
{
    use HasUuids;
    protected $fillable = [
        'center_name','governorate','address','phone','email',
        'primary_color','logo_path','working_hours','accept_new_members',
    ];
    protected $casts = ['working_hours' => 'array', 'accept_new_members' => 'boolean'];
}
