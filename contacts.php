<?php
// contacts.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>3DOPE — Заказ</title>
  <link rel="stylesheet" href="/assets/style.css" />
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<section id="contacts">
  <div class="card">
    <h2>Оформить заказ</h2>
    <p>Заполните шаги — мы уточним детали и свяжемся с вами.</p>

    <div class="form-wrap">
      <form id="orderWizard">

        <!-- Step label -->
        <div class="hint" id="stageText" style="margin-bottom:10px;font-weight:700;">Шаг 1 из 4</div>

        <!-- STEP 1 -->
        <div class="wiz-stage" data-stage="1">
          <label class="label">Тип заказа</label>
          <div class="hint" style="margin-bottom:10px;">Выберите услугу, затем нажмите «Вперёд».</div>

          <label class="hint" style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <input type="radio" name="serviceType" value="modeling" checked>
            3D‑моделирование
          </label>
          <label class="hint" style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <input type="radio" name="serviceType" value="full">
            Полный цикл (модель + печать)
          </label>
          <label class="hint" style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <input type="radio" name="serviceType" value="print">
            Только печать (STL/STP/STEP)
          </label>
          <label class="hint" style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <input type="radio" name="serviceType" value="question">
            Вопрос
          </label>

          <label class="label" for="taskDesc" style="margin-top:12px;">Описание</label>
          <textarea id="taskDesc" rows="5" required placeholder="Что нужно сделать? Размеры, требования, сроки и т.п."></textarea>
        </div>

        <!-- STEP 2 -->
        <div class="wiz-stage hidden" data-stage="2">
          <label class="label" for="clientContact">Контакт для связи</label>
          <input id="clientContact" type="text" required placeholder="Telegram @username / WhatsApp / Email" />
          <div class="hint">По этому контакту сообщим статус и уточним детали.</div>

          <label class="label" for="filesInput" style="margin-top:12px;">Файлы (если есть)</label>
          <input id="filesInput" type="file" multiple />
          <div class="hint">Можно прикрепить фото, PDF, STL/STP/STEP и т.п.</div>
        </div>

        <!-- STEP 3 (only for print/full) -->
        <div class="wiz-stage hidden" data-stage="3">
          <label class="label">Филамент</label>

          <input id="selectedFilamentName" type="text" placeholder="Выберите филамент ниже" readonly />
          <input id="selectedFilamentId" type="hidden" />

          <div class="hint" id="selectedFilamentHint">Нажмите «Выбрать филамент», затем выберите материал.</div>
          <button type="button" class="btn btn-ghost" id="goPickFilamentBtn">Выбрать филамент</button>

          <div class="hint" style="margin-top:10px;">Блок выбора филамента можно оставить на отдельной странице/разделе — скажи, где он у тебя сейчас.</div>
        </div>

        <!-- STEP 4 -->
        <div class="wiz-stage hidden" data-stage="4">
          <div class="hint" style="margin-bottom:10px;">Проверьте данные и оформите заказ.</div>

          <button type="submit" class="btn" id="submitOrderBtn">Оформить заказ</button>
          <button type="button" class="btn btn-ghost" id="prevStage" style="width:100%;margin-top:10px;">Назад</button>
        </div>

        <!-- Nav -->
        <div style="display:flex;gap:10px;justify-content:space-between;margin-top:14px;">
          <button type="button" class="btn btn-ghost" id="prevStageTop" style="flex:1;">Назад</button>
          <button type="button" class="btn" id="nextStage" style="flex:1;">Вперёд</button>
        </div>

      </form>
    </div>
  </div>
</section>

<!-- Success modal -->
<div id="orderSuccessModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="orderSuccessTitle">
  <div class="modal-card">
    <h3 id="orderSuccessTitle">Вы успешно оформили заказ!</h3>
    <p>
      Заглядывайте в профиль, чтобы проверить его статус.
      После того, как заказ будет выполнен, мы отправим вам сообщение с фото/скриншотом/файлом вашего заказа,
      а также адресом, откуда можно забрать ваш заказ.
    </p>
    <p>Если у вас есть вопросы, напишите нам в телеграм: <b>@trinkieC</b>.</p>
    <div class="modal-actions">
      <a class="btn" href="/profile.php">Профиль</a>
      <button type="button" class="btn btn-ghost" id="closeOrderSuccess">Закрыть</button>
    </div>
  </div>
</div>

<!-- Error modal -->
<div id="orderErrorModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="orderErrorTitle">
  <div class="modal-card">
    <h3 id="orderErrorTitle">Не удалось отправить заказ</h3>
    <p id="orderErrorText">Попробуйте ещё раз.</p>
    <div class="modal-actions">
      <button type="button" class="btn" id="closeOrderError">Ок</button>
    </div>
  </div>
</div>

<script src="/assets/app.js"></script>
</body>
</html>
