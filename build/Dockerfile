FROM alpine:3.8

RUN apk update && apk add git \
        php7 \
        php7-xml \
        php7-openssl \
        php7-json \
        php7-phar \
        php7-iconv \
        php7-simplexml \
        php7-tokenizer \
        php7-xmlwriter \
        php7-dom \
        php7-imagick \
        zip

CMD php -S 0.0.0.0:80
