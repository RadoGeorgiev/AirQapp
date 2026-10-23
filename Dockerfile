FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts
COPY . .

FROM php:8.5-cli

RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/html
COPY --from=dependencies /app /var/www/html

EXPOSE 8000
