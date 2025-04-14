<?php
include "koneksi.php";

// Handle Order Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $pesanan_id = $_POST['pesanan_id'];
    $status_pesanan = $_POST['status_pesanan'];
    
    $update_query = "UPDATE pesanan SET status_pesanan = '$status_pesanan', 
                    updated_at = NOW() WHERE id_pesanan = $pesanan_id";
    
    if ($kon->query($update_query) === TRUE) {
        $success_message = "Status pesanan berhasil diperbarui!";
    } else {
        $error_message = "Error: " . $kon->error;
    }
}

// Pagination setup
$limit = 10; // Items per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter functionality
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query conditions
$conditions = [];

if (!empty($status_filter)) {
    $conditions[] = "p.status_pesanan = '$status_filter'";
}

if (!empty($date_filter)) {
    if ($date_filter == 'today') {
        $conditions[] = "DATE(p.created_at) = CURDATE()";
    } elseif ($date_filter == 'yesterday') {
        $conditions[] = "DATE(p.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($date_filter == 'week') {
        $conditions[] = "p.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_filter == 'month') {
        $conditions[] = "p.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
}

if (!empty($search)) {
    $conditions[] = "(p.id_pesanan LIKE '%$search%' OR c.nama_pelanggan LIKE '%$search%' OR p.alamat_pengiriman LIKE '%$search%')";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total orders count for pagination
$count_query = "SELECT COUNT(*) as total FROM transaksi p
                LEFT JOIN user c ON p.id = c.id
                $where_clause";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_orders = $count_row['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders with pagination
// $query = "SELECT p.*, c.nama_pelanggan, c.no_telp,
//           (SELECT SUM(dp.quantity * dp.harga_produk) FROM detail_pesanan dp WHERE dp.id_pesanan = p.id_pesanan) as total_belanja
//           FROM pesanan p
//           LEFT JOIN pelanggan c ON p.id_pelanggan = c.id_pelanggan
//           $where_clause
//           ORDER BY p.created_at DESC LIMIT $offset, $limit";
// $result = $kon->query($query);

// Get status counts for tabs
// $status_counts = [];
// $status_query = "SELECT status_pesanan, COUNT(*) as count FROM pesanan GROUP BY status_pesanan";
// $status_result = $kon->query($status_query);
// while ($status_row = $status_result->fetch_assoc()) {
//     $status_counts[$status_row['status_pesanan']] = $status_row['count'];
// }

// Get total orders count
// $total_count_query = "SELECT COUNT(*) as count FROM pesanan";
// $total_count_result = $kon->query($total_count_query);
// $total_count_row = $total_count_result->fetch_assoc();
// $total_count = $total_count_row['count'];
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
    <title>Manajemen Pesanan - TakuPos</title>
    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
        }
        
        .status-badge.pending {
            background-color: #fff8e6;
            color: #ffa800;
        }
        
        .status-badge.processing {
            background-color: #e6f2ff;
            color: #0077ff;
        }
        
        .status-badge.shipped {
            background-color: #e9f7ef;
            color: #27ae60;
        }
        
        .status-badge.delivered {
            background-color: #e6fff6;
            color: #00b894;
        }
        
        .status-badge.cancelled {
            background-color: #ffe6e6;
            color: #ff0000;
        }
        
        .status-badge i {
            margin-right: 5px;
            font-size: 14px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .filter-tab {
            padding: 10px 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filter-tab:hover {
            border-color: var(--primary-color);
        }
        
        .filter-tab.active {
            background-color: var(--primary-lighter);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .filter-tab .count {
            font-size: 12px;
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 6px;
            border-radius: 20px;
        }
        
        .date-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .date-filter {
            padding: 8px 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        
        .date-filter:hover {
            border-color: var(--primary-color);
        }
        
        .date-filter.active {
            background-color: var(--primary-lighter);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .customer-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--primary-lighter);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }
    </style>
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
                <li class="menu-item active">
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
                        <input type="text" name="search" placeholder="Cari pesanan..." value="<?= htmlspecialchars($search) ?>" />
                        <?php if(!empty($status_filter)): ?>
                            <input type="hidden" name="status" value="<?= $status_filter ?>">
                        <?php endif; ?>
                        <?php if(!empty($date_filter)): ?>
                            <input type="hidden" name="date" value="<?= $date_filter ?>">
                        <?php endif; ?>
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
            
            <!-- Order Management Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Manajemen Pesanan</h2>
                    <div class="header-actions">
                        <a href="laporan.php?type=pesanan" class="btn secondary-btn">
                            <i class="uil uil-file-download"></i> Unduh Laporan
                        </a>
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
                
                <div class="filter-tabs">
                    <a href="pesanan.php" class="filter-tab <?= empty($status_filter) ? 'active' : '' ?>">
                        Semua <span class="count"><?= $total_count ?></span>
                    </a>
                    <a href="?status=pending<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">
                        <i class="uil uil-clock"></i> Pending 
                        <span class="count"><?= isset($status_counts['pending']) ? $status_counts['pending'] : 0 ?></span>
                    </a>
                    <a href="?status=processing<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'processing' ? 'active' : '' ?>">
                        <i class="uil uil-process"></i> Diproses 
                        <span class="count"><?= isset($status_counts['processing']) ? $status_counts['processing'] : 0 ?></span>
                    </a>
                    <a href="?status=shipped<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'shipped' ? 'active' : '' ?>">
                        <i class="uil uil-truck"></i> Dikirim 
                        <span class="count"><?= isset($status_counts['shipped']) ? $status_counts['shipped'] : 0 ?></span>
                    </a>
                    <a href="?status=delivered<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'delivered' ? 'active' : '' ?>">
                        <i class="uil uil-check-circle"></i> Selesai 
                        <span class="count"><?= isset($status_counts['delivered']) ? $status_counts['delivered'] : 0 ?></span>
                    </a>
                    <a href="?status=cancelled<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'cancelled' ? 'active' : '' ?>">
                        <i class="uil uil-times-circle"></i> Dibatalkan 
                        <span class="count"><?= isset($status_counts['cancelled']) ? $status_counts['cancelled'] : 0 ?></span>
                    </a>
                </div>
                
                <div class="date-filters">
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=today" 
                       class="date-filter <?= $date_filter === 'today' ? 'active' : '' ?>">
                        Hari Ini
                    </a>
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=yesterday" 
                       class="date-filter <?= $date_filter === 'yesterday' ? 'active' : '' ?>">
                        Kemarin
                    </a>
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=week" 
                       class="date-filter <?= $date_filter === 'week' ? 'active' : '' ?>">
                        7 Hari Terakhir
                    </a>
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=month" 
                       class="date-filter <?= $date_filter === 'month' ? 'active' : '' ?>">
                        30 Hari Terakhir
                    </a>
                    <?php if(!empty($date_filter)): ?>
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter : '' ?><?= !empty($search) ? (!empty($status_filter) ? '&' : '').'search='.$search : '' ?>" 
                       class="date-filter">
                        Reset Filter
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="content-card">
                    <div class="card-header">
                        <h3>Daftar Pesanan</h3>
                        <div class="header-actions">
                            <span class="product-count"><?= $total_orders ?> Pesanan</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="responsive-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Pelanggan</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $row['id_pesanan'] ?></td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-avatar">
                                                        <?= strtoupper(substr($row['nama_pelanggan'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div><?= $row['nama_pelanggan'] ?></div>
                                                        <div class="text-muted small"><?= $row['no_telp'] ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="price">
                                                    Rp <?= number_format($row['total_belanja'], 0, ',', '.') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_icon = '';
                                                switch($row['status_pesanan']) {
                                                    case 'pending':
                                                        $status_icon = '<i class="uil uil-clock"></i>';
                                                        break;
                                                    case 'processing':
                                                        $status_icon = '<i class="uil uil-process"></i>';
                                                        break;
                                                    case 'shipped':
                                                        $status_icon = '<i class="uil uil-truck"></i>';
                                                        break;
                                                    case 'delivered':
                                                        $status_icon = '<i class="uil uil-check-circle"></i>';
                                                        break;
                                                    case 'cancelled':
                                                        $status_icon = '<i class="uil uil-times-circle"></i>';
                                                        break;
                                                }
                                                ?>
                                                <span class="status-badge <?= $row['status_pesanan'] ?>">
                                                    <?= $status_icon ?>
                                                    <?php
                                                    switch($row['status_pesanan']) {
                                                        case 'pending':
                                                            echo 'Pending';
                                                            break;
                                                        case 'processing':
                                                            echo 'Diproses';
                                                            break;
                                                        case 'shipped':
                                                            echo 'Dikirim';
                                                            break;
                                                        case 'delivered':
                                                            echo 'Selesai';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'Dibatalkan';
                                                            break;
                                                        default:
                                                            echo ucfirst($row['status_pesanan']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                            <td class="action-buttons">
                                                <a href="detail_pesanan.php?id=<?= $row['id_pesanan'] ?>" class="btn view-btn">
                                                    <i class="uil uil-eye"></i> Detail
                                                </a>
                                                <button class="btn edit-btn change-status-btn" data-id="<?= $row['id_pesanan'] ?>" data-status="<?= $row['status_pesanan'] ?>">
                                                    <i class="uil uil-edit"></i> Status
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                <a href="?page=<?= ($page - 1) ?><?= !empty($search) ? '&search='.$search : '' ?><?= !empty($status_filter) ? '&status='.$status_filter : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" class="pagination-btn prev">
                                    <i class="uil uil-angle-left"></i> Sebelumnya
                                </a>
                                <?php endif; ?>
                                
                                <div class="pagination-numbers">
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?><?= !empty($search) ? '&search='.$search : '' ?><?= !empty($status_filter) ? '&status='.$status_filter : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" class="page-number <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if($page < $total_pages): ?>
                                <a href="?page=<?= ($page + 1) ?><?= !empty($search) ? '&search='.$search : '' ?><?= !empty($status_filter) ? '&status='.$status_filter : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" class="pagination-btn next">
                                    Berikutnya <i class="uil uil-angle-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="no-data">
                                <i class="uil uil-shopping-cart"></i>
                                <?php if(!empty($search) || !empty($status_filter) || !empty($date_filter)): ?>
                                <p>Tidak ditemukan pesanan yang sesuai dengan filter</p>
                                <a href="pesanan.php" class="btn secondary-btn">Reset Filter</a>
                                <?php else: ?>
                                <p>Belum ada pesanan masuk</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Change Status Modal -->
    <div class="modal" id="changeStatusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ubah Status Pesanan</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="status_pesanan">Status Pesanan</label>
                        <select id="status_pesanan" name="status_pesanan" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Diproses</option>
                            <option value="shipped">Dikirim</option>
                            <option value="delivered">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="pesanan_id" id="edit_pesanan_id">
                        <input type="hidden" name="update_status" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Batal</button>
                        <button type="submit" class="btn primary-btn">Perbarui Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const changeStatusModal = document.getElementById('changeStatusModal');
        const changeStatusButtons = document.querySelectorAll('.change-status-btn');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        const cancelButtons = document.querySelectorAll('.cancel-modal');
        
        // Show change status modal
        changeStatusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const pesananId = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-status');
                
                document.getElementById('edit_pesanan_id').value = pesananId;
                document.getElementById('status_pesanan').value = currentStatus;
                
                changeStatusModal.style.display = 'flex';
            });
        });
        
        // Close modals
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                changeStatusModal.style.display = 'none';
            });
        });
        
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                changeStatusModal.style.display = 'none';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === changeStatusModal) {
                changeStatusModal.style.display = 'none';
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
            }, 3000);
        }
    </script>
</body>
</html>