<?php
require_once '../config.php';

// Periksa apakah pengguna sudah login dan adalah admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();
$error = '';
$success = '';
$product_data = [
    'id' => null,
    'nama_produk' => '',
    'deskripsi' => '',
    'harga' => '',
    'stok' => '',
    'gambar' => ''
];
$is_edit = false;

// Jika ada ID produk di URL, ini adalah mode edit
if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $is_edit = true;
    $product_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product_data = $result->fetch_assoc();
    } else {
        $error = "Produk tidak ditemukan.";
        $is_edit = false; // Kembali ke mode tambah jika tidak ditemukan
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = (float)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $current_gambar = $_POST['current_gambar'] ?? ''; // Gambar lama jika ada

    // Validasi input
    if (empty($nama_produk) || empty($deskripsi) || $harga <= 0 || $stok < 0) {
        $error = 'Nama produk, deskripsi, harga, dan stok tidak boleh kosong dan harus valid.';
    } else {
        $gambar_filename = $current_gambar; // Default ke gambar lama

        // Tangani upload gambar produk
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "../uploads/products/"; // Folder untuk menyimpan gambar produk
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $imageFileType = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5 MB

            if (!in_array($imageFileType, $allowed_types)) {
                $error = "Hanya file JPG, JPEG, PNG, & GIF yang diizinkan untuk gambar produk.";
            } elseif ($_FILES['gambar']['size'] > $max_size) {
                $error = "Ukuran gambar produk maksimal 5MB.";
            } else {
                $gambar_filename = uniqid('product_') . '.' . $imageFileType;
                $target_file = $target_dir . $gambar_filename;

                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                    $error = "Gagal mengunggah gambar produk.";
                } else {
                    // Hapus gambar lama jika ada dan bukan default
                    if ($current_gambar && file_exists($target_dir . $current_gambar) && $current_gambar !== 'placeholder.jpg') {
                        unlink($target_dir . $current_gambar);
                    }
                }
            }
        }

        if (empty($error)) {
            if ($is_edit) {
                // Update produk
                $stmt = $conn->prepare("UPDATE produk SET nama_produk = ?, deskripsi = ?, harga = ?, stok = ?, gambar = ? WHERE id = ?");
                $stmt->bind_param("ssdisi", $nama_produk, $deskripsi, $harga, $stok, $gambar_filename, $product_data['id']);
            } else {
                // Tambah produk baru
                $stmt = $conn->prepare("INSERT INTO produk (nama_produk, deskripsi, harga, stok, gambar) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdis", $nama_produk, $deskripsi, $harga, $stok, $gambar_filename);
            }

            if ($stmt->execute()) {
                $success = 'Produk berhasil ' . ($is_edit ? 'diperbarui' : 'ditambahkan') . '!';
                // Redirect ke daftar produk setelah sukses
                redirect('produk_manage.php');
            } else {
                $error = 'Terjadi kesalahan saat ' . ($is_edit ? 'memperbarui' : 'menambah') . ' produk: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Jika ada error, populate data form lagi untuk tampilan
    $product_data['nama_produk'] = $nama_produk;
    $product_data['deskripsi'] = $deskripsi;
    $product_data['harga'] = $harga;
    $product_data['stok'] = $stok;
    $product_data['gambar'] = $gambar_filename;
}

$conn->close();

// Pastikan direktori uploads/products ada
$upload_dir = '../uploads/products/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Produk' : 'Tambah Produk'; ?> - Dashboard Admin</title>
    <style>
        /* Gaya dasar dari dashboard admin (salin dari admin/dashboard.php) */
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
        
        main { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { color: #8B4513; font-size: 2.5rem; margin-bottom: 0.5rem; }

        /* Gaya Form */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500; }
        input[type="text"], input[type="number"], textarea {
            width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px;
            font-size: 1rem; transition: border-color 0.3s;
        }
        input[type="text"]:focus, input[type="number"]:focus, textarea:focus {
            outline: none; border-color: #D2691E;
        }
        textarea { resize: vertical; min-height: 100px; }
        input[type="file"] {
            width: 100%;
            padding: 0.75rem 0;
            border: none;
            background-color: transparent;
        }
        .current-image {
            text-align: center;
            margin-bottom: 1rem;
        }
        .current-image img {
            max-width: 200px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .button-container {
            display:flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            gap: 1rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            text-decoration: none;
            padding: 12px 24px; 
            gap: 8px;
            background: #D2691E; 
            color: white;
            border: none; 
            border-radius: 12px; 
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            white-space: nowrap;
        }  
        .btn:hover { background: #B8860B; }
        .btn-back {
            background: #6c757d;
        }
        .btn-back svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
        }
        
        .btn-back:hover { background: #5a6268; }

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

        /* Responsive */
        @media (max-width: 768px) {
            nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-links { gap: 1rem; }
            .page-header h1 { font-size: 2rem; }
            main { padding: 0 1rem; }
            .form-container { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">🍞 Toko Roti Emak (Admin)</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="produk_manage.php">Kelola Produk</a></li>
                <li><a href="users_manage.php">Kelola User</a></li>
                <li><a href="orders_manage.php">Kelola Pesanan</a></li>
            </ul>
            <div class="nav-user">
                <span>Halo, Admin <?php echo $_SESSION['nama']; ?>!</span>
                <a href="../logout.php">Keluar</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1><?php echo $is_edit ? 'Edit Produk' : 'Tambah Produk Baru'; ?></h1>
            <p>Isi detail produk di bawah ini</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="current_gambar" value="<?php echo htmlspecialchars($product_data['gambar']); ?>">

                <?php if ($product_data['gambar']): ?>
                    <div class="current-image">
                        <p>Gambar Saat Ini:</p>
                        <img src="../uploads/products/<?php echo htmlspecialchars($product_data['gambar']); ?>" alt="Gambar Produk">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="gambar">Upload Gambar Produk (JPG, JPEG, PNG, GIF, maks 5MB)</label>
                    <input type="file" id="gambar" name="gambar" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="nama_produk">Nama Produk</label>
                    <input type="text" id="nama_produk" name="nama_produk" value="<?php echo htmlspecialchars($product_data['nama_produk']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" required><?php echo htmlspecialchars($product_data['deskripsi']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="harga">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" step="0.01" min="0" value="<?php echo htmlspecialchars($product_data['harga']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="stok">Stok</label>
                    <input type="number" id="stok" name="stok" min="0" value="<?php echo htmlspecialchars($product_data['stok']); ?>" required>
                </div>
                <div class="button-container">
                    <a href="produk_manage.php" class="btn btn-back">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>Kembali</a>
                    <button type="submit" class="btn"><?php echo $is_edit ? 'Perbarui Produk' : 'Tambah Produk'; ?></button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="dashboard.php">Dashboard Admin</a>
                <a href="../index.php">Kembali ke Situs</a>
            </div>
            <p>© 2024 Toko Roti Emak. Dibuat dengan cinta untuk keluarga Indonesia.</p>
        </div>
    </footer>
</body>
</html>