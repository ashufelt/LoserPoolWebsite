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

# Only htdocs/ is served. Application code, schedule data and the CLI tools
# live beside the docroot, not inside it, so they are unreachable over HTTP by
# layout rather than by an Apache rule that could be misconfigured.
COPY htdocs/ /var/www/html/
COPY src/ /var/www/src/
COPY data/ /var/www/data/
COPY bin/ /var/www/bin/

# COPY preserves the source file modes, and a restrictive local umask (077)
# produces files Apache cannot read -- which fails as a blanket 403 with
# "Permission denied", not as a config error. Normalise instead of trusting
# whatever the build host happened to use.
RUN chmod -R a+rX /var/www/html /var/www/src /var/www/data /var/www/bin

# Directory listing off, and the class/data directories closed to the web.
# Configured here rather than via .htaccess because AllowOverride is None in
# the base image, which would silently ignore the .htaccess protections.
RUN printf '%s\n' \
      '<Directory /var/www/html>' \
      '    Options -Indexes +FollowSymLinks' \
      '    AllowOverride None' \
      '    Require all granted' \
      '</Directory>' \
      'ServerTokens Prod' \
      'ServerSignature Off' \
    > /etc/apache2/conf-available/pool.conf \
 && a2enconf pool

# The ESPN response cache is written at runtime, outside the docroot.
RUN mkdir -p /var/www/var/cache \
 && chown -R www-data:www-data /var/www/var

# /data is a mounted volume, and a volume's root belongs to root at mount time.
# Any ownership set here at build time is shadowed the moment it mounts, so the
# database directory has to be prepared when the container starts instead --
# otherwise Apache gets "unable to open database file" and nothing can be saved.
RUN printf '%s\n' \
      '#!/bin/sh' \
      'set -e' \
      'db_dir="$(dirname "${LP_SQLITE_PATH:-/data/pool.sqlite}")"' \
      'mkdir -p "$db_dir"' \
      'chown -R www-data:www-data "$db_dir"' \
      'exec "$@"' \
    > /usr/local/bin/pool-entrypoint.sh \
 && chmod +x /usr/local/bin/pool-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/pool-entrypoint.sh"]
CMD ["apache2-foreground"]

ENV LP_SQLITE_PATH=/data/pool.sqlite \
    LP_CACHE_DIR=/var/www/var/cache

EXPOSE 80
