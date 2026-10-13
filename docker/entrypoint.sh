#!/bin/sh
# Runs every time the container starts. Volumes mounted by Coolify are owned by root,
# so here the writable folders are created and handed to Apache's user (www-data).
set -e
cd /var/www/html
mkdir -p database cache/skins uploads/avatars
chown -R www-data:www-data database cache uploads
chmod -R u+rwX,g+rX database cache uploads
exec apache2-foreground
