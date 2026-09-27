<?php
// File: cetak_tabungan.php
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
session_start();
if (!isset($_SESSION['admin']) && !isset($_SESSION['user'])) {
    header("location:login.php");
    exit();
}

// Beberapa kemungkinan path koneksi yang umum
$possible_paths = [
    __DIR__ . '/include/koneksi.php',
    __DIR__ . '/../include/koneksi.php',
    __DIR__ . '/../../include/koneksi.php',
    'include/koneksi.php',
    '../include/koneksi.php'
];

$koneksi_file = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $koneksi_file = $path;
        break;
    }
}

if ($koneksi_file) {
    include_once $koneksi_file;
} else {
    // Coba koneksi manual jika file tidak ditemukan
    $koneksi = new mysqli("localhost", "root", "", "pembayaran");
    if ($koneksi->connect_errno) {
        die("Koneksi database gagal: " . $koneksi->connect_error);
    }
}

// Cek jika koneksi berhasil
if (!isset($koneksi) || $koneksi === null) {
    $koneksi = new mysqli("localhost", "root", "", "pembayaran");
    if ($koneksi->connect_errno) {
        die("Koneksi database gagal: " . $koneksi->connect_error);
    }
}

// Query data profile sekolah
$sql_profile = mysqli_query($koneksi, "SELECT * FROM tb_profile");
$data_profile = mysqli_fetch_assoc($sql_profile);

// Cek apakah parameter nis ada
if (isset($_GET['nis'])) {
    // Gunakan mysqli_real_escape_string dengan cara yang benar
    $nis = mysqli_real_escape_string($koneksi, $_GET['nis']);
    
    // Query data siswa
    $query_siswa = mysqli_query($koneksi, "SELECT s.*, k.nama_kelas 
                                   FROM tb_siswa s 
                                   LEFT JOIN tb_kelas k ON s.kelas = k.id_kelas 
                                   WHERE s.nis = '$nis'");
    
    if (!$query_siswa) {
        die("Query error: " . mysqli_error($koneksi));
    }
    
    $siswa = mysqli_fetch_assoc($query_siswa);
    
    // Jika siswa tidak ditemukan
    if (!$siswa) {
        die("Siswa dengan NIS $nis tidak ditemukan");
    }
    
    // Build query dengan filter
    $where_conditions = ["t.nis = '$nis'"];
    
    if (isset($_GET['dari_tanggal']) && !empty($_GET['dari_tanggal'])) {
        $dari_tanggal = mysqli_real_escape_string($koneksi, $_GET['dari_tanggal']);
        $where_conditions[] = "t.tanggal >= '$dari_tanggal'";
    }
    
    if (isset($_GET['sampai_tanggal']) && !empty($_GET['sampai_tanggal'])) {
        $sampai_tanggal = mysqli_real_escape_string($koneksi, $_GET['sampai_tanggal']);
        $where_conditions[] = "t.tanggal <= '$sampai_tanggal'";
    }
    
    if (isset($_GET['jenis']) && !empty($_GET['jenis'])) {
        $jenis = mysqli_real_escape_string($koneksi, $_GET['jenis']);
        $where_conditions[] = "t.jenis = '$jenis'";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Query riwayat transaksi
    $query_riwayat = mysqli_query($koneksi, "
        SELECT t.*, s.nama_siswa, k.nama_kelas
        FROM tb_tabungan t
        JOIN tb_siswa s ON t.nis = s.nis
        LEFT JOIN tb_kelas k ON s.kelas = k.id_kelas
        WHERE $where_clause
        ORDER BY t.tanggal DESC, t.id_tabungan DESC
    ");
    
    if (!$query_riwayat) {
        die("Query error: " . mysqli_error($koneksi));
    }
    
    // Hitung total transaksi
    $total_transaksi = mysqli_num_rows($query_riwayat);
    
    // Hitung saldo dengan benar
    $saldo_akhir = 0;
    $riwayat_data = array();
    
    if ($total_transaksi > 0) {
        // Simpan data untuk diproses dalam urutan kronologis
        $temp_data = array();
        while ($row = mysqli_fetch_assoc($query_riwayat)) {
            $temp_data[] = $row;
        }
        
        // Urutkan dari yang paling lama untuk menghitung saldo
        usort($temp_data, function($a, $b) {
            return strtotime($a['tanggal']) - strtotime($b['tanggal']);
        });
        
        // Hitung saldo untuk setiap transaksi
        $saldo_berjalan = 0;
        foreach ($temp_data as $transaksi) {
            if ($transaksi['jenis'] == 'setor') {
                $saldo_berjalan += $transaksi['jumlah'];
            } else {
                $saldo_berjalan -= $transaksi['jumlah'];
            }
            $transaksi['saldo_akhir'] = $saldo_berjalan;
            $riwayat_data[] = $transaksi;
        }
        
        // Urutkan kembali dari yang terbaru untuk ditampilkan
        usort($riwayat_data, function($a, $b) {
            return strtotime($b['tanggal']) - strtotime($a['tanggal']);
        });
        
        $saldo_akhir = $saldo_berjalan;
    }
    
    // Hitung total setor dan tarik
    $total_setor = 0;
    $total_tarik = 0;
    foreach ($riwayat_data as $t) {
        if ($t['jenis'] == 'setor') {
            $total_setor += $t['jumlah'];
        } else {
            $total_tarik += $t['jumlah'];
        }
    }
    
    // Fungsi konversi tanggal ke Indonesia
    function tglIndonesia($str){
        $tr = trim($str);
        $str = str_replace(array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'), 
                          array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'), $tr);
        return $str;
    }
} else {
    // Jika parameter tidak lengkap
    echo "Parameter tidak lengkap";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Cetak - Laporan Tabungan Siswa</title>
<style type="text/css">
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        margin: 0;
        padding: 20px;
    }
    .tabel {
        border-collapse: collapse;
        width: 100%;
        margin-top: 10px;
    }
    .tabel th {
        padding: 6px 5px;
        background-color: #f2f2f2;
        border: 1px solid #000;
        font-weight: bold;
    }
    .tabel td {
        padding: 6px 5px;
        border: 1px solid #000;
    }
    .text-right {
        text-align: right;
    }
    .text-center {
        text-align: center;
    }
    .text-success {
        color: #28a745;
    }
    .text-danger {
        color: #dc3545;
    }
    @media print {
        .no-print { 
            display: none; 
        }
        body {
            padding: 15px;
        }
    }
    .header {
        text-align: center;
        margin-bottom: 15px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }
    .logo {
        height: 80px;
        margin-bottom: 10px;
    }
    .school-name {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 3px;
        text-transform: uppercase;
    }
    .school-info {
        font-size: 12px;
        margin-bottom: 2px;
    }
    .student-info {
        margin-bottom: 15px;
    }
    .student-info table {
        width: 100%;
    }
    .student-info td {
        padding: 3px 0;
        vertical-align: top;
    }
    .footer {
        margin-top: 30px;
        text-align: right;
    }
    .summary {
        margin-top: 15px;
        font-weight: bold;
    }
    .status-info {
        text-align: center;
        margin: 10px 0;
        padding: 5px;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        border-radius: 4px;
        color: #155724;
        font-weight: bold;
    }
</style>
<script>
    window.print();
    window.onfocus = function() { 
        setTimeout(function() { 
            window.close(); 
        }, 100); 
    }
</script>
</head>
<body>
    <div class="no-print" style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc;">
        <strong>Debug Info:</strong> 
        <?php 
        echo "Koneksi: " . (isset($koneksi) ? "BERHASIL" : "GAGAL") . " | ";
        echo "Siswa: " . (isset($siswa) ? "DITEMUKAN" : "TIDAK DITEMUKAN");
        ?>
    </div>

    <!-- Header dengan logo sekolah -->
    <div class="header">
        <div class="logo-container">
            <?php 
            // Cari path logo yang benar
            $logo_path = null;
            $possible_logo_paths = [
                'images/' . $data_profile['foto'],
                '../images/' . $data_profile['foto'],
                '../../images/' . $data_profile['foto'],
                __DIR__ . '/images/' . $data_profile['foto'],
                __DIR__ . '/../images/' . $data_profile['foto']
            ];
            
            foreach ($possible_logo_paths as $path) {
                if (file_exists($path) && !empty($data_profile['foto'])) {
                    $logo_path = $path;
                    break;
                }
            }
            
            if ($logo_path) {
                echo '<img src="' . $logo_path . '" class="logo" alt="Logo Sekolah">';
            } else if (!empty($data_profile['foto'])) {
                // Coba path relatif sebagai fallback
                echo '<img src="../images/' . $data_profile['foto'] . '" class="logo" alt="Logo Sekolah">';
            }
            ?>
        </div>
        <div class="school-name"><?php echo strtoupper($data_profile['nama_sekolah']); ?></div>
        <div class="school-info"><?php echo $data_profile['alamat']; ?></div>
        <div class="school-info">
            TELP. <?php echo $data_profile['telpon']; ?> 
            WEBSITE: <?php echo $data_profile['website']; ?>
        </div>
    </div>

    <!-- Info status pencarian -->
    <div class="status-info">
        BERHASIL | Siswa: DITEMUKAN
    </div>

    <!-- Informasi siswa -->
    <div class="student-info">
        <table>
            <tr>
                <td width="20%"><strong>Nama Siswa</strong></td>
                <td width="30%">: <?php echo htmlspecialchars($siswa['nama_siswa']); ?></td>
                <td width="20%"><strong>Kelas</strong></td>
                <td width="30%">: <?php echo htmlspecialchars($siswa['nama_kelas']); ?></td>
            </tr>
            <tr>
                <td><strong>NIS</strong></td>
                <td>: <?php echo htmlspecialchars($siswa['nis']); ?></td>
                <td><strong>Status</strong></td>
                <td>: <?php echo htmlspecialchars($siswa['status']); ?></td>
            </tr>
            <tr>
                <td><strong>Saldo Akhir</strong></td>
                <td>: <strong>Rp <?php echo number_format($saldo_akhir, 0, ',', '.'); ?></strong></td>
                <td><strong>Total Transaksi</strong></td>
                <td>: <?php echo $total_transaksi; ?> transaksi</td>
            </tr>
        </table>
    </div>

    <!-- Informasi Filter -->
    <?php
    $filter_info = '';
    if (isset($_GET['dari_tanggal']) && !empty($_GET['dari_tanggal'])) {
        $filter_info .= 'Dari: ' . date('d M Y', strtotime($_GET['dari_tanggal'])) . ' ';
    }
    if (isset($_GET['sampai_tanggal']) && !empty($_GET['sampai_tanggal'])) {
        $filter_info .= 'Sampai: ' . date('d M Y', strtotime($_GET['sampai_tanggal'])) . ' ';
    }
    if (isset($_GET['jenis']) && !empty($_GET['jenis'])) {
        $jenis_filter = ($_GET['jenis'] == 'setor') ? 'Setoran' : 'Penarikan';
        $filter_info .= 'Jenis: ' . $jenis_filter;
    }
    
    if (!empty($filter_info)) {
        echo '<p><strong>Filter: </strong>' . $filter_info . '</p>';
    }
    ?>

    <!-- Tabel riwayat transaksi -->
    <table class="tabel">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th width="10%">Jenis</th>
                <th width="25%">Keterangan</th>
                <th width="15%">Jumlah</th>
                <th width="15%">Saldo</th>
                <th width="15%">Admin</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total_transaksi > 0): ?>
                <?php
                $no = 1;
                foreach ($riwayat_data as $transaksi) {
                    $jenis = ($transaksi['jenis'] == 'setor') ? 'Setor' : 'Tarik';
                    $jumlah_display = ($transaksi['jenis'] == 'setor') ? 
                        '+ Rp ' . number_format($transaksi['jumlah'], 0, ',', '.') : 
                        '- Rp ' . number_format($transaksi['jumlah'], 0, ',', '.');
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo date('d M Y', strtotime($transaksi['tanggal'])); ?></td>
                    <td class="text-center"><?php echo $jenis; ?></td>
                    <td><?php echo !empty($transaksi['keterangan']) ? htmlspecialchars($transaksi['keterangan']) : '-'; ?></td>
                    <td class="text-right <?php echo $transaksi['jenis'] == 'setor' ? 'text-success' : 'text-danger'; ?>">
                        <strong><?php echo $jumlah_display; ?></strong>
                    </td>
                    <td class="text-right"><strong>Rp <?php echo number_format($transaksi['saldo_akhir'], 0, ',', '.'); ?></strong></td>
                    <td class="text-center"><?php echo !empty($transaksi['admin']) ? htmlspecialchars($transaksi['admin']) : '-'; ?></td>
                </tr>
                <?php } ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Tidak ada transaksi ditemukan</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-center">TOTAL</th>
                <th class="text-right">
                    Setor: Rp <?php echo number_format($total_setor, 0, ',', '.'); ?><br>
                    Tarik: Rp <?php echo number_format($total_tarik, 0, ',', '.'); ?>
                </th>
                <th class="text-right">Rp <?php echo number_format($saldo_akhir, 0, ',', '.'); ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <!-- Footer dengan tanda tangan -->
    <div class="footer">
        <div>
            <?php echo $data_profile['kota']; ?>, <?php echo date('d M Y'); ?><br>
            Bendahara,
            <br><br><br><br>
            <u><?php echo $data_profile['bendahara']; ?></u><br>
            NIP. <?php echo $data_profile['nip']; ?>
        </div>
    </div>

    <!-- Tombol cetak dan kembali (hanya tampil di browser) -->
    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" class="btn btn-primary">Cetak</button>
        <a href="?page=tabungan" class="btn btn-default">Kembali</a>
    </div>
</body>
</html>