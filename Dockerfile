# Use official PHP 8.2 image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev zip libonig-dev \
    && docker-php-ext-install intl zip

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for caching)
COPY composer.json composer.lock ./

# Install Composer from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Run composer install (ignore platform reqs for Render)
RUN comRUN composer install -vvv --ignore-platform-reqs


# Now copy the rest of the application
COPY . .

# Configure Apache for public directory
RUN echo "<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/app.conf \
    && a2enconf app

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
