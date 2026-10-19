#!/bin/sh
set -e
cd /var/www/html
mkdir -p database cache/skins uploads/avatars
chown -R www-data:www-data database cache uploads
chmod -R u+rwX,g+rX database cache uploads
exec apache2-foreground
