<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Halaman Laporan Admin
 * ============================================
 * File: admin/laporan.php
 * Deskripsi: Laporan Kehadiran Piket Siswa
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

// Get filter parameters
$tipe_laporan = isset($_POST['tipe_laporan']) ? escape($_POST['tipe_laporan']) : 'bulanan';
$bulan = isset($_POST['bulan']) ? escape($_POST['bulan']) : date('Y-m');
$kelas_id = isset($_POST['kelas_id']) ? escape($_POST['kelas_id']) : '';

// Query untuk get semua kelas
$classes_query = "SELECT id, class_name FROM classes WHERE is_active = 1 ORDER BY class_name ASC";
$classes_result = query($classes_query);

// Initialize variables
$statistics = [
    'total_siswa' => 0,
    'total_hadir' => 0,
    'total_alpha' => 0,
    'total_izin_sakit' => 0,
    'persentase' => 0
];

$attendance_data = [];

// Process Generate Laporan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
    
    $month_year = explode('-', $bulan);
    $tahun = $month_year[0];
    $bulan_num = $month_year[1];
    
    // Base query
    $base_query = "SELECT 
        u.id,
        u.nis,
        u.full_name,
        c.class_name,
        a.attendance_date,
        a.status,
        a.check_in_time
    FROM users u
    LEFT JOIN classes c ON u.class_id = c.id
    LEFT JOIN attendances a ON u.id = a.student_id 
        AND YEAR(a.attendance_date) = $tahun 
        AND MONTH(a.attendance_date) = $bulan_num
    WHERE u.role = 'siswa'";
    
    // Add class filter if selected
    if (!empty($kelas_id) && $kelas_id != '0') {
        $base_query .= " AND c.id = $kelas_id";
    }
    
    $base_query .= " ORDER BY c.class_name, u.full_name, a.attendance_date";
    
    $result = query($base_query);
    
    if ($result) {
        $students_attendance = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $key = $row['nis'];
            if (!isset($students_attendance[$key])) {
                $students_attendance[$key] = [
                    'nis' => $row['nis'],
                    'full_name' => $row['full_name'],
                    'class_name' => $row['class_name'],
                    'hadir' => 0,
                    'alpha' => 0,
                    'izin_sakit' => 0,
                    'detail' => []
                ];
            }
            
            if ($row['status']) {
                if ($row['status'] == 'hadir') {
                    $students_attendance[$key]['hadir']++;
                } elseif ($row['status'] == 'alpha') {
                    $students_attendance[$key]['alpha']++;
                } elseif (in_array($row['status'], ['izin', 'sakit'])) {
                    $students_attendance[$key]['izin_sakit']++;
                }
                
                $students_attendance[$key]['detail'][] = [
                    'date' => $row['attendance_date'],
                    'status' => $row['status'],
                    'check_in_time' => $row['check_in_time']
                ];
            }
        }
        
        $attendance_data = $students_attendance;
        
        // Calculate statistics
        if (!empty($attendance_data)) {
            $statistics['total_siswa'] = count($attendance_data);
            
            foreach ($attendance_data as $student) {
                $statistics['total_hadir'] += $student['hadir'];
                $statistics['total_alpha'] += $student['alpha'];
                $statistics['total_izin_sakit'] += $student['izin_sakit'];
            }
            
            $total_records = array_sum([
                $statistics['total_hadir'],
                $statistics['total_alpha'],
                $statistics['total_izin_sakit']
            ]);
            
            if ($total_records > 0) {
                $statistics['persentase'] = round(($statistics['total_hadir'] / $total_records) * 100, 2);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - E-piket SMEKDA</title>
    
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

        /* Header Section */
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

        .header-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-left h1 i {
            color: #667eea;
        }

        .header-left p {
            color: #999;
            font-size: 14px;
        }

        .header-right {
            display: flex;
            gap: 10px;
        }

        .btn-kembali,
        .btn-print {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-kembali {
            background: #667eea;
            color: white;
        }

        .btn-kembali:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .btn-print {
            background: #27ae60;
            color: white;
        }

        .btn-print:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
            color: white;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .filter-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }

        .filter-section h3 i {
            color: #667eea;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            outline: none;
        }

        .btn-generate {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Statistics Section */
        .statistics-section {
            margin-bottom: 30px;
        }

        .statistics-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .stat-card {
            padding: 25px;
            border-radius: 15px;
            color: white;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card-1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card-2 {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }

        .stat-card-3 {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .stat-card-4 {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        .stat-card-5 {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.95;
        }

        /* Table Section */
        .table-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .table-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .table-responsive {
            overflow-x: auto;
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

        .badge {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
        }

        .bg-success {
            background: #d4edda !important;
            color: #155724 !important;
        }

        .bg-danger {
            background: #f8d7da !important;
            color: #721c24 !important;
        }

        .bg-warning {
            background: #fff3cd !important;
            color: #856404 !important;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }
            
            .hamburger-btn,
            .sidebar,
            .sidebar-overlay,
            .header-right,
            .filter-section {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .header-section {
                box-shadow: none;
                border-bottom: 2px solid #ddd;
                margin-bottom: 20px;
            }
            
            .statistics-section,
            .table-section {
                box-shadow: none;
                page-break-inside: avoid;
            }
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
            
            .header-section {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-right {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .table-responsive {
                font-size: 12px;
            }
            
            .table thead th,
            .table tbody td {
                padding: 10px;
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
            <div class="header-left">
                <h1><i class="fas fa-chart-bar"></i> Laporan Kehadiran Piket</h1>
                <p>Generate laporan kehadiran piket siswa</p>
            </div>
            <div class="header-right">
                <a href="dashboard.php" class="btn-kembali">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Print/PDF
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filter Laporan</h3>
            
            <form method="POST" action="" class="filter-form">
                <div class="form-group">
                    <label for="tipe_laporan">Tipe Laporan</label>
                    <select name="tipe_laporan" id="tipe_laporan" class="form-control">
                        <option value="bulanan" <?php echo $tipe_laporan == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                        <option value="mingguan" <?php echo $tipe_laporan == 'mingguan' ? 'selected' : ''; ?>>Mingguan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bulan">Bulan</label>
                    <input type="month" name="bulan" id="bulan" class="form-control" value="<?php echo $bulan; ?>">
                </div>

                <div class="form-group">
                    <label for="kelas_id">Kelas</label>
                    <select name="kelas_id" id="kelas_id" class="form-control">
                        <option value="">-- Semua Kelas --</option>
                        <?php 
                        if ($classes_result && mysqli_num_rows($classes_result) > 0) {
                            while ($class = mysqli_fetch_assoc($classes_result)) {
                                $selected = $kelas_id == $class['id'] ? 'selected' : '';
                                echo "<option value=\"{$class['id']}\" $selected>{$class['class_name']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" name="generate" class="btn-generate">
                    <i class="fas fa-search"></i> Generate Laporan
                </button>
            </form>
        </div>

        <!-- Statistics Section -->
        <?php if (!empty($attendance_data)): ?>
        <div class="statistics-section">
            <h3>Ringkasan Laporan - Bulan <?php echo date('F Y', strtotime($bulan . '-01')); ?></h3>
            
            <div class="stats-cards">
                <div class="stat-card stat-card-1">
                    <div class="stat-value"><?php echo $statistics['total_siswa']; ?></div>
                    <div class="stat-label">Total Siswa</div>
                </div>
                
                <div class="stat-card stat-card-2">
                    <div class="stat-value"><?php echo $statistics['total_hadir']; ?></div>
                    <div class="stat-label">Total Hadir</div>
                </div>
                
                <div class="stat-card stat-card-3">
                    <div class="stat-value"><?php echo $statistics['total_alpha']; ?></div>
                    <div class="stat-label">Total Alpha</div>
                </div>
                
                <div class="stat-card stat-card-4">
                    <div class="stat-value"><?php echo $statistics['total_izin_sakit']; ?></div>
                    <div class="stat-label">Izin/Sakit</div>
                </div>
                
                <div class="stat-card stat-card-5">
                    <div class="stat-value"><?php echo $statistics['persentase']; ?>%</div>
                    <div class="stat-label">Persentase Rata-rata</div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <h3>Detail Laporan</h3>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Hadir</th>
                            <th>Alpha</th>
                            <th>Izin/Sakit</th>
                            <th>Total</th>
                            <th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($attendance_data as $student): 
                            $total = $student['hadir'] + $student['alpha'] + $student['izin_sakit'];
                            $persentase = $total > 0 ? round(($student['hadir'] / $total) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $student['nis']; ?></td>
                            <td><?php echo $student['full_name']; ?></td>
                            <td><?php echo $student['class_name']; ?></td>
                            <td><span class="badge bg-success"><?php echo $student['hadir']; ?></span></td>
                            <td><span class="badge bg-danger"><?php echo $student['alpha']; ?></span></td>
                            <td><span class="badge bg-warning"><?php echo $student['izin_sakit']; ?></span></td>
                            <td><?php echo $total; ?></td>
                            <td><?php echo $persentase; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to current month if not set
            const bulanInput = document.getElementById('bulan');
            if (bulanInput && !bulanInput.value) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                bulanInput.value = `${year}-${month}`;
            }

            // Form validation
            const generateBtn = document.querySelector('.btn-generate');
            if (generateBtn) {
                generateBtn.addEventListener('click', function(e) {
                    const bulan = document.getElementById('bulan').value;
                    
                    if (!bulan) {
                        e.preventDefault();
                        alert('Silakan pilih bulan terlebih dahulu');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>