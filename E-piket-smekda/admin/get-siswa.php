<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Get Siswa (AJAX)
 * ============================================
 * File: admin/get-siswa.php
 * Deskripsi: Helper untuk load siswa berdasarkan kelas
 * ============================================
 */

session_start();

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode([]);
    exit();
}

require_once '../config/database.php';

if (isset($_GET['class_id'])) {
    $class_id = escape($_GET['class_id']);
    
    $siswa_list = fetch_all("SELECT id, nis, full_name FROM users 
                             WHERE class_id = '$class_id' 
                             AND role = 'siswa' 
                             AND is_active = 1 
                             ORDER BY full_name ASC");
    
    header('Content-Type: application/json');
    echo json_encode($siswa_list);
} else {
    echo json_encode([]);
}
?>