FROM php:8.5-apache

# Install PHP extensions Laravel requires (plus zip for composer).
RUN apt-get update && apt-get install -y libzip-dev libonig-dev libxml2-dev libcurl4-openssl-dev zip unzip git && \
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

# Point Apache's DocumentRoot at Laravel's public directory and allow .htaccess overrides
# (the AllowOverride directive lives in the main apache2.conf <Directory /var/www/> block).
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#<Directory /var/www/>#<Directory /var/www/html/public>#' /etc/apache2/apache2.conf \
    && sed -i 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

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
