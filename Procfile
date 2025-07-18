web: php -S 0.0.0.0:$PORT -t public
worker: php artisan queue:work --tries=3 --verbose