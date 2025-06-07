<?php
require_once 'config.php'; // Pastikan ini baris pertama dan teratas

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login untuk menambahkan produk ke keranjang.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1; // Default 1

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID produk atau jumlah tidak valid.']);
        exit();
    }

    $conn = getConnection();

    // Get product stock
    $stmt = $conn->prepare("SELECT stok FROM produk WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();
    $product = $product_result->fetch_assoc();
    $stmt->close();

    if (!$product || $product['stok'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak cukup atau produk tidak ditemukan.']);
        $conn->close();
        exit();
    }

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
            echo json_encode(['success' => false, 'message' => 'Tidak bisa menambahkan. Total melebihi stok yang tersedia.']);
        } else {
            $stmt = $conn->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan ke keranjang!', 'new_total_quantity' => $new_quantity]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui keranjang.']);
            }
        }
    } else {
        // Add new item to cart
        $stmt = $conn->prepare("INSERT INTO keranjang (id_user, id_produk, jumlah) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan ke keranjang!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk ke keranjang.']);
        }
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>