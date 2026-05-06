FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    libapache2-mod-php8.1 \
    php8.1-mysqli \
    && rm -rf /var/lib/apt/lists/*

RUN rm /var/www/html/index.html
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Replace the hardcoded port with the Railway PORT env variable
CMD bash -c "sed -i 's/Listen 80/Listen ${PORT:-80}/' /etc/apache2/ports.conf && \
    sed -i 's/:80>/:${PORT:-80}>/' /etc/apache2/sites-enabled/000-default.conf && \
    apache2ctl -D FOREGROUND"