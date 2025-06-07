<?php
require_once 'config.php'; // This must be the very first line.
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Toko Roti Emak</title>
    <style>
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

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #8B4513;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .about-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 2.5rem;
            text-align: justify;
        }

        .about-content h2 {
            color: #D2691E;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            text-align: center;
        }

        .about-content p {
            margin-bottom: 1.5rem;
            color: #444;
            line-height: 1.7;
        }

        .about-content ul {
            list-style: none;
            margin-bottom: 1.5rem;
            padding-left: 0;
        }

        .about-content ul li {
            background: #fffaf0;
            border-left: 4px solid #D2691E;
            padding: 0.8rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            color: #555;
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

            .page-header h1 {
                font-size: 2rem;
            }

            main {
                padding: 0 1rem;
            }

            .about-content {
                padding: 1.5rem;
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
        <div class="page-header">
            <h1>Tentang Toko Roti Emak</h1>
            <p>Kisah di balik setiap roti yang kami sajikan</p>
        </div>

        <div class="about-content">
            <h2>Dedikasi pada Kualitas dan Rasa</h2>
            <p>Toko Roti Emak didirikan dengan satu tujuan utama: menyajikan roti berkualitas terbaik yang dibuat dengan bahan-bahan segar dan cinta. Berawal dari dapur rumah tangga Emak yang selalu harum dengan aroma roti baru, kami tumbuh menjadi toko roti yang dicintai masyarakat.</p>
            <p>Kami percaya bahwa roti yang baik dimulai dari bahan yang baik. Oleh karena itu, kami hanya menggunakan tepung pilihan, mentega asli, dan bahan-bahan alami lainnya. Setiap proses pembuatan roti kami dilakukan secara tradisional, dengan sentuhan modern untuk menjaga kebersihan dan konsistensi.</p>
            
            <h2>Filosofi Kami</h2>
            <ul>
                <li><strong>Kualitas Tanpa Kompromi:</strong> Kami tidak pernah mengorbankan kualitas demi kuantitas. Setiap roti adalah mahakarya.</li>
                <li><strong>Segar Setiap Hari:</strong> Roti kami dipanggang setiap pagi, memastikan Anda selalu mendapatkan roti yang paling segar.</li>
                <li><strong>Rasa Autentik:</strong> Kami mempertahankan resep asli yang telah teruji waktu, menghadirkan rasa otentik yang membangkitkan nostalgia.</li>
                <li><strong>Komitmen pada Pelanggan:</strong> Kepuasan Anda adalah prioritas kami. Kami selalu mendengarkan masukan untuk terus berinovasi.</li>
            </ul>

            <p>Terima kasih telah menjadi bagian dari keluarga Toko Roti Emak. Kami berharap setiap gigitan roti kami membawa kehangatan dan kebahagiaan untuk Anda dan keluarga.</p>
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
            <p>© 2024 Toko Roti Emak. Dibuat dengan ❤️ untuk keluarga Indonesia.</p>
        </div>
    </footer>
</body>
</html>