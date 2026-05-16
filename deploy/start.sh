#!/bin/sh
# docker/backend/start.sh

set -e

echo "🚀 بدء تشغيل نيوتري عراق Backend..."

# انتظار قاعدة البيانات
until php artisan migrate:status > /dev/null 2>&1; do
    echo "⏳ انتظار قاعدة البيانات..."
    sleep 3
done

echo "✅ قاعدة البيانات جاهزة"

# تشغيل الـ migrations
php artisan migrate --force --no-interaction
echo "✅ Migrations تمت"

# تشغيل الـ seeders في أول مرة فقط
if ! php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | grep -q "^[1-9]"; then
    php artisan db:seed --class=AdminSeeder --force --no-interaction
    echo "✅ Seeding تم"
fi

# تنظيف الـ cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Cache تم تحديثه"

# تشغيل Supervisor (PHP-FPM + Nginx)
exec /usr/bin/supervisord -c /etc/supervisord.conf
