# syntax=docker/dockerfile:1
FROM php:8.3.36-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

ENV XDEBUG_MODE=off

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN --mount=type=secret,id=host-ca,target=/run/secrets/host-ca.pem,required=false \
    if [ -s /run/secrets/host-ca.pem ]; then \
        cat /etc/ssl/certs/ca-certificates.crt /run/secrets/host-ca.pem > /tmp/ca-certificates.crt && \
        COMPOSER_CAFILE=/tmp/ca-certificates.crt \
        SSL_CERT_FILE=/tmp/ca-certificates.crt \
        composer install --prefer-dist --no-interaction --no-progress && \
        rm /tmp/ca-certificates.crt; \
    else \
        composer install --prefer-dist --no-interaction --no-progress; \
    fi

CMD ["composer", "test"]
