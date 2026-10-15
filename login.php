<?php
require_once __DIR__.'/config/koneksi.php'; require_once __DIR__.'/includes/auth.php';
if (is_logged_in()) { redirect(is_admin() ? 'admin.php' : 'index.php'); }
$pageTitle='Login'; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf();
  $email=trim($_POST['email']??''); $password=$_POST['password']??'';
  if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $password==='') $error='Masukkan email dan password yang valid.';
  else {
    $stmt=$pdo->prepare("SELECT id,nama,email,password_hash,role FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); $user=$stmt->fetch();
    if ($user && password_verify($password,$user['password_hash'])) {
      session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['user_nama']=$user['nama']; $_SESSION['user_role']=$user['role'];
      redirect($user['role']==='admin'?'admin.php':'index.php');
    } else $error='Email atau password salah.';
  }
}
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php';
?>
<main class="auth-page"><div class="auth-card"><div class="auth-brand">K.</div><span class="eyebrow">SELAMAT DATANG KEMBALI</span><h1>Login ke KOSTRA</h1><p class="auth-subtitle">Masuk untuk melihat detail dan informasi kontak kos.</p>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="stack-form"><?= csrf_field() ?><label class="field"><span>Email</span><input type="email" name="email" autocomplete="email" required value="<?= e($_POST['email']??'') ?>"></label><label class="field"><span>Password</span><input type="password" name="password" autocomplete="current-password" required></label><div class="form-between"><a href="lupa-password.php">Lupa password?</a></div><button class="btn btn-primary btn-block" type="submit">Login</button></form><p class="auth-switch">Belum punya akun? <a href="register.php">Daftar sekarang</a></p></div></main>
<?php require __DIR__.'/includes/footer.php'; ?>