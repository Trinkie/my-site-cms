<?php
require __DIR__ . '/config.php';

$authLinksHtml = auth_links_html();

$user = current_user();
$createdId = null;
$err = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$user) {
    header('Location: login.php');
    exit;
  }

  if (!csrf_check($_POST['csrf'] ?? null)) {
    $err = 'Сессия устарела. Обновите страницу и попробуйте снова.';
  } else {
    $client_contact = trim((string)($_POST['client_contact'] ?? ''));
    $task_desc = trim((string)($_POST['task_desc'] ?? ''));
    $request_type = (string)($_POST['request_type'] ?? 'modeling');

    $filament_id = trim((string)($_POST['filament_id'] ?? ''));
    $filament_name = trim((string)($_POST['filament_name'] ?? ''));

    $material = trim((string)($_POST['material'] ?? ''));
    $strength_needed = isset($_POST['strength_needed']) ? 1 : 0;

    if ($client_contact === '' || $task_desc === '') {
      $err = 'Заполните контакт и описание задачи.';
    } elseif ($filament_id === '' || $filament_name === '') {
      $err = 'Выберите филамент перед созданием заказа.';
    } else {
      // title: короткое описание заказа
      $title = mb_substr($task_desc, 0, 240);

      // status: "queue" сразу после создания
      $status = 'queue';

      // комментарий — сохраняем "тип запроса" (и всё доп. можно дописывать вручную в админке)
      $comment = "Тип запроса: {$request_type}";

      $st = db()->prepare('
        INSERT INTO orders (user_id, title, status, comment, filament_id, filament_name, material, strength_needed, client_contact)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ');
      $st->execute([
        (int)$user['id'],
        $title,
        $status,
        $comment,
        $filament_id !== '' ? $filament_id : null,
        $filament_name !== '' ? $filament_name : null,
        $material !== '' ? $material : null,
        (int)$strength_needed,
        $client_contact !== '' ? $client_contact : null,
      ]);

      $createdId = (int)db()->lastInsertId();
      $ok = "Заказ создан: #{$createdId}. Статус: " . status_label($status) . ".";
    }
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>3DOPE — Заказ</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/nav.php'; ?>

  <section>
    <div class="card">
      <h2>Создать заказ</h2>
      <p>Заполните форму — заказ появится в вашем профиле со статусом выполнения.</p>

      <?php if (!$user): ?>
        <div class="msg-err">Чтобы создать заказ, нужно войти или зарегистрироваться.</div>
        <div style="text-align:center">
          <a class="btn" href="login.php">Войти</a>
          <a class="btn btn-muted" href="register.php" style="margin-left:0.5rem">Регистрация</a>
        </div>
      <?php else: ?>

        <?php if ($ok): ?><div class="msg-ok"><?=e($ok)?></div><?php endif; ?>
        <?php if ($err): ?><div class="msg-err"><?=e($err)?></div><?php endif; ?>

        <div class="form-wrap">
          <form method="post" id="orderForm">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">

            <!-- Hidden: filament from JS/localStorage -->
            <input type="hidden" name="filament_id" id="filament_id" value="">
            <input type="hidden" name="filament_name" id="filament_name" value="">

            <div class="form-grid">
              <div class="full">
                <label class="label">Контакт (Telegram / телефон / email)</label>
                <input name="client_contact" id="client_contact" type="text" required placeholder="@username / +7..." value="<?=e($_POST['client_contact'] ?? '')?>">
              </div>

              <div class="full">
                <label class="label">Описание задачи</label>
                <textarea name="task_desc" id="task_desc" rows="5" required placeholder="Что нужно сделать? Какие условия/нагрузка/улица/температура?"><?=e($_POST['task_desc'] ?? '')?></textarea>
                <div class="hint">Если детали нет — можно моделирование по фото/эскизу. Если есть файл — можно печать STL/STP/STEP.</div>
              </div>

              <div class="full">
                <label class="label">Тип запроса</label>
                <div class="choice-group" role="radiogroup" aria-label="Тип запроса">
                  <label class="choice"><input type="radio" name="request_type" value="modeling" checked><div><b>Моделирование</b><br><span>Только 3D‑модель</span></div></label>
                  <label class="choice"><input type="radio" name="request_type" value="full"><div><b>Полный цикл</b><br><span>Модель + печать</span></div></label>
                  <label class="choice"><input type="radio" name="request_type" value="print"><div><b>Печать</b><br><span>Есть STL/STP/STEP</span></div></label>
                  <label class="choice"><input type="radio" name="request_type" value="question"><div><b>Вопрос</b><br><span>Консультация</span></div></label>
                </div>
              </div>

              <div class="full">
                <label class="label">Выбранный филамент</label>
                <input id="selectedFilamentView" type="text" placeholder="Не выбран" readonly>
                <div class="hint" id="filamentHint">Нужно выбрать филамент.</div>
                <button type="button" class="btn btn-muted" id="goPickFilamentBtn" style="margin-top:0.8rem">Выбрать филамент</button>
              </div>

              <div class="full">
                <label class="label">Материал (если нужно)</label>
                <select name="material" id="material">
                  <option value="">Не важно (подберём сами)</option>
                  <option value="absred">ABS</option>
                  <option value="absplusblack">ABS+</option>
                </select>
              </div>

              <div class="full">
                <label class="choice" style="width:100%; justify-content:flex-start">
                  <input type="checkbox" name="strength_needed" id="strength_needed">
                  <div><b>Повышенная прочность</b><br><span>Подберём настройки печати (без наценки).</span></div>
                </label>
              </div>

              <div class="full" style="text-align:center">
                <button type="submit" class="btn">Создать заказ</button>
                <a class="btn btn-muted" href="profile.php" style="margin-left:0.5rem">Перейти в профиль</a>
              </div>
            </div>
          </form>
        </div>

        <script>
          // restore filament from localStorage (same key as in your JS)
          (function () {
            const view = document.getElementById('selectedFilamentView');
            const hidId = document.getElementById('filament_id');
            const hidName = document.getElementById('filament_name');
            const hint = document.getElementById('filamentHint');
            const btn = document.getElementById('goPickFilamentBtn');

            function apply(sel) {
              if (sel && sel.id && sel.name) {
                view.value = sel.name;
                hidId.value = sel.id;
                hidName.value = sel.name;
                hint.textContent = 'Выбран: ' + sel.name;
              } else {
                view.value = '';
                hidId.value = '';
                hidName.value = '';
                hint.textContent = 'Нужно выбрать филамент.';
              }
            }

            try {
              const raw = localStorage.getItem('selectedFilament');
              apply(raw ? JSON.parse(raw) : null);
            } catch (e) {
              apply(null);
            }

            if (btn) btn.addEventListener('click', () => {
              window.location.href = 'filament.html';
            });

            // on submit: ensure filament exists
            document.getElementById('orderForm').addEventListener('submit', (e) => {
              if (!hidId.value || !hidName.value) {
                e.preventDefault();
                alert('Сначала выберите филамент.');
                window.location.href = 'filament.html';
              }
            });
          })();
        </script>

      <?php endif; ?>
    </div>
  </section>
</body>
</html>
