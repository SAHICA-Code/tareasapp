# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# Copia todos los archivos del proyecto dentro del servidor
COPY . /var/www/html/

# Habilita el módulo PDO MySQL para conectar con la base de datos
RUN docker-php-ext-install pdo pdo_mysql

# Expón el puerto 80 para Render
EXPOSE 80
