@echo off
echo Demarrage du serveur Symfony sur http://127.0.0.1:8000
echo.
echo IMPORTANT: Utilise le router pour que /medicament et /mouvement/stock fonctionnent.
echo.
cd /d "%~dp0"
php -S 127.0.0.1:8000 -t public router.php
pause
