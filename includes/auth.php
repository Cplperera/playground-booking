<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function requireAdmin(): void {
    if (!isAdmin()) { header('Location: ../login.php'); exit; }
}