FROM debian:trixie-slim AS build

RUN apt-get update && apt-get install -y php-cli unzip curl git

# Install Composer manually (stable, reliable)
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

WORKDIR /app
COPY --exclude=.git . .

RUN composer install --no-dev --optimize-autoloader

FROM scratch
COPY --from=build /app /app
COPY --from=build /opt/assets /opt/assets
