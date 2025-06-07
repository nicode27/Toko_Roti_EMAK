<?php
require_once 'config.php'; // Pastikan ini baris pertama

// Get all products
$conn = getConnection();
$result = $conn->query("SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk");
$produk_list = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Toko Roti Emak</title>
    <style>
        /* Gaya dari produk.php yang sudah ada */
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
        
        main { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 1rem; }
        .page-header p { color: #666; font-size: 1.1rem; }
        
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; }
        .product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .product-image { width: 100%; height: 200px; background: linear-gradient(45deg, #f0e68c, #daa520); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #8B4513; }
        .product-info { padding: 1.5rem; }
        .product-name { font-size: 1.3rem; font-weight: bold; color: #333; margin-bottom: 0.5rem; }
        .product-description { color: #666; margin-bottom: 1rem; line-height: 1.5; }
        .product-price { font-size: 1.4rem; font-weight: bold; color: #D2691E; margin-bottom: 1rem; }
        .product-stock { color: #28a745; font-size: 0.9rem; margin-bottom: 1rem; }
        .product-actions { display: flex; gap: 0.5rem; }
        .btn { padding: 0.75rem 1rem; border: none; border-radius: 8px; text-decoration: none; display: inline-block; text-align: center; cursor: pointer; transition: all 0.3s; font-size: 0.9rem; }
        .btn-primary { background: #D2691E; color: white; flex: 1; }
        .btn-primary:hover { background: #B8860B; }
        .btn-outline { background: transparent; color: #D2691E; border: 1px solid #D2691E; }
        .btn-outline:hover { background: #D2691E; color: white; }
        
        .empty-state { text-align: center; padding: 3rem; color: #666; }
        .empty-state h3 { margin-bottom: 1rem; }
        
        footer { background: #8B4513; color: white; text-align: center; padding: 2rem; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .footer-links a { color: white; text-decoration: none; }
        .footer-links a:hover { color: #f0e68c; }
        
        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-links { gap: 1rem; }
            .page-header h1 { font-size: 2rem; }
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
            main { padding: 0 1rem; }
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
        <div class="page-header">
            <h1>Produk Roti Segar</h1>
            <p>Pilih roti favorit Anda yang dibuat dengan cinta dan bahan berkualitas</p>
        </div>
        
        <?php if (empty($produk_list)): ?>
            <div class="empty-state">
                <h3>Belum ada produk tersedia</h3>
                <p>Silakan kembali lagi nanti untuk melihat produk terbaru kami</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($produk_list as $produk): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php
                            $emoji = '🍞';
                            if (strpos(strtolower($produk['nama_produk']), 'cokelat') !== false) $emoji = '🍫';
                            elseif (strpos(strtolower($produk['nama_produk']), 'pisang') !== false) $emoji = '🍌';
                            elseif (strpos(strtolower($produk['nama_produk']), 'croissant') !== false) $emoji = '🥐';
                            elseif (strpos(strtolower($produk['nama_produk']), 'donat') !== false) $emoji = '🍩';
                            echo $emoji;
                            ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($produk['nama_produk']); ?></div>
                            <div class="product-description"><?php echo htmlspecialchars($produk['deskripsi']); ?></div>
                            <div class="product-price"><?php echo formatRupiah($produk['harga']); ?></div>
                            <div class="product-stock">Stok: <?php echo $produk['stok']; ?> buah</div>
                            <div class="product-actions">
                                <a href="produk_detail.php?id=<?php echo $produk['id']; ?>" class="btn btn-outline">Detail</a>
                                <?php if (isLoggedIn()): ?>
                                    <button onclick="addToCart(<?php echo $produk['id']; ?>)" class="btn btn-primary">+ Keranjang</button>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login untuk Beli</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
            <p>&copy; 2024 Toko Roti Emak. Dibuat dengan ❤️ untuk keluarga Indonesia.</p>
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
    </script>
</body>
</html>