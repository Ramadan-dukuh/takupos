<?php
include "koneksi.php";

// Handle Product Deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $product_id = $_GET['delete'];
    $delete_query = "DELETE FROM produk WHERE id = $product_id";
    
    if ($kon->query($delete_query) === TRUE) {
        $success_message = "Produk berhasil dihapus!";
    } else {
        $error_message = "Error: " . $kon->error;
    }
}

// Pagination setup
$limit = 12; // Items per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE nama LIKE '%$search%' OR gambar LIKE '%$search%'";
}

// Get total products count for pagination
$count_query = "SELECT COUNT(*) as total FROM produk $search_condition";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $limit);

// Get products with pagination
$query = "SELECT * FROM produk $search_condition ORDER BY id DESC LIMIT $offset, $limit";
$result = $kon->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style/style.css">
    <link rel="shortcut icon" href="img/logo busana-1.png" type="image/x-icon">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="script.js" defer></script>
    <title>Manajemen Produk - Fashion24</title>
    <style>
        .product-filter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px;            
        }
        .filter-group {
            display: flex;
            align-items: center;
            margin-right: 10px;
        }
        .filter-group label {
            margin-right: 10px;
        }
        .filter-group select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .pagination-btn {
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;                                    
        }        
        .pagination-number{
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            margin: 0 5px;            
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
                <li class="menu-item active">
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
                    <form action="" method="GET">
                        <i class="uil uil-search search-icon"></i>
                        <input type="text" name="search" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>" />
                    </form>
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
            
            <!-- Product Management Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Manajemen Produk</h2>
                    <a href="addproduk.php" class="btn primary-btn">
                        <i class="uil uil-plus"></i> Tambah Produk Baru
                    </a>
                </div>
                
                <?php if(isset($success_message)): ?>
                <div class="alert success">
                    <i class="uil uil-check-circle"></i>
                    <?= $success_message ?>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                <div class="alert error">
                    <i class="uil uil-exclamation-triangle"></i>
                    <?= $error_message ?>
                </div>
                <?php endif; ?>
                
                <div class="product-filters">
                    <div class="filter-group">
                        <label for="category">Kategori:</label>
                        <select id="category" name="category">
                            <option value="">Semua Kategori</option>
                            <option value="1">Celana</option>
                            <option value="2">Baju</option>
                            <option value="3">Dress</option>
                            <option value="4">Sepatu</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Urutkan:</label>
                        <select id="sort" name="sort">
                            <option value="newest">Terbaru</option>
                            <option value="oldest">Terlama</option>
                            <option value="price_high">Harga Tertinggi</option>
                            <option value="price_low">Harga Terendah</option>
                            <option value="name_asc">Nama A-Z</option>
                            <option value="name_desc">Nama Z-A</option>
                        </select>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="card-header">
                        <h3>Daftar Produk</h3>
                        <div class="header-actions">
                            <span class="product-count"><?= $total_products ?> Produk</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="product-management-grid">
                                <?php while($row = $result->fetch_assoc()): ?>
                                <div class="product-card">
                                    <div class="product-img">
                                        <img src="<?= !empty($row['gambar']) ? $row['gambar'] : 'img/bg.png' ?>" alt="<?= $row['nama_produk'] ?>">
                                    </div>
                                    <div class="product-info">
                                        <h4><?= $row['nama'] ?></h4>
                                        <p class="product-price">Rp<?= number_format($row['harga'], 0, ',', '.') ?></p>
                                        <p class="product-stock">Stok: <?= isset($row['stock']) ? $row['stock'] : 0 ?></p>
                                        <div class="product-actions">
                                            <a href="edit-produk.php?id=<?= $row['id'] ?>" class="btn edit-btn">
                                                <i class="uil uil-edit"></i> Edit
                                            </a>
                                            <a href="produk.php?delete=<?= $row['id'] ?>" class="btn delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                                <i class="uil uil-trash-alt"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                <a href="?page=<?= ($page - 1) ?><?= !empty($search) ? '&search='.$search : '' ?>" class="pagination-btn prev">
                                    <i class="uil uil-angle-left"></i> Sebelumnya
                                </a>
                                <?php endif; ?>
                                
                                <div class="pagination-numbers">
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?><?= !empty($search) ? '&search='.$search : '' ?>" class="page-number <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if($page < $total_pages): ?>
                                <a href="?page=<?= ($page + 1) ?><?= !empty($search) ? '&search='.$search : '' ?>" class="pagination-btn next">
                                    Berikutnya <i class="uil uil-angle-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="no-product">
                                <i class="uil uil-box"></i>
                                <?php if(!empty($search)): ?>
                                <p>Tidak ditemukan produk dengan kata kunci "<?= htmlspecialchars($search) ?>"</p>
                                <a href="produk.php" class="btn secondary-btn">Tampilkan Semua Produk</a>
                                <?php else: ?>
                                <p>Belum ada produk ditambahkan</p>
                                <a href="tambah-produk.php" class="btn add-btn">Tambah Produk</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>