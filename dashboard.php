<?php
include 'koneksi.php';

// get all product
$count_query = "SELECT COUNT(*) as total FROM produk";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];

//get all transaksi
$count_query = "SELECT COUNT(*) as total FROM transaksi";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_transaction = $count_row['total'];

//get all pelanggan
$count_query = "SELECT COUNT(*) as total FROM user ";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_customer = $count_row['total'];

//get all pendapatan
$sql = "SELECT SUM(dt.jumlah * p.harga_produk) AS total_pendapatan
        FROM detail_transaksi dt
        JOIN produk p ON dt.id_produk = p.id_produk";

$result = $kon->query($sql);
// Cek apakah hasil query berhasil
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_pendapatan = $row['total_pendapatan'] ?? 0;
} else {
    $total_pendapatan = 0;
}

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
    <title>TakuPos - Web Management</title>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="logo">TakuPos</h1>
                <span class="subtitle">Management Panel</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item active">
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
                <li class="menu-item">
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
                
                <div class="search-box">
                    <i class="uil uil-search search-icon"></i>
                    <input type="text" placeholder="Cari..." />
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
            
            <!-- Dashboard Content -->
            <div class="dashboard">
                <h2 class="page-title">Dashboard</h2>
                
                <!-- Stats Cards -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-shopping-cart"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Pesanan</h3>
                            <p class="stat-value"><?= $total_transaction ?></p>
                            <p class="stat-change positive">+12.5% <span>dari bulan lalu</span></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-money-bill"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Pendapatan</h3>
                            <p class="stat-value">Rp <?= $total_pendapatan ?></p>
                            <p class="stat-change positive">+8.3% <span>dari bulan lalu</span></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-users-alt"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Pelanggan Baru</h3>
                            <p class="stat-value"><?= $total_customer ?></p>
                            <p class="stat-change positive">+5.7% <span>dari bulan lalu</span></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-cube"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Produk</h3>
                            <p class="stat-value"><?= $total_products ?></p>
                            <p class="stat-change negative">-2.3% <span>dari bulan lalu</span></p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders and Products Section -->
                <div class="content-grid">
                    <!-- Recent Orders Table -->
                    <div class="content-card orders-table">
                        <div class="card-header">
                            <h3>Pesanan Terbaru</h3>
                            <a href="pesanan.php" class="view-all">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan</th>
                                        <th>Produk</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#ORD-001</td>
                                        <td>Ahmad Rizky</td>
                                        <td>Celana Jeans (2)</td>
                                        <td>Rp550.000</td>
                                        <td><span class="status pending">Menunggu</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn view-btn"><i class="uil uil-eye"></i></button>
                                                <button class="btn edit-btn"><i class="uil uil-edit"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-002</td>
                                        <td>Siti Aminah</td>
                                        <td>Dress Casual (1)</td>
                                        <td>Rp375.000</td>
                                        <td><span class="status shipped">Dikirim</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn view-btn"><i class="uil uil-eye"></i></button>
                                                <button class="btn edit-btn"><i class="uil uil-edit"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-003</td>
                                        <td>Budi Santoso</td>
                                        <td>Sepatu Sneakers (1)</td>
                                        <td>Rp850.000</td>
                                        <td><span class="status completed">Selesai</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn view-btn"><i class="uil uil-eye"></i></button>
                                                <button class="btn edit-btn"><i class="uil uil-edit"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-004</td>
                                        <td>Dewi Anggraini</td>
                                        <td>Baju Kemeja (3)</td>
                                        <td>Rp675.000</td>
                                        <td><span class="status cancelled">Dibatalkan</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn view-btn"><i class="uil uil-eye"></i></button>
                                                <button class="btn edit-btn"><i class="uil uil-edit"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-005</td>
                                        <td>Rina Wijaya</td>
                                        <td>Dress Formal (1)</td>
                                        <td>Rp725.000</td>
                                        <td><span class="status pending">Menunggu</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn view-btn"><i class="uil uil-eye"></i></button>
                                                <button class="btn edit-btn"><i class="uil uil-edit"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Product Management -->
                    <div class="content-card product-management">
                        <div class="card-header">
                            <h3>Produk Terbaru</h3>
                            <a href="produk.php" class="view-all">Kelola Produk</a>
                        </div>
                        <div class="card-body">
                            <div class="product-grid">
                                <?php
                                // Ambil 6 produk terbaru
                                $query = "SELECT * FROM produk ORDER BY id_produk DESC LIMIT 2";
                                $result = $kon->query($query);
                                
                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="product-card">
                                    <div class="product-img">
                                        <img src="<?= !empty($row['gambar_produk']) ? $row['gambar_produk'] : 'img/placeholder.png' ?>" alt="<?= $row['nama_produk'] ?>">
                                    </div>
                                    <div class="product-info">
                                        <h4><?= $row['nama_produk'] ?></h4>
                                        <p class="product-price">Rp<?= number_format($row['harga_produk'], 0, ',', '.') ?></p>
                                        <p class="product-stock">Stok: <?= $row['stock'] ?></p>
                                        <div class="product-actions">
                                            <button class="btn edit-btn"><i class="uil uil-edit"></i> Edit</button>
                                            <button class="btn delete-btn"><i class="uil uil-trash-alt"></i> Hapus</button>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    }
                                } else {
                                ?>
                                <div class="no-product">
                                    <i class="uil uil-box"></i>
                                    <p>Belum ada produk ditambahkan</p>
                                    <a href="tambah-produk.php" class="btn add-btn">Tambah Produk</a>
                                </div>
                                <?php
                                }
                                ?>
                            </div>
                            <div class="add-product-btn">
                                <a href="tambah-produk.php" class="btn primary-btn">
                                    <i class="uil uil-plus"></i> Tambah Produk Baru
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>