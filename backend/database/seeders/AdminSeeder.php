<?php
// database/seeders/AdminSeeder.php
namespace Database\Seeders;

use App\Models\User;
use App\Models\CenterSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@nutri-iraq.iq'],
            [
                'name'        => 'أحمد العبيدي',
                'phone'       => '07901234567',
                'password'    => Hash::make('Admin@2026'),
                'role'        => 'admin',
                'governorate' => 'بغداد',
                'is_active'   => true,
            ]
        );

        CenterSetting::firstOrCreate(
            [],
            [
                'center_name'        => 'مركز نيوتري عراق للتغذية',
                'governorate'        => 'بغداد',
                'address'            => 'الكرادة، شارع السعدون، بناية 14',
                'phone'              => '07901234567',
                'email'              => 'info@nutri-iraq.iq',
                'primary_color'      => '#1D6B45',
                'accept_new_members' => true,
                'working_hours'      => [
                    'saturday_thursday' => '09:00-20:00',
                    'friday'            => 'closed',
                ],
            ]
        );

        $this->command->info('✅ تم إنشاء حساب المدير وإعدادات المركز');
    }
}
