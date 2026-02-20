@echo off
echo Demarrage du serveur HospiSmart sur http://127.0.0.1:8000
echo.
echo IMPORTANT: Ne fermez pas cette fenetre pendant que vous utilisez le site.
echo Ouvrez votre navigateur sur : http://127.0.0.1:8000
echo.
cd /d "%~dp0"
C:\xampp_new\php\php.exe -S 127.0.0.1:8000 public/router.php
pause
