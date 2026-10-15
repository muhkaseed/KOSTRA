<?php
require_once __DIR__.'/config/koneksi.php'; require_once __DIR__.'/includes/auth.php';
if (is_logged_in()) redirect('index.php');
$pageTitle='Daftar Akun'; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf(); $nama=trim($_POST['nama']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??''; $confirm=$_POST['konfirmasi']??'';
  if ($nama==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Nama dan email harus diisi dengan benar.';
  elseif (strlen($pass)<8 || !preg_match('/[A-Za-z]/',$pass) || !preg_match('/[0-9]/',$pass)) $error='Password minimal 8 karakter dan mengandung huruf serta angka.';
  elseif ($pass!==$confirm) $error='Konfirmasi password tidak cocok.';
  else {
    $stmt=$pdo->prepare("SELECT id FROM users WHERE email=?"); $stmt->execute([$email]);
    if ($stmt->fetch()) $error='Email sudah terdaftar.';
    else { $stmt=$pdo->prepare("INSERT INTO users (nama,email,password_hash,role) VALUES (?,?,?,'user')"); $stmt->execute([$nama,$email,password_hash($pass,PASSWORD_DEFAULT)]); redirect('login.php?daftar=berhasil'); }
  }
}
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php';
?>
<main class="auth-page"><div class="auth-card"><div class="auth-brand">K.</div><span class="eyebrow">MULAI BERSAMA KOSTRA</span><h1>Buat akun baru</h1><p class="auth-subtitle">Daftar untuk mengakses informasi lengkap kos.</p><?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="stack-form"><?= csrf_field() ?><label class="field"><span>Nama lengkap</span><input type="text" name="nama" autocomplete="name" required value="<?= e($_POST['nama']??'') ?>"></label><label class="field"><span>Email</span><input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email']??'') ?>"></label><label class="field"><span>Password</span><input type="password" name="password" autocomplete="new-password" minlength="8" required><small>Minimal 8 karakter, mengandung huruf dan angka.</small></label><label class="field"><span>Konfirmasi password</span><input type="password" name="konfirmasi" autocomplete="new-password" required></label><button class="btn btn-primary btn-block" type="submit">Buat Akun</button></form><p class="auth-switch">Sudah punya akun? <a href="login.php">Login</a></p></div></main>
<?php require __DIR__.'/includes/footer.php'; ?>