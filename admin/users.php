<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

/* Toggle user active */
if (($_GET['action'] ?? '') === 'toggle' && ($uid = (int)$_GET['id'])) {
    // admin කෙනෙක්ව disable කරන්න බැහැ
    $pdo->prepare("UPDATE users SET status = 1 - status
                   WHERE id = ? AND role <> 'admin'")->execute([$uid]);
    $_SESSION['flash'] = 'User status update කළා.';
    header('Location: users.php'); exit;
}

$rows = $pdo->query(
   "SELECT u.*,
           (SELECT COUNT(*) FROM bookings WHERE user_id=u.id) AS bookings_count
    FROM users u ORDER BY u.id DESC")->fetchAll();

$pageTitle = 'Users';
require 'includes/admin_header.php';
?>

<h3 class="fw-bold mb-4">Users</h3>

<div class="card stat-card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Name</th><th>Email</th><th>Phone</th>
            <th>Role</th><th>Bookings</th><th>Status</th><th>Joined</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td class="fw-semibold"><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['phone']) ?></td>
            <td>
              <span class="badge bg-<?= $u['role']==='admin'?'dark':'info' ?>">
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td><?= (int)$u['bookings_count'] ?></td>
            <td>
              <span class="badge bg-<?= $u['status']?'success':'secondary' ?>">
                <?= $u['status']?'Active':'Disabled' ?>
              </span>
            </td>
            <td class="text-muted small"><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if ($u['role'] !== 'admin'): ?>
                <a href="?action=toggle&id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-<?= $u['status']?'danger':'success' ?>">
                  <?= $u['status'] ? 'Disable' : 'Enable' ?>
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require 'includes/admin_footer.php'; ?>