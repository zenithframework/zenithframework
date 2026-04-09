$directories = @(
    'c:\Users\shamimstack\Desktop\herd\zen\core',
    'c:\Users\shamimstack\Desktop\herd\zen\app',
    'c:\Users\shamimstack\Desktop\herd\zen\tests',
    'c:\Users\shamimstack\Desktop\herd\zen\database',
    'c:\Users\shamimstack\Desktop\herd\zen\boot',
    'c:\Users\shamimstack\Desktop\herd\zen\routes',
    'c:\Users\shamimstack\Desktop\herd\zen\docs'
)

$updatedFiles = @()

foreach ($dir in $directories) {
    if (Test-Path $dir) {
        $files = Get-ChildItem -Path $dir -Recurse -Filter '*.php' -File
        foreach ($file in $files) {
            $content = Get-Content $file.FullName -Raw
            if ($content -match '\\Zen\\') {
                $newContent = $content -replace '\\Zen\\', '\Zenith\'
                Set-Content -Path $file.FullName -Value $newContent -NoNewline
                $updatedFiles += $file.FullName
            }
        }
    }
}

Write-Output "Updated $($updatedFiles.Count) files:"
$updatedFiles | ForEach-Object { Write-Output $_ }
