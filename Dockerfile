# =============================================================================
# Clínica Vida Salud – imagen para producción (Render, Railway o cualquier servidor con Docker)
# Apache + PHP 8.3 + pdo_pgsql. Sirve backend/public; las vistas y assets se leen de frontend/.
# =============================================================================
FROM php:8.3-apache

# Extensiones: PostgreSQL (pdo_pgsql), zip para Composer; opcache para rendimiento
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip git \
 && docker-php-ext-install pdo_pgsql pgsql zip opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app
# Dependencias primero (aprovecha la caché de Docker)
COPY backend/composer.json backend/composer.lock backend/
RUN cd backend && composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Código del proyecto (backend + frontend)
COPY . .
RUN cd backend && composer dump-autoload --optimize --no-dev \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private \
 && chown -R www-data:www-data storage bootstrap/cache

# Apache: el sitio apunta a backend/public y escucha en el puerto que indique la plataforma ($PORT)
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/clinica.ini
COPY deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=10000 APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
