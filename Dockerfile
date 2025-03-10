# Use the official PHP image with Apache (adjust the PHP version as needed)
FROM php:8.0-apache

# Enable Apache modules if needed (e.g., mod_rewrite)
RUN a2enmod rewrite

# Install any PHP extensions (for example, mysqli and pdo_mysql)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your application code into the container (this copies everything except files ignored by .dockerignore)
COPY . /var/www/html

# Set proper permissions if needed (optional)
# RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for the web server
EXPOSE 80
