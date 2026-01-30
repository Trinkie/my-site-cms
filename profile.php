<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$u = $_SESSION['user'];

// Подключение к БД
require 'db.php';

// Получаем все заказы пользователя
$st = $pdo->prepare('
    SELECT 
        id, 
        title, 
        status, 
        comment, 
        filament_name, 
        filament_status,
        strength_needed, 
        service_type,
        updated_at, 
        created_at 
    FROM orders 
    WHERE user_id = ? 
    ORDER BY id DESC 
');
$st->execute([(int)$u['id']]);
$orders = $st->fetchAll();

// Функция для определения первого этапа статуса
function getStage1($filament_status) {
    if ($filament_status === 'preorder' || $filament_status === 'под заказ') {
        return 'Пластик в доставке';
    }
    return 'В очереди';
}

// Функция для определения второго этапа статуса
function getStage2($service_type) {
    if ($service_type === 'full' || $service_type === 'print') {
        return 'В печати';
    }
    if ($service_type === 'modeling') {
        return 'В процессе';
    }
    return 'В процессе';
}

// Функция для получения финального сообщения при готовности
function getFinalMessage($service_type) {
    if ($service_type === 'full' || $service_type === 'print') {
        return 'Модель готова, заберите её по адресу Г.Пермь, Односторонняя, д.1';
    }
    if ($service_type === 'modeling') {
        return 'Модель готова, мы вышлем вам её в Telegram';
    }
    return '';
}

// Функция для преобразования типа услуги
function getServiceName($service_type) {
    $types = [
        'full' => 'Полный цикл',
        'modeling' => 'Моделирование',
        'print' => 'Печать'
    ];
    return $types[$service_type] ?? $service_type;
}

// Функция для получения этапов заказа
function getOrderStages($order) {
    $stages = [];
    
    // Этап 1
    $stage1 = getStage1($order['filament_status']);
    $stages[] = [
        'name' => $stage1,
        'status' => 'queue' // red
    ];
    
    // Этап 2
    $stage2 = getStage2($order['service_type']);
    $stages[] = [
        'name' => $stage2,
        'status' => 'printing' // yellow
    ];
    
    // Этап 3 (всегда)
    $stages[] = [
        'name' => 'Готово',
        'status' => 'ready' // green
    ];
    
    return $stages;
}

// Функция для определения активного этапа по текущему статусу
function getActiveStageIndex($current_status) {
    $status_map = [
        'queue' => 0,
        'printing' => 1,
        'ready' => 2,
        'ready' => 2,
        'delivered' => 2,
        'canceled' => -1
    ];
    return $status_map[$current_status] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>3DOPE — Профиль</title>
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body>

  <!-- NAV -->
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
        <a class="nav-ic nav-ic-exit" href="logout.php" title="Выйти" aria-label="Выйти">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M10 17v-2h4v2h-4Zm0-4V11h7V9l4 3-4 3v-2h-7ZM4 4h10a2 2 0 0 1 2 2v2h-2V6H4v12h10v-2h2v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
          </svg>
        </a>
      </div>
    </nav>
    <button class="nav-toggle" id="navToggle" type="button" aria-expanded="true" aria-label="Свернуть меню">‹</button>
  </div>

  <section>
    <div class="card">
      <h2>Ваши заказы</h2>
      <p style="opacity:.85">Статусы выполнения и информация о заказах.</p>

      <?php if (empty($orders)): ?>
        <div class="msg-err" style="margin-top: 2rem;">
          У вас нет заказов. <a href="contacts.php" class="btn" style="margin-top: 1rem; display: inline-block;">Оформить заказ</a>
        </div>
      <?php else: ?>
        <div class="orders-list">
          <?php foreach ($orders as $order): 
            $stages = getOrderStages($order);
            $activeStageIdx = getActiveStageIndex($order['status']);
            $finalMsg = getFinalMessage($order['service_type']);
          ?>
            <div class="order-card">
              <div class="order-card-header">
                <div class="order-card-title">
                  <h3><?php echo htmlspecialchars($order['title']); ?></h3>
                  <span class="order-date"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="order-card-type">
                  <span class="order-badge"><?php echo getServiceName($order['service_type']); ?></span>
                </div>
              </div>

              <!-- Статусы заказа -->
              <div class="order-stages">
                <?php foreach ($stages as $idx => $stage): 
                  $isActive = ($idx === $activeStageIdx);
                  $isCompleted = ($idx < $activeStageIdx);
                  $isCanceled = ($order['status'] === 'canceled');
                ?>
                  <div class="order-stage <?php echo $isCompleted ? 'completed' : ($isActive ? 'active' : 'pending'); ?> <?php echo $isCanceled ? 'canceled' : ''; ?>">
                    <div class="stage-dot"></div>
                    <div class="stage-content">
                      <p class="stage-name"><?php echo htmlspecialchars($stage['name']); ?></p>
                    </div>
                  </div>
                  <?php if ($idx < count($stages) - 1): ?>
                    <div class="stage-connector <?php echo $isCompleted ? 'completed' : ''; ?>"></div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>

              <!-- Финальное сообщение при готовности -->
              <?php if ($order['status'] === 'ready' && $finalMsg): ?>
                <div class="order-final-message">
                  📦 <?php echo htmlspecialchars($finalMsg); ?>
                </div>
              <?php endif; ?>

              <!-- Информация о заказе -->
              <div class="order-info">
                <div class="info-row">
                  <span class="info-label">Филамент:</span>
                  <span class="info-value"><?php echo htmlspecialchars($order['filament_name'] ?? 'Не указан'); ?></span>
                </div>
                <?php if ($order['comment']): ?>
                  <div class="info-row">
                    <span class="info-label">Заметка:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['comment']); ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Кнопки действий -->
              <div class="order-actions">
                <a href="https://t.me/trinkieC" class="btn btn-telegram" target="_blank" rel="noopener noreferrer">
                  Написать в Telegram
                </a>
                <button class="btn btn-danger" onclick="cancelOrder(<?php echo (int)$order['id']; ?>)">
                  Отменить заказ
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <script src="assets/app.js"></script>
  <script>
    function cancelOrder(orderId) {
      if (!confirm('Вы уверены, что хотите отменить этот заказ?')) {
        return;
      }

      fetch('api/cancel-order.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ order_id: orderId })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('Заказ отменен');
          location.reload();
        } else {
          alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
      })
      .catch(err => {
        console.error(err);
        alert('Ошибка сети');
      });
    }
  </script>
</body>
</html>
