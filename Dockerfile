# PHP 8.2 with Apache (Laravel ke liye stable version)
FROM php:8.2-apache

# System dependencies install karein
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    curl \
    unzip \
    git

# PHP extensions (MySQL support ke liye)
RUN docker-php-ext-install pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Apache rewrite module enable karein (Laravel routes ke liye zaroori hai)
RUN a2enmod rewrite

# Apache ka document root 'public' folder par set karein
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Project files copy karein
WORKDIR /var/www/html
COPY . .

# Composer install karein
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Permissions set karein (Laravel storage ke liye)
RUN chown -R www-data:www-data storage bootstrap/cache

# Port 80 expose karein
EXPOSE 80

CMD ["apache2-foreground"]