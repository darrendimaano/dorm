# Use official PHP 8 image with Apache
FROM php:8.1-apache

# Install system dependencies and PHP extensions required by the app
RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
        zip \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules commonly needed by PHP frameworks
RUN a2enmod rewrite

# Copy Composer from the official Composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Copy application source
COPY . .

# Ensure storage/runtime directories are writable (if needed by the app)
RUN chown -R www-data:www-data runtime public/uploads

# Expose Apache port (Render will map this automatically)
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
