<?php
// XSS නවත්තන්න
function e($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Slot එක free ද? — Sinhala: overlap logic
 * existing.start < new.end  AND  existing.end > new.start
 */
function isSlotAvailable(PDO $pdo, int $pgId, string $date,
                         string $start, string $end): bool
{
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE playground_id = ?
              AND booking_date  = ?
              AND status IN ('pending','confirmed')
              AND (start_time < ? AND end_time > ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pgId, $date, $end, $start]);
    return (int)$stmt->fetchColumn() === 0;
}

// මිනිත්තු 60 slot generate කරනවා (open_time → close_time)
function generateSlots(string $open, string $close, int $minutes = 60): array {
    $slots = [];
    $cur   = strtotime($open);
    $end   = strtotime($close);
    while ($cur + $minutes * 60 <= $end) {
        $slots[] = [
            'start' => date('H:i:s', $cur),
            'end'   => date('H:i:s', $cur + $minutes * 60),
        ];
        $cur += $minutes * 60;
    }
    return $slots;
}