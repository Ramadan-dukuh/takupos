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
            width: 100%;
            margin: auto;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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

        .summary {
      margin-top: 30px;
      background: #f9f9f9;
      padding: 15px;
      border-radius: 5px;
      border-left: 4px solid #3498db;
    }
    .summary h3 {
      margin-top: 0;
      color: #333;
    }
    .data-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .data-label {
      font-weight: bold;
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
                    <a href="event.php">
                        <i class="uil uil-calendar-alt"></i>
                        <span>Events</span>
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
                
                <!-- Charts -->
                <div class="content-grid">
                    <!-- Revenue Chart -->
                    <div class="chart-container">
                    <h2>Grafik Penjualan & Pendapatan</h2>
    <div id="error-message" style="color: red; text-align: center; display: none;">
      Gagal memuat data. Silakan periksa koneksi database.
    </div>
    <canvas id="salesChart" height="100"></canvas>
    
    <div class="summary">
      <h3>Ringkasan Penjualan</h3>
      <div id="summary-data">
        <p>Memuat data...</p>
      </div>
    </div>
                    <script>
    // Fungsi untuk memformat angka ke format rupiah
    function formatRupiah(angka) {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(angka);
    }
    
    // Data statis untuk testing jika API gagal
    const fallbackData = [
      {name: 'Hari Ini', sales: 10, revenue: 1500000},
      {name: 'Kemarin', sales: 8, revenue: 1200000},
      {name: 'Minggu Lalu', sales: 45, revenue: 6700000},
      {name: 'Bulan Lalu', sales: 180, revenue: 25000000},
      {name: 'Total', sales: 243, revenue: 34400000}
    ];
    
    function renderChart(data) {
      const labels = data.map(d => d.name);
      const sales = data.map(d => d.sales);
      const revenue = data.map(d => d.revenue);
      
      // Render chart
      const ctx = document.getElementById('salesChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Jumlah Transaksi',
              data: sales,
              backgroundColor: 'rgba(54, 162, 235, 0.6)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1,
              yAxisID: 'y'
            },
            {
              label: 'Pendapatan (Rp)',
              data: revenue,
              backgroundColor: 'rgba(255, 206, 86, 0.6)',
              borderColor: 'rgba(255, 206, 86, 1)',
              borderWidth: 1,
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              position: 'left',
              title: {
                display: true,
                text: 'Jumlah Transaksi'
              }
            },
            y1: {
              beginAtZero: true,
              position: 'right',
              title: {
                display: true,
                text: 'Pendapatan (Rp)'
              },
              grid: {
                drawOnChartArea: false
              }
            }
          }
        }
      });
      
      // Render summary
      const summaryDiv = document.getElementById('summary-data');
      let summaryHTML = '';
      
      // Find total data
      const totalData = data.find(item => item.name === 'Total') || 
                         {name: 'Total', sales: 0, revenue: 0};
      
      summaryHTML += `
        <div class="data-row">
          <span class="data-label">Total Transaksi:</span>
          <span>${totalData.sales}</span>
        </div>
        <div class="data-row">
          <span class="data-label">Total Pendapatan:</span>
          <span>${formatRupiah(totalData.revenue)}</span>
        </div>
      `;
      
      // Tampilkan juga detail per periode
      data.forEach(item => {
        if (item.name !== 'Total') {
          summaryHTML += `
            <div class="data-row">
              <span class="data-label">${item.name}:</span>
              <span>${item.sales} transaksi (${formatRupiah(item.revenue)})</span>
            </div>
          `;
        }
      });
      
      summaryDiv.innerHTML = summaryHTML;
    }
    
    // Ambil data dari server
    fetch('sales_data.php')
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data && data.length > 0) {
          renderChart(data);
        } else {
          throw new Error('Empty data received');
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        document.getElementById('error-message').style.display = 'block';
        // Gunakan data fallback untuk testing
        renderChart(fallbackData);
      });
  </script>

              
        </main>

    
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