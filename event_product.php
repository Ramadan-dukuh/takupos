<?php
include "koneksi.php";

// Get event ID from URL
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : 0;

if ($event_id == 0) {
    header("Location: event.php");
    exit();
}

// Get event details
$event_query = "SELECT * FROM events WHERE id = $event_id";
$event_result = $kon->query($event_query);

if (!$event_result || $event_result->num_rows == 0) {
    header("Location: event.php");
    exit();
}

$event = $event_result->fetch_assoc();

// Handle Product Removal from Event
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $product_id = $_GET['remove'];
    
    $remove_query = "DELETE FROM event_product WHERE event_id = $event_id AND product_id = $product_id";
    
    if ($kon->query($remove_query) === TRUE) {
        $success_message = "Produk berhasil dihapus dari event!";
    } else {
        $error_message = "Error: " . $kon->error;
    }
}

// Handle Add Product to Event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $product_id = $_POST['product_id'];
    
    // Check if product already in event
    $check_query = "SELECT COUNT(*) as total FROM event_product WHERE event_id = $event_id AND product_id = $product_id";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Produk sudah ditambahkan ke event ini.";
    } else {
        // Add product to event
        $insert_query = "INSERT INTO event_product (event_id, product_id, created_at) 
                        VALUES ($event_id, $product_id, NOW())";
        
        if ($kon->query($insert_query) === TRUE) {
            $success_message = "Produk berhasil ditambahkan ke event!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Pagination setup
$limit = 12; // Items per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = '';

if (!empty($search)) {
    $where_clause = "AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%' OR c.name LIKE '%$search%')";
}

// Get total products count for pagination
$count_query = "SELECT COUNT(*) as total FROM event_product ep
                JOIN products p ON ep.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE ep.event_id = $event_id $where_clause";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];
$total_pages = ceil($total_products / $limit);

// Get products in event with pagination
$query = "SELECT p.*, c.name as category_name, ep.created_at as added_to_event,
          (p.price - (p.price * {$event['discount_percentage']} / 100)) as discounted_price
          FROM event_product ep
          JOIN products p ON ep.product_id = p.id
          JOIN categories c ON p.category_id = c.id
          WHERE ep.event_id = $event_id $where_clause
          ORDER BY ep.created_at DESC 
          LIMIT $offset, $limit";
$result = $kon->query($query);

// Get available products not in event for adding
$available_query = "SELECT p.id, p.name, p.price, c.name as category_name
                   FROM products p
                   JOIN categories c ON p.category_id = c.id
                   WHERE p.id NOT IN (SELECT product_id FROM event_product WHERE event_id = $event_id)
                   ORDER BY p.name";
$available_result = $kon->query($available_query);
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
    <title>Produk Event: <?= htmlspecialchars($event['name']) ?> - Fashion24</title>
    <style>
        .event-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .event-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .event-details {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .event-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .event-detail-item i {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .discount-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            height: 200px;
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: var(--primary-color);
            position: relative;
        }
        
        .discount-label {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--danger-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-text);
        }
        
        .product-category {
            font-size: 13px;
            color: var(--light-text);
            margin-bottom: 12px;
        }
        
        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .original-price {
            font-size: 14px;
            color: var(--light-text);
            text-decoration: line-through;
        }
        
        .discounted-price {
            font-size: 18px;
            font-weight: 600;
            color: var(--danger-color);
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 15px;
            width: 100%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 20px;
        }
        
        .close-modal {
            cursor: pointer;
            font-size: 24px;
            color: var(--light-text);
        }
        
        .close-modal:hover {
            color: var(--dark-text);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-text);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }
        
        .primary-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .secondary-btn {
            background-color: var(--light-bg);
            color: var(--dark-text);
        }
        
        .danger-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: opacity 0.3s;
        }
        
        .alert.success {
            background-color: #e9f7ef;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }
        
        .alert.error {
            background-color: #ffe6e6;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }
        
        .breadcrumb {
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--light-text);
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--light-text);
        }
        
        .no-data i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .available-product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .available-product-item:last-child {
            border-bottom: none;
        }
        
        .available-product-info {
            flex: 1;
        }
        
        .available-product-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .available-product-details {
            font-size: 13px;
            color: var(--light-text);
        }
        
        .add-product-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .add-product-btn:hover {
            background-color: var(--primary-dark);
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
                <li class="menu-item active">
                    <a href="event.php">
                        <i class="uil uil-calendar-alt"></i>
                        <span>Events</span>
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
                        <input type="hidden" name="event_id" value="<?= $event_id ?>">
                        <i class="uil uil-search search-icon"></i>
                        <input type="text" name="search" placeholder="Cari Produk..." value="<?= htmlspecialchars($search) ?>" />
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
            
            <!-- Event Product Content -->
            <div class="dashboard">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="event.php">Events</a> / <span>Produk Event</span>
                </div>
                
                <!-- Event Header -->
                <div class="event-header">
                    <div class="event-title"><?= htmlspecialchars($event['name']) ?></div>
                    <p><?= htmlspecialchars($event['description']) ?></p>
                    <div class="event-details">
                        <div class="event-detail-item">
                            <i class="uil uil-calendar-alt"></i>
                            <span><?= date('d M Y', strtotime($event['start_date'])) ?> - <?= date('d M Y', strtotime($event['end_date'])) ?></span>
                        </div>
                        <div class="discount-badge">
                            <?= $event['discount_percentage'] ?>% OFF
                        </div>
                    </div>
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
                
                <!-- Statistics -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_products ?></div>
                        <div class="stat-label">Total Produk</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $event['discount_percentage'] ?>%</div>
                        <div class="stat-label">Diskon Event</div>
                    </div>
                </div>
                
                <div class="content-card">
                    <div class="card-header">
                        <h3>Produk dalam Event</h3>
                        <div class="header-actions">
                            <button class="btn primary-btn" id="addProductBtn">
                                <i class="uil uil-plus"></i> Tambah Produk
                            </button>
                            <a href="event.php" class="btn secondary-btn">
                                <i class="uil uil-arrow-left"></i> Kembali ke Event
                            </a>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="product-grid">
                                <?php while($row = $result->fetch_assoc()): ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <i class="uil uil-image"></i>
                                        <div class="discount-label">-<?= $event['discount_percentage'] ?>%</div>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-name"><?= htmlspecialchars($row['name']) ?></div>
                                        <div class="product-category"><?= htmlspecialchars($row['category_name']) ?></div>
                                        <div class="product-price">
                                            <span class="original-price">Rp <?= number_format($row['price'], 0, ',', '.') ?></span>
                                            <span class="discounted-price">Rp <?= number_format($row['discounted_price'], 0, ',', '.') ?></span>
                                        </div>
                                        <div class="product-actions">
                                            <a href="?event_id=<?= $event_id ?>&remove=<?= $row['id'] ?><?= !empty($search) ? '&search='.$search : '' ?>" 
                                               class="btn danger-btn" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini dari event?');">
                                                <i class="uil uil-trash"></i> Hapus
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
                                <a href="?event_id=<?= $event_id ?>&page=<?= ($page - 1) ?><?= !empty($search) ? '&search='.$search : '' ?>" class="pagination-btn prev">
                                    <i class="uil uil-angle-left"></i> Sebelumnya
                                </a>
                                <?php endif; ?>
                                
                                <div class="pagination-numbers">
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?event_id=<?= $event_id ?>&page=<?= $i ?><?= !empty($search) ? '&search='.$search : '' ?>" class="page-number <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if($page < $total_pages): ?>
                                <a href="?event_id=<?= $event_id ?>&page=<?= ($page + 1) ?><?= !empty($search) ? '&search='.$search : '' ?>" class="pagination-btn next">
                                    Berikutnya <i class="uil uil-angle-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="no-data">
                                <i class="uil uil-shopping-bag"></i>
                                <?php if(!empty($search)): ?>
                                <p>Tidak ditemukan produk yang sesuai dengan pencarian</p>
                                <a href="?event_id=<?= $event_id ?>" class="btn secondary-btn">Reset Pencarian</a>
                                <?php else: ?>
                                <p>Belum ada produk dalam event ini</p>
                                <button class="btn primary-btn" id="addProductBtnEmpty">
                                    <i class="uil uil-plus"></i> Tambah Produk Pertama
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Product Modal -->
    <div class="modal" id="addProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Produk ke Event</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <?php if ($available_result && $available_result->num_rows > 0): ?>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Pilih Produk</label>
                            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px;">
                                <?php while($product = $available_result->fetch_assoc()): ?>
                                <div class="available-product-item">
                                    <div class="available-product-info">
                                        <div class="available-product-name"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="available-product-details">
                                            <?= htmlspecialchars($product['category_name']) ?> - Rp <?= number_format($product['price'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_product" value="1" class="add-product-btn"
                                            onclick="document.getElementById('selected_product_id').value = <?= $product['id'] ?>">
                                        Tambah
                                    </button>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <input type="hidden" name="product_id" id="selected_product_id">
                        </div>
                    </form>
                <?php else: ?>
                    <p style="text-align: center; color: var(--light-text); padding: 40px;">
                        <i class="uil uil-info-circle" style="font-size: 48px; margin-bottom: 15px;"></i><br>
                        Semua produk sudah ditambahkan ke event ini atau belum ada produk yang tersedia.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const addProductModal = document.getElementById('addProductModal');
        const addProductBtn = document.getElementById('addProductBtn');
        const addProductBtnEmpty = document.getElementById('addProductBtnEmpty');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        
        // Show add product modal
        if (addProductBtn) {
            addProductBtn.addEventListener('click', function() {
                addProductModal.style.display = 'flex';
            });
        }
        
        if (addProductBtnEmpty) {
            addProductBtnEmpty.addEventListener('click', function() {
                addProductModal.style.display = 'flex';
            });
        }
        
        // Close modals
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (addProductModal) addProductModal.style.display = 'none';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addProductModal) {
                addProductModal.style.display = 'none';
            }
        });
        
        // Alert auto-hide
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                });
            }, 4000);
        }
    </script>
</body>
</html>