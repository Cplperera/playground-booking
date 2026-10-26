<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $conf  = $_POST['confirm']    ?? '';

    if ($name === '')  $errors[] = 'නම අවශ්‍යයි.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email එක වැරදියි.';
    if (strlen($pass) < 6) $errors[] = 'Password අඩුම තරමේ අකුරු 6ක් විය යුතුයි.';
    if ($pass !== $conf) $errors[] = 'Password දෙක සමාන නැහැ.';

    // Email දැනටමත් තියෙනවද?
    if (!$errors) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'මේ email එක දැනටමත් register වෙලා.';
    }

    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $ins  = $pdo->prepare("INSERT INTO users (name,email,phone,password_hash)
                               VALUES (?,?,?,?)");
        $ins->execute([$name,$email,$phone,$hash]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['name']    = $name;
        $_SESSION['role']    = 'user';
        $_SESSION['flash']   = 'සාර්ථකව register වුණා! සාදරයෙන් පිළිගන්නාවා.';
        header('Location: index.php'); exit;
    }
}

$pageTitle = 'Register';
require 'includes/header.php';
?>

<div class="card auth-card shadow-sm border-0">
  <div class="card-body p-4">
    <h3 class="fw-bold text-center mb-1">Create Account</h3>
    <p class="text-center text-muted small mb-4">Bookings කරන්න account එකක් හදන්න</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger small">
        <?php foreach ($errors as $e) echo '<div>• ' . e($e) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required
               value="<?= e($_POST['name'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control"
               value="<?= e($_POST['phone'] ?? '') ?>">
      </div>
      <div class="row">
        <div class="col-6 mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-6 mb-3">
          <label class="form-label">Confirm</label>
          <input type="password" name="confirm" class="form-control" required>
        </div>
      </div>
      <button class="btn btn-success w-100 py-2 fw-semibold">Register</button>
      <p class="text-center small mt-3 mb-0">
        දැනටමත් account එකක් තියෙනවද?
        <a href="login.php" class="fw-semibold">Login</a>
      </p>
    </form>
  </div>
</div>

<?php require 'includes/footer.php'; ?>