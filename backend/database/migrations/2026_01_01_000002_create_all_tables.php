<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('assigned_trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('governorate');
            $table->string('health_condition')->nullable();
            $table->enum('goal', ['lose', 'gain', 'muscle', 'health'])->default('lose');
            $table->string('allergies')->nullable();
            $table->enum('activity_level', ['sedentary', 'light', 'moderate', 'active', 'athlete'])->default('moderate');
            $table->string('referral_source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('package', ['1m', '3m', '6m']);
            $table->unsignedInteger('price_iqd');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('payment_method', ['cash', 'zain_cash', 'asia', 'bank'])->default('cash');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('measurements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('bmi', 4, 2)->nullable();
            $table->decimal('body_fat_pct', 4, 2)->nullable();
            $table->decimal('waist_cm', 5, 2)->nullable();
            $table->decimal('muscle_mass_pct', 4, 2)->nullable();
            $table->date('measured_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('weight_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('current_weight_kg', 5, 2);
            $table->decimal('target_weight_kg', 5, 2);
            $table->decimal('progress_pct', 5, 2)->default(0);
            $table->date('target_date')->nullable();
            $table->timestamps();
        });

        Schema::create('meal_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_calories');
            $table->decimal('protein_g', 6, 2)->default(0);
            $table->decimal('carbs_g', 6, 2)->default(0);
            $table->decimal('fat_g', 6, 2)->default(0);
            $table->enum('goal', ['lose', 'gain', 'muscle', 'health'])->default('lose');
            $table->unsignedInteger('duration_days')->default(90);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('meals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->enum('meal_type', ['breakfast', 'snack1', 'lunch', 'snack2', 'dinner']);
            $table->time('scheduled_time')->nullable();
            $table->unsignedInteger('calories')->default(0);
            $table->text('description');
            $table->unsignedSmallInteger('day_number')->default(1);
            $table->timestamps();
        });

        Schema::create('daily_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->date('tracking_date');
            $table->boolean('diet_followed')->default(false);
            $table->decimal('water_liters', 3, 1)->default(0);
            $table->unsignedInteger('steps_count')->default(0);
            $table->string('exercise_notes')->nullable();
            $table->decimal('sleep_hours', 3, 1)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'tracking_date']);
        });

        Schema::create('before_after_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->enum('photo_type', ['before', 'after', 'progress']);
            $table->string('file_path');
            $table->date('taken_at');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['subscription', 'weight', 'compliance', 'achievement', 'system']);
            $table->enum('priority', ['urgent', 'normal', 'low'])->default('normal');
            $table->string('title');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_sent_whatsapp')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('table_name');
            $table->uuid('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('center_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('center_name');
            $table->string('governorate');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('primary_color')->default('#1D6B45');
            $table->string('logo_path')->nullable();
            $table->json('working_hours')->nullable();
            $table->boolean('accept_new_members')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('before_after_photos');
        Schema::dropIfExists('daily_tracking');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('meal_plans');
        Schema::dropIfExists('weight_goals');
        Schema::dropIfExists('measurements');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('users');
    }
};
