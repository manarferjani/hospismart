$ErrorActionPreference = "Stop"
$baseDir = "C:\Users\User\IdeaProjects\demo"
Set-Location $baseDir

Write-Host "Reorganizing MVC architecture..." -ForegroundColor Cyan

# 1. Create Directories
$controllerPath = "src\main\java\com\example\demo\controller"
$modelPath = "src\main\java\com\example\demo\model"
$viewPath = "src\main\resources\com\example\demo\view"

New-Item -ItemType Directory -Force -Path $controllerPath | Out-Null
New-Item -ItemType Directory -Force -Path $modelPath | Out-Null
New-Item -ItemType Directory -Force -Path $viewPath | Out-Null

# 2. Move files
Write-Host "Moving Controllers..."
Get-ChildItem -Path "src\main\java\com\example\demo" -Filter "*Controller*.java" | Move-Item -Destination $controllerPath -Force

Write-Host "Moving Models..."
Get-ChildItem -Path "src\main\java\com\example\demo\domain" -Filter "*.java" | Move-Item -Destination $modelPath -Force
if (Test-Path "src\main\java\com\example\demo\domain") { Remove-Item -Path "src\main\java\com\example\demo\domain" -Recurse -Force }

Write-Host "Moving Views..."
Get-ChildItem -Path "src\main\resources\com\example\demo" -Filter "*.fxml" | Move-Item -Destination $viewPath -Force

# 3. Text Replacements
Write-Host "Updating Code Imports and Packages..."

# Replace in Java files
Get-ChildItem -Path "src\main\java" -Filter "*.java" -Recurse | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $changed = $false

    # Model Package
    if ($content -match "package com.example.demo.domain;") {
        $content = $content -replace "package com.example.demo.domain;", "package com.example.demo.model;"
        $changed = $true
    }
    if ($content -match "import com.example.demo.domain.") {
        $content = $content -replace "import com.example.demo.domain.", "import com.example.demo.model."
        $changed = $true
    }

    # Controller Package
    if ($_.FullName -like "*\controller\*") {
        if ($content -match "package com.example.demo;") {
            $content = $content -replace "package com.example.demo;", "package com.example.demo.controller;"
            $changed = $true
        }
    }

    # Add controller import where needed
    if ($_.FullName -notlike "*\controller\*" -and $content -match "Controller") {
        if (-not ($content -match "import com.example.demo.controller.")) {
            $content = $content -replace "import com.example.demo.model.", "import com.example.demo.model.`r`nimport com.example.demo.controller.*;"
            $changed = $true
        }
    }

    # FXML Paths
    if ($content -match "\.getResource\(`"([a-zA-Z0-9_-]+\.fxml)`"\)") {
        $content = [regex]::Replace($content, "\.getResource\(`"([a-zA-Z0-9_-]+\.fxml)`"\)", '.getResource("view/$1")')
        $changed = $true
    }

    if ($changed) { Set-Content -Path $_.FullName -Value $content }
}

# Replace in FXML
Write-Host "Updating FXML Controllers..."
Get-ChildItem -Path $viewPath -Filter "*.fxml" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -match "fx:controller=`"com.example.demo.([a-zA-Z0-9_]+Controller)`"") {
        $content = [regex]::Replace($content, "fx:controller=`"com.example.demo.([a-zA-Z0-9_]+Controller)`"", 'fx:controller="com.example.demo.controller.$1"')
        Set-Content -Path $_.FullName -Value $content
    }
}

# Fix module-info.java
Write-Host "Updating module-info... "
$modulePath = "src\main\java\module-info.java"
$moduleInfo = Get-Content $modulePath -Raw
if (-not ($moduleInfo -match "exports com.example.demo.controller;")) {
    $moduleInfo = $moduleInfo -replace "exports com.example.demo;", "exports com.example.demo;`r`n    exports com.example.demo.controller;`r`n    opens com.example.demo.controller to javafx.fxml;"
    Set-Content -Path $modulePath -Value $moduleInfo
}

Write-Host "Done! Successfully implemented MVC architecture." -ForegroundColor Green
