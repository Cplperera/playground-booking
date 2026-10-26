<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Same filters as payments.php
$from      = $_GET['from'] ?? date('Y-m-01');
$to        = $_GET['to']   ?? date('Y-m-d');
$pgFilter  = (int)($_GET['playground_id'] ?? 0);
$payFilter = $_GET['payment_status'] ?? '';

$allowedPay = ['unpaid','paid','refunded'];
if ($payFilter && !in_array($payFilter, $allowedPay)) $payFilter = '';

$where  = ["b.booking_date BETWEEN ? AND ?"];
$params = [$from, $to];
if ($pgFilter) { $where[] = "b.playground_id = ?"; $params[] = $pgFilter; }
if ($payFilter) { $where[] = "b.payment_status = ?"; $params[] = $payFilter; }
$whereSQL = "WHERE " . implode(' AND ', $where);

$sql = "SELECT b.id, b.booking_date, u.name AS uname, u.email AS uemail, u.phone,
               p.name AS pname, b.start_time, b.end_time,
               b.total_amount, b.status, b.payment_status, b.created_at
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        JOIN playgrounds p ON p.id = b.playground_id
        $whereSQL
        ORDER BY b.booking_date DESC, b.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Send CSV headers
$filename = 'payments_' . $from . '_to_' . $to . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// BOM — Excel Sinhala support
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header row
fputcsv($out, [
    'Booking ID', 'Date', 'Customer', 'Email', 'Phone',
    'Playground', 'Start', 'End', 'Amount (Rs.)',
    'Booking Status', 'Payment Status', 'Created At'
]);

$total = 0;
foreach ($rows as $r) {
    if ($r['payment_status'] === 'paid') $total += $r['total_amount'];
    fputcsv($out, [
        $r['id'],
        $r['booking_date'],
        $r['uname'],
        $r['uemail'],
        $r['phone'],
        $r['pname'],
        substr($r['start_time'],0,5),
        substr($r['end_time'],0,5),
        number_format($r['total_amount'], 2, '.', ''),
        $r['status'],
        $r['payment_status'],
        $r['created_at'],
    ]);
}

// Summary row
fputcsv($out, []);
fputcsv($out, ['', '', '', '', '', '', '', '', '', '', '', '']);
fputcsv($out, ['TOTAL PAID', '', '', '', '', '', '', '',
               number_format($total, 2, '.', ''), '', '', '']);

fclose($out);
exit;