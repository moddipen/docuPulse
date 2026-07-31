FROM php:8.4-cli

# Tools Composer needs to unpack packages
RUN apt-get update && apt-get install -y unzip libpq-dev

# The extension PHP needs to talk to Postgres
RUN docker-php-ext-install pdo_pgsql

# Composer itself, baked in permanently
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www