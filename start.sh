#!/bin/sh
sleep 15s

sudo /usr/sbin/crond

composer install

php artisan horizon:publish

php artisan l5-swagger:generate

php artisan config:cache

php artisan cache:clear

composer dumpautoload

php artisan migrate

php artisan serve --host=0.0.0.0 --port=80 & sudo supervisord -c /etc/supervisord.conf


