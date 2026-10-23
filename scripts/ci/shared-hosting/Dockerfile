# CI only: a cPanel-like Apache + PHP 8.3 host that already runs WordPress in public_html.
# PHP runs as the hosting account user (same uid as the FTP user), like suEXEC/LSAPI on shared hosting.
FROM wordpress:php8.3-apache
ARG ACCOUNT_UID=1001
ARG ACCOUNT_GID=1001
RUN docker-php-ext-install pdo_mysql \
    && groupadd -o -g "$ACCOUNT_GID" account \
    && useradd -o -u "$ACCOUNT_UID" -g account -M -d /home/user -s /usr/sbin/nologin account
ENV APACHE_RUN_USER=account \
    APACHE_RUN_GROUP=account
COPY vhosts.conf /etc/apache2/sites-enabled/010-accounts.conf
