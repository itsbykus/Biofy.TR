# CI only: a cPanel-like Apache + PHP 8.3 host that already runs WordPress in public_html.
# PHP runs as the account user (APACHE_RUN_USER=#uid), like suEXEC/LSAPI on shared hosting.
FROM wordpress:php8.3-apache
RUN docker-php-ext-install pdo_mysql
COPY vhosts.conf /etc/apache2/sites-enabled/010-accounts.conf
