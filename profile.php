<?php
require_once 'config.php'; // Pastikan ini baris pertama

// Periksa apakah pengguna sudah login
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$conn = getConnection();

// Ambil data pengguna saat ini, termasuk kolom baru
$stmt = $conn->prepare("SELECT nama, email, alamat, nomor_telepon, foto_profil, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_nama = trim($_POST['nama']);
    $new_email = trim($_POST['email']);
    $new_alamat = trim($_POST['alamat']);
    $new_nomor_telepon = trim($_POST['nomor_telepon']);
    $current_password = $_POST['current_password']; // Untuk validasi keamanan

    // Inisialisasi variabel untuk foto profil
    $foto_profil_filename = $user_data['foto_profil']; // Default ke foto yang sudah ada

    // Validasi input
    if (empty($new_nama) || empty($new_email) || empty($new_alamat) || empty($new_nomor_telepon)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Verifikasi password saat ini
        if (!password_verify($current_password, $user_data['password'])) {
            $error = 'Password saat ini salah!';
        } else {
            // Tangani upload foto profil
            if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == UPLOAD_ERR_OK) {
                $target_dir = "uploads/profiles/"; // Folder untuk menyimpan foto profil
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true); // Buat folder jika belum ada
                }

                $imageFileType = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                $max_size = 5 * 1024 * 1024; // 5 MB

                if (!in_array($imageFileType, $allowed_types)) {
                    $error = "Hanya file JPG, JPEG, PNG, & GIF yang diizinkan untuk foto profil.";
                } elseif ($_FILES['foto_profil']['size'] > $max_size) {
                    $error = "Ukuran foto profil maksimal 5MB.";
                } else {
                    $foto_profil_filename = uniqid('profile_') . '.' . $imageFileType; // Nama unik
                    $target_file = $target_dir . $foto_profil_filename;

                    if (!move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                        $error = "Gagal mengunggah foto profil.";
                    } else {
                        // Hapus foto lama jika ada dan bukan default
                        if ($user_data['foto_profil'] && file_exists($target_dir . $user_data['foto_profil']) && $user_data['foto_profil'] !== 'default.jpg') {
                            unlink($target_dir . $user_data['foto_profil']);
                        }
                    }
                }
            }

            if (empty($error)) { // Lanjutkan jika tidak ada error dari upload
                // Periksa apakah email baru sudah terdaftar oleh pengguna lain
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $new_email, $user_id);
                $stmt->execute();
                $email_check_result = $stmt->get_result();
                $stmt->close();

                if ($email_check_result->num_rows > 0) {
                    $error = 'Email baru sudah digunakan oleh akun lain!';
                } else {
                    // Update data pengguna
                    $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, alamat = ?, nomor_telepon = ?, foto_profil = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $new_nama, $new_email, $new_alamat, $new_nomor_telepon, $foto_profil_filename, $user_id);

                    if ($stmt->execute()) {
                        // Perbarui session setelah perubahan berhasil
                        $_SESSION['nama'] = $new_nama;
                        $_SESSION['email'] = $new_email;
                        $success = 'Profil berhasil diperbarui!';
                        // Muat ulang data pengguna setelah update berhasil
                        $user_data['nama'] = $new_nama;
                        $user_data['email'] = $new_email;
                        $user_data['alamat'] = $new_alamat;
                        $user_data['nomor_telepon'] = $new_nomor_telepon;
                        $user_data['foto_profil'] = $foto_profil_filename;
                    } else {
                        $error = 'Terjadi kesalahan saat memperbarui profil: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$conn->close();

// Pastikan direktori uploads/profiles ada
$upload_dir = 'uploads/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Akun - Toko Roti Emak</title>
    <style>
        /* Gaya dasar - disalin dari file lain untuk konsistensi */
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
        
        main { max-width: 600px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 0.5rem; }

        /* Gaya Khusus Profil */
        .profile-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .profile-picture {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .profile-picture img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #D2691E;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="password"], textarea {
            width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;
            font-size: 1rem; transition: border-color 0.3s;
        }
        input[type="text"]:focus, input[type="email"]:focus, input[type="tel"]:focus, input[type="password"]:focus, textarea:focus {
            outline: none; border-color: #D2691E;
        }
        textarea { resize: vertical; min-height: 80px; }
        input[type="file"] {
            width: 100%;
            padding: 0.75rem 0; /* Padding vertikal saja */
            border: none; /* Hilangkan border asli input file */
            background-color: transparent;
        }


        .btn {
            width: 100%; padding: 0.75rem; background: #D2691E; color: white;
            border: none; border-radius: 8px; font-size: 1rem; cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover { background: #B8860B; }

        .alert {
            padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;
        }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #363; border: 1px solid #cfc; }

        /* Footer */
        footer { background: #8B4513; color: white; text-align: center; padding: 2rem; margin-top: 2rem; }
        .footer-content { max-width: 1200px; margin: 0 auto; }
        .footer-links { display: flex; justify-content: center; gap: 2rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .footer-links a { color: white; text-decoration: none; }
        .footer-links a:hover { color: #f0e68c; }

        /* Responsif */
        @media (max-width: 480px) {
            main { margin: 1rem; padding: 0 1rem; }
            .profile-container { padding: 1.5rem; }
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
                    <a href="profile.php">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</a>
                    <a href="logout.php">Keluar</a>
                <?php else: ?>
                    <a href="login.php">Masuk</a>
                    <a href="register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1>Profil Akun Anda</h1>
            <p>Kelola informasi pribadi Anda</p>
        </div>

        <div class="profile-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="profile-picture">
                <img src="<?php echo ($user_data['foto_profil'] && file_exists($upload_dir . $user_data['foto_profil'])) ? $upload_dir . htmlspecialchars($user_data['foto_profil']) : $upload_dir . 'default.png'; ?>" alt="Foto Profil">
                    <br>
                    <label for="foto_profil">Ubah Foto Profil:</label>
                    <input type="file" id="foto_profil" name="foto_profil" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user_data['nama']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat" required><?php echo htmlspecialchars($user_data['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="nomor_telepon">Nomor Telepon</label>
                    <input type="tel" id="nomor_telepon" name="nomor_telepon" value="<?php echo htmlspecialchars($user_data['nomor_telepon'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="current_password">Password Saat Ini (untuk konfirmasi perubahan)</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <button type="submit" class="btn">Perbarui Profil</button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <p><a href="index.php" style="color: #D2691E; text-decoration: none;">← Kembali ke Beranda</a></p>
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
            <p>© 2024 Toko Roti Emak. Dibuat dengan ❤️ untuk keluarga Indonesia.</p>
        </div>
    </footer>
</body>
</html>