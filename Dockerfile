FROM php:8.2-apache

# Habilitar mod_rewrite para el .htaccess
RUN a2enmod rewrite

# Instalar extensiones de MySQL necesarias para PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar todo el código a la carpeta pública de Apache
COPY . /var/www/html/

# Configurar Apache para que lea los archivos .htaccess (AllowOverride All)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html/
