<?php
// app/Models/Subscription.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'member_id', 'trainer_id', 'package', 'price_iqd',
        'start_date', 'end_date', 'payment_method', 'status', 'notes',
    ];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (Subscription $sub) {
            $months = match($sub->package) {
                '1m' => 1, '3m' => 3, '6m' => 6, default => 1,
            };
            $sub->end_date = Carbon::parse($sub->start_date)->addMonths($months);
        });
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->status === 'active' && $this->days_remaining <= 3;
    }

    public function member() { return $this->belongsTo(Member::class); }
    public function trainer() { return $this->belongsTo(User::class, 'trainer_id'); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeExpiringSoon($query, int $days = 3) {
        return $query->active()->whereDate('end_date', '<=', now()->addDays($days));
    }
}

// ─────────────────────────────────────

// app/Models/Measurement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Measurement extends Model
{
    use HasUuids;

    protected $fillable = [
        'member_id', 'weight_kg', 'height_cm', 'bmi',
        'body_fat_pct', 'waist_cm', 'muscle_mass_pct', 'measured_at', 'notes',
    ];

    protected $casts = ['measured_at' => 'date'];

    protected static function booted(): void
    {
        static::saving(function (Measurement $m) {
            if ($m->weight_kg && $m->height_cm) {
                $heightM = $m->height_cm / 100;
                $m->bmi = round($m->weight_kg / ($heightM * $heightM), 2);
            }
        });
    }

    public function getBmiCategoryAttribute(): string
    {
        return match(true) {
            $this->bmi < 18.5 => 'نحيف',
            $this->bmi < 25   => 'طبيعي',
            $this->bmi < 30   => 'زيادة وزن',
            $this->bmi < 35   => 'سمنة درجة 1',
            default            => 'سمنة مرتفعة',
        };
    }

    public function member() { return $this->belongsTo(Member::class); }
}

// ─────────────────────────────────────

// app/Models/MealPlan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MealPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'member_id', 'created_by', 'total_calories',
        'protein_g', 'carbs_g', 'fat_g', 'goal', 'duration_days', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function member() { return $this->belongsTo(Member::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function meals() { return $this->hasMany(Meal::class)->orderBy('day_number')->orderBy('scheduled_time'); }
}

// ─────────────────────────────────────

// app/Models/Meal.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Meal extends Model
{
    use HasUuids;

    protected $fillable = ['meal_plan_id', 'meal_type', 'scheduled_time', 'calories', 'description', 'day_number'];

    public function getMealTypeArabicAttribute(): string
    {
        return match($this->meal_type) {
            'breakfast' => 'الفطور',
            'snack1'    => 'وجبة خفيفة صباحية',
            'lunch'     => 'الغداء',
            'snack2'    => 'وجبة خفيفة مسائية',
            'dinner'    => 'العشاء',
            default     => $this->meal_type,
        };
    }

    public function mealPlan() { return $this->belongsTo(MealPlan::class); }
}

// ─────────────────────────────────────

// app/Models/DailyTracking.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DailyTracking extends Model
{
    use HasUuids;

    protected $table = 'daily_tracking';

    protected $fillable = [
        'member_id', 'tracking_date', 'diet_followed', 'water_liters',
        'steps_count', 'exercise_notes', 'sleep_hours', 'notes',
    ];

    protected $casts = [
        'tracking_date' => 'date',
        'diet_followed' => 'boolean',
        'water_liters'  => 'float',
        'sleep_hours'   => 'float',
    ];

    public function member() { return $this->belongsTo(Member::class); }
}

// ─────────────────────────────────────

// app/Models/Notification.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'member_id', 'type', 'priority',
        'title', 'body', 'is_read', 'is_sent_whatsapp', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_sent_whatsapp' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function markAsRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }

    public function user() { return $this->belongsTo(User::class); }
    public function member() { return $this->belongsTo(Member::class); }

    public function scopeUnread($query) { return $query->where('is_read', false); }
    public function scopeUrgent($query) { return $query->where('priority', 'urgent'); }
}
