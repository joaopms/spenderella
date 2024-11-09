# Heavily inspired by https://github.com/jdsantos/laravel-alpine-nginx-phpfpm-opcache-docker
FROM php:8.3-fpm-alpine

RUN apk --no-cache add \
    nginx \
    supervisor

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf

WORKDIR /var/www
RUN rm -r /var/www/*

EXPOSE 80

RUN echo "* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1" | crontab -

RUN mkdir -p /var/www/storage

ADD entrypoint.sh /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
