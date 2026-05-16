<?php
// app/Models/Member.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Member extends Model
{
    use HasUuids;

    protected $fillable = [
        'assigned_trainer_id', 'first_name', 'last_name', 'phone', 'email',
        'birth_date', 'gender', 'governorate', 'health_condition', 'goal',
        'allergies', 'activity_level', 'referral_source', 'is_active',
    ];

    protected $casts = ['birth_date' => 'date', 'is_active' => 'boolean'];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date?->age ?? 0;
    }

    public function trainer() { return $this->belongsTo(User::class, 'assigned_trainer_id'); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function activeSubscription() { return $this->hasOne(Subscription::class)->where('status', 'active')->latest(); }
    public function measurements() { return $this->hasMany(Measurement::class)->orderByDesc('measured_at'); }
    public function latestMeasurement() { return $this->hasOne(Measurement::class)->orderByDesc('measured_at'); }
    public function mealPlans() { return $this->hasMany(MealPlan::class); }
    public function activeMealPlan() { return $this->hasOne(MealPlan::class)->where('is_active', true)->latest(); }
    public function dailyTracking() { return $this->hasMany(DailyTracking::class); }
    public function weightGoal() { return $this->hasOne(WeightGoal::class)->latest(); }
    public function photos() { return $this->hasMany(BeforeAfterPhoto::class); }
    public function notifications() { return $this->hasMany(Notification::class); }
}
