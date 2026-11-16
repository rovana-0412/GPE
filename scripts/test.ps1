$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$environmentFile = Join-Path $root '.env'

if (-not (Test-Path $environmentFile)) {
    throw "Le fichier .env est requis a la racine. Copiez .env.example vers .env puis renseignez les identifiants PostgreSQL."
}

$settings = @{}
Get-Content $environmentFile | ForEach-Object {
    if ($_ -match '^\s*([^#=]+)=(.*)$') {
        $key = $matches[1].Trim()
        $value = $matches[2].Trim()

        if ($value.Length -ge 2 -and $value[0] -eq $value[$value.Length - 1] -and ($value[0] -eq '"' -or $value[0] -eq "'")) {
            $value = $value.Substring(1, $value.Length - 2)
        }

        $settings[$key] = $value
    }
}

$databaseUsername = if ($env:DB_USERNAME) { $env:DB_USERNAME } else { $settings['DB_USERNAME'] }
$databasePassword = if ($env:DB_PASSWORD) { $env:DB_PASSWORD } else { $settings['DB_PASSWORD'] }
$databasePort = if ($env:POSTGRES_HOST_PORT) { $env:POSTGRES_HOST_PORT } else { $settings['POSTGRES_HOST_PORT'] }

if (-not $databaseUsername -or -not $databasePassword) {
    throw "DB_USERNAME et DB_PASSWORD doivent etre definis dans .env ou dans l'environnement."
}

if (-not $databasePort) {
    $databasePort = '5433'
}

$env:DB_CONNECTION = 'pgsql'
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = $databasePort
$env:DB_USERNAME = $databaseUsername
$env:DB_PASSWORD = $databasePassword
$env:DB_URL = ''

$services = @(
    @{ Path = 'services\auth-service'; Database = 'auth' },
    @{ Path = 'services\patrimoine-service'; Database = 'patrimoine' },
    @{ Path = 'services\mouvements-service'; Database = 'mouvements' },
    @{ Path = 'web'; Database = 'web' }
)
$runId = '{0}_{1}' -f (Get-Date -Format 'yyyyMMddHHmmss'), ([Guid]::NewGuid().ToString('N').Substring(0, 6))
$failed = $false

foreach ($service in $services) {
    $database = "gpe_validation_${runId}_$($service.Database)"
    & docker compose exec -T postgres createdb -U $databaseUsername $database
    if ($LASTEXITCODE -ne 0) {
        throw "Impossible de créer la base de test isolée $database. Aucune base existante n'a été supprimée."
    }

    $env:DB_DATABASE = $database
    Write-Host "`n=== Tests : $($service.Path) sur $database ==="
    Push-Location (Join-Path $root $service.Path)
    try {
        & php .\vendor\bin\phpunit
        if ($LASTEXITCODE -ne 0) {
            $failed = $true
        }
    } finally {
        Pop-Location
    }
}

if ($failed) {
    exit 1
}
