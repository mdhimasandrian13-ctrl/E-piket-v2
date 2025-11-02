<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Dashboard Admin
 * ============================================
 * File: admin/dashboard.php
 * Deskripsi: Dashboard utama untuk Administrator
 * ============================================
 */

session_start();

// Cek apakah sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database
require_once '../config/database.php';

// Ambil data statistik
$total_siswa = count_rows("SELECT * FROM users WHERE role = 'siswa'");
$total_guru = count_rows("SELECT * FROM users WHERE role = 'guru'");
$total_kelas = count_rows("SELECT * FROM classes");
$total_jadwal = count_rows("SELECT * FROM schedules");

// Statistik hari ini
$today = date('Y-m-d');
$jadwal_hari_ini = count_rows("SELECT * FROM schedules WHERE schedule_date = '$today'");
$hadir_hari_ini = count_rows("SELECT * FROM attendances WHERE attendance_date = '$today' AND status = 'hadir'");
$alpha_hari_ini = count_rows("SELECT * FROM attendances WHERE attendance_date = '$today' AND status = 'alpha'");

// Ambil data siswa terbaru
$siswa_terbaru = fetch_all("SELECT u.*, c.class_name FROM users u 
                            LEFT JOIN classes c ON u.class_id = c.id 
                            WHERE u.role = 'siswa' 
                            ORDER BY u.created_at DESC LIMIT 5");

// Ambil jadwal hari ini
$jadwal_today = fetch_all("SELECT s.*, u.full_name, u.nis, c.class_name 
                          FROM schedules s
                          JOIN users u ON s.student_id = u.id
                          JOIN classes c ON s.class_id = c.id
                          WHERE s.schedule_date = '$today'
                          ORDER BY c.class_name");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - E-piket SMEKDA</title>
    
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
            background: #f5f6fa;
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
        
        /* Main Content */
        .main-content {
            margin-left: 0;
            padding: 20px;
            padding-top: 90px;
            transition: margin-left 0.3s ease;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .top-bar h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-weight: 600;
            font-size: 18px;
        }
        
        .top-bar small {
            display: block;
            color: #999;
            font-size: 13px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
        }
        
        .logout-btn {
            padding: 8px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            color: white;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-info h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .stat-info p {
            color: #999;
            font-size: 14px;
            margin: 0;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .stat-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        /* Content Section */
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .btn-primary-custom {
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table-custom {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-custom thead {
            background: #f8f9fa;
        }
        
        .table-custom th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 14px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table-custom td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
        }
        
        .table-custom tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .quick-action-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .quick-action-card i {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
        
        .quick-action-card span {
            font-size: 14px;
            font-weight: 500;
            color: white;
        }
        
        /* Responsive */
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
            
            .top-bar {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table-responsive {
                font-size: 12px;
            }
            
            .table-custom th,
            .table-custom td {
                padding: 10px 8px;
                font-size: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h4><i class="fas fa-tachometer-alt"></i> Dashboard Administrator</h4>
                <small>Selamat datang, <?php echo $_SESSION['full_name']; ?>!</small>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <strong style="display: block; font-size: 14px;"><?php echo $_SESSION['full_name']; ?></strong>
                    <small style="color: #999;">Administrator</small>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_siswa; ?></h3>
                    <p>Total Siswa</p>
                </div>
                <div class="stat-icon blue">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_guru; ?></h3>
                    <p>Total Guru</p>
                </div>
                <div class="stat-icon green">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_kelas; ?></h3>
                    <p>Total Kelas</p>
                </div>
                <div class="stat-icon orange">
                    <i class="fas fa-school"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $jadwal_hari_ini; ?></h3>
                    <p>Piket Hari Ini</p>
                </div>
                <div class="stat-icon purple">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="quick-actions">
                <a href="kelola-siswa.php?action=add" class="quick-action-card">
                    <i class="fas fa-user-plus"></i>
                    <span>Tambah Siswa</span>
                </a>
                <a href="kelola-guru.php?action=add" class="quick-action-card">
                    <i class="fas fa-user-tie"></i>
                    <span>Tambah Guru</span>
                </a>
                <a href="kelola-kelas.php?action=add" class="quick-action-card">
                    <i class="fas fa-plus-circle"></i>
                    <span>Buat Kelas</span>
                </a>
                <a href="kelola-jadwal.php?action=generate" class="quick-action-card">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Generate Jadwal</span>
                </a>
            </div>
        </div>
        
        <!-- Jadwal Piket Hari Ini -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-calendar-day"></i> Jadwal Piket Hari Ini (<?php echo format_tanggal_indonesia($today); ?>)</h5>
                <a href="kelola-jadwal.php" class="btn-primary-custom">
                    <i class="fas fa-eye"></i> Lihat Semua
                </a>
            </div>
            
            <?php if (count($jadwal_today) > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Shift</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($jadwal_today as $jadwal): 
                            $check_absen = fetch_single("SELECT status FROM attendances WHERE schedule_id = {$jadwal['id']} AND attendance_date = '$today'");
                            $status = $check_absen ? $check_absen['status'] : 'belum_absen';
                            
                            $badge_class = 'badge-warning';
                            $status_text = 'Belum Absen';
                            
                            if ($status == 'hadir') {
                                $badge_class = 'badge-success';
                                $status_text = 'Hadir';
                            } elseif ($status == 'alpha') {
                                $badge_class = 'badge-danger';
                                $status_text = 'Alpha';
                            } elseif ($status == 'izin') {
                                $badge_class = 'badge-info';
                                $status_text = 'Izin';
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $jadwal['nis']; ?></td>
                            <td><?php echo $jadwal['full_name']; ?></td>
                            <td><?php echo $jadwal['class_name']; ?></td>
                            <td><?php echo ucfirst($jadwal['shift']); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center" style="padding: 40px; color: #999;">
                <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>Tidak ada jadwal piket untuk hari ini</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Siswa Terbaru -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-users"></i> Siswa Terdaftar Terbaru</h5>
                <a href="kelola-siswa.php" class="btn-primary-custom">
                    <i class="fas fa-eye"></i> Lihat Semua
                </a>
            </div>
            
            <?php if (count($siswa_terbaru) > 0): ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama Lengkap</th>
                            <th>Kelas</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siswa_terbaru as $siswa): ?>
                        <tr>
                            <td><?php echo $siswa['nis']; ?></td>
                            <td><?php echo $siswa['full_name']; ?></td>
                            <td><?php echo $siswa['class_name'] ?? '-'; ?></td>
                            <td><?php echo $siswa['email']; ?></td>
                            <td>
                                <?php if ($siswa['is_active'] == 1): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center" style="padding: 40px; color: #999;">
                <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>Belum ada data siswa</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>