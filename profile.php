<?php
require __DIR__ . '/config.php';
require_login();

$authLinksHtml = auth_links_html();
$u = current_user();
if (!$u) { header('Location: logout.php'); exit; }

$st = db()->prepare('
  SELECT id, title, status, comment, filament_name, material, strength_needed, client_contact, updated_at, created_at
  FROM orders
  WHERE user_id = ?
  ORDER BY id DESC
');
$st->execute([(int)$u['id']]);
$orders = $st->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>3DOPE — Профиль</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section>
    <div class="card">
      <h2>Профиль</h2>
      <p>Ваши заказы и статусы выполнения.</p>

      <div class="content-item" style="margin-top:1.2rem">
        <div><b>Email:</b> <?=e((string)$u['email'])?></div>
        <?php if (!empty($u['name'])): ?><div><b>Имя:</b> <?=e((string)$u['name'])?></div><?php endif; ?>
        <div style="margin-top:0.8rem">
          <a class="btn" href="contacts.php" style="margin-top:0">Создать новый заказ</a>
        </div>
      </div>

      <?php if (!$orders): ?>
        <div class="content-item">
          Пока нет заказов. Нажмите “Создать новый заказ”.
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Статус</th>
              <th>Детали</th>
              <th>Обновлено</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td><b><?=e((string)$o['id'])?></b></td>
                <td>
                  <span class="badge <?=e(status_class((string)$o['status']))?>">
                    <?=e(status_label((string)$o['status']))?>
                  </span>
                </td>
                <td>
                  <div><b>Описание:</b> <?=e((string)$o['title'])?></div>
                  <?php if (!empty($o['filament_name'])): ?><div><b>Филамент:</b> <?=e((string)$o['filament_name'])?></div><?php endif; ?>
                  <?php if (!empty($o['material'])): ?><div><b>Материал:</b> <?=e((string)$o['material'])?></div><?php endif; ?>
                  <div><b>Прочность:</b> <?=((int)$o['strength_needed'] === 1) ? 'Повышенная' : 'Обычная'?></div>
                  <?php if (!empty($o['client_contact'])): ?><div><b>Контакт:</b> <?=e((string)$o['client
