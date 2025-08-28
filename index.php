<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

// Hitung total barang
$query = "SELECT COUNT(*) as total FROM barang";
$stmt = $db->prepare($query);
$stmt->execute();
$total_barang = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Hitung total peminjaman
$query = "SELECT COUNT(*) as total FROM peminjaman";
$stmt = $db->prepare($query);
$stmt->execute();
$total_peminjaman = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Hitung barang dipinjam
$query = "SELECT COUNT(*) as total FROM barang WHERE status = 'dipinjam'";
$stmt = $db->prepare($query);
$stmt->execute();
$barang_dipinjam = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Hitung peminjaman menunggu
$query = "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'menunggu'";
$stmt = $db->prepare($query);
$stmt->execute();
$peminjaman_menunggu = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aplikasi Peminjaman Barang</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include "components/header.php"; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include "components/sidebar.php"; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Barang</h5>
                                <p class="card-text display-4"><?php echo $total_barang; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Peminjaman</h5>
                                <p class="card-text display-4"><?php echo $total_peminjaman; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Barang Dipinjam</h5>
                                <p class="card-text display-4"><?php echo $barang_dipinjam; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Menunggu Persetujuan</h5>
                                <p class="card-text display-4"><?php echo $peminjaman_menunggu; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Peminjaman Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $query = "SELECT p.*, COUNT(d.id) as jumlah_barang 
                                          FROM peminjaman p 
                                          LEFT JOIN detail_peminjaman d ON p.id = d.id_peminjaman 
                                          GROUP BY p.id 
                                          ORDER BY p.created_at DESC 
                                          LIMIT 5";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                if ($stmt->rowCount() > 0) {
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<div class='mb-2'>";
                                        echo "<strong>{$row['kode_peminjaman']}</strong> - {$row['nama_peminjam']}";
                                        echo "<span class='badge bg-secondary float-end'>{$row['status']}</span>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<p>Belum ada data peminjaman</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Barang Populer</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $query = "SELECT b.nama_barang, COUNT(d.id) as jumlah_pinjam 
                                          FROM barang b 
                                          INNER JOIN detail_peminjaman d ON b.id = d.id_barang 
                                          GROUP BY b.id 
                                          ORDER BY jumlah_pinjam DESC 
                                          LIMIT 5";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                if ($stmt->rowCount() > 0) {
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<div class='mb-2'>";
                                        echo "{$row['nama_barang']}";
                                        echo "<span class='badge bg-primary float-end'>{$row['jumlah_pinjam']}x</span>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<p>Belum ada data barang dipinjam</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>