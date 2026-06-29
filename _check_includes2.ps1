$root = "C:\xampp\htdocs\ISNM"
$broken = @()

# Check root-level PHP files
$rootFiles = Get-ChildItem -Path $root -Filter "*.php" -File
foreach ($file in $rootFiles) {
    $content = Get-Content $file.FullName
    for ($i = 0; $i -lt $content.Count; $i++) {
        $line = $content[$i]
        if ($line -match '(require_once|require|include_once|include)\s*\(\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $root $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: $($file.Name):$($i+1) -> $path"
            }
        }
        elseif ($line -match '(require_once|require|include_once|include)\s+__DIR__\s*\.\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: $($file.Name):$($i+1) -> __DIR__.$path"
            }
        }
        elseif ($line -match '(require_once|require|include_once|include)\s+[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $root $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: $($file.Name):$($i+1) -> $path"
            }
        }
    }
}

# Check admin_panel partials (bus-shared)
$busShared = Get-ChildItem -Path "$root\admin_panel\partials\bus-shared" -Filter "*.php" -File -ErrorAction SilentlyContinue
foreach ($file in $busShared) {
    $content = Get-Content $file.FullName
    for ($i = 0; $i -lt $content.Count; $i++) {
        $line = $content[$i]
        if ($line -match '(require_once|require|include_once|include)\s*\(\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: admin_panel/partials/bus-shared/$($file.Name):$($i+1) -> $path"
            }
        }
    }
}

# Check includes internal includes
$incFiles = Get-ChildItem -Path "$root\includes" -Filter "*.php" -File
foreach ($file in $incFiles) {
    $content = Get-Content $file.FullName
    for ($i = 0; $i -lt $content.Count; $i++) {
        $line = $content[$i]
        if ($line -match '(require_once|require|include_once|include)\s*\(\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: includes/$($file.Name):$($i+1) -> $path"
            }
        }
        elseif ($line -match '(require_once|require|include_once|include)\s+__DIR__\s*\.\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: includes/$($file.Name):$($i+1) -> __DIR__.$path"
            }
        }
    }
}

# Check owner_panel/fetch-data
$fetchFiles = Get-ChildItem -Path "$root\owner_panel\fetch-data" -Filter "*.php" -File
foreach ($file in $fetchFiles) {
    $content = Get-Content $file.FullName
    for ($i = 0; $i -lt $content.Count; $i++) {
        $line = $content[$i]
        if ($line -match '(require_once|require|include_once|include)\s*\(\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: owner_panel/fetch-data/$($file.Name):$($i+1) -> $path"
            }
        }
    }
}

# Check quality-assurance
$qaFiles = Get-ChildItem -Path "$root\quality-assurance" -Filter "*.php" -File -ErrorAction SilentlyContinue
foreach ($file in $qaFiles) {
    $content = Get-Content $file.FullName
    for ($i = 0; $i -lt $content.Count; $i++) {
        $line = $content[$i]
        if ($line -match '(require_once|require|include_once|include)\s*\(\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: quality-assurance/$($file.Name):$($i+1) -> $path"
            }
        }
        elseif ($line -match '(require_once|require|include_once|include)\s+__DIR__\s*\.\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: quality-assurance/$($file.Name):$($i+1) -> __DIR__.$path"
            }
        }
    }
}

# Check sql/ files
$sqlFiles = Get-ChildItem -Path "$root\sql" -Filter "*.php" -Recurse -File -ErrorAction SilentlyContinue
foreach ($file in $sqlFiles) {
    $content = Get-Content $file.FullName
    for ($i = 0; $i -lt $content.Count; $i++) {
        $line = $content[$i]
        if ($line -match '(require_once|require|include_once|include)\s*\(\s*[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            $fullPath = Join-Path $file.DirectoryName $path
            if (-not (Test-Path $fullPath)) {
                $broken += "BROKEN: sql/$($file.Name):$($i+1) -> $path"
            }
        }
        elseif ($line -match '(require_once|require|include_once|include)\s+[''"]([^''"]+)[''"]') {
            $path = $Matches[2]
            # Check if it's an absolute path
            if ($path -match '^[A-Z]:\\') {
                if (-not (Test-Path $path)) {
                    $broken += "BROKEN: sql/$($file.Name):$($i+1) -> $path (absolute)"
                }
            } else {
                $fullPath = Join-Path $file.DirectoryName $path
                if (-not (Test-Path $fullPath)) {
                    $broken += "BROKEN: sql/$($file.Name):$($i+1) -> $path"
                }
            }
        }
    }
}

if ($broken.Count -eq 0) {
    Write-Host "All paths are valid!"
} else {
    Write-Host "Found $($broken.Count) broken paths:"
    $broken | ForEach-Object { Write-Host $_ }
}
