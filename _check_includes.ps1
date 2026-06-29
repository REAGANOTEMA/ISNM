$root = "C:\xampp\htdocs\ISNM"
$dirs = @("dashboards","handlers","includes","admin_panel","teacher_panel","owner_panel","student_panel","sql")
$broken = @()

foreach ($dir in $dirs) {
    $phpFiles = Get-ChildItem -Path (Join-Path $root $dir) -Filter "*.php" -Recurse -ErrorAction SilentlyContinue
    foreach ($file in $phpFiles) {
        $content = Get-Content $file.FullName
        $fileDir = Split-Path $file.FullName
        for ($i = 0; $i -lt $content.Count; $i++) {
            $line = $content[$i]
            # Match: include/require('path') or include/require "path"
            if ($line -match '(require_once|require|include_once|include)\s*\(\s*[''"]([^''"]+)[''"]') {
                $path = $Matches[2]
                $fullPath = Join-Path $fileDir $path
                if (-not (Test-Path $fullPath)) {
                    $broken += "BROKEN: $($file.FullName):$($i+1) -> $path"
                }
            }
            # Match: include/require __DIR__ . '/../path'
            elseif ($line -match '(require_once|require|include_once|include)\s+__DIR__\s*\.\s*[''"]([^''"]+)[''"]') {
                $path = $Matches[2]
                $fullPath = Join-Path $fileDir $path
                if (-not (Test-Path $fullPath)) {
                    $broken += "BROKEN: $($file.FullName):$($i+1) -> __DIR__.$path"
                }
            }
            # Match: include 'path' (no parentheses)
            elseif ($line -match '(require_once|require|include_once|include)\s+[''"]([^''"]+)[''"]') {
                $path = $Matches[2]
                $fullPath = Join-Path $fileDir $path
                if (-not (Test-Path $fullPath)) {
                    $broken += "BROKEN: $($file.FullName):$($i+1) -> $path"
                }
            }
        }
    }
}

if ($broken.Count -eq 0) {
    Write-Host "All include/require paths are valid!"
} else {
    Write-Host "Found $($broken.Count) broken include paths:"
    $broken | ForEach-Object { Write-Host $_ }
}
