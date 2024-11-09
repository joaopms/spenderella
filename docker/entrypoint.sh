#!/usr/bin/env sh

if [ ! -f .env ]; then
    echo "---------------------------------------"
    echo "!!! Container running in setup mode !!!"
    echo "---------------------------------------"
    echo "Run the commands to setup the app and restart the container"
    sleep infinity
fi

chown -R :www-data /var/www/storage
chmod -R 775 /var/www/storage

chown -R :www-data /var/www/database/database.sqlite
chmod -R 775 /var/www/database/database.sqlite

exec /usr/bin/supervisord -c /etc/supervisord.conf
