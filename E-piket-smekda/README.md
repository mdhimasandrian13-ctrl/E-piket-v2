# E-piket SMEKDA

Sistem Manajemen Absensi Piket Siswa berbasis Web untuk SMKN 2 Surabaya

## Daftar Isi

- [Deskripsi Project](#deskripsi-project)
- [Fitur Utama](#fitur-utama)
- [Teknologi](#teknologi)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Cara Menggunakan](#cara-menggunakan)
- [Akun Default](#akun-default)
- [Struktur Project](#struktur-project)
- [API Endpoints](#api-endpoints)
- [Troubleshooting](#troubleshooting)
- [Kontribusi](#kontribusi)
- [Lisensi](#lisensi)

## Deskripsi Project

E-piket SMEKDA adalah aplikasi web untuk mengelola sistem absensi piket siswa SMKN 2 Surabaya secara digital. Sistem ini menggantikan pencatatan manual dengan solusi yang lebih efisien, transparan, dan akurat.

Aplikasi ini memungkinkan:
- Admin untuk mengelola data siswa, guru, kelas, dan jadwal piket
- Guru untuk memonitor kehadiran piket siswa secara real-time
- Siswa untuk melakukan absensi piket dengan sekali klik

## Fitur Utama

### Admin Features
- Dashboard dengan statistik lengkap
- CRUD Siswa (Tambah, Edit, Hapus, Lihat)
- CRUD Guru (Tambah, Edit, Hapus, Lihat)
- CRUD Kelas (Tambah, Edit, Hapus, Lihat)
- Generate Jadwal Piket Otomatis
- CRUD Jadwal Piket Manual
- Export/Import Data

### Guru Features
- Dashboard Kelas yang Diampu
- Monitoring Jadwal Piket Harian
- Input Absensi Manual
- Generate Laporan Kehadiran Bulanan/Tahunan
- Export Laporan ke PDF

### Siswa Features
- Dashboard dengan Jadwal Hari Ini
- Absensi Piket 1 Klik
- Riwayat Kehadiran
- Statistik Kehadiran Bulanan

## Teknologi

### Frontend
- HTML5
- CSS3 dengan Gradient & Modern Design
- JavaScript Vanilla (ES6+)
- Bootstrap 5
- Font Awesome 6
- Google Fonts (Poppins)

### Backend
- PHP 7.4+
- Apache Server

### Database
- MySQL 5.7+
- MariaDB 10.3+

### Tools
- XAMPP (Local Development)
- Git & GitHub
- VS Code

## Persyaratan Sistem

### Minimum Requirements
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau MariaDB 10.3
- Apache Web Server
- 500MB Storage
- Browser modern (Chrome, Firefox, Safari, Edge)

### Recommended
- PHP 8.0+
- MySQL 8.0 atau MariaDB 10.5+
- SSD Storage
- 1GB RAM

## Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/e-piket-smekda.git
cd e-piket-smekda
```

### 2. Setup dengan XAMPP

#### Windows:
1. Ekstrak folder ke `C:\xampp\htdocs\e-piket-smekda`
2. Buka XAMPP Control Panel
3. Start Apache dan MySQL

#### MacOS/Linux:
```bash
# Copy ke direktori Apache
sudo cp -r e-piket-smekda /Library/WebServer/Documents/
# atau
sudo cp -r e-piket-smekda /var/www/html/
```

### 3. Setup Database

1. Buka browser, akses phpMyAdmin: `http://localhost/phpmyadmin`
2. Import database:
   - Klik tab "Import"
   - Pilih file database (jika ada di project)
   - Atau manual: jalankan SQL queries di tab SQL

**Manual Setup Query:**
```sql
-- Copy semua query dari database setup instructions
```

### 4. Verifikasi Instalasi

Akses aplikasi: `http://localhost/e-piket-smekda`

Jika muncul halaman login, instalasi berhasil!

## Konfigurasi

### Edit Database Connection

File: `config/database.php`

```php
define('DB_HOST', 'localhost');      // Host database
define('DB_USER', 'root');           // Username
define('DB_PASS', '');               // Password
define('DB_NAME', 'epiket_smekda');  // Nama database
```

### Timezone

File: `config/database.php`

```php
date_default_timezone_set('Asia/Jakarta');  // Sesuaikan dengan timezone Anda
```

## Cara Menggunakan

### Login

1. Akses `http://localhost/e-piket-smekda/auth/login.php`
2. Pilih role (Admin/Guru/Siswa)
3. Masukkan username dan password
4. Klik LOGIN

### Admin Workflow

**Langkah 1: Persiapan Data**
1. Buka Kelola Siswa → Tambah data siswa
2. Buka Kelola Guru → Tambah data guru
3. Buka Kelola Kelas → Tambah kelas dan assign wali kelas
4. Assign siswa ke kelas

**Langkah 2: Generate Jadwal**
1. Buka Jadwal Piket
2. Klik "Generate Jadwal Otomatis"
3. Pilih kelas, tanggal mulai, tanggal selesai
4. Tentukan jumlah siswa per hari
5. Klik Generate

**Langkah 3: Monitoring**
- Dashboard menampilkan statistik real-time
- Buka Kelola Siswa/Guru/Kelas untuk mengedit data

### Guru Workflow

**Dashboard**
- Lihat statistik kelas yang diampu
- Monitoring jadwal piket hari ini
- Lihat siswa yang belum absen

**Monitoring**
- Pilih tanggal dan kelas
- Input status absensi siswa
- Tambah catatan jika perlu

**Laporan**
- Pilih periode (Bulanan/Tahunan)
- Lihat rekap kehadiran per siswa
- Print/Export laporan

### Siswa Workflow

**Dashboard Siswa**
1. Login dengan NIS dan password
2. Lihat jadwal piket hari ini
3. Klik "Absensi Sekarang" untuk check-in
4. Lihat riwayat kehadiran
5. Monitor statistik kehadiran bulanan

## Akun Default

| Role | Username | Password | NIS/NIP |
|------|----------|----------|---------|
| Admin | admin | admin123 | - |
| Guru 1 | guru001 | guru123 | 198501012010011001 |
| Guru 2 | guru002 | guru123 | 199002022012012002 |
| Guru 3 | guru003 | guru123 | 198803032015011003 |
| Siswa 1 | 2024001 | siswa123 | 2024001 |
| Siswa 2 | 2024002 | siswa123 | 2024002 |
| Siswa 3 | 2024003 | siswa123 | 2024003 |
| Siswa 4 | 2024004 | siswa123 | 2024004 |
| Siswa 5 | 2024005 | siswa123 | 2024005 |

**Catatan:** Ubah password default segera setelah instalasi untuk keamanan!

## Struktur Project

```
e-piket-smekda/
├── config/                          # Database Configuration
│   └── database.php                 # DB Connection & Helper Functions
├── auth/                            # Authentication
│   ├── login.php                    # Login Page
│   └── logout.php                   # Logout Process
├── admin/                           # Admin Features
│   ├── dashboard.php                # Admin Dashboard
│   ├── kelola-siswa.php             # Manage Students
│   ├── kelola-guru.php              # Manage Teachers
│   ├── kelola-kelas.php             # Manage Classes
│   ├── kelola-jadwal.php            # Manage Schedules
│   └── get-siswa.php                # AJAX Helper
├── guru/                            # Teacher Features
│   ├── dashboard.php                # Teacher Dashboard
│   ├── monitoring.php               # Attendance Monitoring
│   └── laporan.php                  # Reports
├── siswa/                           # Student Features
│   └── dashboard.php                # Student Dashboard
├── includes/                        # Include Files
│   ├── header.php                   # Header with CSS
│   └── footer.php                   # Footer with JS
├── assets/                          # Static Assets
│   ├── css/
│   │   ├── style.css                # Global CSS
│   │   ├── admin.css                # Admin CSS
│   │   ├── guru.css                 # Teacher CSS
│   │   └── siswa.css                # Student CSS
│   ├── js/
│   │   ├── script.js                # Global JS
│   │   ├── admin.js                 # Admin JS
│   │   ├── guru.js                  # Teacher JS
│   │   └── siswa.js                 # Student JS
│   └── img/                         # Images & Icons
├── index.php                        # Landing Page
├── .gitignore                       # Git Ignore File
├── README.md                        # This File
└── LICENSE                          # License (Optional)
```

## API Endpoints

### Authentication
- `POST /auth/login.php` - User Login
- `GET /auth/logout.php` - User Logout

### Admin Routes
- `GET /admin/dashboard.php` - Dashboard
- `GET/POST /admin/kelola-siswa.php` - Student Management
- `GET/POST /admin/kelola-guru.php` - Teacher Management
- `GET/POST /admin/kelola-kelas.php` - Class Management
- `GET/POST /admin/kelola-jadwal.php` - Schedule Management
- `GET /admin/get-siswa.php` - Get Students (AJAX)

### Teacher Routes
- `GET /guru/dashboard.php` - Dashboard
- `GET /guru/monitoring.php` - Monitoring
- `GET /guru/laporan.php` - Reports

### Student Routes
- `GET/POST /siswa/dashboard.php` - Dashboard & Attendance

## Troubleshooting

### Error: "Connection Refused"
**Solusi:**
- Pastikan XAMPP/Apache running
- Cek MySQL service di XAMPP Control Panel
- Restart Apache dan MySQL

### Error: "Table doesn't exist"
**Solusi:**
- Import database SQL file ke phpMyAdmin
- Atau jalankan manual CREATE TABLE queries

### Error: "Undefined array key"
**Solusi:**
- Clear browser cache (Ctrl+Shift+Delete)
- Pastikan session sudah di-set saat login
- Cek PHP error logs di XAMPP

### Error: 404 Not Found
**Solusi:**
- Pastikan folder path benar
- Cek file ada di lokasi yang tepat
- Refresh halaman browser

### Session tidak tersimpan
**Solusi:**
- Pastikan `session_start()` di awal setiap file PHP
- Cek PHP.ini session settings
- Clear browser cookies

## Kontribusi

### Cara Berkontribusi

1. Fork repository
2. Buat branch baru (`git checkout -b feature/fitur-baru`)
3. Commit changes (`git commit -m 'Add new feature'`)
4. Push ke branch (`git push origin feature/fitur-baru`)
5. Buat Pull Request

### Pedoman Kontribusi

- Ikuti coding style yang sudah ada
- Tambahkan komentar untuk kode yang kompleks
- Update dokumentasi sesuai perubahan
- Test semua fitur sebelum submit PR

## Lisensi

Project ini dilisensikan di bawah MIT License - lihat file [LICENSE](LICENSE) untuk detail.

## Kontak & Support

**Email:** support@smkn2sby.sch.id
**Website:** https://smkn2sby.sch.id
**GitHub Issues:** [Report Bug](https://github.com/yourusername/e-piket-smekda/issues)

## Changelog

### v1.0.0 (2024)
- Initial Release
- Admin Dashboard
- Teacher Features
- Student Features
- Database Setup

---

**Last Updated:** October 2024
**Version:** 1.0.0
**Status:** Production Ready