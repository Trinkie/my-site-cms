<?php
declare(strict_types=1);

ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.cookie_httponly', '1'); // норм защита от XSS-воровства cookie
// Если сайт на https, включи:
// ini_set('session.cookie_secure', '1');

session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'ca091776_trinkie';
const DB_USER = 'ca091776_trinkie';
const DB_PASS = 'Zehopa40';

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  return $pdo;
}

function is_logged_in(): bool {
  return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}

function current_user(): ?array {
  if (!is_logged_in()) return null;
  $st = db()->prepare('SELECT id, email, name, is_admin FROM users WHERE id = ?');
  $st->execute([$_SESSION['user_id']]);
  $u = $st->fetch();
  return $u ?: null;
}

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
