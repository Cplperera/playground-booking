<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pageTitle = 'Home';
$rows = $pdo->query("SELECT * FROM playgrounds WHERE status=1 ORDER BY id DESC")
            ->fetchAll();
require 'includes/header.php';
?>

<!-- Hero -->
<section class="hero mb-5">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <h1 class="fw-bold mb-2">Book Your Game. Anytime. Anywhere.</h1>
      <p class="mb-0 opacity-75">
        Cricket — ඔබේ ප්‍රියතම ක්‍රීඩාංගණය තෝරන්න, වෙලාව වෙන් කරගන්න.
      </p>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="#list" class="btn btn-light btn-lg fw-semibold">
        <i class="bi bi-arrow-down-circle"></i> View Grounds
      </a>
    </div>
  </div>
</section>

<h3 id="list" class="fw-bold mb-4">Available Playgrounds</h3>

<?php if (!$rows): ?>
  <div class="alert alert-info">දැනට playground කිසිවක් නොමැත.</div>
<?php else: ?>
  <div class="row g-4">
  <?php foreach ($rows as $p): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card playground-card h-100">
        <img src="<?= $p['image'] ? e(UPLOAD_URL.$p['image']) : 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800' ?>"
             alt="<?= e($p['name']) ?>">
        <div class="card-body">
          <h5 class="fw-bold mb-1"><?= e($p['name']) ?></h5>
          <p class="text-muted small mb-2">
            <i class="bi bi-geo-alt"></i> <?= e($p['location']) ?>
          </p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="badge bg-success-subtle text-success-emphasis fs-6">
              Rs. <?= number_format($p['price_per_hour'], 2) ?> /hr
            </span>
            <a href="playground.php?id=<?= $p['id'] ?>" class="btn btn-success btn-sm">
              Book Now <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>