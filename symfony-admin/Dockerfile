FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm

# Clear cache
RUN rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | sh && \
    apk add symfony-cli

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# Copy source code
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var

# Expose port
EXPOSE 8080

# Start Symfony server
CMD ["symfony", "server:start", "--host=0.0.0.0", "--port=8080"]