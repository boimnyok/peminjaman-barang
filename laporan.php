<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

// Filter laporan
$filter_tanggal = isset($_GET['filter_tanggal']) ? $_GET['filter_tanggal'] : '';
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';

// Query dengan filter
$query = "SELECT p.*, COUNT(d.id) as jumlah_barang 
          FROM peminjaman p 
          LEFT JOIN detail_peminjaman d ON p.id = d.id_peminjaman 
          WHERE 1=1";

$params = array();

if (!empty($filter_tanggal)) {
    $query .= " AND DATE(p.created_at) = :filter_tanggal";
    $params[':filter_tanggal'] = $filter_tanggal;
}

if (!empty($filter_status)) {
    $query .= " AND p.status = :filter_status";
    $params[':filter_status'] = $filter_status;
}

$query .= " GROUP BY p.id ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman - Aplikasi Peminjaman Barang</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include "components/header.php"; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include "components/sidebar.php"; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Laporan Peminjaman</h1>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filter Laporan</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="filter_tanggal" class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" id="filter_tanggal" name="filter_tanggal" value="<?php echo $filter_tanggal; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="filter_status" class="form-label">Status</label>
                                        <select class="form-select" id="filter_status" name="filter_status">
                                            <option value="">Semua Status</option>
                                            <option value="menunggu" <?php echo ($filter_status == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="disetujui" <?php echo ($filter_status == 'disetujui') ? 'selected' : ''; ?>>Disetujui</option>
                                            <option value="ditolak" <?php echo ($filter_status == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                            <option value="selesai" <?php echo ($filter_status == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 align-self-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="laporan.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kode Peminjaman</th>
                                <th>Nama Peminjam</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Jumlah Barang</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($peminjaman) > 0): ?>
                            <?php foreach ($peminjaman as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $item['kode_peminjaman']; ?></td>
                                <td><?php echo $item['nama_peminjam']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['tanggal_pinjam'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($item['tanggal_kembali'])); ?></td>
                                <td><?php echo $item['jumlah_barang']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                    switch($item['status']) {
                                        case 'menunggu': echo 'warning'; break;
                                        case 'disetujui': echo 'success'; break;
                                        case 'ditolak': echo 'danger'; break;
                                        case 'selesai': echo 'info'; break;
                                        default: echo 'secondary';
                                    }
                                    ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="peminjaman_detail.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data peminjaman</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>