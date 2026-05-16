// src/types/index.ts

export type Role = 'admin' | 'trainer' | 'reception'
export type Gender = 'male' | 'female'
export type Goal = 'lose' | 'gain' | 'muscle' | 'health'
export type ActivityLevel = 'sedentary' | 'light' | 'moderate' | 'active' | 'athlete'
export type SubscriptionPackage = '1m' | '3m' | '6m'
export type SubscriptionStatus = 'active' | 'expired' | 'cancelled'
export type PaymentMethod = 'cash' | 'zain_cash' | 'asia' | 'bank'
export type MealType = 'breakfast' | 'snack1' | 'lunch' | 'snack2' | 'dinner'
export type NotifType = 'subscription' | 'weight' | 'compliance' | 'achievement' | 'system'
export type NotifPriority = 'urgent' | 'normal' | 'low'

// ── Auth ──────────────────────────────────────────
export interface AuthUser {
  id: string
  name: string
  email: string
  phone: string
  role: Role
  governorate: string
}

export interface LoginPayload {
  email: string
  password: string
}

// ── User ──────────────────────────────────────────
export interface User {
  id: string
  name: string
  email: string
  phone: string
  role: Role
  governorate: string
  is_active: boolean
  last_login: string | null
  created_at: string
}

// ── Member ────────────────────────────────────────
export interface Member {
  id: string
  assigned_trainer_id: string | null
  first_name: string
  last_name: string
  full_name: string
  phone: string
  email: string | null
  birth_date: string | null
  age: number
  gender: Gender
  governorate: string
  goal: Goal
  activity_level: ActivityLevel
  health_condition: string | null
  allergies: string | null
  referral_source: string | null
  is_active: boolean
  created_at: string
  trainer?: Pick<User, 'id' | 'name'>
  latest_measurement?: Measurement
  active_subscription?: Subscription
  weight_goal?: WeightGoal
}

export interface CreateMemberPayload {
  first_name: string
  last_name: string
  phone: string
  email?: string
  birth_date?: string
  gender: Gender
  governorate: string
  goal: Goal
  activity_level?: ActivityLevel
  health_condition?: string
  allergies?: string
  referral_source?: string
  assigned_trainer_id?: string
  current_weight_kg?: number
  target_weight_kg?: number
}

// ── Subscription ──────────────────────────────────
export interface Subscription {
  id: string
  member_id: string
  trainer_id: string | null
  package: SubscriptionPackage
  price_iqd: number
  start_date: string
  end_date: string
  payment_method: PaymentMethod
  status: SubscriptionStatus
  notes: string | null
  days_remaining: number
  is_expiring_soon: boolean
  created_at: string
  member?: Pick<Member, 'id' | 'first_name' | 'last_name' | 'full_name' | 'phone' | 'governorate'>
}

export interface CreateSubscriptionPayload {
  member_id: string
  trainer_id?: string
  package: SubscriptionPackage
  price_iqd: number
  start_date: string
  payment_method: PaymentMethod
  notes?: string
}

// ── Measurement ───────────────────────────────────
export interface Measurement {
  id: string
  member_id: string
  weight_kg: number
  height_cm: number | null
  bmi: number | null
  bmi_category: string
  body_fat_pct: number | null
  waist_cm: number | null
  muscle_mass_pct: number | null
  measured_at: string
  notes: string | null
}

export interface MeasurementChartData {
  labels: string[]
  weight: number[]
  bmi: (number | null)[]
  body_fat: (number | null)[]
  waist: (number | null)[]
}

// ── WeightGoal ────────────────────────────────────
export interface WeightGoal {
  id: string
  member_id: string
  current_weight_kg: number
  target_weight_kg: number
  progress_pct: number
  target_date: string | null
}

// ── MealPlan ──────────────────────────────────────
export interface Meal {
  id: string
  meal_plan_id: string
  meal_type: MealType
  meal_type_arabic: string
  scheduled_time: string | null
  calories: number
  description: string
  day_number: number
}

export interface MealPlan {
  id: string
  member_id: string
  created_by: string | null
  total_calories: number
  protein_g: number
  carbs_g: number
  fat_g: number
  goal: Goal
  duration_days: number
  is_active: boolean
  created_at: string
  updated_at: string
  meals?: Meal[]
  member?: Pick<Member, 'id' | 'first_name' | 'last_name' | 'full_name'>
}

// ── DailyTracking ─────────────────────────────────
export interface DailyTracking {
  id: string
  member_id: string
  tracking_date: string
  diet_followed: boolean
  water_liters: number
  steps_count: number
  exercise_notes: string | null
  sleep_hours: number
  notes: string | null
}

// ── Notification ──────────────────────────────────
export interface Notification {
  id: string
  user_id: string | null
  member_id: string | null
  type: NotifType
  priority: NotifPriority
  title: string
  body: string
  is_read: boolean
  is_sent_whatsapp: boolean
  read_at: string | null
  created_at: string
  member?: Pick<Member, 'id' | 'full_name'>
}

// ── Dashboard ─────────────────────────────────────
export interface DashboardStats {
  total_members: number
  active_members: number
  new_members_this_month: number
  active_subscriptions: number
  expiring_soon: number
  revenue_this_month_iqd: number
  total_weight_lost_kg: number
  compliance_rate: number
}

// ── Pagination ────────────────────────────────────
export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    total: number
    page: number
    last_page: number
    per_page: number
  }
}

// ── API Error ─────────────────────────────────────
export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}
