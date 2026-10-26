<?php
require_once __DIR__ . '/../config/config.php';        // ← මේක උඩම දාන්න!
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

/* ---------- HANDLE POST (create/update) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $price    = (float)($_POST['price_per_hour'] ?? 0);
    $open     = $_POST['open_time']  ?? '06:00';
    $close    = $_POST['close_time'] ?? '23:00';
    $status   = isset($_POST['status']) ? 1 : 0;
    $pgId     = (int)($_POST['id'] ?? 0);

    if ($name === '')     $errors[] = 'නම අවශ්‍යයි.';
    if ($location === '') $errors[] = 'Location අවශ්‍යයි.';
    if ($price <= 0)      $errors[] = 'Price එක වැරදියි.';
    if (strtotime($close) <= strtotime($open)) $errors[] = 'Close time open එකට පස්සේ විය යුතුයි.';

    // Image upload
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image එක jpg/png/webp විය යුතුයි.';
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Image එක 3MB ට වඩා ලොකු වැඩියි.';
        } else {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
            $imageName = uniqid('pg_', true) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $imageName);
        }
    }

    if (!$errors) {
        if ($pgId) {
            // UPDATE
            if ($imageName) {
                $sql = "UPDATE playgrounds SET name=?, location=?, description=?,
                        price_per_hour=?, open_time=?, close_time=?, status=?, image=?
                        WHERE id=?";
                $pdo->prepare($sql)->execute(
                    [$name,$location,$desc,$price,$open,$close,$status,$imageName,$pgId]);
            } else {
                $sql = "UPDATE playgrounds SET name=?, location=?, description=?,
                        price_per_hour=?, open_time=?, close_time=?, status=?
                        WHERE id=?";
                $pdo->prepare($sql)->execute(
                    [$name,$location,$desc,$price,$open,$close,$status,$pgId]);
            }
            $_SESSION['flash'] = 'Playground එක update කළා.';
        } else {
            // INSERT
            $sql = "INSERT INTO playgrounds
                    (name,location,description,price_per_hour,open_time,close_time,status,image)
                    VALUES (?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute(
                [$name,$location,$desc,$price,$open,$close,$status,$imageName]);
            $_SESSION['flash'] = 'Playground එක එකතු කළා.';
        }
        header('Location: playgrounds.php'); exit;
    }
}

/* ---------- DELETE ---------- */
if ($action === 'delete' && $id) {
    // Delete image too
    $stmt = $pdo->prepare("SELECT image FROM playgrounds WHERE id=?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists(UPLOAD_DIR.$img)) @unlink(UPLOAD_DIR.$img);

    $pdo->prepare("DELETE FROM playgrounds WHERE id=?")->execute([$id]);
    $_SESSION['flash'] = 'Playground එක delete කළා.';
    header('Location: playgrounds.php'); exit;
}

/* ---------- TOGGLE STATUS ---------- */
if ($action === 'toggle' && $id) {
    $pdo->prepare("UPDATE playgrounds SET status = 1 - status WHERE id=?")
        ->execute([$id]);
    header('Location: playgrounds.php'); exit;
}

/* ---------- EDIT FORM DATA ---------- */
$editRow = null;
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM playgrounds WHERE id=?");
    $stmt->execute([$id]);
    $editRow = $stmt->fetch();
    if (!$editRow) { header('Location: playgrounds.php'); exit; }
}

/* ---------- LIST ---------- */
$rows = $pdo->query("SELECT * FROM playgrounds ORDER BY id DESC")->fetchAll();

$pageTitle = 'Playgrounds';
require 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="fw-bold mb-0">Playgrounds</h3>
  <a href="?action=new" class="btn btn-success">
    <i class="bi bi-plus-circle"></i> Add New
  </a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e) echo '<div>• '.e($e).'</div>'; ?>
  </div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
  <!-- ============ FORM ============ -->
  <div class="card stat-card mb-4">
    <div class="card-body">
      <h5 class="fw-bold mb-3">
        <?= $editRow ? 'Edit Playground' : 'Add Playground' ?>
      </h5>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $editRow['id'] ?? 0 ?>">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Name *</label>
            <input type="text" name="name" class="form-control" required
                   value="<?= e($editRow['name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Location *</label>
            <input type="text" name="location" class="form-control" required
                   value="<?= e($editRow['location'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" rows="3" class="form-control"><?=
              e($editRow['description'] ?? '') ?></textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Price per Hour (Rs.) *</label>
            <input type="number" step="0.01" name="price_per_hour"
                   class="form-control" required
                   value="<?= e($editRow['price_per_hour'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Open Time</label>
            <input type="time" name="open_time" class="form-control"
                   value="<?= substr($editRow['open_time'] ?? '06:00',0,5) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Close Time</label>
            <input type="time" name="close_time" class="form-control"
                   value="<?= substr($editRow['close_time'] ?? '23:00',0,5) ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Image (jpg/png/webp, ≤ 3MB)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <?php if (!empty($editRow['image'])): ?>
              <div class="mt-2">
                <img src="<?= UPLOAD_URL . e($editRow['image']) ?>"
                     style="height:70px;border-radius:8px">
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="status"
                     id="status" <?= (!isset($editRow['status']) || $editRow['status']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="status">Active</label>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button class="btn btn-success px-4">
            <i class="bi bi-save"></i> <?= $editRow ? 'Update' : 'Save' ?>
          </button>
          <a href="playgrounds.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- ============ LIST TABLE ============ -->
<div class="card stat-card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th width="70">Image</th>
            <th>Name</th>
            <th>Location</th>
            <th>Price/hr</th>
            <th>Hours</th>
            <th>Status</th>
            <th width="160">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">
            Playground කිසිවක් නැහැ. "Add New" ඔබන්න.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td>
              <?php if ($p['image']): ?>
                <img src="<?= UPLOAD_URL . e($p['image']) ?>"
                     style="width:60px;height:45px;object-fit:cover;border-radius:6px">
              <?php else: ?>
                <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center"
                     style="width:60px;height:45px">
                  <i class="bi bi-image text-muted"></i>
                </div>
              <?php endif; ?>
            </td>
            <td class="fw-semibold"><?= e($p['name']) ?></td>
            <td class="text-muted small"><?= e($p['location']) ?></td>
            <td>Rs. <?= number_format($p['price_per_hour'],2) ?></td>
            <td class="small">
              <?= substr($p['open_time'],0,5) ?>–<?= substr($p['close_time'],0,5) ?>
            </td>
            <td>
              <a href="?action=toggle&id=<?= $p['id'] ?>"
                 class="badge text-decoration-none bg-<?= $p['status'] ? 'success' : 'secondary' ?>">
                <?= $p['status'] ? 'Active' : 'Hidden' ?>
              </a>
            </td>
            <td>
              <a href="?action=edit&id=<?= $p['id'] ?>"
                 class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil"></i>
              </a>
              <a href="?action=delete&id=<?= $p['id'] ?>"
                 class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Delete කරන්නද? Booking ඔක්කොම ඉවත් වෙනවා.')">
                <i class="bi bi-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require 'includes/admin_footer.php'; ?>