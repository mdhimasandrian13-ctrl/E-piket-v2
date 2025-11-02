<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Dashboard Guru
 * ============================================
 * File: guru/dashboard.php
 * Deskripsi: Dashboard untuk guru/wali kelas
 * ============================================
 */

session_start();

// Cek apakah sudah login dan role guru
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';

$guru_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// ============================================
// AMBIL DATA KELAS YANG DIAMPU
// ============================================
$kelas_diampu = fetch_all("SELECT * FROM classes WHERE homeroom_teacher_id = '$guru_id' AND is_active = 1");

if (count($kelas_diampu) == 0) {
    $kelas_ids = array();
} else {
    $kelas_ids = array_column($kelas_diampu, 'id');
}

// ============================================
// STATISTIK UMUM
// ============================================
$total_siswa = 0;
$jadwal_hari_ini = 0;
$hadir_hari_ini = 0;
$alpha_hari_ini = 0;

if (count($kelas_ids) > 0) {
    $kelas_str = implode(',', $kelas_ids);
    
    // Total siswa
    $total_siswa = count_rows("SELECT * FROM users WHERE class_id IN ($kelas_str) AND role = 'siswa' AND is_active = 1");
    
    // Jadwal hari ini
    $jadwal_hari_ini = count_rows("SELECT * FROM schedules WHERE schedule_date = '$today' AND class_id IN ($kelas_str)");
    
    // Hadir hari ini
    $hadir_hari_ini = count_rows("SELECT * FROM attendances WHERE attendance_date = '$today' AND status = 'hadir' 
                                  AND student_id IN (SELECT id FROM users WHERE class_id IN ($kelas_str))");
    
    // Alpha hari ini
    $alpha_hari_ini = count_rows("SELECT * FROM attendances WHERE attendance_date = '$today' AND status = 'alpha' 
                                  AND student_id IN (SELECT id FROM users WHERE class_id IN ($kelas_str))");
}

// ============================================
// JADWAL PIKET HARI INI
// ============================================
$jadwal_today = array();
if (count($kelas_ids) > 0) {
    $kelas_str = implode(',', $kelas_ids);
    $jadwal_today = fetch_all("SELECT s.*, 
                               u.full_name, 
                               u.nis, 
                               c.class_name,
                               a.status as status_absen,
                               a.check_in_time
                               FROM schedules s
                               JOIN users u ON s.student_id = u.id
                               JOIN classes c ON s.class_id = c.id
                               LEFT JOIN attendances a ON s.id = a.schedule_id AND s.schedule_date = a.attendance_date
                               WHERE s.schedule_date = '$today' AND s.class_id IN ($kelas_str)
                               ORDER BY c.class_name, u.full_name");
}

// ============================================
// STATISTIK KEHADIRAN BULANAN
// ============================================
$bulan_ini = date('Y-m');
$statistik_bulanan = array();

if (count($kelas_ids) > 0) {
    $kelas_str = implode(',', $kelas_ids);
    
    $statistik_bulanan = fetch_all("SELECT 
                                    u.id,
                                    u.full_name,
                                    u.nis,
                                    c.class_name,
                                    COUNT(a.id) as total_piket,
                                    SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
                                    SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
                                    SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
                                    SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as total_alpha
                                    FROM users u
                                    LEFT JOIN classes c ON u.class_id = c.id
                                    LEFT JOIN attendances a ON u.id = a.student_id AND YEAR(a.attendance_date) = YEAR(NOW()) AND MONTH(a.attendance_date) = MONTH(NOW())
                                    WHERE u.class_id IN ($kelas_str) AND u.role = 'siswa' AND u.is_active = 1
                                    GROUP BY u.id, u.full_name, u.nis, c.class_name
                                    ORDER BY c.class_name, u.full_name");
}

// ============================================
// SISWA BELUM ABSEN HARI INI
// ============================================
$siswa_belum_absen = array();
if (count($kelas_ids) > 0) {
    $kelas_str = implode(',', $kelas_ids);
    
    $siswa_belum_absen = fetch_all("SELECT s.*, 
                                    u.full_name, 
                                    u.nis, 
                                    c.class_name
                                    FROM schedules s
                                    JOIN users u ON s.student_id = u.id
                                    JOIN classes c ON s.class_id = c.id
                                    LEFT JOIN attendances a ON s.id = a.schedule_id AND s.schedule_date = a.attendance_date
                                    WHERE s.schedule_date = '$today' 
                                    AND s.class_id IN ($kelas_str)
                                    AND a.id IS NULL
                                    ORDER BY c.class_name, u.full_name");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - E-piket SMEKDA</title>
    
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
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            color: white;
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
        }
        
        .sidebar-header h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
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
            margin-left: 260px;
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .top-bar h4 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
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
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
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
        .stat-icon.red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
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
        }
        
        .section-header h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .table-custom {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-custom thead {
            background: #f8f9fa;
        }
        
        .table-custom th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 13px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table-custom td {
            padding: 15px 12px;
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
        .badge-light { background: #e2e3e5; color: #383d41; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>E-PIKET</h3>
            <p>SMEKDA Guru</p>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="monitoring.php" class="nav-link">
                    <i class="fas fa-binoculars"></i>
                    <span>Monitoring Piket</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="laporan.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h4>Dashboard Guru</h4>
                <small style="color: #999;">Selamat datang, <?php echo $_SESSION['full_name']; ?>!</small>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <strong style="display: block; font-size: 14px;"><?php echo $_SESSION['full_name']; ?></strong>
                    <small style="color: #999;">Guru</small>
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
                    <h3><?php echo count($kelas_diampu); ?></h3>
                    <p>Kelas yang Diampu</p>
                </div>
                <div class="stat-icon blue">
                    <i class="fas fa-door-open"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_siswa; ?></h3>
                    <p>Total Siswa</p>
                </div>
                <div class="stat-icon green">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $hadir_hari_ini; ?></h3>
                    <p>Hadir Hari Ini</p>
                </div>
                <div class="stat-icon orange">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $alpha_hari_ini; ?></h3>
                    <p>Alpha Hari Ini</p>
                </div>
                <div class="stat-icon red">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
        
        <!-- Jadwal Hari Ini -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-calendar-day"></i> Jadwal Piket Hari Ini (<?php echo format_tanggal_indonesia($today); ?>)</h5>
            </div>
            
            <?php if (count($jadwal_today) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kelas</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Shift</th>
                            <th>Check In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($jadwal_today as $jadwal): 
                            $badge_class = 'badge-warning';
                            $status_text = 'Belum Absen';
                            
                            if ($jadwal['status_absen'] == 'hadir') {
                                $badge_class = 'badge-success';
                                $status_text = 'Hadir';
                            } elseif ($jadwal['status_absen'] == 'alpha') {
                                $badge_class = 'badge-danger';
                                $status_text = 'Alpha';
                            } elseif ($jadwal['status_absen'] == 'izin') {
                                $badge_class = 'badge-info';
                                $status_text = 'Izin';
                            } elseif ($jadwal['status_absen'] == 'sakit') {
                                $badge_class = 'badge-info';
                                $status_text = 'Sakit';
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $jadwal['class_name']; ?></td>
                            <td><?php echo $jadwal['nis']; ?></td>
                            <td><?php echo $jadwal['full_name']; ?></td>
                            <td><?php echo ucfirst($jadwal['shift']); ?></td>
                            <td><?php echo $jadwal['check_in_time'] ?? '-'; ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>Tidak ada jadwal piket untuk hari ini</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Siswa Belum Absen -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-exclamation-triangle"></i> Siswa Belum Absen Hari Ini</h5>
            </div>
            
            <?php if (count($siswa_belum_absen) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kelas</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($siswa_belum_absen as $siswa): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $siswa['class_name']; ?></td>
                            <td><?php echo $siswa['nis']; ?></td>
                            <td><?php echo $siswa['full_name']; ?></td>
                            <td><span class="badge badge-warning">Belum Absen</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-double"></i>
                <p>Semua siswa sudah absen</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistik Kehadiran Bulanan -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-chart-bar"></i> Statistik Kehadiran Bulan Ini</h5>
            </div>
            
            <?php if (count($statistik_bulanan) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kelas</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Total</th>
                            <th>Hadir</th>
                            <th>Izin</th>
                            <th>Sakit</th>
                            <th>Alpha</th>
                            <th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($statistik_bulanan as $stat): 
                            $persentase = $stat['total_piket'] > 0 ? round(($stat['total_hadir'] / $stat['total_piket']) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $stat['class_name']; ?></td>
                            <td><?php echo $stat['nis']; ?></td>
                            <td><?php echo $stat['full_name']; ?></td>
                            <td><strong><?php echo $stat['total_piket']; ?></strong></td>
                            <td><span class="badge badge-success"><?php echo $stat['total_hadir']; ?></span></td>
                            <td><span class="badge badge-info"><?php echo $stat['total_izin']; ?></span></td>
                            <td><span class="badge badge-info"><?php echo $stat['total_sakit']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $stat['total_alpha']; ?></span></td>
                            <td><strong><?php echo $persentase; ?>%</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Tidak ada data statistik kehadiran</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto refresh setiap 30 detik
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>