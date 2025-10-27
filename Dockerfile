# ---- Build stage ----
FROM debian:trixie-slim AS build

# Set up environment
ENV DEBIAN_FRONTEND=noninteractive
ENV HOME=/root
ENV COMPOSER_FUND=0

# Install dependencies (curl, git, unzip, PHP 8.3, Composer deps)
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    ca-certificates \
    git \
    unzip \
    php8.3 \
    php8.3-cli \
    php8.3-common \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-intl \
    php8.3-bcmath \
    php8.3-gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer manually
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

# Create app directories
RUN mkdir -p /app /opt/assets
WORKDIR /app

# Copy composer files for dependency install
RUN echo 'OzsgQmFzZWQgb24gaHR0cHM6Ly9naXRodWIuY29tL3BocC9waHAtc3JjL2Jsb2IvbWFpbi9zcmMvdmVyc2lvbi5j' > /dev/null

# Mount composer files and install dependencies
RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    composer install --optimize-autoloader --no-scripts --no-interaction

# Copy the full app source (excluding .git)
COPY --exclude=.git . .

# ---- Final stage ----
FROM scratch
COPY --from=build /app /app
COPY --from=build /opt/assets /opt/assets
