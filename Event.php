<?php
include "koneksi.php";

// Handle Event Deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $event_id = $_GET['delete'];
    
    // Check if event has associated products
    $check_query = "SELECT COUNT(*) as total FROM event_product WHERE event_id = $event_id";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Event tidak dapat dihapus karena memiliki {$check_row['total']} produk terkait.";
    } else {
        $delete_query = "DELETE FROM events WHERE id = $event_id";
        
        if ($kon->query($delete_query) === TRUE) {
            $success_message = "Data event berhasil dihapus!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Handle Event Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $discount_percentage = $_POST['discount_percentage'];
    
    // Check if event with same name already exists
    $check_query = "SELECT COUNT(*) as total FROM events WHERE name = '$name'";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Nama event sudah ada. Gunakan nama lain.";
    } else {
        // Add new event
        $insert_query = "INSERT INTO events (name, description, start_date, end_date, discount_percentage, created_at, updated_at) 
                        VALUES ('$name', '$description', '$start_date', '$end_date', $discount_percentage, NOW(), NOW())";
        
        if ($kon->query($insert_query) === TRUE) {
            $success_message = "Data event berhasil ditambahkan!";
        } else {
            $error_message = "Error: " . $kon->error;
        }
    }
}

// Handle Event Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_event'])) {
    $event_id = $_POST['event_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $discount_percentage = $_POST['discount_percentage'];
    
    // Check if name already exists (except current event)
    $check_query = "SELECT COUNT(*) as total FROM events WHERE name = '$name' AND id != $event_id";
    $check_result = $kon->query($check_query);
    $check_row = $check_result->fetch_assoc();
    
    if ($check_row['total'] > 0) {
        $error_message = "Nama event sudah ada. Gunakan nama lain.";
    } else {
        // Update event
        $update_query = "UPDATE events SET 
                        name = '$name', 
                        description = '$description', 
                        start_date = '$start_date', 
                        end_date = '$end_date', 
                        discount_percentage = $discount_percentage, 
                        updated_at = NOW() 
                        WHERE id = $event_id";
        
        if ($kon->query($update_query) === TRUE) {
            $success_message = "Data event berhasil diperbarui!";
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
    $where_clause = "WHERE name LIKE '%$search%' OR description LIKE '%$search%'";
}

// Get total events count for pagination
$count_query = "SELECT COUNT(*) as total FROM events $where_clause";
$count_result = $kon->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_events = $count_row['total'];
$total_pages = ceil($total_events / $limit);

// Get events with pagination
$query = "SELECT e.*, 
         (SELECT COUNT(*) FROM event_product WHERE event_id = e.id) as product_count
         FROM events e 
         $where_clause
         ORDER BY e.id DESC 
         LIMIT $offset, $limit";
$result = $kon->query($query);

// Get event for edit modal
$edit_event = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = "SELECT * FROM events WHERE id = $edit_id";
    $edit_result = $kon->query($edit_query);
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_event = $edit_result->fetch_assoc();
    }
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
    <script src="script.js" defer></script>
    <title>Manajemen Event - Fashion24</title>
    <style>
        .event-badge {
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
        
        .event-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .event-name {
            font-weight: 500;
        }
        
        .event-description {
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
        
        .event-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .event-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .event-stats {
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
                <li class="menu-item">
                    <a href="pesanan.php">
                        <i class="uil uil-shopping-cart"></i>
                        <span>Orders</span>
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
                        <input type="text" name="search" placeholder="Cari Event..." value="<?= htmlspecialchars($search) ?>" />
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
            
            <!-- Event Management Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Manajemen Event</h2>
                    <div class="header-actions">
                        <button class="btn primary-btn" id="addEventBtn">
                            <i class="uil uil-plus"></i> Tambah Event
                        </button>                      
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
                        <h3>Daftar Event</h3>
                        <div class="header-actions">
                            <span class="product-count"><?= $total_events ?> Event</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="responsive-table">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama Event</th>
                                            <th>Deskripsi</th>
                                            <th>Tanggal Mulai</th>
                                            <th>Tanggal Selesai</th>
                                            <th>Diskon (%)</th>
                                            <th>Produk Terkait</th>
                                            <th>Dibuat Pada</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= $row['id'] ?></td>
                                            <td>
                                                <div class="event-info">
                                                    <div class="event-badge">
                                                        <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="event-name"><?= $row['name'] ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $row['description'] ?></td>
                                            <td><?= date('d M Y', strtotime($row['start_date'])) ?></td>
                                            <td><?= date('d M Y', strtotime($row['end_date'])) ?></td>
                                            <td><?= $row['discount_percentage'] ?>%</td>
                                            <td>
                                                <span class="highlight"><?= $row['product_count'] ?></span>
                                                <div class="stats-label">Produk</div>
                                            </td>
                                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                            <td class="action-buttons">                                               
                                                <?php if($row['product_count'] == 0): ?>
                                             <a href="event_product.php?event_id=<?= $row['id'] ?>" class="btn view-btn">
                                                    <i class="uil uil-eye"></i> Lihat Produk
                                                </a>
                                                <?php else: ?>
                                                <a href="event_product.php?event_id=<?= $row['id'] ?>" class="btn view-btn">
                                                    <i class="uil uil-eye"></i> Lihat Produk
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
                                <i class="uil uil-calendar-alt"></i>
                                <?php if(!empty($search)): ?>
                                <p>Tidak ditemukan event yang sesuai dengan pencarian</p>
                                <a href="event.php" class="btn secondary-btn">Reset Pencarian</a>
                                <?php else: ?>
                                <p>Belum ada data Event</p>
                                <button class="btn primary-btn" id="addEventBtnEmpty">
                                    <i class="uil uil-plus"></i> Tambah Event Pertama
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Event Modal -->
    <div class="modal" id="addEventModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Event Baru</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="name">Nama Event</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">Tanggal Selesai</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_percentage">Persentase Diskon</label>
                        <input type="number" id="discount_percentage" name="discount_percentage" class="form-control" min="0" max="100" required>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="add_event" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Batal</button>
                        <button type="submit" class="btn primary-btn">Tambah Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Event Modal -->
    <?php if($edit_event): ?>
    <div class="modal" id="editEventModal" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Data Event</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="edit_name">Nama Event</label>
                        <input type="text" id="edit_name" name="name" class="form-control" value="<?= htmlspecialchars($edit_event['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Deskripsi</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="3" required><?= htmlspecialchars($edit_event['description']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_start_date">Tanggal Mulai</label>
                        <input type="date" id="edit_start_date" name="start_date" class="form-control" value="<?= date('Y-m-d', strtotime($edit_event['start_date'])) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_end_date">Tanggal Selesai</label>
                        <input type="date" id="edit_end_date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime($edit_event['end_date'])) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_discount_percentage">Persentase Diskon</label>
                        <input type="number" id="edit_discount_percentage" name="discount_percentage" class="form-control" min="0" max="100" value="<?= $edit_event['discount_percentage'] ?>" required>
                    </div>
                    
                    <div class="btn-container">
                        <input type="hidden" name="event_id" value="<?= $edit_event['id'] ?>">
                        <input type="hidden" name="edit_event" value="1">
                        <button type="button" class="btn secondary-btn cancel-modal">Batal</button>
                        <button type="submit" class="btn primary-btn">Perbarui Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
   <script>
// Ganti kode JavaScript Anda dengan ini di bagian bawah file
document.addEventListener('DOMContentLoaded', function() {
  console.log("DOM fully loaded"); // Debugging
  
  // Handle Add Event Modal
  const addEventBtn = document.getElementById('addEventBtn');
  const addEventBtnEmpty = document.getElementById('addEventBtnEmpty');
  const addEventModal = document.getElementById('addEventModal');
  
  if (addEventBtn) {
    addEventBtn.addEventListener('click', function() {
      console.log("Add button clicked");
      addEventModal.style.display = 'flex';
    addEventModal.style.opacity = '1';
    });
  }
  
  if (addEventBtnEmpty) {
    addEventBtnEmpty.addEventListener('click', function() {
      console.log("Empty button clicked");
      addEventModal.style.display = 'flex';
      addEventModal.style.opacitiy = '1';
    });
  }
  
  // Close modal
  const closeButtons = document.querySelectorAll('.close-modal, .cancel-modal');
  closeButtons.forEach(button => {
    button.addEventListener('click', function() {
      addEventModal.style.display = 'none';
      if (editEventModal) editEventModal.style.display = 'none';
    });
  });
});
</script>
</body>
</html>