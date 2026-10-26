<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

/* =========================================================
   FILTERS
   ========================================================= */
$from      = $_GET['from']      ?? date('Y-m-01');   // මේ මාසයේ මුල
$to        = $_GET['to']        ?? date('Y-m-d');
$pgFilter  = (int)($_GET['playground_id'] ?? 0);
$payFilter = $_GET['payment_status'] ?? '';

// Whitelist payment status
$allowedPay = ['unpaid','paid','refunded'];
if ($payFilter && !in_array($payFilter, $allowedPay)) $payFilter = '';

/* =========================================================
   BUILD WHERE CLAUSE
   ========================================================= */
$where  = ["b.booking_date BETWEEN ? AND ?"];
$params = [$from, $to];

if ($pgFilter) {
    $where[]  = "b.playground_id = ?";
    $params[] = $pgFilter;
}
if ($payFilter) {
    $where[]  = "b.payment_status = ?";
    $params[] = $payFilter;
}
$whereSQL = "WHERE " . implode(' AND ', $where);

/* =========================================================
   SUMMARY STATS
   ========================================================= */
$statSQL = "SELECT
    COUNT(*) AS total_bookings,
    COALESCE(SUM(CASE WHEN payment_status='paid'   THEN total_amount END),0) AS paid_amount,
    COALESCE(SUM(CASE WHEN payment_status='unpaid' THEN total_amount END),0) AS unpaid_amount,
    COALESCE(SUM(CASE WHEN payment_status='refunded' THEN total_amount END),0) AS refunded_amount,
    COALESCE(SUM(CASE WHEN payment_status='paid'   THEN 1 END),0) AS paid_count,
    COALESCE(SUM(CASE WHEN payment_status='unpaid' THEN 1 END),0) AS unpaid_count,
    COALESCE(SUM(CASE WHEN payment_status='refunded' THEN 1 END),0) AS refunded_count,
    COALESCE(SUM(CASE WHEN b.status='cancelled' THEN 1 END),0) AS cancelled_count
    FROM bookings b $whereSQL";

$stmt = $pdo->prepare($statSQL);
$stmt->execute($params);
$s = $stmt->fetch();

/* =========================================================
   PAYMENT METHOD BREAKDOWN — per playground
   ========================================================= */
$pgSQL = "SELECT p.id, p.name,
                 COUNT(b.id) AS bookings,
                 COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.total_amount END),0) AS revenue
          FROM playgrounds p
          LEFT JOIN bookings b
            ON b.playground_id = p.id
            AND b.booking_date BETWEEN ? AND ?
          GROUP BY p.id, p.name
          ORDER BY revenue DESC";
$stmt = $pdo->prepare($pgSQL);
$stmt->execute([$from, $to]);
$perPlayground = $stmt->fetchAll();

/* =========================================================
   DAILY BREAKDOWN (for chart)
   ========================================================= */
$daySQL = "SELECT b.booking_date,
                  COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.total_amount END),0) AS paid,
                  COALESCE(SUM(CASE WHEN b.payment_status='unpaid' THEN b.total_amount END),0) AS unpaid
           FROM bookings b $whereSQL
           GROUP BY b.booking_date
           ORDER BY b.booking_date";
$stmt = $pdo->prepare($daySQL);
$stmt->execute($params);
$daily = $stmt->fetchAll();

/* =========================================================
   TRANSACTIONS TABLE
   ========================================================= */
$listSQL = "SELECT b.*, u.name AS uname, u.email AS uemail,
                   p.name AS pname
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN playgrounds p ON p.id = b.playground_id
            $whereSQL
            ORDER BY b.booking_date DESC, b.id DESC";
$stmt = $pdo->prepare($listSQL);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Payments';
require __DIR__ . '/includes/admin_header.php';
?>

<!-- =========================================================
     CSV EXPORT HANDLER (inline before HTML) — placed at top
     ========================================================= -->
<?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
  <!-- Not executed here; handled below via separate file -->
<?php endif; ?>

<!-- ================= HEADER ================= -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h3 class="fw-bold mb-0">Payment Summary</h3>
    <div class="text-muted small">
      <?= date('d M Y', strtotime($from)) ?> — <?= date('d M Y', strtotime($to)) ?>
    </div>
  </div>
  <a href="export_payments.php?<?= http_build_query($_GET) ?>"
     class="btn btn-outline-success">
    <i class="bi bi-file-earmark-excel"></i> Export CSV
  </a>
</div>

<!-- ================= FILTER BAR ================= -->
<div class="card stat-card mb-4">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">From</label>
        <input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">To</label>
        <input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Playground</label>
        <select name="playground_id" class="form-select form-select-sm">
          <option value="">All Playgrounds</option>
          <?php foreach ($perPlayground as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $pgFilter == $p['id'] ? 'selected' : '' ?>>
              <?= e($p['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Payment</label>
        <select name="payment_status" class="form-select form-select-sm">
  <option value="">All</option>
  <?php foreach (['paid','unpaid','refunded'] as $st): ?>   <!-- $s → $st -->
    <option value="<?= $st ?>" <?= $payFilter === $st ? 'selected' : '' ?>>
      <?= ucfirst($st) ?>
    </option>
  <?php endforeach; ?>
</select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-success btn-sm w-100">
          <i class="bi bi-funnel"></i> Filter
        </button>
        <a href="payments.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- ================= STATS ROW ================= -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-3">
    <div class="card stat-card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Total Revenue</div>
          <div class="fs-4 fw-bold text-success">
            Rs. <?= number_format($s['paid_amount'], 2) ?>
          </div>
          <div class="small text-muted"><?= (int)$s['paid_count'] ?> paid bookings</div>
        </div>
        <div class="text-success fs-2"><i class="bi bi-cash-coin"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="card stat-card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Pending (Unpaid)</div>
          <div class="fs-4 fw-bold text-warning">
            Rs. <?= number_format($s['unpaid_amount'], 2) ?>
          </div>
          <div class="small text-muted"><?= (int)$s['unpaid_count'] ?> unpaid bookings</div>
        </div>
        <div class="text-warning fs-2"><i class="bi bi-hourglass-split"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="card stat-card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Refunded</div>
          <div class="fs-4 fw-bold text-danger">
            Rs. <?= number_format($s['refunded_amount'], 2) ?>
          </div>
          <div class="small text-muted"><?= (int)$s['refunded_count'] ?> refunds</div>
        </div>
        <div class="text-danger fs-2"><i class="bi bi-arrow-counterclockwise"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="card stat-card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-muted small">Total Bookings</div>
          <div class="fs-4 fw-bold"><?= (int)$s['total_bookings'] ?></div>
          <div class="small text-muted"><?= (int)$s['cancelled_count'] ?> cancelled</div>
        </div>
        <div class="text-primary fs-2"><i class="bi bi-calendar-check"></i></div>
      </div>
    </div>
  </div>
</div>

<!-- ================= CHART ================= -->
<div class="card stat-card mb-4">
  <div class="card-body">
    <h5 class="fw-bold mb-3">Daily Revenue</h5>
    <canvas id="revenueChart" height="80"></canvas>
  </div>
</div>

<!-- ================= PER-PLAYGROUND BREAKDOWN ================= -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card stat-card h-100">
      <div class="card-body">
        <h5 class="fw-bold mb-3">By Playground</h5>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Playground</th>
                <th class="text-end">Bookings</th>
                <th class="text-end">Revenue (Rs.)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($perPlayground as $p): ?>
                <tr>
                  <td class="fw-semibold"><?= e($p['name']) ?></td>
                  <td class="text-end"><?= (int)$p['bookings'] ?></td>
                  <td class="text-end fw-semibold text-success">
                    <?= number_format($p['revenue'], 2) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <th>Total</th>
                <th class="text-end"><?= (int)$s['total_bookings'] ?></th>
                <th class="text-end text-success">
                  Rs. <?= number_format($s['paid_amount'], 2) ?>
                </th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick summary panel -->
  <div class="col-lg-6">
    <div class="card stat-card h-100">
      <div class="card-body">
        <h5 class="fw-bold mb-3">At a Glance</h5>

        <?php
        $rate = $s['total_bookings'] > 0
              ? ($s['paid_count'] / $s['total_bookings']) * 100 : 0;
        ?>
        <div class="d-flex justify-content-between mb-2">
          <span>Payment Collection Rate</span>
          <strong><?= number_format($rate, 1) ?>%</strong>
        </div>
        <div class="progress mb-4" style="height:10px">
          <div class="progress-bar bg-success" style="width:<?= $rate ?>%"></div>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <span>Average Booking Value</span>
          <strong>Rs. <?= $s['total_bookings'] > 0
            ? number_format(($s['paid_amount']+$s['unpaid_amount']) / $s['total_bookings'], 2)
            : '0.00' ?></strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
          <span>Best Performing Court</span>
          <strong><?= e($perPlayground[0]['name'] ?? '—') ?></strong>
        </div>

        <hr>
        <div class="small text-muted">
          Period: <strong><?= e($from) ?></strong> to <strong><?= e($to) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================= TRANSACTIONS TABLE ================= -->
<div class="card stat-card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-3">
      <h5 class="fw-bold mb-0">Transactions (<?= count($rows) ?>)</h5>
    </div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Playground</th>
            <th>Time</th>
            <th class="text-end">Amount</th>
            <th>Booking</th>
            <th>Payment</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">
            මේ period එකට transactions නැහැ.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $b): ?>
          <tr>
            <td>#<?= $b['id'] ?></td>
            <td><?= e($b['booking_date']) ?></td>
            <td>
              <div class="fw-semibold"><?= e($b['uname']) ?></div>
              <div class="text-muted small"><?= e($b['uemail']) ?></div>
            </td>
            <td><?= e($b['pname']) ?></td>
            <td class="small">
              <?= substr($b['start_time'],0,5) ?>–<?= substr($b['end_time'],0,5) ?>
            </td>
            <td class="text-end fw-semibold">
              Rs. <?= number_format($b['total_amount'], 2) ?>
            </td>
            <td>
              <span class="badge bg-<?=
                ['pending'=>'warning','confirmed'=>'success',
                 'cancelled'=>'secondary','completed'=>'primary'][$b['status']] ?>">
                <?= ucfirst($b['status']) ?>
              </span>
            </td>
            <td>
              <span class="badge bg-<?=
                ['unpaid'=>'secondary','paid'=>'success','refunded'=>'danger'][$b['payment_status']] ?>">
                <?= ucfirst($b['payment_status']) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ================= CHART.JS ================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const dailyData = <?= json_encode($daily) ?>;

new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels: dailyData.map(d => d.booking_date),
    datasets: [
      {
        label: 'Paid (Rs.)',
        data: dailyData.map(d => d.paid),
        backgroundColor: '#198754',
        borderRadius: 4
      },
      {
        label: 'Unpaid (Rs.)',
        data: dailyData.map(d => d.unpaid),
        backgroundColor: '#ffc107',
        borderRadius: 4
      }
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: {
        callbacks: {
          label: ctx => ctx.dataset.label + ': Rs. ' + Number(ctx.raw).toLocaleString()
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: v => 'Rs. ' + Number(v).toLocaleString()
        }
      }
    }
  }
});
</script>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>