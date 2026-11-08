FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libzip-dev libssl-dev \
    && docker-php-ext-install pdo_mysql zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Le DocumentRoot pointe sur public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
      /etc/apache2/sites-available/*.conf \
      /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN mkdir -p /home/vite-et-gourmand-ecf/admin/tmp \
    && chown -R www-data:www-data /home/vite-et-gourmand-ecf
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo "variables_order = EGPCS" > "$PHP_INI_DIR/conf.d/zz-variables-order.ini"
ENV PHPRC=$PHP_INI_DIR
WORKDIR /var/www/html