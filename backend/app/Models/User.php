<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasUuids;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'governorate', 'is_active', 'last_login'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean', 'last_login' => 'datetime'];

    public function members() { return $this->hasMany(Member::class, 'assigned_trainer_id'); }
    public function notifications() { return $this->hasMany(Notification::class); }
    public function auditLogs() { return $this->hasMany(AuditLog::class); }
    public function mealPlans() { return $this->hasMany(MealPlan::class, 'created_by'); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isTrainer(): bool { return $this->role === 'trainer'; }
    public function isReception(): bool { return $this->role === 'reception'; }
}
