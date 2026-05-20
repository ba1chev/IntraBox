FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        postgresql-dev \
        git \
        unzip \
        oniguruma-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        mbstring \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN addgroup -g 1000 -S app && adduser -u 1000 -S app -G app
USER app

EXPOSE 9000
CMD ["php-fpm"]
