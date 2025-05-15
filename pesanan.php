<?php
include "koneksi.php";

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_column = isset($_GET['search_column']) ? $_GET['search_column'] : '';

// Filter waktu
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Bangun WHERE clause
$where_clauses = [];

// Filter waktu
if (!empty($date_filter)) {
    if ($date_filter == 'today') {
        $where_clauses[] = "DATE(t.created_at) = CURDATE()";
    } elseif ($date_filter == 'yesterday') {
        $where_clauses[] = "DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($date_filter == 'week') {
        $where_clauses[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_filter == 'month') {
        $where_clauses[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
}

// Filter pencarian kolom tertentu
$allowed_columns = ['id', 'user_id', 'payment_type'];
if (!empty($search) && in_array($search_column, $allowed_columns)) {
    $where_clauses[] = "t.$search_column LIKE '%" . $kon->real_escape_string($search) . "%'";
}

// Gabungkan semua kondisi WHERE
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Hitung total data
$count_query = "SELECT COUNT(*) as total FROM transactions t $where_sql";
$count_result = $kon->query($count_query);
$total_row = $count_result->fetch_assoc();
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data transaksi dengan detail
$query = "
    SELECT 
        t.id AS transaction_id,
        t.user_id,
        t.payment_type,
        t.created_at,
        t.updated_at,
        (
            SELECT SUM(td.quantity)
            FROM transaction_details td
            WHERE td.transaction_id = t.id
        ) AS total_items
    FROM transactions t
    $where_sql
    ORDER BY t.created_at DESC
    LIMIT ?, ?
";

$stmt = $kon->prepare($query);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
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
    <title>Order Management - Fashion24</title>
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
                        <span>Products</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="kategori.php">
                        <i class="uil uil-tag-alt"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="pesanan.php">
                        <i class="uil uil-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="menu-item">
                 <a href="event.php">
                        <i class="uil uil-calendar-alt"></i>
                        <span>Events</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="laporan.php">
                        <i class="uil uil-chart"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="pengaturan.php">
                        <i class="uil uil-setting"></i>
                        <span>Settings</span>
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
                        <input type="text" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>" />
                        <?php if(!empty($status_filter)): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                        <?php endif; ?>
                        <?php if(!empty($date_filter)): ?>
                            <input type="hidden" name="date" value="<?= htmlspecialchars($date_filter) ?>">
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
        <h2 class="page-title">Order Management</h2>
        <div class="header-actions">
       <a href="download_report.php?search=<?= urlencode($search) ?>&search_column=<?= urlencode($search_column) ?>&date=<?= urlencode($date_filter) ?>" class="btn secondary-btn">
    <i class="uil uil-file-download"></i> Download Report
</a>

        </div>
    </div>

    <?php if(isset($success_message)): ?>
    <div class="alert success">
        <i class="uil uil-check-circle"></i>
        <?= htmlspecialchars($success_message) ?>
    </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
    <div class="alert error">
        <i class="uil uil-exclamation-triangle"></i>
        <?= htmlspecialchars($error_message) ?>
    </div>
    <?php endif; ?>

    <div class="content-card">
        <div class="card-header">
            <h3>Transaction List</h3>
        </div>

        <!-- Form Pencarian dan Filter -->
        <form method="GET" style="margin-bottom: 10px;" class="filter-form">            
            <select name="search_column">
                <option value="">Kolom </option>
                <option value="id" <?= $search_column == 'id' ? 'selected' : '' ?>>ID Transaksi</option>
                <option value="user_id" <?= $search_column == 'user_id' ? 'selected' : '' ?>>User ID</option>
                <option value="payment_type" <?= $search_column == 'payment_type' ? 'selected' : '' ?>>Metode Bayar</option>
            </select>
            <select name="date" class="date-filter">
                <option value="">Tanggal </option>
                <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Hari Ini</option>
                <option value="yesterday" <?= $date_filter == 'yesterday' ? 'selected' : '' ?>>Kemarin</option>
                <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Minggu Ini</option>
                <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Bulan Ini</option>
            </select>
            <button type="submit" class="btn">Filter</button>
        </form>

        <!-- Tabel Data Transaksi -->
        <div class="responsive-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>User ID</th>
                        <th>Metode Pembayaran</th>
                        <th>Total Item</th>
                        <th>Tanggal Transaksi</th>
                        <th>Update Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['transaction_id'] ?></td>
                        <td><?= $row['user_id'] ?></td>
                        <td><?= $row['payment_type'] ?></td>
                        <td><?= $row['total_items'] ?? 0 ?></td>
                        <td><?= $row['created_at'] ?></td>
                        <td><?= $row['updated_at'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Navigasi Halaman -->
<div style="margin-top: 10px;">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&search_column=<?= $search_column ?>&date=<?= $date_filter ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
                            <!-- Pagination -->
                            <?php if($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                <a href="?page=<?= ($page - 1) ?><?= !empty($search) ? '&search='.$search : '' ?><?= !empty($status_filter) ? '&status='.$status_filter : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" class="pagination-btn prev">
                                    <i class="uil uil-angle-left"></i> Previous
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
                                    Next <i class="uil uil-angle-right"></i>
                                </a>
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
                <h3>Change Order Status</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="order_status">Order Status</label>
                        <select id="order_status" name="order_status" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="order_id" id="edit_order_id">
                        <input type="hidden" name="update_status" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Cancel</button>
                        <button type="submit" class="btn primary-btn">Update Status</button>
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
                const orderId = this.getAttribute('data-id');
                const currentStatus = this.getAttribute('data-status');
                
                document.getElementById('edit_order_id').value = orderId;
                document.getElementById('order_status').value = currentStatus;
                
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