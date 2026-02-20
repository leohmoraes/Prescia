FROM php:8.2-apache

# Install required PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set up required directories with proper permissions
RUN mkdir -p _temp/_logs _temp/_cache _temp/_backups \
    && chmod -R 775 _temp \
    && chown -R www-data:www-data _temp

# Copy configuration files if they don't exist
RUN if [ ! -f config/domains ]; then cp config/domains.original config/domains; fi \
    && if [ ! -f config/settings.php ]; then cp config/settings.php.original config/settings.php; fi

# Configure Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure PHP
RUN echo "short_open_tag = On" > /usr/local/etc/php/conf.d/short-open-tag.ini \
    && echo "date.timezone = America/Sao_Paulo" >> /usr/local/etc/php/conf.d/timezone.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
