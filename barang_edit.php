<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

// Inisialisasi variabel
$error = '';
$success = '';
$barang = null;

// Ambil data barang berdasarkan ID
$id = $_GET['id'] ?? 0;
if (!$id) {
    header("Location: barang.php");
    exit();
}

$query = "SELECT * FROM barang WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 1) {
    $barang = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $error = "Barang tidak ditemukan!";
    header("Location: barang.php");
    exit();
}

// Proses update data barang
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $kode_inventaris = $_POST['kode_inventaris'];
        $nama_barang = $_POST['nama_barang'];
        $deskripsi = $_POST['deskripsi'];
        $kategori = $_POST['kategori'];
        $status = $_POST['status'];
        
        // Cek apakah kode inventaris sudah digunakan oleh barang lain
        $query = "SELECT id FROM barang WHERE kode_inventaris = :kode_inventaris AND id != :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':kode_inventaris', $kode_inventaris);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Kode inventaris sudah digunakan oleh barang lain!";
        } else {
            // Handle upload gambar
            $gambar = $barang['gambar']; // Default ke gambar lama
            
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
                $target_dir = "uploads/";
                
                // Pastikan folder uploads ada
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                // Hapus gambar lama jika ada
                if (!empty($barang['gambar']) && file_exists($barang['gambar'])) {
                    unlink($barang['gambar']);
                }
                
                // Upload gambar baru
                $file_extension = pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION);
                $new_filename = "barang_" . time() . "." . $file_extension;
                $gambar = $target_dir . $new_filename;
                
                // Validasi tipe file
                $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $gambar)) {
                        // File berhasil diupload
                    } else {
                        $error = "Maaf, terjadi kesalahan saat mengupload gambar.";
                        $gambar = $barang['gambar'];
                    }
                } else {
                    $error = "Hanya file JPG, JPEG, PNG, dan GIF yang diizinkan.";
                    $gambar = $barang['gambar'];
                }
            }
            
            // Update data barang
            $query = "UPDATE barang 
                      SET kode_inventaris = :kode_inventaris, 
                          nama_barang = :nama_barang, 
                          deskripsi = :deskripsi, 
                          kategori = :kategori, 
                          status = :status, 
                          gambar = :gambar 
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':kode_inventaris', $kode_inventaris);
            $stmt->bindParam(':nama_barang', $nama_barang);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':kategori', $kategori);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':gambar', $gambar);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $success = "Data barang berhasil diperbarui!";
                
                // Refresh data barang
                $query = "SELECT * FROM barang WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $barang = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Gagal memperbarui data barang!";
            }
        }
    }
    
    // Proses hapus gambar
    if (isset($_POST['hapus_gambar'])) {
        // Hapus file gambar dari server
        if (!empty($barang['gambar']) && file_exists($barang['gambar'])) {
            unlink($barang['gambar']);
        }
        
        // Update database untuk menghapus referensi gambar
        $query = "UPDATE barang SET gambar = '' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $success = "Gambar berhasil dihapus!";
            $barang['gambar'] = '';
        } else {
            $error = "Gagal menghapus gambar!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - Aplikasi Peminjaman Barang</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <style>
        .img-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .card-header {
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #224abe, #4e73df);
        }
    </style>
</head>
<body>
    <?php include "components/header.php"; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include "components/sidebar.php"; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Data Barang</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="barang.php" class="btn btn-secondary">
                            <span data-feather="arrow-left"></span> Kembali
                        </a>
                    </div>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($barang): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Form Edit Barang</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $barang['id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="kode_inventaris" class="form-label">Kode Inventaris <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="kode_inventaris" name="kode_inventaris" 
                                               value="<?php echo htmlspecialchars($barang['kode_inventaris']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="nama_barang" class="form-label">Nama Barang <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                                               value="<?php echo htmlspecialchars($barang['nama_barang']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="kategori" class="form-label">Kategori</label>
                                        <input type="text" class="form-control" id="kategori" name="kategori" 
                                               value="<?php echo htmlspecialchars($barang['kategori']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="tersedia" <?php echo $barang['status'] == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                            <option value="dipinjam" <?php echo $barang['status'] == 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                                            <option value="rusak" <?php echo $barang['status'] == 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                                            <option value="hilang" <?php echo $barang['status'] == 'hilang' ? 'selected' : ''; ?>>Hilang</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="deskripsi" class="form-label">Deskripsi</label>
                                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5"><?php echo htmlspecialchars($barang['deskripsi']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="gambar" class="form-label">Gambar Barang</label>
                                        <input class="form-control" type="file" id="gambar" name="gambar" accept="image/*">
                                        <div class="form-text">Format: JPG, JPEG, PNG, GIF. Maksimal 2MB.</div>
                                    </div>
                                    
                                    <?php if (!empty($barang['gambar'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Gambar Saat Ini</label>
                                        <div>
                                            <img src="<?php echo $barang['gambar']; ?>" alt="Gambar Barang" class="img-preview">
                                            <button type="submit" name="hapus_gambar" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Yakin ingin menghapus gambar?')">
                                                Hapus Gambar
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="barang.php" class="btn btn-secondary me-md-2">Batal</a>
                                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Informasi tambahan -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Informasi Barang</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Dibuat Pada</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($barang['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Terakhir Diubah</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($barang['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Riwayat Peminjaman</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $query = "SELECT COUNT(*) as total FROM detail_peminjaman WHERE id_barang = :id_barang";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':id_barang', $barang['id']);
                                $stmt->execute();
                                $total_peminjaman = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                ?>
                                
                                <p class="card-text">Barang ini telah dipinjam <strong><?php echo $total_peminjaman; ?> kali</strong>.</p>
                                
                                <?php if ($total_peminjaman > 0): ?>
                                <a href="laporan.php?barang=<?php echo $barang['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Lihat Riwayat Lengkap
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <div class="alert alert-warning">
                    Data barang tidak ditemukan. <a href="barang.php">Kembali ke daftar barang</a>.
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/feather.min.js"></script>
    <script>
        // Feather Icons
        feather.replace();
        
        // Preview gambar sebelum upload
        document.getElementById('gambar').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    // Cek jika sudah ada preview gambar, jika tidak buat elemen baru
                    var previewContainer = document.querySelector('.img-preview') ? 
                        document.querySelector('.img-preview').parentNode : 
                        document.createElement('div');
                        
                    var newPreview = document.createElement('img');
                    newPreview.src = e.target.result;
                    newPreview.className = 'img-preview';
                    newPreview.alt = 'Preview Gambar';
                    
                    // Ganti atau tambahkan preview
                    var oldPreview = previewContainer.querySelector('.img-preview');
                    if (oldPreview) {
                        previewContainer.replaceChild(newPreview, oldPreview);
                    } else {
                        previewContainer.appendChild(newPreview);
                        document.querySelector('input[name="gambar"]').after(previewContainer);
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Validasi form
        document.querySelector('form').addEventListener('submit', function(e) {
            let kode = document.getElementById('kode_inventaris').value.trim();
            let nama = document.getElementById('nama_barang').value.trim();
            
            if (kode === '' || nama === '') {
                e.preventDefault();
                alert('Kode inventaris dan nama barang harus diisi!');
                return false;
            }
            
            // Validasi ukuran file
            let fileInput = document.getElementById('gambar');
            if (fileInput.files.length > 0) {
                let fileSize = fileInput.files[0].size / 1024 / 1024; // MB
                if (fileSize > 2) {
                    e.preventDefault();
                    alert('Ukuran gambar maksimal 2MB!');
                    return false;
                }
            }
        });
    </script>
</body>
</html>