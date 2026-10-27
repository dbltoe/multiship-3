# Lints every plugin PHP file and runs tests/compat_check.php (all three modes) on each PHP
# build the plugin supports, using the zclab rig's portable interpreters.
#
#   powershell -ExecutionPolicy Bypass -File tests\run_all.ps1
#
# Exit code 0 only if every lint and every check passed on every build.
$ErrorActionPreference = 'Stop'
$repo   = Split-Path $PSScriptRoot -Parent
$check  = Join-Path $PSScriptRoot 'compat_check.php'
$files  = Get-ChildItem (Join-Path $repo 'zc_plugins') -Recurse -Filter *.php | ForEach-Object FullName
$builds = 'php80', 'php81', 'php82', 'php83', 'php84', 'php85'
$failed = @()

foreach ($b in $builds) {
    $php = "F:\zclab\php\$b\php.exe"
    if (-not (Test-Path $php)) { Write-Host "skip $b (not installed)"; continue }

    $lintErrors = 0
    foreach ($f in $files) {
        $out = & $php -n -l $f 2>&1
        if ($LASTEXITCODE -ne 0) { $lintErrors++; Write-Host "$b LINT FAIL $f`n$out" }
    }
    if ($lintErrors) { $failed += "$b lint" }
    Write-Host "$b lint: $($files.Count) files, $lintErrors errors"

    foreach ($mode in 'static', 'loader-old', 'loader-new', 'manifest') {
        $out = & $php -n $check $mode 2>&1
        $code = $LASTEXITCODE
        ($out | Select-Object -Last 1) | ForEach-Object { Write-Host "$b ${mode}: $_" }
        if ($code -ne 0) { $failed += "$b $mode"; $out | Where-Object { $_ -match 'FAIL' } | ForEach-Object { Write-Host "   $_" } }
    }
}

if ($failed.Count) { Write-Host "FAILED: $($failed -join ', ')"; exit 1 }
Write-Host 'ALL GREEN'
exit 0
