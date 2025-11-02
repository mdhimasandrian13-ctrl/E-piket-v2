<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Logout
 * ============================================
 * File: auth/logout.php
 * Deskripsi: Proses logout dan destroy session
 * ============================================
 */

session_start();

// Destroy semua session
session_unset();
session_destroy();

// Redirect ke halaman login dengan pesan
header("Location: login.php?logout=success");
exit();
?>