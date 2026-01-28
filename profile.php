<?php
require __DIR__ . '/config.php';
require_login();

$u = current_user();
if (!$u) { header('Location: logout.php'); exit; }

$st = db()->prepare('
  SELECT id, title, status, comment, filament_name, strength_needed, updated_at, created_at
  FROM orders
  WHERE user_id = ?
  ORDER BY id DESC
');
$st->execute([(int)$u['id']]);
$orders = $st->fetchAll();

function dot_class(string $s): string {
  // required: red(queue), yellow(printing), green(ready)
  if ($s === 'printing') return 'printing';
  if ($s === 'ready' || $s === 'delivered') return 'ready';
  if ($s === 'canceled') return 'canceled';
  return 'queue';
}
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

      <div class="content-item" style="margin-top:1.2rem; text-align:left">
        <div><b>Email:</b> <?=e((string)$u['email'])?></div>
        <?php if (!empty($u['name'])): ?><div><b>Имя:</b> <?=e((string)$u['name'])?></div><?php endif; ?>
        <div style="margin-top:0.8rem">
          <a class="btn" href="contacts.php" style="margin-top:0">Создать новый заказ</a>
        </div>
      </div>

      <div class="orders-wrap">
        <?php if (!$orders): ?>
          <div class="content-item">Пока нет заказов.</div>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
            <div class="order-card">
              <div class="order-dot <?=e(dot_class((string)$o['status']))?>"></div>

              <div>
                <div class="order-head">
                  <div class="order-id">Заказ #<?=e((string)$o['id'])?></div>
                  <div class="order-status"><?=e(status_label((string)$o['status']))?></div>
                </div>

                <div class="order-body">
                  <div><b>Описание:</b> <?=e((string)$o['title'])?></div>
                  <?php if (!empty($o['filament_name'])): ?><div><b>Филамент:</b> <?=e((string)$o['filament_name'])?></div><?php endif; ?>
                  <div><b>Прочность:</b> <?=((int)$o['strength_needed'] === 1) ? 'Повышенная' : 'Обычная'?></div>

                  <?php if (!empty($o['comment'])): ?>
                    <div class="order-meta" style="margin-top:6px"><b>Примечание:</b> <?=nl2br(e((string)$o['comment']))?></div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="order-actions">
                <div>Обновлено</div>
                <div><?=e((string)$o['updated_at'])?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </section>
</body>
</html>
