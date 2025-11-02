<?php
/**
 * ============================================
 * E-PIKET SMEKDA - Sidebar Component
 * ============================================
 * File: admin/includes/sidebar.php
 * Deskripsi: Komponen sidebar yang konsisten untuk semua halaman admin
 * ============================================
 */

// Tentukan halaman aktif berdasarkan nama file
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Hamburger Button -->
<button class="hamburger-btn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <button class="close-sidebar" onclick="toggleSidebar()">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="sidebar-header">
        <h3>E-PIKET</h3>
        <p>SMEKDA Admin</p>
    </div>
    
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="kelola-siswa.php" class="nav-link <?php echo $current_page == 'kelola-siswa' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Kelola Siswa</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="kelola-guru.php" class="nav-link <?php echo $current_page == 'kelola-guru' ? 'active' : ''; ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Kelola Guru</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="kelola-kelas.php" class="nav-link <?php echo $current_page == 'kelola-kelas' ? 'active' : ''; ?>">
                <i class="fas fa-school"></i>
                <span>Kelola Kelas</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="kelola-jadwal.php" class="nav-link <?php echo $current_page == 'kelola-jadwal' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Jadwal Piket</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="laporan.php" class="nav-link <?php echo $current_page == 'laporan' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Laporan</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="pengaturan.php" class="nav-link <?php echo $current_page == 'pengaturan' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Pengaturan</span>
            </a>
        </li>
    </ul>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking a link on mobile
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            toggleSidebar();
        }
    });
});
</script>