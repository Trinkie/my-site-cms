<?php
require __DIR__ . '/config.php';
require_login();

$u = current_user();
if (!$u) { header('Location: logout.php'); exit; }

$st = db()->prepare('SELECT id, title, status, comment, updated_at, created_at FROM orders WHERE user_id = ? ORDER BY id DESC');
$st->execute([$u['id']]);
$orders = $st->fetchAll();

function status_ru(string $s): string {
  $map = [
    'new' => 'Новый',
    'modeling' => 'Моделирование',
    'printing' => 'Печать',
    'post' => 'Постобработка',
    'ready' => 'Готово',
    'delivered' => 'Выдано',
    'canceled' => 'Отменён'
  ];
  return $map[$s] ?? $s;
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Мой профиль</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:900px;margin:30px auto;padding:0 16px}
    .top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}
    .card{border:1px solid #ddd;border-radius:12px;padding:14px;margin:12px 0}
    .badge{display:inline-block;padding:4px 10px;border-radius:999px;background:#e9f8ef;color:#0b3b1e;font-weight:700}
    .muted{color:#666}
    a{color:#00AE42}
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1>Мои заказы</h1>
      <div class="muted">Вы вошли как: <?=e($u['email'])?></div>
    </div>
    <div>
      <?php if ((int)$u['is_admin'] === 1): ?>
        <a href="admin/orders.php">Админка</a> |
      <?php endif; ?>
      <a href="logout.php">Выйти</a>
    </div>
  </div>

  <?php if (!$orders): ?>
    <div class="card">
      Пока нет заказов. Если вы уже оставляли заявку — напишите нам, и мы привяжем заказ к вашему email.
    </div>
  <?php endif; ?>

  <?php foreach ($orders as $o): ?>
    <div class="card">
      <div><b>Заказ #<?=e((string)$o['id'])?></b> — <span class="badge"><?=e(status_ru((string)$o['status']))?></span></div>
      <div class="muted" style="margin-top:6px">Обновлено: <?=e((string)$o['updated_at'])?></div>
      <div style="margin-top:10px"><b>Описание:</b> <?=e((string)$o['title'])?></div>
      <?php if (!empty($o['comment'])): ?>
        <div style="margin-top:10px"><b>Комментарий:</b> <?=nl2br(e((string)$o['comment']))?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</body>
</html>
