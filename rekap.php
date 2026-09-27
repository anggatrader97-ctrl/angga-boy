<?php
// Query untuk rekap saldo per siswa
$rekap = $koneksi->query("
    SELECT s.id, s.nis, s.nama, s.kelas, s.saldo,
           COUNT(t.id) as total_transaksi
    FROM siswa s
    LEFT JOIN tabungan_siswa t ON s.id = t.siswa_id
    GROUP BY s.id
    ORDER BY s.nama
");
?>

<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title">Rekap Saldo Tabungan Siswa</h3>
    <div class="pull-right">
      <a href="javascript:window.print()" class="btn btn-default btn-sm">
        <i class="fa fa-print"></i> Cetak
      </a>
    </div>
  </div>
  <div class="box-body">
    <table id="tabel-rekap" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>No</th>
          <th>NIS</th>
          <th>Nama</th>
          <th>Kelas</th>
          <th>Saldo</th>
          <th>Total Transaksi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1; 
        while($data = $rekap->fetch_assoc()){ 
        ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $data['nis'] ?></td>
          <td><?= $data['nama'] ?></td>
          <td><?= $data['kelas'] ?></td>
          <td>
            <span class="label label-<?= $data['saldo'] >= 0 ? 'success' : 'danger' ?>">
              Rp<?= number_format($data['saldo'], 0, ',', '.') ?>
            </span>
          </td>
          <td><?= $data['total_transaksi'] ?></td>
          <td>
            <a href="?page=tabungan&aksi=detail&id=<?= $data['id'] ?>" class="btn btn-info btn-xs">
              <i class="fa fa-list"></i> Detail
            </a>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<script>
$(document).ready(function() {
    $('#tabel-rekap').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[3, 'asc'], [2, 'asc']],
        "language": {
            "search": "Cari:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });
});
</script>