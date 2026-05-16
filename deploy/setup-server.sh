#!/bin/bash
# scripts/setup-server.sh
# إعداد خادم Ubuntu 22.04 LTS من الصفر
# شغّله مرة واحدة فقط: sudo bash setup-server.sh

set -euo pipefail

echo "═══════════════════════════════════════"
echo "  إعداد خادم نيوتري عراق"
echo "═══════════════════════════════════════"

# ── 1. تحديث النظام ───────────────────────
echo "📦 تحديث النظام..."
apt-get update -qq && apt-get upgrade -y -qq

# ── 2. تثبيت الأدوات الأساسية ─────────────
echo "🔧 تثبيت الأدوات..."
apt-get install -y -qq \
    curl wget git unzip \
    ufw fail2ban \
    certbot python3-certbot-nginx

# ── 3. تثبيت Docker ───────────────────────
echo "🐳 تثبيت Docker..."
curl -fsSL https://get.docker.com | sh
systemctl enable docker
systemctl start docker

# Docker Compose plugin
apt-get install -y docker-compose-plugin

# ── 4. إعداد Firewall ──────────────────────
echo "🔒 إعداد جدار الحماية..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# ── 5. إعداد Fail2ban ─────────────────────
echo "🛡️ إعداد Fail2ban..."
systemctl enable fail2ban
systemctl start fail2ban

# ── 6. إنشاء مستخدم النظام ───────────────
echo "👤 إنشاء مستخدم nutri..."
if ! id "nutri" &>/dev/null; then
    useradd -m -s /bin/bash nutri
    usermod -aG docker nutri
    echo "nutri ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers.d/nutri
fi

# ── 7. إنشاء مجلد المشروع ─────────────────
echo "📁 إنشاء مجلد المشروع..."
mkdir -p /opt/nutri-iraq
chown nutri:nutri /opt/nutri-iraq

# ── 8. إعداد SSL (Certbot) ────────────────
echo "🔐 إعداد SSL..."
echo "شغّل هذا الأمر لاحقاً بعد ربط الدومين:"
echo ""
echo "  certbot certonly --standalone \\"
echo "    -d nutri-iraq.iq \\"
echo "    -d www.nutri-iraq.iq \\"
echo "    -d api.nutri-iraq.iq \\"
echo "    --email info@nutri-iraq.iq \\"
echo "    --agree-tos --no-eff-email"
echo ""

# ── 9. Auto-renewal للـ SSL ───────────────
(crontab -l 2>/dev/null; echo "0 0 * * * certbot renew --quiet && docker exec nutri_nginx nginx -s reload") | crontab -

# ── 10. إعداد نسخ احتياطي تلقائي ─────────
echo "💾 إعداد نسخ احتياطي..."
cat > /opt/nutri-backup.sh << 'BACKUP'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups/nutri-iraq"
mkdir -p $BACKUP_DIR

# نسخ قاعدة البيانات
docker exec nutri_db pg_dump -U nutri_user nutri_iraq \
    | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# نسخ ملفات التخزين
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" \
    /var/lib/docker/volumes/nutri-iraq-deploy_backend_storage

# حذف النسخ أقدم من 30 يوم
find $BACKUP_DIR -type f -mtime +30 -delete

echo "✅ نسخة احتياطية: $DATE"
BACKUP

chmod +x /opt/nutri-backup.sh
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/nutri-backup.sh >> /var/log/nutri-backup.log 2>&1") | crontab -

echo ""
echo "═══════════════════════════════════════"
echo "✅ إعداد الخادم اكتمل!"
echo ""
echo "الخطوات التالية:"
echo "1. اربط الدومين nutri-iraq.iq بـ IP الخادم"
echo "2. شغّل أمر Certbot أعلاه"
echo "3. انسخ المشروع: git clone ... /opt/nutri-iraq"
echo "4. أنشئ ملف .env من .env.production"
echo "5. شغّل: cd /opt/nutri-iraq && docker compose up -d"
echo "═══════════════════════════════════════"
