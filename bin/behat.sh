#!/bin/bash

WORDPRESS_URL=localhost:8080;
WORDPRESS_PATH=tmp/antispam-bee/;
PLUGIN_PATH=$GITHUB_WORKSPACE;
DB_NAME=pluginkollektiv_antispambee_behat;
DB_USER=root;
DB_PASS=;
DB_HOST=127.0.0.1:8888;

echo "Chrome version:"
google-chrome --version
which google-chrome
mkdir -p $WORDPRESS_PATH
vendor/bin/wp core download --force --version=$WORDPRESS_VERSION --path=$WORDPRESS_PATH
rm -f ${WORDPRESS_PATH}wp-config.php
vendor/bin/wp config create --path=$WORDPRESS_PATH --dbname=$DB_NAME --dbuser=$DB_USER --dbpass=$DB_PASS --dbhost=$DB_HOST --skip-salts  --extra-php="define('WP_DEBUG', true); define('WP_DEBUG_DISPLAY', false); define('WP_DEBUG_LOG', true):"
vendor/bin/wp core install --path=$WORDPRESS_PATH --url=$WORDPRESS_URL --title="wordpress.dev" --admin_user="admin" --admin_password="abc" --admin_email="admin@example.com"

wait_for_port() {
  while echo | telnet localhost 9222 2>&1 | grep -qe 'Connection refused'; do
    echo "Connection refused on port 9222. Waiting $NAP_LENGTH seconds..."
    sleep $NAP_LENGTH
  done
}
export DISPLAY=:99.0
sh -e /etc/init.d/xvfb start
sleep 1

#wget -c -nc --retry-connrefused --tries=0 https://bit.ly/2TlkRyu -O selenium-server-standalone.jar
wget -c -nc --retry-connrefused --tries=0 https://chromedriver.storage.googleapis.com/91.0.4472.19/chromedriver_linux64.zip -O driver.zip
unzip driver.zip
chmod +x chromedriver
#wget -c -nc --retry-connrefused --tries=0 https://github.com/mozilla/geckodriver/releases/download/v0.29.1/geckodriver-v0.29.1-linux64.tar.gz -O driver.tar.gz
#tar xzf driver.tar.gz
#chmod +x geckodriver

export PATH=$PATH:$PWD
ls -la

echo "Run selenium server - background process"
nohup bash -c "google-chrome-stable --headless --disable-gpu --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222 &" && sleep 1; cat nohup.out

wait_for_port
sleep 5

php -S "$WORDPRESS_URL" -t "$WORDPRESS_PATH" >/dev/null 2>&1 &

ln -s $PLUGIN_PATH $WORDPRESS_PATH/wp-content/plugins/antispam-bee
ln -s $PLUGIN_PATH/tests/Acceptance/Behat/env/mu-plugins/ $WORDPRESS_PATH/wp-content/mu-plugins

ls $WORDPRESS_PATH/wp-content/plugins

vendor/bin/wp --path=$WORDPRESS_PATH plugin activate antispam-bee
vendor/bin/wp --path=$WORDPRESS_PATH theme activate twentynineteen

vendor/bin/behat
