<?php
require_once 'config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    redirect('produk.php'); // Redirect if no product ID is provided
}

$conn = getConnection();

// Get product details
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ? AND stok > 0");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    // Product not found or out of stock
    redirect('produk.php');
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    $user_id = $_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($quantity <= 0 || $quantity > $product['stok']) {
        $error = 'Jumlah tidak valid atau melebihi stok yang tersedia.';
    } else {
        // Check if product already in cart
        $stmt = $conn->prepare("SELECT id, jumlah FROM keranjang WHERE id_user = ? AND id_produk = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $cart_item_result = $stmt->get_result();

        if ($cart_item_result->num_rows > 0) {
            // Update quantity if already in cart
            $cart_item = $cart_item_result->fetch_assoc();
            $new_quantity = $cart_item['jumlah'] + $quantity;
            if ($new_quantity > $product['stok']) {
                $error = 'Tidak bisa menambahkan. Total melebihi stok yang tersedia.';
            } else {
                $stmt = $conn->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
                $stmt->execute();
                $success = 'Jumlah produk di keranjang berhasil diperbarui!';
            }
        } else {
            // Add new item to cart
            $stmt = $conn->prepare("INSERT INTO keranjang (id_user, id_produk, jumlah) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
            if ($stmt->execute()) {
                $success = 'Produk berhasil ditambahkan ke keranjang!';
            } else {
                $error = 'Gagal menambahkan produk ke keranjang.';
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['nama_produk']); ?> - Toko Roti Emak</title>
    <style>
        <?php include 'style.css'; // Assuming common styles are in a file named style.css or copied here ?>
        /* Basic Styles - Copied from other files for consistency */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f5f0;
            line-height: 1.6;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #f0e68c;
        }

        .nav-user {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-user a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-user a:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Main Content */
        main {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .product-detail-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }

        .product-detail-image {
            width: 100%;
            max-width: 300px;
            height: 300px;
            background: linear-gradient(45deg, #f0e68c, #daa520);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
            color: #8B4513;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .product-detail-info {
            text-align: center;
        }

        .product-detail-name {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }

        .product-detail-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .product-detail-price {
            font-size: 2rem;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 1.5rem;
        }

        .product-detail-stock {
            color: #28a745;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .add-to-cart-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            width: 100%;
            max-width: 300px;
        }

        .quantity-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            text-align: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            font-weight: 500;
            width: 100%;
        }

        .btn-primary {
            background: #D2691E;
            color: white;
        }

        .btn-primary:hover {
            background: #B8860B;
        }

        .btn-outline {
            background: transparent;
            color: #D2691E;
            border: 1px solid #D2691E;
        }

        .btn-outline:hover {
            background: #D2691E;
            color: white;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            width: 100%;
            text-align: center;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        /* Footer */
        footer {
            background: #8B4513;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
        }

        .footer-links a:hover {
            color: #f0e68c;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .product-detail-container {
                padding: 1.5rem;
            }

            .product-detail-name {
                font-size: 2rem;
            }

            .product-detail-price {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">🍞 Toko Roti Emak</div>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="tentang.php">Tentang</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
            <div class="nav-user">
                <?php if (isLoggedIn()): ?>
                    <a href="cart.php">🛒 Keranjang</a>
                    <a href="riwayat.php">Riwayat</a>
                    <a href="profile.php">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</a> <a href="logout.php">Keluar</a>
                <?php else: ?>
                    <a href="login.php">Masuk</a>
                    <a href="register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <div class="product-detail-container">
        <div class="product-detail-image" style="background: none; display: flex; align-items: center; justify-content: center;">
    <?php 
    $imagePath = 'uploads/products/' . htmlspecialchars($product['gambar']);
    if ($product['gambar'] && file_exists($imagePath)) {
        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($product['nama_produk']) . '" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 10px;">';
    } else {
        // Fallback jika tidak ada gambar atau gambar tidak ditemukan
        $emoji = '🍞'; // Default emoji
        if (strpos(strtolower($product['nama_produk']), 'cokelat') !== false) $emoji = '🍫';
        elseif (strpos(strtolower($product['nama_produk']), 'pisang') !== false) $emoji = '🍌';
        elseif (strpos(strtolower($product['nama_produk']), 'croissant') !== false) $emoji = '🥐';
        elseif (strpos(strtolower($product['nama_produk']), 'donat') !== false) $emoji = '🍩';
        echo '<span style="font-size: 6rem; color: #8B4513;">' . $emoji . '</span>';
    }
    ?>
</div>
            <div class="product-detail-info">
                <div class="product-detail-name"><?php echo htmlspecialchars($product['nama_produk']); ?></div>
                <div class="product-detail-description"><?php echo htmlspecialchars($product['deskripsi']); ?></div>
                <div class="product-detail-price"><?php echo formatRupiah($product['harga']); ?></div>
                <div class="product-detail-stock">Stok: <?php echo $product['stok']; ?> buah</div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stok']; ?>" class="quantity-input">
                    <?php if (isLoggedIn()): ?>
                        <button type="submit" class="btn btn-primary">Tambahkan ke Keranjang</button>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login untuk Membeli</a>
                    <?php endif; ?>
                </form>
                <a href="produk.php" class="btn btn-outline" style="margin-top: 1rem;">Kembali ke Daftar Produk</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="index.php">Beranda</a>
                <a href="produk.php">Produk</a>
                <a href="tentang.php">Tentang</a>
                <a href="kontak.php">Kontak</a>
            </div>
            <p>&copy; 2024 Toko Roti Emak. Dibuat dengan ❤️ untuk keluarga Indonesia.</p>
        </div>
    </footer>
</body>
</html>