<?php
include "koneksi.php";

// Handle Customer Deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $pelanggan_id = $_GET['delete'];
    
    // Check if customer has orders
    $check_query = "SELECT COUNT(*) as total FROM pesanan WHERE id_pelanggan = $pelanggan_id";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Pelanggan tidak dapat dihapus karena memiliki {$check_row['total']} pesanan.";
    } else {
        $delete_query = "DELETE FROM pelanggan WHERE id_pelanggan = $pelanggan_id";
        
        if ($kon->query($delete_query) === TRUE) {
            $success_message = "Data pelanggan berhasil dihapus!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Handle Customer Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_pelanggan'])) {
    $nama_pelanggan = $_POST['nama_pelanggan'];
    $email = $_POST['email'];
    $no_telp = $_POST['no_telp'];
    $alamat = $_POST['alamat'];
    
    // Check if email already exists
    $check_query = "SELECT COUNT(*) as total FROM pelanggan WHERE email = '$email'";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Email sudah terdaftar. Gunakan email lain.";
    } else {
        // Add new customer
        $insert_query = "INSERT INTO pelanggan (nama_pelanggan, email, no_telp, alamat, created_at) 
                        VALUES ('$nama_pelanggan', '$email', '$no_telp', '$alamat', NOW())";
        
        if ($kon->query($insert_query) === TRUE) {
            $success_message = "Data pelanggan berhasil ditambahkan!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Handle Customer Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_pelanggan'])) {
    $pelanggan_id = $_POST['pelanggan_id'];
    $nama_pelanggan = $_POST['nama_pelanggan'];
    $email = $_POST['email'];
    $no_telp = $_POST['no_telp'];
    $alamat = $_POST['alamat'];
    
    // Check if email already exists (except current customer)
    $check_query = "SELECT COUNT(*) as total FROM pelanggan WHERE email = '$email' AND id_pelanggan != $pelanggan_id";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Email sudah terdaftar. Gunakan email lain.";
    } else {
        // Update customer
        $update_query = "UPDATE pelanggan SET 
                        nama_pelanggan = '$nama_pelanggan', 
                        email = '$email', 
                        no_telp = '$no_telp', 
                        alamat = '$alamat', 
                        updated_at = NOW() 
                        WHERE id_pelanggan = $pelanggan_id";
        
        if ($kon->query($update_query) === TRUE) {
            $success_message = "Data pelanggan berhasil diperbarui!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Pagination setup
$limit = 10; // Items per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = '';

if (!empty($search)) {
    $where_clause = "WHERE nama_pelanggan LIKE '%$search%' OR email LIKE '%$search%' OR no_telp LIKE '%$search%'";
}

// Get total customers count for pagination
// $count_query = "SELECT COUNT(*) as total FROM pelanggan $where_clause";
// $count_result = $kon->query($count_query);
// $count_row = $count_result->fetch_assoc();
// $total_customers = $count_row['total'];
// $total_pages = ceil($total_customers / $limit);

// Get customers with pagination
// $query = "SELECT p.*, 
//          (SELECT COUNT(*) FROM pesanan WHERE id_pelanggan = p.id_pelanggan) as order_count,
//          (SELECT SUM(dp.quantity * dp.harga_produk) FROM pesanan o
//           JOIN detail_pesanan dp ON o.id_pesanan = dp.id_pesanan
//           WHERE o.id_pelanggan = p.id_pelanggan) as total_spend
//          FROM pelanggan p 
//          $where_clause
//          ORDER BY p.id_pelanggan DESC 
//          LIMIT $offset, $limit";
// $result = $kon->query($query);

// Get customer for edit modal
$edit_customer = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM pelanggan WHERE id_pelanggan = $edit_id";
    $edit_result = $kon->query($edit_query);
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_customer = $edit_result->fetch_assoc();
    }
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
    <title>Manajemen Pelanggan - TakuPos</title>
    <style>
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-lighter);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 16px;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .customer-name {
            font-weight: 500;
        }
        
        .customer-email {
            font-size: 12px;
            color: #777;
        }
        
        .stats-label {
            font-size: 12px;
            color: #777;
            margin-top: 3px;
        }
        
        .highlight {
            color: var(--primary-color);
            font-weight: 500;
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
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            padding: 15px 20px;
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
        }
        
        .close-modal {
            cursor: pointer;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .primary-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .secondary-btn {
            background-color: var(--light-bg);
            color: var(--dark-text);
        }
        
        .customer-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .customer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .customer-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .stat-item {
            flex: 1;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: opacity 0.3s;
        }
        
        .alert.success {
            background-color: #e9f7ef;
            color: #27ae60;
        }
        
        .alert.error {
            background-color: #ffe6e6;
            color: #ff0000;
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
                <li class="menu-item">
                    <a href="pesanan.php">
                        <i class="uil uil-shopping-cart"></i>
                        <span>Pesanan</span>
                    </a>
                </li>
                <li class="menu-item active">
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
                        <input type="text" name="search" placeholder="Cari pelanggan..." value="<?= htmlspecialchars($search) ?>" />
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
            
            <!-- Customer Management Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Manajemen Pelanggan</h2>
                    <div class="header-actions">
                        <button class="btn primary-btn" id="addCustomerBtn">
                            <i class="uil uil-plus"></i> Tambah Pelanggan
                        </button>
                        <a href="laporan.php?type=pelanggan" class="btn secondary-btn">
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
                
                <div class="content-card">
                    <div class="card-header">
                        <h3>Daftar Pelanggan</h3>
                        <div class="header-actions">
                            <span class="product-count"><?= $total_customers ?> Pelanggan</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="responsive-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Pelanggan</th>
                                            <th>Kontak</th>
                                            <th>Alamat</th>
                                            <th>Total Pesanan</th>
                                            <th>Total Belanja</th>
                                            <th>Tanggal Daftar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $row['id_pelanggan'] ?></td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-avatar">
                                                        <?= strtoupper(substr($row['nama_pelanggan'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="customer-name"><?= $row['nama_pelanggan'] ?></div>
                                                        <div class="customer-email"><?= $row['email'] ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $row['no_telp'] ?></td>
                                            <td><?= $row['alamat'] ?></td>
                                            <td>
                                                <span class="highlight"><?= $row['order_count'] ?></span>
                                                <div class="stats-label">Pesanan</div>
                                            </td>
                                            <td>
                                                <span class="highlight">
                                                    Rp <?= number_format($row['total_spend'] ?? 0, 0, ',', '.') ?>
                                                </span>
                                                <div class="stats-label">Total Belanja</div>
                                            </td>
                                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                            <td class="action-buttons">
                                                <a href="?edit=<?= $row['id_pelanggan'] ?>" class="btn edit-btn">
                                                    <i class="uil uil-edit"></i> Edit
                                                </a>
                                                <?php if($row['order_count'] == 0): ?>
                                                <a href="?delete=<?= $row['id_pelanggan'] ?>" class="btn delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini?');">
                                                    <i class="uil uil-trash"></i> Hapus
                                                </a>
                                                <?php else: ?>
                                                <a href="pesanan.php?search=<?= $row['nama_pelanggan'] ?>" class="btn view-btn">
                                                    <i class="uil uil-eye"></i> Lihat Pesanan
                                                </a>
                                                <?php endif; ?>
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
                            <div class="no-data">
                                <i class="uil uil-users-alt"></i>
                                <?php if(!empty($search)): ?>
                                <p>Tidak ditemukan pelanggan yang sesuai dengan pencarian</p>
                                <a href="pelanggan.php" class="btn secondary-btn">Reset Pencarian</a>
                                <?php else: ?>
                                <p>Belum ada data pelanggan</p>
                                <button class="btn primary-btn" id="addCustomerBtnEmpty">
                                    <i class="uil uil-plus"></i> Tambah Pelanggan Pertama
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Customer Modal -->
    <div class="modal" id="addCustomerModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Pelanggan Baru</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="nama_pelanggan">Nama Pelanggan</label>
                        <input type="text" id="nama_pelanggan" name="nama_pelanggan" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="no_telp">No. Telepon</label>
                        <input type="text" id="no_telp" name="no_telp" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="add_pelanggan" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Batal</button>
                        <button type="submit" class="btn primary-btn">Tambah Pelanggan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Customer Modal -->
    <?php if($edit_customer): ?>
    <div class="modal" id="editCustomerModal" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Data Pelanggan</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="edit_nama_pelanggan">Nama Pelanggan</label>
                        <input type="text" id="edit_nama_pelanggan" name="nama_pelanggan" class="form-control" value="<?= htmlspecialchars($edit_customer['nama_pelanggan']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" value="<?= htmlspecialchars($edit_customer['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_no_telp">No. Telepon</label>
                        <input type="text" id="edit_no_telp" name="no_telp" class="form-control" value="<?= htmlspecialchars($edit_customer['no_telp']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_alamat">Alamat</label>
                        <textarea id="edit_alamat" name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($edit_customer['alamat']) ?></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="pelanggan_id" value="<?= $edit_customer['id_pelanggan'] ?>">
                        <input type="hidden" name="edit_pelanggan" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Batal</button>
                        <button type="submit" class="btn primary-btn">Perbarui Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // Modal functionality
        const addCustomerModal = document.getElementById('addCustomerModal');
        const editCustomerModal = document.getElementById('editCustomerModal');
        const addCustomerBtn = document.getElementById('addCustomerBtn');
        const addCustomerBtnEmpty = document.getElementById('addCustomerBtnEmpty');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        const cancelButtons = document.querySelectorAll('.cancel-modal');
        
        // Show add customer modal
        if (addCustomerBtn) {
            addCustomerBtn.addEventListener('click', function() {
                addCustomerModal.style.display = 'flex';
            });
        }
        
        if (addCustomerBtnEmpty) {
            addCustomerBtnEmpty.addEventListener('click', function() {
                addCustomerModal.style.display = 'flex';
            });
        }
        
        // Close modals
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (addCustomerModal) addCustomerModal.style.display = 'none';
                if (editCustomerModal) editCustomerModal.style.display = 'none';
                // Redirect to remove edit parameter
                if (window.location.href.includes('edit=')) {
                    window.location.href = 'pelanggan.php' + (window.location.href.includes('search=') ? 
                    '?search=<?= urlencode($search) ?>' : '');
                }
            });
        });
        
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (addCustomerModal) addCustomerModal.style.display = 'none';
                if (editCustomerModal) editCustomerModal.style.display = 'none';
                // Redirect to remove edit parameter
                if (window.location.href.includes('edit=')) {
                    window.location.href = 'pelanggan.php' + (window.location.href.includes('search=') ? 
                    '?search=<?= urlencode($search) ?>' : '');
                }
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addCustomerModal) {
                addCustomerModal.style.display = 'none';
            }
            if (event.target === editCustomerModal) {
                editCustomerModal.style.display = 'none';
                // Redirect to remove edit parameter
                if (window.location.href.includes('edit=')) {
                    window.location.href = 'pelanggan.php' + (window.location.href.includes('search=') ? 
                    '?search=<?= urlencode($search) ?>' : '');
                }
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