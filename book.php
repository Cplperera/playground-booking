<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$pgId  = (int)($_POST['playground_id'] ?? 0);
$date  = $_POST['booking_date'] ?? '';
$slots = $_POST['slots'] ?? [];   // array of "HH:MM|HH:MM"

// ============ BASIC VALIDATION ============
if (!$pgId || !$date || !is_array($slots) || count($slots) === 0) {
    $_SESSION['flash_error'] = 'Slot එකක්වත් තෝරලා නැහැ.';
    header("Location: playground.php?id=$pgId"); exit;
}

if ($date < date('Y-m-d')) {
    $_SESSION['flash_error'] = 'කල් ගිය දිනයකට booking කරන්න බැහැ.';
    header("Location: playground.php?id=$pgId"); exit;
}

// ============ PARSE + SORT SLOTS ============
$parsed = [];
foreach ($slots as $s) {
    $parts = explode('|', $s);
    if (count($parts) !== 2) continue;

    $start = $parts[0] . ':00';   // "09:00" → "09:00:00"
    $end   = $parts[1] . ':00';

    if (strtotime($end) <= strtotime($start)) continue;

    $parsed[] = ['start' => $start, 'end' => $end];
}

if (!$parsed) {
    $_SESSION['flash_error'] = 'වලංගු slot නැහැ.';
    header("Location: playground.php?id=$pgId"); exit;
}

// Sort by start time
usort($parsed, fn($a, $b) => strtotime($a['start']) <=> strtotime($b['start']));

// Remove duplicates
$unique = [];
foreach ($parsed as $p) {
    $key = $p['start'] . '-' . $p['end'];
    if (!isset($unique[$key])) $unique[$key] = $p;
}
$parsed = array_values($unique);

// ============ GROUP CONTIGUOUS SLOTS ============
// Sinhala: යාබද slots එකට එකතු කරලා එක booking එකක් කරනවා
$groups = [];
foreach ($parsed as $slot) {
    if (empty($groups)) {
        $groups[] = $slot;
        continue;
    }
    $last = &$groups[count($groups) - 1];

    if ($last['end'] === $slot['start']) {
        // Contiguous — extend
        $last['end'] = $slot['end'];
    } else {
        // Separate booking
        $groups[] = $slot;
    }
    unset($last);
}

// ============ INSERT WITH TRANSACTION ============
try {
    $pdo->beginTransaction();

    // Fetch playground (with lock)
    $stmt = $pdo->prepare(
        "SELECT price_per_hour, open_time, close_time
         FROM playgrounds WHERE id=? AND status=1 FOR UPDATE");
    $stmt->execute([$pgId]);
    $pg = $stmt->fetch();
    if (!$pg) throw new Exception('Playground not found');

    $rate = (float)$pg['price_per_hour'];

    // Validate each group is within open/close hours
    foreach ($groups as $g) {
        if (strtotime($g['start']) < strtotime($pg['open_time']) ||
            strtotime($g['end'])   > strtotime($pg['close_time'])) {
            throw new Exception('Slot එකක් වෙන්කරන වෙලාවෙන් එළියේ.');
        }
    }

    // Check availability for each group
    foreach ($groups as $g) {
        if (!isSlotAvailable($pdo, $pgId, $date, $g['start'], $g['end'])) {
            $msg = substr($g['start'],0,5) . ' - ' . substr($g['end'],0,5);
            throw new Exception("Slot $msg දැනටමත් book කරලා තියෙනවා!");
        }
    }

    // Insert one booking per group
    $ins = $pdo->prepare(
        "INSERT INTO bookings
         (user_id, playground_id, booking_date, start_time, end_time,
          total_amount, status, payment_status)
         VALUES (?,?,?,?,?,?, 'pending', 'unpaid')");

    $totalAmount = 0;
    $createdIds  = [];

    foreach ($groups as $g) {
        $hours = (strtotime($g['end']) - strtotime($g['start'])) / 3600;
        $amt   = $hours * $rate;
        $totalAmount += $amt;

        $ins->execute([
            $_SESSION['user_id'], $pgId, $date,
            $g['start'], $g['end'], $amt
        ]);
        $createdIds[] = $pdo->lastInsertId();
    }

    $pdo->commit();

    $count = count($createdIds);
    $_SESSION['flash'] = $count > 1
        ? "Booking $count ක් සාර්ථකව ලැබුණා! මුළු මුදල: Rs. " . number_format($totalAmount, 2)
        : "Booking එක සාර්ථකව ලැබුණා! මුළු මුදල: Rs. " . number_format($totalAmount, 2);

    header('Location: my_bookings.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: playground.php?id=$pgId");
    exit;
}