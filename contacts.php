<?php
require __DIR__ . '/config.php';

$user = current_user();
$createdId = null;
$err = '';
$ok = '';

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) mkdir($dir, 0755, true);
}

function safe_ext(string $name): string {
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  return preg_replace('/[^a-z0-9]/', '', $ext);
}

function safe_name(string $name): string {
  $base = strtolower(pathinfo($name, PATHINFO_FILENAME));
  $base = preg_replace('/[^a-z0-9_\-]+/i', '_', $base);
  $base = trim($base, '_');
  return $base !== '' ? $base : 'file';
}

function upload_one(string $field, array $allowedExt, string $dstDir, string $prefix): ?string {
  if (empty($_FILES[$field]) || !isset($_FILES[$field]['tmp_name'])) return null;
  if ((int)$_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

  $ext = safe_ext((string)$_FILES[$field]['name']);
  if (!in_array($ext, $allowedExt, true)) return null;

  $dst = rtrim($dstDir, '/') . '/' . $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  if (!move_uploaded_file((string)$_FILES[$field]['tmp_name'], $dst)) return null;
  return $dst;
}

function upload_multi(string $field, array $allowedExt, string $dstDir, string $prefix): array {
  $out = [];
  if (empty($_FILES[$field]) || !isset($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) return $out;

  $names = $_FILES[$field]['name'];
  $tmps  = $_FILES[$field]['tmp_name'];
  $errs  = $_FILES[$field]['error'];

  for ($i=0; $i<count($names); $i++) {
    if ((int)$errs[$i] !== UPLOAD_ERR_OK) continue;
    $ext = safe_ext((string)$names[$i]);
    if (!in_array($ext, $allowedExt, true)) continue;

    $dst = rtrim($dstDir, '/') . '/' . $prefix . '_' . safe_name((string)$names[$i]) . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file((string)$tmps[$i], $dst)) $out[] = $dst;
  }
  return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$user) { header('Location: login.php'); exit; }

  if (!csrf_check($_POST['csrf'] ?? null)) {
    $err = 'Сессия устарела. Обновите страницу и попробуйте снова.';
  } else {
    $task_desc = trim((string)($_POST['task_desc'] ?? ''));
    $request_type = (string)($_POST['request_type'] ?? 'modeling');

    $filament_id = trim((string)($_POST['filament_id'] ?? ''));
    $filament_name = trim((string)($_POST['filament_name'] ?? ''));

    $strength_needed = isset($_POST['strength_needed']) ? 1 : 0;

    if ($task_desc === '') {
      $err = 'Заполните описание задачи.';
    } elseif ($filament_id === '' || $filament_name === '') {
      $err = 'Выберите филамент перед созданием заказа.';
    } else {
      // create order row first (to get id for folder)
      $title = mb_substr($task_desc, 0, 240);
      $status = 'queue';
      $comment = "Тип запроса: {$request_type}";

      $st = db()->prepare('
        INSERT INTO orders (user_id, title, status, comment, filament_id, filament_name, strength_needed)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ');
      $st->execute([
        (int)$user['id'],
        $title,
        $status,
        $comment,
        $filament_id,
        $filament_name,
        (int)$strength_needed,
      ]);

      $createdId = (int)db()->lastInsertId();

      // upload files into per-order folder
      $baseDir = __DIR__ . '/uploads/orders/' . $createdId;
      ensure_dir($baseDir);

      $modelPath = null;
      $imgPaths = [];

      if ($request_type === 'print') {
        // model required
        $modelPath = upload_one('modelFile', ['stl','stp','step'], $baseDir, 'model');
        if (!$modelPath) {
          // rollback the order if file missing/invalid
          db()->prepare('DELETE FROM orders WHERE id=? AND user_id=?')->execute([$createdId, (int)$user['id']]);
          $err = 'Для "Печать" нужно прикрепить STL/STP/STEP файл (валидный).';
          $createdId = null;
        }
      } else {
        // images/pdf optional
        $imgPaths = upload_multi('attachmentsImages', ['jpg','jpeg','png','webp','pdf'], $baseDir, 'ref');
      }

      if (!$err && $createdId) {
        // store file paths in comment (simple, without extra table)
        $pathsText = '';
        if ($modelPath) $pathsText .= "\nФайл модели: " . str_replace(__DIR__ . '/', '', $modelPath);
        if ($imgPaths) {
          $pathsText .= "\nВложения:\n- " . implode("\n- ", array_map(fn($p) => str_replace(__DIR__ . '/', '', $p), $imgPaths));
        }
        if ($pathsText !== '') {
          db()->prepare('UPDATE orders SET comment = CONCAT(comment, ?) WHERE id=? AND user_id=?')
            ->execute([$pathsText, $createdId, (int)$user['id']]);
        }

        $ok = "Заказ создан: #{$createdId}. Статус: " . status_label($status) . ".";
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
          <form method="post" id="orderForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="filament_id" id="filament_id" value="">
            <input type="hidden" name="filament_name" id="filament_name" value="">

            <div class="form-grid">
              <div class="full">
                <label class="label">Описание задачи</label>
                <textarea name="task_desc" id="task_desc" rows="5" required placeholder="Что нужно сделать? Какие условия/нагрузка/улица/температура?"><?=e($_POST['task_desc'] ?? '')?></textarea>
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

              <div class="full" id="imagesBlock">
                <label class="label">Фото/эскизы (необязательно)</label>
                <div class="file-box">
                  <div class="file-row">
                    <input class="file-input" id="attachmentsImages" name="attachmentsImages[]" type="file" multiple accept="image/*,.pdf">
                  </div>
                  <div class="hint">Можно прикрепить фото детали, эскиз, чертёж, скрин.</div>
                </div>
              </div>

              <div class="full hidden" id="modelBlock">
                <label class="label">Файл модели (обязательно для “Печать”)</label>
                <div class="file-box">
                  <div class="file-row">
                    <input class="file-input" id="modelFile" name="modelFile" type="file" accept=".stl,.stp,.step" />
                  </div>
                  <div class="hint">Загрузите STL/STP/STEP.</div>
                </div>
              </div>

              <div class="full">
                <label class="choice" style="width:100%; justify-content:flex-start">
                  <input type="checkbox" name="strength_needed" id="strength_needed">
                  <div><b>Повышенная прочность</b><br><span>Подберём настройки печати.</span></div>
                </label>
              </div>

              <div class="full" style="text-align:center">
                <button type="submit" class="btn">Создать заказ</button>
                <a class="btn btn-muted" href="profile.php" style="margin-left:0.5rem">В профиль</a>
              </div>
            </div>
          </form>
        </div>

        <script>
          (function () {
            const view = document.getElementById('selectedFilamentView');
            const hidId = document.getElementById('filament_id');
            const hidName = document.getElementById('filament_name');
            const hint = document.getElementById('filamentHint');
            const btn = document.getElementById('goPickFilamentBtn');

            const imagesBlock = document.getElementById('imagesBlock');
            const modelBlock = document.getElementById('modelBlock');
            const modelFile = document.getElementById('modelFile');

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
            } catch (e) { apply(null); }

            if (btn) btn.addEventListener('click', () => { window.location.href = 'filament.html'; });

            function updateByType() {
              const t = document.querySelector('input[name="request_type"]:checked')?.value || 'modeling';
              if (t === 'print') {
                imagesBlock.classList.add('hidden');
                modelBlock.classList.remove('hidden');
                modelFile.required = true;
              } else {
                modelBlock.classList.add('hidden');
                imagesBlock.classList.remove('hidden');
                modelFile.required = false;
              }
            }

            document.querySelectorAll('input[name="request_type"]').forEach(r => r.addEventListener('change', updateByType));
            updateByType();

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
