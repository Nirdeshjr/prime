# Use the official PHP-FPM image
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    zip \
    unzip \
    nginx \
    && docker-php-ext-install pdo_mysql mbstring xml zip gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy NGINX configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Set permissions for Laravel storage and bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy deploy script from root folder to container
COPY deploy.sh /usr/local/bin/deploy.sh
RUN chmod +x /var/www/html/deploy.sh && /var/www/html/deploy.sh

# Expose port 80 for NGINX
EXPOSE 80

# Start NGINX, PHP-FPM, and run deploy script
CMD ["sh", "-c", "/usr/local/bin/deploy.sh && service nginx start && php-fpm"]
