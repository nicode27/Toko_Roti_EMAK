<?php
require_once 'config.php';

// Initialize user data and upload directory for profiles
$user_data = [];
$upload_dir = 'uploads/profiles/'; // For profile pictures

// Initialize upload directory for products
$upload_dir_products = 'uploads/products/'; // For product images

// Fetch user data if logged in
if (isLoggedIn()) {
    $conn_local = getConnection();
    $stmt_user = $conn_local->prepare("SELECT nama, foto_profil FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $_SESSION['user_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();
    $conn_local->close();
}

// Ensure $user_data has default values if not logged in or user_id invalid
if (!$user_data) {
    $user_data = ['nama' => 'Guest', 'foto_profil' => ''];
}

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getConnection();

// Handle cart actions (removing item only, update handled via JS now)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $conn->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        redirect('cart.php'); // Redirect to prevent form resubmission
    }
}

// Handle AJAX quantity update
if (isset($_POST['action']) && $_POST['action'] === 'update_quantity_ajax') {
    header('Content-Type: application/json');
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity <= 0) { // If quantity is 0 or less, remove item
        $stmt = $conn->prepare("DELETE FROM keranjang WHERE id = ? AND id_user = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Produk dihapus dari keranjang.', 'removed' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk.']);
        }
    } else {
        // Get product stock to prevent over-ordering
        $stmt = $conn->prepare("SELECT p.stok FROM keranjang k JOIN produk p ON k.id_produk = p.id WHERE k.id = ? AND k.id_user = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $available_stock = $res['stok'] ?? 0;

        if ($quantity > $available_stock) {
            echo json_encode(['success' => false, 'message' => 'Jumlah melebihi stok yang tersedia.', 'max_quantity' => $available_stock]);
        } else {
            $stmt = $conn->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ? AND id_user = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Jumlah diperbarui.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui jumlah.']);
            }
        }
    }
    $stmt->close();
    $conn->close();
    exit(); // Exit after AJAX response
}


// Get cart items for display - NOW FETCHING p.gambar
$stmt = $conn->prepare("
    SELECT k.id as cart_id, k.jumlah, p.id as product_id, p.nama_produk, p.harga, p.stok, p.gambar
    FROM keranjang k
    JOIN produk p ON k.id_produk = p.id
    WHERE k.id_user = ?
    ORDER BY k.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['harga'] * $item['jumlah'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Roti Emak</title>
    <style>
        /* Gaya dari cart.php yang sudah ada */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html {
            height: 100%; /* Ensure html takes full height */
        }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f8f5f0; 
            line-height: 1.6; 
            display: flex; /* Enable flexbox */
            flex-direction: column; /* Stack children vertically */
            min-height: 100vh; /* Ensure body takes at least full viewport height */
        }
        header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000;
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
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
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

        /* Dropdown Styles */
        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dropdown-toggle:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .dropdown-toggle::after {
            content: '▼';
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .dropdown.active .dropdown-toggle::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 180px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 2000;
            overflow: hidden;
            margin-top: 5px;
        }

        .dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a.dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-menu a.dropdown-item .icon {
            fill: #333;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        /* Profile Dropdown Specific Styles */
        .profile-dropdown {
            position: relative;
        }

        .profile-toggle {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-toggle:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        .profile-name {
            font-weight: 500;
            font-size: 14px;
        }

        .profile-dropdown .dropdown-menu {
            right: 0;
            left: auto;
            min-width: 200px;
        }

        .dropdown-item.logout {
            color: #dc3545;
            font-weight: 500;
        }

        .dropdown-item.logout:hover {
            background-color: #fff5f5;
        }

        /* Icon styles */
        .icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
            flex-shrink: 0;
        }

        main { 
            max-width: 1000px; 
            margin: 2rem auto; 
            padding: 0 2rem; 
            flex-grow: 1; /* Allow main content to grow and push footer */
        }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 0.5rem; }
        
        .cart-container { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        
        .cart-item {
            display: grid;
            grid-template-columns: 80px 1fr auto auto auto; /* image | info | quantity | remove-btn | total */
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .cart-item:last-child { border-bottom: none; }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0; /* Placeholder background */
        }
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .item-image span {
            color: #ccc;
            font-size: 0.8rem;
            text-align: center;
        }

        .item-info { flex: 1; }
        .item-name { font-weight: bold; color: #333; margin-bottom: 0.25rem; }
        .item-price { color: #D2691E; font-weight: 600; }
        .item-quantity { display: flex; align-items: center; justify-content: center; } /* Center quantity */
        
        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        
        .item-total { font-weight: bold; color: #333; min-width: 100px; text-align: right; }
        .item-actions { display: flex; justify-content: center; } /* Center remove button */
        
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; transition: all 0.3s; font-size: 0.9rem; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
        
        .btn-remove { background: #dc3545; color: white; }
        .btn-remove:hover { background: #c82333; }
        
        .btn-primary { background: #D2691E; color: white; padding: 1rem 2rem; font-size: 1.1rem; }
        .btn-primary:hover { background: #B8860B; }
        .btn-outline { background: transparent; color: #D2691E; border: 1px solid #D2691E; }
        .btn-outline:hover { background: #D2691E; color: white; }
        
        .cart-summary { background: #f8f9fa; padding: 2rem; text-align: center; }
        .total-amount { font-size: 1.5rem; font-weight: bold; color: #333; margin-bottom: 1rem; }
        .checkout-actions { display: flex; gap: 1rem; justify-content: center; }
        
        .empty-cart { text-align: center; padding: 3rem; color: #666; }
        .empty-cart h3 { margin-bottom: 1rem; font-size: 1.5rem; }
        .empty-cart p { margin-bottom: 2rem; }

         /* Footer */
         footer { background: #8B4513; color: white; text-align: center; padding: 2rem; margin-top: 2rem; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .footer-links a { color: white; text-decoration: none; }
        .footer-links a:hover { color: #f0e68c; }
        
        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-links { gap: 1rem; }
            .cart-item { 
                grid-template-columns: auto 1fr; /* Image, Info | Quantity, Remove, Total */
                grid-template-areas: 
                    "image info"
                    "quantity actions"
                    "total total";
                text-align: center; 
            }
            .item-image { grid-area: image; margin: 0 auto; }
            .item-info { grid-area: info; text-align: left; }
            .item-quantity { grid-area: quantity; justify-content: flex-start; }
            .item-actions { grid-area: actions; justify-content: flex-end; }
            .item-total { grid-area: total; text-align: center; }

            .checkout-actions { flex-direction: column; }
            main { padding: 0 1rem; }
            .profile-name { display: none; }
            .dropdown-menu { left: auto; right: 0; }
        }
        @media (max-width: 480px) {
            .cart-item { 
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "image"
                    "info"
                    "quantity"
                    "actions"
                    "total";
            }
            .item-info { text-align: center; }
            .item-quantity, .item-actions { justify-content: center; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Toko Roti Emak</div>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="tentang.php">Tentang</a></li>
                <li><a href="kontak.php">Kontak</a></li>
            </ul>
            <div class="nav-user">
                <?php if (isLoggedIn()): ?>
                <div class="dropdown" id="pesananDropdown">
                    <button class="dropdown-toggle">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                        </svg>
                        Pesanan
                    </button>
                    <div class="dropdown-menu">
                        <a href="cart.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M17,18C17.56,18 18,17.56 18,17V5C18,4.44 17.56,4 17,4H7C6.44,4 6,4.44 6,5V17C6,17.56 6.44,18 7,18H17M17,2A2,2 0 0,1 19,4V18A2,2 0 0,1 17,20H7C6.46,20 5.96,19.79 5.59,19.41C5.21,19.04 5,18.53 5,18V4A2,2 0 0,1 7,2H17Z"/>
                            </svg>
                            Keranjang
                        </a>
                        <a href="riwayat.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3"/>
                            </svg>
                            Riwayat
                        </a>
                    </div>
                </div>

                <div class="dropdown profile-dropdown" id="profileDropdown">
                    <button class="profile-toggle">
                        <img src="<?php echo ($user_data['foto_profil'] && file_exists($upload_dir . $user_data['foto_profil'])) ? $upload_dir . htmlspecialchars($user_data['foto_profil']) : $upload_dir . 'default.png'; ?>" alt="Profile" class="profile-avatar">
                        <span class="profile-name"><?php echo htmlspecialchars($user_data['nama']); ?></span>
                        <svg class="icon" style="width: 14px; height: 14px;" viewBox="0 0 24 24">
                            <path d="M7,10L12,15L17,10H7Z"/>
                        </svg>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                            </svg>
                            Profil Saya
                        </a>
                        <a href="logout.php" class="dropdown-item logout">
                            <svg class="icon" style="width: 16px; height: 16px; margin-right: 8px;" viewBox="0 0 24 24">
                                <path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/>
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>
                <?php else: ?>
                    <a href="login.php">Masuk</a>
                    <a href="register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    
    <main>
        <div class="page-header">
            <h1>🛒 Keranjang Belanja</h1>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="cart-container">
                <div class="empty-cart">
                    <h3>Keranjang Anda Kosong</h3>
                    <p>Silakan pilih produk roti yang ingin Anda beli</p>
                    <a href="produk.php" class="btn btn-primary">Lihat Produk</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" id="cart-item-<?php echo $item['cart_id']; ?>">
                        <div class="item-image">
                            <?php
                            $imagePath = $upload_dir_products . htmlspecialchars($item['gambar']);
                            if ($item['gambar'] && file_exists($imagePath)) {
                                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($item['nama_produk']) . '">';
                            } else {
                                echo '<span>No Image</span>';
                            }
                            ?>
                        </div>
                        
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['nama_produk']); ?></div>
                            <div class="item-price"><?php echo formatRupiah($item['harga']); ?> per buah</div>
                        </div>
                        
                        <div class="item-quantity">
                            <input type="number"
                                   data-cart-id="<?php echo $item['cart_id']; ?>"
                                   data-product-price="<?php echo $item['harga']; ?>"
                                   data-product-stock="<?php echo $item['stok']; ?>"
                                   value="<?php echo $item['jumlah']; ?>"
                                   min="1"
                                   max="<?php echo $item['stok']; ?>"
                                   class="quantity-input"
                                   onchange="updateCartItem(this)">
                        </div>

                        <div class="item-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" class="btn btn-remove btn-sm"
                                        onclick="return confirm('Hapus item dari keranjang?')">Hapus</button>
                            </form>
                        </div>
                        
                        <div class="item-total" id="item-total-<?php echo $item['cart_id']; ?>">
                            <?php echo formatRupiah($item['harga'] * $item['jumlah']); ?>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
                
                <div class="cart-summary">
                    <div class="total-amount" id="grand-total">
                        Total: <?php echo formatRupiah($total); ?>
                    </div>
                    <div class="checkout-actions">
                        <a href="produk.php" class="btn btn-outline">Lanjut Belanja</a>
                        <a href="checkout.php" class="btn btn-primary">Checkout</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="index.php">Beranda</a>
                <a href="produk.php">Produk</a>
                <a href="tentang.php">Tentang</a>
                <a href="kontak.php">Kontak</a>
            </div>
            <p>© 2024 Toko Roti Emak. Dibuat dengan cinta untuk keluarga Indonesia.</p>
        </div>
    </footer>
    
    <script>
         function addToCart(productId) {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1); // Default quantity
            formData.append('action', 'add'); // Tambahkan action jika diperlukan di backend

            fetch('<?php echo BASE_URL; ?>add_to_cart.php', { // PENTING: Pastikan BASE_URL digunakan di sini
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status + ' ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                } else {
                    alert('Gagal menambahkan ke keranjang: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menambahkan ke keranjang: ' + error.message);
            });
        }
        function updateCartItem(inputElement) {
            const cartId = inputElement.dataset.cartId;
            let newQuantity = parseInt(inputElement.value);
            const productPrice = parseFloat(inputElement.dataset.productPrice);
            const productStock = parseInt(inputElement.dataset.productStock);

            // Validasi input
            if (isNaN(newQuantity) || newQuantity < 1) {
                newQuantity = 1;
                inputElement.value = 1; // Setel kembali ke 1 jika input tidak valid
            }
            if (newQuantity > productStock) {
                alert('Jumlah tidak boleh melebihi stok yang tersedia (' + productStock + ').');
                newQuantity = productStock;
                inputElement.value = productStock; // Setel kembali ke max stock
            }

            const formData = new FormData();
            formData.append('action', 'update_quantity_ajax');
            formData.append('cart_id', cartId);
            formData.append('quantity', newQuantity);

            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.removed) {
                        // Jika item dihapus (quantity 0), hapus elemen dari DOM
                        document.getElementById('cart-item-' + cartId).remove();
                        alert(data.message);
                    } else {
                        // Perbarui total item
                        const itemTotalElement = document.getElementById('item-total-' + cartId);
                        itemTotalElement.innerText = formatRupiah(productPrice * newQuantity);
                        // Perbarui total keseluruhan
                        updateGrandTotal();
                    }
                } else {
                    alert('Gagal memperbarui: ' + data.message);
                    // Jika ada masalah, kembalikan nilai input ke nilai sebelumnya atau max_quantity
                    if (data.max_quantity !== undefined) {
                        inputElement.value = data.max_quantity;
                        alert('Jumlah dikembalikan ke stok maksimum yang tersedia: ' + data.max_quantity);
                    } else {
                         // Fallback: reload page if update fails for unknown reason
                        location.reload();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memperbarui keranjang.');
                location.reload(); // Reload jika ada error jaringan
            });
        }

        function updateGrandTotal() {
            let grandTotal = 0;
            document.querySelectorAll('.cart-item').forEach(itemElement => {
                // Ensure the element still exists before trying to access its children
                if (itemElement.querySelector('.quantity-input')) {
                    const quantity = parseInt(itemElement.querySelector('.quantity-input').value);
                    const price = parseFloat(itemElement.querySelector('.quantity-input').dataset.productPrice);
                    grandTotal += (quantity * price);
                }
            });
            document.getElementById('grand-total').innerText = 'Total: ' + formatRupiah(grandTotal);
        }

        function formatRupiah(amount) {
            return "Rp " + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown, .profile-dropdown');
            
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle, .profile-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                
                if (toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Cek apakah dropdown ini sudah aktif
                    const isActive = dropdown.classList.contains('active');

                    // Tutup semua dropdown lain terlebih dahulu
                    dropdowns.forEach(otherDropdown => {
                        otherDropdown.classList.remove('active');
                    });
                    
                    // Jika dropdown yang diklik belum aktif, aktifkan
                    if (!isActive) {
                        dropdown.classList.add('active');
                    }
                });
            }
        });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                dropdowns.forEach(dropdown => {
                    // Jika area yang diklik bukan bagian dari dropdown, tutup dropdown tersebut
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            });

            
            // Prevent dropdown from closing when clicking inside menu
            const logoutButton = document.querySelector('.dropdown-item.logout');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    if (e.target.getAttribute('href') === 'logout.php') {
                        if (!confirm('Apakah Anda yakin ingin keluar?')) {
                            e.preventDefault();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>