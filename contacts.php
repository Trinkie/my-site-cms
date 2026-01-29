<?php
// тут можешь подключить свою логику PHP/сессий при необходимости
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

  <!-- NAV -->
  <div class="nav-glass">
    <div class="nav-inner">
      <div class="nav-left">
        <nav>
          <ul class="nav-row">
            <li><a href="index.html">Главная</a></li>
            <li><a href="services.html">Услуги</a></li>
            <li><a href="advantages.html">Преимущества</a></li>
            <li><a href="faq.html">FAQ</a></li>
          </ul>
          <ul class="nav-row">
            <li><a href="process.html">Процесс</a></li>
            <li><a href="contacts.php">Заказ</a></li>
          </ul>
        </nav>
      </div>

      <div class="nav-right">
        <a class="nav-ic" href="#" data-netlify-identity-button aria-label="Профиль">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0 2c-4.2 0-8 2.2-8 5v1h16v-1c0-2.8-3.8-5-8-5Z"/>
          </svg>
        </a>

        <a class="nav-ic nav-ic-exit" href="#" id="logoutBtn" aria-label="Выход" style="display:none">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10 17v-2h4v-2h-4V11l-3 3 3 3Zm9-12H11V3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-8v-2h8V5Z"/>
          </svg>
        </a>
      </div>
    </div>

    <button class="nav-toggle" id="navToggle" type="button" aria-expanded="true" aria-label="Свернуть меню">‹</button>
  </div>

  <!-- PAGE -->
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
                <div><b>Моделирование</b><br><span>По эскизам/чертежам/детали. Файл подготовим для печати.</span></div>
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

          <!-- STAGE 3 -->
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
              <div class="fc-d
