# Lints every PHP file (the plugin's and the harnesses') and runs tests/compat_check.php (all
# four modes) and tests/readme_check.php on each PHP build the plugin supports, using the zclab
# rig's portable interpreters.
#
#   powershell -ExecutionPolicy Bypass -File tests\run_all.ps1
#
# Exit code 0 only if every lint and every check passed on every build. A harness passes only
# when it exits 0 and its last line is its own PASS line.
$ErrorActionPreference = 'Stop'
$repo   = Split-Path $PSScriptRoot -Parent
$check  = Join-Path $PSScriptRoot 'compat_check.php'
$readme = Join-Path $PSScriptRoot 'readme_check.php'
$files  = @(Get-ChildItem (Join-Path $repo 'zc_plugins') -Recurse -Filter *.php | ForEach-Object FullName)
$files += @(Get-ChildItem $PSScriptRoot -Filter *.php | ForEach-Object FullName)
$builds = 'php80', 'php81', 'php82', 'php83', 'php84', 'php85'
$failed = @()
$ran    = 0

foreach ($b in $builds) {
    $php = "F:\zclab\php\$b\php.exe"
    if (-not (Test-Path $php)) { Write-Host "skip $b (not installed)"; continue }
    $ran++

    $lintErrors = 0
    foreach ($f in $files) {
        $out = & $php -n -l $f 2>&1
        if ($LASTEXITCODE -ne 0) { $lintErrors++; Write-Host "$b LINT FAIL $f`n$out" }
    }
    if ($lintErrors) { $failed += "$b lint" }
    Write-Host "$b lint: $($files.Count) files, $lintErrors errors"

    $runs = @()
    foreach ($mode in 'static', 'loader-old', 'loader-new', 'manifest') { $runs += , @($mode, $check, $mode) }
    $runs += , @('readme', $readme, '')
    foreach ($run in $runs) {
        $name = $run[0]
        if ($run[2]) { $out = & $php -n $run[1] $run[2] 2>&1 } else { $out = & $php -n $run[1] 2>&1 }
        $code = $LASTEXITCODE
        $last = "$($out | Select-Object -Last 1)"
        Write-Host "$b ${name}: $last"
        if ($code -ne 0 -or $last -notmatch '^PASS ') {
            $failed += "$b $name"
            $out | Where-Object { $_ -match 'FAIL|Warning|Deprecated|Fatal|Parse error' } | ForEach-Object { Write-Host "   $_" }
        }
    }
}

if ($failed.Count) { Write-Host "FAILED: $($failed -join ', ')"; exit 1 }
if ($ran -eq 0) { Write-Host 'FAILED: no PHP build found under F:\zclab\php'; exit 1 }
Write-Host "ALL GREEN ($ran PHP builds x 5 harness runs, $($files.Count) files linted on each)"
exit 0
