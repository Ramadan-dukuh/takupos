<?php
include 'koneksi.php';

function getSalesData($kon) {
    $data = array();
    
    // Today's sales
    $today_query = "SELECT 
                    COUNT(DISTINCT t.id) as sales, 
                    SUM(td.quantity * p.price) AS revenue
                FROM transactions t
                JOIN transaction_details td ON t.id = td.transaction_id
                JOIN products p ON td.product_variant_id = p.id
                WHERE DATE(t.created_at) = CURDATE()";
    $today_result = $kon->query($today_query);
    $today_data = $today_result->fetch_assoc();
    $data['Today'] = array(
        'sales' => $today_data['sales'] ?? 0,
        'revenue' => $today_data['revenue'] ?? 0
    );
    
    // Yesterday's sales
    $yesterday_query = "SELECT 
                        COUNT(DISTINCT t.id) as sales, 
                        SUM(td.quantity * p.price) AS revenue
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    JOIN products p ON td.product_variant_id = p.id
                    WHERE DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $yesterday_result = $kon->query($yesterday_query);
    $yesterday_data = $yesterday_result->fetch_assoc();
    $data['Yesterday'] = array(
        'sales' => $yesterday_data['sales'] ?? 0,
        'revenue' => $yesterday_data['revenue'] ?? 0
    );
    
    // Last week's sales (last 7 days)
    $last_week_query = "SELECT 
                        COUNT(DISTINCT t.id) as sales, 
                        SUM(td.quantity * p.price) AS revenue
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    JOIN products p ON td.product_variant_id = p.id
                    WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    AND t.created_at < CURDATE()";
    $last_week_result = $kon->query($last_week_query);
    $last_week_data = $last_week_result->fetch_assoc();
    $data['Last Week'] = array(
        'sales' => $last_week_data['sales'] ?? 0,
        'revenue' => $last_week_data['revenue'] ?? 0
    );
    
    // Last month's sales
    $last_month_query = "SELECT 
                        COUNT(DISTINCT t.id) as sales, 
                        SUM(td.quantity * p.price) AS revenue
                    FROM transactions t
                    JOIN transaction_details td ON t.id = td.transaction_id
                    JOIN products p ON td.product_variant_id = p.id
                    WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND t.created_at < CURDATE()";
    $last_month_result = $kon->query($last_month_query);
    $last_month_data = $last_month_result->fetch_assoc();
    $data['Last Month'] = array(
        'sales' => $last_month_data['sales'] ?? 0,
        'revenue' => $last_month_data['revenue'] ?? 0
    );
    
    return $data;
}

// Get sales data
$salesData = getSalesData($kon);

// Convert to JSON for JavaScript
$salesDataJSON = json_encode(array_map(function($key, $value) {
    return array(
        'name' => $key,
        'sales' => (int)$value['sales'],
        'revenue' => (float)$value['revenue']
    );
}, array_keys($salesData), array_values($salesData)));

// get all products
$count_query = "SELECT COUNT(*) as total FROM products";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_products = $count_row['total'];

// get all transactions
$count_query = "SELECT COUNT(*) as total FROM transactions";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_transactions = $count_row['total'];

// get all customers
$count_query = "SELECT COUNT(*) as total FROM users";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_customers = $count_row['total'];

// get total revenue
$sql = "SELECT SUM(td.quantity * p.price) AS total_revenue
        FROM transaction_details td
        JOIN products p ON td.product_variant_id = p.id";

$result = $kon->query($sql);
// Check if query was successful
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_revenue = $row['total_revenue'] ?? 0;
} else {
    $total_revenue = 0;
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script.js" defer></script>
    <title>Fashion24 - Web Management</title>
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
                <li class="menu-item active">
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
                <li class="menu-item">
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
                    <i class="uil uil-search search-icon"></i>
                    <input type="text" placeholder="Search..." />
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
                            <h3>Total Transaction</h3>
                            <p class="stat-value"><?= $total_transactions ?></p>                            
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-money-bill"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Income</h3>
                            <p class="stat-value">Rp <?= $total_pendapatan ?></p>                            
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-users-alt"></i>
                        </div>
                        <div class="stat-details">
                            <h3>New Costumer</h3>
                            <p class="stat-value"><?= $total_customers ?></p>                            
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="uil uil-cube"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Product</h3>
                            <p class="stat-value"><?= $total_products ?></p>                            
                        </div>
                    </div>
                </div>
                
             <!-- Replace the Recent Orders Table with Sales Statistics Chart -->
             <div class="content-card sales-statistics">
    <div class="card-header">
        <h3>Sales Statistics</h3>
        <a href="laporan.php" class="view-all">See Full Reports</a>
    </div>
    <div class="card-body">
        <!-- We'll insert our React component here -->
    

        <div id="sales-statistics-chart"></div>
        <h2>Grafik Penjualan & Pendapatan</h2>
    <!-- <div id="error-message" style="color: red; text-align: center; display: none;">
      Gagal memuat data. Silakan periksa koneksi database.
    </div> -->
    <canvas id="salesChart" height="100"></canvas>
    
    <!-- <div class="summary">
      <h3>Ringkasan Penjualan</h3>
      <div id="summary-data">
        <p>Memuat data...</p>
      </div> -->
    </div>
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
                    <!-- Product Management -->
                    <div class="content-card product-management">
                        <div class="card-header">
                            <h3>New Product</h3>
                            <a href="produk.php" class="view-all">Manage Product</a>
                        </div>
                        <div class="card-body">
                            <div class="product-grid">
                                <?php
                                // Ambil 6 produk terbaru
                                $query = "SELECT 
    p.*, 
    COALESCE(SUM(vp.stock), 0) AS total_stok
FROM 
    products p
LEFT JOIN 
    product_variants vp ON p.id = vp.product_id
GROUP BY 
    p.id  ORDER BY id DESC
    LIMIT 5;

                                                    ";
                                $result = $kon->query($query);
                                
                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                ?>
                                <div class="product-card">
                                        <a href="product_detail.php?id=<?= $row['id'] ?>" class="product-link">
                                    <div class="product-img">
                                        <img src="<?= !empty($row['image']) ? $row['image'] : 'img/bg.png' ?>" alt="<?= $row['nama_produk'] ?>">
                                    </div>
                                    <div class="product-info">
                                        <h4><?= $row['name'] ?></h4>
                                        <p class="product-price">Rp<?= number_format($row['price'], 0, ',', '.') ?></p>
                                        <p class="product-stock">Stock: <?= $row['total_stok'] ?></p>                                        
                                    </div>
                                    </a>
                                </div>
                                <?php
                                    }
                                } else {
                                ?>
                                <div class="no-product">
                                    <i class="uil uil-box"></i>
                                    <p>Theres no new product</p>
                                    <a href="addproduk.php" class="btn add-btn">Add Product</a>
                                </div>
                                <?php
                                }
                                ?>
                            </div>
                            <div class="add-product-btn">
                                <a href="addproduk.php" class="btn primary-btn">
                                    <i class="uil uil-plus"></i> Add New Produk
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