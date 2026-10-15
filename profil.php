<?php
require_once __DIR__.'/config/koneksi.php'; require_once __DIR__.'/includes/auth.php'; require_login();
$pageTitle='Profil Saya'; $error=''; $success='';
$stmt=$pdo->prepare("SELECT id,nama,email,created_at,password_hash FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]); $user=$stmt->fetch();
if (!$user) { logout_user(); redirect('login.php'); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf(); $action=$_POST['action']??'';
  if ($action==='nama') {
    $nama=trim($_POST['nama']??'');
    if ($nama==='') $error='Nama tidak boleh kosong.';
    else { $pdo->prepare("UPDATE users SET nama=? WHERE id=?")->execute([$nama,$user['id']]); $_SESSION['user_nama']=$nama; $success='Nama berhasil diperbarui.'; }
  } elseif ($action==='password') {
    $old=$_POST['password_lama']??''; $new=$_POST['password_baru']??''; $confirm=$_POST['konfirmasi']??'';
    if (!password_verify($old,$user['password_hash'])) $error='Password lama tidak sesuai.';
    elseif (strlen($new)<8 || !preg_match('/[A-Za-z]/',$new) || !preg_match('/[0-9]/',$new)) $error='Password baru minimal 8 karakter dan mengandung huruf serta angka.';
    elseif ($new!==$confirm) $error='Konfirmasi password tidak cocok.';
    else { $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$user['id']]); logout_user(); redirect('login.php?password=berubah'); }
  }
  $stmt=$pdo->prepare("SELECT id,nama,email,created_at,password_hash FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]); $user=$stmt->fetch();
}
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php';
?>
<main class="page-main"><div class="container narrow-container"><div class="page-intro"><span class="eyebrow">AKUN KOSTRA</span><h1>Profil saya</h1><p>Kelola informasi akunmu di sini.</p></div><?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<section class="card-panel profile-panel"><div class="profile-avatar"><?= e(strtoupper(substr($user['nama'],0,1))) ?></div><h2><?= e($user['nama']) ?></h2><p class="muted"><?= e($user['email']) ?></p><p class="muted">Bergabung sejak <?= e(date('d M Y',strtotime($user['created_at']))) ?></p></section>
<div class="profile-forms"><section class="card-panel"><h2>Ubah nama</h2><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="nama"><label class="field"><span>Nama lengkap</span><input type="text" name="nama" required value="<?= e($user['nama']) ?>"></label><button class="btn btn-primary" type="submit">Simpan Nama</button></form></section><section class="card-panel"><h2>Ubah password</h2><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="password"><label class="field"><span>Password lama</span><input type="password" name="password_lama" required></label><label class="field"><span>Password baru</span><input type="password" name="password_baru" minlength="8" required></label><label class="field"><span>Konfirmasi password baru</span><input type="password" name="konfirmasi" required></label><button class="btn btn-primary" type="submit">Ubah Password</button></form></section></div><a class="btn btn-outline" href="logout.php">Logout</a></div></main>
<?php require __DIR__.'/includes/footer.php'; ?>