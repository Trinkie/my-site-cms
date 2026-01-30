<?php
$isAuth = function_exists('is_logged_in') && is_logged_in();
?>
<div class="nav-glass">
  <nav class="nav-inner">
    <div class="nav-left">
      <ul class="nav-row">
        <li><a href="index.html">Главная</a></li>
        <li><a href="services.html">Услуги</a></li>
      </ul>

      <ul class="nav-row">
        <li><a href="advantages.html">Преимущества</a></li>
        <li><a href="faq.html">FAQ</a></li>
        <li><a href="process.html">Процесс</a></li>
        <li><a href="contacts.php">Заказ</a></li>
      </ul>
    </div>

    <div class="nav-right">
      <a class="nav-ic" href="profile.php" title="Профиль" aria-label="Профиль">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0 2c-4.2 0-7.5 2.2-7.5 5v1h15v-1c0-2.8-3.3-5-7.5-5Z"/>
        </svg>
      </a>

      <?php if ($isAuth): ?>
        <a class="nav-ic nav-ic-exit" href="logout.php" title="Выйти" aria-label="Выйти">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10 17v-2h4v2h-4Zm0-4V11h7V9l4 3-4 3v-2h-7ZM4 4h10a2 2 0 0 1 2 2v2h-2V6H4v12h10v-2h2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
          </svg>
        </a>
      <?php else: ?>
        <a class="nav-ic nav-ic-exit" href="login.php" title="Войти" aria-label="Войти">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10 17v-2h4v2h-4Zm0-4V11h7V9l4 3-4 3v-2h-7ZM4 4h10a2 2 0 0 1 2 2v2h-2V6H4v12h10v-2h2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
          </svg>
        </a>
      <?php endif; ?>
    </div>
  </nav>
</div>
