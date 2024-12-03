FROM php:8.1-fpm

# Instalar dependências do sistema
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
    netcat-openbsd \
    && docker-php-ext-install pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Xdebug para cobertura de código
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Verifique se o arquivo de configuração do Xdebug não está sendo carregado duas vezes
RUN if [ ! -f /usr/local/etc/php/conf.d/xdebug.ini ]; then \
      echo "zend_extension=xdebug.so" > /usr/local/etc/php/conf.d/xdebug.ini && \
      echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/xdebug.ini && \
      echo "xdebug.start_with_request=no" >> /usr/local/etc/php/conf.d/xdebug.ini && \
      echo "xdebug.output_dir=/tmp" >> /usr/local/etc/php/conf.d/xdebug.ini; \
    fi


# Configuração do diretório de trabalho
WORKDIR /var/www/html

# Copiar os arquivos do projeto
COPY . .

# Adicionar o script de entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Tornar o script executável
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expor a porta 8000
EXPOSE 8000

# Usar o script como ponto de entrada
ENTRYPOINT ["docker-entrypoint.sh"]