#!/bin/sh
# Arranque del contenedor: configura Apache con $PORT, prepara Laravel, migra la base de datos,
# carga los datos de demostración la primera vez y deja corriendo la tarea programada (recordatorios).
set -e
cd /var/www/app/backend

# Apache escucha en el puerto que asigna la plataforma
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Laravel necesita APP_KEY con el formato "base64:<32 bytes>". Si la plataforma entrega un texto
# aleatorio (Render: generateValue), se deriva de él una clave válida y siempre la misma.
case "$APP_KEY" in
  base64:*) ;;
  "") echo "Falta APP_KEY: defina la variable de entorno" >&2; exit 1 ;;
  *) export APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("APP_KEY"), true));')" ;;
esac

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migraciones (crea o actualiza las tablas) y datos de demostración si la base está vacía
php artisan migrate --force
USUARIOS=$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo App\Models\P1_SeguridadAcceso\User::count();')
if [ "$USUARIOS" = "0" ] && [ "${SEED_DEMO:-true}" = "true" ]; then
  echo "Base de datos vacía: cargando datos de demostración"
  php artisan db:seed --force
fi

chown -R www-data:www-data storage bootstrap/cache

# CU22: envío de recordatorios programados mientras el servicio está activo
su -s /bin/sh www-data -c "php artisan schedule:work" > /dev/stdout 2>&1 &

exec apache2-foreground
