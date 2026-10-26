<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM playgrounds WHERE id=? AND status=1");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    $_SESSION['flash_error'] = 'Playground එක හමු නොවුණා.';
    header('Location: index.php'); exit;
}

$pageTitle = $p['name'];
require 'includes/header.php';
?>

<div class="row g-4">
  <!-- LEFT: image + info -->
  <div class="col-lg-7">
    <img src="<?= $p['image'] ? e(UPLOAD_URL.$p['image']) : 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=1200' ?>"
         class="img-fluid rounded-4 shadow-sm mb-3 w-100"
         style="height:380px;object-fit:cover">

    <h2 class="fw-bold"><?= e($p['name']) ?></h2>
    <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($p['location']) ?></p>
    <p><?= nl2br(e($p['description'])) ?></p>

    <div class="row text-center mt-4">
      <div class="col-4">
        <div class="border rounded-3 p-3">
          <div class="text-muted small">Price</div>
          <div class="fw-bold text-success">Rs. <?= number_format($p['price_per_hour'],2) ?></div>
        </div>
      </div>
      <div class="col-4">
        <div class="border rounded-3 p-3">
          <div class="text-muted small">Open</div>
          <div class="fw-bold"><?= substr($p['open_time'],0,5) ?></div>
        </div>
      </div>
      <div class="col-4">
        <div class="border rounded-3 p-3">
          <div class="text-muted small">Close</div>
          <div class="fw-bold"><?= substr($p['close_time'],0,5) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT: booking form -->
  <div class="col-lg-5">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-1">
          <i class="bi bi-calendar-check text-success"></i> Book Slot(s)
        </h5>
        <p class="small text-muted mb-3">
          Slots කිහිපයක් එකවර තෝරන්න පුළුවන් — ආයෙ click කරනකොට cancel වෙනවා.
        </p>

        <?php if (!isLoggedIn()): ?>
          <div class="alert alert-warning small">
            Booking කරන්න <a href="login.php" class="fw-bold">Login</a> වෙන්න ඕන.
          </div>
        <?php endif; ?>

        <form action="book.php" method="POST" id="bookingForm">
          <input type="hidden" name="playground_id" value="<?= $p['id'] ?>">
          <input type="hidden" id="pg_id" value="<?= $p['id'] ?>">
          <input type="hidden" id="price_per_hour" value="<?= $p['price_per_hour'] ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Select Date</label>
            <input type="date" class="form-control" id="booking_date"
                   name="booking_date"
                   min="<?= date('Y-m-d') ?>"
                   value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-semibold mb-0">Available Slots</label>
            <button type="button" id="clearSlots"
                    class="btn btn-link btn-sm text-decoration-none p-0">
              Clear All
            </button>
          </div>

          <div id="slots" class="mb-3">
            <div class="text-muted small">Date එකක් තෝරන්න...</div>
          </div>

          <!-- Selected summary -->
          <div id="selectionSummary" class="alert alert-light border small mb-3 d-none">
            <div class="d-flex justify-content-between">
              <span><strong>Selected:</strong> <span id="slotCount">0</span> slot(s)</span>
              <span class="fw-bold text-success">Rs. <span id="totalPrice">0</span></span>
            </div>
            <div id="slotList" class="mt-1 text-muted"></div>
          </div>

          <button type="submit" class="btn btn-success w-100 fw-semibold py-2"
                  id="submitBtn" <?= isLoggedIn() ? '' : 'disabled' ?>>
            <i class="bi bi-check-circle"></i> Confirm Booking
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>