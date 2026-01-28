<?php
require __DIR__ . '/config.php';

$authLinksHtml = auth_links_html();
$err = '';
$ok = '';

if (isset($_GET['registered'])) $ok = 'Аккаунт создан. Теперь войдите.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? null)) {
    $err = 'Сессия устарела. Обновите страницу и попробуйте снова.';
  } else {
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    $st = db()->prepare('SELECT id, pass_hash FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();

    if (!$u || !password_verify($pass, (string)$u['pass_hash'])) {
      $err = 'Неверный email или пароль.';
    } else {
      session_regenerate_id(true);
      $_SESSION['user_id'] = (int)$u['id'];
      header('Location: profile.php');
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
  <title>3DOPE — Вход</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section>
    <div class="card">
      <h2>Вход</h2>
      <p>Войдите, чтобы увидеть ваши заказы и статусы.</p>

      <?php if ($ok): ?><div class="msg-ok"><?=e($ok)?></div><?php endif; ?>
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
              <label class="label">Пароль</label>
              <input name="password" type="password" required>
            </div>

            <div class="full" style="text-align:center">
              <button type="submit" class="btn">Войти</button>
              <a class="btn btn-muted" href="register.php" style="margin-left:0.5rem">Регистрация</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>
</body>
</html>
