<?php
require __DIR__ . '/config.php';

$authLinksHtml = auth_links_html();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? null)) {
    $err = 'Сессия устарела. Обновите страницу и попробуйте снова.';
  } else {
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
        // 1062 Duplicate entry
        $msg = $e->getMessage();
        $err = (str_contains($msg, '1062') || str_contains($msg, 'Duplicate'))
          ? 'Такой email уже зарегистрирован.'
          : 'Ошибка сервера. Попробуйте позже.';
      }
    }
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>3DOPE — Регистрация</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section>
    <div class="card">
      <h2>Регистрация</h2>
      <p>Создайте аккаунт, чтобы оформлять заказы и отслеживать статус.</p>

      <?php if ($err): ?><div class="msg-err"><?=e($err)?></div><?php endif; ?>

      <div class="form-wrap">
        <form method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <div class="form-grid">
            <div class="full">
              <label class="label">Email</label>
              <input name="email" type="email" required value="<?=e($_POST['email'] ?? '')?>">
            </div>

            <div class="full">
              <label class="label">Имя (необязательно)</label>
              <input name="name" type="text" value="<?=e($_POST['name'] ?? '')?>">
            </div>

            <div class="full">
              <label class="label">Пароль</label>
              <input name="password" type="password" required>
              <div class="hint">Минимум 6 символов.</div>
            </div>

            <div class="full" style="text-align:center">
              <button type="submit" class="btn">Создать аккаунт</button>
              <a class="btn btn-muted" href="login.php" style="margin-left:0.5rem">Уже есть аккаунт</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>
</body>
</html>
