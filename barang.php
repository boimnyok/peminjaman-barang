<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

// Tambah barang
if (isset($_POST['tambah'])) {
    $kode_inventaris = $_POST['kode_inventaris'];
    $nama_barang = $_POST['nama_barang'];
    $deskripsi = $_POST['deskripsi'];
    $kategori = $_POST['kategori'];
    $status = $_POST['status'];
    
    // Upload gambar
    $gambar = "";
    if (!empty($_FILES['gambar']['name'])) {
        $target_dir = "uploads/";
        $gambar = $target_dir . basename($_FILES["gambar"]["name"]);
        move_uploaded_file($_FILES["gambar"]["tmp_name"], $gambar);
    }
    
    $query = "INSERT INTO barang (kode_inventaris, nama_barang, deskripsi, kategori, status, gambar) 
              VALUES (:kode_inventaris, :nama_barang, :deskripsi, :kategori, :status, :gambar)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':kode_inventaris', $kode_inventaris);
    $stmt->bindParam(':nama_barang', $nama_barang);
    $stmt->bindParam(':deskripsi', $deskripsi);
    $stmt->bindParam(':kategori', $kategori);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':gambar', $gambar);
    
    if ($stmt->execute()) {
        $success = "Barang berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan barang!";
    }
}

// Hapus barang
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    $query = "DELETE FROM barang WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $success = "Barang berhasil dihapus!";
    } else {
        $error = "Gagal menghapus barang!";
    }
}

// Ambil data barang
$query = "SELECT * FROM barang ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$barang = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang - Aplikasi Peminjaman Barang</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include "components/header.php"; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include "components/sidebar.php"; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manajemen Barang</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
                        Tambah Barang
                    </button>
                </div>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kode Inventaris</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($barang) > 0): ?>
                            <?php foreach ($barang as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $item['kode_inventaris']; ?></td>
                                <td><?php echo $item['nama_barang']; ?></td>
                                <td><?php echo $item['kategori']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                    switch($item['status']) {
                                        case 'tersedia': echo 'success'; break;
                                        case 'dipinjam': echo 'warning'; break;
                                        case 'rusak': echo 'danger'; break;
                                        case 'hilang': echo 'dark'; break;
                                        default: echo 'secondary';
                                    }
                                    ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="barang_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="barang.php?hapus=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data barang</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal Tambah Barang -->
    <div class="modal fade" id="tambahBarangModal" tabindex="-1" aria-labelledby="tambahBarangModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahBarangModalLabel">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="kode_inventaris" class="form-label">Kode Inventaris</label>
                            <input type="text" class="form-control" id="kode_inventaris" name="kode_inventaris" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_barang" class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" id="nama_barang" name="nama_barang" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <input type="text" class="form-control" id="kategori" name="kategori">
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="tersedia">Tersedia</option>
                                <option value="dipinjam">Dipinjam</option>
                                <option value="rusak">Rusak</option>
                                <option value="hilang">Hilang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="gambar" class="form-label">Gambar</label>
                            <input class="form-control" type="file" id="gambar" name="gambar" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>