<?php
header('Content-Type: application/json; charset=utf-8');

// === CONFIG ===
$BOT_TOKEN = '8383618613:AAHj3dLZihRejU6cP2FwA9Luia3kw6OKOhM';
$CHAT_ID   = '1309723376';

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

function tgPost($method, $fields, $token) {
  $url = "https://api.telegram.org/bot{$token}/{$method}";
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $fields,
    CURLOPT_TIMEOUT => 30,
  ]);
  $res = curl_exec($ch);
  $err = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $res, $err];
}

// 1) send text message
$lines = [];
$lines[] = "Новый заказ 3DOPE";
$lines[] = "Услуга: {$serviceType}";
if ($serviceType === 'print' || $serviceType === 'full') {
  $lines[] = "Филамент: " . ($filamentName ?: $filamentId ?: '(не выбран)');
}
$lines[] = "Контакт: {$clientContact}";
$lines[] = "Описание: " . mb_strimwidth($taskDesc, 0, 1200, '…', 'UTF-8');

$text = implode("\n", $lines);

list($code1, $res1, $err1) = tgPost('sendMessage', [
  'chat_id' => $CHAT_ID,
  'text' => $text,
  'disable_web_page_preview' => true
], $BOT_TOKEN);

if ($code1 >= 400 || $res1 === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Telegram sendMessage failed', 'details' => $err1 ?: $res1]);
  exit;
}

// 2) send uploaded files (if any)
$sentFiles = [];

if (!empty($_FILES)) {
  @mkdir(__DIR__ . '/../uploads', 0775, true);

  foreach ($_FILES as $key => $f) {
    if (!isset($f['error']) || $f['error'] !== UPLOAD_ERR_OK) continue;
    if (!is_uploaded_file($f['tmp_name'])) continue;

    $safeName = preg_replace('/[^a-zA-Z0-9._-]+/u', '_', $f['name']);
    $dst = __DIR__ . '/../uploads/' . time() . '_' . $safeName;

    if (!move_uploaded_file($f['tmp_name'], $dst)) continue;

    // sendDocument
    $cFile = new CURLFile($dst, $f['type'] ?: 'application/octet-stream', $safeName);

    list($code2, $res2, $err2) = tgPost('sendDocument', [
      'chat_id' => $CHAT_ID,
      'document' => $cFile,
      'caption' => "Файл: {$safeName}"
    ], $BOT_TOKEN);

    // удаляем локальную копию
    @unlink($dst);

    if ($code2 < 400 && $res2 !== false) {
      $sentFiles[] = $safeName;
    }
  }
}

echo json_encode(['ok' => true, 'sentFiles' => $sentFiles]);
