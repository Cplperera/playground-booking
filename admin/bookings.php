<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

/* ---------- ACTION: change status / payment ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid    = (int)($_POST['booking_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $pay    = $_POST['payment_status'] ?? '';

    $allowedStatus = ['pending','confirmed','cancelled','completed'];
    $allowedPay    = ['unpaid','paid','refunded'];

    if ($bid && in_array($status, $allowedStatus) && in_array($pay, $allowedPay)) {
        $pdo->prepare("UPDATE bookings SET status=?, payment_status=? WHERE id=?")
            ->execute([$status, $pay, $bid]);
        $_SESSION['flash'] = "Booking #$bid update කළා.";
    }
    header('Location: bookings.php'); exit;
}

/* ---------- FILTER ---------- */
$filter = $_GET['status'] ?? '';
$where  = '';
$params = [];
if (in_array($filter, ['pending','confirmed','cancelled','completed'])) {
    $where  = "WHERE b.status = ?";
    $params[] = $filter;
}

$stmt = $pdo->prepare(
   "SELECT b.*, u.name AS uname, u.email AS uemail, u.phone AS uphone,
           p.name AS pname
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    JOIN playgrounds p ON p.id = b.playground_id
    $where
    ORDER BY b.id DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Bookings';
require 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h3 class="fw-bold mb-0">Bookings</h3>
  <div class="btn-group">
    <a href="bookings.php"
       class="btn btn-sm btn-<?= $filter==='' ? 'success' : 'outline-success' ?>">All</a>
    <a href="?status=pending"
       class="btn btn-sm btn-<?= $filter==='pending' ? 'success' : 'outline-success' ?>">Pending</a>
    <a href="?status=confirmed"
       class="btn btn-sm btn-<?= $filter==='confirmed' ? 'success' : 'outline-success' ?>">Confirmed</a>
    <a href="?status=completed"
       class="btn btn-sm btn-<?= $filter==='completed' ? 'success' : 'outline-success' ?>">Completed</a>
    <a href="?status=cancelled"
       class="btn btn-sm btn-<?= $filter==='cancelled' ? 'success' : 'outline-success' ?>">Cancelled</a>
  </div>
</div>

<div class="card stat-card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Customer</th><th>Playground</th>
            <th>Date / Time</th><th>Amount</th>
            <th>Payment</th><th>Status</th><th width="220">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">
            Booking කිසිවක් නැහැ.
          </td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $b): ?>
          <tr>
            <td>#<?= $b['id'] ?></td>
            <td>
              <div class="fw-semibold"><?= e($b['uname']) ?></div>
              <div class="text-muted small"><?= e($b['uemail']) ?></div>
              <div class="text-muted small"><?= e($b['uphone']) ?></div>
            </td>
            <td><?= e($b['pname']) ?></td>
            <td>
              <div><?= e($b['booking_date']) ?></div>
              <div class="text-muted small">
                <?= substr($b['start_time'],0,5) ?>–<?= substr($b['end_time'],0,5) ?>
              </div>
            </td>
            <td>Rs. <?= number_format($b['total_amount'],2) ?></td>
            <td>
              <span class="badge bg-<?=
                 ['unpaid'=>'secondary','paid'=>'success','refunded'=>'danger'][$b['payment_status']] ?>">
                <?= ucfirst($b['payment_status']) ?>
              </span>
            </td>
            <td>
              <span class="badge bg-<?=
                 ['pending'=>'warning','confirmed'=>'success',
                  'cancelled'=>'secondary','completed'=>'primary'][$b['status']] ?>">
                <?= ucfirst($b['status']) ?>
              </span>
            </td>
            <td>
              <form method="POST" class="d-flex gap-1">
                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                <select name="status" class="form-select form-select-sm">
                  <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>>
                      <?= ucfirst($s) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <select name="payment_status" class="form-select form-select-sm">
                  <?php foreach (['unpaid','paid','refunded'] as $s): ?>
                    <option value="<?= $s ?>" <?= $b['payment_status']===$s?'selected':'' ?>>
                      <?= ucfirst($s) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-success">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require 'includes/admin_footer.php'; ?>