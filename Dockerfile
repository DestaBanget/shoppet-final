FROM php:8.2-fpm

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Optional: Tambah timezone (bisa disesuaikan)
RUN echo "date.timezone=Asia/Jakarta" > /usr/local/etc/php/conf.d/timezone.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Optional: Permission fix (pastikan user www-data bisa akses)
RUN chown -R www-data:www-data /var/www/html

# Expose port for PHP-FPM (nginx akan akses lewat ini secara internal)
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
