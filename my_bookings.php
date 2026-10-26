<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();

// Cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $bid = (int)$_POST['booking_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status='cancelled'
                           WHERE id=? AND user_id=? AND status IN ('pending','confirmed')");
    $stmt->execute([$bid, $_SESSION['user_id']]);
    $_SESSION['flash'] = 'Booking එක cancel කළා.';
    header('Location: my_bookings.php'); exit;
}

$stmt = $pdo->prepare(
   "SELECT b.*, p.name AS pg_name, p.location
    FROM bookings b
    JOIN playgrounds p ON p.id = b.playground_id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

$pageTitle = 'My Bookings';
require 'includes/header.php';

$badge = [
  'pending'   => 'warning',
  'confirmed' => 'success',
  'cancelled' => 'secondary',
  'completed' => 'primary',
];
?>

<h3 class="fw-bold mb-4">My Bookings</h3>

<?php if (!$rows): ?>
  <div class="alert alert-info">
    තවම booking කිසිවක් නැහැ.
    <a href="index.php" class="fw-bold">Playground එකක් තෝරන්න →</a>
  </div>
<?php else: ?>
  <div class="table-responsive bg-white rounded-4 shadow-sm">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Playground</th><th>Date</th>
          <th>Time</th><th>Amount</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $b): ?>
        <tr>
          <td><?= $b['id'] ?></td>
          <td>
            <div class="fw-semibold"><?= e($b['pg_name']) ?></div>
            <div class="text-muted small"><?= e($b['location']) ?></div>
          </td>
          <td><?= e($b['booking_date']) ?></td>
          <td><?= substr($b['start_time'],0,5) ?> - <?= substr($b['end_time'],0,5) ?></td>
          <td>Rs. <?= number_format($b['total_amount'],2) ?></td>
          <td>
            <span class="badge bg-<?= $badge[$b['status']] ?? 'secondary' ?>">
              <?= ucfirst($b['status']) ?>
            </span>
          </td>
          <td>
            <?php if (in_array($b['status'], ['pending','confirmed'])): ?>
              <form method="POST" onsubmit="return confirm('Cancel කරන්නද?')">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                <button class="btn btn-outline-danger btn-sm">Cancel</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>