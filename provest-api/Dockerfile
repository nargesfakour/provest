# =============================================================================
# Stage 1 — Composer dependencies
# Uses the official composer image to install production-only packages and
# generate an optimised classmap autoloader.
# This stage is discarded after COPY; vendor/ is the only output we keep.
# =============================================================================
FROM composer:2.8 AS vendor

WORKDIR /app

# Copy lockfiles first so this layer is cached until dependencies change.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-autoloader \
        --no-scripts \
        --prefer-dist

# Copy source so dump-autoload can find all classes.
COPY . .

RUN composer dump-autoload \
        --optimize \
        --classmap-authoritative \
        --no-dev

# =============================================================================
# Stage 2 — Production PHP-FPM image
# =============================================================================
FROM php:8.3-fpm-alpine

LABEL org.opencontainers.image.title="provest-laravel"
LABEL org.opencontainers.image.description="Provest API — PHP-FPM"

# ── System packages ──────────────────────────────────────────────────────────
# .build-deps are removed after ext compilation to keep the image lean.
# libpq stays because it is needed at runtime by pdo_pgsql.
RUN apk add --no-cache --virtual .build-deps \
        postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        bcmath \
        opcache \
    && apk del .build-deps \
    && apk add --no-cache libpq \
    && rm -rf /var/cache/apk/*

# ── PHP runtime configuration ─────────────────────────────────────────────
RUN { \
        echo 'display_errors=Off'; \
        echo 'display_startup_errors=Off'; \
        echo 'log_errors=On'; \
        echo 'error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
        echo 'memory_limit=256M'; \
        echo 'max_execution_time=300'; \
        echo 'max_input_time=300'; \
        echo 'post_max_size=20M'; \
        echo 'upload_max_filesize=20M'; \
        echo 'expose_php=Off'; \
        echo 'date.timezone=UTC'; \
    } > /usr/local/etc/php/conf.d/provest.ini

# ── OPcache configuration (production: no timestamp revalidation) ─────────
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.save_comments=1'; \
        echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# ── Application code ──────────────────────────────────────────────────────
# vendor/ comes from the build stage; everything else comes from source.
COPY --from=vendor /app/vendor ./vendor
COPY . .

# ── Storage directories and permissions ──────────────────────────────────
# Create all directories Laravel writes to and hand them to www-data.
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Run as non-root ──────────────────────────────────────────────────────
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
