FROM ubuntu:latest AS base

ENV DEBIAN_FRONTEND noninteractive

# Install dependencies
RUN apt update && \
    apt install -y software-properties-common

# Install php dependencies
RUN add-apt-repository -y ppa:ondrej/php && \
    apt update && \
    apt install -y php8.2 \
    php8.2-cli \
    php8.2-common \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-pdo \
    php-pear

# Install pecl
RUN apt install -y php8.2-dev

# Install redis extension
RUN pecl install redis && \
    echo "extension=redis.so" > /etc/php/8.2/cli/conf.d/20-redis.ini && \
    echo "extension=redis.so" > /etc/php/8.2/fpm/conf.d/20-redis.ini

# Install php-fpm
RUN apt install -y php8.2-fpm php8.2-cli

# Install composer
RUN apt install -y curl && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install nodejs
RUN apt install -y ca-certificates gnupg && \
    mkdir -p /etc/apt/keyrings && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
ENV NODE_MAJOR 20
RUN echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list && \
    apt update && \
    apt install -y nodejs

# Install sockets extension
RUN apt install -y php8.2-sockets

# Install supervisor
RUN apt install -y supervisor

COPY . /var/www/html
COPY supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# ARG FIREBASE_SERVICE_JSON

# RUN echo ${FIREBASE_SERVICE_JSON} > /var/www/html/storage/credentials.json

# RUN chown -R www-data:www-data /var/www/html/storage

RUN composer install

RUN php artisan optimize:clear

# Expose port 5000
EXPOSE 5000

CMD ["supervisord", "-c", "/etc/supervisord.conf"]