<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "config/database.php";
$database = new Database();
$db = $database->getConnection();

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
        die("Data peminjaman tidak ditemukan!");
    }
} else {
    die("ID peminjaman tidak valid!");
}

// Hitung total barang
$total_barang = 0;
foreach ($barang_dipinjam as $barang) {
    $total_barang += $barang['jumlah'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Peminjaman - <?php echo $peminjaman['kode_peminjaman']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 18px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 150px;
        }
        .info-value {
            color: #333;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-success { background-color: #28a745; color: #fff; }
        .badge-danger { background-color: #dc3545; color: #fff; }
        .badge-info { background-color: #17a2b8; color: #fff; }
        .badge-secondary { background-color: #6c757d; color: #fff; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .signature {
            display: inline-block;
            width: 250px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin: 40px 0 5px 0;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        @media print {
            body {
                padding: 0;
            }
            .container {
                max-width: 100%;
            }
            .no-print {
                display: none;
            }
            .header {
                border-bottom: 2px solid #000;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>LAPORAN DETAIL PEMINJAMAN BARANG</h1>
            <p>Aplikasi Peminjaman Barang dan Alat</p>
            <p>Periode: <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) . ' - ' . date('d/m/Y', strtotime($peminjaman['tanggal_kembali'])); ?></p>
        </div>

        <!-- Informasi Peminjaman -->
        <div class="section">
            <div class="section-title">Informasi Peminjaman</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Kode Peminjaman:</span>
                    <span class="info-value"><?php echo htmlspecialchars($peminjaman['kode_peminjaman']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <?php
                        $badge_class = '';
                        switch($peminjaman['status']) {
                            case 'menunggu': $badge_class = 'badge-warning'; break;
                            case 'disetujui': $badge_class = 'badge-success'; break;
                            case 'ditolak': $badge_class = 'badge-danger'; break;
                            case 'selesai': $badge_class = 'badge-info'; break;
                            default: $badge_class = 'badge-secondary';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php echo ucfirst($peminjaman['status']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Pengajuan:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Pinjam:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Kembali:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($peminjaman['tanggal_kembali'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Lama Peminjaman:</span>
                    <span class="info-value">
                        <?php
                        $tgl_pinjam = new DateTime($peminjaman['tanggal_pinjam']);
                        $tgl_kembali = new DateTime($peminjaman['tanggal_kembali']);
                        $selisih = $tgl_pinjam->diff($tgl_kembali);
                        echo $selisih->days . ' hari';
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Informasi Peminjam -->
        <div class="section">
            <div class="section-title">Informasi Peminjam</div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nama Peminjam:</span>
                    <span class="info-value"><?php echo htmlspecialchars($peminjaman['nama_peminjam']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nomor Identitas:</span>
                    <span class="info-value"><?php echo htmlspecialchars($peminjaman['nomor_identitas']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Instansi:</span>
                    <span class="info-value"><?php echo !empty($peminjaman['instansi']) ? htmlspecialchars($peminjaman['instansi']) : '-'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">No. Telepon:</span>
                    <span class="info-value"><?php echo htmlspecialchars($peminjaman['no_telepon']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo !empty($peminjaman['email']) ? htmlspecialchars($peminjaman['email']) : '-'; ?></span>
                </div>
            </div>
            <div class="info-item">
                <span class="info-label">Catatan:</span>
                <span class="info-value"><?php echo !empty($peminjaman['catatan']) ? nl2br(htmlspecialchars($peminjaman['catatan'])) : '-'; ?></span>
            </div>
        </div>

        <!-- Daftar Barang Dipinjam -->
        <div class="section">
            <div class="section-title">Barang yang Dipinjam</div>
            <?php if (count($barang_dipinjam) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
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
                        <td><?php echo htmlspecialchars($barang['kode_inventaris']); ?></td>
                        <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                        <td><?php echo !empty($barang['kategori']) ? htmlspecialchars($barang['kategori']) : '-'; ?></td>
                        <td>
                            <?php
                            $badge_class = '';
                            switch($barang['status']) {
                                case 'tersedia': $badge_class = 'badge-success'; break;
                                case 'dipinjam': $badge_class = 'badge-warning'; break;
                                case 'rusak': $badge_class = 'badge-danger'; break;
                                case 'hilang': $badge_class = 'badge-secondary'; break;
                                default: $badge_class = 'badge-secondary';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst($barang['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $barang['jumlah']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Total Barang:</strong></td>
                        <td><strong><?php echo $total_barang; ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <p>Tidak ada barang yang dipinjam.</p>
            <?php endif; ?>
        </div>

        <!-- Footer dan Tanda Tangan -->
        <div class="footer">
            <div class="signature">
                <p>Mengetahui,</p>
                <div class="signature-line"></div>
                <p>Admin Peminjaman Barang</p>
            </div>
        </div>

        <!-- Tombol Cetak (hanya tampil di browser) -->
        <div class="no-print text-center" style="margin-top: 30px;">
            <button onclick="window.print()" class="btn btn-primary" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Cetak Laporan
            </button>
            <button onclick="window.close()" class="btn btn-secondary" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                Tutup
            </button>
        </div>
    </div>

    <script>
        // Otomatis cetak ketika dokumen sudah dimuat (opsional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>