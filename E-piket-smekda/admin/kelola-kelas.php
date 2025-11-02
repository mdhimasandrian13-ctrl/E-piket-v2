<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Kelola Kelas
 * ============================================
 * File: admin/kelola-kelas.php
 * Deskripsi: CRUD untuk mengelola data kelas
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
// PROSES TAMBAH KELAS
// ============================================
if (isset($_POST['tambah_kelas'])) {
    $class_name = escape($_POST['class_name']);
    $grade = escape($_POST['grade']);
    $major = escape($_POST['major']);
    $class_number = escape($_POST['class_number']);
    $homeroom_teacher_id = !empty($_POST['homeroom_teacher_id']) ? escape($_POST['homeroom_teacher_id']) : 'NULL';
    $academic_year = escape($_POST['academic_year']);
    
    // Cek kelas sudah ada atau belum
    $cek_kelas = count_rows("SELECT * FROM classes WHERE class_name = '$class_name' AND academic_year = '$academic_year'");
    
    if ($cek_kelas > 0) {
        $message = "Kelas dengan nama yang sama sudah ada di tahun ajaran ini!";
        $message_type = "danger";
    } else {
        if ($homeroom_teacher_id == 'NULL') {
            $query = "INSERT INTO classes (class_name, grade, major, class_number, academic_year) 
                      VALUES ('$class_name', '$grade', '$major', '$class_number', '$academic_year')";
        } else {
            $query = "INSERT INTO classes (class_name, grade, major, class_number, homeroom_teacher_id, academic_year) 
                      VALUES ('$class_name', '$grade', '$major', '$class_number', '$homeroom_teacher_id', '$academic_year')";
        }
        
        if (query($query)) {
            $message = "Kelas berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Gagal menambahkan kelas!";
            $message_type = "danger";
        }
    }
}

// ============================================
// PROSES EDIT KELAS
// ============================================
if (isset($_POST['edit_kelas'])) {
    $id = escape($_POST['id']);
    $class_name = escape($_POST['class_name']);
    $grade = escape($_POST['grade']);
    $major = escape($_POST['major']);
    $class_number = escape($_POST['class_number']);
    $homeroom_teacher_id = !empty($_POST['homeroom_teacher_id']) ? escape($_POST['homeroom_teacher_id']) : 'NULL';
    $academic_year = escape($_POST['academic_year']);
    $is_active = escape($_POST['is_active']);
    
    if ($homeroom_teacher_id == 'NULL') {
        $query = "UPDATE classes SET 
                  class_name = '$class_name',
                  grade = '$grade',
                  major = '$major',
                  class_number = '$class_number',
                  homeroom_teacher_id = NULL,
                  academic_year = '$academic_year',
                  is_active = '$is_active'
                  WHERE id = '$id'";
    } else {
        $query = "UPDATE classes SET 
                  class_name = '$class_name',
                  grade = '$grade',
                  major = '$major',
                  class_number = '$class_number',
                  homeroom_teacher_id = '$homeroom_teacher_id',
                  academic_year = '$academic_year',
                  is_active = '$is_active'
                  WHERE id = '$id'";
    }
    
    if (query($query)) {
        $message = "Data kelas berhasil diupdate!";
        $message_type = "success";
    } else {
        $message = "Gagal mengupdate data kelas!";
        $message_type = "danger";
    }
}

// ============================================
// PROSES HAPUS KELAS
// ============================================
if (isset($_GET['delete'])) {
    $id = escape($_GET['delete']);
    
    // Cek apakah ada siswa di kelas ini
    $jumlah_siswa = count_rows("SELECT * FROM users WHERE class_id = '$id' AND role = 'siswa'");
    
    if ($jumlah_siswa > 0) {
        $message = "Kelas tidak bisa dihapus karena masih ada $jumlah_siswa siswa!";
        $message_type = "danger";
    } else {
        if (query("DELETE FROM classes WHERE id = '$id'")) {
            $message = "Kelas berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus kelas!";
            $message_type = "danger";
        }
    }
}

// ============================================
// AMBIL DATA KELAS
// ============================================
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$grade_filter = isset($_GET['grade_filter']) ? escape($_GET['grade_filter']) : '';

$where = "WHERE 1=1";

if (!empty($search)) {
    $where .= " AND (c.class_name LIKE '%$search%' OR c.major LIKE '%$search%')";
}

if (!empty($grade_filter)) {
    $where .= " AND c.grade = '$grade_filter'";
}

$kelas_list = fetch_all("SELECT c.*, 
                         u.full_name as wali_kelas_nama,
                         (SELECT COUNT(*) FROM users WHERE class_id = c.id AND role = 'siswa') as jumlah_siswa
                         FROM classes c 
                         LEFT JOIN users u ON c.homeroom_teacher_id = u.id 
                         $where 
                         ORDER BY c.grade ASC, c.class_name ASC");

// Ambil data guru untuk dropdown
$guru_list = fetch_all("SELECT * FROM users WHERE role = 'guru' AND is_active = 1 ORDER BY full_name");

// Data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = escape($_GET['edit']);
    $edit_data = fetch_single("SELECT * FROM classes WHERE id = '$edit_id'");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas - E-piket SMEKDA</title>
    
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
        
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }
        
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
        }
        
        .btn-edit {
            background: #ffc107;
            color: white;
        }
        
        .btn-edit:hover {
            background: #e0a800;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
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
        
        .row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .col-md-3, .col-md-6 {
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
            
            .table-responsive {
                font-size: 12px;
            }
            
            .table-custom th,
            .table-custom td {
                padding: 10px 8px;
                font-size: 12px;
            }
            
            .col-md-3, .col-md-6 {
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
                <h4><i class="fas fa-school"></i> Kelola Kelas</h4>
                <small style="color: #999;">Manajemen Data Kelas</small>
            </div>
            <a href="dashboard.php" class="btn-primary-custom">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        
        <div class="content-section">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <div class="section-header">
                <h5><i class="fas fa-door-open"></i> Daftar Kelas (<?php echo count($kelas_list); ?>)</h5>
                <button class="btn-primary-custom" onclick="openModal('modalTambah')">
                    <i class="fas fa-plus"></i> Tambah Kelas
                </button>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama kelas atau jurusan..." value="<?php echo $search; ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="grade_filter" class="form-control">
                                <option value="">-- Semua Tingkat --</option>
                                <option value="10" <?php echo $grade_filter == '10' ? 'selected' : ''; ?>>Kelas 10</option>
                                <option value="11" <?php echo $grade_filter == '11' ? 'selected' : ''; ?>>Kelas 11</option>
                                <option value="12" <?php echo $grade_filter == '12' ? 'selected' : ''; ?>>Kelas 12</option>
                            </select>
                        </div>
                        <div class="col-md-3">
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
                            <th>Nama Kelas</th>
                            <th>Tingkat</th>
                            <th>Jurusan</th>
                            <th>Wali Kelas</th>
                            <th>Jumlah Siswa</th>
                            <th>Tahun Ajaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($kelas_list) > 0): ?>
                            <?php 
                            $no = 1;
                            foreach ($kelas_list as $kelas): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo $kelas['class_name']; ?></strong></td>
                                <td><span class="badge badge-primary">Kelas <?php echo $kelas['grade']; ?></span></td>
                                <td><?php echo $kelas['major']; ?></td>
                                <td><?php echo $kelas['wali_kelas_nama'] ?? '<span style="color: #999;">Belum ditentukan</span>'; ?></td>
                                <td><span class="badge badge-info"><?php echo $kelas['jumlah_siswa']; ?> siswa</span></td>
                                <td><?php echo $kelas['academic_year']; ?></td>
                                <td>
                                    <?php if ($kelas['is_active'] == 1): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $kelas['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $kelas['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus kelas ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                    <p>Tidak ada data kelas</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah Kelas -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-plus-circle"></i> Tambah Kelas Baru</h5>
                <button class="close-modal" onclick="closeModal('modalTambah')">&times;</button>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Kelas <span style="color: red;">*</span></label>
                    <input type="text" name="class_name" class="form-control" placeholder="Contoh: X RPL 1" required>
                    <small style="color: #999;">Contoh: X RPL 1, XI TKJ 2, XII MM 1</small>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tingkat <span style="color: red;">*</span></label>
                            <select name="grade" class="form-control" required>
                                <option value="">-- Pilih Tingkat --</option>
                                <option value="10">Kelas 10</option>
                                <option value="11">Kelas 11</option>
                                <option value="12">Kelas 12</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nomor Kelas <span style="color: red;">*</span></label>
                            <input type="number" name="class_number" class="form-control" placeholder="1, 2, 3..." required min="1">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Jurusan <span style="color: red;">*</span></label>
                    <select name="major" class="form-control" required>
                        <option value="">-- Pilih Jurusan --</option>
                        <option value="Rekayasa Perangkat Lunak">RPL - Rekayasa Perangkat Lunak</option>
                        <option value="Teknik Komputer dan Jaringan">TKJ - Teknik Komputer dan Jaringan</option>
                        <option value="Multimedia">MM - Multimedia</option>
                        <option value="Teknik Elektronika Industri">TEI - Teknik Elektronika Industri</option>
                        <option value="Teknik Mekatronika">TM - Teknik Mekatronika</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="form-control">
                        <option value="">-- Pilih Wali Kelas --</option>
                        <?php foreach ($guru_list as $guru): ?>
                            <option value="<?php echo $guru['id']; ?>"><?php echo $guru['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #999;">Opsional, bisa diisi nanti</small>
                </div>
                
                <div class="form-group">
                    <label>Tahun Ajaran <span style="color: red;">*</span></label>
                    <input type="text" name="academic_year" class="form-control" placeholder="2024/2025" value="2024/2025" required>
                </div>
                
                <button type="submit" name="tambah_kelas" class="btn-primary-custom" style="width: 100%;">
                    <i class="fas fa-save"></i> Simpan Data
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Kelas -->
    <?php if ($edit_data): ?>
    <div id="modalEdit" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-edit"></i> Edit Data Kelas</h5>
                <button class="close-modal" onclick="window.location.href='kelola-kelas.php'">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                
                <div class="form-group">
                    <label>Nama Kelas <span style="color: red;">*</span></label>
                    <input type="text" name="class_name" class="form-control" value="<?php echo $edit_data['class_name']; ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tingkat <span style="color: red;">*</span></label>
                            <select name="grade" class="form-control" required>
                                <option value="10" <?php echo $edit_data['grade'] == 10 ? 'selected' : ''; ?>>Kelas 10</option>
                                <option value="11" <?php echo $edit_data['grade'] == 11 ? 'selected' : ''; ?>>Kelas 11</option>
                                <option value="12" <?php echo $edit_data['grade'] == 12 ? 'selected' : ''; ?>>Kelas 12</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nomor Kelas <span style="color: red;">*</span></label>
                            <input type="number" name="class_number" class="form-control" value="<?php echo $edit_data['class_number']; ?>" required min="1">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Jurusan <span style="color: red;">*</span></label>
                    <input type="text" name="major" class="form-control" value="<?php echo $edit_data['major']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="form-control">
                        <option value="">-- Pilih Wali Kelas --</option>
                        <?php foreach ($guru_list as $guru): ?>
                            <option value="<?php echo $guru['id']; ?>" <?php echo $edit_data['homeroom_teacher_id'] == $guru['id'] ? 'selected' : ''; ?>>
                                <?php echo $guru['full_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tahun Ajaran <span style="color: red;">*</span></label>
                    <input type="text" name="academic_year" class="form-control" value="<?php echo $edit_data['academic_year']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?php echo $edit_data['is_active'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?php echo $edit_data['is_active'] == 0 ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
                
                <button type="submit" name="edit_kelas" class="btn-primary-custom" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Data
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
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
    </script>
</body>
</html