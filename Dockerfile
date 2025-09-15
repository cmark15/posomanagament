# Use the official PHP image with CLI and built-in server
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies without scripts first
RUN composer install --no-dev --no-scripts --ignore-platform-reqs

# Copy the rest of the application
COPY . .

# Create necessary directories and files
RUN mkdir -p database storage/logs && \
    cp .env.example .env && \
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env && \
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env && \
    touch database/database.sqlite

# Generate app key and complete setup
RUN php artisan key:generate --force && \
    composer dump-autoload --optimize && \
    php artisan package:discover --ansi

# Install npm dependencies and build assets
RUN npm ci && npm run build

# Set permissions
RUN chmod -R 755 storage bootstrap/cache && \
    chmod 664 database/database.sqlite

# Expose port (Railway will set the PORT environment variable)
EXPOSE $PORT

# Start the application
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
