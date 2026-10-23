#!/usr/bin/env bash
# Shared WordPress hosting test: an Apache + PHP 8.3 + MariaDB 10.6 "hosting account" that already
# runs WordPress in public_html. The release ZIP is uploaded over FTP (no shell on the "server"),
# installed through the web installer in three layouts, then checked next to WordPress.
#   A: bio.example.test  -> /home/user/biofy/public            (app outside public_html, recommended)
#   B: bio3.example.test -> /home/user2/bio3.example.test      (only public files there, app in ../biofy)
#   C: bio2.example.test -> /home/user/public_html/bio2        (whole package inside WordPress' public_html)
# Requires: docker, python3, lftp, unzip, a built dist/biofy-tr-*.zip.
set -euo pipefail
cd "$(dirname "$0")/../.."
source scripts/ci/lib.sh

ZIP=$(ls dist/biofy-tr-*.zip | head -1)
HOSTING="$WORK/hosting"
RUN_UID=$(id -u); RUN_GID=$(id -g)
ADMIN_PW="Ci$(openssl rand -hex 8)9x"
mkdir -p "$HOSTING/user/public_html" "$HOSTING/user2"

on_exit() {
    local code=$?
    if [ "$code" -ne 0 ] || [ "$FAILURES" -ne 0 ]; then
        echo "---- hosting logs ----"; docker logs --tail 100 hosting 2>&1 || true
        for log in "$HOSTING"/user/biofy/storage/logs/*.log "$HOSTING"/user2/biofy/storage/logs/*.log "$HOSTING"/user/public_html/bio2/storage/logs/*.log; do
            [ -f "$log" ] && { echo "---- $log"; tail -40 "$log"; }
        done
    fi
}
trap on_exit EXIT

section "Hosting server: MariaDB 10.6 + Apache/PHP 8.3 + WordPress"
docker network create hostnet >/dev/null
docker run -d --name db --network hostnet -e MARIADB_ROOT_PASSWORD=rootpw mariadb:10.6 >/dev/null
# TCP, not the socket: the image's temporary init server only listens on the socket.
for _ in $(seq 1 60); do docker exec db mariadb -h127.0.0.1 -uroot -prootpw -e 'SELECT 1' >/dev/null 2>&1 && break; sleep 2; done
docker exec -i db mariadb -h127.0.0.1 -uroot -prootpw <<'SQL'
CREATE DATABASE wordpress; CREATE USER 'wp'@'%' IDENTIFIED BY 'wp-pass-123'; GRANT ALL ON wordpress.* TO 'wp'@'%';
CREATE DATABASE user_biofya; CREATE USER 'user_a'@'%' IDENTIFIED BY 'a-pass-123'; GRANT ALL ON user_biofya.* TO 'user_a'@'%';
CREATE DATABASE user2_biofyb; CREATE USER 'user2_b'@'%' IDENTIFIED BY 'b-pass-123'; GRANT ALL ON user2_biofyb.* TO 'user2_b'@'%';
CREATE DATABASE user_biofyc; CREATE USER 'user_c'@'%' IDENTIFIED BY 'c-pass-123'; GRANT ALL ON user_biofyc.* TO 'user_c'@'%';
SQL
docker build -q -t biofy-ci-hosting scripts/ci/shared-hosting >/dev/null
docker run -d --name hosting --network hostnet -p 80:80 \
    -e APACHE_RUN_USER="#$RUN_UID" -e APACHE_RUN_GROUP="#$RUN_GID" \
    -e WORDPRESS_DB_HOST=db -e WORDPRESS_DB_USER=wp -e WORDPRESS_DB_PASSWORD=wp-pass-123 -e WORDPRESS_DB_NAME=wordpress \
    -v "$HOSTING/user:/home/user" -v "$HOSTING/user/public_html:/var/www/html" -v "$HOSTING/user2:/home/user2" \
    biofy-ci-hosting >/dev/null
for _ in $(seq 1 60); do [ -f "$HOSTING/user/public_html/wp-config.php" ] && break; sleep 2; done
sleep 3
echo "  PHP runs as: $(docker exec hosting sh -c 'echo "$APACHE_RUN_USER"') (runner uid $RUN_UID); wp-config.php owner: $(stat -c %u "$HOSTING/user/public_html/wp-config.php")"

CURL_OPTS=(--resolve example.test:80:127.0.0.1)
curl "${CURL_OPTS[@]}" -s -o /dev/null --data-urlencode "weblog_title=CI Blog" --data "user_name=wpadmin&admin_password=Wp-Pass-12345&admin_password2=Wp-Pass-12345&pw_weak=1&admin_email=wp@example.com&blog_public=0" \
    "http://example.test/wp-admin/install.php?step=2"
body_contains "WordPress is installed and running" "http://example.test/" "CI Blog"
[ -f "$HOSTING/user/public_html/.htaccess" ] && pass "WordPress .htaccess present in public_html" || fail "WordPress .htaccess missing"

section "Upload the release over FTP"
python3 -m venv "$WORK/ftpenv" && "$WORK/ftpenv/bin/pip" install -q pyftpdlib
# Typical FTP server defaults: files 644, folders 755.
(umask 022; exec "$WORK/ftpenv/bin/python" -m pyftpdlib -i 127.0.0.1 -p 2121 -u ftpuser -P ftppass -d "$HOSTING" -w >"$WORK/ftp.log" 2>&1) &
FTP_PID=$!
sleep 2
unzip -q "$ZIP" -d "$WORK/release"
REL=$(ls -d "$WORK"/release/biofy-tr-*)
ftp_mirror() {
    lftp -u ftpuser,ftppass -p 2121 127.0.0.1 -e "set ftp:ssl-allow no; set ftp:passive-mode on; set net:max-retries 2; mirror --reverse --no-perms --parallel=8 '$1' '$2'; bye"
}
ftp_mirror "$REL" user/biofy                          # A
ftp_mirror "$REL" user2/biofy                         # B (application)
ftp_mirror "$REL/public" user2/bio3.example.test      # B (public files only)
ftp_mirror "$REL" user/public_html/bio2               # C
kill "$FTP_PID" || true
expected=$(cd "$REL" && find . -type f | wc -l)
for dir in user/biofy user2/biofy user/public_html/bio2; do
    expect_eq "files uploaded to $dir" "$(cd "$HOSTING/$dir" && find . -type f | wc -l)" "$expected"
done
[ -f "$HOSTING/user/public_html/bio2/.htaccess" ] && pass "dotfiles (.htaccess) uploaded" || fail ".htaccess not uploaded"
ls "$HOSTING/user/biofy/vendor/autoload.php" "$HOSTING/user/biofy/public/build/manifest.json" >/dev/null && pass "vendor/ and public/build/ shipped (no Composer/Node on the server)" || fail "package incomplete"

# run_installer HOST DB USER PASS APP_DIR
run_installer() {
    local host=$1 base="http://$1" jar="$WORK/install-$1" page="$WORK/page-$1.html" token
    CURL_OPTS=(--resolve "$host:80:127.0.0.1")
    get() { curl "${CURL_OPTS[@]}" -sS -b "$jar" -c "$jar" -o "$page" "$base/?lang=en"; if grep -q 'class="err"' "$page"; then fail "$host installer error: $(grep -o 'class="err".*' "$page" | head -c 400)"; fi; }
    post() { curl "${CURL_OPTS[@]}" -sS -b "$jar" -c "$jar" -o "$WORK/post-$host.html" -w '%{http_code}' --data-urlencode "_token=$token" "$@" "$base/?lang=en"; }

    get
    token=$(grep -o 'name="_token" value="[^"]*"' "$page" | head -1 | sed 's/.*value="//; s/"$//')
    grep -q 'class="bad"' "$page" && fail "$host: a required check failed" || pass "$host: requirements met"
    post --data action=requirements >/dev/null; get
    grep -q 'name="install_key"' "$page" || fail "$host: install key step not shown"
    # Read on the server side, as the owner would with the hosting file manager.
    echo "  install.key: $(docker exec hosting stat -c '%u:%g %a' "/home/$5/storage/app/private/install.key" 2>&1)"
    post --data action=key --data-urlencode "install_key=$(docker exec hosting cat "/home/$5/storage/app/private/install.key" | tr -d '\r\n')" >/dev/null; get
    post --data action=database --data host=db --data port=3306 --data "database=$2" --data "username=$3" --data-urlencode "password=$4" --data prefix= >/dev/null; get
    grep -q 'name="admin_username"' "$page" && pass "$host: database accepted" || fail "$host: database step failed"
    post --data action=site --data-urlencode "url=$base" --data-urlencode "name=Biofy $host" --data locale=tr --data timezone=Europe/Istanbul \
        --data admin_name=Owner --data admin_username=owner --data admin_email=owner@example.com \
        --data-urlencode "admin_password=$ADMIN_PW" --data-urlencode "admin_password_confirmation=$ADMIN_PW" >/dev/null; get
    expect_eq "$host: install request" "$(post --data action=install)" 200
    [ -f "$HOSTING/$5/storage/app/private/installed.lock" ] && pass "$host: installer locked itself" || fail "$host: lock file missing"
    [ ! -f "$HOSTING/$5/storage/app/private/install.key" ] && pass "$host: install key removed" || fail "$host: install key still present"
    expect_eq "$host: .env permissions" "$(stat -c %a "$HOSTING/$5/.env")" 640
}

# check_site HOST APP_DIR MEDIA_DIR
check_site() {
    local host=$1 base="http://$1" jar="$WORK/jar-$1"
    CURL_OPTS=(--resolve "$host:80:127.0.0.1")
    expect_eq "$host: home page" "$(http_code "$base/")" 200
    expect_eq "$host: installer is not reachable any more" "$(curl "${CURL_OPTS[@]}" -s "$base/?lang=en" | grep -c 'name="install_key"\|name="action"' || true)" 0
    expect_eq "$host: superadmin login" "$(login "$base" "$jar" owner@example.com "$ADMIN_PW")" "$base/admin"
    expect_eq "$host: admin panel" "$(http_code -b "$jar" "$base/admin")" 200
    local url
    url=$(absolute "$base" "$(upload_media "$base" "$jar" "$WORK/test.png")")
    [ "$url" != "$base" ] && pass "$host: image uploaded" || fail "$host: upload failed"
    expect_eq "$host: uploaded image served" "$(http_code "$url")" 200
    docker exec hosting sh -c "echo '<?php echo 6*7;' > /home/$3/probe.php"
    body_lacks "$host: PHP inside media/ is not executed" "$base/media/probe.php" 42
    docker exec hosting rm -f "/home/$3/probe.php"
    for path in /.env /.htaccess /composer.json /artisan /storage/app/private/installed.lock /vendor/autoload.php /install/installer.php /bootstrap/app.php /config/app.php /docs/INSTALLATION.md /release-manifest.json; do
        expect_in "$host: GET $path" "$(http_code "$base$path")" 403 404
    done
    body_lacks "$host: .env content never served" "$base/.env" "APP_KEY"
    docker exec -u "$(stat -c %u:%g "$HOSTING/$2/.env")" hosting php "/home/${2}/artisan" biofy:maintenance >/dev/null && pass "$host: CLI/cron command runs" || fail "$host: artisan failed"
}

make_png "$WORK/test.png"

section "Layout A (application outside public_html)"
run_installer bio.example.test user_biofya user_a a-pass-123 user/biofy
check_site bio.example.test user/biofy user/biofy/public/media

section "Layout B (public files in the subdomain folder, application in ../biofy)"
run_installer bio3.example.test user2_biofyb user2_b b-pass-123 user2/biofy
[ -f "$HOSTING/user2/biofy/bootstrap/public-path.php" ] && pass "installer recorded the public path" || fail "public-path.php missing"
check_site bio3.example.test user2/biofy user2/bio3.example.test/media

section "Layout C (whole package inside WordPress' public_html)"
run_installer bio2.example.test user_biofyc user_c c-pass-123 user/public_html/bio2
check_site bio2.example.test user/public_html/bio2 user/public_html/bio2/public/media
CURL_OPTS=(--resolve example.test:80:127.0.0.1)
for path in /bio2/.env /bio2/composer.json /bio2/storage/app/private/installed.lock /bio2/vendor/autoload.php /bio2/install/installer.php /bio2/config/app.php; do
    expect_in "via WordPress domain: GET $path" "$(http_code "http://example.test$path")" 403 404
done

section "WordPress still works"
CURL_OPTS=(--resolve example.test:80:127.0.0.1)
body_contains "WordPress home page" "http://example.test/" "CI Blog"
body_contains "WordPress still routes its own URLs (.htaccess)" "http://example.test/no-such-page/" "wp-content"
expect_eq "WordPress login page" "$(http_code "http://example.test/wp-login.php")" 200

finish
