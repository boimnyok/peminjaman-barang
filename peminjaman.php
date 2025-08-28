<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

// Ambil data barang yang tersedia
$query = "SELECT * FROM barang WHERE status = 'tersedia' ORDER BY nama_barang";
$stmt = $db->prepare($query);
$stmt->execute();
$barang_tersedia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses peminjaman
if (isset($_POST['ajukan'])) {
    $kode_peminjaman = "PJM" . date('YmdHis');
    $nama_peminjam = $_POST['nama_peminjam'];
    $nomor_identitas = $_POST['nomor_identitas'];
    $instansi = $_POST['instansi'];
    $no_telepon = $_POST['no_telepon'];
    $email = $_POST['email'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $catatan = $_POST['catatan'];
    $barang_dipinjam = $_POST['barang_dipinjam'];
    
    // Simpan data peminjaman
    $query = "INSERT INTO peminjaman (kode_peminjaman, nama_peminjam, nomor_identitas, instansi, no_telepon, email, tanggal_pinjam, tanggal_kembali, catatan) 
              VALUES (:kode_peminjaman, :nama_peminjam, :nomor_identitas, :instansi, :no_telepon, :email, :tanggal_pinjam, :tanggal_kembali, :catatan)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':kode_peminjaman', $kode_peminjaman);
    $stmt->bindParam(':nama_peminjam', $nama_peminjam);
    $stmt->bindParam(':nomor_identitas', $nomor_identitas);
    $stmt->bindParam(':instansi', $instansi);
    $stmt->bindParam(':no_telepon', $no_telepon);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':tanggal_pinjam', $tanggal_pinjam);
    $stmt->bindParam(':tanggal_kembali', $tanggal_kembali);
    $stmt->bindParam(':catatan', $catatan);
    
    if ($stmt->execute()) {
        $id_peminjaman = $db->lastInsertId();
        
        // Simpan detail peminjaman
        foreach ($barang_dipinjam as $id_barang) {
            $query = "INSERT INTO detail_peminjaman (id_peminjaman, id_barang) VALUES (:id_peminjaman, :id_barang)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_peminjaman', $id_peminjaman);
            $stmt->bindParam(':id_barang', $id_barang);
            $stmt->execute();
            
            // Update status barang menjadi dipinjam
            $query = "UPDATE barang SET status = 'dipinjam' WHERE id = :id_barang";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_barang', $id_barang);
            $stmt->execute();
        }
        
        $success = "Peminjaman berhasil diajukan dengan kode: " . $kode_peminjaman;
    } else {
        $error = "Gagal mengajukan peminjaman!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman - Aplikasi Peminjaman Barang</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include "components/header.php"; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include "components/sidebar.php"; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Formulir Peminjaman Barang</h1>
                </div>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Data Peminjam</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="nama_peminjam" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" id="nama_peminjam" name="nama_peminjam" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nomor_identitas" class="form-label">Nomor Identitas (KTP/NIM/NIP)</label>
                                        <input type="text" class="form-control" id="nomor_identitas" name="nomor_identitas" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="instansi" class="form-label">Instansi</label>
                                        <input type="text" class="form-control" id="instansi" name="instansi">
                                    </div>
                                    <div class="mb-3">
                                        <label for="no_telepon" class="form-label">No. Telepon</label>
                                        <input type="tel" class="form-control" id="no_telepon" name="no_telepon" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Detail Peminjaman</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="tanggal_pinjam" class="form-label">Tanggal Pinjam</label>
                                        <input type="date" class="form-control" id="tanggal_pinjam" name="tanggal_pinjam" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tanggal_kembali" class="form-label">Tanggal Kembali</label>
                                        <input type="date" class="form-control" id="tanggal_kembali" name="tanggal_kembali" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="barang_dipinjam" class="form-label">Pilih Barang</label>
                                        <select multiple class="form-select" id="barang_dipinjam" name="barang_dipinjam[]" required>
                                            <?php foreach ($barang_tersedia as $barang): ?>
                                            <option value="<?php echo $barang['id']; ?>">
                                                <?php echo $barang['nama_barang'] . " (" . $barang['kode_inventaris'] . ")"; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Gunakan Ctrl untuk memilih multiple barang</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="catatan" class="form-label">Catatan</label>
                                        <textarea class="form-control" id="catatan" name="catatan" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="ajukan" class="btn btn-primary btn-">Ajukan Peminjaman</button>
                        <a href="index.php" class="btn btn-secondary">
                            <span data-feather="arrow-left"></span> Kembali ke Dashboard
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>