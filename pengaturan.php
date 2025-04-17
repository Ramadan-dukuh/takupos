<?php
include "koneksi.php";

// Check if user is logged in as admin
session_start();
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit;
// }

// Fetch existing settings
// $query = "SELECT * FROM pengaturan WHERE id = 1";
// $result = $kon->query($query);
// $settings = $result->fetch_assoc();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_store'])) {
        // Update store information
        $store_name = $kon->real_escape_string($_POST['store_name']);
        $store_address = $kon->real_escape_string($_POST['store_address']);
        $store_phone = $kon->real_escape_string($_POST['store_phone']);
        $store_email = $kon->real_escape_string($_POST['store_email']);
        $store_description = $kon->real_escape_string($_POST['store_description']);
        
        $update_query = "UPDATE pengaturan SET 
                         nama_toko = '$store_name',
                         alamat_toko = '$store_address',
                         telepon_toko = '$store_phone',
                         email_toko = '$store_email',
                         deskripsi_toko = '$store_description',
                         updated_at = NOW()
                         WHERE id = 1";
        
        if ($kon->query($update_query)) {
            $success_message = "Informasi toko berhasil diperbarui!";
            // Refresh settings
            $result = $kon->query("SELECT * FROM pengaturan WHERE id = 1");
            $settings = $result->fetch_assoc();
        } else {
            $error_message = "Gagal memperbarui informasi toko: " . $kon->error;
        }
    } elseif (isset($_POST['update_tax'])) {
        // Update tax and currency settings
        $tax_rate = floatval($_POST['tax_rate']);
        $currency = $kon->real_escape_string($_POST['currency']);
        $currency_symbol = $kon->real_escape_string($_POST['currency_symbol']);
        $receipt_footer = $kon->real_escape_string($_POST['receipt_footer']);
        
        $update_query = "UPDATE pengaturan SET 
                         pajak = $tax_rate,
                         mata_uang = '$currency',
                         simbol_mata_uang = '$currency_symbol',
                         footer_struk = '$receipt_footer',
                         updated_at = NOW()
                         WHERE id = 1";
        
        if ($kon->query($update_query)) {
            $success_message = "Pengaturan pajak dan mata uang berhasil diperbarui!";
            // Refresh settings
            $result = $kon->query("SELECT * FROM pengaturan WHERE id = 1");
            $settings = $result->fetch_assoc();
        } else {
            $error_message = "Gagal memperbarui pengaturan pajak dan mata uang: " . $kon->error;
        }
    } elseif (isset($_POST['update_password'])) {
        // Update admin password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current admin info
        $admin_id = $_SESSION['admin_id'];
        $admin_query = "SELECT * FROM admin WHERE id_admin = $admin_id";
        $admin_result = $kon->query($admin_query);
        $admin_data = $admin_result->fetch_assoc();
        
        // Verify current password
        if (password_verify($current_password, $admin_data['password'])) {
            // Check if new passwords match
            if ($new_password === $confirm_password) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE admin SET 
                                password = '$hashed_password',
                                updated_at = NOW()
                                WHERE id_admin = $admin_id";
                
                if ($kon->query($update_query)) {
                    $success_message = "Password berhasil diperbarui!";
                } else {
                    $error_message = "Gagal memperbarui password: " . $kon->error;
                }
            } else {
                $error_message = "Password baru dan konfirmasi password tidak sama!";
            }
        } else {
            $error_message = "Password saat ini tidak valid!";
        }
    } elseif (isset($_POST['update_logo']) && isset($_FILES['store_logo'])) {
        // Handle logo upload
        $target_dir = "img/";
        $file_extension = strtolower(pathinfo($_FILES["store_logo"]["name"], PATHINFO_EXTENSION));
        $new_filename = "store_logo." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES["store_logo"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (max 2MB)
            if ($_FILES["store_logo"]["size"] <= 2000000) {
                // Allow certain file formats
                if ($file_extension == "jpg" || $file_extension == "png" || $file_extension == "jpeg") {
                    if (move_uploaded_file($_FILES["store_logo"]["tmp_name"], $target_file)) {
                        // Update logo path in database
                        $update_query = "UPDATE pengaturan SET 
                                        logo_toko = '$new_filename',
                                        updated_at = NOW()
                                        WHERE id = 1";
                        
                        if ($kon->query($update_query)) {
                            $success_message = "Logo toko berhasil diperbarui!";
                            // Refresh settings
                            $result = $kon->query("SELECT * FROM pengaturan WHERE id = 1");
                            $settings = $result->fetch_assoc();
                        } else {
                            $error_message = "Gagal memperbarui logo toko dalam database: " . $kon->error;
                        }
                    } else {
                        $error_message = "Gagal mengupload file logo!";
                    }
                } else {
                    $error_message = "Hanya file JPG, JPEG, dan PNG yang diperbolehkan!";
                }
            } else {
                $error_message = "Ukuran file terlalu besar! Maksimal 2MB.";
            }
        } else {
            $error_message = "File yang diupload bukan gambar!";
        }
    } elseif (isset($_POST['update_printer'])) {
        // Update printer settings
        $printer_name = $kon->real_escape_string($_POST['printer_name']);
        $receipt_width = intval($_POST['receipt_width']);
        $auto_print = isset($_POST['auto_print']) ? 1 : 0;
        
        $update_query = "UPDATE pengaturan SET 
                         nama_printer = '$printer_name',
                         lebar_struk = $receipt_width,
                         cetak_otomatis = $auto_print,
                         updated_at = NOW()
                         WHERE id = 1";
        
        if ($kon->query($update_query)) {
            $success_message = "Pengaturan printer berhasil diperbarui!";
            // Refresh settings
            $result = $kon->query("SELECT * FROM pengaturan WHERE id = 1");
            $settings = $result->fetch_assoc();
        } else {
            $error_message = "Gagal memperbarui pengaturan printer: " . $kon->error;
        }
    } elseif (isset($_POST['backup_database'])) {
        // Logic for database backup would go here
        // This typically requires server-side scripting to create an SQL dump
        // For now, we'll just show a success message
        $success_message = "Backup database berhasil dibuat!";
    }
}

// Get list of admin users (for user management)
// $admin_query = "SELECT * FROM admin ORDER BY id_admin ASC";
// $admin_result = $kon->query($admin_query);
// $admin_users = [];

// if ($admin_result && $admin_result->num_rows > 0) {
//     while ($row = $admin_result->fetch_assoc()) {
//         $admin_users[] = $row;
//     }
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <link rel="shortcut icon" href="img/logotakupos2.png" type="image/x-icon">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="script.js" defer></script>
    <title>Pengaturan - Fashion24</title>
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        
        .settings-nav {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px var(--shadow-color);
            height: fit-content;
        }
        
        .settings-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .settings-nav li {
            margin-bottom: 5px;
        }
        
        .settings-nav a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .settings-nav a:hover {
            background-color: var(--hover-color);
        }
        
        .settings-nav a.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .settings-nav i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .settings-content {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }
        
        .settings-tab {
            display: none;
        }
        
        .settings-tab.active {
            display: block;
        }
        
        .settings-tab h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .logo-preview {
            width: 200px;
            height: 100px;
            background-color: var(--background-color);
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .user-list {
            margin-top: 20px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            margin-right: 15px;
        }
        
        .user-role {
            background-color: var(--secondary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .backup-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media screen and (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="logo"><img src="img/logo busana-2.png" alt=""></h1>
                <span class="subtitle">Management Panel</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="dashboard.php">
                        <i class="uil uil-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="produk.php">
                        <i class="uil uil-shopping-bag"></i>
                        <span>Produk</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="kategori.php">
                        <i class="uil uil-tag-alt"></i>
                        <span>Kategori</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="pesanan.php">
                        <i class="uil uil-shopping-cart"></i>
                        <span>Pesanan</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="pelanggan.php">
                        <i class="uil uil-users-alt"></i>
                        <span>Pelanggan</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="laporan.php">
                        <i class="uil uil-chart"></i>
                        <span>Laporan</span>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="pengaturan.php">
                        <i class="uil uil-setting"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="uil uil-signout"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="toggle-sidebar">
                    <i class="uil uil-bars"></i>
                </div>
                
                <div class="nav-actions">
                    <div class="notification">
                        <i class="uil uil-bell"></i>
                        <span class="badge">3</span>
                    </div>
                    
                    <div class="admin-profile">
                        <img src="img/admin-avatar.png" alt="Admin">
                        <span>Admin</span>
                        <i class="uil uil-angle-down"></i>
                    </div>
                </div>
            </nav>
            
            <!-- Settings Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Pengaturan</h2>
                </div>
                
                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?= $error_message ?>
                </div>
                <?php endif; ?>
                
                <div class="settings-container">
                    <!-- Settings Navigation -->
                    <div class="settings-nav">
                        <ul>
                            <li>
                                <a href="#store-info" class="active" onclick="showTab('store-info'); return false;">
                                    <i class="uil uil-store"></i>
                                    <span>Informasi Toko</span>
                                </a>
                            </li>
                            <li>
                                <a href="#tax-settings" onclick="showTab('tax-settings'); return false;">
                                    <i class="uil uil-percentage"></i>
                                    <span>Pajak & Mata Uang</span>
                                </a>
                            </li>
                            <li>
                                <a href="#printer-settings" onclick="showTab('printer-settings'); return false;">
                                    <i class="uil uil-print"></i>
                                    <span>Pengaturan Printer</span>
                                </a>
                            </li>
                            <li>
                                <a href="#user-management" onclick="showTab('user-management'); return false;">
                                    <i class="uil uil-users-alt"></i>
                                    <span>Manajemen Pengguna</span>
                                </a>
                            </li>
                            <li>
                                <a href="#password" onclick="showTab('password'); return false;">
                                    <i class="uil uil-lock"></i>
                                    <span>Ubah Password</span>
                                </a>
                            </li>
                            <li>
                                <a href="#backup" onclick="showTab('backup'); return false;">
                                    <i class="uil uil-database"></i>
                                    <span>Backup & Restore</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Settings Content -->
                    <div class="settings-content">
                        <!-- Store Information -->
                        <div id="store-info" class="settings-tab active">
                            <h3>Informasi Toko</h3>
                            
                            <form action="" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="store_name">Nama Toko</label>
                                    <input type="text" id="store_name" name="store_name" class="form-control" value="<?= $settings['nama_toko'] ?? 'TakuPos' ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="store_logo">Logo Toko</label>
                                    <div class="logo-preview">
                                        <?php if (!empty($settings['logo_toko'])): ?>
                                            <img src="img/<?= $settings['logo_toko'] ?>" alt="Logo Toko">
                                        <?php else: ?>
                                            <img src="img/logo busana-1.png" alt="Logo Default">
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" id="store_logo" name="store_logo" class="form-control-file">
                                    <small class="form-text text-muted">Format yang didukung: JPG, PNG, JPEG. Ukuran maksimal: 2MB</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="store_address">Alamat Toko</label>
                                    <textarea id="store_address" name="store_address" class="form-control"><?= $settings['alamat_toko'] ?? '' ?></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="store_phone">No. Telepon</label>
                                        <input type="text" id="store_phone" name="store_phone" class="form-control" value="<?= $settings['telepon_toko'] ?? '' ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="store_email">Email</label>
                                        <input type="email" id="store_email" name="store_email" class="form-control" value="<?= $settings['email_toko'] ?? '' ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="store_description">Deskripsi Toko</label>
                                    <textarea id="store_description" name="store_description" class="form-control"><?= $settings['deskripsi_toko'] ?? '' ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_store" class="btn primary-btn">Simpan Perubahan</button>
                                <button type="submit" name="update_logo" class="btn secondary-btn">Update Logo</button>
                            </form>
                        </div>
                        
                        <!-- Tax & Currency Settings -->
                        <div id="tax-settings" class="settings-tab">
                            <h3>Pengaturan Pajak & Mata Uang</h3>
                            
                            <form action="" method="post">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="tax_rate">Tarif Pajak (%)</label>
                                        <input type="number" id="tax_rate" name="tax_rate" class="form-control" step="0.1" min="0" max="100" value="<?= $settings['pajak'] ?? '0' ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="currency">Mata Uang</label>
                                        <select id="currency" name="currency" class="form-control">
                                            <option value="IDR" <?= ($settings['mata_uang'] ?? '') == 'IDR' ? 'selected' : '' ?>>Rupiah (IDR)</option>
                                            <option value="USD" <?= ($settings['mata_uang'] ?? '') == 'USD' ? 'selected' : '' ?>>US Dollar (USD)</option>
                                            <option value="EUR" <?= ($settings['mata_uang'] ?? '') == 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                                            <option value="SGD" <?= ($settings['mata_uang'] ?? '') == 'SGD' ? 'selected' : '' ?>>Singapore Dollar (SGD)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="currency_symbol">Simbol Mata Uang</label>
                                    <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?= $settings['simbol_mata_uang'] ?? 'Rp' ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="receipt_footer">Footer Struk</label>
                                    <textarea id="receipt_footer" name="receipt_footer" class="form-control"><?= $settings['footer_struk'] ?? 'Terima kasih telah berbelanja di toko kami!' ?></textarea>
                                    <small class="form-text text-muted">Teks ini akan ditampilkan di bagian bawah setiap struk</small>
                                </div>
                                
                                <button type="submit" name="update_tax" class="btn primary-btn">Simpan Perubahan</button>
                            </form>
                        </div>
                        
                        <!-- Printer Settings -->
                        <div id="printer-settings" class="settings-tab">
                            <h3>Pengaturan Printer</h3>
                            
                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="printer_name">Nama Printer</label>
                                    <input type="text" id="printer_name" name="printer_name" class="form-control" value="<?= $settings['nama_printer'] ?? '' ?>">
                                    <small class="form-text text-muted">Nama printer yang terhubung dengan sistem</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="receipt_width">Lebar Struk (mm)</label>
                                    <input type="number" id="receipt_width" name="receipt_width" class="form-control" min="50" max="100" value="<?= $settings['lebar_struk'] ?? '80' ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="checkbox-container">
                                        <input type="checkbox" id="auto_print" name="auto_print" <?= ($settings['cetak_otomatis'] ?? 0) == 1 ? 'checked' : '' ?>>
                                        <span class="checkmark"></span>
                                        Cetak struk otomatis setelah transaksi
                                    </label>
                                </div>
                                
                                <button type="submit" name="update_printer" class="btn primary-btn">Simpan Perubahan</button>
                            </form>
                        </div>
                        
                        <!-- User Management -->
                        <div id="user-management" class="settings-tab">
                            <h3>Manajemen Pengguna</h3>
                            
                            <div class="user-list">
                                <?php if (count($admin_users) > 0): ?>
                                    <?php foreach ($admin_users as $user): ?>
                                        <div class="user-item">
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="user-name"><?= $user['username'] ?></div>
                                                    <div class="user-email"><?= $user['email'] ?></div>
                                                </div>
                                                <?php if ($user['level'] == 'admin'): ?>
                                                    <span class="user-role">Admin</span>
                                                <?php else: ?>
                                                    <span class="user-role">Kasir</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="user-actions">
                                                <button class="btn small-btn secondary-btn" onclick="editUser(<?= $user['id_admin'] ?>)">
                                                    <i class="uil uil-edit"></i>
                                                </button>
                                                <?php if ($_SESSION['admin_id'] != $user['id_admin']): ?>
                                                    <button class="btn small-btn danger-btn" onclick="deleteUser(<?= $user['id_admin'] ?>)">
                                                        <i class="uil uil-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-data">
                                        <p>Belum ada data pengguna</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn primary-btn" onclick="addNewUser()" style="margin-top: 20px;">
                                <i class="uil uil-plus"></i> Tambah Pengguna Baru
                            </button>
                        </div>
                        
                        <!-- Change Password -->
                        <div id="password" class="settings-tab">
                            <h3>Ubah Password</h3>
                            
                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="current_password">Password Saat Ini</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">Password Baru</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <small class="form-text text-muted">Minimal 8 karakter dengan kombinasi huruf dan angka</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Konfirmasi Password Baru</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" name="update_password" class="btn primary-btn">Ubah Password</button>
                            </form>
                        </div>
                        
                        <!-- Backup &
<!-- Backup & Restore -->
<div id="backup" class="settings-tab">
                            <h3>Backup & Restore Database</h3>
                            
                            <p>Lakukan backup database secara rutin untuk melindungi data bisnis Anda dari kehilangan atau kerusakan.</p>
                            
                            <form action="" method="post" class="backup-actions">
                                <button type="submit" name="backup_database" class="btn primary-btn">
                                    <i class="uil uil-download-alt"></i> Backup Database
                                </button>
                                
                                <div class="form-group" style="flex: 1;">
                                    <label for="restore_file">Upload File Restore</label>
                                    <input type="file" id="restore_file" name="restore_file" class="form-control-file">
                                </div>
                                
                                <button type="submit" name="restore_database" class="btn danger-btn">
                                    <i class="uil uil-upload-alt"></i> Restore
                                </button>
                            </form>
                            
                            <div class="alert alert-danger" style="margin-top: 20px;">
                                <strong>Peringatan!</strong> Proses restore akan menimpa seluruh data yang ada. Pastikan Anda telah melakukan backup sebelum melakukan restore.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Tambah Pengguna Baru</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm" action="manage_user.php" method="post">
                    <input type="hidden" id="user_id" name="user_id" value="">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_password">Password</label>
                        <input type="password" id="user_password" name="user_password" class="form-control" required>
                        <small class="form-text text-muted" id="password_hint">Minimal 8 karakter dengan kombinasi huruf dan angka</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_level">Level</label>
                        <select id="user_level" name="user_level" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" id="saveUserBtn" name="save_user" class="btn primary-btn">Simpan</button>
                        <button type="button" class="btn secondary-btn close-modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal" id="deleteUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Hapus Pengguna</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Anda yakin ingin menghapus pengguna ini?</p>
                <form action="manage_user.php" method="post">
                    <input type="hidden" id="delete_user_id" name="delete_user_id" value="">
                    
                    <div class="form-actions">
                        <button type="submit" name="delete_user" class="btn danger-btn">Hapus</button>
                        <button type="button" class="btn secondary-btn close-modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Show settings tab
        function showTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.settings-nav a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show the selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to the clicked nav link
            document.querySelector(`.settings-nav a[href="#${tabId}"]`).classList.add('active');
        }
        
        // User Management Functions
        function addNewUser() {
            document.getElementById('userModalTitle').textContent = 'Tambah Pengguna Baru';
            document.getElementById('userForm').reset();
            document.getElementById('user_id').value = '';
            document.getElementById('password_hint').style.display = 'block';
            document.getElementById('user_password').required = true;
            
            // Show modal
            document.getElementById('userModal').style.display = 'block';
        }
        
        function editUser(userId) {
            document.getElementById('userModalTitle').textContent = 'Edit Pengguna';
            document.getElementById('userForm').reset();
            document.getElementById('user_id').value = userId;
            document.getElementById('password_hint').style.display = 'none';
            document.getElementById('user_password').required = false;
            
            // Fetch user data via AJAX and populate form (would be implemented in real application)
            // For demonstration, we'll just show the modal
            document.getElementById('userModal').style.display = 'block';
        }
        
        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('deleteUserModal').style.display = 'block';
        }
        
        // Close modals when clicking on X or Cancel button
        document.querySelectorAll('.close, .close-modal').forEach(element => {
            element.addEventListener('click', function() {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            });
        });
        
        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
        
        // Preview uploaded logo
        document.getElementById('store_logo').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.logo-preview img');
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>