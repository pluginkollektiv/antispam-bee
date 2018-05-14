#!/bin/bash

WORDPRESS_URL=localhost:8080;
WORDPRESS_PATH=/tmp/antispam-bee/;
PLUGIN_PATH=/var/www/html/antispambee/wp-content/plugins/antispam-bee;
WORDPRESS_VERSION=latest;
DB_NAME=pluginkollektiv_antispambee_behat;
DB_USER=root;
DB_PASS=eregi2008;
DB_HOST=localhost;

mkdir -p $WORDPRESS_PATH
vendor/bin/wp core download --force --version=$WORDPRESS_VERSION --path=$WORDPRESS_PATH
rm -f ${WORDPRESS_PATH}wp-config.php
vendor/bin/wp config create --path=$WORDPRESS_PATH --dbname=$DB_NAME --dbuser=$DB_USER --dbpass=$DB_PASS --dbhost=$DB_HOST --skip-salts
vendor/bin/wp db create --path=$WORDPRESS_PATH
vendor/bin/wp core install --path=$WORDPRESS_PATH --url=$WORDPRESS_URL --title="wordpress.dev" --admin_user="admin" --admin_password="abc" --admin_email="admin@example.com"
wait_for_port() {
  while echo | telnet localhost 4444 2>&1 | grep -qe 'Connection refused'; do
    echo "Connection refused on port 4444. Waiting $NAP_LENGTH seconds..."
    sleep $NAP_LENGTH
  done
}

export DISPLAY=:99.0
sh -e /etc/init.d/xvfb start
sleep 1

php -r 'require "vendor/joomla-projects/selenium-server-standalone/Selenium.php";
$selenium = new Selenium(["browser" => "chrome", "selenium_params" => [" -Dselenium.LOGGER.level=OFF"] ]);
$selenium->run();';

wait_for_port
sleep 5

php -S "$WORDPRESS_URL" -t "$WORDPRESS_PATH" >/dev/null 2>&1 &

ln -s $PLUGIN_PATH $WORDPRESS_PATH/wp-content/plugins/antispam-bee
ln -s $PLUGIN_PATH/tests/Acceptance/Behat/env/mu-plugins/ $WORDPRESS_PATH/wp-content/mu-plugins

ls $WORDPRESS_PATH/wp-content/plugins

vendor/bin/wp --path=$WORDPRESS_PATH plugin activate antispam-bee

vendor/bin/behat --tags=test