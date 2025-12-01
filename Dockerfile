FROM php:8.2-apache

# Carpeta donde vivirá tu app
WORKDIR /var/www/html

# Copia TODOS tus archivos al contenedor
COPY . .

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite si lo usas
RUN a2enmod rewrite

# Puerto del servidor
EXPOSE 80

CMD ["apache2-foreground"]
