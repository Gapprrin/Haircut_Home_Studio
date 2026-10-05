#!/bin/sh
set -e

echo "Waiting for MySQL..."
counter=0
while ! nc -z hhs-db 3306; do
  counter=$((counter+1))
  if [ $counter -gt 30 ]; then
    echo "ERROR: MySQL did not start"
    exit 1
  fi
  echo "MySQL not ready (attempt $counter/30)..."
  sleep 1
done
echo "✓ MySQL is ready"

echo "Running migrations..."
php /app/artisan migrate --force

echo "Creating storage link..."
php /app/artisan storage:link --force

echo "Starting application..."
exec supervisord -c /etc/supervisord.conf
