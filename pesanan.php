<?php
include "koneksi.php";

// Handle Order Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $order_status = $kon->real_escape_string($_POST['order_status']);
    
    $update_query = "UPDATE transactions SET status = ?, 
                    updated_at = NOW() WHERE id = ?";
    
    $stmt = $kon->prepare($update_query);
    $stmt->bind_param("si", $order_status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status successfully updated!";
    } else {
        $error_message = "Error: " . $kon->error;
    }
    $stmt->close();
}

// Pagination setup
$limit = 10; // Items per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter functionality
$status_filter = isset($_GET['status']) ? $kon->real_escape_string($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? $kon->real_escape_string($_GET['date']) : '';
$search = isset($_GET['search']) ? $kon->real_escape_string($_GET['search']) : '';

// Build query conditions
$conditions = [];
$params = [];
$types = "";

if (!empty($status_filter)) {
    $conditions[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_filter)) {
    if ($date_filter == 'today') {
        $conditions[] = "DATE(t.created_at) = CURDATE()";
    } elseif ($date_filter == 'yesterday') {
        $conditions[] = "DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($date_filter == 'week') {
        $conditions[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_filter == 'month') {
        $conditions[] = "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
}

if (!empty($search)) {
    $conditions[] = "(t.id LIKE ? OR u.username LIKE ? OR t.payment_type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get status counts for filter badges
// $status_counts = [];
// $status_query = "SELECT status, COUNT(*) as count FROM transactions GROUP BY status";
// $status_result = $kon->query($status_query);
// if ($status_result) {
//     while ($row = $status_result->fetch_assoc()) {
//         $status_counts[$row['status']] = $row['count'];
//     }
// }

// Get total orders count for pagination
$count_query = "SELECT COUNT(*) as total FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                $where_clause";

if (!empty($params)) {
    $stmt = $kon->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $stmt->close();
} else {
    $count_result = $kon->query($count_query);
    $count_row = $count_result->fetch_assoc();
}

$total_orders = $count_row['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders with pagination
// $query = "SELECT 
//     t.*, 
//     u.username as customer_name,
//     u.id as user_id,
//     (
//         SELECT SUM(td.quantity * pv.price)
//         FROM transaction_details td
//         JOIN product_variants pv ON td.product_variant_id = pv.id
//         WHERE td.transaction_id = t.id
//     ) AS total_amount
// FROM transactions t
// LEFT JOIN users u ON t.user_id = u.id
// $where_clause
// ORDER BY t.created_at DESC
// LIMIT ?, ?";

// $params[] = $offset;
// $params[] = $limit;
// $types .= "ii";

// $stmt = $kon->prepare($query);
// $stmt->bind_param($types, ...$params);
// $stmt->execute();
// $result = $stmt->get_result();

// Get total orders count
$total_count_query = "SELECT COUNT(*) as count FROM transaction_details";
$total_count_result = $kon->query($total_count_query);
$total_count_row = $total_count_result->fetch_assoc();
$total_count = $total_count_row['count'];
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
                    <a href="pelanggan.php">
                        <i class="uil uil-users-alt"></i>
                        <span>Customers</span>
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
                        <a href="reports.php?type=orders" class="btn secondary-btn">
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
                
                <div class="filter-tabs">
                    <a href="orders.php" class="filter-tab <?= empty($status_filter) ? 'active' : '' ?>">
                        All <span class="count"><?= $total_count ?></span>
                    </a>
                    <a href="?status=pending<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">
                        <i class="uil uil-clock"></i> Pending 
                        <span class="count"><?= isset($status_counts['pending']) ? $status_counts['pending'] : 0 ?></span>
                    </a>
                    <a href="?status=processing<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'processing' ? 'active' : '' ?>">
                        <i class="uil uil-process"></i> Processing 
                        <span class="count"><?= isset($status_counts['processing']) ? $status_counts['processing'] : 0 ?></span>
                    </a>
                    <a href="?status=shipped<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'shipped' ? 'active' : '' ?>">
                        <i class="uil uil-truck"></i> Shipped 
                        <span class="count"><?= isset($status_counts['shipped']) ? $status_counts['shipped'] : 0 ?></span>
                    </a>
                    <a href="?status=delivered<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'delivered' ? 'active' : '' ?>">
                        <i class="uil uil-check-circle"></i> Delivered 
                        <span class="count"><?= isset($status_counts['delivered']) ? $status_counts['delivered'] : 0 ?></span>
                    </a>
                    <a href="?status=cancelled<?= !empty($search) ? '&search='.$search : '' ?><?= !empty($date_filter) ? '&date='.$date_filter : '' ?>" 
                       class="filter-tab <?= $status_filter === 'cancelled' ? 'active' : '' ?>">
                        <i class="uil uil-times-circle"></i> Cancelled 
                        <span class="count"><?= isset($status_counts['cancelled']) ? $status_counts['cancelled'] : 0 ?></span>
                    </a>
                </div>
                
                <div class="date-filters">
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=today" 
                       class="date-filter <?= $date_filter === 'today' ? 'active' : '' ?>">
                        Today
                    </a>
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=yesterday" 
                       class="date-filter <?= $date_filter === 'yesterday' ? 'active' : '' ?>">
                        Yesterday
                    </a>
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=week" 
                       class="date-filter <?= $date_filter === 'week' ? 'active' : '' ?>">
                        Last 7 Days
                    </a>
                    <a href="?<?= !empty($status_filter) ? 'status='.$status_filter.'&' : '' ?><?= !empty($search) ? 'search='.$search.'&' : '' ?>date=month" 
                       class="date-filter <?= $date_filter === 'month' ? 'active' : '' ?>">
                        Last 30 Days
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
                        <h3>Order List</h3>
                        <div class="header-actions">
                            <span class="product-count"><?= $total_orders ?> Orders</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="responsive-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $row['id'] ?></td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-avatar">
                                                        <?= strtoupper(substr($row['customer_name'] ?? 'U', 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div><?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?></div>
                                                        <div class="text-muted small"><?= htmlspecialchars($row['user_id']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="price">
                                                    $ <?= number_format($row['total_amount'] ?? 0, 2, '.', ',') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $row['status'] ?? 'pending';
                                                $status_icon = '';
                                                switch($status) {
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
                                                <span class="status-badge <?= $status ?>">
                                                    <?= $status_icon ?>
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                            <td class="action-buttons">
                                                <a href="order_details.php?id=<?= $row['id'] ?>" class="btn view-btn">
                                                    <i class="uil uil-eye"></i> Details
                                                </a>
                                                <button class="btn edit-btn change-status-btn" data-id="<?= $row['id'] ?>" data-status="<?= $status ?>">
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
                            
                        <?php else: ?>
                            <div class="no-data">
                                <i class="uil uil-shopping-cart"></i>
                                <?php if(!empty($search) || !empty($status_filter) || !empty($date_filter)): ?>
                                <p>No orders found matching your filters</p>
                                <a href="orders.php" class="btn secondary-btn">Reset Filters</a>
                                <?php else: ?>
                                <p>No orders found</p>
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