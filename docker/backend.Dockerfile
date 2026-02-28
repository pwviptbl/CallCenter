FROM php:8.3-fpm

# Argumentos
ARG user=callcenter
ARG uid=1000

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário do sistema
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar aplicação
COPY . /var/www

# Ajustar permissões
RUN chown -R $user:$user /var/www

# Mudar para usuário criado
USER $user

# Expor porta
EXPOSE 9000

CMD ["php-fpm"]
