FROM alpine:3.8

RUN apk update
RUN apk add git \
        php7 \
        php7-xml \
        php7-openssl \
        php7-json \
        php7-phar \
        php7-iconv \
        php7-simplexml \
        php7-tokenizer \
        php7-xmlwriter \
        zip

CMD php -S 0.0.0.0:80