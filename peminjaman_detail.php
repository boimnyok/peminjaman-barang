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
$peminjaman = null;
$barang_dipinjam = array();

// Ambil data peminjaman berdasarkan ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Query data peminjaman
    $query = "SELECT * FROM peminjaman WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Query barang yang dipinjam
        $query_barang = "SELECT b.*, d.jumlah 
                         FROM detail_peminjaman d 
                         INNER JOIN barang b ON d.id_barang = b.id 
                         WHERE d.id_peminjaman = :id_peminjaman";
        $stmt_barang = $db->prepare($query_barang);
        $stmt_barang->bindParam(':id_peminjaman', $id);
        $stmt_barang->execute();
        $barang_dipinjam = $stmt_barang->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Data peminjaman tidak ditemukan!";
    }
} else {
    header("Location: laporan.php");
    exit();
}

// Proses update status peminjaman
if (isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $query = "UPDATE peminjaman SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $success = "Status peminjaman berhasil diperbarui!";
        
        // Jika status diubah menjadi selesai, kembalikan status barang
        if ($status == 'selesai') {
            $query_barang = "SELECT id_barang FROM detail_peminjaman WHERE id_peminjaman = :id_peminjaman";
            $stmt_barang = $db->prepare($query_barang);
            $stmt_barang->bindParam(':id_peminjaman', $id);
            $stmt_barang->execute();
            $barang_ids = $stmt_barang->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($barang_ids)) {
                $placeholders = implode(',', array_fill(0, count($barang_ids), '?'));
                $query_update = "UPDATE barang SET status = 'tersedia' WHERE id IN ($placeholders)";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute($barang_ids);
            }
        }
        
        // Refresh data
        $query = "SELECT * FROM peminjaman WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Gagal memperbarui status peminjaman!";
    }
}

// Proses hapus peminjaman
if (isset($_POST['hapus_peminjaman'])) {
    $id = $_POST['id'];
    
    // Kembalikan status barang terlebih dahulu
    $query_barang = "SELECT id_barang FROM detail_peminjaman WHERE id_peminjaman = :id_peminjaman";
    $stmt_barang = $db->prepare($query_barang);
    $stmt_barang->bindParam(':id_peminjaman', $id);
    $stmt_barang->execute();
    $barang_ids = $stmt_barang->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($barang_ids)) {
        $placeholders = implode(',', array_fill(0, count($barang_ids), '?'));
        $query_update = "UPDATE barang SET status = 'tersedia' WHERE id IN ($placeholders)";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->execute($barang_ids);
    }
    
    // Hapus data peminjaman
    $query = "DELETE FROM peminjaman WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $success = "Data peminjaman berhasil dihapus!";
        header("Refresh: 2; URL=laporan.php");
    } else {
        $error = "Gagal menghapus data peminjaman!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman - Aplikasi Peminjaman Barang</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
        }
        .barang-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
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
                    <h1 class="h2">Detail Peminjaman</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="laporan.php" class="btn btn-secondary">
                            <span data-feather="arrow-left"></span> Kembali ke Laporan
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
                
                <?php if ($peminjaman): ?>
                <!-- Informasi Peminjaman -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Informasi Peminjaman</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">Kode Peminjaman:</span>
                                    <h4><?php echo htmlspecialchars($peminjaman['kode_peminjaman']); ?></h4>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Status:</span>
                                    <?php
                                    $badge_class = '';
                                    switch($peminjaman['status']) {
                                        case 'menunggu': $badge_class = 'bg-warning'; break;
                                        case 'disetujui': $badge_class = 'bg-success'; break;
                                        case 'ditolak': $badge_class = 'bg-danger'; break;
                                        case 'selesai': $badge_class = 'bg-info'; break;
                                        default: $badge_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge status-badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($peminjaman['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Tanggal Pengajuan:</span>
                                    <?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">Tanggal Pinjam:</span>
                                    <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Tanggal Kembali:</span>
                                    <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_kembali'])); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Lama Peminjaman:</span>
                                    <?php
                                    $tgl_pinjam = new DateTime($peminjaman['tanggal_pinjam']);
                                    $tgl_kembali = new DateTime($peminjaman['tanggal_kembali']);
                                    $selisih = $tgl_pinjam->diff($tgl_kembali);
                                    echo $selisih->days . ' hari';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informasi Peminjam -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Informasi Peminjam</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">Nama Peminjam:</span>
                                    <?php echo htmlspecialchars($peminjaman['nama_peminjam']); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Nomor Identitas:</span>
                                    <?php echo htmlspecialchars($peminjaman['nomor_identitas']); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Instansi:</span>
                                    <?php echo !empty($peminjaman['instansi']) ? htmlspecialchars($peminjaman['instansi']) : '-'; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">No. Telepon:</span>
                                    <?php echo htmlspecialchars($peminjaman['no_telepon']); ?>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Email:</span>
                                    <?php echo !empty($peminjaman['email']) ? htmlspecialchars($peminjaman['email']) : '-'; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="info-label">Catatan:</span>
                                    <?php echo !empty($peminjaman['catatan']) ? nl2br(htmlspecialchars($peminjaman['catatan'])) : '-'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daftar Barang Dipinjam -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Barang yang Dipinjam</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($barang_dipinjam) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Gambar</th>
                                        <th>Kode Inventaris</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($barang_dipinjam as $index => $barang): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if (!empty($barang['gambar'])): ?>
                                            <img src="<?php echo $barang['gambar']; ?>" alt="Gambar Barang" class="barang-img">
                                            <?php else: ?>
                                            <div class="barang-img bg-light d-flex align-items-center justify-content-center">
                                                <span data-feather="package" class="text-muted"></span>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($barang['kode_inventaris']); ?></td>
                                        <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                        <td><?php echo !empty($barang['kategori']) ? htmlspecialchars($barang['kategori']) : '-'; ?></td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            switch($barang['status']) {
                                                case 'tersedia': $badge_class = 'bg-success'; break;
                                                case 'dipinjam': $badge_class = 'bg-warning'; break;
                                                case 'rusak': $badge_class = 'bg-danger'; break;
                                                case 'hilang': $badge_class = 'bg-dark'; break;
                                                default: $badge_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($barang['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $barang['jumlah']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Tidak ada barang yang dipinjam.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Form Aksi -->
                <div class="card action-buttons">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">Aksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Ubah Status Peminjaman</h6>
                                <form method="POST" action="" class="row g-3">
                                    <input type="hidden" name="id" value="<?php echo $peminjaman['id']; ?>">
                                    <div class="col-md-8">
                                        <select class="form-select" name="status" required>
                                            <option value="menunggu" <?php echo $peminjaman['status'] == 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="disetujui" <?php echo $peminjaman['status'] == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                                            <option value="ditolak" <?php echo $peminjaman['status'] == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                            <option value="selesai" <?php echo $peminjaman['status'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <h6>Hapus Peminjaman</h6>
                                    <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menghapus peminjaman ini? Tindakan ini tidak dapat dibatalkan.');">
                                        <input type="hidden" name="id" value="<?php echo $peminjaman['id']; ?>">
                                            <button type="submit" name="hapus_peminjaman" class="btn btn-danger w-100 mb-2">
                                                <span data-feather="trash-2"></span> Hapus Peminjaman
                                            </button>
                                    </form>
                             
                                    <!-- Tombol Cetak -->
                                    <a href="cetak_peminjaman.php?id=<?php echo $peminjaman['id']; ?>" target="_blank" class="btn btn-success w-100">
                                    <span data-feather="printer"></span> Cetak Laporan
                                    </a>
                            </div>

                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <div class="alert alert-warning">
                    Data peminjaman tidak ditemukan. <a href="laporan.php">Kembali ke daftar peminjaman</a>.
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
        
        // Alert otomatis tutup setelah 5 detik
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>