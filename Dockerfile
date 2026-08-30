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
#
# The Cache-Control block is the reason mod_headers is enabled. style.css and
# teampicker.js are served under fixed names with no fingerprint, so a browser
# left to its own heuristics will happily keep a copy from before a deploy for
# days. That is not a cosmetic problem here: the picker's popup is hidden by a
# CSS rule, so a stale stylesheet leaves a dropdown that will not close and
# looks like a broken control rather than an old file. "no-cache" still caches
# and still answers 304; it only stops the browser skipping the check.
#
# Guarded with IfModule despite a2enmod above: an unrecognised Header directive
# stops Apache from starting at all, and a site that will not boot is a worse
# outcome than one serving a stale stylesheet.
RUN a2enmod headers \
 && printf '%s\n' \
      '<Directory /var/www/html>' \
      '    Options -Indexes +FollowSymLinks' \
      '    AllowOverride None' \
      '    Require all granted' \
      '</Directory>' \
      '<IfModule mod_headers.c>' \
      '    <FilesMatch "\.(css|js)$">' \
      '        Header set Cache-Control "no-cache"' \
      '    </FilesMatch>' \
      '</IfModule>' \
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
