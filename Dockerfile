FROM php:8.0-fpm

# # Copy composer.lock and composer.json into the working directory
# COPY composer.lock composer.json /var/www/html/

RUN apt update \
    && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

# Set working directory
WORKDIR /var/www/html/

# # Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# # Install the Symfony CLI
# RUN curl -sS https://get.symfony.com/cli/installer | bash
# RUN mv /root/.symfony/bin/symfony /usr/local/bin/symfony
# RUN git config --global user.email "you@example.com" \
#     && git config --global user.name "Your Name"

# Update system core
#Lib PDO
RUN apt-get update \
  && apt-get install -y --no-install-recommends libpq-dev \
  && docker-php-ext-install mysqli pdo_pgsql pdo_mysql

RUN apt update && apt install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev libxml2-dev libcurl4-gnutls-dev

#Lib mysqli
# RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli


# Copy existing application directory contents to the working directory
COPY . /var/www/html

# Start PHP-FPM
EXPOSE 9000
CMD ["php-fpm"]