@echo off
REM Script pour lancer l'application HospiSmart Desktop

echo.
echo ========================================
echo   HospiSmart Desktop Application
echo ========================================
echo.

REM Vérifier que nous sommes dans le bon répertoire
if not exist "pom.xml" (
    echo Erreur : pom.xml non trouvé. Veuillez être dans le répertoire du projet.
    pause
    exit /b 1
)

echo Démarrage de l'application...
echo.

REM Lancer avec Maven
call mvnw.cmd javafx:run

if errorlevel 1 (
    echo.
    echo Erreur lors du lancement. Vérifiez votre configuration MySQL.
    echo Assurez-vous que :
    echo - MySQL est en cours d'exécution
    echo - La base de données 'hospismart' existe
    echo - Les identifiants dans application.properties sont corrects
    pause
    exit /b 1
)

pause

