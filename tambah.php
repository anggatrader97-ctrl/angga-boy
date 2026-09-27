<?php
if(isset($_POST['simpan'])){
    $siswa_id = $_POST['siswa_id'];
    $jenis = $_POST['jenis'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];
    $admin_id = $_SESSION['user_id']; // ID admin yang login
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    
    // Validasi saldo untuk penarikan
    if($jenis == 'tarik'){
        $cek_saldo = $koneksi->query("SELECT saldo FROM siswa WHERE id = '$siswa_id'");
        $saldo = $cek_saldo->fetch_assoc()['saldo'];
        if($saldo < $jumlah){
            echo "<script>alert('Saldo tidak mencukupi! Saldo tersedia: Rp".number_format($saldo,0,',','.')."');</script>";
            return;
        }
    }
    
    // Simpan transaksi
    $simpan = $koneksi->query("INSERT INTO tabungan_siswa (siswa_id, jenis_transaksi, jumlah, tanggal, waktu, keterangan, admin_id) 
                              VALUES ('$siswa_id', '$jenis', '$jumlah', '$tanggal', '$waktu', '$keterangan', '$admin_id')");
    
    if($simpan){
        // Update saldo siswa
        $operator = ($jenis == 'setor') ? '+' : '-';
        $update_saldo = $koneksi->query("UPDATE siswa SET saldo = saldo $operator $jumlah WHERE id = '$siswa_id'");
        
        echo "<script>alert('Transaksi berhasil disimpan!');</script>";
        echo "<script>window.location.href = '?page=tabungan';</script>";
    } else {
        echo "<script>alert('Transaksi gagal disimpan!');</script>";
    }
}
?>

<div class="box box-primary">
  <div class="box-header with-border">
    <h3 class="box-title">Tambah Transaksi Tabungan</h3>
  </div>
  <form method="post">
    <div class="box-body">
      <div class="form-group">
        <label>Pilih Siswa</label>
        <select class="form-control select2" name="siswa_id" required>
          <option value="">-- Pilih Siswa --</option>
          <?php
          $siswa = $koneksi->query("SELECT id, nis, nama, kelas, saldo FROM siswa ORDER BY nama");
          while($s = $siswa->fetch_assoc()){
            echo "<option value='{$s['id']}' data-saldo='{$s['saldo']}'>{$s['nis']} - {$s['nama']} ({$s['kelas']}) - Saldo: Rp".number_format($s['saldo'],0,',','.')."</option>";
          }
          ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Jenis Transaksi</label>
        <select class="form-control" name="jenis" required>
          <option value="setor">Setor Tabungan</option>
          <option value="tarik">Tarik Tabungan</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Jumlah (Rp)</label>
        <input type="number" class="form-control" name="jumlah" min="0" required>
      </div>
      
      <div class="form-group">
        <label>Keterangan</label>
        <textarea class="form-control" name="keterangan" rows="2"></textarea>
      </div>
    </div>
    <div class="box-footer">
      <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
      <a href="?page=tabungan" class="btn btn-default">Batal</a>
    </div>
  </form>
</div>

<script>
$(document).ready(function(){
    // Inisialisasi select2
    $('.select2').select2();
    
    // Validasi jumlah penarikan
    $('select[name="siswa_id"]').change(function(){
        var saldo = $(this).find(':selected').data('saldo');
        $('input[name="jumlah"]').attr('max', saldo);
    });
});
</script>