<?php
require __DIR__ . '/config.php';

$err = '';
$info = '';
if (isset($_GET['registered'])) $info = 'Аккаунт создан. Теперь войдите.';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  $st = db()->prepare('SELECT id, pass_hash FROM users WHERE email = ?');
  $st->execute([$email]);
  $u = $st->fetch();

  if (!$u || !password_verify($pass, $u['pass_hash'])) {
    $err = 'Неверный email или пароль.';
  } else {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    header('Location: profile.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Вход</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:520px;margin:40px auto;padding:0 16px}
    input,button{width:100%;padding:12px;margin:8px 0}
    .err{background:#ffe2e2;padding:10px;border-radius:8px}
    .ok{background:#e6ffe9;padding:10px;border-radius:8px}
    a{color:#00AE42}
  </style>
</head>
<body>
  <h1>Вход</h1>
  <?php if ($info): ?><div class="ok"><?=e($info)?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?=e($err)?></div><?php endif; ?>

  <form method="post">
    <label>Email</label>
    <input name="email" type="email" required value="<?=e($_POST['email'] ?? '')?>">

    <label>Пароль</label>
    <input name="password" type="password" required>

    <button type="submit">Войти</button>
  </form>

  <p>Нет аккаунта? <a href="register.php">Регистрация</a></p>
</body>
</html>

