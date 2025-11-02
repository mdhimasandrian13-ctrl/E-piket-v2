<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Halaman Pengaturan Admin
 * ============================================
 * File: admin/pengaturan.php
 * Deskripsi: Pengaturan Sistem E-Piket (Waktu, Sekolah, Admin)
 * ============================================
 */

session_start();

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include koneksi database
require_once '../config/database.php';

$success_message = '';
$error_message = '';

// Handle Save Settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $check_in_start = escape($_POST['check_in_start']);
    $check_in_end = escape($_POST['check_in_end']);
    $check_out_start = escape($_POST['check_out_start']);
    $check_out_end = escape($_POST['check_out_end']);
    $school_name = escape($_POST['school_name']);
    $academic_year = escape($_POST['academic_year']);
    
    // Update settings
    $settings_to_update = [
        'check_in_start' => $check_in_start,
        'check_in_end' => $check_in_end,
        'check_out_start' => $check_out_start,
        'check_out_end' => $check_out_end,
        'school_name' => $school_name,
        'academic_year' => $academic_year
    ];
    
    foreach ($settings_to_update as $key => $value) {
        $query = "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'";
        query($query);
    }
    
    $success_message = 'Pengaturan berhasil disimpan!';
}

// Handle Delete Admin
if (isset($_GET['delete_admin'])) {
    $admin_id = escape($_GET['delete_admin']);
    
    // Pastikan tidak menghapus admin terakhir dan tidak menghapus diri sendiri
    if ($admin_id == $_SESSION['user_id']) {
        $error_message = 'Anda tidak bisa menghapus akun Anda sendiri!';
    } else {
        $count_query = query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
        $count = mysqli_fetch_assoc($count_query);
        
        if ($count['total'] > 1) {
            query("DELETE FROM users WHERE id = $admin_id AND role = 'admin'");
            $success_message = 'Admin berhasil dihapus!';
        } else {
            $error_message = 'Tidak bisa menghapus admin karena hanya tersisa 1 admin!';
        }
    }
}

// Get current settings
$settings_query = query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_query)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get all admins
$admins_query = query("SELECT id, username, full_name, email FROM users WHERE role = 'admin' ORDER BY id ASC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - E-piket SMEKDA</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        /* Hamburger Button */
        .hamburger-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 2000;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }
        
        .hamburger-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: -260px;
            top: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            z-index: 1999;
            overflow-y: auto;
            transition: left 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.active {
            left: 0;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1998;
            display: none;
            transition: opacity 0.3s;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        .sidebar-header {
            color: white;
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
            margin-top: 40px;
        }
        
        .sidebar-header h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .close-sidebar {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .close-sidebar:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            margin-left: 0;
            padding: 20px;
            padding-top: 90px;
            transition: margin-left 0.3s ease;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .header-section h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .header-section h1 i {
            color: #667eea;
        }

        .btn-kembali {
            padding: 12px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-kembali:hover {
            background: #5568d3;
            transform: translateY(-2px);
            color: white;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .nav-tabs {
            border-bottom: 2px solid #e0e0e0;
            gap: 10px;
            background: white;
            padding: 0 20px;
            border-radius: 15px 15px 0 0;
            margin-bottom: 0;
        }

        .nav-link {
            color: #666;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .nav-link:hover {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .nav-link.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: transparent;
        }

        .tab-content {
            background: white;
            padding: 30px;
            border-radius: 0 15px 15px 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .form-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h3 i {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
            display: block;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            width: 100%;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            outline: none;
        }

        .time-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }

        .time-group h5 {
            grid-column: 1 / -1;
            font-size: 15px;
            font-weight: 600;
            color: #667eea;
            margin: 0 0 10px 0;
        }

        .btn-save {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-add {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .btn-add:hover {
            background: #229954;
            transform: translateY(-2px);
            color: white;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
        }

        .table thead th {
            border-bottom: 2px solid #e0e0e0;
            color: #333;
            font-weight: 600;
            padding: 15px;
            font-size: 14px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e0e0e0;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .btn-delete {
            padding: 6px 12px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        @media (max-width: 768px) {
            .hamburger-btn {
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
            
            .main-content {
                padding: 15px;
                padding-top: 85px;
            }
            
            .header-section {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .tab-content {
                padding: 15px;
            }

            .time-group {
                grid-template-columns: 1fr;
            }

            .nav-tabs {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Header Section -->
        <div class="header-section">
            <h1><i class="fas fa-cog"></i> Pengaturan Sistem</h1>
            <a href="dashboard.php" class="btn-kembali">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-waktu" data-bs-toggle="tab" data-bs-target="#content-waktu" type="button" role="tab">
                    <i class="fas fa-clock"></i> Waktu Piket
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-sekolah" data-bs-toggle="tab" data-bs-target="#content-sekolah" type="button" role="tab">
                    <i class="fas fa-school"></i> Informasi Sekolah
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-admin" data-bs-toggle="tab" data-bs-target="#content-admin" type="button" role="tab">
                    <i class="fas fa-user-shield"></i> Manajemen Admin
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Tab 1: Waktu Piket -->
            <div class="tab-pane fade show active" id="content-waktu" role="tabpanel">
                <div class="form-section">
                    <h3><i class="fas fa-hourglass-start"></i> Pengaturan Waktu Piket</h3>
                    
                    <form method="POST" action="">
                        <div class="time-group">
                            <h5>Waktu Check-in (Masuk)</h5>
                            
                            <div class="form-group">
                                <label for="check_in_start">Jam Mulai Check-in</label>
                                <input type="time" name="check_in_start" id="check_in_start" class="form-control" 
                                    value="<?php echo substr($settings['check_in_start'] ?? '06:00:00', 0, 5); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="check_in_end">Jam Berakhir Check-in</label>
                                <input type="time" name="check_in_end" id="check_in_end" class="form-control" 
                                    value="<?php echo substr($settings['check_in_end'] ?? '07:00:00', 0, 5); ?>" required>
                            </div>
                        </div>

                        <div class="time-group">
                            <h5>Waktu Check-out (Pulang)</h5>
                            
                            <div class="form-group">
                                <label for="check_out_start">Jam Mulai Check-out</label>
                                <input type="time" name="check_out_start" id="check_out_start" class="form-control" 
                                    value="<?php echo substr($settings['check_out_start'] ?? '14:00:00', 0, 5); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="check_out_end">Jam Berakhir Check-out</label>
                                <input type="time" name="check_out_end" id="check_out_end" class="form-control" 
                                    value="<?php echo substr($settings['check_out_end'] ?? '15:00:00', 0, 5); ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="save_settings" class="btn-save">
                            <i class="fas fa-save"></i> Simpan Waktu Piket
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Informasi Sekolah -->
            <div class="tab-pane fade" id="content-sekolah" role="tabpanel">
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Informasi Sekolah</h3>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="school_name">Nama Sekolah</label>
                            <input type="text" name="school_name" id="school_name" class="form-control" 
                                value="<?php echo $settings['school_name'] ?? 'SMKN 2 SURABAYA'; ?>" required>
                            <small class="text-muted">Masukkan nama lengkap sekolah</small>
                        </div>

                        <div class="form-group">
                            <label for="academic_year">Tahun Akademik</label>
                            <input type="text" name="academic_year" id="academic_year" class="form-control" 
                                value="<?php echo $settings['academic_year'] ?? '2024/2025'; ?>" placeholder="Contoh: 2024/2025" required>
                            <small class="text-muted">Format: YYYY/YYYY (Contoh: 2024/2025)</small>
                        </div>

                        <button type="submit" name="save_settings" class="btn-save">
                            <i class="fas fa-save"></i> Simpan Informasi Sekolah
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tab 3: Manajemen Admin -->
            <div class="tab-pane fade" id="content-admin" role="tabpanel">
                <div class="form-section">
                    <h3><i class="fas fa-user-shield"></i> Daftar Admin</h3>
                    
                    <a href="#" class="btn-add" onclick="alert('Fitur tambah admin akan dilanjutkan'); return false;">
                        <i class="fas fa-plus"></i> Tambah Admin Baru
                    </a>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($admins_query && mysqli_num_rows($admins_query) > 0) {
                                    while ($admin = mysqli_fetch_assoc($admins_query)):
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $admin['username']; ?></td>
                                    <td><?php echo $admin['full_name']; ?></td>
                                    <td><?php echo $admin['email']; ?></td>
                                    <td>
                                        <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete_admin=<?php echo $admin['id']; ?>" 
                                               class="btn-delete" 
                                               onclick="return confirm('Yakin ingin menghapus admin ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted text-sm">(Akun Anda)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                } else {
                                    echo '<tr><td colspan="5" class="text-center text-muted">Tidak ada data admin</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto hide success message after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>