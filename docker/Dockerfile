FROM php:8.3-fpm-alpine

# Instalar dependências do sistema
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    libxslt-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    nodejs \
    npm \
    icu-dev

# Instalar extensões PHP
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    opcache \
    xml \
    dom \
    intl

# Instalar Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar working directory
WORKDIR /var/www

# Permissões base (resto será feito em runtime)
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www

# Expor porta 9000 (PHP-FPM)
EXPOSE 9000

CMD ["php-fpm"]
