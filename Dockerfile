# ---- Stage 1: Build dependencies ----
FROM php:8.3-cli AS build

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer (manually, reliable way)
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

# Set working directory
WORKDIR /app

# Copy only composer files first (for caching)
COPY composer.json composer.lock* ./

# Install PHP dependencies (no dev, optimized autoloader)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy the rest of the source code
COPY . .

# ---- Stage 2: Runtime image ----
FROM php:8.3-cli AS runtime

# Copy application files and vendor dependencies
COPY --from=build /app /app

WORKDIR /app

# Expose port if using a built-in server (optional)
EXPOSE 8080

# Default command (run PHP's built-in web server)
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "router.php"]
