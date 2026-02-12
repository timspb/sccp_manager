# Build FreePBX module package for sccp_manager
# Run from repo root: .\build-module.ps1
# Output: dist\sccp_manager-<version>.zip

$ErrorActionPreference = "Stop"
$rawname = "sccp_manager"

# Get version from module.xml
$moduleXml = Join-Path $PSScriptRoot "module.xml"
if (-not (Test-Path $moduleXml)) { throw "module.xml not found" }
[xml]$xml = Get-Content $moduleXml -Encoding UTF8
$version = $xml.module.version
if (-not $version) { $version = "17.0.1.0" }

$distDir = Join-Path $PSScriptRoot "dist"
$stageDir = Join-Path $distDir $rawname
$zipName = "${rawname}-${version}.zip"
$zipPath = Join-Path $distDir $zipName

# Clean and create stage
if (Test-Path $stageDir) { Remove-Item $stageDir -Recurse -Force }
New-Item -ItemType Directory -Path $stageDir -Force | Out-Null

# Copy all files except .git and build artifacts
$exclude = @(".git", ".gitignore", "dist", "build-module.ps1", "*.zip")
Get-ChildItem -Path $PSScriptRoot -Force | Where-Object {
    $name = $_.Name
    $skip = $false
    foreach ($e in $exclude) {
        if ($e -like "*.*") { if ($name -like $e) { $skip = $true; break } }
        elseif ($name -eq $e) { $skip = $true; break }
    }
    -not $skip
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $stageDir -Recurse -Force
}

# Remove .git if copied by mistake
$gitCopy = Join-Path $stageDir ".git"
if (Test-Path $gitCopy) { Remove-Item $gitCopy -Recurse -Force }

# Create zip (PowerShell 5+)
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Compress-Archive -Path $stageDir -DestinationPath $zipPath -CompressionLevel Optimal

# Clean stage (optional; keep for inspection)
# Remove-Item $stageDir -Recurse -Force

Write-Host "Built: $zipPath"
Write-Host "Version: $version"
Write-Host "Install in FreePBX: Admin -> Module Admin -> Upload Modules -> path to zip or URL"
