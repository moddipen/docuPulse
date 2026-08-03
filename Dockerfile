FROM php:8.4-cli

# Tools + Postgres extension (you had these — keep)
RUN apt-get update && apt-get install -y unzip libpq-dev
RUN docker-php-ext-install pdo_pgsql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# NEW: copy your application code into the image
COPY . /var/www

# NEW: install PHP dependencies (production, no dev packages)
RUN composer install --no-dev --optimize-autoloader

# NEW: expose and start a web server on Railway's port
# Railway provides $PORT — the app MUST listen on it
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT