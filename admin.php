<?php
require_once __DIR__.'/config/koneksi.php'; require_once __DIR__.'/includes/auth.php'; require_admin();
$page = $_GET['page'] ?? 'dashboard';
$allowed=['dashboard','kos','lokasi','fasilitas','pengguna'];
if (!in_array($page,$allowed,true)) $page='dashboard';
$error=''; $success='';
function admin_redirect($page='dashboard') { redirect('admin.php?page='.urlencode($page)); }
function clean_upload_name($name) { return preg_replace('/[^a-zA-Z0-9._-]/','_',basename($name)); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf(); $action=$_POST['action']??'';
  try {
    if ($action==='kos_save') {
      $id=(int)($_POST['id']??0); $nama=trim($_POST['nama_kos']??''); $lokasi=(int)($_POST['lokasi_id']??0); $harga=filter_var($_POST['harga']??null,FILTER_VALIDATE_INT); $desc=trim($_POST['deskripsi']??''); $kontak=preg_replace('/\D/','',$_POST['kontak']??''); $total=filter_var($_POST['jumlah_kamar_total']??null,FILTER_VALIDATE_INT); $tersedia=filter_var($_POST['jumlah_kamar_tersedia']??null,FILTER_VALIDATE_INT); $aktif=isset($_POST['status_aktif'])?1:0; $rekom=isset($_POST['is_rekomendasi'])?1:0;
      if ($nama===''||$lokasi<1||$harga===false||$harga<0||$desc===''||$kontak===''||$total===false||$tersedia===false||$total<0||$tersedia<0||$tersedia>$total) throw new RuntimeException('Periksa kembali data kos. Harga dan jumlah kamar harus valid.');
      $st=$pdo->prepare("SELECT id FROM lokasi WHERE id=? AND status_aktif=1"); $st->execute([$lokasi]); if(!$st->fetch()) throw new RuntimeException('Pilih lokasi yang aktif.');
      if ($rekom) { $st=$pdo->prepare("SELECT COUNT(*) FROM kos WHERE is_rekomendasi=1 AND status_aktif=1 AND id<>?"); $st->execute([$id]); if ((int)$st->fetchColumn()>=4 && $aktif) throw new RuntimeException('Maksimal empat kos aktif dapat menjadi rekomendasi.'); }
      if ($id) {
        $st=$pdo->prepare("UPDATE kos SET nama_kos=?,lokasi_id=?,harga=?,deskripsi=?,kontak=?,jumlah_kamar_total=?,jumlah_kamar_tersedia=?,status_aktif=?,is_rekomendasi=?,updated_at=NOW() WHERE id=?");
        $st->execute([$nama,$lokasi,$harga,$desc,$kontak,$total,$tersedia,$aktif,$rekom,$id]);
      } else {
        $st=$pdo->prepare("INSERT INTO kos (nama_kos,lokasi_id,harga,deskripsi,kontak,jumlah_kamar_total,jumlah_kamar_tersedia,status_aktif,is_rekomendasi) VALUES (?,?,?,?,?,?,?,?,?)");
        $st->execute([$nama,$lokasi,$harga,$desc,$kontak,$total,$tersedia,$aktif,$rekom]); $id=(int)$pdo->lastInsertId();
      }
      $fac=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['fasilitas']??[])))));
      $pdo->prepare("DELETE FROM kos_fasilitas WHERE kos_id=?")->execute([$id]);
      foreach($fac as $fid) { $pdo->prepare("INSERT INTO kos_fasilitas (kos_id,fasilitas_id) SELECT ?,id FROM fasilitas WHERE id=? AND status_aktif=1")->execute([$id,$fid]); }
      if (!empty($_FILES['foto']['name'][0])) {
        $allowedMime=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $uploadDir=__DIR__.'/uploads/kos/'; if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
        $count=count($_FILES['foto']['name']);
        for($i=0;$i<$count;$i++) {
          if ($_FILES['foto']['error'][$i]===UPLOAD_ERR_NO_FILE) continue;
          if ($_FILES['foto']['error'][$i]!==UPLOAD_ERR_OK) throw new RuntimeException('Ada foto yang gagal diunggah.');
          if ($_FILES['foto']['size'][$i]>5*1024*1024) throw new RuntimeException('Ukuran setiap foto maksimal 5 MB.');
          $tmp=$_FILES['foto']['tmp_name'][$i]; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
          if(!isset($allowedMime[$mime]) || !getimagesize($tmp)) throw new RuntimeException('Foto harus berformat JPG, PNG, atau WEBP.');
          $filename=bin2hex(random_bytes(12)).'.'.$allowedMime[$mime]; if(!move_uploaded_file($tmp,$uploadDir.$filename)) throw new RuntimeException('Foto gagal disimpan.');
          $path='uploads/kos/'.$filename; $pdo->prepare("INSERT INTO foto_kos (kos_id,path_file,is_utama) VALUES (?,?,?)")->execute([$id,$path,0]);
        }
      }
      $st=$pdo->prepare("SELECT COUNT(*) FROM foto_kos WHERE kos_id=?"); $st->execute([$id]); if((int)$st->fetchColumn()<1) throw new RuntimeException('Setiap kos wajib memiliki minimal satu foto. Tambahkan foto sebelum menyimpan kos.');
      $pdo->prepare("UPDATE foto_kos SET is_utama=0 WHERE kos_id=?")->execute([$id]);
      $st=$pdo->prepare("UPDATE foto_kos SET is_utama=1 WHERE kos_id=? ORDER BY id ASC LIMIT 1"); $st->execute([$id]);
      admin_redirect('kos');
    } elseif ($action==='kos_delete') {
      $id=(int)($_POST['id']??0); $st=$pdo->prepare("SELECT path_file FROM foto_kos WHERE kos_id=?"); $st->execute([$id]); $photos=$st->fetchAll();
      $pdo->prepare("DELETE FROM kos WHERE id=?")->execute([$id]);
      foreach($photos as $photo) { $path=__DIR__.'/'.$photo['path_file']; if(is_file($path)) @unlink($path); }
      admin_redirect('kos');
    } elseif ($action==='kos_status') {
      $id=(int)($_POST['id']??0); $pdo->prepare("UPDATE kos SET status_aktif=1-status_aktif,updated_at=NOW() WHERE id=?")->execute([$id]); admin_redirect('kos');
    } elseif ($action==='kos_foto_delete') {
      $id=(int)($_POST['id']??0); $st=$pdo->prepare("SELECT kos_id,path_file,is_utama FROM foto_kos WHERE id=?"); $st->execute([$id]); $ph=$st->fetch();
      if($ph) { $st=$pdo->prepare("SELECT COUNT(*) FROM foto_kos WHERE kos_id=?"); $st->execute([$ph['kos_id']]); if((int)$st->fetchColumn()<=1) throw new RuntimeException('Foto terakhir tidak dapat dihapus. Tambahkan foto pengganti terlebih dahulu.'); $pdo->prepare("DELETE FROM foto_kos WHERE id=?")->execute([$id]); $path=__DIR__.'/'.$ph['path_file']; if(is_file($path)) @unlink($path); if($ph['is_utama']) $pdo->prepare("UPDATE foto_kos SET is_utama=1 WHERE kos_id=? ORDER BY id ASC LIMIT 1")->execute([$ph['kos_id']]); }
      admin_redirect('kos');
    } elseif ($action==='foto_utama') {
      $id=(int)($_POST['id']??0); $st=$pdo->prepare("SELECT kos_id FROM foto_kos WHERE id=?"); $st->execute([$id]); $ph=$st->fetch(); if($ph) { $pdo->prepare("UPDATE foto_kos SET is_utama=0 WHERE kos_id=?")->execute([$ph['kos_id']]); $pdo->prepare("UPDATE foto_kos SET is_utama=1 WHERE id=?")->execute([$id]); } admin_redirect('kos');
    } elseif ($action==='lokasi_save') {
      $id=(int)($_POST['id']??0); $nama=trim($_POST['nama_lokasi']??''); $desc=trim($_POST['deskripsi']??''); $aktif=isset($_POST['status_aktif'])?1:0; if($nama==='') throw new RuntimeException('Nama lokasi wajib diisi.');
      if($id) $pdo->prepare("UPDATE lokasi SET nama_lokasi=?,deskripsi=?,status_aktif=? WHERE id=?")->execute([$nama,$desc,$aktif,$id]);
      else $pdo->prepare("INSERT INTO lokasi (nama_lokasi,deskripsi,status_aktif) VALUES (?,?,?)")->execute([$nama,$desc,$aktif]); admin_redirect('lokasi');
    } elseif ($action==='lokasi_delete') {
      $id=(int)$_POST['id']; $st=$pdo->prepare("SELECT COUNT(*) FROM kos WHERE lokasi_id=?"); $st->execute([$id]); if((int)$st->fetchColumn()) throw new RuntimeException('Lokasi yang sudah digunakan kos tidak dapat dihapus.'); $pdo->prepare("DELETE FROM lokasi WHERE id=?")->execute([$id]); admin_redirect('lokasi');
    } elseif ($action==='fasilitas_save') {
      $id=(int)($_POST['id']??0); $nama=trim($_POST['nama_fasilitas']??''); $aktif=isset($_POST['status_aktif'])?1:0; if($nama==='') throw new RuntimeException('Nama fasilitas wajib diisi.');
      if($id) $pdo->prepare("UPDATE fasilitas SET nama_fasilitas=?,status_aktif=? WHERE id=?")->execute([$nama,$aktif,$id]); else $pdo->prepare("INSERT INTO fasilitas (nama_fasilitas,status_aktif) VALUES (?,?)")->execute([$nama,$aktif]); admin_redirect('fasilitas');
    } elseif ($action==='fasilitas_delete') {
      $id=(int)$_POST['id']; $st=$pdo->prepare("SELECT COUNT(*) FROM kos_fasilitas WHERE fasilitas_id=?"); $st->execute([$id]); if((int)$st->fetchColumn()) throw new RuntimeException('Fasilitas yang sudah digunakan kos tidak dapat dihapus.'); $pdo->prepare("DELETE FROM fasilitas WHERE id=?")->execute([$id]); admin_redirect('fasilitas');
    }
  } catch (Throwable $ex) { $error=$ex instanceof PDOException ? 'Operasi gagal. Periksa data dan relasi database.' : $ex->getMessage(); }
}
$stats=[];
foreach ([
 'kos_aktif'=>"SELECT COUNT(*) FROM kos WHERE status_aktif=1",
 'kos_nonaktif'=>"SELECT COUNT(*) FROM kos WHERE status_aktif=0",
 'pengguna'=>"SELECT COUNT(*) FROM users WHERE role='user'",
 'lokasi'=>"SELECT COUNT(*) FROM lokasi WHERE status_aktif=1",
 'fasilitas'=>"SELECT COUNT(*) FROM fasilitas WHERE status_aktif=1",
 'tersedia'=>"SELECT COUNT(*) FROM kos WHERE status_aktif=1 AND jumlah_kamar_tersedia>0",
 'penuh'=>"SELECT COUNT(*) FROM kos WHERE status_aktif=1 AND jumlah_kamar_tersedia=0"
] as $key=>$sql) $stats[$key]=(int)$pdo->query($sql)->fetchColumn();
$editKos=null; $editLokasi=null; $editFasilitas=null;
if ($page==='kos' && isset($_GET['edit'])) { $st=$pdo->prepare("SELECT * FROM kos WHERE id=?"); $st->execute([(int)$_GET['edit']]); $editKos=$st->fetch(); }
if ($page==='lokasi' && isset($_GET['edit'])) { $st=$pdo->prepare("SELECT * FROM lokasi WHERE id=?"); $st->execute([(int)$_GET['edit']]); $editLokasi=$st->fetch(); }
if ($page==='fasilitas' && isset($_GET['edit'])) { $st=$pdo->prepare("SELECT * FROM fasilitas WHERE id=?"); $st->execute([(int)$_GET['edit']]); $editFasilitas=$st->fetch(); }
require __DIR__.'/includes/header.php';
?>
<div class="admin-layout"><aside class="admin-sidebar"><a class="admin-brand" href="admin.php"><span class="brand-mark">K.</span><span>KOSTRA<small>ADMIN PANEL</small></span></a><nav class="admin-nav"><a class="<?= $page==='dashboard'?'active':'' ?>" href="admin.php">▦ <span>Dashboard</span></a><a class="<?= $page==='kos'?'active':'' ?>" href="admin.php?page=kos">⌂ <span>Kelola Kos</span></a><a class="<?= $page==='lokasi'?'active':'' ?>" href="admin.php?page=lokasi">⌖ <span>Kelola Lokasi</span></a><a class="<?= $page==='fasilitas'?'active':'' ?>" href="admin.php?page=fasilitas">◇ <span>Kelola Fasilitas</span></a><a class="<?= $page==='pengguna'?'active':'' ?>" href="admin.php?page=pengguna">♙ <span>Pengguna</span></a></nav><div class="admin-sidebar-bottom"><a href="index.php">← Lihat website</a><a href="logout.php">Keluar</a></div></aside>
<div class="admin-main"><header class="admin-topbar"><div><span class="eyebrow">PANEL PENGELOLA</span><h1><?= e(ucfirst($page==='kos'?'Kelola Kos':($page==='lokasi'?'Kelola Lokasi':($page==='fasilitas'?'Kelola Fasilitas':($page==='pengguna'?'Pengguna':'Dashboard'))))) ?></h1></div><div class="admin-user"><span class="profile-avatar small"><?= e(strtoupper(substr($_SESSION['user_nama']??'A',0,1))) ?></span><div><b><?= e($_SESSION['user_nama']??'Admin') ?></b><small>Administrator</small></div></div></header>
<?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if($page==='dashboard'): ?>
<div class="stats-grid"><?php foreach([['Kos aktif',$stats['kos_aktif'],'⌂'],['Kos nonaktif',$stats['kos_nonaktif'],'◷'],['Kamar tersedia',$stats['tersedia'],'✓'],['Kos penuh',$stats['penuh'],'!'],['Pengguna',$stats['pengguna'],'♙'],['Lokasi aktif',$stats['lokasi'],'⌖']] as $stat): ?><article class="stat-card"><span><?= e($stat[0]) ?></span><div><strong><?= (int)$stat[1] ?></strong><i><?= e($stat[2]) ?></i></div></article><?php endforeach; ?></div>
<section class="admin-panel"><div class="panel-heading"><div><h2>Mulai kelola KOSTRA</h2><p>Tambahkan informasi kos agar bisa dilihat oleh pengunjung.</p></div><a class="btn btn-primary" href="admin.php?page=kos">Kelola Kos →</a></div><div class="quick-links"><a href="admin.php?page=lokasi">Kelola lokasi <span>→</span></a><a href="admin.php?page=fasilitas">Kelola fasilitas <span>→</span></a><a href="admin.php?page=pengguna">Lihat pengguna <span>→</span></a></div></section>
<?php elseif($page==='kos'): ?>
<?php $locations=$pdo->query("SELECT id,nama_lokasi FROM lokasi ORDER BY nama_lokasi")->fetchAll(); $facilities=$pdo->query("SELECT id,nama_fasilitas,status_aktif FROM fasilitas ORDER BY nama_fasilitas")->fetchAll(); $kosRows=$pdo->query("SELECT k.*,l.nama_lokasi FROM kos k JOIN lokasi l ON l.id=k.lokasi_id ORDER BY k.status_aktif DESC,k.created_at DESC")->fetchAll(); $currentFac=[]; $currentPhotos=[]; if($editKos){$st=$pdo->prepare("SELECT fasilitas_id FROM kos_fasilitas WHERE kos_id=?");$st->execute([$editKos['id']]);$currentFac=array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN));$st=$pdo->prepare("SELECT * FROM foto_kos WHERE kos_id=? ORDER BY is_utama DESC,id");$st->execute([$editKos['id']]);$currentPhotos=$st->fetchAll();} ?>
<section class="admin-panel"><div class="panel-heading"><div><h2><?= $editKos?'Edit kos':'Tambah kos' ?></h2><p>Isi informasi utama dan unggah setidaknya satu foto.</p></div><?php if($editKos): ?><a class="btn btn-outline" href="admin.php?page=kos">Batal Edit</a><?php endif; ?></div>
<form method="post" enctype="multipart/form-data" class="admin-form"><?= csrf_field() ?><input type="hidden" name="action" value="kos_save"><input type="hidden" name="id" value="<?= (int)($editKos['id']??0) ?>">
<div class="form-grid"><label class="field"><span>Nama kos *</span><input name="nama_kos" required value="<?= e($editKos['nama_kos']??'') ?>"></label><label class="field"><span>Lokasi *</span><select name="lokasi_id" required><option value="">Pilih lokasi</option><?php foreach($locations as $l): ?><option value="<?= (int)$l['id'] ?>" <?= (int)($editKos['lokasi_id']??0)===(int)$l['id']?'selected':'' ?>><?= e($l['nama_lokasi']) ?></option><?php endforeach; ?></select></label><label class="field"><span>Harga per bulan (Rp) *</span><input type="number" min="0" name="harga" required value="<?= e($editKos['harga']??'') ?>"></label><label class="field"><span>Nomor WhatsApp (628...)*</span><input name="kontak" inputmode="numeric" required value="<?= e($editKos['kontak']??'') ?>"></label><label class="field"><span>Jumlah kamar total *</span><input type="number" min="0" name="jumlah_kamar_total" required value="<?= e($editKos['jumlah_kamar_total']??'') ?>"></label><label class="field"><span>Jumlah kamar tersedia *</span><input type="number" min="0" name="jumlah_kamar_tersedia" required value="<?= e($editKos['jumlah_kamar_tersedia']??'') ?>"></label><label class="field full-field"><span>Deskripsi *</span><textarea name="deskripsi" rows="4" required><?= e($editKos['deskripsi']??'') ?></textarea></label><label class="field full-field"><span>Foto kos (JPG, PNG, WEBP; maks. 5 MB per foto)</span><input type="file" name="foto[]" accept="image/jpeg,image/png,image/webp" multiple <?= $editKos?'':'required' ?>><small>Foto yang sudah ada tetap disimpan saat edit.</small></label></div>
<div class="check-grid admin-checks"><?php foreach($facilities as $f): ?><label class="check-option"><input type="checkbox" name="fasilitas[]" value="<?= (int)$f['id'] ?>" <?= in_array((int)$f['id'],$currentFac,true)?'checked':'' ?> <?= !$f['status_aktif']?'disabled':'' ?>><span><?= e($f['nama_fasilitas']) ?><?= !$f['status_aktif']?' (nonaktif)':'' ?></span></label><?php endforeach; ?></div>
<div class="admin-toggles"><label><input type="checkbox" name="status_aktif" value="1" <?= !isset($editKos['status_aktif']) || $editKos['status_aktif']?'checked':'' ?>> Kos aktif</label><label><input type="checkbox" name="is_rekomendasi" value="1" <?= !empty($editKos['is_rekomendasi'])?'checked':'' ?>> Jadikan rekomendasi</label></div><button class="btn btn-primary" type="submit">Simpan Kos</button></form>
<?php if($currentPhotos): ?><h3 class="subheading">Foto saat ini</h3><div class="admin-photo-grid"><?php foreach($currentPhotos as $ph): ?><div class="admin-photo"><img src="<?= e($ph['path_file']) ?>" alt="Foto kos"><?php if($ph['is_utama']): ?><span class="photo-main-label">Foto utama</span><?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="foto_utama"><input type="hidden" name="id" value="<?= (int)$ph['id'] ?>"><button class="mini-action" type="submit">Jadikan utama</button></form><?php endif; ?><form method="post" onsubmit="return confirm('Hapus foto ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="kos_foto_delete"><input type="hidden" name="id" value="<?= (int)$ph['id'] ?>"><button class="mini-action danger" type="submit">Hapus foto</button></form></div><?php endforeach; ?></div><?php endif; ?>
</section>
<section class="admin-panel"><div class="panel-heading"><div><h2>Daftar kos</h2><p><?= count($kosRows) ?> data kos tersimpan.</p></div></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Nama kos</th><th>Lokasi</th><th>Harga/bulan</th><th>Kamar</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($kosRows as $k): ?><tr><td><b><?= e($k['nama_kos']) ?></b></td><td><?= e($k['nama_lokasi']) ?></td><td>Rp<?= number_format((float)$k['harga'],0,',','.') ?></td><td><?= (int)$k['jumlah_kamar_tersedia'] ?>/<?= (int)$k['jumlah_kamar_total'] ?></td><td><span class="status-badge <?= $k['status_aktif']?'available':'full' ?>"><?= $k['status_aktif']?'Aktif':'Nonaktif' ?></span></td><td><div class="table-actions"><a href="admin.php?page=kos&edit=<?= (int)$k['id'] ?>">Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="kos_status"><input type="hidden" name="id" value="<?= (int)$k['id'] ?>"><button type="submit"><?= $k['status_aktif']?'Nonaktifkan':'Aktifkan' ?></button></form><form method="post" onsubmit="return confirm('Hapus kos ini beserta foto dan relasinya?')"><?= csrf_field() ?><input type="hidden" name="action" value="kos_delete"><input type="hidden" name="id" value="<?= (int)$k['id'] ?>"><button class="danger-text" type="submit">Hapus</button></form></div></td></tr><?php endforeach; if(!$kosRows): ?><tr><td colspan="6" class="table-empty">Belum ada kos. Tambahkan data melalui formulir di atas.</td></tr><?php endif; ?></tbody></table></div></section>
<?php elseif($page==='lokasi'): ?>
<?php $locRows=$pdo->query("SELECT l.*,COUNT(k.id) jumlah_kos FROM lokasi l LEFT JOIN kos k ON k.lokasi_id=l.id GROUP BY l.id ORDER BY l.id")->fetchAll(); ?>
<section class="admin-panel"><div class="panel-heading"><div><h2><?= $editLokasi?'Edit lokasi':'Tambah lokasi' ?></h2><p>Lokasi yang nonaktif tidak tersedia untuk kos baru.</p></div></div><form method="post" class="admin-form"><?= csrf_field() ?><input type="hidden" name="action" value="lokasi_save"><input type="hidden" name="id" value="<?= (int)($editLokasi['id']??0) ?>"><div class="form-grid"><label class="field"><span>Nama lokasi *</span><input name="nama_lokasi" required value="<?= e($editLokasi['nama_lokasi']??'') ?>"></label><label class="field"><span>Deskripsi</span><input name="deskripsi" value="<?= e($editLokasi['deskripsi']??'') ?>"></label></div><label class="admin-toggle"><input type="checkbox" name="status_aktif" <?= !isset($editLokasi['status_aktif'])||$editLokasi['status_aktif']?'checked':'' ?>> Aktif</label><button class="btn btn-primary" type="submit">Simpan Lokasi</button></form></section>
<section class="admin-panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>Nama lokasi</th><th>Deskripsi</th><th>Jumlah kos</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($locRows as $l): ?><tr><td><b><?= e($l['nama_lokasi']) ?></b></td><td><?= e($l['deskripsi']) ?></td><td><?= (int)$l['jumlah_kos'] ?></td><td><?= $l['status_aktif']?'Aktif':'Nonaktif' ?></td><td><div class="table-actions"><a href="admin.php?page=lokasi&edit=<?= (int)$l['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus lokasi ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="lokasi_delete"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>"><button class="danger-text" type="submit">Hapus</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php elseif($page==='fasilitas'): ?>
<?php $facRows=$pdo->query("SELECT f.*,COUNT(kf.kos_id) dipakai FROM fasilitas f LEFT JOIN kos_fasilitas kf ON kf.fasilitas_id=f.id GROUP BY f.id ORDER BY f.id")->fetchAll(); ?>
<section class="admin-panel"><div class="panel-heading"><div><h2><?= $editFasilitas?'Edit fasilitas':'Tambah fasilitas' ?></h2><p>Fasilitas aktif dapat dipilih saat menambahkan atau mengedit kos.</p></div></div><form method="post" class="admin-form"><?= csrf_field() ?><input type="hidden" name="action" value="fasilitas_save"><input type="hidden" name="id" value="<?= (int)($editFasilitas['id']??0) ?>"><div class="form-grid"><label class="field"><span>Nama fasilitas *</span><input name="nama_fasilitas" required value="<?= e($editFasilitas['nama_fasilitas']??'') ?>"></label></div><label class="admin-toggle"><input type="checkbox" name="status_aktif" <?= !isset($editFasilitas['status_aktif'])||$editFasilitas['status_aktif']?'checked':'' ?>> Aktif</label><button class="btn btn-primary" type="submit">Simpan Fasilitas</button></form></section>
<section class="admin-panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>Nama fasilitas</th><th>Dipakai kos</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($facRows as $f): ?><tr><td><b><?= e($f['nama_fasilitas']) ?></b></td><td><?= (int)$f['dipakai'] ?></td><td><?= $f['status_aktif']?'Aktif':'Nonaktif' ?></td><td><div class="table-actions"><a href="admin.php?page=fasilitas&edit=<?= (int)$f['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Hapus fasilitas ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="fasilitas_delete"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><button class="danger-text" type="submit">Hapus</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php elseif($page==='pengguna'): ?>
<?php $users=$pdo->query("SELECT id,nama,email,role,created_at FROM users ORDER BY created_at DESC")->fetchAll(); ?>
<section class="admin-panel"><div class="panel-heading"><div><h2>Daftar pengguna</h2><p><?= count($users) ?> akun terdaftar. Data pengguna hanya dapat dilihat admin.</p></div></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Tanggal daftar</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><?= e($u['nama']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td><td><?= e(date('d M Y',strtotime($u['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php endif; ?>
<footer class="admin-footer">KOSTRA Admin · Kelola informasi dengan teliti.</footer></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>