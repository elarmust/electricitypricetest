FROM php:8.5-apache

# Install PHP extensions Laravel requires (plus zip for composer).
RUN apt-get update && apt-get install -y libzip-dev zip unzip git && \
    docker-php-ext-install mbstring xml bcmath curl zip && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for Laravel's public/.htaccess.
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy application files (vendor/ and .env are excluded via .dockerignore).
COPY . .

# Make storage and bootstrap/cache writable by the web server.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Point Apache's DocumentRoot at Laravel's public directory.
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP runtime settings (Estonian timezone for price windows).
RUN echo "display_errors=Off" >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo "error_log=/dev/stderr" >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini \
    && echo "date.timezone=Europe/Tallinn" >> /usr/local/etc/php/conf.d/docker-php-ext-custom.ini

# Install Composer and project dependencies.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Create .env from the example and generate an application key.
RUN if [ ! -f .env ]; then cp .env.example .env; fi \
    && php artisan key:generate --force

EXPOSE 80
