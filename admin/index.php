<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// ===== Stats =====
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalPg       = (int)$pdo->query("SELECT COUNT(*) FROM playgrounds")->fetchColumn();
$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending       = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$revenue       = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings
                                     WHERE payment_status='paid'")->fetchColumn();

// Recent 5 bookings
$recent = $pdo->query(
   "SELECT b.*, u.name AS uname, p.name AS pname
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    JOIN playgrounds p ON p.id = b.playground_id
    ORDER BY b.id DESC LIMIT 5")->fetchAll();

// Bookings per last 7 days (for mini chart)
$chartData = $pdo->query(
   "SELECT booking_date, COUNT(*) AS c
    FROM bookings
    WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY booking_date ORDER BY booking_date")->fetchAll();

$pageTitle = 'Dashboard';
require 'includes/admin_header.php';
?>

<h3 class="fw-bold mb-4">Dashboard</h3>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex justify-content-between">
        <div>
          <div class="text-muted small">Total Users</div>
          <div class="fs-3 fw-bold"><?= $totalUsers ?></div>
        </div>
        <div class="text-primary fs-2"><i class="bi bi-people"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex justify-content-between">
        <div>
          <div class="text-muted small">Playgrounds</div>
          <div class="fs-3 fw-bold"><?= $totalPg ?></div>
        </div>
        <div class="text-success fs-2"><i class="bi bi-building"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex justify-content-between">
        <div>
          <div class="text-muted small">Bookings</div>
          <div class="fs-3 fw-bold"><?= $totalBookings ?></div>
        </div>
        <div class="text-warning fs-2"><i class="bi bi-calendar-check"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex justify-content-between">
        <div>
          <div class="text-muted small">Revenue (Paid)</div>
          <div class="fs-4 fw-bold text-success">Rs. <?= number_format($revenue,0) ?></div>
        </div>
        <div class="text-success fs-2"><i class="bi bi-cash-coin"></i></div>
      </div>
    </div>
  </div>
</div>

<?php if ($pending > 0): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center">
    <div><i class="bi bi-exclamation-triangle"></i>
      Booking <strong><?= $pending ?></strong> ක් approve කරන්න ඕන.</div>
    <a href="bookings.php?status=pending" class="btn btn-sm btn-dark">Review Now</a>
  </div>
<?php endif; ?>

<!-- RECENT BOOKINGS -->
<div class="card stat-card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-3">
      <h5 class="fw-bold mb-0">Recent Bookings</h5>
      <a href="bookings.php" class="small">View all →</a>
    </div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>User</th><th>Playground</th>
              <th>Date</th><th>Time</th><th>Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No bookings yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $b): ?>
          <tr>
            <td>#<?= $b['id'] ?></td>
            <td><?= e($b['uname']) ?></td>
            <td><?= e($b['pname']) ?></td>
            <td><?= e($b['booking_date']) ?></td>
            <td><?= substr($b['start_time'],0,5) ?>-<?= substr($b['end_time'],0,5) ?></td>
            <td>Rs. <?= number_format($b['total_amount'],2) ?></td>
            <td>
              <span class="badge bg-<?=
                ['pending'=>'warning','confirmed'=>'success',
                 'cancelled'=>'secondary','completed'=>'primary'][$b['status']] ?>">
                <?= ucfirst($b['status']) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require 'includes/admin_footer.php'; ?>