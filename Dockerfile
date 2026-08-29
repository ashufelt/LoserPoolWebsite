# Loser Pool: one PHP container, SQLite on a mounted volume, no database server.
FROM php:8.3-apache

# pdo_sqlite and sqlite3 ship enabled in the official image; opcache is worth
# turning on for a site that re-renders the same tables on every request.
RUN docker-php-ext-enable opcache \
 && printf '%s\n' \
      'opcache.enable=1' \
      'opcache.memory_consumption=64' \
      'opcache.max_accelerated_files=2000' \
      'opcache.validate_timestamps=0' \
    > /usr/local/etc/php/conf.d/opcache.ini

# date.timezone matters: the pool's deadline rules are day-of-week questions.
# The application always passes an explicit timezone, but a sane default keeps
# any stray date() call honest.
RUN printf '%s\n' 'date.timezone=America/Chicago' 'expose_php=Off' \
    > /usr/local/etc/php/conf.d/pool.ini

# The repo keeps the docroot in htdocs/. bin/ and the source live outside it.
COPY htdocs/ /var/www/html/
COPY bin/ /var/www/bin/

# Directory listing off, and the class/data directories closed to the web.
# Configured here rather than via .htaccess because AllowOverride is None in
# the base image, which would silently ignore the .htaccess protections.
RUN printf '%s\n' \
      '<Directory /var/www/html>' \
      '    Options -Indexes +FollowSymLinks' \
      '    AllowOverride None' \
      '    Require all granted' \
      '</Directory>' \
      '<Directory /var/www/html/src>' \
      '    Require all denied' \
      '</Directory>' \
      '<Directory /var/www/html/SqlAccess>' \
      '    Require all denied' \
      '</Directory>' \
      '<Directory /var/www/html/data>' \
      '    Require all denied' \
      '</Directory>' \
      'ServerTokens Prod' \
      'ServerSignature Off' \
    > /etc/apache2/conf-available/pool.conf \
 && a2enconf pool

# The ESPN response cache is written at runtime; /data is the mounted volume.
RUN mkdir -p /var/www/html/data/cache /data \
 && chown -R www-data:www-data /var/www/html/data /data

ENV LP_STORE=sqlite \
    LP_SQLITE_PATH=/data/pool.sqlite

EXPOSE 80
