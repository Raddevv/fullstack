# Use PHP with Apache
FROM php:8.2-apache

# Install required extensions for MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# (Optional) Enable Apache mod_rewrite if you need clean URLs
RUN a2enmod rewrite
