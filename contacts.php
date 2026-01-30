<?php
// contacts.php — Заказ

// IMPORTANT:
// Если твоя админка/ CMS умеет отдавать JSON филаментов — подставь его сюда,
// чтобы фронт взял данные из админки.
// Пример: $filamentsFromAdminJson = file_get_contents(__DIR__ . '/content/filament.json');
$filamentsFromAdminJson = null; // <-- сюда подставишь JSON из админки (строкой) если нужно
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>3DOPE — Заказ</title>
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body>

  <!-- NAV (без калькулятора/филамента) -->
  <div class="nav-glass">
    <div class="nav-inner">
      <div class="nav-left">
        <nav>
          <ul class="nav-row">
            <li><a href="index.html">Главная</a></li>
            <li><a href="services.html">Услуги</a></li>
          </ul>
          <ul class="nav-row">
            <li><a href="process.html">Процесс</a></li>
            <li><a href="contacts.php">Заказ</a></li>
            <li><a href="advantages.html">Преимущества</a></li>
            <li><a href="faq.html">FAQ</a></li>
          </ul>
        </nav>
      </div>

      <div class="nav-right">
        <a class="nav-ic" href="profile.php" title="Профиль" aria-label="Профиль">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0 2c-4.2 0-7.5 2.2-7.5 5v1h15v-1c0-2.8-3.3-5-7.5-5Z"/>
          </svg>
        </a>

        <a class="nav-ic nav-ic-exit" href="logout.php" title="Выйти" aria-label="Выйти">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10 17v-2h4v2h-4Zm0-4V11h7V9l4 3-4 3v-2h-7ZM4 4h10a2 2 0 0 1 2 2v2h-2V6H4v12h10v-2h2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
          </svg>
        </a>
      </div>
    </div>

    <!-- стрелка сворачивания меню (моб) -->
    <button class="nav-toggle" id="navToggle" type="button" aria-expanded="true" aria-label="Свернуть меню">‹</button>
  </div>

  <section>
    <div class="card">
      <h2>Оформить заказ</h2>
      <p style="opacity:.85">Заполните шаги — мы уточним детали и свяжемся с вами.</p>

      <div class="wiz-top">
        <div class="wiz-step" id="stageText">Шаг 1 из 4</div>
      </div>

      <div class="form-wrap">
        <form id="orderWizard" enctype="multipart/form-data">

          <!-- STAGE 1 -->
          <div class="wiz-stage" data-stage="1">
            <label class="label" for="taskDesc">Описание задачи</label>
            <textarea id="taskDesc" rows="6" required
              placeholder="Опишите, что нужно сделать: особенности, условия использования модели, нагрузки/температуры, или ваш вопрос."></textarea>
            <div class="hint">Чем подробнее описание — тем быстрее и точнее оценим работу.</div>
          </div>

          <!-- STAGE 2 -->
          <div class="wiz-stage hidden" data-stage="2">
            <div class="label">Выберите услугу</div>

            <div class="choice-group" role="radiogroup" aria-label="Тип услуги">
              <label class="choice">
                <input type="radio" name="serviceType" value="full" required>
                <div><b>Полный цикл</b><br><span>От моделирования до печати готовой детали.</span></div>
              </label>

              <label class="choice">
                <input type="radio" name="serviceType" value="modeling" required>
                <div><b>Моделирование</b><br><span>По эскизам/чертежам/детали. Подготовим модель для печати.</span></div>
              </label>

              <label class="choice">
                <input type="radio" name="serviceType" value="print" required>
                <div><b>Печать</b><br><span>Если у вас уже есть STL/STP/STEP — распечатаем.</span></div>
              </label>
            </div>

            <div style="margin-top:14px">
              <div class="file-box">
                <div class="label" id="filesLabel">Файлы</div>
                <div class="file-row">
                  <input class="file-input" id="filesInput" type="file" required>
                </div>
                <div class="hint" id="filesHint">Прикрепите файлы.</div>
                <div id="filesList" class="files-list"></div>
              </div>
            </div>
          </div>

          <!-- STAGE 3 (только для print/full) -->
          <div class="wiz-stage hidden" data-stage="3" id="filamentStage">
            <div class="label">Выбор филамента</div>
            <div class="hint">ABS+ eSUN: прочный, термостойкий, подходит для функциональных деталей.</div>

            <div style="margin-top:12px">
              <label class="label" for="selectedFilamentName">Выбранный филамент</label>
              <input id="selectedFilamentName" type="text" placeholder="Выберите филамент ниже" readonly>
              <input id="selectedFilamentId" type="hidden">
              <div class="hint" id="selectedFilamentHint">Филамент не выбран.</div>
            </div>

            <div class="content-item" style="margin-top:14px">
              <div class="filament-carousel">
                <button class="fc-btn" id="fcPrev" type="button" aria-label="Назад">‹</button>
                <div class="fc-viewport" id="fcViewport" aria-label="Список филаментов"></div>
                <button class="fc-btn" id="fcNext" type="button" aria-label="Вперед">›</button>
              </div>
              <div class="fc-dots" id="fcDots" aria-label="Навигация"></div>
              <div class="hint" style="margin-top:.8rem">Нажмите “Выбрать” на нужном филаменте.</div>
            </div>
          </div>

          <!-- STAGE 4 -->
          <div class="wiz-stage hidden" data-stage="4">
            <label class="label" for="clientContact">Контакт для связи</label>
            <input id="clientContact" type="text" required placeholder="Telegram @username / WhatsApp / Email">
            <div class="hint">По этому контакту сообщим, что модель/печать готова, и уточним детали, если будут вопросы.</div>

            <div id="submitWrap" style="margin-top:10px">
              <button type="submit" class="btn" style="width:100%">Оформить заказ</button>
            </div>
          </div>

          <!-- arrows -->
          <div class="wiz-nav">
            <button type="button" class="btn btn-muted" id="prevStage">‹ Назад</button>
            <button type="button" class="btn" id="nextStage">Вперёд ›</button>
          </div>

        </form>
      </div>
    </div>
  </section>

  <!-- 1) Сюда админка должна подставить JSON филаментов (если есть) -->
  <script>
    window.FILAMENTSFROMADMIN =
      <?php echo $filamentsFromAdminJson ? $filamentsFromAdminJson : 'null'; ?>;
  </script>

  <!-- 2) JS -->
  <script src="assets/app.js"></script>
</body>
</html>
