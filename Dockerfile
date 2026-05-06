FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    libapache2-mod-php8.1 \
    php8.1-mysqli \
    && rm -rf /var/lib/apt/lists/*

RUN rm -rf /var/www/html/*

COPY . /var/www/html/

# This will print files during build so we can see what's there
RUN ls -la /var/www/html/

RUN chown -R www-data:www-data /var/www/html && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    a2enmod php8.1

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]