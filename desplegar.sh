#!/usr/bin/env bash
set -e

CONTAINER="sigefiv-app"
IMAGE="sigefiv"
VENDOR_VOLUME="sigefiv_vendor"
NODE_VOLUME="sigefiv_node_modules"
PROJECT_PATH="$(pwd)"

echo "=========================================="
echo "     SIGEFIV - DESPLIEGUE PRODUCCIÓN"
echo "=========================================="
echo "Proyecto: $PROJECT_PATH"

echo
echo "[1/8] Limpiando cachés y permisos locales..."
sudo rm -f "$PROJECT_PATH/bootstrap/cache/"*.php
sudo chown -R "$USER:$USER" \
    "$PROJECT_PATH/storage" \
    "$PROJECT_PATH/bootstrap/cache"
sudo chmod -R 775 \
    "$PROJECT_PATH/storage" \
    "$PROJECT_PATH/bootstrap/cache"

echo
echo "[2/8] Construyendo imagen Docker..."
docker build -t "$IMAGE" .

echo
echo "[3/8] Deteniendo contenedor anterior..."
docker stop "$CONTAINER" 2>/dev/null || true
docker rm -f "$CONTAINER" 2>/dev/null || true

echo
echo "[4/8] Verificando volúmenes..."
docker volume create "$VENDOR_VOLUME" >/dev/null
docker volume create "$NODE_VOLUME" >/dev/null

echo
echo "[5/8] Instalando dependencias Composer..."
docker run --rm \
    --mount "type=bind,source=$PROJECT_PATH,target=/var/www/html" \
    --mount "type=volume,source=$VENDOR_VOLUME,target=/var/www/html/vendor" \
    -w /var/www/html \
    "$IMAGE" \
    sh -c "
        composer install \
            --no-dev \
            --no-interaction \
            --prefer-dist \
            --optimize-autoloader &&
        composer dump-autoload -o
    "

echo
echo "[6/8] Iniciando contenedor..."
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

echo
echo "[7/8] Esperando respuesta del servidor..."
until curl -s http://127.0.0.1:8080 >/dev/null; do
    sleep 1
done

echo
echo "[8/8] Optimizando Laravel..."

docker exec "$CONTAINER" \
    chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

docker exec "$CONTAINER" \
    chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

docker exec "$CONTAINER" sh -c "
    rm -f bootstrap/cache/*.php
"

docker exec "$CONTAINER" php artisan optimize:clear
docker exec "$CONTAINER" php artisan config:cache
docker exec "$CONTAINER" php artisan route:cache

echo
echo "=========================================="
echo "     DESPLIEGUE COMPLETADO"
echo "=========================================="

echo
echo "Estado del contenedor:"
docker ps --filter "name=$CONTAINER"

echo
echo "Verificando aplicación:"
curl -I http://127.0.0.1:8080 || true

echo
echo "Rutas ZOE:"
docker exec "$CONTAINER" php artisan route:list --path=zoe || true

echo
echo "Laravel:"
docker exec "$CONTAINER" php artisan about || true