<?php
include "koneksi.php";

// Handle Category Deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $kategori_id = $_GET['delete'];
    
    // Check if category is used by any product
    $check_query = "SELECT COUNT(*) as total FROM produk WHERE id_kategori = $kategori_id";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Kategori tidak dapat dihapus karena masih digunakan oleh {$check_row['total']} produk.";
    } else {
        $delete_query = "DELETE FROM kategori WHERE id = $kategori_id";
        
        if ($kon->query($delete_query) === TRUE) {
            $success_message = "Kategori berhasil dihapus!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Handle Category Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_kategori'])) {
    $nama_kategori = $_POST['nama'];
    $deskripsi_kategori = $_POST['deskripsi_kategori'];
    
    // Check if category already exists
    $check_query = "SELECT COUNT(*) as total FROM kategori WHERE nama = '$nama_kategori'";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Kategori dengan nama tersebut sudah ada.";
    } else {
        $insert_query = "INSERT INTO kategori (nama) VALUES ('$nama_kategori')";
        
        if ($kon->query($insert_query) === TRUE) {
            $success_message = "Kategori baru berhasil ditambahkan!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Handle Category Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_kategori'])) {
    $kategori_id = $_POST['id'];
    $nama_kategori = $_POST['nama'];
    $deskripsi_kategori = $_POST['deskripsi_kategori'];
    
    $update_query = "UPDATE kategori SET nama = '$nama_kategori' 
                    WHERE id = $kategori_id";
    
    if ($kon->query($update_query) === TRUE) {
        $success_message = "Kategori berhasil diperbarui!";
    } else {
        $error_message = "Error: " . $kon->error;
    }
}

// Pagination setup
$limit = 10; // Items per page
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE nama LIKE '%$search%' ";
}

// Get total categories count for pagination
$count_query = "SELECT COUNT(*) as total FROM kategori $search_condition";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_categories = $count_row['total'];
$total_pages = ceil($total_categories / $limit);

// Get categories with pagination
$query = "SELECT k.*, COUNT(p.id) as product_count 
          FROM kategori k
          LEFT JOIN produk p ON k.id = p.id
          $search_condition
          GROUP BY k.id
          ORDER BY k.id DESC LIMIT $offset, $limit";
$result = $kon->query($query);
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
    <title>Manajemen Kategori - TakuPos</title>
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
                <li class="menu-item active">
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
                        <input type="text" name="search" placeholder="Cari kategori..." value="<?= htmlspecialchars($search) ?>" />
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
            
            <!-- Category Management Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Manajemen Kategori</h2>
                    <button class="btn primary-btn" id="showAddCategoryModal">
                        <i class="uil uil-plus"></i> Tambah Kategori Baru
                    </button>
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
                        <h3>Daftar Kategori</h3>
                        <div class="header-actions">
                            <span class="product-count"><?= $total_categories ?> Kategori</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="responsive-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Kategori</th>                                            
                                            <th>Jumlah Produk</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= $row['nama'] ?></td>
                                            <td><?= !empty($row['deskripsi_kategori']) ? $row['deskripsi_kategori'] : '<em>Tidak ada deskripsi</em>' ?></td>
                                            <td>
                                                <span class="badge <?= $row['product_count'] > 0 ? 'success-badge' : 'neutral-badge' ?>">
                                                    <?= $row['product_count'] ?> Produk
                                                </span>
                                            </td>
                                            <td class="action-buttons">
                                                <button class="btn edit-btn edit-category-btn" 
                                                        data-id="<?= $row['id'] ?>" 
                                                        data-name="<?= htmlspecialchars($row['nama']) ?>"                                                         
                                                    <i class="uil uil-edit"></i> Edit
                                                </button>
                                                <a href="kategori.php?delete=<?= $row['id'] ?>" class="btn delete-btn <?= $row['product_count'] > 0 ? 'disabled' : '' ?>" 
                                                   onclick="return <?= $row['product_count'] > 0 ? 'alert(\'Kategori tidak dapat dihapus karena masih digunakan oleh produk.\'); false' : 'confirm(\'Apakah Anda yakin ingin menghapus kategori ini?\')' ?>;">
                                                    <i class="uil uil-trash-alt"></i> Hapus
                                                </a>
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
                                <i class="uil uil-tag"></i>
                                <?php if(!empty($search)): ?>
                                <p>Tidak ditemukan kategori dengan kata kunci "<?= htmlspecialchars($search) ?>"</p>
                                <a href="kategori.php" class="btn secondary-btn">Tampilkan Semua Kategori</a>
                                <?php else: ?>
                                <p>Belum ada kategori ditambahkan</p>
                                <button class="btn add-btn" id="showAddCategoryModalEmpty">Tambah Kategori</button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal" id="addCategoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Kategori Baru</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="nama_kategori">Nama Kategori</label>
                        <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi_kategori">Deskripsi (Opsional)</label>
                        <textarea id="deskripsi_kategori" name="deskripsi_kategori" class="form-control"></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="add_kategori" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Batal</button>
                        <button type="submit" class="btn primary-btn">Simpan Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal" id="editCategoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Kategori</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="edit_nama_kategori">Nama Kategori</label>
                        <input type="text" id="edit_nama_kategori" name="nama_kategori" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_deskripsi_kategori">Deskripsi (Opsional)</label>
                        <textarea id="edit_deskripsi_kategori" name="deskripsi_kategori" class="form-control"></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="kategori_id" id="edit_kategori_id">
                        <input type="hidden" name="edit_kategori" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Batal</button>
                        <button type="submit" class="btn primary-btn">Perbarui Kategori</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functionality
        const addCategoryModal = document.getElementById('addCategoryModal');
        const editCategoryModal = document.getElementById('editCategoryModal');
        const showAddCategoryModal = document.getElementById('showAddCategoryModal');
        const showAddCategoryModalEmpty = document.getElementById('showAddCategoryModalEmpty');
        const closeButtons = document.querySelectorAll('.close-modal');
        const cancelButtons = document.querySelectorAll('.cancel-modal');
        const editButtons = document.querySelectorAll('.edit-category-btn');
        
        // Show add category modal
        if (showAddCategoryModal) {
            showAddCategoryModal.addEventListener('click', function() {
                addCategoryModal.style.display = 'flex';
            });
        }
        
        if (showAddCategoryModalEmpty) {
            showAddCategoryModalEmpty.addEventListener('click', function() {
                addCategoryModal.style.display = 'flex';
            });
        }
        
        // Close modals
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                addCategoryModal.style.display = 'none';
                editCategoryModal.style.display = 'none';
            });
        });
        
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                addCategoryModal.style.display = 'none';
                editCategoryModal.style.display = 'none';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addCategoryModal) {
                addCategoryModal.style.display = 'none';
            }
            if (event.target === editCategoryModal) {
                editCategoryModal.style.display = 'none';
            }
        });
        
        // Edit category
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const desc = this.getAttribute('data-desc');
                
                document.getElementById('edit_kategori_id').value = id;
                document.getElementById('edit_nama_kategori').value = name;
                document.getElementById('edit_deskripsi_kategori').value = desc;
                
                editCategoryModal.style.display = 'flex';
            });
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