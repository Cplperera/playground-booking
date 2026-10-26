<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND status=1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        session_regenerate_id(true);           // session fixation නවත්තන්න
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['name']    = $u['name'];
        $_SESSION['role']    = $u['role'];
        $_SESSION['flash']   = 'නැවත සාදරයෙන් පිළිගන්නවා, ' . $u['name'] . '!';

        header('Location: ' . ($u['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
        exit;
    } else {
        $error = 'Email හෝ password වැරදියි.';
    }
}

$pageTitle = 'Login';
require 'includes/header.php';
?>

<div class="card auth-card shadow-sm border-0">
  <div class="card-body p-4">
    <h3 class="fw-bold text-center mb-1">Welcome Back</h3>
    <p class="text-center text-muted small mb-4">Booking කරන්න login වෙන්න</p>

    <?php if ($error): ?>
      <div class="alert alert-danger small"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-success w-100 py-2 fw-semibold">Login</button>
      <p class="text-center small mt-3 mb-0">
        Account එකක් නැද්ද? <a href="register.php" class="fw-semibold">Register</a>
      </p>
    </form>
  </div>
</div>

<?php require 'includes/footer.php'; ?>