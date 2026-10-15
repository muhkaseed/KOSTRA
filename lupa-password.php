<?php
require_once __DIR__.'/config/koneksi.php'; require_once __DIR__.'/includes/auth.php';
$pageTitle='Lupa Password'; $message=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf(); $email=trim($_POST['email']??'');
  if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Masukkan alamat email yang valid.';
  else {
    // Token tidak ditampilkan kepada pengguna. Pengiriman email memerlukan konfigurasi SMTP yang dijelaskan di README.
    $stmt=$pdo->prepare("SELECT id FROM users WHERE email=?"); $stmt->execute([$email]); $user=$stmt->fetch();
    if ($user) {
      $token=bin2hex(random_bytes(32)); $hash=hash('sha256',$token);
      $pdo->prepare("UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL")->execute([$user['id']]);
      $pdo->prepare("INSERT INTO password_resets (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 30 MINUTE))")->execute([$user['id'],$hash]);
      // Aplikasi belum mengirim email secara otomatis sampai SMTP dikonfigurasi.
    }
    $message='Jika email terdaftar, instruksi reset akan dikirim setelah pengiriman email dikonfigurasi oleh pengelola.';
  }
}
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php';
?>
<main class="auth-page"><div class="auth-card"><div class="auth-brand">K.</div><span class="eyebrow">PEMULIHAN AKUN</span><h1>Lupa password?</h1><p class="auth-subtitle">Masukkan email yang terhubung dengan akunmu.</p><?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?><?php if($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><form method="post" class="stack-form"><?= csrf_field() ?><label class="field"><span>Email</span><input type="email" name="email" required></label><button class="btn btn-primary btn-block" type="submit">Kirim Instruksi</button></form><p class="auth-switch"><a href="login.php">← Kembali ke login</a></p></div></main>
<?php require __DIR__.'/includes/footer.php'; ?>