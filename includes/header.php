<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>PlayZone Booking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>index.php">
      <i class="bi bi-dribbble"></i> PlayZone
    </a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">Playgrounds</a></li>

        <?php if (isLoggedIn()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>my_bookings.php">My Bookings</a></li>
          <?php if (isAdmin()): ?>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/index.php">Admin</a></li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
              <i class="bi bi-person-circle"></i> <?= e($_SESSION['name']) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>logout.php">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>login.php">Login</a></li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-light btn-sm fw-semibold" href="<?= BASE_URL ?>register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="py-4">
<div class="container">

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= e($_SESSION['flash']) ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <?= e($_SESSION['flash_error']) ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>