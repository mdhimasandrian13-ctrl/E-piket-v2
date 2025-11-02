<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Kelola Jadwal Piket
 * ============================================
 * File: admin/kelola-jadwal.php
 * Deskripsi: CRUD dan Generate Jadwal Piket
 * ============================================
 */

session_start();

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';

$message = '';
$message_type = '';

// ============================================
// PROSES GENERATE JADWAL OTOMATIS
// ============================================
if (isset($_POST['generate_jadwal'])) {
    $class_id = escape($_POST['class_id']);
    $start_date = escape($_POST['start_date']);
    $end_date = escape($_POST['end_date']);
    $jumlah_siswa_per_hari = escape($_POST['jumlah_siswa_per_hari']);
    
    // Ambil semua siswa di kelas tersebut
    $siswa_list = fetch_all("SELECT id FROM users WHERE class_id = '$class_id' AND role = 'siswa' AND is_active = 1 ORDER BY RAND()");
    
    if (count($siswa_list) == 0) {
        $message = "Tidak ada siswa di kelas ini!";
        $message_type = "danger";
    } else {
        $total_siswa = count($siswa_list);
        $siswa_index = 0;
        
        // Loop dari tanggal mulai sampai tanggal selesai
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        $success_count = 0;
        
        while ($current_date <= $end_timestamp) {
            $day_name = get_hari_indonesia(date('Y-m-d', $current_date));
            
            // Skip Sabtu dan Minggu
            if ($day_name != 'Sabtu' && $day_name != 'Minggu') {
                $schedule_date = date('Y-m-d', $current_date);
                
                // Assign siswa untuk hari ini
                for ($i = 0; $i < $jumlah_siswa_per_hari; $i++) {
                    if ($siswa_index >= $total_siswa) {
                        $siswa_index = 0; // Reset ke awal jika sudah habis
                    }
                    
                    $student_id = $siswa_list[$siswa_index]['id'];
                    
                    // Cek apakah sudah ada jadwal untuk siswa ini di tanggal ini
                    $cek_jadwal = count_rows("SELECT * FROM schedules WHERE student_id = '$student_id' AND schedule_date = '$schedule_date'");
                    
                    if ($cek_jadwal == 0) {
                        $query = "INSERT INTO schedules (class_id, student_id, schedule_date, day_name, shift) 
                                  VALUES ('$class_id', '$student_id', '$schedule_date', '$day_name', 'pagi')";
                        
                        if (query($query)) {
                            $success_count++;
                        }
                    }
                    
                    $siswa_index++;
                }
            }
            
            // Tambah 1 hari
            $current_date = strtotime('+1 day', $current_date);
        }
        
        if ($success_count > 0) {
            $message = "Berhasil generate $success_count jadwal piket!";
            $message_type = "success";
        } else {
            $message = "Jadwal sudah ada atau tidak ada yang bisa di-generate!";
            $message_type = "warning";
        }
    }
}

// ============================================
// PROSES TAMBAH JADWAL MANUAL
// ============================================
if (isset($_POST['tambah_jadwal'])) {
    $class_id = escape($_POST['class_id']);
    $student_id = escape($_POST['student_id']);
    $schedule_date = escape($_POST['schedule_date']);
    $shift = escape($_POST['shift']);
    
    $day_name = get_hari_indonesia($schedule_date);
    
    // Cek duplikat
    $cek = count_rows("SELECT * FROM schedules WHERE student_id = '$student_id' AND schedule_date = '$schedule_date' AND shift = '$shift'");
    
    if ($cek > 0) {
        $message = "Siswa ini sudah terjadwal di tanggal dan shift yang sama!";
        $message_type = "danger";
    } else {
        $query = "INSERT INTO schedules (class_id, student_id, schedule_date, day_name, shift) 
                  VALUES ('$class_id', '$student_id', '$schedule_date', '$day_name', '$shift')";
        
        if (query($query)) {
            $message = "Jadwal berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Gagal menambahkan jadwal!";
            $message_type = "danger";
        }
    }
}

// ============================================
// PROSES HAPUS JADWAL
// ============================================
if (isset($_GET['delete'])) {
    $id = escape($_GET['delete']);
    
    if (query("DELETE FROM schedules WHERE id = '$id'")) {
        $message = "Jadwal berhasil dihapus!";
        $message_type = "success";
    } else {
        $message = "Gagal menghapus jadwal!";
        $message_type = "danger";
    }
}

// ============================================
// PROSES HAPUS JADWAL BERDASARKAN RANGE
// ============================================
if (isset($_POST['hapus_range'])) {
    $class_id_delete = escape($_POST['class_id_delete']);
    $start_date_delete = escape($_POST['start_date_delete']);
    $end_date_delete = escape($_POST['end_date_delete']);
    
    $query = "DELETE FROM schedules WHERE class_id = '$class_id_delete' 
              AND schedule_date BETWEEN '$start_date_delete' AND '$end_date_delete'";
    
    if (query($query)) {
        $message = "Jadwal dalam range tanggal berhasil dihapus!";
        $message_type = "success";
    } else {
        $message = "Gagal menghapus jadwal!";
        $message_type = "danger";
    }
}

// ============================================
// AMBIL DATA JADWAL
// ============================================
$class_filter = isset($_GET['class_filter']) ? escape($_GET['class_filter']) : '';
$date_filter = isset($_GET['date_filter']) ? escape($_GET['date_filter']) : '';

$where = "WHERE 1=1";

if (!empty($class_filter)) {
    $where .= " AND s.class_id = '$class_filter'";
}

if (!empty($date_filter)) {
    $where .= " AND s.schedule_date = '$date_filter'";
}

$jadwal_list = fetch_all("SELECT s.*, 
                          u.full_name as nama_siswa, 
                          u.nis,
                          c.class_name,
                          a.status as status_absen
                          FROM schedules s
                          JOIN users u ON s.student_id = u.id
                          JOIN classes c ON s.class_id = c.id
                          LEFT JOIN attendances a ON s.id = a.schedule_id AND s.schedule_date = a.attendance_date
                          $where 
                          ORDER BY s.schedule_date DESC, c.class_name ASC");

// Ambil data kelas untuk dropdown
$kelas_list = fetch_all("SELECT * FROM classes WHERE is_active = 1 ORDER BY class_name");

// Statistik
$total_jadwal = count_rows("SELECT * FROM schedules");
$jadwal_hari_ini = count_rows("SELECT * FROM schedules WHERE schedule_date = CURDATE()");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal Piket - E-piket SMEKDA</title>
    
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
        
        .main-content {
            margin-left: 0;
            padding: 20px;
            padding-top: 90px;
            transition: margin-left 0.3s ease;
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
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-mini-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-mini-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-mini-icon.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-mini-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        
        .stat-mini-info h3 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #333;
        }
        
        .stat-mini-info p {
            margin: 0;
            font-size: 13px;
            color: #999;
        }
        
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
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn-primary-custom {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-danger-custom {
            padding: 10px 25px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-danger-custom:hover {
            background: #c82333;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
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
        .badge-primary { background: #cfe2ff; color: #084298; }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            margin-right: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-header h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .col-md-3, .col-md-4, .col-md-6 {
            flex: 1;
            min-width: 200px;
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
                gap: 15px;
                text-align: center;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .action-buttons button {
                width: 100%;
            }
            
            .stats-mini {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                font-size: 12px;
            }
            
            .table-custom th,
            .table-custom td {
                padding: 10px 8px;
                font-size: 12px;
            }
            
            .col-md-3, .col-md-4, .col-md-6 {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h4><i class="fas fa-calendar-alt"></i> Kelola Jadwal Piket</h4>
                <small style="color: #999;">Manajemen Jadwal Piket Siswa</small>
            </div>
            <a href="dashboard.php" class="btn-primary-custom">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        
        <!-- Mini Stats -->
        <div class="stats-mini">
            <div class="stat-mini-card">
                <div class="stat-mini-icon purple">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-mini-info">
                    <h3><?php echo $total_jadwal; ?></h3>
                    <p>Total Jadwal</p>
                </div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-icon green">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-mini-info">
                    <h3><?php echo $jadwal_hari_ini; ?></h3>
                    <p>Jadwal Hari Ini</p>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-tools"></i> Menu Aksi</h5>
            </div>
            <div class="action-buttons">
                <button class="btn-primary-custom" onclick="openModal('modalGenerate')">
                    <i class="fas fa-magic"></i> Generate Jadwal Otomatis
                </button>
                <button class="btn-primary-custom" onclick="openModal('modalTambah')">
                    <i class="fas fa-plus"></i> Tambah Jadwal Manual
                </button>
                <button class="btn-danger-custom" onclick="openModal('modalHapusRange')">
                    <i class="fas fa-trash"></i> Hapus Jadwal (Range Tanggal)
                </button>
            </div>
        </div>
        
        <!-- Daftar Jadwal -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-list"></i> Daftar Jadwal (<?php echo count($jadwal_list); ?>)</h5>
            </div>
            
            <!-- Filter -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <select name="class_filter" class="form-control">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?php echo $kelas['id']; ?>" <?php echo $class_filter == $kelas['id'] ? 'selected' : ''; ?>>
                                        <?php echo $kelas['class_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="date" name="date_filter" class="form-control" value="<?php echo $date_filter; ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn-primary-custom" style="width: 100%;">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Table -->
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Kelas</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Shift</th>
                            <th>Status Absen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($jadwal_list) > 0): ?>
                            <?php 
                            $no = 1;
                            foreach ($jadwal_list as $jadwal): 
                                $status_badge = 'badge-warning';
                                $status_text = 'Belum Absen';
                                
                                if ($jadwal['status_absen'] == 'hadir') {
                                    $status_badge = 'badge-success';
                                    $status_text = 'Hadir';
                                } elseif ($jadwal['status_absen'] == 'izin') {
                                    $status_badge = 'badge-info';
                                    $status_text = 'Izin';
                                } elseif ($jadwal['status_absen'] == 'sakit') {
                                    $status_badge = 'badge-info';
                                    $status_text = 'Sakit';
                                } elseif ($jadwal['status_absen'] == 'alpha') {
                                    $status_badge = 'badge-danger';
                                    $status_text = 'Alpha';
                                }
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo format_tanggal_indonesia($jadwal['schedule_date']); ?></td>
                                <td><span class="badge badge-primary"><?php echo $jadwal['day_name']; ?></span></td>
                                <td><?php echo $jadwal['class_name']; ?></td>
                                <td><?php echo $jadwal['nis']; ?></td>
                                <td><?php echo $jadwal['nama_siswa']; ?></td>
                                <td><?php echo ucfirst($jadwal['shift']); ?></td>
                                <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <a href="?delete=<?php echo $jadwal['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus jadwal ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                    <p>Tidak ada jadwal piket</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Generate Jadwal -->
    <div id="modalGenerate" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-magic"></i> Generate Jadwal Otomatis</h5>
                <button class="close-modal" onclick="closeModal('modalGenerate')">&times;</button>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Pilih Kelas <span style="color: red;">*</span></label>
                    <select name="class_id" class="form-control" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?php echo $kelas['id']; ?>"><?php echo $kelas['class_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Mulai <span style="color: red;">*</span></label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Selesai <span style="color: red;">*</span></label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Jumlah Siswa per Hari <span style="color: red;">*</span></label>
                    <input type="number" name="jumlah_siswa_per_hari" class="form-control" value="2" min="1" max="10" required>
                    <small style="color: #999;">Berapa siswa yang piket setiap harinya</small>
                </div>
                
                <div class="alert alert-info" style="margin-top: 15px;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Catatan:</strong> Sistem akan otomatis skip hari Sabtu dan Minggu. Jadwal akan dibagi rata ke semua siswa dalam kelas secara acak.
                </div>
                
                <button type="submit" name="generate_jadwal" class="btn-primary-custom" style="width: 100%;">
                    <i class="fas fa-magic"></i> Generate Jadwal
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Tambah Jadwal Manual -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-plus"></i> Tambah Jadwal Manual</h5>
                <button class="close-modal" onclick="closeModal('modalTambah')">&times;</button>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Pilih Kelas <span style="color: red;">*</span></label>
                    <select name="class_id" id="class_id_manual" class="form-control" required onchange="loadSiswaManual(this.value)">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?php echo $kelas['id']; ?>"><?php echo $kelas['class_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Pilih Siswa <span style="color: red;">*</span></label>
                    <select name="student_id" id="student_list_manual" class="form-control" required>
                        <option value="">-- Pilih Kelas Dulu --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Piket <span style="color: red;">*</span></label>
                    <input type="date" name="schedule_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Shift <span style="color: red;">*</span></label>
                    <select name="shift" class="form-control" required>
                        <option value="pagi">Pagi</option>
                        <option value="siang">Siang</option>
                    </select>
                </div>
                
                <button type="submit" name="tambah_jadwal" class="btn-primary-custom" style="width: 100%;">
                    <i class="fas fa-save"></i> Simpan Jadwal
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Hapus Range -->
    <div id="modalHapusRange" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-trash"></i> Hapus Jadwal (Range Tanggal)</h5>
                <button class="close-modal" onclick="closeModal('modalHapusRange')">&times;</button>
            </div>
            
            <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus semua jadwal dalam range ini?')">
                <div class="form-group">
                    <label>Pilih Kelas <span style="color: red;">*</span></label>
                    <select name="class_id_delete" class="form-control" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?php echo $kelas['id']; ?>"><?php echo $kelas['class_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Mulai <span style="color: red;">*</span></label>
                    <input type="date" name="start_date_delete" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Selesai <span style="color: red;">*</span></label>
                    <input type="date" name="end_date_delete" class="form-control" required>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Perhatian:</strong> Semua jadwal dalam range tanggal ini akan dihapus permanen!
                </div>
                
                <button type="submit" name="hapus_range" class="btn-danger-custom" style="width: 100%;">
                    <i class="fas fa-trash"></i> Hapus Jadwal
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
        
        // Auto hide alert after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
        
        // Load siswa berdasarkan kelas (untuk tambah manual)
        function loadSiswaManual(classId) {
            const studentSelect = document.getElementById('student_list_manual');
            
            if (!classId) {
                studentSelect.innerHTML = '<option value="">-- Pilih Kelas Dulu --</option>';
                return;
            }
            
            // Fetch siswa dari server menggunakan AJAX
            studentSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch('get-siswa.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- Pilih Siswa --</option>';
                    data.forEach(siswa => {
                        options += `<option value="${siswa.id}">${siswa.nis} - ${siswa.full_name}</option>`;
                    });
                    studentSelect.innerHTML = options;
                })
                .catch(error => {
                    console.error('Error:', error);
                    studentSelect.innerHTML = '<option value="">Error loading data</option>';
                });
        }
    </script>
</body>
</html>