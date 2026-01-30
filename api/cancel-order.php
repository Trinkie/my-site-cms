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

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID заказа']);
    exit;
}

try {
    // Проверяем, что заказ принадлежит пользователю
    $st = $pdo->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ?');
    $st->execute([$order_id, (int)$_SESSION['user']['id']]);
    
    if (!$st->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Заказ не найден']);
        exit;
    }

    // Обновляем статус на canceled
    $st = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $st->execute(['canceled', $order_id]);

    echo json_encode(['success' => true, 'message' => 'Заказ отменен']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
