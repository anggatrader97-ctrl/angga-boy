<?php 
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
include "include/koneksi.php";
session_start();

if (isset($_POST['login'])) {
    $username = addslashes(trim($_POST['username']));
    $pass = addslashes(trim($_POST['pass']));

    $sql = $koneksi->query("select * from tb_user where username='$username' and password='$pass'");
    $data = $sql->fetch_assoc();
    $ketemu = $sql->num_rows;

    if ($ketemu >= 1) {
        if ($data['level'] == "admin") {
            $_SESSION['admin'] = $data['id'];
            header("location:index.php");
            exit();
        } else if ($data['level'] == "user") {
            $_SESSION['user'] = $data['id'];
            header("location:index.php");
            exit();
        }
    } else {
        $error = true;
    }
}

// Ambil data profil sekolah dari database
$sql2 = $koneksi->query("SELECT * FROM tb_profile");
$profile = $sql2->fetch_assoc();

// Jika tidak ada data, buat data default
if (!$profile) {
    $profile = array(
        'nama_sekolah' => 'MTs Jaya',
        'foto' => 'default_logo.png',
        'alamat' => 'Jl. Pendidikan No. 123',
        'telpon' => '(031) 1234567',
        'website' => 'www.mtsjaya.sch.id',
        'kota' => 'Probolinggo'
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Login - <?php echo $profile['nama_sekolah']; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="description" content="Sistem Pembayaran Sekolah Terintegrasi">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  
  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
      --border-color: #e0e0e0;
      --shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s ease;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    
    .login-container {
      width: 100%;
      max-width: 400px;
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow);
      overflow: hidden;
      animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .login-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 30px 20px;
      text-align: center;
    }
    
    .school-logo {
      width: 80px;
      height: 80px;
      margin: 0 auto 15px;
      border-radius: 50%;
      background: white;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 5px;
    }
    
    .school-logo img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
    }
    
    .school-name {
      font-size: 22px;
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .system-name {
      font-size: 14px;
      opacity: 0.9;
      font-weight: 400;
    }
    
    .login-body {
      padding: 30px;
    }
    
    .login-title {
      text-align: center;
      margin-bottom: 25px;
    }
    
    .login-title h2 {
      color: var(--primary-color);
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
    }
    
    .login-title p {
      color: #666;
      font-size: 14px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .input-group {
      position: relative;
    }
    
    .form-control {
      width: 100%;
      height: 50px;
      padding: 0 15px 0 45px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 15px;
      color: var(--dark-color);
      transition: var(--transition);
      background: #fafafa;
    }
    
    .form-control:focus {
      outline: none;
      border-color: var(--secondary-color);
      background: white;
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    
    .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #777;
      font-size: 18px;
    }
    
    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #777;
      cursor: pointer;
      font-size: 16px;
      padding: 5px;
    }
    
    .password-toggle:hover {
      color: var(--secondary-color);
    }
    
    .btn-login {
      width: 100%;
      height: 50px;
      background: var(--secondary-color);
      border: none;
      border-radius: 8px;
      color: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    .btn-login:hover {
      background: #2980b9;
    }
    
    .login-footer {
      margin-top: 25px;
      text-align: center;
      color: #666;
      font-size: 13px;
      padding-top: 20px;
      border-top: 1px solid var(--border-color);
    }
    
    .school-info {
      font-size: 12px;
      line-height: 1.5;
      margin-top: 10px;
      color: #777;
    }
    
    .school-info i {
      margin-right: 5px;
      width: 15px;
      text-align: center;
    }
    
    /* Demo credentials (for testing only - remove in production) */
    .demo-credentials {
      background: #f8f9fa;
      border: 1px dashed #dee2e6;
      border-radius: 6px;
      padding: 10px;
      margin-top: 20px;
      font-size: 12px;
      color: #666;
    }
    
    .demo-credentials h4 {
      font-size: 13px;
      margin-bottom: 5px;
      color: var(--primary-color);
    }
    
    /* Responsive */
    @media (max-width: 480px) {
      .login-container {
        max-width: 100%;
      }
      
      .login-header {
        padding: 25px 15px;
      }
      
      .login-body {
        padding: 25px 20px;
      }
      
      .school-logo {
        width: 70px;
        height: 70px;
      }
      
      .school-name {
        font-size: 20px;
      }
    }
    
    @media (max-width: 360px) {
      .login-header {
        padding: 20px 15px;
      }
      
      .login-body {
        padding: 20px 15px;
      }
      
      .school-name {
        font-size: 18px;
      }
      
      .form-control {
        height: 45px;
      }
      
      .btn-login {
        height: 45px;
      }
    }
  </style>
</head>
<body>
  <!-- Main Login Container -->
  <div class="login-container">
    <!-- Header with School Info -->
    <div class="login-header">
      <div class="school-logo">
        <img src="images/<?php echo $profile['foto']; ?>" 
             alt="Logo <?php echo $profile['nama_sekolah']; ?>" 
             onerror="this.src='https://via.placeholder.com/80/2c3e50/ffffff?text=LOGO'">
      </div>
      <h1 class="school-name"><?php echo $profile['nama_sekolah']; ?></h1>
      <p class="system-name">Sistem Pembayaran Sekolah</p>
    </div>
    
    <!-- Login Form -->
    <div class="login-body">
      <div class="login-title">
        <h2>Selamat Datang</h2>
        <p>Silakan masuk dengan akun Anda</p>
      </div>
      
      <form method="post" id="loginForm">
        <div class="form-group">
          <div class="input-group">
            <input type="text" class="form-control" name="username" id="username" 
                   placeholder="Username / NIS" required>
            <span class="input-icon">
              <i class="fas fa-user"></i>
            </span>
          </div>
        </div>
        
        <div class="form-group">
          <div class="input-group">
            <input type="password" class="form-control" name="pass" id="password" 
                   placeholder="Password" required>
            <span class="input-icon">
              <i class="fas fa-lock"></i>
            </span>
            <button type="button" class="password-toggle" id="togglePassword">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        
        <button type="submit" name="login" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Masuk ke Sistem
        </button>
        
        <div class="login-footer">
          <p>Hubungi administrator jika mengalami masalah login</p>
          <div class="school-info">
            <p><i class="fas fa-map-marker-alt"></i> <?php echo $profile['alamat']; ?>, <?php echo $profile['kota']; ?></p>
            <p><i class="fas fa-phone"></i> <?php echo $profile['telpon']; ?></p>
            <p><small>© <?php echo date('Y'); ?> <?php echo $profile['nama_sekolah']; ?></small></p>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    // Password toggle
    document.getElementById('togglePassword').addEventListener('click', function() {
      const password = document.getElementById('password');
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
    
    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value.trim();
      
      if (!username || !password) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Data Tidak Lengkap',
          text: 'Silakan isi username dan password terlebih dahulu.',
          confirmButtonColor: '#3498db'
        });
      }
    });
    
    // Auto focus on username field
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('username').focus();
    });
    
    // Show error message if login failed
    <?php if (isset($error) && $error): ?>
      setTimeout(() => {
        Swal.fire({
          icon: 'error',
          title: 'Login Gagal',
          text: 'Username atau password yang Anda masukkan salah.',
          confirmButtonColor: '#e74c3c',
          confirmButtonText: 'Coba Lagi'
        });
      }, 300);
    <?php endif; ?>
  </script>
</body>
</html>