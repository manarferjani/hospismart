@echo off
setlocal enabledelayedexpansion
REM Script de configuration JAVA_HOME pour Windows

REM Chercher JDK dans les emplacements courants
if exist "C:\Program Files\Java\jdk21\bin\java.exe" (
    set "JAVA_HOME=C:\Program Files\Java\jdk21"
    echo JAVA_HOME defini sur: C:\Program Files\Java\jdk21
    goto compile
)

if exist "C:\Program Files\Java\jdk-21\bin\java.exe" (
    set "JAVA_HOME=C:\Program Files\Java\jdk-21"
    echo JAVA_HOME defini sur: C:\Program Files\Java\jdk-21
    goto compile
)

if exist "C:\Program Files (x86)\Java\jdk21\bin\java.exe" (
    set "JAVA_HOME=C:\Program Files (x86)\Java\jdk21"
    echo JAVA_HOME defini sur: C:\Program Files (x86)\Java\jdk21
    goto compile
)

if exist "%JAVA_HOME%" (
    echo JAVA_HOME deja configure: %JAVA_HOME%
    goto compile
)

echo.
echo ERREUR: Java 21 JDK n'a pas ete trouve
echo.
echo Veuillez installer Java 21 JDK depuis:
echo https://www.oracle.com/java/technologies/downloads/
echo.
echo Ou definir manuellement JAVA_HOME:
echo setx JAVA_HOME "C:\chemin\vers\jdk21"
echo.
pause
exit /b 1

:compile
echo.
echo Compilation en cours...
echo.
call mvnw.cmd clean compile
if %ERRORLEVEL% EQU 0 (
    echo.
    echo Compilation reussie!
    echo.
    echo Pour lancer l'application, utilisez:
    echo mvnw.cmd javafx:run
    echo.
) else (
    echo.
    echo Erreur lors de la compilation
    echo.
)
pause



