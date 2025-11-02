<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Dashboard Siswa
 * ============================================
 * File: siswa/dashboard.php
 * Deskripsi: Dashboard untuk siswa dengan fitur absensi
 * ============================================
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';

$student_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Ambil data siswa
$siswa = fetch_single("SELECT u.*, c.class_name FROM users u 
                       LEFT JOIN classes c ON u.class_id = c.id 
                       WHERE u.id = '$student_id'");

$class_id = $siswa['class_id'];

// ============================================
// PROSES ABSENSI
// ============================================
$message = '';
$message_type = '';

if (isset($_POST['absensi'])) {
    $schedule_id = escape($_POST['schedule_id']);
    
    // Cek apakah jadwal sudah ada di tabel schedules
    $schedule = fetch_single("SELECT * FROM schedules WHERE id = '$schedule_id' AND student_id = '$student_id'");
    
    if (!$schedule) {
        $message = "Jadwal tidak ditemukan atau bukan milik Anda!";
        $message_type = "danger";
    } else {
        // Cek apakah sudah absen hari ini
        $cek_absen = fetch_single("SELECT id FROM attendances WHERE schedule_id = '$schedule_id' AND attendance_date = '$today'");
        
        if ($cek_absen) {
            $message = "Anda sudah absen untuk jadwal ini!";
            $message_type = "warning";
        } else {
            // Insert absensi
            $check_in_time = date('H:i:s');
            $query = "INSERT INTO attendances (schedule_id, student_id, attendance_date, check_in_time, status) 
                      VALUES ('$schedule_id', '$student_id', '$today', '$check_in_time', 'hadir')";
            
            if (query($query)) {
                $message = "Absensi berhasil! Check-in time: $check_in_time";
                $message_type = "success";
            } else {
                $message = "Gagal melakukan absensi!";
                $message_type = "danger";
            }
        }
    }
}

// ============================================
// JADWAL PIKET HARI INI
// ============================================
$jadwal_hari_ini = fetch_all("SELECT s.*, a.id as attendance_id, a.status, a.check_in_time 
                              FROM schedules s
                              LEFT JOIN attendances a ON s.id = a.schedule_id AND a.attendance_date = '$today'
                              WHERE s.student_id = '$student_id' AND s.schedule_date = '$today'
                              ORDER BY s.shift");

// ============================================
// STATISTIK KEHADIRAN BULAN INI
// ============================================
$bulan_ini = date('Y-m');
$stat_bulan = fetch_single("SELECT 
                            COUNT(a.id) as total_jadwal,
                            SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
                            SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
                            SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
                            SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as total_alpha
                            FROM attendances a
                            JOIN schedules s ON a.schedule_id = s.id
                            WHERE a.student_id = '$student_id' 
                            AND YEAR(a.attendance_date) = YEAR(NOW())
                            AND MONTH(a.attendance_date) = MONTH(NOW())");

// Jika tidak ada data, set default
if (!$stat_bulan) {
    $stat_bulan = array(
        'total_jadwal' => 0,
        'total_hadir' => 0,
        'total_izin' => 0,
        'total_sakit' => 0,
        'total_alpha' => 0
    );
}

$persentase_hadir = $stat_bulan['total_jadwal'] > 0 ? round(($stat_bulan['total_hadir'] / $stat_bulan['total_jadwal']) * 100, 2) : 0;

// ============================================
// RIWAYAT KEHADIRAN (10 TERBARU)
// ============================================
$riwayat = fetch_all("SELECT s.schedule_date, s.day_name, s.shift, a.status, a.check_in_time, c.class_name
                      FROM schedules s
                      JOIN classes c ON s.class_id = c.id
                      LEFT JOIN attendances a ON s.id = a.schedule_id AND a.attendance_date = s.schedule_date
                      WHERE s.student_id = '$student_id'
                      ORDER BY s.schedule_date DESC
                      LIMIT 10");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - E-piket SMEKDA</title>
    
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container-custom {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .top-bar {
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
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
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
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
            cursor: pointer;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .content-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .stat-box p {
            margin: 8px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
        }
        
        .stat-box.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-box.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-box.cyan { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .jadwal-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .jadwal-info h5 {
            margin: 0 0 5px 0;
            color: #333;
            font-weight: 600;
        }
        
        .jadwal-info p {
            margin: 0;
            color: #999;
            font-size: 13px;
        }
        
        .jadwal-action {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-absensi {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-absensi:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-absensi.disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
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
            font-size: 13px;
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
    <div class="container-custom">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h4>Dashboard Siswa</h4>
                <small style="color: #999;">Selamat datang, <?php echo $_SESSION['full_name']; ?>! (<?php echo isset($siswa['nis']) ? $siswa['nis'] : '-'; ?>)</small>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <strong style="display: block; font-size: 14px;"><?php echo $_SESSION['full_name']; ?></strong>
                    <small style="color: #999;">Siswa</small>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Jadwal Hari Ini -->
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-calendar-check"></i> Jadwal Piket Hari Ini (<?php echo format_tanggal_indonesia($today); ?>)
            </div>
            
            <?php if (count($jadwal_hari_ini) > 0): ?>
                <?php foreach ($jadwal_hari_ini as $jadwal): ?>
                <div class="jadwal-item">
                    <div class="jadwal-info">
                        <h5>Shift <?php echo ucfirst($jadwal['shift']); ?></h5>
                        <p><i class="fas fa-clock"></i> <?php echo $jadwal['day_name']; ?>, <?php echo format_tanggal_indonesia($jadwal['schedule_date']); ?></p>
                    </div>
                    <div class="jadwal-action">
                        <?php if ($jadwal['attendance_id']): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i> Sudah Absen (<?php echo $jadwal['check_in_time']; ?>)
                            </span>
                        <?php else: ?>
                            <form method="POST" action="" style="margin: 0;">
                                <input type="hidden" name="schedule_id" value="<?php echo $jadwal['id']; ?>">
                                <button type="submit" name="absensi" class="btn-absensi">
                                    <i class="fas fa-check"></i> Absensi Sekarang
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Anda tidak terjadwal piket hari ini</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistik Bulan Ini -->
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Statistik Kehadiran Bulan Ini
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <h3><?php echo $stat_bulan['total_jadwal']; ?></h3>
                    <p>Total Jadwal</p>
                </div>
                <div class="stat-box green">
                    <h3><?php echo $stat_bulan['total_hadir']; ?></h3>
                    <p>Hadir</p>
                </div>
                <div class="stat-box orange">
                    <h3><?php echo $stat_bulan['total_alpha']; ?></h3>
                    <p>Alpha</p>
                </div>
                <div class="stat-box cyan">
                    <h3><?php echo $persentase_hadir; ?>%</h3>
                    <p>Persentase</p>
                </div>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-top: 15px;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong>Rincian:</strong> 
                    <span class="badge badge-success"><?php echo $stat_bulan['total_hadir']; ?> Hadir</span>
                    <span class="badge badge-info"><?php echo $stat_bulan['total_izin']; ?> Izin</span>
                    <span class="badge badge-info"><?php echo $stat_bulan['total_sakit']; ?> Sakit</span>
                    <span class="badge badge-danger"><?php echo $stat_bulan['total_alpha']; ?> Alpha</span>
                </p>
            </div>
        </div>
        
        <!-- Riwayat Kehadiran -->
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-history"></i> Riwayat Kehadiran (10 Terbaru)
            </div>
            
            <?php if (count($riwayat) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Check-in</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $h): 
                            $badge_class = 'badge-warning';
                            $status_text = 'Belum Absen';
                            
                            if ($h['status'] == 'hadir') {
                                $badge_class = 'badge-success';
                                $status_text = 'Hadir';
                            } elseif ($h['status'] == 'alpha') {
                                $badge_class = 'badge-danger';
                                $status_text = 'Alpha';
                            } elseif ($h['status'] == 'izin') {
                                $badge_class = 'badge-info';
                                $status_text = 'Izin';
                            } elseif ($h['status'] == 'sakit') {
                                $badge_class = 'badge-info';
                                $status_text = 'Sakit';
                            }
                        ?>
                        <tr>
                            <td><?php echo format_tanggal_indonesia($h['schedule_date']); ?></td>
                            <td><?php echo $h['day_name']; ?></td>
                            <td><?php echo ucfirst($h['shift']); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo $h['check_in_time'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Belum ada riwayat kehadiran</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-hide alert setelah 5 detik
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>