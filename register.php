<?php
require __DIR__ . '/config.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $name  = trim((string)($_POST['name'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Введите корректный email.';
  } elseif (mb_strlen($pass) < 6) {
    $err = 'Пароль минимум 6 символов.';
  } else {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    try {
      $st = db()->prepare('INSERT INTO users (email, pass_hash, name) VALUES (?, ?, ?)');
      $st->execute([$email, $hash, $name !== '' ? $name : null]);
      header('Location: login.php?registered=1');
      exit;
    } catch (PDOException $e) {
      // Показываем реальную причину (на время!)
      $err = 'Ошибка БД: ' . $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Регистрация</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:520px;margin:40px auto;padding:0 16px}
    input,button{width:100%;padding:12px;margin:8px 0}
    .err{background:#ffe2e2;padding:10px;border-radius:8px}
    a{color:#00AE42}
  </style>
</head>
<body>
  <h1>Регистрация</h1>
  <?php if ($err): ?><div class="err"><?=e($err)?></div><?php endif; ?>

  <form method="post">
    <label>Email</label>
    <input name="email" type="email" required value="<?=e($_POST['email'] ?? '')?>">

    <label>Имя (необязательно)</label>
    <input name="name" type="text" value="<?=e($_POST['name'] ?? '')?>">

    <label>Пароль</label>
    <input name="password" type="password" required>

    <button type="submit">Создать аккаунт</button>
  </form>

  <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
</body>
</html>
