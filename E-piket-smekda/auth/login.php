<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Halaman Login
 * ============================================
 * File: auth/login.php
 * Deskripsi: Halaman login untuk Admin, Guru, dan Siswa
 * ============================================
 */

session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: ../$role/dashboard.php");
    exit();
}

// Include koneksi database
require_once '../config/database.php';

$error = '';
$success = '';

// Proses Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = escape($_POST['username']);
    $password = md5($_POST['password']); // MD5 sesuai dengan database
    $role = escape($_POST['role']);
    
    // Validasi input
    if (empty($username) || empty($password) || empty($role)) {
        $error = 'Semua field harus diisi!';
    } else {
        // Query cek user
        $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND role = '$role' AND is_active = 1";
        $result = query($query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nis'] = $user['nis']; // untuk siswa
            $_SESSION['profile_photo'] = $user['profile_photo'];
            
            // Redirect sesuai role
            header("Location: ../{$user['role']}/dashboard.php");
            exit();
        } else {
            $error = 'Username, Password, atau Role salah!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-piket SMEKDA</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../asset/css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="school-logo">
                <i class="fas fa-school"></i>
            </div>
            <h1>E-piket SMEKDA</h1>
            <p>Sistem Manajemen Absensi Piket Siswa<br>SMKN 2 SURABAYA</p>
            <div style="margin-top: 30px; opacity: 0.8;">
                <p style="font-size: 14px; margin-bottom: 10px;">
                    <i class="fas fa-check-circle"></i> Absensi Real-time
                </p>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    <i class="fas fa-check-circle"></i> Monitoring Piket
                </p>
                <p style="font-size: 14px;">
                    <i class="fas fa-check-circle"></i> Laporan Otomatis
                </p>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <h2>Selamat Datang!</h2>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Role Selection -->
                <div class="role-tabs">
                    <div class="role-tab active" data-role="admin">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin</span>
                    </div>
                    <div class="role-tab" data-role="guru">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Guru</span>
                    </div>
                    <div class="role-tab" data-role="siswa">
                        <i class="fas fa-user-graduate"></i>
                        <span>Siswa</span>
                    </div>
                </div>
                
                <input type="hidden" name="role" id="role" value="admin">
                
                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username / NIS / NIP</label>
                    <div class="input-group-custom">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group-custom">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>
                
                <!-- Login Button -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> LOGIN
                </button>
            </form>
            
            <div class="footer-text">
                © 2024 E-piket SMEKDA. All rights reserved.
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Role Tab Selection
            const roleTabs = document.querySelectorAll('.role-tab');
            const roleInput = document.getElementById('role');
            
            if (roleTabs.length > 0) {
                roleTabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        // Remove active class from all tabs
                        roleTabs.forEach(t => t.classList.remove('active'));
                        
                        // Add active class to clicked tab
                        this.classList.add('active');
                        
                        // Update hidden input value
                        roleInput.value = this.dataset.role;
                    });
                });
            }
            
            // Auto hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>