<?php
include "koneksi.php";

// Set default date range to current month
$today = date('Y-m-d');
$first_day_of_month = date('Y-m-01');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $first_day_of_month;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $today;
$report_type = isset($_GET['type']) ? $_GET['type'] : 'sales';

// Export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$report_type.'_report_'.date('Y-m-d').'.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Different headers and data based on report type
    if ($report_type == 'sales') {
        fputcsv($output, array('ID Pesanan', 'Tanggal', 'Pelanggan', 'Total', 'Metode Pembayaran', 'Status'));
        
        $query = "SELECT p.id_pesanan, p.tanggal_pesanan, pl.nama_pelanggan, 
                 SUM(dp.quantity * dp.harga_produk) as total,
                 p.metode_pembayaran, p.status_pesanan
                 FROM pesanan p
                 JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                 JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                 WHERE p.tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
                 GROUP BY p.id_pesanan
                 ORDER BY p.tanggal_pesanan DESC";
                 
        $result = $kon->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, array(
                    $row['id_pesanan'],
                    date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])),
                    $row['nama_pelanggan'],
                    $row['total'],
                    $row['metode_pembayaran'],
                    $row['status_pesanan']
                ));
            }
        }
    } elseif ($report_type == 'products') {
        fputcsv($output, array('ID Produk', 'Nama Produk', 'Kategori', 'Harga', 'Stok', 'Total Terjual', 'Total Pendapatan'));
        
        $query = "SELECT p.id_produk, p.nama_produk, k.nama_kategori, p.harga, p.stok,
                 SUM(IFNULL(dp.quantity, 0)) as total_sold,
                 SUM(IFNULL(dp.quantity * dp.harga_produk, 0)) as total_revenue 
                 FROM produk p
                 LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
                 LEFT JOIN detail_pesanan dp ON p.id_produk = dp.id_produk
                 LEFT JOIN pesanan ps ON dp.id_pesanan = ps.id_pesanan AND ps.tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
                 GROUP BY p.id_produk
                 ORDER BY total_sold DESC";
                 
        $result = $kon->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, array(
                    $row['id_produk'],
                    $row['nama_produk'],
                    $row['nama_kategori'],
                    $row['harga'],
                    $row['stok'],
                    $row['total_sold'] ?: '0',
                    $row['total_revenue'] ?: '0'
                ));
            }
        }
    } elseif ($report_type == 'pelanggan') {
        fputcsv($output, array('ID Pelanggan', 'Nama', 'Email', 'No. Telepon', 'Alamat', 'Tanggal Daftar', 'Total Pesanan', 'Total Belanja'));
        
        $query = "SELECT p.*, 
                 COUNT(DISTINCT ps.id_pesanan) as order_count,
                 SUM(IFNULL(dp.quantity * dp.harga_produk, 0)) as total_spend
                 FROM pelanggan p
                 LEFT JOIN pesanan ps ON p.id_pelanggan = ps.id_pelanggan AND ps.tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
                 LEFT JOIN detail_pesanan dp ON ps.id_pesanan = dp.id_pesanan
                 GROUP BY p.id_pelanggan
                 ORDER BY total_spend DESC";
                 
        $result = $kon->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, array(
                    $row['id_pelanggan'],
                    $row['nama_pelanggan'],
                    $row['email'],
                    $row['no_telp'],
                    $row['alamat'],
                    date('d/m/Y', strtotime($row['created_at'])),
                    $row['order_count'] ?: '0',
                    $row['total_spend'] ?: '0'
                ));
            }
        }
    }
    
    fclose($output);
    exit();
}

// Get summary data
$summary = array();

// Total sales for the period
// $sales_query = "SELECT COUNT(id_pesanan) as order_count, 
//                SUM((SELECT SUM(quantity * harga_produk) FROM detail_pesanan WHERE id_pesanan = p.id_pesanan)) as total_revenue,
//                COUNT(DISTINCT id_pelanggan) as customer_count
//                FROM pesanan p 
//                WHERE tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'";
               
// $sales_result = $kon->query($sales_query);
// $sales_data = $sales_result->fetch_assoc();

// $summary['order_count'] = $sales_data['order_count'] ?: 0;
// $summary['total_revenue'] = $sales_data['total_revenue'] ?: 0;
// $summary['customer_count'] = $sales_data['customer_count'] ?: 0;

// Average order value
// $summary['average_order'] = $summary['order_count'] > 0 ? 
//                            round($summary['total_revenue'] / $summary['order_count']) : 0;

// Top selling products
// $top_products_query = "SELECT p.id_produk, p.nama_produk, SUM(dp.quantity) as total_sold
//                       FROM detail_pesanan dp
//                       JOIN produk p ON dp.id_produk = p.id_produk
//                       JOIN pesanan ps ON dp.id_pesanan = ps.id_pesanan
//                       WHERE ps.tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
//                       GROUP BY p.id_produk
//                       ORDER BY total_sold DESC
//                       LIMIT 5";
                      
// $top_products_result = $kon->query($top_products_query);
// $top_products = array();

// if ($top_products_result && $top_products_result->num_rows > 0) {
//     while ($row = $top_products_result->fetch_assoc()) {
//         $top_products[] = $row;
//     }
// }

// Sales by day
// $daily_sales_query = "SELECT DATE(tanggal_pesanan) as date, 
//                      COUNT(id_pesanan) as order_count,
//                      SUM((SELECT SUM(quantity * harga_produk) FROM detail_pesanan WHERE id_pesanan = p.id_pesanan)) as daily_revenue
//                      FROM pesanan p
//                      WHERE tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
//                      GROUP BY DATE(tanggal_pesanan)
//                      ORDER BY date";
                     
// $daily_sales_result = $kon->query($daily_sales_query);
// $daily_sales = array();

// if ($daily_sales_result && $daily_sales_result->num_rows > 0) {
//     while ($row = $daily_sales_result->fetch_assoc()) {
//         $daily_sales[] = $row;
//     }
// }

// Get report data based on type
// $report_data = array();

// if ($report_type == 'sales') {
//     $query = "SELECT p.id_pesanan, p.tanggal_pesanan, pl.nama_pelanggan, 
//              SUM(dp.quantity * dp.harga_produk) as total,
//              p.metode_pembayaran, p.status_pesanan
//              FROM pesanan p
//              JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
//              JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
//              WHERE p.tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
//              GROUP BY p.id_pesanan
//              ORDER BY p.tanggal_pesanan DESC
//              LIMIT 100";
// } elseif ($report_type == 'products') {
//     $query = "SELECT p.id_produk, p.nama_produk, k.nama_kategori, p.harga, p.stok,
//              SUM(IFNULL(dp.quantity, 0)) as total_sold,
//              SUM(IFNULL(dp.quantity * dp.harga_produk, 0)) as total_revenue 
//              FROM produk p
//              LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
//              LEFT JOIN detail_pesanan dp ON p.id_produk = dp.id_produk
//              LEFT JOIN pesanan ps ON dp.id_pesanan = ps.id_pesanan AND ps.tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
//              GROUP BY p.id_produk
//              ORDER BY total_sold DESC
//              LIMIT 100";
// } elseif ($report_type == 'pelanggan') {
//     $query = "SELECT p.*, 
//              COUNT(DISTINCT ps.id_pesanan) as order_count,
//              SUM(IFNULL(dp.quantity * dp.harga_produk, 0)) as total_spend
//              FROM pelanggan p
//              LEFT JOIN pesanan ps ON p.id_pelanggan = ps.id_pelanggan AND ps.tanggal_pesanan BETWEEN '$start_date' AND '$end_date 23:59:59'
//              LEFT JOIN detail_pesanan dp ON ps.id_pesanan = dp.id_pesanan
//              GROUP BY p.id_pelanggan
//              ORDER BY total_spend DESC
//              LIMIT 100";
// }

// $result = $kon->query($query);
// if ($result && $result->num_rows > 0) {
//     while ($row = $result->fetch_assoc()) {
//         $report_data[] = $row;
//     }
// }
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
    <title>Laporan - Fashion24</title>
    <style>
        .chart-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px var(--shadow-color);
            height: 350px;
        }
        
        .sales-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px var(--shadow-color);
            display: flex;
            flex-direction: column;
        }
        
        .summary-card .label {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .summary-card .value {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .summary-card .change {
            margin-top: 5px;
            font-size: 12px;
            display: flex;
            align-items: center;
        }
        
        .summary-card .change.positive {
            color: var(--success-color);
        }
        
        .summary-card .change.negative {
            color: var(--danger-color);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-weight: 500;
            white-space: nowrap;
        }
        
        .filter-group input[type="date"] {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            width: 160px;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            width: 160px;
        }
        
        .export-btn {
            margin-left: auto;
        }
        
        .tab-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab-item {
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 1px solid var(--border-color);
            background-color: var(--background-color);
        }
        
        .tab-item:hover {
            background-color: var(--border-color);
        }
        
        .tab-item.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .data-summary {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }
        
        .top-products {
            margin-top: 10px;
        }
        
        .product-rank {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .product-rank:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .rank-number {
            width: 24px;
            height: 24px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            margin-right: 10px;
            font-size: 12px;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 500;
        }
        
        .product-sold {
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Responsive date picker for mobile */
        @media screen and (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-group input[type="date"],
            .filter-group select {
                width: 100%;
            }
            
            .export-btn {
                margin-left: 0;
                width: 100%;
            }
            
            .tab-container {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li class="menu-item active">
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
            
            <!-- Report Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Laporan</h2>
                </div>
                
                <!-- Report Filters -->
                <form action="" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Periode:</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" />
                        <span>hingga</span>
                        <input type="date" name="end_date" value="<?= $end_date ?>" />
                    </div>
                    
                    <div class="filter-group">
                        <label>Jenis Laporan:</label>
                        <select name="type">
                            <option value="sales" <?= $report_type == 'sales' ? 'selected' : '' ?>>Penjualan</option>
                            <option value="products" <?= $report_type == 'products' ? 'selected' : '' ?>>Produk</option>
                            <option value="pelanggan" <?= $report_type == 'pelanggan' ? 'selected' : '' ?>>Pelanggan</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn primary-btn">
                        <i class="uil uil-filter"></i> Terapkan Filter
                    </button>
                    
                    <a href="?export=csv&type=<?= $report_type ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn secondary-btn export-btn">
                        <i class="uil uil-download-alt"></i> Export CSV
                    </a>
                </form>
                
                <!-- Sales Summary -->
                <div class="sales-summary">
                    <div class="summary-card">
                        <div class="label">Total Pendapatan</div>
                        <div class="value">Rp <?= number_format($summary['total_revenue'], 0, ',', '.') ?></div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="label">Jumlah Pesanan</div>
                        <div class="value"><?= $summary['order_count'] ?></div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="label">Rata-rata Order</div>
                        <div class="value">Rp <?= number_format($summary['average_order'], 0, ',', '.') ?></div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="label">Pelanggan Aktif</div>
                        <div class="value"><?= $summary['customer_count'] ?></div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="content-grid">
                    <!-- Revenue Chart -->
                    <div class="chart-container">
                        <h3>Pendapatan</h3>
                        <canvas id="revenueChart"></canvas>
                    </div>
                    
                    <!-- Product Performance -->
                    <div class="data-summary">
                        <h3>Produk Terlaris</h3>
                        <div class="top-products">
                            <?php if (count($top_products) > 0): ?>
                                <?php $rank = 1; foreach ($top_products as $product): ?>
                                <div class="product-rank">
                                    <div class="rank-number"><?= $rank ?></div>
                                    <div class="product-info">
                                        <div class="product-name"><?= $product['nama_produk'] ?></div>
                                        <div class="product-sold"><?= $product['total_sold'] ?> unit terjual</div>
                                    </div>
                                </div>
                                <?php $rank++; endforeach; ?>
                            <?php else: ?>
                                <div class="no-data">
                                    <p>Belum ada data produk terjual pada periode ini</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Report Data -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>
                            <?php 
                            if ($report_type == 'sales') echo 'Data Penjualan';
                            elseif ($report_type == 'products') echo 'Performa Produk';
                            elseif ($report_type == 'pelanggan') echo 'Performa Pelanggan';
                            ?>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <?php if (count($report_data) > 0): ?>
                            <div class="responsive-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <?php if ($report_type == 'sales'): ?>
                                                <th>ID Pesanan</th>
                                                <th>Tanggal</th>
                                                <th>Pelanggan</th>
                                                <th>Total</th>
                                                <th>Metode Pembayaran</th>
                                                <th>Status</th>
                                            <?php elseif ($report_type == 'products'): ?>
                                                <th>ID</th>
                                                <th>Produk</th>
                                                <th>Kategori</th>
                                                <th>Harga</th>
                                                <th>Stok</th>
                                                <th>Total Terjual</th>
                                                <th>Total Pendapatan</th>
                                            <?php elseif ($report_type == 'pelanggan'): ?>
                                                <th>ID</th>
                                                <th>Pelanggan</th>
                                                <th>Kontak</th>
                                                <th>Tanggal Daftar</th>
                                                <th>Total Pesanan</th>
                                                <th>Total Belanja</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $item): ?>
                                            <tr>
                                                <?php if ($report_type == 'sales'): ?>
                                                    <td>#<?= $item['id_pesanan'] ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($item['tanggal_pesanan'])) ?></td>
                                                    <td><?= $item['nama_pelanggan'] ?></td>
                                                    <td>Rp <?= number_format($item['total'], 0, ',', '.') ?></td>
                                                    <td><?= $item['metode_pembayaran'] ?></td>
                                                    <td>
                                                        <span class="status <?= strtolower($item['status_pesanan']) ?>">
                                                            <?= $item['status_pesanan'] ?>
                                                        </span>
                                                    </td>
                                                <?php elseif ($report_type == 'products'): ?>
                                                    <td>#<?= $item['id_produk'] ?></td>
                                                    <td><?= $item['nama_produk'] ?></td>
                                                    <td><?= $item['nama_kategori'] ?></td>
                                                    <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                                    <td><?= $item['stok'] ?></td>
                                                    <td><?= $item['total_sold'] ?: '0' ?></td>
                                                    <td>Rp <?= number_format($item['total_revenue'] ?: 0, 0, ',', '.') ?></td>
                                                <?php elseif ($report_type == 'pelanggan'): ?>
                                                    <td>#<?= $item['id_pelanggan'] ?></td>
                                                    <td>
                                                        <div class="customer-info">
                                                            <div class="customer-avatar">
                                                                <?= strtoupper(substr($item['nama_pelanggan'], 0, 1)) ?>
                                                            </div>
                                                            <div>
                                                                <div class="customer-name"><?= $item['nama_pelanggan'] ?></div>
                                                                <div class="customer-email"><?= $item['email'] ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= $item['no_telp'] ?></td>
                                                    <td><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
                                                    <td><?= $item['order_count'] ?: '0' ?></td>
                                                    <td>Rp <?= number_format($item['total_spend'] ?: 0, 0, ',', '.') ?></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="uil uil-chart-down"></i>
                                <p>Tidak ada data untuk ditampilkan pada periode ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Revenue Chart
        const revenueChartCtx = document.getElementById('revenueChart').getContext('2d');
        
        const dailySalesData = <?= json_encode($daily_sales) ?>;
        const labels = dailySalesData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        });
        
        // const revenues = dailySalesData.map(item => parseInt(item.daily_revenue));
        // const orderCounts = dailySalesData.