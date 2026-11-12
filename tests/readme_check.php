<?php
// -----
// README.md, the admin ReadMe page that shows it, the manifest's ReadMe button and the license,
// for the free Multiple Ship-To Addresses plugin.
//
// Added in v3.0.6, when the public HTML readme under zc_plugins/ was replaced by README.md.
// README.md is Markdown, kept in the plugin's version folder and at the repository root
// (byte-identical). It isn't a public file: the ReadMe button in Plugin Manager opens it through
// admin/multiship_readme.php, behind the admin login, for superusers only (zencart/zencart#8031).
//
//   php tests/readme_check.php
//
// tests/run_all.ps1 runs it on every PHP build in F:\zclab\php. Exit code 0 = pass.
//
$repo = dirname(__DIR__);
$plugin_root = $repo . '/zc_plugins/MultiShip';
$versions = glob($plugin_root . '/v*', GLOB_ONLYDIR);
if (count($versions) !== 1) {
    fwrite(STDERR, "Expected exactly one version folder under $plugin_root\n");
    exit(1);
}
$ver_dir = $versions[0];
$ver = basename($ver_dir);

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
// Every file in the repository except git's own, as repository-relative paths.
//
function repo_files(string $root): array
{
    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
        if ($f->isFile() && strpos($rel, '.git/') !== 0) {
            $files[] = $rel;
        }
    }
    sort($files);
    return $files;
}

// -----
// Runs a snippet in a child PHP, so zen_is_superuser() and SUPERUSER_PROFILE can be defined (or
// left out) per case. A temp script, because escapeshellarg() strips double quotes on Windows.
//
function child_php(string $code): string
{
    $script = tempnam(sys_get_temp_dir(), 'msr');
    file_put_contents($script, '<?php ' . $code);
    $out = trim((string)shell_exec(escapeshellarg(PHP_BINARY) . ' -n ' . escapeshellarg($script) . ' 2>&1'));
    unlink($script);
    return $out;
}

echo "$ver, readme checks\n";

$all = repo_files($repo);
$readme = (string)@file_get_contents($ver_dir . '/README.md');
$readme_lines = explode("\n", $readme);

// -----
// 1. README.md, in two places, and no HTML readme left to be served from zc_plugins/.
//
check($readme !== '', 'README.md is in the plugin\'s version folder');
check(is_file($repo . '/README.md') && md5_file($repo . '/README.md') === md5_file($ver_dir . '/README.md'), 'the repository-root README.md is byte-identical to the plugin\'s');
$html_readmes = array_values(array_filter($all, fn($f) => preg_match('~(^|/)readme\.html?$~i', $f) === 1));
check($html_readmes === [], 'no readme.html anywhere (it was served publicly from zc_plugins/)' . ($html_readmes ? ': ' . implode(', ', $html_readmes) : ''));
check(!is_dir($ver_dir . '/docs'), 'the plugin ships no docs/ folder (it held only the HTML readme and its assets)');
$public_docs = array_values(array_filter($all, fn($f) => strpos($f, 'zc_plugins/') === 0 && preg_match('~\.(html?|md|txt)$~i', $f) === 1
    && !in_array(basename($f), ['README.md', 'index.html'], true)));
check($public_docs === [], 'no other document ships under zc_plugins/' . ($public_docs ? ': ' . implode(', ', $public_docs) : ''));
check(strpos($readme, "\r") === false, 'README.md uses LF line endings');

// -----
// 2. The top block, the version and the paths it quotes.
//
check(($readme_lines[0] ?? '') === '# Multiple Ship-To Addresses' && ($readme_lines[1] ?? 'x') === '', 'it opens with the plugin\'s name');
check(($readme_lines[2] ?? '') === 'Version ' . substr($ver, 1) . ' · Zen Cart 2.0.0 through 3.0.0 · PHP 8.0 through 8.5 · GPL v3 · by My Zen Cart Host (dbltoe)', 'its second line states version ' . substr($ver, 1) . ', the Zen Cart and PHP range, the license and the author');
$manifest_src = (string)file_get_contents($ver_dir . '/manifest.php');
check(strpos($manifest_src, "'zcVersions' => ['v2.0.0', 'v2.0.1', 'v2.1.0', 'v2.2.0', 'v2.2.1', 'v2.2.2', 'v2.3.0', 'v3.0.0'],") !== false, 'the Zen Cart range it states is the manifest\'s (v2.0.0 through v3.0.0)');
check(preg_match_all('~zc_plugins/MultiShip/(v[0-9.]+)~', $readme, $m) > 0 && array_unique($m[1]) === [$ver], 'every path it quotes uses this version folder');
check(preg_match_all('/^## /m', $readme) >= 10, 'it has at least 10 sections');
check(strpos($readme, 'Once it\'s installed, a superuser can open this guide from the **ReadMe** button in Plugin Manager.') !== false, 'it says where the guide is found once installed');
check(preg_match('/\n## Changelog\n.*\n## License\n[^#]+$/s', $readme) === 1, 'it ends with Changelog, then License');
check(preg_match('/^### ' . preg_quote($ver, '/') . "\n\nReleased \\d{4}-\\d\\d-\\d\\d\\.$/m", $readme) === 1, 'the changelog leads with a dated ' . $ver . ' entry');
foreach (['v3.0.5', 'v3.0.0', 'v2.0.0', 'v1.0.0 BETA 1'] as $old) {
    check(preg_match('/^### ' . preg_quote($old, '/') . '$/m', $readme) === 1, "the $old changelog entry is still there");
}

// -----
// 3. The subjects a store owner will look for.
//
foreach ([
    'One Page Checkout' => 'One Page Checkout',
    'Edit Orders' => 'Edit Orders',
    'free shipping on a split order' => 'free-shipping threshold',
    'coupons' => 'ot_coupon',
    'the three checkout steps' => 'Step 3',
    'the email banner' => 'EMAIL_LOGO_FILENAME',
    'product weights' => 'Product weights matter',
    'upgrading from v2.x' => 'Upgrading from v2.x',
    'what uninstalling destroys' => 'Read this before you uninstall',
    'the support thread' => 'https://www.zen-cart.com/threads/189154',
] as $label => $needle) {
    check(strpos($readme, $needle) !== false, "it covers $label");
}
$installer = (string)file_get_contents($ver_dir . '/Installer/ScriptedInstaller.php');
foreach (['Unsupported Payment Methods', 'Maximum Delivery Addresses', 'Enable debug?'] as $title) {
    check(strpos($installer, "('$title',") !== false && strpos($readme, "**$title**") !== false, "it names the setting \"$title\" as the installer does");
}

// -----
// 4. Only the Markdown the admin page's renderer understands. Fenced code blocks are skipped.
//
$prose = [];
$in_code = false;
foreach ($readme_lines as $line) {
    if (strpos(trim($line), '```') === 0) {
        $in_code = !$in_code;
        continue;
    }
    if (!$in_code) {
        $prose[] = $line;
    }
}
$prose_text = preg_replace('/`[^`]*`/', '', implode("\n", $prose));
check(preg_match('~</?[a-z][a-z0-9]*[\s>/]~i', $prose_text) === 0, 'no raw HTML');
check(preg_match('/\]\(/', $prose_text) === 0 && strpos($prose_text, '![') === false, 'no [text](url) links and no images');
check(preg_match('/^#{4,}/m', $prose_text) === 0, 'no heading deeper than ###');
check(preg_match('/^[ \t]+(-|\*|\d+\.)[ \t]/m', $prose_text) === 0 && preg_match('/^\*[ \t]/m', $prose_text) === 0, 'no nested lists, and only - bullets');

// -----
// 5. The license: GPL v3 everywhere, and other people's copyright lines still in place.
//
check(strpos($readme, 'GNU General Public License, version 3') !== false && strpos($readme, 'https://www.gnu.org/licenses/gpl-3.0.html') !== false, 'the readme names the license: GPL version 3, with the GNU URL');
$license = (string)@file_get_contents($repo . '/LICENSE.txt');
check(strpos($license, 'GNU GENERAL PUBLIC LICENSE') !== false && strpos($license, 'Version 3, 29 June 2007') !== false && strpos($license, 'Version 2, June 1991') === false, 'LICENSE.txt is the GNU GPL version 3 text');
check(strpos($readme, 'the full text is in `LICENSE.txt`') !== false && !is_file($repo . '/LICENSE'), 'the readme points at LICENSE.txt, the only license file');
$old_license = [];
$bad_tag = [];
$headers = ['vinos' => 0, 'zencart' => 0, 'oscommerce' => 0];
foreach ($all as $f) {
    if (preg_match('~\.(php|css|js|md|txt|html|ps1)$~i', $f) !== 1 || $f === 'LICENSE.txt' || $f === 'tests/readme_check.php') {
        continue;
    }
    $src = (string)file_get_contents($repo . '/' . $f);
    foreach (explode("\n", $src) as $i => $line) {
        // The changelog's own "was version 2" is the one place the old license is named.
        if (preg_match('~GPL[ -]?v?2|gpl-2\.0|zen-cart\.com/license|License V2|version 2(\.0)? of the GPL~i', $line) === 1) {
            $old_license[] = $f . ':' . ($i + 1);
        }
        if (strpos($line, '@license') !== false && strpos($f, 'zc_plugins/') === 0
            && strpos($line, '@license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0') === false) {
            $bad_tag[] = $f . ':' . ($i + 1);
        }
    }
    if (strpos($f, 'zc_plugins/') === 0) {
        $headers['vinos'] += preg_match_all('/Copyright (\(C\) )?2014-201[79], Vinos de Frutas Tropicales/', $src);
        $headers['zencart'] += preg_match_all('/Copyright (\(c\) 2003 The zen-cart developers|2003-201[01] Zen Cart Development Team)/', $src);
        $headers['oscommerce'] += preg_match_all('/Portions Copyright (\(c\) )?2003 osCommerce/', $src);
    }
}
check($old_license === [], 'no GPL v2 or zen-cart.com license mention is left' . ($old_license ? ': ' . implode(', ', array_slice($old_license, 0, 6)) : ''));
check($bad_tag === [], 'every @license line in the plugin is the GPL v3 one' . ($bad_tag ? ': ' . implode(', ', array_slice($bad_tag, 0, 6)) : ''));
check($headers === ['vinos' => 45, 'zencart' => 4, 'oscommerce' => 4], "the file headers keep their copyright lines: Vinos de Frutas Tropicales {$headers['vinos']}/45, Zen Cart {$headers['zencart']}/4, osCommerce {$headers['oscommerce']}/4");

// -----
// 6. The admin ReadMe page: login first, superusers only, strict headers, one local read, one
//    escaped output, no script. The page is generated; these pin what it must keep doing.
//
$page_file = $ver_dir . '/admin/multiship_readme.php';
$page = (string)@file_get_contents($page_file);
check($page !== '', 'admin/multiship_readme.php exists');
$admin_pages = array_map('basename', glob($ver_dir . '/admin/*.php'));
check(count($admin_pages) === count(array_unique(array_map('strtolower', $admin_pages))) && in_array('invoice_multiship.php', $admin_pages, true) && in_array('packingslip_multiship.php', $admin_pages, true), 'it sits beside the invoice and packing-slip pages without replacing either (' . implode(', ', $admin_pages) . ')');
$guard = strpos($page, "if (!defined('IS_ADMIN_FLAG'))");
$top = strpos($page, "require 'includes/application_top.php';");
$super = strpos($page, "if (!function_exists('zen_is_superuser') || !zen_is_superuser()) {");
check($guard !== false && $top !== false && $guard < $top, '404 guard, then application_top (the login check)');
check($super !== false && $super > $top && strpos($page, 'zen_redirect(zen_href_link(FILENAME_DENIED') > $super, 'superusers only, everyone else to the denied page');
foreach ([
    'HTML' => "header('Content-Type: text/html; charset=utf-8');",
    'nosniff' => "header('X-Content-Type-Options: nosniff');",
    'no framing' => "header('X-Frame-Options: DENY');",
    'a CSP with no scripts, no framing, sandboxed' => "header(\"Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'; sandbox allow-popups allow-popups-to-escape-sandbox\");",
    'no referrer' => "header('Referrer-Policy: no-referrer');",
    'no caching' => "header('Cache-Control: no-store, private');",
] as $what => $line) {
    check(strpos($page, $line) !== false, "it sends $what");
}
check(substr_count($page, 'file_get_contents(') === 1 && strpos($page, "\$multishipReadmeFile = dirname(__DIR__) . '/README.md';") !== false && strpos($page, 'file_get_contents($multishipReadmeFile)') !== false, 'it reads only the plugin\'s own README.md (one fixed local path)');
check(preg_match('~\$_(GET|POST|REQUEST|COOKIE|SERVER|FILES)\b~', $page) === 0, 'it takes no input from the request');
check(substr_count($page, 'echo ') === 1 && strpos($page, '<?php echo $multishipReadmeHtml; ?>') !== false && strpos($page, "\$text = htmlspecialchars((string)\$text, ENT_QUOTES, 'UTF-8');") !== false, 'it outputs only the escape-first Markdown render');
check(stripos($page, '<script') === false && preg_match('~\b(include|require)(_once)?\b~', str_replace("require 'includes/application_top.php';", '', $page)) === 0, 'it has no script and loads nothing but application_top');
check(strpos($page, '@license  https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0') !== false, 'it carries the GPL v3 license line');

// -----
// 7. The renderer itself: lift the two functions out of the page and run them on the guide.
//
$a = strpos($page, "if (!function_exists('multiship_markdown_html'))");
$b = strpos($page, '$multishipReadmeFile');
if ($a === false || $b === false) {
    check(false, 'the renderer can be lifted out of the page');
} else {
    eval(substr($page, $a, $b - $a));
    $html = multiship_markdown_html($readme);
    check(substr_count($html, '<h1>') === 1 && substr_count($html, '<h2>') === preg_match_all('/^## /m', $readme) && substr_count($html, '<h3>') === preg_match_all('/^### /m', $readme), 'the guide renders with every heading');
    check(strpos($html, '<li><strong>Unsupported Payment Methods</strong>') !== false && strpos($html, '<code>/zc_plugins/MultiShip/' . $ver . '/</code>') !== false, 'lists, bold and code render');
    check(preg_match('~<(?!/?(h[1-3]|p|ul|ol|li|blockquote|pre|code|table|tr|th|td|strong|em|a)[ >])~', $html) === 0, 'nothing in the render but the tags the renderer makes');
    check(preg_match_all('~<a ~', $html) > 0 && preg_match_all('~<a ~', $html) === preg_match_all('~<a href="https?://[^"]+" target="_blank" rel="noopener noreferrer">~', $html), 'every link opens in a new window with no opener or referrer');
    check(strpos($html, '**') === false && preg_match('/(^|[\s(])\*[A-Za-z]/', strip_tags($html)) === 0, 'no Markdown marker is left unrendered');
    $evil = multiship_markdown_html("# <script>alert(1)</script>\n\n<img src=x onerror=alert(1)> [x](javascript:alert(1))\n\n| <b>a</b> | `<i>` |\n| --- | --- |\n| \"onmouseover=\"x | javascript:alert(1) |\n\n```\n</code></pre><script>x</script>\n```\n");
    check(stripos($evil, '<script') === false && stripos($evil, '<img') === false && stripos($evil, '<b>') === false
        && stripos($evil, '<i>') === false && strpos($evil, 'href="javascript') === false && strpos($evil, '"onmouseover') === false, 'markup in the file comes out escaped, never live');
}

// -----
// 8. The manifest: the ReadMe button opens the admin page, for superusers only, and the manifest
//    still loads where Zen Cart 3.0.0 reads it, before admin access exists.
//
$manifest = require $ver_dir . '/manifest.php';
$desc = $manifest['pluginDescription'];
check($manifest['pluginVersion'] === $ver, 'pluginVersion matches the folder: ' . $manifest['pluginVersion']);
check($manifest['pluginAuthor'] === 'My Zen Cart Host (dbltoe)', 'the author is My Zen Cart Host (dbltoe)');
check(strpos($desc, '<a href="index.php?cmd=multiship_readme" target="_blank" rel="noopener noreferrer" class="btn btn-primary" role="button"') !== false, 'ReadMe opens index.php?cmd=multiship_readme in its own window, noopener noreferrer');
check(strpos($desc, 'readme.html') === false && strpos($desc, 'zc_plugins/') === false && strpos($desc, '/docs/') === false, 'the description links no public file');
check(strpos($manifest_src, 'zc_plugins/MultiShip/v') === false && strpos($manifest_src, 'DIR_WS_CATALOG') === false && strpos($manifest_src, '/docs/') === false, 'no version-folder path is built in the manifest');
check(preg_match_all('/role="button"[^>]*>([^<]+)<\/a>/', $desc, $labels) === 3 && $labels[1] === ['ReadMe', 'Forum Thread', 'GitHub'], 'outside an installed admin the row reads ReadMe | Forum Thread | GitHub');
check(substr_count($desc, 'target="_blank"') === 3 && substr_count($desc, 'rel="noopener noreferrer"') === 3, 'all three open a new window with rel=noopener noreferrer');
check(strpos($manifest_src, "if (function_exists('zen_is_superuser') && defined('SUPERUSER_PROFILE')) {") !== false && strpos($manifest_src, '$multiship_show_readme = zen_is_superuser();') !== false, 'the superuser question is asked only once admin access exists');
check(stripos($desc, 'lat9') === false && stripos($desc, 'Vinos') === false && stripos($desc, 'Originally') === false && strpos($desc, '<em>') === false, 'the description is the description and the buttons, nothing else');
libxml_use_internal_errors(true);
check(simplexml_load_string('<div>' . $desc . '</div>') !== false, 'the description\'s tags are balanced');
libxml_clear_errors();

$manifest_path = var_export($ver_dir . '/manifest.php', true);
$report = ' echo (strpos($m[\'pluginDescription\'], \'>ReadMe</a>\') === false ? \'hidden\' : \'shown\') . (strpos($m[\'pluginDescription\'], \'>GitHub</a>\') !== false ? \'+github\' : \'\');';
check(child_php('define(\'SUPERUSER_PROFILE\', 1); function zen_is_superuser() { return false; } $m = require ' . $manifest_path . ';' . $report) === 'hidden+github', 'a non-superuser sees no ReadMe button (the other buttons stay)');
check(child_php('define(\'SUPERUSER_PROFILE\', 1); function zen_is_superuser() { return true; } $m = require ' . $manifest_path . ';' . $report) === 'shown+github', 'a superuser sees it');
// Core's zen_is_superuser() dies on the undefined constant when a manifest is read that early.
check(child_php('function zen_is_superuser() { return 1 === SUPERUSER_PROFILE; } $m = require ' . $manifest_path . ';' . $report) === 'shown+github', 'the manifest loads before admin access exists (3.0.0 reads it on every admin page)');

finish();
