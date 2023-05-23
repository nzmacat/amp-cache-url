# Use the official Alpine Linux base image with PHP 8
FROM php:8-alpine

# Install required system dependencies
RUN apk --no-cache add \
    icu-dev \
    libzip-dev \
    zlib-dev \
    && docker-php-ext-install \
    intl \
    zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHPUnit
RUN wget -O /usr/local/bin/phpunit https://phar.phpunit.de/phpunit.phar && chmod +x /usr/local/bin/phpunit

# Install Xdebug dependencies
RUN apk add --update linux-headers

# Install Xdebug
RUN yes | apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Set the working directory to /app
WORKDIR /app

# Copy the application code to the container
COPY . /app

# Install the project dependencies with Composer
RUN composer install --no-interaction --no-suggest --optimize-autoloader

ENV XDEBUG_MODE=coverage

# Run PHPUnit tests
CMD ["phpunit", "--configuration", "phpunit.xml", "--coverage-text"]
