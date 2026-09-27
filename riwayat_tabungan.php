<?php
// File: riwayat_tabungan.php
session_start();
if (!isset($_SESSION['username'])) {
    header("location:login.php");
    exit();
}

include 'koneksi.php';

// Cek apakah parameter aksi=riwayat dan nis ada
if (isset($_GET['aksi']) && $_GET['aksi'] == 'riwayat' && isset($_GET['nis'])) {
    $nis = $koneksi->real_escape_string($_GET['nis']);
    
    // Query data siswa
    $query_siswa = $koneksi->query("SELECT s.*, k.nama_kelas 
                                   FROM tb_siswa s 
                                   LEFT JOIN tb_kelas k ON s.kelas = k.id_kelas 
                                   WHERE s.nis = '$nis'");
    
    if (!$query_siswa) {
        die("Query error: " . $koneksi->error);
    }
    
    $siswa = $query_siswa->fetch_assoc();
    
    // Jika siswa tidak ditemukan
    if (!$siswa) {
        echo "<script>alert('Siswa dengan NIS $nis tidak ditemukan'); window.location.href='?page=tabungan';</script>";
        exit();
    }
    
    // Query saldo terakhir
    $query_saldo = $koneksi->query("
        SELECT COALESCE(SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END), 0) as saldo
        FROM tb_tabungan 
        WHERE nis = '$nis'
    ");
    
    if (!$query_saldo) {
        die("Query error: " . $koneksi->error);
    }
    
    $saldo = $query_saldo->fetch_assoc();
    $saldo_akhir = $saldo['saldo'];
    
    // Build query dengan filter
    $where_conditions = ["nis = '$nis'"];
    
    if (isset($_GET['dari_tanggal']) && !empty($_GET['dari_tanggal'])) {
        $dari_tanggal = $koneksi->real_escape_string($_GET['dari_tanggal']);
        $where_conditions[] = "tanggal >= '$dari_tanggal'";
    }
    
    if (isset($_GET['sampai_tanggal']) && !empty($_GET['sampai_tanggal'])) {
        $sampai_tanggal = $koneksi->real_escape_string($_GET['sampai_tanggal']);
        $where_conditions[] = "tanggal <= '$sampai_tanggal'";
    }
    
    if (isset($_GET['jenis']) && !empty($_GET['jenis'])) {
        $jenis = $koneksi->real_escape_string($_GET['jenis']);
        $where_conditions[] = "jenis = '$jenis'";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $query_riwayat = $koneksi->query("
        SELECT * FROM tb_tabungan 
        WHERE $where_clause
        ORDER BY tanggal DESC, id_tabungan DESC
    ");
    
    if (!$query_riwayat) {
        die("Query error: " . $koneksi->error);
    }
    
    // Hitung total transaksi
    $total_transaksi = $query_riwayat->num_rows;
} else {
    // Jika parameter tidak lengkap, redirect ke halaman tabungan
    header("location:?page=tabungan");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi Tabungan - MTi NURU: HIDAYAI1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3c8dbc;
            --secondary-color: #f4f4f4;
            --accent-color: #00a65a;
            --text-color: #333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        
        .sidebar {
            background-color: #222d32;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 50px;
            width: 230px;
            z-index: 1000;
        }
        
        .sidebar a {
            color: #b8c7ce;
            display: block;
            padding: 12px 5px 12px 15px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            color: white;
            background-color: #1e282c;
            border-left: 4px solid var(--accent-color);
        }
        
        .sidebar .menu-divider {
            border-top: 1px solid #4b646f;
            margin: 10px 0;
        }
        
        .main-content {
            margin-left: 230px;
            padding: 20px;
        }
        
        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            position: fixed;
            width: calc(100% - 230px);
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-wrapper {
            margin-top: 70px;
        }
        
        .card {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            border: none;
        }
        
        .card-header {
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            position: relative;
            border-radius: 8px 8px 0 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .student-info {
            background: linear-gradient(to right, #f0f9ff, #e6f7ff);
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .transaction-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .deposit {
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .withdrawal {
            color: #dd4b39;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #eee;
        }
        
        .filter-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .balance-box {
            background: linear-gradient(to right, #00a65a, #00c0ef);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .balance-amount {
            font-size: 28px;
            font-weight: bold;
        }
        
        .badge-success {
            background-color: var(--accent-color);
        }
        
        .badge-warning {
            background-color: #f39c12;
        }
        
        .summary-card {
            background: linear-gradient(45deg, #3c8dbc, #00c0ef);
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: bold;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="px-3">MENUT URAN</h4>
        <div class="menu-divider"></div>
        <a href="?page=dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
        <a href="?page=pengaturan"><i class="fas fa-school me-2"></i> Pengstaran Sekolah</a>
        <a href="?page=pengguna"><i class="fas fa-users me-2"></i> Daflar Pengguna</a>
        <a href="?page=master"><i class="fas fa-database me-2"></i> Master Data</a>
        <a href="?page=kelas"><i class="fas fa-book me-2"></i> Kessingan</a>
        <a href="?page=pembayaran"><i class="fas fa-money-bill-wave me-2"></i> Mesn Pembayana</a>
        <a href="?page=tabungan" class="active"><i class="fas fa-piggy-bank me-2"></i> Tokungan Sinna</a>
        <a href="?page=report"><i class="fas fa-chart-bar me-2"></i> Report</a>
    </div>

    <!-- Header -->
    <div class="header">
        <h3><i class="fas fa-piggy-bank me-2"></i> Riwayat Transaksi Tabungan</h3>
        <div>
            <span class="me-3"><i class="fas fa-user me-1"></i> <?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="btn btn-sm btn-light"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Informasi Siswa -->
            <div class="student-info">
                <div class="row">
                    <div class="col-md-8">
                        <h4><i class="fas fa-user-graduate me-2"></i> <?php echo $siswa['nama_siswa']; ?></h4>
                        <div class="row mt-3">
                            <div class="col-sm-4">
                                <strong><i class="fas fa-chalkboard me-1"></i> Kelas:</strong> <?php echo $siswa['nama_kelas']; ?>
                            </div>
                            <div class="col-sm-4">
                                <strong><i class="fas fa-id-card me-1"></i> NIS:</strong> <?php echo $siswa['nis']; ?>
                            </div>
                            <div class="col-sm-4">
                                <strong><i class="fas fa-circle me-1"></i> Status:</strong> 
                                <span class="badge <?php echo $siswa['status'] == 'Aktif' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $siswa['status']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="?page=tabungan" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Tabungan
                        </a>
                    </div>
                </div>
            </div>

            <!-- Saldo Info -->
            <div class="balance-box">
                <h5><i class="fas fa-wallet me-2"></i> Saldo Tabungan Saat Ini</h5>
                <div class="balance-amount">Rp <?php echo number_format($saldo_akhir, 0, ',', '.'); ?></div>
            </div>

            <!-- Ringkasan Statistik -->
            <div class="row">
                <div class="col-md-4">
                    <div class="summary-card text-center">
                        <i class="fas fa-exchange-alt fa-2x mb-2"></i>
                        <h6>Total Transaksi</h6>
                        <div class="summary-value"><?php echo $total_transaksi; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-card text-center" style="background: linear-gradient(45deg, #00a65a, #00efc0);">
                        <i class="fas fa-arrow-down fa-2x mb-2"></i>
                        <h6>Total Setoran</h6>
                        <div class="summary-value">
                            <?php
                            $query_setor = $koneksi->query("SELECT SUM(jumlah) as total_setor FROM tb_tabungan WHERE nis = '$nis' AND jenis = 'setor'");
                            $setor = $query_setor->fetch_assoc();
                            echo 'Rp ' . number_format($setor['total_setor'] ?? 0, 0, ',', '.');
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-card text-center" style="background: linear-gradient(45deg, #dd4b39, #ff6b6b);">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        <h6>Total Penarikan</h6>
                        <div class="summary-value">
                            <?php
                            $query_tarik = $koneksi->query("SELECT SUM(jumlah) as total_tarik FROM tb_tabungan WHERE nis = '$nis' AND jenis = 'tarik'");
                            $tarik = $query_tarik->fetch_assoc();
                            echo 'Rp ' . number_format($tarik['total_tarik'] ?? 0, 0, ',', '.');
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-container">
                <h5><i class="fas fa-filter me-2"></i> Filter Transaksi</h5>
                <form method="GET" action="">
                    <input type="hidden" name="page" value="tabungan">
                    <input type="hidden" name="aksi" value="riwayat">
                    <input type="hidden" name="nis" value="<?php echo $nis; ?>">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label for="startDate" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" id="startDate" name="dari_tanggal" 
                                   value="<?php echo isset($_GET['dari_tanggal']) ? $_GET['dari_tanggal'] : ''; ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="endDate" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="endDate" name="sampai_tanggal" 
                                   value="<?php echo isset($_GET['sampai_tanggal']) ? $_GET['sampai_tanggal'] : ''; ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="jenisTransaksi" class="form-label">Jenis Transaksi</label>
                            <select class="form-control" id="jenisTransaksi" name="jenis">
                                <option value="">- Semua Jenis -</option>
                                <option value="setor" <?php echo (isset($_GET['jenis']) && $_GET['jenis'] == 'setor') ? 'selected' : ''; ?>>Setoran</option>
                                <option value="tarik" <?php echo (isset($_GET['jenis']) && $_GET['jenis'] == 'tarik') ? 'selected' : ''; ?>>Penarikan</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Terapkan Filter
                            </button>
                            <a href="?page=tabungan&aksi=riwayat&nis=<?php echo $nis; ?>" class="btn btn-secondary">
                                <i class="fas fa-sync me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Transaction History -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i> Riwayat Transaksi</h5>
                    <span class="badge bg-primary">Total: <?php echo $total_transaksi; ?> transaksi</span>
                </div>
                <div class="card-body">
                    <?php if ($total_transaksi > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover transaction-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Jenis Transaksi</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end">Saldo</th>
                                    <th>Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($transaksi = $query_riwayat->fetch_assoc()) {
                                    $jenis = ($transaksi['jenis'] == 'setor') ? 'Setoran' : 'Penarikan';
                                    $badge_class = ($transaksi['jenis'] == 'setor') ? 'bg-success' : 'bg-warning';
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo date('d M Y', strtotime($transaksi['tanggal'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <i class="fas <?php echo $transaksi['jenis'] == 'setor' ? 'fa-arrow-down' : 'fa-arrow-up'; ?> me-1"></i>
                                            <?php echo $jenis; ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($transaksi['keterangan']) ? $transaksi['keterangan'] : '-'; ?></td>
                                    <td class="text-end <?php echo $transaksi['jenis'] == 'setor' ? 'deposit' : 'withdrawal'; ?>">
                                        <?php echo $transaksi['jenis'] == 'setor' ? '+' : '-'; ?>
                                        Rp <?php echo number_format($transaksi['jumlah'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="text-end">
                                        <strong>Rp <?php echo number_format($transaksi['saldo'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td><?php echo $transaksi['admin']; ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada transaksi ditemukan</h5>
                        <p class="text-muted">Belum ada transaksi tabungan untuk siswa ini.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <i class="fas fa-copyright me-1"></i> Copyright © 2023 Wirayides_Shop. All rights reserved.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set tanggal default untuk filter
        document.addEventListener('DOMContentLoaded', function() {
            // Set tanggal mulai ke 1 bulan yang lalu
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            
            if (!startDate.value) {
                const oneMonthAgo = new Date();
                oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
                startDate.value = oneMonthAgo.toISOString().split('T')[0];
            }
            
            if (!endDate.value) {
                endDate.value = new Date().toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>