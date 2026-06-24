<?php
/**
 * Run: docker compose exec webserver php tests/cov_diag.php
 */

echo "=== Xdebug mode diagnostics ===\n";
echo "ini xdebug.mode   : " . ini_get('xdebug.mode') . "\n";
echo "env XDEBUG_MODE   : " . (getenv('XDEBUG_MODE') ?: '(not set)') . "\n";
echo "php ini files     : " . php_ini_loaded_file() . "\n";
echo "scanned ini files : " . php_ini_scanned_files() . "\n\n";

if (!extension_loaded('xdebug')) {
    die("Xdebug not loaded\n");
}

xdebug_start_code_coverage();
echo "coverage started  : " . (xdebug_code_coverage_started() ? 'YES' : 'NO') . "\n\n";

require_once __DIR__ . '/../vendor/autoload.php';
$user = new \App\Models\User(1, 'x', 'x@x.com', 'h', 'user');

$cov = xdebug_get_code_coverage(); // must read BEFORE stopping (stop clears by default)
xdebug_stop_code_coverage();

$filterPath = realpath(__DIR__ . '/../src');
echo "PHPUnit filter path: $filterPath\n";
echo "Total files tracked: " . count($cov) . "\n\n";

$srcFiles = array_filter(array_keys($cov), fn($f) => str_contains($f, '/src/'));
echo count($srcFiles) . " src/ file(s) tracked:\n";
foreach ($srcFiles as $f) {
    $match = str_starts_with($f, (string)$filterPath);
    echo ($match ? '[MATCH]' : '[MISS ]') . " $f\n";
}

if (empty($srcFiles)) {
    echo "All tracked files:\n";
    foreach (array_keys($cov) as $f) {
        echo "  $f\n";
    }
}
