# PHP 8.4: latest security/patch release within the branch, Debian Bookworm base.
# Avoid an unreviewed jump to PHP 8.6, which is incompatible with some locked deps.
FROM php:8.4-cli-bookworm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install -j"$(nproc)" intl pcntl pdo pdo_pgsql pgsql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
