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

Write-Host "[1/6] Construyendo imagen..." -ForegroundColor Yellow

docker build -t $image .

if ($LASTEXITCODE -ne 0) {
    throw "El docker build fallo."
}

# ------------------------------------------------------------
# 2. Detener contenedor anterior
# ------------------------------------------------------------

Write-Host ""
Write-Host "[2/6] Deteniendo contenedor anterior..." -ForegroundColor Yellow

docker stop $container 2>$null

# ------------------------------------------------------------
# 3. Eliminar contenedor anterior
# ------------------------------------------------------------

Write-Host ""
Write-Host "[3/6] Eliminando contenedor anterior..." -ForegroundColor Yellow

docker rm $container 2>$null

# ------------------------------------------------------------
# 4. Crear volúmenes de dependencias
# ------------------------------------------------------------

Write-Host ""
Write-Host "[4/6] Preparando volumenes..." -ForegroundColor Yellow

docker volume create $vendorVolume | Out-Null
docker volume create $nodeVolume | Out-Null

# ------------------------------------------------------------
# 5. Crear contenedor sincronizado
# ------------------------------------------------------------

Write-Host ""
Write-Host "[5/6] Creando contenedor sincronizado..." -ForegroundColor Yellow

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
# 6. Mostrar información
# ------------------------------------------------------------

Write-Host ""
Write-Host "[6/6] Verificando montaje..." -ForegroundColor Yellow

docker inspect $container --format='{{range .Mounts}}{{println .Type ":" .Source " -> " .Destination}}{{end}}'

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "       SIGEFIV ACTUALIZADO" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""

Write-Host "Contenedor:" -ForegroundColor White
Write-Host $container

Write-Host ""
Write-Host "Aplicacion:" -ForegroundColor White
Write-Host "http://localhost:8080"

Write-Host ""
Write-Host "Codigo sincronizado:" -ForegroundColor White
Write-Host "$projectPath -> /var/www/html"

Write-Host ""
Write-Host "Logs recientes:" -ForegroundColor Cyan

docker logs --tail 30 $container

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "Listo." -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green