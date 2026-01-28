<?php
require __DIR__ . '/../config.php';
require_login();

$u = current_user();
if (!$u || (int)$u['is_admin'] !== 1) { http_response_code(403); echo 'Нет доступа'; exit; }

$statuses = ['new','modeling','printing','post','ready','delivered','canceled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $status = (string)($_POST['status'] ?? '');
  $comment = trim((string)($_POST['comment'] ?? ''));

  if ($id > 0 && in_array($status, $statuses, true)) {
    $st = db()->prepare('UPDATE orders SET status=?, comment=? WHERE id=?');
    $st->execute([$status, $comment !== '' ? $comment : null, $id]);
  }
  header('Location: orders.php');
  exit;
}

$st = db()->query('
  SELECT o.id, o.title, o.status, o.comment, o.updated_at, u.email
  FROM orders o
  JOIN users u ON u.id = o.user_id
  ORDER BY o.id DESC
');
$orders = $st->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Админка — Заказы</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:1100px;margin:30px auto;padding:0 16px}
    .card{border:1px solid #ddd;border-radius:12px;padding:14px;margin:12px 0}
    select,textarea,button{padding:10px;margin-top:8px;width:100%}
    .row{display:grid;grid-template-columns:1fr 180px;gap:12px}
    a{color:#00AE42}
  </style>
</head>
<body>
  <h1>Админка — Заказы</h1>
  <p>
    <a href="../profile.php">Профиль</a> |
    <a href="add_order.php">Добавить заказ</a> |
    <a href="../logout.php">Выйти</a>
  </p>

  <?php foreach ($orders as $o): ?>
    <div class="card">
      <div><b>#<?=e((string)$o['id'])?></b> — <?=e((string)$o['email'])?></div>
      <div style="margin-top:6px"><b>Описание:</b> <?=e((string)$o['title'])?></div>
      <div style="margin-top:6px;color:#666">Обновлено: <?=e((string)$o['updated_at'])?></div>

      <form method="post" class="row" style="margin-top:10px">
        <input type="hidden" name="id" value="<?=e((string)$o['id'])?>">
        <div>
          <label>Комментарий клиенту (необязательно)</label>
          <textarea name="comment" rows="3"><?=e((string)($o['comment'] ?? ''))?></textarea>
        </div>
        <div>
          <label>Статус</label>
          <select name="status">
            <?php foreach (['new'=>'Новый','modeling'=>'Моделирование','printing'=>'Печать','post'=>'Постобработка','ready'=>'Готово','delivered'=>'Выдано','canceled'=>'Отменён'] as $k=>$v): ?>
              <option value="<?=e($k)?>" <?=$o['status']===$k?'selected':''?>><?=e($v)?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Сохранить</button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
</body>
</html>

