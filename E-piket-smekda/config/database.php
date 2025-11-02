<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Database Configuration
 * ============================================
 * File: config/database.php
 * Deskripsi: File koneksi database menggunakan MySQLi
 * ============================================
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'epiket_smekda');

// Zona Waktu
date_default_timezone_set('Asia/Jakarta');

// Buat koneksi ke database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("
    <div style='font-family: Arial; max-width: 600px; margin: 100px auto; padding: 30px; background: #fee; border-left: 5px solid #c00; border-radius: 8px;'>
        <h2 style='color: #c00;'>❌ Koneksi Database Gagal!</h2>
        <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>
        <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
        <p><strong>Troubleshooting:</strong></p>
        <ol>
            <li>Pastikan XAMPP sudah running (Apache & MySQL)</li>
            <li>Cek apakah database <strong>epiket_smekda</strong> sudah dibuat</li>
            <li>Periksa username dan password database</li>
        </ol>
    </div>
    ");
}

// Set charset ke UTF-8
mysqli_set_charset($conn, "utf8");

/**
 * Fungsi untuk menjalankan query
 */
function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("Database Error: " . mysqli_error($conn));
        return false;
    }
    return $result;
}

/**
 * Fungsi untuk escape string (mencegah SQL Injection)
 */
function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($string));
}

/**
 * Fungsi untuk mendapatkan satu baris data
 */
function fetch_single($query) {
    $result = query($query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Fungsi untuk mendapatkan semua baris data
 */
function fetch_all($query) {
    $result = query($query);
    $data = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    
    return $data;
}

/**
 * Fungsi untuk menghitung jumlah baris
 */
function count_rows($query) {
    $result = query($query);
    if ($result) {
        return mysqli_num_rows($result);
    }
    return 0;
}

/**
 * Fungsi untuk mendapatkan ID terakhir yang di-insert
 */
function last_insert_id() {
    global $conn;
    return mysqli_insert_id($conn);
}

/**
 * Fungsi untuk format tanggal Indonesia
 */
function format_tanggal_indonesia($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $split = explode('-', $date);
    if (count($split) == 3) {
        return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }
    return $date;
}

/**
 * Fungsi untuk format nama hari Indonesia
 */
function get_hari_indonesia($date) {
    $hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $day = date('l', strtotime($date));
    return $hari[$day];
}

?>