<?php
// nav.php should be self-contained
// requires config.php already included by the page
$authLinksHtml = function_exists('auth_links_html') ? auth_links_html() : '<li><a href="login.php">Войти</a></li><li><a href="register.php">Регистрация</a></li>';
?>
<div class="nav-glass">
  <nav>
    <ul class="nav-row">
      <li><a href="index.html">Главная</a></li>
      <li><a href="services.html">Услуги</a></li>
      <li><a href="calculator.html">Калькулятор</a></li>
      <li><a href="filament.html">Филамент</a></li>
    </ul>
    <ul class="nav-row">
      <li><a href="advantages.html">Преимущества</a></li>
      <li><a href="faq.html">FAQ</a></li>
      <li><a href="process.html">Процесс</a></li>
      <li><a href="contacts.php">Заказ</a></li>
    </ul>
    <ul class="nav-row nav-auth">
      <?= $authLinksHtml ?>
    </ul>
  </nav>
</div>
