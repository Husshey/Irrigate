FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    software-properties-common \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y \
    php8.3 \
    libapache2-mod-php8.3 \
    php8.3-mysqli \
    php8.3-zip \
    php8.3-xml \
    && rm -rf /var/lib/apt/lists/*

RUN rm -rf /var/www/html/*

COPY . /var/www/html/

RUN rm -f /var/www/html/index.html

RUN chown -R www-data:www-data /var/www/html && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf && \
    a2enmod php8.3

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]