FROM php:8.1-fpm

# Instalar dependências necessárias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_mysql

# Instalar Xdebug para cobertura de código
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configurar Xdebug para cobertura de código
RUN echo "zend_extension=xdebug.so" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.start_with_request=no" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.output_dir=/tmp" >> /usr/local/etc/php/conf.d/xdebug.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuração do diretório de trabalho
WORKDIR /var/www/html
