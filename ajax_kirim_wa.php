<?php
// File: SPP-SEKOLAH/ajax_kirim_wa.php
session_start();
include "koneksi.php";
include "include/wa_notification.php"; // Include file WA

header('Content-Type: application/json');

// Cek session admin
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'pembayaran') {
        $id_bayar = $_POST['id_bayar'] ?? '';
        $nis = $_POST['nis'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $jumlah = $_POST['jumlah'] ?? 0;
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $keterangan = $_POST['keterangan'] ?? '';
        $jenis_bayar = $_POST['jenis_bayar'] ?? 'Pembayaran';
        $cara_bayar = $_POST['cara_bayar'] ?? '';
        
        // Validasi data
        if (empty($id_bayar) || empty($nis) || empty($phone) || $phone == '-') {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }
        
        // Bersihkan cara bayar dari tag WA sebelumnya
        $cara_bayar_clean = trim(str_replace(['[WA-SENT]', '[WA-FAILED]'], '', $cara_bayar));
        
        // Ambil id_bayar dari database jika perlu
        $id_bayar_db = $id_bayar; // Anda bisa query untuk mendapatkan id_bayar dari tb_jenis_bayar jika perlu
        
        // Kirim WA menggunakan fungsi dari wa_notification.php
        $success = sendPaymentNotification($nis, $id_bayar_db, $jumlah, $tanggal, $keterangan);
        
        if ($success) {
            // Update status di database - tambahkan [WA-SENT]
            $cara_bayar_new = $cara_bayar_clean . ' [WA-SENT]';
            $update = $koneksi->query("UPDATE tb_bayar_bebas SET cara_bayar = '$cara_bayar_new' WHERE id_bayar_bebas = '$id_bayar'");
            
            echo json_encode([
                'success' => true,
                'message' => '✅ Notifikasi WA berhasil dikirim',
                'cara_bayar_clean' => $cara_bayar_clean
            ]);
        } else {
            // Update status jika gagal - tambahkan [WA-FAILED]
            $cara_bayar_new = $cara_bayar_clean . ' [WA-FAILED]';
            $koneksi->query("UPDATE tb_bayar_bebas SET cara_bayar = '$cara_bayar_new' WHERE id_bayar_bebas = '$id_bayar'");
            
            echo json_encode([
                'success' => false,
                'message' => '❌ Gagal mengirim notifikasi WA'
            ]);
        }
        
    } elseif ($action == 'test') {
        // Fungsi test koneksi WA
        $test_number = $_POST['test_number'] ?? '';
        
        if (empty($test_number)) {
            echo json_encode(['success' => false, 'message' => 'Nomor test kosong']);
            exit;
        }
        
        // Test dengan fungsi testWAConnection jika ada, atau buat sendiri
        if (function_exists('testWAConnection')) {
            $result = testWAConnection($test_number);
        } else {
            // Alternatif test sederhana
            $message = "🎉 *TEST NOTIFIKASI PEMBAYARAN*\\n\\n" .
                       "Halo, ini adalah pesan test dari Sistem Pembayaran.\\n\\n" .
                       "✅ Sistem notifikasi WhatsApp berfungsi dengan baik.\\n" .
                       "📅 Tanggal: " . date('d-m-Y H:i:s') . "\\n\\n" .
                       "_Pesan ini dikirim otomatis oleh sistem._";
            
            // Ambil API Key
            $sql = $koneksi->query("SELECT wa_api_key FROM tb_profile LIMIT 1");
            $settings = $sql->fetch_assoc();
            $api_key = $settings['wa_api_key'] ?? '';
            
            if (empty($api_key)) {
                $result = ['success' => false, 'message' => 'API Key tidak ditemukan'];
            } else {
                // Format nomor
                $phone = preg_replace('/[^0-9]/', '', $test_number);
                if (substr($phone, 0, 1) == '0') {
                    $phone = '62' . substr($phone, 1);
                } elseif (substr($phone, 0, 2) != '62') {
                    $phone = '62' . $phone;
                }
                
                // Kirim test
                $success = sendWhatsAppSidobe($phone, $message);
                $result = $success 
                    ? ['success' => true, 'message' => '✅ Test berhasil dikirim ke ' . $phone]
                    : ['success' => false, 'message' => '❌ Gagal mengirim test'];
            }
        }
        
        echo json_encode($result);
    }
}
?>