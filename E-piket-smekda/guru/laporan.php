<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Laporan Kehadiran Piket
 * ============================================
 * File: guru/laporan.php
 * Deskripsi: Generate laporan kehadiran piket bulanan/tahunan
 * ============================================
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/database.php';

$guru_id = $_SESSION['user_id'];

// Ambil kelas yang diampu
$kelas_diampu = fetch_all("SELECT * FROM classes WHERE homeroom_teacher_id = '$guru_id' AND is_active = 1");

if (count($kelas_diampu) == 0) {
    $kelas_ids = array();
} else {
    $kelas_ids = array_column($kelas_diampu, 'id');
}

// Filter laporan
$filter_type = isset($_GET['filter_type']) ? escape($_GET['filter_type']) : 'bulanan';
$filter_month = isset($_GET['filter_month']) ? escape($_GET['filter_month']) : date('Y-m');
$filter_year = isset($_GET['filter_year']) ? escape($_GET['filter_year']) : date('Y');
$filter_class = isset($_GET['filter_class']) ? escape($_GET['filter_class']) : '';

// Query laporan berdasarkan filter
$laporan_data = array();

if (count($kelas_ids) > 0) {
    $kelas_str = implode(',', $kelas_ids);
    
    if ($filter_type == 'bulanan') {
        // Filter bulanan
        $year_month = substr($filter_month, 0, 7);
        $where = "WHERE YEAR(a.attendance_date) = {$year_month} ";
        $where .= "AND u.class_id IN ($kelas_str) AND u.role = 'siswa' AND u.is_active = 1";
        
        if (!empty($filter_class)) {
            $where .= " AND u.class_id = '$filter_class'";
        }
        
        $laporan_data = fetch_all("SELECT 
                                  u.id,
                                  u.full_name,
                                  u.nis,
                                  c.class_name,
                                  COUNT(a.id) as total_piket,
                                  SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
                                  SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
                                  SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
                                  SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as total_alpha,
                                  ROUND((SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) / COUNT(a.id) * 100), 2) as persentase
                                  FROM users u
                                  LEFT JOIN classes c ON u.class_id = c.id
                                  LEFT JOIN attendances a ON u.id = a.student_id
                                  $where
                                  GROUP BY u.id, u.full_name, u.nis, c.class_name
                                  ORDER BY c.class_name, u.full_name");
        
        $periode = "Bulan " . date('F Y', strtotime($filter_month . '-01'));
    } else {
        // Filter tahunan
        $where = "WHERE YEAR(a.attendance_date) = '$filter_year' ";
        $where .= "AND u.class_id IN ($kelas_str) AND u.role = 'siswa' AND u.is_active = 1";
        
        if (!empty($filter_class)) {
            $where .= " AND u.class_id = '$filter_class'";
        }
        
        $laporan_data = fetch_all("SELECT 
                                  u.id,
                                  u.full_name,
                                  u.nis,
                                  c.class_name,
                                  COUNT(a.id) as total_piket,
                                  SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
                                  SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
                                  SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
                                  SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as total_alpha,
                                  ROUND((SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) / COUNT(a.id) * 100), 2) as persentase
                                  FROM users u
                                  LEFT JOIN classes c ON u.class_id = c.id
                                  LEFT JOIN attendances a ON u.id = a.student_id
                                  $where
                                  GROUP BY u.id, u.full_name, u.nis, c.class_name
                                  ORDER BY c.class_name, u.full_name");
        
        $periode = "Tahun $filter_year";
    }
}

// Hitung ringkasan total
$ringkasan = array(
    'total_siswa' => count(array_unique(array_column($laporan_data, 'id'))),
    'total_piket' => array_sum(array_column($laporan_data, 'total_piket')),
    'total_hadir' => array_sum(array_column($laporan_data, 'total_hadir')),
    'total_izin' => array_sum(array_column($laporan_data, 'total_izin')),
    'total_sakit' => array_sum(array_column($laporan_data, 'total_sakit')),
    'total_alpha' => array_sum(array_column($laporan_data, 'total_alpha')),
);

$ringkasan['persentase_rata_rata'] = $ringkasan['total_piket'] > 0 ? round(($ringkasan['total_hadir'] / $ringkasan['total_piket']) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Piket - E-piket SMEKDA</title>
    
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
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        
        .btn-print {
            padding: 10px 25px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            background: #218838;
        }
        
        .ringkasan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .ringkasan-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .ringkasan-card h3 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .ringkasan-card p {
            margin: 5px 0 0 0;
            font-size: 12px;
            opacity: 0.9;
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
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        
        .form-control {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .col {
            flex: 1;
            min-width: 150px;
        }
        
        @media print {
            .sidebar, .top-bar, .filter-section, .btn-primary-custom, .btn-print {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .content-section {
                box-shadow: none;
                page-break-inside: avoid;
            }
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
                <a href="dashboard.php" class="nav-link">
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
                <a href="laporan.php" class="nav-link active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h4><i class="fas fa-chart-bar"></i> Laporan Kehadiran Piket</h4>
                <small style="color: #999;">Generate laporan kehadiran piket siswa</small>
            </div>
            <div>
                <a href="dashboard.php" class="btn-primary-custom" style="margin-right: 10px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <button class="btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Print/PDF
                </button>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="content-section">
            <div class="section-header">
                <h5>Filter Laporan</h5>
            </div>
            
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #666; font-size: 14px;">Tipe Laporan</label>
                            <select name="filter_type" class="form-control" onchange="document.forms[0].submit()">
                                <option value="bulanan" <?php echo $filter_type == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                                <option value="tahunan" <?php echo $filter_type == 'tahunan' ? 'selected' : ''; ?>>Tahunan</option>
                            </select>
                        </div>
                        
                        <?php if ($filter_type == 'bulanan'): ?>
                        <div class="col">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #666; font-size: 14px;">Bulan</label>
                            <input type="month" name="filter_month" class="form-control" value="<?php echo $filter_month; ?>">
                        </div>
                        <?php else: ?>
                        <div class="col">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #666; font-size: 14px;">Tahun</label>
                            <input type="number" name="filter_year" class="form-control" value="<?php echo $filter_year; ?>" min="2020" max="<?php echo date('Y'); ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="col">
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #666; font-size: 14px;">Kelas</label>
                            <select name="filter_class" class="form-control">
                                <option value="">-- Semua Kelas --</option>
                                <?php foreach ($kelas_diampu as $kelas): ?>
                                    <option value="<?php echo $kelas['id']; ?>" <?php echo $filter_class == $kelas['id'] ? 'selected' : ''; ?>>
                                        <?php echo $kelas['class_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn-primary-custom" style="width: 100%;">
                                <i class="fas fa-search"></i> Generate Laporan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Ringkasan -->
        <div class="content-section">
            <div class="section-header">
                <h5>Ringkasan Laporan - <?php echo isset($periode) ? $periode : 'Pilih Filter'; ?></h5>
            </div>
            
            <div class="ringkasan-grid">
                <div class="ringkasan-card">
                    <h3><?php echo $ringkasan['total_siswa']; ?></h3>
                    <p>Total Siswa</p>
                </div>
                <div class="ringkasan-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <h3><?php echo $ringkasan['total_hadir']; ?></h3>
                    <p>Total Hadir</p>
                </div>
                <div class="ringkasan-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3><?php echo $ringkasan['total_alpha']; ?></h3>
                    <p>Total Alpha</p>
                </div>
                <div class="ringkasan-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3><?php echo $ringkasan['total_izin'] + $ringkasan['total_sakit']; ?></h3>
                    <p>Izin/Sakit</p>
                </div>
                <div class="ringkasan-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <h3><?php echo $ringkasan['persentase_rata_rata']; ?>%</h3>
                    <p>Persentase Rata-rata</p>
                </div>
            </div>
        </div>
        
        <!-- Detail Laporan -->
        <div class="content-section">
            <div class="section-header">
                <h5><i class="fas fa-table"></i> Detail Laporan</h5>
            </div>
            
            <?php if (count($laporan_data) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kelas</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Total Piket</th>
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
                        foreach ($laporan_data as $data): 
                            $persentase_badge = 'badge-success';
                            if ($data['persentase'] < 50) {
                                $persentase_badge = 'badge-danger';
                            } elseif ($data['persentase'] < 80) {
                                $persentase_badge = 'badge-warning';
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $data['class_name']; ?></td>
                            <td><?php echo $data['nis']; ?></td>
                            <td><?php echo $data['full_name']; ?></td>
                            <td><strong><?php echo $data['total_piket']; ?></strong></td>
                            <td><span class="badge badge-success"><?php echo $data['total_hadir']; ?></span></td>
                            <td><span class="badge badge-info"><?php echo $data['total_izin']; ?></span></td>
                            <td><span class="badge badge-info"><?php echo $data['total_sakit']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $data['total_alpha']; ?></span></td>
                            <td><span class="badge <?php echo $persentase_badge; ?>"><?php echo $data['persentase']; ?>%</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>Tidak ada data laporan untuk filter yang dipilih</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>