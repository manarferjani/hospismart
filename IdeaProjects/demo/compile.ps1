# Script de configuration JAVA_HOME pour PowerShell

# Chercher JDK dans les emplacements courants
$jdkPaths = @(
    "C:\Program Files\Java\jdk21",
    "C:\Program Files\Java\jdk-21",
    "C:\Program Files (x86)\Java\jdk21",
    "C:\Program Files (x86)\Java\jdk-21",
    "$env:JAVA_HOME"
)

$javaFound = $false

foreach ($path in $jdkPaths) {
    if (Test-Path "$path\bin\java.exe") {
        $env:JAVA_HOME = $path
        Write-Host "✅ JAVA_HOME configuré sur: $path" -ForegroundColor Green
        $javaFound = $true
        break
    }
}

if (-not $javaFound) {
    Write-Host "❌ ERREUR: Java 21 JDK n'a pas été trouvé" -ForegroundColor Red
    Write-Host ""
    Write-Host "Veuillez installer Java 21 JDK depuis:" -ForegroundColor Yellow
    Write-Host "https://www.oracle.com/java/technologies/downloads/" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Ou définir manuellement JAVA_HOME:" -ForegroundColor Yellow
    Write-Host "[Environment]::SetEnvironmentVariable('JAVA_HOME', 'C:\chemin\vers\jdk21', 'User')" -ForegroundColor Cyan
    Write-Host ""
    exit 1
}

Write-Host ""
Write-Host "🔧 Compilation en cours..." -ForegroundColor Cyan
Write-Host ""

# Compiler le projet
& .\mvnw.cmd clean compile

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Compilation réussie!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Pour lancer l'application, utilisez:" -ForegroundColor Yellow
    Write-Host ".\mvnw.cmd javafx:run" -ForegroundColor Cyan
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "❌ Erreur lors de la compilation" -ForegroundColor Red
    Write-Host ""
}

