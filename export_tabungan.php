<?php
// File: export_tabungan.php
session_start();
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header("location:login.php");
    exit();
}

// Include koneksi database
include_once 'include/koneksi.php';

// Set waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk format tanggal Indonesia
function tglIndonesia($tanggal) {
    $bulan = array(
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    );
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

// Ambil data filter dari POST
$jenis_export = isset($_POST['jenis_export']) ? $_POST['jenis_export'] : 'all';
$dari_tanggal = isset($_POST['dari_tanggal']) ? $_POST['dari_tanggal'] : '';
$sampai_tanggal = isset($_POST['sampai_tanggal']) ? $_POST['sampai_tanggal'] : '';
$kelas = isset($_POST['kelas']) ? $_POST['kelas'] : '';

// Header untuk file Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data_Tabungan_" . date('Y-m-d') . ".xls");

// Query data berdasarkan filter
$where_conditions = array();

if (!empty($dari_tanggal)) {
    $where_conditions[] = "t.tanggal >= '$dari_tanggal'";
}

if (!empty($sampai_tanggal)) {
    $where_conditions[] = "t.tanggal <= '$sampai_tanggal'";
}

if (!empty($kelas) && $kelas != 'all') {
    $where_conditions[] = "s.kelas = '$kelas'";
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(' AND ', $where_conditions);
}

// Query data tabungan
$query = "
    SELECT t.*, s.nama_siswa, s.nis, s.kelas, k.nama_kelas 
    FROM tb_tabungan t 
    JOIN tb_siswa s ON t.nis = s.nis 
    LEFT JOIN tb_kelas k ON s.kelas = k.id_kelas 
    $where_clause 
    ORDER BY t.tanggal DESC, t.id_tabungan DESC
";

$result = mysqli_query($koneksi, $query);

// Query data profile sekolah
$sql_profile = mysqli_query($koneksi, "SELECT * FROM tb_profile");
$profile = mysqli_fetch_assoc($sql_profile);

// Hitung total saldo
$total_saldo = 0;
$saldo_per_siswa = array();

// Simpan data untuk diproses
$data_tabungan = array();
while ($row = mysqli_fetch_assoc($result)) {
    $data_tabungan[] = $row;
    
    // Hitung saldo per siswa
    if (!isset($saldo_per_siswa[$row['nis']])) {
        $saldo_per_siswa[$row['nis']] = 0;
    }
    
    if ($row['jenis'] == 'setor') {
        $saldo_per_siswa[$row['nis']] += $row['jumlah'];
    } else {
        $saldo_per_siswa[$row['nis']] -= $row['jumlah'];
    }
    
    $total_saldo += $saldo_per_siswa[$row['nis']];
}

// Mulai output Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Data Tabungan</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #f2f2f2; font-weight: bold; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #e8f4ff; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Header Sekolah -->
    <div class="header">
        <h2><?php echo strtoupper($profile['nama_sekolah']); ?></h2>
        <p><?php echo $profile['alamat']; ?></p>
        <p>Telp: <?php echo $profile['telpon']; ?> | Website: <?php echo $profile['website']; ?></p>
        <h3>LAPORAN DATA TABUNGAN SISWA</h3>
        <p>Periode: 
            <?php 
            if (!empty($dari_tanggal) && !empty($sampai_tanggal)) {
                echo tglIndonesia($dari_tanggal) . ' - ' . tglIndonesia($sampai_tanggal);
            } else {
                echo 'Semua Periode';
            }
            ?>
        </p>
    </div>

    <!-- Ringkasan Saldo -->
    <table>
        <tr>
            <td colspan="4" class="total-row">TOTAL SALDO TABUNGAN SISWA</td>
            <td class="total-row text-right">Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></td>
        </tr>
    </table>
    <br>

    <!-- Data Saldo per Siswa -->
    <h4>SALDO PER SISWA</h4>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Saldo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            // Query untuk mendapatkan saldo terkini per siswa
            $query_siswa = "
                SELECT s.nis, s.nama_siswa, s.kelas, k.nama_kelas, s.status,
                (SELECT COALESCE(SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END), 0) 
                 FROM tb_tabungan WHERE nis = s.nis) as saldo
                FROM tb_siswa s 
                LEFT JOIN tb_kelas k ON s.kelas = k.id_kelas 
                ORDER BY s.nama_siswa
            ";
            
            $result_siswa = mysqli_query($koneksi, $query_siswa);
            
            while ($siswa = mysqli_fetch_assoc($result_siswa)) {
                // Filter berdasarkan kelas jika dipilih
                if (!empty($kelas) && $kelas != 'all' && $siswa['kelas'] != $kelas) {
                    continue;
                }
                
                echo "<tr>
                    <td class='text-center'>" . $no++ . "</td>
                    <td>" . $siswa['nis'] . "</td>
                    <td>" . $siswa['nama_siswa'] . "</td>
                    <td class='text-center'>" . $siswa['nama_kelas'] . "</td>
                    <td class='text-right'>Rp " . number_format($siswa['saldo'], 0, ',', '.') . "</td>
                    <td class='text-center'>" . $siswa['status'] . "</td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
    <br><br>

    <!-- Riwayat Transaksi -->
    <h4>RIWAYAT TRANSAKSI TABUNGAN</h4>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Jenis Transaksi</th>
                <th>Jumlah</th>
                <th>Saldo</th>
                <th>Admin</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no_transaksi = 1;
            foreach ($data_tabungan as $transaksi) {
                $jenis = ($transaksi['jenis'] == 'setor') ? 'Setor' : 'Tarik';
                $jumlah_display = ($transaksi['jenis'] == 'setor') ? 
                    'Rp ' . number_format($transaksi['jumlah'], 0, ',', '.') : 
                    'Rp ' . number_format($transaksi['jumlah'], 0, ',', '.');
                
                // Hitung saldo untuk transaksi ini
                $saldo_transaksi = $saldo_per_siswa[$transaksi['nis']];
                
                echo "<tr>
                    <td class='text-center'>" . $no_transaksi++ . "</td>
                    <td>" . tglIndonesia($transaksi['tanggal']) . "</td>
                    <td>" . $transaksi['nis'] . "</td>
                    <td>" . $transaksi['nama_siswa'] . "</td>
                    <td class='text-center'>" . $jenis . "</td>
                    <td class='text-right'>" . $jumlah_display . "</td>
                    <td class='text-right'>Rp " . number_format($saldo_transaksi, 0, ',', '.') . "</td>
                    <td class='text-center'>" . ($transaksi['admin'] ? $transaksi['admin'] : '-') . "</td>
                </tr>";
            }
            
            if (count($data_tabungan) == 0) {
                echo "<tr><td colspan='8' class='text-center'>Tidak ada data transaksi</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <br><br>

    <!-- Footer -->
    <table>
        <tr>
            <td colspan="4"></td>
            <td colspan="4" class="text-center">
                <?php echo $profile['kota']; ?>, <?php echo tglIndonesia(date('Y-m-d')); ?><br>
                Bendahara,<br><br><br><br>
                <u><?php echo $profile['bendahara']; ?></u><br>
                NIP. <?php echo $profile['nip']; ?>
            </td>
        </tr>
    </table>
</body>
</html>