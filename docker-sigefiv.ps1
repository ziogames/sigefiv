# ============================================================
# SIGEFIV - Actualizar y reiniciar Docker
# ============================================================
# Ejecutar desde la carpeta raiz del proyecto:
# C:\Users\User\Desktop\Nueva carpeta\mi-app\mi-app
#
# Uso:
#   .\docker-sigefiv.ps1
#
# Si PowerShell bloquea la ejecucion:
#   powershell -ExecutionPolicy Bypass -File .\docker-sigefiv.ps1
# ============================================================

$ErrorActionPreference = "Stop"

$image = "sigefiv"
$container = "sigefiv-test"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "       ACTUALIZANDO SIGEFIV EN DOCKER" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/4] Construyendo imagen..." -ForegroundColor Yellow
docker build -t $image .

if ($LASTEXITCODE -ne 0) {
    throw "El docker build fallo."
}

Write-Host ""
Write-Host "[2/4] Deteniendo contenedor anterior..." -ForegroundColor Yellow
docker stop $container 2>$null

Write-Host ""
Write-Host "[3/4] Eliminando contenedor anterior..." -ForegroundColor Yellow
docker rm $container 2>$null

Write-Host ""
Write-Host "[4/4] Creando nuevo contenedor..." -ForegroundColor Yellow

docker run -d `
    --name $container `
    -p 8080:80 `
    --add-host=host.docker.internal:host-gateway `
    $image

if ($LASTEXITCODE -ne 0) {
    throw "No se pudo crear el contenedor."
}

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "       SIGEFIV ACTUALIZADO CORRECTAMENTE" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Contenedor: $container" -ForegroundColor White
Write-Host "Aplicacion: http://localhost:8080" -ForegroundColor White
Write-Host ""
Write-Host "Logs recientes:" -ForegroundColor Cyan
docker logs --tail 20 $container

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "Listo." -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green