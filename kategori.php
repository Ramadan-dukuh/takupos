<?php
include "koneksi.php";

// Handle Category Deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $kategori_id = $_GET['delete'];
    
    // Check if category is used by any product
    $check_query = "SELECT COUNT(*) as total FROM products WHERE category_id = $kategori_id";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Category cannot be deleted because it is still used by {$check_row['total']} products.";
    } else {
        $delete_query = "DELETE FROM categories WHERE id = $kategori_id";
        
        if ($kon->query($delete_query) === TRUE) {
            $success_message = "Category successfully deleted!";
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
    $check_query = "SELECT COUNT(*) as total FROM categories WHERE name = '$nama_kategori'";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Category with this name already exists.";
    } else {
        $insert_query = "INSERT INTO categories (name) VALUES ('$nama_kategori')";
        
        if ($kon->query($insert_query) === TRUE) {
            $success_message = "New category successfully added!";
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
    
    $update_query = "UPDATE categories SET name = '$nama_kategori' 
                    WHERE id = $kategori_id";
    
    if ($kon->query($update_query) === TRUE) {
        $success_message = "Category successfully updated!";
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
    $search_condition = "WHERE name LIKE '%$search%' ";
}

// Get total categories count for pagination
$count_query = "SELECT COUNT(*) as total FROM categories $search_condition";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_categories = $count_row['total'];
$total_pages = ceil($total_categories / $limit);

// Get categories with pagination
$query = "SELECT 
    c.*,
    (
        SELECT COUNT(*) 
        FROM products p 
        WHERE p.category_id = c.id
    ) AS product_count,
    (
        SELECT COALESCE(SUM(pv.stock), 0)
        FROM products p
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        WHERE p.category_id = c.id
    ) AS total_stock
FROM 
    categories c
$search_condition
ORDER BY 
    c.id ASC
LIMIT 
    $offset, $limit
";
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
    <title>Category Management - Fashion24</title>
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
                <li class="menu-item active">
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
                        <input type="text" name="search" placeholder="Search categories..." value="<?= htmlspecialchars($search) ?>" />
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
                    <h2 class="page-title">Category Management</h2>
                    <button class="btn primary-btn" id="showAddCategoryModal">
                        <i class="uil uil-plus"></i> Add New Category
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
                        <h3>Category List</h3>
                        <div class="header-actions">
                            <span class="product-count"><?= $total_categories ?> Categories</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="responsive-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Category Name</th> 
                                            <th>Description</th>                                           
                                            <th>Product Count</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= $row['name'] ?></td>
                                            <td><?= !empty($row['description']) ? $row['description'] : '<em>No description</em>' ?></td>
                                            <td><?= $row['total_stock'] ?></td>                                            
                                            <td class="action-buttons">
                                                <button class="btn edit-btn edit-category-btn" 
                                                        data-id="<?= $row['id'] ?>" 
                                                        data-name="<?= htmlspecialchars($row['name']) ?>"                                                         
                                                    <i class="uil uil-edit"></i> Edit
                                                </button>
                                                <a href="kategori.php?delete=<?= $row['id'] ?>" class="btn delete-btn <?= $row['total_stock'] > 0 ? 'disabled' : '' ?>" 
                                                   onclick="return <?= $row['total_stock'] > 0 ? 'alert(\'Category cannot be deleted because it is still used by products.\'); false' : 'confirm(\'Are you sure you want to delete this category?\')' ?>;">
                                                    <i class="uil uil-trash-alt"></i> Delete
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
                                    <i class="uil uil-angle-left"></i> Previous
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
                                    Next <i class="uil uil-angle-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="no-data">
                                <i class="uil uil-tag"></i>
                                <?php if(!empty($search)): ?>
                                <p>No categories found with keyword "<?= htmlspecialchars($search) ?>"</p>
                                <a href="kategori.php" class="btn secondary-btn">Show All Categories</a>
                                <?php else: ?>
                                <p>No categories added yet</p>
                                <button class="btn add-btn" id="showAddCategoryModalEmpty">Add Category</button>
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
                <h3>Add New Category</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="nama_kategori">Category Name</label>
                        <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi_kategori">Description (Optional)</label>
                        <textarea id="deskripsi_kategori" name="deskripsi_kategori" class="form-control"></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="add_kategori" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Cancel</button>
                        <button type="submit" class="btn primary-btn">Save Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal" id="editCategoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Category</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="edit_nama_kategori">Category Name</label>
                        <input type="text" id="edit_nama_kategori" name="nama_kategori" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_deskripsi_kategori">Description (Optional)</label>
                        <textarea id="edit_deskripsi_kategori" name="deskripsi_kategori" class="form-control"></textarea>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="kategori_id" id="edit_kategori_id">
                        <input type="hidden" name="edit_kategori" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Cancel</button>
                        <button type="submit" class="btn primary-btn">Update Category</button>
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
                addCategoryModal.style.zIndex = '100';
            });
        }
        
        if (showAddCategoryModalEmpty) {
            showAddCategoryModalEmpty.addEventListener('click', function() {
                addCategoryModal.style.display = 'flex';
                addCategoryModal.style.zIndex = '100';
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