<?php
include "koneksi.php";

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: produk.php");
    exit;
}

$product_id = $_GET['id'];

// Get product details
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = $product_id";
$result = $kon->query($query);

if (!$result || $result->num_rows == 0) {
    header("Location: produk.php");
    exit;
}

$product = $result->fetch_assoc();

// Get product variants
$variants_query = "SELECT * FROM product_variants WHERE product_id = $product_id";
$variants_result = $kon->query($variants_query);
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
    <title><?= $product['name'] ?> - Fashion24</title>
    <style>
        .product-detail {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        .product-image {
            flex: 0 0 40%;
            max-width: 500px;
        }
        .product-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-info {
            flex: 1;
        }
        .product-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 22px;
            font-weight: 600;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .product-category {
            display: inline-block;
            background-color: #f1f1f1;
            padding: 5px 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .product-description {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .variant-options {
            margin-bottom: 20px;
        }
        .variant-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        .variant-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .variant-item {
            padding: 5px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        .variant-item.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        .stock-info {
            margin-bottom: 20px;
            font-weight: 500;
        }
        .stock-available {
            color: #27ae60;
        }
        .stock-low {
            color: #f39c12;
        }
        .stock-out {
            color: #e74c3c;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #f1f1f1;
            color: #333;
            border: 1px solid #ddd;
        }
        .btn-secondary:hover {
            background-color: #e1e1e1;
        }
        .back-button {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            cursor: pointer;
            color: #333;
            text-decoration: none;
        }
        .back-button:hover {
            color: #3498db;
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
                        <span>Products</span>
                    </a>
                </li>
                <li class="menu-item ">
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
                    <form action="produk.php" method="GET">
                        <i class="uil uil-search search-icon"></i>
                        <input type="text" name="search" placeholder="Search products..." />
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
            
            <!-- Product Detail Content -->
            <div class="dashboard">
                <div class="page-header">
                    <h2 class="page-title">Product Detail</h2>
                    <div class="header-actions">
                        <a href="editproduk.php?id=<?= $product_id ?>" class="btn btn-secondary">
                            <i class="uil uil-edit"></i> Edit Product
                        </a>
                    </div>
                </div>
                
                <a href="produk.php" class="back-button">
                    <i class="uil uil-arrow-left"></i> Back to Products
                </a>
                
                <div class="content-card">
                    <div class="card-body">
                        <div class="product-detail">
                            <div class="product-image">
                                <img src="<?= !empty($product['image']) ? $product['image'] : 'img/bg.png' ?>" alt="<?= $product['name'] ?>">
                                <svg id="result"></svg>    
                                 <button onclick="downloadBarcode()" class="btn">Download Barcode</button>                                                                  
                                <button onclick="printBarcode()" class="btn">Print Barcode</button>
                            </div>
                            
                            <div class="product-info">
    <h1 class="product-title"><?= $product['name'] ?></h1>

    <div class="product-category">
        <i class="uil uil-tag-alt"></i> <?= $product['category_name'] ?? 'Uncategorized' ?>
    </div>

    <div class="product-price">
        Rp<?= number_format($product['price'], 0, ',', '.') ?>
    </div>

    <?php if ($variants_result && $variants_result->num_rows > 0): ?>
        <?php
        // Ekstraksi semua varian hanya sekali
        $barcodes = [];
        $sizes = [];
        $colors = [];
        $stocks = [];

        while ($variant = $variants_result->fetch_assoc()) {
            if (!in_array($variant['barcode'], $barcodes)) {
                $barcodes[] = $variant['barcode'];
            }
            if (!in_array($variant['size'], $sizes)) {
                $sizes[] = $variant['size'];
            }
            if (!in_array($variant['color'], $colors)) {
                $colors[] = $variant['color'];
            }
            $stocks[] = $variant['stock'];
        }

        // Ambil stok total atau pertama (sesuai logika Anda)
        $stock = max($stocks);
        ?>

        <div class="variant-options">
            <div class="variant-label">Barcode:</div>
            <div class="variant-selector">
                <?php foreach ($barcodes as $barcode): ?>
                    <div class="variant-item" data-barcode="<?= htmlspecialchars($barcode) ?>">
                        <?= htmlspecialchars($barcode) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="variant-label" style="margin-top: 15px;">Size:</div>
            <div class="variant-selector">
                <?php foreach ($sizes as $size): ?>
                    <div class="variant-item" data-size="<?= htmlspecialchars($size) ?>">
                        <?= htmlspecialchars($size) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="variant-label" style="margin-top: 15px;">Color:</div>
            <div class="variant-selector">
                <?php foreach ($colors as $color): ?>
                    <div class="variant-item" data-color="<?= htmlspecialchars($color) ?>">
                        <?= htmlspecialchars($color) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stock-info">
            <?php if ($stock > 20): ?>
                <span class="stock-available">In Stock (<?= $stock ?> items)</span>
            <?php elseif ($stock > 0): ?>
                <span class="stock-low">Low in Stock (<?= $stock ?> items)</span>
            <?php else: ?>
                <span class="stock-out">Out of Stock</span>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="stock-info">
            <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                <span class="stock-available">In Stock (<?= $product['stock'] ?> items)</span>
                <?php else: ?>
                    <span class="stock-out">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
    
    
    <div class="action-buttons">
        <a href="produk.php" class="btn btn-secondary">
            <i class="uil uil-arrow-left"></i> Back
        </a>
        <a href="editproduk.php?id=<?= $product_id ?>" class="btn btn-primary">
            <i class="uil uil-edit"></i> Edit Product
        </a>
    </div>
</div>
</div>
                        </div>
                    </div>                    
                </div>
            </div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
        </main>
    </div>
<?php
$first_barcode = $barcodes[0] ?? ''; // Atau dari satu varian tertentu
echo "<script>var barcode = '" . htmlspecialchars($first_barcode, ENT_QUOTES) . "';</script>";
?>

<script>
    // Fungsi untuk generate barcode
    function genBar() {
        if (barcode) {
            JsBarcode('#result', barcode, {
                format: "code128",
                lineColor: "#000",
                width: 3,
                height: 100
            });
        }
    }

    // Jalankan langsung saat halaman dimuat
    window.onload = genBar;

    // Variasi klik untuk update barcode (jika ingin interaktif)
    document.querySelectorAll('.variant-item[data-barcode]').forEach(item => {
        item.addEventListener('click', function () {
            // Ambil barcode dari atribut
            barcode = this.getAttribute('data-barcode');

            // Regenerate barcode
            genBar();
        });
    });

    // Highlight active variant
    document.querySelectorAll('.variant-item').forEach(item => {
        item.addEventListener('click', function () {
            const parentSelector = this.parentElement;
            parentSelector.querySelectorAll('.variant-item').forEach(el => {
                el.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
</script>
<script>
function downloadBarcode() {
    const svg = document.querySelector("#result");

    // Buat file blob dari SVG
    const serializer = new XMLSerializer();
    const svgBlob = new Blob([serializer.serializeToString(svg)], { type: "image/svg+xml;charset=utf-8" });
    const url = URL.createObjectURL(svgBlob);

    // Buat link dan trigger download
    const link = document.createElement("a");
    link.href = url;
    link.download = "barcode.svg";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function printBarcode() {
    const printWindow = window.open('', '_blank');
    const svgContent = document.getElementById('result').outerHTML;

    printWindow.document.write(`
        <html>
        <head><title>Print Barcode</title></head>
        <body style="text-align:center;">
            ${svgContent}
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() { window.close(); };
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>

</body>
</html>