<?php
require_once 'config.php'; // Pastikan ini baris pertama

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


// Get cart items for display
$stmt = $conn->prepare("
    SELECT k.id as cart_id, k.jumlah, p.id as product_id, p.nama_produk, p.harga, p.stok
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
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f5f0; line-height: 1.6; }
        header { background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%); color: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        nav { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 2rem; }
        .nav-links a { color: white; text-decoration: none; transition: color 0.3s; }
        .nav-links a:hover { color: #f0e68c; }
        .nav-user { display: flex; gap: 1rem; align-items: center; }
        .nav-user a { color: white; text-decoration: none; padding: 0.5rem 1rem; border: 1px solid rgba(255,255,255,0.3); border-radius: 5px; transition: all 0.3s; }
        .nav-user a:hover { background: rgba(255,255,255,0.1); }
        
        main { max-width: 1000px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 0.5rem; }
        
        .cart-container { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        
        .cart-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto; /* Disesuaikan, tombol update dihapus */
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .cart-item:last-child { border-bottom: none; }
        .item-emoji { font-size: 2rem; }
        .item-info { flex: 1; }
        .item-name { font-weight: bold; color: #333; margin-bottom: 0.25rem; }
        .item-price { color: #D2691E; font-weight: 600; }
        .item-quantity { display: flex; align-items: center; gap: 0.5rem; }
        
        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        
        .item-total { font-weight: bold; color: #333; min-width: 100px; text-align: right; }
        .item-actions { display: flex; gap: 0.5rem; }
        
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
        
        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-links { gap: 1rem; }
            .cart-item { grid-template-columns: 1fr; gap: 1rem; text-align: center; }
            .checkout-actions { flex-direction: column; }
            main { padding: 0 1rem; }
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
                        <div class="item-emoji">
                            <?php
                            $emoji = '🍞';
                            if (strpos(strtolower($item['nama_produk']), 'cokelat') !== false) $emoji = '🍫';
                            elseif (strpos(strtolower($item['nama_produk']), 'pisang') !== false) $emoji = '🍌';
                            elseif (strpos(strtolower($item['nama_produk']), 'croissant') !== false) $emoji = '🥐';
                            elseif (strpos(strtolower($item['nama_produk']), 'donat') !== false) $emoji = '🍩';
                            echo $emoji;
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
                        
                        <div class="item-total" id="item-total-<?php echo $item['cart_id']; ?>">
                            <?php echo formatRupiah($item['harga'] * $item['jumlah']); ?>
                        </div>
                        
                        <div class="item-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" class="btn btn-remove btn-sm"
                                        onclick="return confirm('Hapus item dari keranjang?')">Hapus</button>
                            </form>
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

    <script>
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
                const quantity = parseInt(itemElement.querySelector('.quantity-input').value);
                const price = parseFloat(itemElement.querySelector('.quantity-input').dataset.productPrice);
                grandTotal += (quantity * price);
            });
            document.getElementById('grand-total').innerText = 'Total: ' + formatRupiah(grandTotal);
        }

        function formatRupiah(amount) {
            return "Rp " + amount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }
    </script>
</body>
</html>