<?php
require __DIR__ . '/../config.php';
require_login();

$u = current_user();
if (!$u || (int)$u['is_admin'] !== 1) { http_response_code(403); echo 'Нет доступа'; exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $title = trim((string)($_POST['title'] ?? ''));
  $status = (string)($_POST['status'] ?? 'new');

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Введите корректный email клиента.';
  } elseif ($title === '') {
    $err = 'Введите описание заказа.';
  } else {
    $st = db()->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();
    if (!$user) {
      $err = 'Пользователь с таким email не найден (пусть клиент сначала зарегистрируется).';
    } else {
      $st2 = db()->prepare('INSERT INTO orders (user_id, title, status) VALUES (?, ?, ?)');
      $st2->execute([(int)$user['id'], $title, $status]);
      header('Location: orders.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Добавить заказ</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:700px;margin:30px auto;padding:0 16px}
    input,select,textarea,button{width:100%;padding:12px;margin:8px 0}
    .err{background:#ffe2e2;padding:10px;border-radius:8px}
    a{color:#00AE42}
  </style>
</head>
<body>
  <h1>Добавить заказ</h1>
  <p><a href="orders.php">← Назад</a></p>

  <?php if ($err): ?><div class="err"><?=e($err)?></div><?php endif; ?>

  <form method="post">
    <label>Email клиента (должен быть зарегистрирован)</label>
    <input name="email" type="email" required value="<?=e($_POST['email'] ?? '')?>">

    <label>Описание/название заказа</label>
    <textarea name="title" rows="4" required><?=e($_POST['title'] ?? '')?></textarea>

    <label>Статус</label>
    <select name="status">
      <option value="new">Новый</option>
      <option value="modeling">Моделирование</option>
      <option value="printing">Печать</option>
      <option value="post">Постобработка</option>
      <option value="ready">Готово</option>
      <option value="delivered">Выдано</option>
      <option value="canceled">Отменён</option>
    </select>

    <button type="submit">Создать</button>
  </form>
</body>
</html>

