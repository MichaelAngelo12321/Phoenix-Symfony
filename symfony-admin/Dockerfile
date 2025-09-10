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
    icu-dev \
    libzip-dev

# Clear cache
RUN rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd intl zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application code first
COPY . .

# Install dependencies after copying all files
RUN composer install --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod +x /var/www/bin/console
RUN mkdir -p /var/www/var/cache /var/www/var/log
RUN chmod -R 777 /var/www/var

# Expose port
EXPOSE 8080

# Start PHP-FPM server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]