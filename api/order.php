<?php
header('Content-Type: application/json; charset=utf-8');

// === CONFIG ===
$BOT_TOKEN = 'PASTE_BOT_TOKEN_HERE';
$CHAT_ID   = 'PASTE_CHAT_ID_HERE';

// === INPUT ===
$clientContact = trim($_POST['clientContact'] ?? '');
$taskDesc      = trim($_POST['taskDesc'] ?? '');
$serviceType   = trim($_POST['serviceType'] ?? '');
$filamentId    = trim($_POST['filamentId'] ?? '');
$filamentName  = trim($_POST['filamentName'] ?? '');

if ($clientContact === '' || $taskDesc === '' || $serviceType === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
  exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

$lines = [];
$lines[] = "🧾 Новый заказ 3DOPE";
$lines[] = "Услуга: {$serviceType}";
if ($serviceType === 'print' || $serviceType === 'full') {
  $lines[] = "Филамент: " . ($filamentName ?: $filamentId ?: '(не выбран)');
}
$lines[] = "Контакт: {$clientContact}";
$lines[] = "Описание: " . mb_strimwidth($taskDesc, 0, 1200, '…', 'UTF-8');
$lines[] = "IP: {$ip}";
$lines[] = "UA: " . mb_strimwidth($ua, 0, 300, '…', 'UTF-8');

$text = implode("\n", $lines);

// === SEND TELEGRAM ===
$url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
$payload = [
  'chat_id' => $CHAT_ID,
  'text' => $text,
  'disable_web_page_preview' => true
];

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query($payload),
  CURLOPT_TIMEOUT => 10
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false || $code >= 400) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Telegram send failed', 'details' => $err ?: $res]);
  exit;
}

// === OPTIONAL: save to file ===
@mkdir(__DIR__ . '/../orders', 0775, true);
$order = [
  'ts' => date('c'),
  'serviceType' => $serviceType,
  'clientContact' => $clientContact,
  'taskDesc' => $taskDesc,
  'filamentId' => $filamentId,
  'filamentName' => $filamentName,
  'ip' => $ip,
  'ua' => $ua
];
file_put_contents(__DIR__ . '/../orders/' . time() . '.json', json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(['ok' => true]);
