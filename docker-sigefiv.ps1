# ============================================================
# SIGEFIV - Desarrollo sincronizado Windows <-> Docker
# ============================================================
# Ejecutar desde:
# C:\Users\User\Desktop\Nueva carpeta\mi-app\mi-app
# ============================================================

$ErrorActionPreference = "Stop"

$image = "sigefiv"
$container = "sigefiv-test"

# Volúmenes persistentes para dependencias
$vendorVolume = "sigefiv_vendor"
$nodeVolume = "sigefiv_node_modules"

# Ruta absoluta del proyecto Windows
$projectPath = (Get-Location).Path

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "       SIGEFIV - DOCKER DESARROLLO" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Proyecto Windows:" -ForegroundColor Yellow
Write-Host $projectPath -ForegroundColor White
Write-Host ""

# ------------------------------------------------------------
# 1. Construir imagen
# ------------------------------------------------------------

Write-Host "[1/8] Construyendo imagen..." -ForegroundColor Yellow

docker build -t $image .

if ($LASTEXITCODE -ne 0) {
    throw "El docker build fallo."
}

# ------------------------------------------------------------
# 2. Detener contenedor anterior
# ------------------------------------------------------------

Write-Host ""
Write-Host "[2/8] Deteniendo contenedor anterior..." -ForegroundColor Yellow

docker stop $container 2>$null

# ------------------------------------------------------------
# 3. Eliminar contenedor anterior
# ------------------------------------------------------------

Write-Host ""
Write-Host "[3/8] Eliminando contenedor anterior..." -ForegroundColor Yellow

docker rm $container 2>$null

# ------------------------------------------------------------
# 4. Crear volúmenes
# ------------------------------------------------------------

Write-Host ""
Write-Host "[4/8] Preparando volumenes..." -ForegroundColor Yellow

docker volume create $vendorVolume | Out-Null
docker volume create $nodeVolume | Out-Null

# ------------------------------------------------------------
# 5. Crear contenedor sincronizado
# ------------------------------------------------------------

Write-Host ""
Write-Host "[5/8] Creando contenedor sincronizado..." -ForegroundColor Yellow

docker run -d `
    --name $container `
    -p 8080:80 `
    --add-host=host.docker.internal:host-gateway `
    --mount "type=bind,source=$projectPath,target=/var/www/html" `
    --mount "type=volume,source=$vendorVolume,target=/var/www/html/vendor" `
    --mount "type=volume,source=$nodeVolume,target=/var/www/html/node_modules" `
    $image

if ($LASTEXITCODE -ne 0) {
    throw "No se pudo crear el contenedor."
}

# ------------------------------------------------------------
# Esperar a que el contenedor esté listo
# ------------------------------------------------------------

Write-Host ""
Write-Host "[6/8] Esperando al contenedor..." -ForegroundColor Yellow

Start-Sleep -Seconds 3

# ------------------------------------------------------------
# 7. Instalar dependencias PHP dentro del volumen Docker
# ------------------------------------------------------------

Write-Host ""
Write-Host "[7/8] Preparando dependencias PHP dentro de Docker..." -ForegroundColor Yellow

docker exec $container composer install --no-interaction

if ($LASTEXITCODE -ne 0) {
    throw "Composer install fallo dentro del contenedor."
}

Write-Host ""
Write-Host "Regenerando autoload de Composer..." -ForegroundColor Yellow

docker exec $container composer dump-autoload -o

if ($LASTEXITCODE -ne 0) {
    throw "Composer dump-autoload fallo dentro del contenedor."
}

# ------------------------------------------------------------
# Verificar Firebase
# ------------------------------------------------------------

Write-Host ""
Write-Host "Verificando Firebase..." -ForegroundColor Yellow

docker exec $container php -r "require 'vendor/autoload.php'; var_dump(class_exists('Kreait\\Laravel\\Firebase\\ServiceProvider'));"

if ($LASTEXITCODE -ne 0) {
    throw "No se pudo verificar Firebase."
}

# ------------------------------------------------------------
# Limpiar cachés Laravel
# ------------------------------------------------------------

Write-Host ""
Write-Host "Limpiando cache de Laravel..." -ForegroundColor Yellow

docker exec $container php artisan optimize:clear

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Advertencia: optimize:clear devolvio un error." -ForegroundColor Yellow
    Write-Host "El contenedor continuara ejecutandose." -ForegroundColor Yellow
}

# ------------------------------------------------------------
# 8. Mostrar información
# ------------------------------------------------------------

Write-Host ""
Write-Host "[8/8] Verificando montaje..." -ForegroundColor Yellow

docker inspect $container --format='{{range .Mounts}}{{println .Type ":" .Source " -> " .Destination}}{{end}}'

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "       SIGEFIV ACTUALIZADO" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""

Write-Host "Contenedor:" -ForegroundColor White
Write-Host $container

Write-Host ""
Write-Host "Imagen:" -ForegroundColor White
Write-Host $image

Write-Host ""
Write-Host "Aplicacion:" -ForegroundColor White
Write-Host "http://localhost:8080"

Write-Host ""
Write-Host "Codigo sincronizado:" -ForegroundColor White
Write-Host "$projectPath -> /var/www/html"

Write-Host ""
Write-Host "Vendor Docker:" -ForegroundColor White
Write-Host "$vendorVolume -> /var/www/html/vendor"

Write-Host ""
Write-Host "Node Modules Docker:" -ForegroundColor White
Write-Host "$nodeVolume -> /var/www/html/node_modules"

Write-Host ""
Write-Host "Firebase:" -ForegroundColor White

docker exec $container php -r "require 'vendor/autoload.php'; echo class_exists('Kreait\\Laravel\\Firebase\\ServiceProvider') ? 'OK - Firebase disponible' : 'ERROR - Firebase no disponible';"

Write-Host ""
Write-Host "Logs recientes:" -ForegroundColor Cyan

docker logs --tail 30 $container

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "             SIGEFIV LISTO" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""