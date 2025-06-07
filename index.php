<?php
require_once 'config.php';

// Get featured products (limit 6)
$conn = getConnection();
$result = $conn->query("SELECT * FROM produk WHERE stok > 0 ORDER BY created_at DESC LIMIT 6");
$featured_products = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Roti Emak - Roti Segar Setiap Hari</title>
    <style>
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(139, 69, 19, 0.8), rgba(210, 105, 30, 0.8)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f5e6d3" width="1200" height="600"/><circle fill="%23e6d7c1" cx="200" cy="200" r="100" opacity="0.5"/><circle fill="%23dac5a8" cx="800" cy="150" r="80" opacity="0.4"/><circle fill="%23ceb48f" cx="1000" cy="400" r="120" opacity="0.3"/></svg>');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 4rem 2rem;
        }

        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
            font-weight: 500;
        }

        .btn-primary {
            background: #fff;
            color: #8B4513;
        }

        .btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: #8B4513;
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            color: #8B4513;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: #8B4513;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #666;
        }

        /* Products Section */
        .products-section {
            background: white;
            padding: 4rem 2rem;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .products-section h2 {
            text-align: center;
            color: #8B4513;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #f0e68c, #daa520);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #8B4513;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 1rem;
        }

        .more-products {
            text-align: center;
        }

        /* Footer */
        footer {
            background: #8B4513;
            color: white;
            text-align: center;
            padding: 2rem;
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

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-cta {
                flex-direction: column;
                align-items: center;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
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

    <section class="hero">
        <div class="hero-content">
            <h1>🍞 Selamat Datang di Toko Roti Emak</h1>
            <p>Nikmati kelezatan roti segar yang dipanggang dengan cinta setiap hari. Dibuat dari bahan-bahan berkualitas untuk keluarga tercinta.</p>
            <div class="hero-cta">
                <a href="produk.php" class="btn btn-primary">Lihat Produk</a>
                <a href="tentang.php" class="btn btn-outline">Tentang Kami</a>
            </div>
        </div>
    </section>

    <section class="features">
        <h2>Mengapa Pilih Toko Roti Emak?</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🥖</div>
                <h3>Segar Setiap Hari</h3>
                <p>Roti dipanggang fresh setiap pagi dengan resep turun temurun yang terjaga kualitasnya</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🌾</div>
                <h3>Bahan Berkualitas</h3>
                <p>Menggunakan tepung terbaik dan bahan-bahan pilihan tanpa pengawet berbahaya</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">❤️</div>
                <h3>Dibuat dengan Cinta</h3>
                <p>Setiap roti dibuat dengan penuh perhatian dan kasih sayang untuk keluarga Indonesia</p>
            </div>
        </div>
    </section>

    <section class="products-section">
        <div class="products-container">
            <h2>Produk Unggulan</h2>
            <?php if (!empty($featured_products)): ?>
                <div class="product-grid">
                    <?php foreach ($featured_products as $produk): ?>
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
                                <div class="product-price"><?php echo formatRupiah($produk['harga']); ?></div>
                                <a href="produk_detail.php?id=<?php echo $produk['id']; ?>" class="btn btn-primary">Lihat Detail</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="more-products">
                <a href="produk.php" class="btn btn-primary">Lihat Semua Produk</a>
            </div>
        </div>
    </section>

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