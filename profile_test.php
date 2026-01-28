<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/config.php';

echo "<pre>PHP OK\n";
echo "Logged in: " . (is_logged_in() ? "yes" : "no") . "\n";

try {
  $v = db()->query('SELECT DATABASE() as db, USER() as user, VERSION() as ver')->fetch();
  print_r($v);
} catch (Throwable $e) {
  echo "DB FAIL: " . $e->getMessage() . "\n";
}

echo "Including nav...\n";
require __DIR__ . '/partials/nav.php';
echo "\nNav include OK\n</pre>";
