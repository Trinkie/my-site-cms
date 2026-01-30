<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

require '../db.php';

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);
$status = trim($input['status'] ?? '');

if (!$order_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Неверные параметры']);
    exit;
}

// Допустимые статусы
$allowed_statuses = ['queue', 'printing', 'ready', 'canceled', 'delivered'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Неверный статус']);
    exit;
}

try {
    // Обновляем статус
    $st = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
    $st->execute([$status, $order_id]);

    if ($st->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Заказ не найден']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Статус обновлен']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
