# Script de configuration pour les variables d'environnement MySQL
# À exécuter une seule fois pour configurer les accès

# Configuration MySQL - à adapter selon votre setup
$env:DB_URL = "jdbc:mysql://localhost:3306/hospismart?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true"
$env:DB_USERNAME = "root"
$env:DB_PASSWORD = ""
$env:DB_DRIVER = "com.mysql.cj.jdbc.Driver"
$env:DB_LOGIN_TIMEOUT_SECONDS = "5"

Write-Host "Variables d'environnement configurées :" -ForegroundColor Green
Write-Host "DB_URL: $env:DB_URL" -ForegroundColor Cyan
Write-Host "DB_USERNAME: $env:DB_USERNAME" -ForegroundColor Cyan
Write-Host "DB_DRIVER: $env:DB_DRIVER" -ForegroundColor Cyan

Write-Host ""
Write-Host "Vous pouvez maintenant lancer l'application avec :" -ForegroundColor Yellow
Write-Host "mvnw.cmd javafx:run" -ForegroundColor Cyan

