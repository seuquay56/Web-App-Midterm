# Starting from a base image to be used for this image
# Taken from https://hub.docker.com/layers/library/php/8.5-cli/images/sha256-09d20dfc7c29dda284b6cf3ed7c2eef13b95cb63230bf873b2fa9278c6072d6f
FROM  php:8.5-cli 

# Then install dependencies for this image
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip \
    # docker-php-ext-install, is a helper script that comes from the base image,
    # it buils, and enable php extension.
    # pdo_mysql is the database driver used to talk to the database
    && docker-php-ext-install pdo_mysql \
    # Installation of redis.
    && pecl install redis && docker-php-ext-enable redis

# Copy composer from the official image, and move it over to this image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set the default working directory inside the image
WORKDIR /app
# Copy all files in the build context(items next to Dockerfile) to the image
COPY . .

# Install PHP dependencies
RUN composer install
#Documentation only
EXPOSE 8000
# Command to start when the container starts
CMD ["sh","-c", "php artisan migrate --force && exec php artisan serve --host=0.0.0.0 --port=8000"]
