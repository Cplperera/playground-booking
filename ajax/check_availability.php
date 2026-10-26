<?php
require '../config/database.php';
require '../includes/functions.php';
header('Content-Type: application/json');

$pgId = (int)($_GET['playground_id'] ?? 0);
$date = $_GET['date'] ?? '';

if (!$pgId || !$date) {
    echo json_encode(['error' => 'Missing params']); exit;
}

$pg = $pdo->prepare("SELECT open_time, close_time, price_per_hour
                     FROM playgrounds WHERE id=? AND status=1");
$pg->execute([$pgId]);
$p = $pg->fetch();
if (!$p) { echo json_encode(['error' => 'Not found']); exit; }

$slots = generateSlots($p['open_time'], $p['close_time']);
$out   = [];

foreach ($slots as $s) {
    $out[] = [
        'start'     => substr($s['start'], 0, 5),
        'end'       => substr($s['end'], 0, 5),
        'available' => isSlotAvailable($pdo, $pgId, $date,
                                       $s['start'], $s['end']),
        'price'     => (float)$p['price_per_hour'],
    ];
}
echo json_encode(['slots' => $out]);