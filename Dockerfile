FROM php:8.2-apache

# Dependencias de sistema + extensoes PHP exigidas pelo CodeIgniter 4
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install \
        intl \
        mbstring \
        mysqli \
        pdo_mysql \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Composer disponivel dentro do container (builds, installs e create-project)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache servindo a pasta public/ do CodeIgniter 4
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

# Evita o warning AH00558 (ServerName) nos logs do Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permite que o .htaccess do CI4 (mod_rewrite) seja processado
RUN sed -ri '/APACHE_DOCUMENT_ROOT/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

EXPOSE 80
