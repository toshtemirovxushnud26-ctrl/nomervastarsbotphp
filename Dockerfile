FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3

RUN docker-php-ext-install pdo_sqlite

WORKDIR /app

COPY . .

CMD php -S 0.0.0.0:${PORT:-10000} bot-1.php
