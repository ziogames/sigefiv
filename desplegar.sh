#!/usr/bin/env bash
set -e

CONTAINER="sigefiv-app"
IMAGE="sigefiv"
VENDOR_VOLUME="sigefiv_vendor"
NODE_VOLUME="sigefiv_node_modules"
PROJECT_PATH="$(pwd)"

echo "=========================================="
echo "       SIGEFIV - DOCKER EN DEBIAN"
echo "=========================================="
echo "Ruta local: $PROJECT_PATH"

echo -e "\n[1/7] Limpiando cachés residuales y permisos en el host..."
sudo rm -f "$PROJECT_PATH/bootstrap/cache/"*.php
sudo chown -R "$USER:$USER" "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache"
sudo chmod -R 775 "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache"

echo -e "\n[2/7] Construyendo imagen..."
docker build -t "$IMAGE" .

echo -e "\n[3/7] Deteniendo y eliminando contenedor previo..."
docker stop "$CONTAINER" 2>/dev/null || true
docker rm -f "$CONTAINER" 2>/dev/null || true

echo -e "\n[4/7] Preparando volúmenes persistentes..."
docker volume create "$VENDOR_VOLUME" >/dev/null
docker volume create "$NODE_VOLUME" >/dev/null

echo -e "\n[5/7] Verificando dependencias en el volumen de vendor..."
# Arrancamos un contenedor auxiliar temporal para garantizar que composer no falle si el contenedor principal crashea
docker run --rm \
    --mount "type=bind,source=$PROJECT_PATH,target=/var/www/html" \
    --mount "type=volume,source=$VENDOR_VOLUME,target=/var/www/html/vendor" \
    -w /var/www/html \
    "$IMAGE" sh -c "composer install --no-interaction && composer dump-autoload -o"

echo -e "\n[6/7] Iniciando contenedor de producción local..."
docker run -d \
    --name "$CONTAINER" \
    --network sigefiv-net \
    -p 127.0.0.1:8080:80 \
    --add-host=host.docker.internal:host-gateway \
    --mount "type=bind,source=$PROJECT_PATH,target=/var/www/html" \
    --mount "type=volume,source=$VENDOR_VOLUME,target=/var/www/html/vendor" \
    --mount "type=volume,source=$NODE_VOLUME,target=/var/www/html/node_modules" \
    --restart unless-stopped \
    "$IMAGE"

echo -e "\n[7/7] Esperando que el servicio web responda..."
sleep 3

# Asegurar permisos correctos para el servidor web (www-data)
docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec "$CONTAINER" chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Limpiar cachés de Laravel
docker exec "$CONTAINER" php artisan optimize:clear

echo -e "\n=========================================="
echo "       SIGEFIV MONTADO Y SINCRONIZADO"
echo "=========================================="
echo "Verificando rutas activas de ZOE:"
docker exec "$CONTAINER" php artisan route:list --path=zoe