<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Kelola Guru
 * ============================================
 * File: admin/kelola-guru.php
 * Deskripsi: CRUD untuk mengelola data guru
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
// PROSES TAMBAH GURU
// ============================================
if (isset($_POST['tambah_guru'])) {
    $username = escape($_POST['username']);
    $password = md5($_POST['password']);
    $full_name = escape($_POST['full_name']);
    $nip = escape($_POST['nip']);
    $email = escape($_POST['email']);
    $phone = escape($_POST['phone']);
    
    // Cek username sudah ada atau belum
    $cek_username = count_rows("SELECT * FROM users WHERE username = '$username'");
    $cek_nip = count_rows("SELECT * FROM users WHERE nip = '$nip'");
    
    if ($cek_username > 0) {
        $message = "Username sudah digunakan!";
        $message_type = "danger";
    } elseif ($cek_nip > 0) {
        $message = "NIP sudah terdaftar!";
        $message_type = "danger";
    } else {
        $query = "INSERT INTO users (username, password, full_name, role, nip, email, phone) 
                  VALUES ('$username', '$password', '$full_name', 'guru', '$nip', '$email', '$phone')";
        
        if (query($query)) {
            $message = "Guru berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Gagal menambahkan guru!";
            $message_type = "danger";
        }
    }
}

// ============================================
// PROSES EDIT GURU
// ============================================
if (isset($_POST['edit_guru'])) {
    $id = escape($_POST['id']);
    $username = escape($_POST['username']);
    $full_name = escape($_POST['full_name']);
    $nip = escape($_POST['nip']);
    $email = escape($_POST['email']);
    $phone = escape($_POST['phone']);
    $is_active = escape($_POST['is_active']);
    
    // Update password jika diisi
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $query = "UPDATE users SET 
                  username = '$username',
                  password = '$password',
                  full_name = '$full_name',
                  nip = '$nip',
                  email = '$email',
                  phone = '$phone',
                  is_active = '$is_active'
                  WHERE id = '$id'";
    } else {
        $query = "UPDATE users SET 
                  username = '$username',
                  full_name = '$full_name',
                  nip = '$nip',
                  email = '$email',
                  phone = '$phone',
                  is_active = '$is_active'
                  WHERE id = '$id'";
    }
    
    if (query($query)) {
        $message = "Data guru berhasil diupdate!";
        $message_type = "success";
    } else {
        $message = "Gagal mengupdate data guru!";
        $message_type = "danger";
    }
}

// ============================================
// PROSES HAPUS GURU
// ============================================
if (isset($_GET['delete'])) {
    $id = escape($_GET['delete']);
    
    // Cek apakah guru menjadi wali kelas
    $is_wali_kelas = count_rows("SELECT * FROM classes WHERE homeroom_teacher_id = '$id'");
    
    if ($is_wali_kelas > 0) {
        $message = "Guru tidak bisa dihapus karena masih menjadi wali kelas!";
        $message_type = "danger";
    } else {
        if (query("DELETE FROM users WHERE id = '$id' AND role = 'guru'")) {
            $message = "Guru berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus guru!";
            $message_type = "danger";
        }
    }
}

// ============================================
// AMBIL DATA GURU
// ============================================
$search = isset($_GET['search']) ? escape($_GET['search']) : '';

$where = "WHERE u.role = 'guru'";

if (!empty($search)) {
    $where .= " AND (u.full_name LIKE '%$search%' OR u.nip LIKE '%$search%' OR u.username LIKE '%$search%')";
}

$guru_list = fetch_all("SELECT u.*, 
                        (SELECT GROUP_CONCAT(c.class_name SEPARATOR ', ') 
                         FROM classes c 
                         WHERE c.homeroom_teacher_id = u.id) as wali_kelas
                        FROM users u 
                        $where 
                        ORDER BY u.full_name ASC");

// Data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = escape($_GET['edit']);
    $edit_data = fetch_single("SELECT * FROM users WHERE id = '$edit_id' AND role = 'guru'");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Guru - E-piket SMEKDA</title>
    
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
        
        .col-md-3, .col-md-9 {
            flex: 1;
            min-width: 200px;
        }
        
        .col-md-9 {
            flex: 3;
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
            
            .col-md-3, .col-md-9 {
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
                <h4><i class="fas fa-chalkboard-teacher"></i> Kelola Guru</h4>
                <small style="color: #999;">Manajemen Data Guru</small>
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
                <h5><i class="fas fa-users"></i> Daftar Guru (<?php echo count($guru_list); ?>)</h5>
                <button class="btn-primary-custom" onclick="openModal('modalTambah')">
                    <i class="fas fa-plus"></i> Tambah Guru
                </button>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-9">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama, NIP, atau username..." value="<?php echo $search; ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn-primary-custom" style="width: 100%;">
                                <i class="fas fa-search"></i> Cari
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
                            <th>NIP</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. HP</th>
                            <th>Wali Kelas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($guru_list) > 0): ?>
                            <?php 
                            $no = 1;
                            foreach ($guru_list as $guru): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $guru['nip']; ?></td>
                                <td><?php echo $guru['username']; ?></td>
                                <td><?php echo $guru['full_name']; ?></td>
                                <td><?php echo $guru['email']; ?></td>
                                <td><?php echo $guru['phone']; ?></td>
                                <td>
                                    <?php if (!empty($guru['wali_kelas'])): ?>
                                        <span class="badge badge-info"><?php echo $guru['wali_kelas']; ?></span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($guru['is_active'] == 1): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $guru['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $guru['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus guru ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                                    <p>Tidak ada data guru</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah Guru -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-user-plus"></i> Tambah Guru Baru</h5>
                <button class="close-modal" onclick="closeModal('modalTambah')">&times;</button>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>NIP <span style="color: red;">*</span></label>
                    <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP" required>
                </div>
                
                <div class="form-group">
                    <label>Username <span style="color: red;">*</span></label>
                    <input type="text" name="username" class="form-control" placeholder="Username untuk login" required>
                </div>
                
                <div class="form-group">
                    <label>Password <span style="color: red;">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" class="form-control" placeholder="Nama lengkap guru" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="email@example.com">
                </div>
                
                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="phone" class="form-control" placeholder="08xx-xxxx-xxxx">
                </div>
                
                <button type="submit" name="tambah_guru" class="btn-primary-custom" style="width: 100%;">
                    <i class="fas fa-save"></i> Simpan Data
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Guru -->
    <?php if ($edit_data): ?>
    <div id="modalEdit" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-edit"></i> Edit Data Guru</h5>
                <button class="close-modal" onclick="window.location.href='kelola-guru.php'">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                
                <div class="form-group">
                    <label>NIP <span style="color: red;">*</span></label>
                    <input type="text" name="nip" class="form-control" value="<?php echo $edit_data['nip']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Username <span style="color: red;">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?php echo $edit_data['username']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Password <small>(Kosongkan jika tidak ingin mengubah)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="Password baru">
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap <span style="color: red;">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo $edit_data['full_name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $edit_data['email']; ?>">
                </div>
                
                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo $edit_data['phone']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?php echo $edit_data['is_active'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?php echo $edit_data['is_active'] == 0 ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>
                
                <button type="submit" name="edit_guru" class="btn-primary-custom" style="width: 100%;">
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
</html>