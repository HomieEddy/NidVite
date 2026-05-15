param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$PestArgs
)

$ErrorActionPreference = "Stop"

Write-Host "Starting PostgreSQL service via docker compose..."
docker compose up -d pgsql | Out-Null

Write-Host "Waiting for PostgreSQL to become ready..."
$maxAttempts = 30
for ($attempt = 1; $attempt -le $maxAttempts; $attempt++) {
    docker compose exec -T pgsql pg_isready -U sail -d postgres | Out-Null
    if ($LASTEXITCODE -eq 0) {
        break
    }

    if ($attempt -eq $maxAttempts) {
        throw "PostgreSQL did not become ready in time."
    }

    Start-Sleep -Seconds 2
}

Write-Host "Ensuring testing database exists..."
$exists = docker compose exec -T pgsql psql -U sail -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='testing';"
if (-not $exists.Trim()) {
    docker compose exec -T pgsql psql -U sail -d postgres -c "CREATE DATABASE testing;" | Out-Null
}

Write-Host "Ensuring PostGIS extension exists in testing database..."
docker compose exec -T pgsql psql -U sail -d testing -c "CREATE EXTENSION IF NOT EXISTS postgis;" | Out-Null

$env:APP_ENV = "testing"
$env:DB_CONNECTION = "pgsql"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "5432"
$env:DB_DATABASE = "testing"
$env:DB_USERNAME = "sail"
$env:DB_PASSWORD = "password"
$env:CACHE_STORE = "array"
$env:QUEUE_CONNECTION = "sync"
$env:SESSION_DRIVER = "array"
$env:MAIL_MAILER = "array"

$command = @("./vendor/bin/pest", "--parallel", "--colors=never")
if ($PestArgs -and $PestArgs.Count -gt 0) {
    $command += $PestArgs
}

Write-Host "Running Pest with host database settings..."
& $command[0] $command[1..($command.Count - 1)]
exit $LASTEXITCODE
