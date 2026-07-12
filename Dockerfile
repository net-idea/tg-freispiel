FROM php:8.4-fpm-alpine3.24 AS php-base

RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    oniguruma-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM php-base AS dev

COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader

RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log && \
    chown -R www-data:www-data /var/www/html/var && \
    chmod -R 775 /var/www/html/var

FROM node:24-alpine3.24 AS assets

WORKDIR /app

RUN corepack enable && corepack prepare yarn@1.22.22 --activate

COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile

COPY . .
RUN yarn build

FROM php-base AS prod

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative && \
    mkdir -p /var/www/html/var/cache /var/www/html/var/log && \
    chown -R www-data:www-data /var/www/html/var && \
    chmod -R 775 /var/www/html/var

EXPOSE 9000

CMD ["php-fpm"]
