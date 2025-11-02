<?php
// Navbar Responsive dengan Hamburger Menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .navbar-wrapper {
        display: flex;
        height: 100vh;
    }

    .sidebar {
        width: 250px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 0;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        transition: transform 0.3s ease;
    }

    .sidebar.hidden {
        transform: translateX(-100%);
    }

    .main-wrapper {
        flex: 1;
        margin-left: 250px;
        width: calc(100% - 250px);
        transition: all 0.3s ease;
    }

    .sidebar-logo {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 20px;
    }

    .sidebar-logo h3 {
        font-size: 18px;
        font-weight: 700;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li {
        margin: 0;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 20px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s;
        border-left: 4px solid transparent;
    }

    .sidebar-menu a:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-left-color: white;
    }

    .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border-left-color: white;
    }

    .sidebar-menu i {
        width: 24px;
        text-align: center;
        font-size: 18px;
    }

    .hamburger-btn {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1100;
        background: #667eea;
        border: none;
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        transition: all 0.3s;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .hamburger-btn:hover {
        background: #5568d3;
        transform: scale(1.1);
    }

    .navbar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        z-index: 999;
    }

    .navbar-overlay.active {
        display: block;
    }

    .sidebar-close {
        display: none;
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        color: white;
        font-size: 28px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .hamburger-btn {
            display: flex;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            max-width: 100%;
            height: 100vh;
            transform: translateX(-100%);
            box-shadow: none;
            border-radius: 0;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-close {
            display: block;
        }

        .main-wrapper {
            margin-left: 0;
            width: 100%;
        }

        .navbar-overlay.active {
            display: block;
            background: rgba(0, 0, 0, 0.3);
        }
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }
</style>

<button class="hamburger-btn" id="hamburgerBtn">
    <i class="fas fa-bars"></i>
</button>

<div class="navbar-overlay" id="navbarOverlay"></div>

<div class="navbar-wrapper">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>

        <div class="sidebar-logo">
            <h3><i class="fas fa-graduation-cap"></i> E-piket</h3>
        </div>

        <nav>
            <ul class="sidebar-menu">
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li>
                        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="kelola_siswa.php" class="<?php echo $current_page == 'kelola_siswa.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Kelola Siswa
                        </a>
                    </li>
                    <li>
                        <a href="kelola_guru.php" class="<?php echo $current_page == 'kelola_guru.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-user"></i> Kelola Guru
                        </a>
                    </li>
                    <li>
                        <a href="kelola_kelas.php" class="<?php echo $current_page == 'kelola_kelas.php' ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i> Kelola Kelas
                        </a>
                    </li>
                    <li>
                        <a href="jadwal_piket.php" class="<?php echo $current_page == 'jadwal_piket.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar"></i> Jadwal Piket
                        </a>
                    </li>
                    <li>
                        <a href="laporan.php" class="<?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> Laporan
                        </a>
                    </li>
                    <li>
                        <a href="pengaturan.php" class="<?php echo $current_page == 'pengaturan.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i> Pengaturan
                        </a>
                    </li>

                <?php elseif ($_SESSION['role'] == 'guru'): ?>
                    <li>
                        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="monitoring.php" class="<?php echo $current_page == 'monitoring.php' ? 'active' : ''; ?>">
                            <i class="fas fa-eye"></i> Monitoring Piket
                        </a>
                    </li>
                    <li>
                        <a href="laporan.php" class="<?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> Laporan
                        </a>
                    </li>

                <?php elseif ($_SESSION['role'] == 'siswa'): ?>
                    <li>
                        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="absensi.php" class="<?php echo $current_page == 'absensi.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-check"></i> Absensi
                        </a>
                    </li>
                    <li>
                        <a href="jadwal_saya.php" class="<?php echo $current_page == 'jadwal_saya.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> Jadwal Saya
                        </a>
                    </li>
                    <li>
                        <a href="riwayat.php" class="<?php echo $current_page == 'riwayat.php' ? 'active' : ''; ?>">
                            <i class="fas fa-history"></i> Riwayat
                        </a>
                    </li>
                <?php endif; ?>

                <li style="margin-top: 30px; border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 20px;">
                    <a href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-wrapper" id="mainWrapper">
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarClose = document.getElementById('sidebarClose');
        const navbarOverlay = document.getElementById('navbarOverlay');
        const mainWrapper = document.getElementById('mainWrapper');

        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.add('active');
            navbarOverlay.classList.add('active');
            mainWrapper.style.overflow = 'hidden';
        });

        sidebarClose.addEventListener('click', function() {
            closeSidebar();
        });

        navbarOverlay.addEventListener('click', function() {
            closeSidebar();
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                navbarOverlay.classList.remove('active');
            }
        });

        function closeSidebar() {
            sidebar.classList.remove('active');
            navbarOverlay.classList.remove('active');
            mainWrapper.style.overflow = 'auto';
        }

        const menuLinks = document.querySelectorAll('.sidebar-menu a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
    });
</script>