@echo off
cd /d "%~dp0"

echo Organisation des dossiers en MVC...

echo 1. Creation des dossiers...
mkdir src\main\java\com\example\demo\controller 2>nul
mkdir src\main\java\com\example\demo\model 2>nul
mkdir src\main\resources\com\example\demo\view 2>nul

echo 2. Deplacement des Controllers...
move src\main\java\com\example\demo\*Controller*.java src\main\java\com\example\demo\controller\ >nul 2>&1

echo 3. Deplacement des Models (depuis domain)...
move src\main\java\com\example\demo\domain\*.java src\main\java\com\example\demo\model\ >nul 2>&1
rmdir src\main\java\com\example\demo\domain >nul 2>&1

echo 4. Deplacement des Views...
move src\main\resources\com\example\demo\*.fxml src\main\resources\com\example\demo\view\ >nul 2>&1

echo Termine! Les fichiers sont maintenant organises.
pause
