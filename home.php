<?php 
// File: home.php

// Mengatasi error reporting
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
include "include/koneksi.php";

// Pastikan session sudah start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inisialisasi variabel
$kas_hari_ini = 0;
$penerimaan = 0;
$pengeluaran = 0;
$saldo = 0;
$total_saldo_tabungan = 0;
$transaksi_tabungan_hari_ini = 0;

// Query untuk kas hari ini
$tgl = date("Y-m-d");
$sql = $koneksi->query("SELECT COALESCE(SUM(penerimaan), 0) as total_penerimaan FROM tb_kas WHERE tgl_kas = '$tgl'");

if ($sql) {
    $data = $sql->fetch_assoc();
    $kas_hari_ini = $data['total_penerimaan'];
}

// Query untuk total kas
$sql2 = $koneksi->query("SELECT 
    COALESCE(SUM(penerimaan), 0) as total_penerimaan,
    COALESCE(SUM(pengeluaran), 0) as total_pengeluaran 
    FROM tb_kas");

if ($sql2) {
    $data = $sql2->fetch_assoc();
    $penerimaan = $data['total_penerimaan'];
    $pengeluaran = $data['total_pengeluaran'];
    $saldo = $penerimaan - $pengeluaran;
}

// Query untuk total saldo tabungan
$sql_tabungan = $koneksi->query("SELECT 
    COALESCE(SUM(CASE WHEN jenis = 'setor' THEN jumlah ELSE -jumlah END), 0) as total_saldo 
    FROM tb_tabungan");

if ($sql_tabungan) {
    $data = $sql_tabungan->fetch_assoc();
    $total_saldo_tabungan = $data['total_saldo'];
}

// Query untuk transaksi tabungan hari ini
$sql_tabungan_hari_ini = $koneksi->query("SELECT COUNT(*) as jumlah FROM tb_tabungan WHERE DATE(tanggal) = '$tgl'");
if ($sql_tabungan_hari_ini) {
    $data = $sql_tabungan_hari_ini->fetch_assoc();
    $transaksi_tabungan_hari_ini = $data['jumlah'];
}

// ==============================================
// DATA UNTUK GRAFIK
// ==============================================

// 1. Data Grafik 7 Hari Terakhir
$labels_7hari = [];
$data_penerimaan_7hari = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_label = date('d M', strtotime($date));
    
    $sql_harian = $koneksi->query("SELECT COALESCE(SUM(penerimaan), 0) as total FROM tb_kas WHERE DATE(tgl_kas) = '$date'");
    if ($sql_harian) {
        $data_harian = $sql_harian->fetch_assoc();
        $total_harian = $data_harian['total'];
    } else {
        $total_harian = 0;
    }
    
    $labels_7hari[] = $day_label;
    $data_penerimaan_7hari[] = $total_harian;
}

// 2. Data Grafik Penerimaan vs Pengeluaran Bulan Ini
$bulan_ini = date('Y-m');
$sql_bulan_ini = $koneksi->query("SELECT 
    COALESCE(SUM(penerimaan), 0) as penerimaan_bulan_ini,
    COALESCE(SUM(pengeluaran), 0) as pengeluaran_bulan_ini
    FROM tb_kas WHERE DATE_FORMAT(tgl_kas, '%Y-%m') = '$bulan_ini'");

if ($sql_bulan_ini) {
    $data_bulan_ini = $sql_bulan_ini->fetch_assoc();
    $penerimaan_bulan_ini = $data_bulan_ini['penerimaan_bulan_ini'];
    $pengeluaran_bulan_ini = $data_bulan_ini['pengeluaran_bulan_ini'];
} else {
    $penerimaan_bulan_ini = 0;
    $pengeluaran_bulan_ini = 0;
}

// 3. Data Jumlah Siswa per Kelas
$sql_siswa_kelas = $koneksi->query("
    SELECT k.nama_kelas, COUNT(s.id_siswa) as jumlah_siswa
    FROM tb_kelas k
    LEFT JOIN tb_siswa s ON k.id_kelas = s.kelas AND s.status = 'Aktif'
    GROUP BY k.id_kelas, k.nama_kelas
    ORDER BY k.nama_kelas
");

$labels_kelas = [];
$data_siswa_kelas = [];

if ($sql_siswa_kelas) {
    while ($row = $sql_siswa_kelas->fetch_assoc()) {
        $labels_kelas[] = $row['nama_kelas'];
        $data_siswa_kelas[] = $row['jumlah_siswa'];
    }
}

// 4. Data Tabungan 7 Hari Terakhir
$labels_tabungan = [];
$data_setoran = [];
$data_penarikan = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_label = date('d M', strtotime($date));
    
    // Setoran
    $sql_setoran = $koneksi->query("SELECT COALESCE(SUM(jumlah), 0) as total FROM tb_tabungan WHERE jenis = 'setor' AND DATE(tanggal) = '$date'");
    $setoran_data = $sql_setoran ? $sql_setoran->fetch_assoc() : ['total' => 0];
    
    // Penarikan
    $sql_penarikan = $koneksi->query("SELECT COALESCE(SUM(jumlah), 0) as total FROM tb_tabungan WHERE jenis = 'tarik' AND DATE(tanggal) = '$date'");
    $penarikan_data = $sql_penarikan ? $sql_penarikan->fetch_assoc() : ['total' => 0];
    
    $labels_tabungan[] = $day_label;
    $data_setoran[] = $setoran_data['total'];
    $data_penarikan[] = $penarikan_data['total'];
}

// Query untuk total siswa aktif
$jumlah_siswa_query = $koneksi->query("SELECT COUNT(*) as total FROM tb_siswa WHERE status = 'Aktif'");
if ($jumlah_siswa_query) {
    $data_siswa = $jumlah_siswa_query->fetch_assoc();
    $jumlah_siswa = $data_siswa['total'];
} else {
    $jumlah_siswa = 0;
}

// Query untuk jumlah kelas
$jumlah_kelas_query = $koneksi->query("SELECT COUNT(*) as total FROM tb_kelas");
if ($jumlah_kelas_query) {
    $data_kelas = $jumlah_kelas_query->fetch_assoc();
    $jumlah_kelas = $data_kelas['total'];
} else {
    $jumlah_kelas = 0;
}

// Cek jika user adalah admin
$is_admin = isset($_SESSION['admin']) && $_SESSION['admin'];
?>

<?php if ($is_admin) { ?>

<section class="content-header">
    <h1><b>
        Dashboard Administrator
        <small>Statistik & Monitoring</small></b>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Dashboard</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3>Rp<?php echo number_format($kas_hari_ini, 0, ",", "."); ?></h3>
                    <p><b>Pemasukan Hari Ini</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-archive"></i>
                </div>
                <a href="?page=kas" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3>Rp<?php echo number_format($penerimaan, 0, ",", ".") ?></h3>
                    <p><b>Pemasukan Total</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-arrow-shrink"></i>
                </div>
                <a href="?page=kas" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-fuchsia">
                <div class="inner">
                    <h3>Rp<?php echo number_format($pengeluaran, 0, ",", ".") ?></h3>
                    <p><b>Pengeluaran Total</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-arrow-graph-up-right"></i>
                </div>
                <a href="?page=kas" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3>Rp<?php echo number_format($saldo, 0, ",", ".") ?></h3>
                    <p><b>SALDO KAS</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-arrow-swap"></i>
                </div>
                <a href="?page=kas" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        
        <!-- Kotak untuk Total Saldo Tabungan -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>Rp<?php echo number_format($total_saldo_tabungan, 0, ",", ".") ?></h3>
                    <p><b>Total Tabungan</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-cash"></i>
                </div>
                <a href="?page=tabungan" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        
        <!-- Total Data Siswa -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-red">
                <div class="inner">
                    <h3><?php echo $jumlah_siswa; ?></h3>
                    <p><b>Siswa Aktif</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-person-stalker"></i>
                </div>
                <a href="?page=siswa" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        
        <!-- Data Total Kelas -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3><?php echo $jumlah_kelas; ?></h3>
                    <p><b>Jumlah Kelas</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-home"></i>
                </div>
                <a href="?page=kelas" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
      
        <!-- Transaksi Tabungan Hari Ini -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3><?php echo $transaksi_tabungan_hari_ini; ?></h3>
                    <p><b>Transaksi Tabungan Hari Ini</b></p>
                </div>
                <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                </div>
                <a href="?page=tabungan&aksi=riwayat" class="small-box-footer">Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
    </div>
    
    <!-- ============================================== -->
    <!-- GRAFIK DAN CHART -->
    <!-- ============================================== -->
    
    <div class="row">
        <!-- Grafik Penerimaan 7 Hari Terakhir -->
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-line-chart"></i> Penerimaan 7 Hari Terakhir</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="penerimaan7HariChart" style="height: 250px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grafik Penerimaan vs Pengeluaran Bulan Ini -->
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-pie-chart"></i> Penerimaan vs Pengeluaran Bulan Ini</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="penerimaanPengeluaranChart" style="height: 250px; width: 100%;"></canvas>
                    </div>
                    <div class="text-center mt-3">
                        <span class="label label-success">Penerimaan: Rp<?php echo number_format($penerimaan_bulan_ini, 0, ",", "."); ?></span>
                        <span class="label label-danger ml-2">Pengeluaran: Rp<?php echo number_format($pengeluaran_bulan_ini, 0, ",", "."); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Grafik Distribusi Siswa per Kelas -->
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-users"></i> Distribusi Siswa per Kelas</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="siswaPerKelasChart" style="height: 250px; width: 100%;"></canvas>
                    </div>
                    <div class="text-center mt-3">
                        <small>Total Siswa Aktif: <strong><?php echo $jumlah_siswa; ?></strong></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grafik Tabungan 7 Hari Terakhir -->
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-piggy-bank"></i> Tabungan 7 Hari Terakhir</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="tabungan7HariChart" style="height: 250px; width: 100%;"></canvas>
                    </div>
                    <div class="text-center mt-3">
                        <span class="label label-success">Setoran: Rp<?php echo number_format(array_sum($data_setoran), 0, ",", "."); ?></span>
                        <span class="label label-danger ml-2">Penarikan: Rp<?php echo number_format(array_sum($data_penarikan), 0, ",", "."); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabel Transaksi Terbaru -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-history"></i> 10 Transaksi Kas Terbaru</h3>
                    <div class="box-tools pull-right">
                        <a href="?page=kas" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Penerimaan</th>
                                    <th>Pengeluaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_transaksi = $koneksi->query("
                                    SELECT * FROM tb_kas 
                                    ORDER BY tgl_kas DESC, id_kas DESC 
                                    LIMIT 10
                                ");
                                
                                $no = 1;
                                if ($sql_transaksi && $sql_transaksi->num_rows > 0) {
                                    while ($row = $sql_transaksi->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . date('d/m/Y', strtotime($row['tgl_kas'])) . "</td>";
                                        echo "<td>" . htmlspecialchars(substr($row['keterangan'], 0, 50)) . 
                                             (strlen($row['keterangan']) > 50 ? "..." : "") . "</td>";
                                        echo "<td class='text-success'>" . 
                                             ($row['penerimaan'] > 0 ? "Rp " . number_format($row['penerimaan'], 0, ",", ".") : "-") . 
                                             "</td>";
                                        echo "<td class='text-danger'>" . 
                                             ($row['pengeluaran'] > 0 ? "Rp " . number_format($row['pengeluaran'], 0, ",", ".") : "-") . 
                                             "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Belum ada transaksi</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- Tambahkan Chart.js library di HEADER template utama -->
<script>
// Data dari PHP ke JavaScript
var labels7Hari = <?php echo json_encode($labels_7hari); ?>;
var dataPenerimaan7Hari = <?php echo json_encode($data_penerimaan_7hari); ?>;

var penerimaanBulanIni = <?php echo $penerimaan_bulan_ini; ?>;
var pengeluaranBulanIni = <?php echo $pengeluaran_bulan_ini; ?>;

var labelsKelas = <?php echo json_encode($labels_kelas); ?>;
var dataSiswaKelas = <?php echo json_encode($data_siswa_kelas); ?>;

var labelsTabungan = <?php echo json_encode($labels_tabungan); ?>;
var dataSetoran = <?php echo json_encode($data_setoran); ?>;
var dataPenarikan = <?php echo json_encode($data_penarikan); ?>;

// Function to initialize charts
function initializeCharts() {
    // 1. Grafik Penerimaan 7 Hari Terakhir
    var ctx1 = document.getElementById('penerimaan7HariChart');
    if (ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: labels7Hari,
                datasets: [{
                    label: 'Penerimaan (Rp)',
                    data: dataPenerimaan7Hari,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 2. Grafik Penerimaan vs Pengeluaran Bulan Ini
    var ctx2 = document.getElementById('penerimaanPengeluaranChart');
    if (ctx2) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Penerimaan', 'Pengeluaran'],
                datasets: [{
                    data: [penerimaanBulanIni, pengeluaranBulanIni],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // 3. Grafik Distribusi Siswa per Kelas
    var ctx3 = document.getElementById('siswaPerKelasChart');
    if (ctx3) {
        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: labelsKelas,
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: dataSiswaKelas,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    // 4. Grafik Tabungan 7 Hari Terakhir
    var ctx4 = document.getElementById('tabungan7HariChart');
    if (ctx4) {
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: labelsTabungan,
                datasets: [
                    {
                        label: 'Setoran',
                        data: dataSetoran,
                        backgroundColor: 'rgba(75, 192, 192, 0.8)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Penarikan',
                        data: dataPenarikan,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// Load Chart.js dynamically
function loadChartJS() {
    // Check if Chart.js is already loaded
    if (typeof Chart === 'undefined') {
        // Load Chart.js from CDN
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = function() {
            // Initialize charts after Chart.js is loaded
            initializeCharts();
        };
        document.head.appendChild(script);
    } else {
        // Chart.js already loaded, initialize charts
        initializeCharts();
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    loadChartJS();
});
</script>

<?php } else { ?>
    <!-- Tampilan untuk user non-admin -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Dashboard Siswa/Orangtua</h3>
                    </div>
                    <div class="box-body">
                        <?php 
                        // Include file untuk user non-admin
                        if (file_exists("lihat_pem_wali.php")) {
                            include "lihat_pem_wali.php";
                        } else {
                            echo "<div class='alert alert-info'>Selamat datang di Sistem Pembayaran Sekolah</div>";
                            echo "<p>Anda login sebagai siswa/orangtua. Gunakan menu di sidebar untuk melihat informasi pembayaran.</p>";
                            
                            // Tampilkan info pembayaran siswa jika login sebagai user
                            if (isset($_SESSION['user'])) {
                                $user_id = $_SESSION['user'];
                                $sql_user = $koneksi->query("SELECT * FROM tb_user WHERE id = '$user_id'");
                                if ($sql_user && $sql_user->num_rows > 0) {
                                    $user_data = $sql_user->fetch_assoc();
                                    $nis = $user_data['username'];
                                    
                                    // Cek data siswa
                                    $sql_siswa = $koneksi->query("SELECT * FROM tb_siswa WHERE nis = '$nis'");
                                    if ($sql_siswa && $sql_siswa->num_rows > 0) {
                                        $siswa = $sql_siswa->fetch_assoc();
                                        echo "<div class='alert alert-success'>";
                                        echo "<h4><i class='fa fa-user'></i> Informasi Siswa</h4>";
                                        echo "<p><strong>Nama:</strong> " . $siswa['nama_siswa'] . "</p>";
                                        echo "<p><strong>Kelas:</strong> " . $siswa['kelas'] . "</p>";
                                        echo "<p><strong>Status:</strong> " . $siswa['status'] . "</p>";
                                        echo "</div>";
                                    }
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php } ?>