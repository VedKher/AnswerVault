# Use official PHP + Apache image
FROM php:8.2-apache

# Enable Apache mod_rewrite (needed for .htaccess rules)
RUN a2enmod rewrite

# Copy project files into the web root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Optional: install Composer if you plan to use it
RUN apt-get update && apt-get install -y unzip git \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Expose port 80 for Render
EXPOSE 80
