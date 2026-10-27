<?php
// -----
// Zen Cart version-compatibility checks for the free Multiple Ship-To Addresses plugin.
//
// Added in v3.0.3, for the three faults the zclab rig found in v3.0.2 on 2026-10-20. Each check
// reproduces the core behavior that caused a fault and asserts the plugin now survives it, so a
// regression fails here before it reaches a store.
//
//   php tests/compat_check.php                  every check except the include_path pair
//   php tests/compat_check.php loader-old       include_path fallback, 2.0/2.1-style loader
//   php tests/compat_check.php loader-new       include_path untouched, 2.2+-style loader
//
// tests/run_all.ps1 runs all three on every PHP build in F:\zclab\php. Exit code 0 = pass.
//
$plugin_root = dirname(__DIR__) . '/zc_plugins/MultiShip';
$versions = glob($plugin_root . '/v*', GLOB_ONLYDIR);
if (count($versions) !== 1) {
    fwrite(STDERR, "Expected exactly one version folder under $plugin_root\n");
    exit(1);
}
$ver_dir = $versions[0];
$ver = basename($ver_dir);
$catalog = $ver_dir . '/catalog/';

$failures = 0;
$passes = 0;
function check(bool $ok, string $what): void
{
    global $failures, $passes;
    if ($ok) {
        $passes++;
        echo "  ok   $what\n";
    } else {
        $failures++;
        echo "  FAIL $what\n";
    }
}
function finish(): void
{
    global $failures, $passes;
    echo ($failures === 0 ? 'PASS' : 'FAIL') . " ($passes passed, $failures failed) on PHP " . PHP_VERSION . "\n";
    exit($failures === 0 ? 0 : 1);
}

// -----
// The core pages this plugin adds files to; everything else under modules/pages is its own.
//
$core_pages = [
    'account', 'account_history', 'account_history_info', 'checkout_confirmation',
    'checkout_payment', 'checkout_shipping', 'checkout_success', 'login',
];

$mode = $argv[1] ?? 'static';

if ($mode === 'manifest') {
    // -----
    // 7. v3.0.5: Plugin Manager's row is ReadMe | Settings | Forum Thread | GitHub (dbltoe,
    // 2026-10-27), with Settings only while the configMultiship admin page is registered, and the
    // installer re-saves the description at Install, Upgrade and Uninstall (v2.0/v2.1 store it once).
    //
    final class MultishipFakeDb
    {
        public $row;
        public $sql = '';
        public function Execute($sql)
        {
            $this->sql = $sql;
            return $this->row === null
                ? (object)['EOF' => true, 'fields' => []]
                : (object)['EOF' => false, 'fields' => $this->row];
        }
    }
    define('IS_ADMIN_FLAG', true);
    define('TABLE_ADMIN_PAGES', 'zen_admin_pages');
    define('DIR_WS_CATALOG', '/shop/');
    define('FILENAME_CONFIGURATION', 'configuration');
    $row_labels = static function (array $m): string {
        preg_match_all('/role="button"[^>]*>([^<]+)<\/a>/', $m['pluginDescription'], $l);
        return implode('|', $l[1]);
    };
    $GLOBALS['db'] = new MultishipFakeDb();
    $m_before = require $ver_dir . '/manifest.php';
    check($row_labels($m_before) === 'ReadMe|Forum Thread|GitHub', 'not installed: ReadMe | Forum Thread | GitHub (' . $row_labels($m_before) . ')');
    check(strpos($GLOBALS['db']->sql, "page_key = 'configMultiship'") !== false && strpos($GLOBALS['db']->sql, 'zen_admin_pages') !== false, 'Settings is looked up by page key configMultiship');
    $GLOBALS['db']->row = ['main_page' => 'FILENAME_CONFIGURATION', 'page_params' => 'gID=42'];
    $m_after = require $ver_dir . '/manifest.php';
    check($row_labels($m_after) === 'ReadMe|Settings|Forum Thread|GitHub', 'installed: ReadMe | Settings | Forum Thread | GitHub (' . $row_labels($m_after) . ')');
    check(strpos($m_after['pluginDescription'], 'href="index.php?cmd=configuration&amp;gID=42"') !== false, 'Settings links relatively to this store\'s group');
    check(strpos($m_after['pluginDescription'], '/shop/zc_plugins/MultiShip/' . $ver . '/docs/readme.html') !== false, 'ReadMe points at this version\'s readme');
    $installer = file_get_contents($ver_dir . '/Installer/ScriptedInstaller.php');
    foreach (['executeInstall', 'executeUpgrade', 'executeUninstall'] as $method) {
        preg_match('/function ' . $method . '\(.*?\r?\n    \}\r?\n/s', $installer, $body);
        check(isset($body[0]) && strpos($body[0], '$this->refreshPluginDescription();') !== false, "$method() re-saves the description");
    }
    check(strpos($installer, "WHERE unique_key = 'MultiShip'") !== false, 'the description is saved to the MultiShip row');

    finish();
}

if ($mode === 'loader-old' || $mode === 'loader-new') {
    // -----
    // Stand-ins for the two generations of core's page loader: 2.2.0 added
    // listModulePagesFiles(), and its presence is what the plugin keys on.
    //
    if ($mode === 'loader-old') {
        eval('namespace Zencart\PageLoader; class PageLoader { public function getTemplatePart() {} }');
    } else {
        eval('namespace Zencart\PageLoader; class PageLoader { public function listModulePagesFiles() {} }');
    }
    eval('class base { public function attach($o, $e) {} }');
    require $catalog . 'includes/classes/observers/class.multiship_early_observer.php';

    // A store root with none of the plugin's page files, as 2.0/2.1 core has none of them.
    $store = sys_get_temp_dir() . '/multiship_compat_' . getmypid();
    @mkdir($store . '/includes/modules/pages/login', 0777, true);
    file_put_contents($store . '/includes/modules/pages/login/header_php.php', "<?php\n");
    chdir($store);

    $before = get_include_path();
    $m = new ReflectionMethod('multiship_early_observer', 'letOlderLoadersFindPageFiles');
    $m->setAccessible(true);
    $m->invoke(null);
    $m->invoke(null);   // a second instantiation must not add the entry twice
    $after = get_include_path();

    echo "$ver, $mode\n";
    if ($mode === 'loader-new') {
        check($after === $before, 'a 2.2+ loader leaves include_path untouched');
    } else {
        $entries = explode(PATH_SEPARATOR, $after);
        $plugin_entry = realpath($catalog);
        check(end($entries) !== false && realpath(end($entries)) === $plugin_entry, 'plugin catalog appended LAST to include_path');
        check(count(array_filter($entries, fn($e) => realpath($e) === $plugin_entry)) === 1, 'appended once, not once per instantiation');

        // Exactly the path index.php / html_header.php build: $code_page_directory . '/' . $value
        foreach ($core_pages as $page) {
            foreach (glob($catalog . "includes/modules/pages/$page/*.php") as $file) {
                $rel = "includes/modules/pages/$page/" . basename($file);
                $resolved = stream_resolve_include_path($rel);
                check($resolved !== false && realpath($resolved) === realpath($file), "2.0/2.1 require of $rel finds the plugin's copy");
            }
        }
        $resolved = stream_resolve_include_path('includes/modules/pages/login/header_php.php');
        check($resolved !== false && realpath($resolved) === realpath($store . '/includes/modules/pages/login/header_php.php'), "a file core has still resolves to core's copy");
    }
    unlink($store . '/includes/modules/pages/login/header_php.php');
    finish();
}

echo "$ver, static checks\n";

// -----
// 1. Zen Cart 2.0.x: every storefront 'class' loader entry must name a relative classPath.
//
// 2.0's InitSystem::processAutoTypeClass() otherwise starts from DIR_FS_CATALOG . DIR_WS_CLASSES
// and its FileSystem::getRelativeDir() cannot strip the prefix (isCatalogDir() is inverted), so
// the plugin file is never found. Reproduced below with 2.0's own logic.
//
define('IS_ADMIN_FLAG', false);
define('DIR_FS_CATALOG', '/store/');
define('DIR_WS_INCLUDES', 'includes/');
define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
function zc200_isCatalogDir(string $p): bool
{
    return str_replace(DIR_FS_CATALOG, '', $p) === $p;   // sic: inverted, as in 2.0.x
}
function zc200_getRelativeDir(string $p): string
{
    return zc200_isCatalogDir($p) ? str_replace(DIR_FS_CATALOG, '', $p) : $p;
}
$autoLoadConfig = [];
foreach (glob($catalog . 'includes/auto_loaders/*.php') as $loader) {
    require $loader;
}
$class_entries = 0;
foreach ($autoLoadConfig as $point => $entries) {
    foreach ($entries as $entry) {
        if ($entry['autoType'] !== 'class') {
            continue;
        }
        $class_entries++;
        $start = $entry['classPath'] ?? (DIR_FS_CATALOG . DIR_WS_CLASSES);   // 2.0.x
        $actual = $catalog . zc200_getRelativeDir($start) . $entry['loadFile'];
        check(isset($entry['classPath']) && $entry['classPath'][0] !== '/', "[$point] {$entry['loadFile']} names a relative classPath");
        check(is_file($actual), "[$point] {$entry['loadFile']} is found by 2.0.x's lookup");
    }
}
check($class_entries === 3, "three storefront class entries ($class_entries found)");

// -----
// 2. No plugin catalog file may share a path with a core file, or the include_path fallback
//    could shadow core on a host whose include_path lacks '.'. Checked against the rig's
//    2.0 and 2.1 checkouts when they are present.
//
foreach (['F:/zclab/sites/zc200/', 'F:/zclab/sites/zc210/'] as $core) {
    if (!is_dir($core)) {
        echo "  skip collision check, $core not present\n";
        continue;
    }
    $collisions = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($catalog, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($catalog)));
        if (file_exists($core . $rel)) {
            $collisions[] = $rel;
        }
    }
    check($collisions === [], "no plugin catalog file shadows a core file in $core" . ($collisions ? ': ' . implode(', ', $collisions) : ''));
}

// -----
// 3. The multiship invoice and packing slip must turn away an ordinary or missing order before
//    anything reads the multiship or address data such an order does not have.
//
foreach (['invoice_multiship.php', 'packingslip_multiship.php'] as $page) {
    // Comment lines dropped, so a comment that names those keys is not mistaken for a use.
    $src = preg_replace('~^\s*//.*$~m', '', file_get_contents($ver_dir . '/admin/' . $page));
    $guard = strpos($src, "if (empty(\$order->info['is_multiship_order']))");
    $first_use = min(array_filter([
        strpos($src, '$order->billing['),
        strpos($src, '$order->delivery['),
        strpos($src, "\$order->info['multiship_info']"),
    ], fn($p) => $p !== false));
    check($guard !== false && $guard < $first_use, "$page guards the order before its first use");
    $zero_guard = strpos($src, 'if ($oID <= 0) {');
    check($zero_guard !== false && $zero_guard < strpos($src, 'new order($oID)'), "$page refuses a missing oID before building an order (new order(0) builds from the cart)");
    check(strpos($src, "\$_GET['oID'])") === false, "$page does not read \$_GET['oID'] unguarded");
}

// -----
// 4. Version bookkeeping: folder, manifest and both copies of the readme agree.
//
$manifest = file_get_contents($ver_dir . '/manifest.php');
check(strpos($manifest, "\$multiship_version = '$ver';") !== false, "manifest version is $ver");
$readme_root = file_get_contents(dirname(__DIR__) . '/docs/readme.html');
$readme_plugin = file_get_contents($ver_dir . '/docs/readme.html');
check($readme_root === $readme_plugin, 'docs/readme.html and the plugin copy are identical');
check(strpos($readme_plugin, 'Version ' . substr($ver, 1) . '</span>') !== false, 'readme title carries ' . $ver);
check(strpos($readme_plugin, '<li>' . $ver . ', ') !== false, 'readme changelog has a ' . $ver . ' entry');

// -----
// 5. v3.0.4: Zen Cart 3.0.0 stays listed (John: every plugin targets 1.5.8-3.0.0), and no
// plugin file calls zen_db_output(), which core marks deprecated on every version from 2.0
// (it logged a notice per status comment on the multiship invoice and packing slip).
//
check(preg_match("/'zcVersions' => \[[^\]]*'v3\.0\.0'/", $manifest) === 1, "manifest zcVersions include v3.0.0");
$deprecated_calls = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ver_dir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->getExtension() === 'php' && preg_match('/\bzen_db_output\s*\(/', file_get_contents($file->getPathname()))) {
        $deprecated_calls[] = substr($file->getPathname(), strlen($ver_dir) + 1);
    }
}
check($deprecated_calls === [], 'no plugin file calls the deprecated zen_db_output()' . ($deprecated_calls ? ' (' . implode(', ', $deprecated_calls) . ')' : ''));

// -----
// 6. v3.0.4: checkoutInitialize() builds order_total once and refreshes its module objects for
// each later address. A "new order_total" per address priced every address after the first to
// nothing on Zen Cart 2.1, whose language loader answers false for an already-loaded module file.
//
$ms_src = file_get_contents($catalog . 'includes/classes/class.multiship.php');
check(preg_match_all('/=\s*new\s+order_total\s*[;(]/', $ms_src) === 1, 'class.multiship.php creates order_total exactly once');
check(strpos($ms_src, 'if ($order_total_modules === null) {') !== false && strpos($ms_src, '$GLOBALS[$ot_class] = new $ot_class();') !== false, 'later addresses get fresh order-total module objects, not a new order_total');

finish();
