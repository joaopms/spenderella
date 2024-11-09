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

# SQLite needs to have permission to write to the folder as well, even if it only uses database.sqlite
chown -R :www-data /var/www/database
chmod -R 775 /var/www/database

exec /usr/bin/supervisord -c /etc/supervisord.conf
