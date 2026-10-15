<?php
require_once __DIR__.'/config/koneksi.php'; require_once __DIR__.'/includes/auth.php';
$pageTitle='Reset Password'; $error=''; $success='';
$token=trim($_GET['token']??$_POST['token']??'');
$reset=null;
if (preg_match('/^[a-f0-9]{64}$/',$token)) { $stmt=$pdo->prepare("SELECT id,user_id FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1"); $stmt->execute([hash('sha256',$token)]); $reset=$stmt->fetch(); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf();
  $pass=$_POST['password']??''; $confirm=$_POST['konfirmasi']??'';
  if (!$reset) $error='Token reset tidak valid atau sudah kedaluwarsa.';
  elseif (strlen($pass)<8 || !preg_match('/[A-Za-z]/',$pass) || !preg_match('/[0-9]/',$pass)) $error='Password minimal 8 karakter dan mengandung huruf serta angka.';
  elseif ($pass!==$confirm) $error='Konfirmasi password tidak cocok.';
  else { $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($pass,PASSWORD_DEFAULT),$reset['user_id']]); $pdo->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?")->execute([$reset['id']]); $success='Password berhasil diubah. Silakan login kembali.'; $reset=null; }
}
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php';
?>
<main class="auth-page"><div class="auth-card"><div class="auth-brand">K.</div><span class="eyebrow">KEAMANAN AKUN</span><h1>Reset password</h1><?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><a class="btn btn-primary btn-block" href="login.php">Ke halaman login</a><?php elseif($reset): ?><p class="auth-subtitle">Buat password baru untuk akunmu.</p><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><label class="field"><span>Password baru</span><input type="password" name="password" minlength="8" required></label><label class="field"><span>Konfirmasi password</span><input type="password" name="konfirmasi" required></label><button class="btn btn-primary btn-block" type="submit">Simpan Password</button></form><?php else: ?><p class="auth-subtitle">Token reset tidak valid atau sudah kedaluwarsa. Minta instruksi reset yang baru.</p><a class="btn btn-primary btn-block" href="lupa-password.php">Minta reset baru</a><?php endif; ?></div></main>
<?php require __DIR__.'/includes/footer.php'; ?>