<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();   // admin නැත්නම් redirect

// Sidebar active link helper
$current = basename($_SERVER['PHP_SELF']);
function activeIf($file, $current) {
    return $file === $current ? 'active bg-success text-white' : '';
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>Admin — PlayZone</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background:#f4f6f9; }
  .admin-sidebar {
    min-height: 100vh; background:#1f2937; color:#cbd5e1;
    position: sticky; top: 0;
  }
  .admin-sidebar .nav-link {
    color:#cbd5e1; border-radius:8px; padding:.6rem .9rem; margin-bottom:4px;
    font-weight:500;
  }
  .admin-sidebar .nav-link:hover { background:#374151; color:#fff; }
  .admin-sidebar .nav-link.active { background:#198754 !important; color:#fff; }
  .stat-card { border:none; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.06); }
</style>
</head>
<body>

<div class="container-fluid">
<div class="row">

  <!-- SIDEBAR -->
  <aside class="col-md-3 col-lg-2 admin-sidebar p-3 d-flex flex-column">
    <a href="<?= BASE_URL ?>admin/index.php"
       class="text-white text-decoration-none fw-bold fs-5 mb-4 d-block">
      <i class="bi bi-shield-lock"></i> Admin Panel
    </a>

    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= activeIf('index.php',$current) ?>" href="index.php">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= activeIf('playgrounds.php',$current) ?>" href="playgrounds.php">
          <i class="bi bi-building"></i> Playgrounds
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= activeIf('bookings.php',$current) ?>" href="bookings.php">
          <i class="bi bi-calendar-check"></i> Bookings
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= activeIf('users.php',$current) ?>" href="users.php">
          <i class="bi bi-people"></i> Users
        </a>
      </li>

      <li class="nav-item">
  <a class="nav-link <?= activeIf('payments.php',$current) ?>" href="payments.php">
    <i class="bi bi-cash-coin"></i> Payments
  </a>
</li>
    </ul>

    <div class="mt-auto pt-4 border-top border-secondary">
      <div class="small mb-2">
        <i class="bi bi-person-circle"></i> <?= e($_SESSION['name']) ?>
      </div>
      <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-light btn-sm w-100">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="col-md-9 col-lg-10 py-4 px-md-4">