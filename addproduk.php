<?php
include "koneksi.php";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_produk = $_POST['nama'];
    $harga_produk = $_POST['harga'];
    $kategori_id = $_POST['id_kategori'];
    
    
    // Handle file upload
    $gambar_produk = '';
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "img/produk/";
        $file_extension = pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
            $gambar_produk = $target_file;
        } else {
            $upload_error = "Gagal mengunggah gambar produk.";
        }
    }
    
    // Start transaction
    $kon->begin_transaction();
    
    try {
        // Insert product into database
        $created_at = date("Y-m-d H:i:s");
        $insert_query = "INSERT INTO products (name, price, image, category_id, created_at) 
                VALUES (?, ?, ?, ?, ?)";


        
        $stmt = $kon->prepare($insert_query);
$stmt->bind_param("sdsss", $nama_produk, $harga_produk, $gambar_produk, $kategori_id, $created_at);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting product: " . $stmt->error);
        }
        
        $product_id = $kon->insert_id;
        
        // Insert product variants
        if (isset($_POST['variants']) && !empty($_POST['variants'])) {
            $variants = $_POST['variants'];
            
            $variant_query = "INSERT INTO product_variants (product_id, size, color, barcode, stock, created_at, updated_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
            $variant_stmt = $kon->prepare($variant_query);
            
            foreach ($variants as $variant) {
                if (!empty($variant['size']) || !empty($variant['color'])) {
                    $size = !empty($variant['size']) ? $variant['size'] : '';
                    $color = !empty($variant['color']) ? $variant['color'] : '';
                    $barcode = !empty($variant['barcode']) ? $variant['barcode'] : '';
                    $stock = !empty($variant['stock']) ? intval($variant['stock']) : 0;
                    $updated_at = $created_at;
                    
                    $variant_stmt->bind_param("isssiss", 
                        $product_id, 
                        $size, 
                        $color, 
                        $barcode, 
                        $stock, 
                        $created_at,
                        $updated_at
                    );
                    
                    if (!$variant_stmt->execute()) {
                        throw new Exception("Error inserting variant: " . $variant_stmt->error);
                    }
                }
            }
            $variant_stmt->close();
        }
        
        // Commit transaction
        $kon->commit();
        $stmt->close();
        
        // Redirect to product management page
        header("Location: produk.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $kon->rollback();
        $error_message = $e->getMessage();
    }
}

// Get categories for dropdown
$kategori_query = "SELECT * FROM categories ORDER BY name";
$kategori_result = $kon->query($kategori_query);
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
    <title>Tambah Produk - Fashion24</title>
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(77, 14, 14, 0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .secondary-btn {
            background-color: #f4f4f4;
            color: var(--text-color);
        }
        
        .secondary-btn:hover {
            background-color: #e0e0e0;
        }
        
        .image-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed var(--border-color);
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .image-preview-text {
            color: var(--text-light);
            font-size: 14px;
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .alert.error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Variant Styles */
        .variants-section {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        
        .variants-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .variants-header h4 {
            margin: 0;
            color: var(--text-color);
        }
        
        .add-variant-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .add-variant-btn:hover {
            background-color: #5a1010;
        }
        
        .variant-item {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
        }
        
        .variant-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 100px auto;
            gap: 15px;
            align-items: end;
        }
        
        .variant-row .form-group {
            margin-bottom: 0;
        }
        
        .remove-variant-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            height: 40px;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-variant-btn:hover {
            background-color: #c82333;
        }
        
        .variant-examples {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .barcode-generate-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .barcode-generate-btn:hover {
            background-color: #5a6268;
        }
        
        @media (max-width: 768px) {
            .variant-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .remove-variant-btn {
                justify-self: end;
            }
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
                <li class="menu-item active">
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
                
                <div class="nav-title">
                    <h3>Tambah Produk Baru</h3>
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
            
            <!-- Add Product Form -->
            <div class="dashboard">
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a> / 
                    <a href="produk.php">Produk</a> / 
                    <span>Tambah Produk</span>
                </div>
                
                <?php if(isset($error_message)): ?>
                <div class="alert error">
                    <i class="uil uil-exclamation-triangle"></i>
                    <?= $error_message ?>
                </div>
                <?php endif; ?>
                
                <div class="content-card">
                    <div class="card-header">
                        <h3>Tambah Produk Baru</h3>
                    </div>
                    
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data" class="form-container">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama_produk">Nama Produk</label>
                                    <input type="text" id="nama_produk" name="nama" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="kategori_id">Kategori</label>
                                    <select id="kategori_id" name="id_kategori" class="form-control" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php if($kategori_result && $kategori_result->num_rows > 0): ?>
                                            <?php while($row = $kategori_result->fetch_assoc()): ?>
                                                <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option value="1">Celana</option>
                                            <option value="2">Baju</option>
                                            <option value="3">Dress</option>                                            
                                            <option value="4">Sepatu</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="harga_produk">Harga Produk (Rp)</label>
                                <input type="number" id="harga_produk" name="harga" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="gambar_produk">Gambar Produk</label>
                                <div class="image-preview" id="imagePreview">
                                    <div class="image-preview-text">
                                        <i class="uil uil-image-upload" style="font-size: 40px;"></i>
                                        <p>Pilih gambar produk</p>
                                    </div>
                                </div>
                                <input type="file" id="gambar_produk" name="gambar" class="form-control" accept="image/*">
                            </div>
                            
                            <!-- <div class="form-group">
                                <label for="deskripsi_produk">Deskripsi Produk</label>
                                <textarea id="deskripsi_produk" name="deskripsi_produk" class="form-control"></textarea>
                            </div> -->
                            
                            <!-- Product Variants Section -->
                            <div class="variants-section">
                                <div class="variants-header">
                                    <h4>Variant Produk</h4>
                                    <button type="button" class="add-variant-btn" onclick="addVariant()">
                                        <i class="uil uil-plus"></i>
                                        Tambah Variant
                                    </button>
                                </div>
                                
                                <div id="variantsContainer">
                                    <!-- Variants will be added here dynamically -->
                                </div>
                                
                                <small class="variant-examples">
                                    <strong>Contoh:</strong> Size: S, M, L, XL | Color: Merah, Biru, Hijau, Putih
                                </small>
                            </div>
                            
                            <div class="btn-container">
                                <a href="produk.php" class="btn secondary-btn">Batal</a>
                                <button type="submit" class="btn primary-btn">Simpan Produk</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let variantCount = 0;
        
        // Image preview script
        const imageInput = document.getElementById('gambar_produk');
        const imagePreview = document.getElementById('imagePreview');
        const previewText = imagePreview.querySelector('.image-preview-text');
        
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                previewText.style.display = "none";
                
                reader.addEventListener('load', function() {
                    const img = document.createElement('img');
                    img.src = this.result;
                    
                    // Remove any previous preview
                    while (imagePreview.firstChild) {
                        imagePreview.removeChild(imagePreview.firstChild);
                    }
                    
                    imagePreview.appendChild(img);
                });
                
                reader.readAsDataURL(file);
            } else {
                previewText.style.display = "flex";
                
                // Remove any previous preview
                while (imagePreview.firstChild) {
                    if (imagePreview.firstChild.className === 'image-preview-text') {
                        break;
                    }
                    imagePreview.removeChild(imagePreview.firstChild);
                }
            }
        });
        
        // Generate random barcode
        function generateBarcode() {
            return 'PRD' + Date.now().toString().slice(-6) + Math.random().toString(36).substr(2, 3).toUpperCase();
        }
        
        // Variant management functions
        function addVariant() {
            const container = document.getElementById('variantsContainer');
            const randomBarcode = generateBarcode();
            
            const variantHtml = `
                <div class="variant-item" id="variant-${variantCount}">
                    <div class="variant-row">
                        <div class="form-group">
                            <label>Ukuran (Size)</label>
                            <select name="variants[${variantCount}][size]" class="form-control">
                                <option value="">Pilih Ukuran</option>
                                <option value="XS">XS</option>
                                <option value="S">S</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                                <option value="XXL">XXL</option>
                                <option value="XXXL">XXXL</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Warna (Color)</label>
                            <input type="text" name="variants[${variantCount}][color]" class="form-control" placeholder="Contoh: Merah, Biru">
                        </div>
                        <div class="form-group">
                            <label>Barcode</label>
                            <input type="text" name="variants[${variantCount}][barcode]" class="form-control" value="${randomBarcode}" readonly>
                            <button type="button" class="barcode-generate-btn" onclick="generateNewBarcode(${variantCount})">Generate Baru</button>
                        </div>
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number" name="variants[${variantCount}][stock]" class="form-control" placeholder="0" min="0" required>
                        </div>
                        <button type="button" class="remove-variant-btn" onclick="removeVariant(${variantCount})">
                            <i class="uil uil-trash-alt"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', variantHtml);
            variantCount++;
        }
        
        function removeVariant(id) {
            const variant = document.getElementById(`variant-${id}`);
            if (variant) {
                variant.remove();
            }
        }
        
        function generateNewBarcode(id) {
            const barcodeInput = document.querySelector(`input[name="variants[${id}][barcode]"]`);
            if (barcodeInput) {
                barcodeInput.value = generateBarcode();
            }
        }
        
        // Add initial variant on page load
        document.addEventListener('DOMContentLoaded', function() {
            addVariant();
        });
    </script>
</body>
</html>