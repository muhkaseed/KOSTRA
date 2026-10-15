<?php
require_once __DIR__ . '/config/koneksi.php';
$pageTitle = 'Cari Kos';
$lokasiList = $pdo->query("SELECT id,nama_lokasi FROM lokasi WHERE status_aktif=1 ORDER BY nama_lokasi")->fetchAll();
$fasilitasList = $pdo->query("SELECT id,nama_fasilitas FROM fasilitas WHERE status_aktif=1 ORDER BY nama_fasilitas")->fetchAll();
$q = trim($_GET['q'] ?? '');
$lokasiId = filter_input(INPUT_GET, 'lokasi', FILTER_VALIDATE_INT) ?: 0;
$min = isset($_GET['min']) && $_GET['min'] !== '' ? max(0, (int)$_GET['min']) : null;
$max = isset($_GET['max']) && $_GET['max'] !== '' ? max(0, (int)$_GET['max']) : null;
$sort = $_GET['sort'] ?? 'terbaru';
$selectedFacilities = array_values(array_filter(array_map('intval', (array)($_GET['fasilitas'] ?? [])), fn($id) => $id > 0));
$where = ["k.status_aktif=1"]; $params = [];
if ($q !== '') { $where[] = "k.nama_kos LIKE ?"; $params[] = "%$q%"; }
if ($lokasiId > 0) { $where[] = "k.lokasi_id=?"; $params[] = $lokasiId; }
if ($min !== null) { $where[] = "k.harga>=?"; $params[] = $min; }
if ($max !== null) { $where[] = "k.harga<=?"; $params[] = $max; }
foreach ($selectedFacilities as $fid) {
  $where[] = "EXISTS (SELECT 1 FROM kos_fasilitas kf WHERE kf.kos_id=k.id AND kf.fasilitas_id=?)";
  $params[] = $fid;
}
$order = match ($sort) { 'termurah' => 'k.harga ASC', 'termahal' => 'k.harga DESC', default => 'k.created_at DESC' };
$sql = "SELECT k.*, l.nama_lokasi, (SELECT f.path_file FROM foto_kos f WHERE f.kos_id=k.id ORDER BY f.is_utama DESC, f.id ASC LIMIT 1) AS foto FROM kos k JOIN lokasi l ON l.id=k.lokasi_id WHERE ".implode(' AND ', $where)." ORDER BY $order";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $kosList = $stmt->fetchAll();
require __DIR__ . '/includes/header.php'; require __DIR__ . '/includes/navbar.php';
?>
<main class="page-main"><div class="container">
  <div class="page-intro"><span class="eyebrow">JELAJAHI PILIHAN</span><h1>Cari kos yang cocok untukmu</h1><p>Gunakan pencarian dan filter untuk menemukan pilihan di sekitar ITS Raya.</p></div>
  <form class="filter-panel" method="get" action="kos.php">
    <div class="filter-main"><label class="field"><span>Nama kos</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama kos..."></label>
      <label class="field"><span>Lokasi</span><select name="lokasi"><option value="">Semua lokasi</option><?php foreach ($lokasiList as $l): ?><option value="<?= (int)$l['id'] ?>" <?= $lokasiId===(int)$l['id']?'selected':'' ?>><?= e($l['nama_lokasi']) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span>Harga minimum</span><input type="number" min="0" name="min" value="<?= $min !== null ? (int)$min : '' ?>" placeholder="Contoh: 500000"></label>
      <label class="field"><span>Harga maksimum</span><input type="number" min="0" name="max" value="<?= $max !== null ? (int)$max : '' ?>" placeholder="Contoh: 1500000"></label>
    </div>
    <div class="facility-filter"><strong>Fasilitas</strong><div class="check-grid"><?php foreach ($fasilitasList as $f): ?><label class="check-option"><input type="checkbox" name="fasilitas[]" value="<?= (int)$f['id'] ?>" <?= in_array((int)$f['id'],$selectedFacilities,true)?'checked':'' ?>><span><?= e($f['nama_fasilitas']) ?></span></label><?php endforeach; ?></div></div>
    <div class="filter-actions"><label class="field sort-field"><span>Urutkan</span><select name="sort"><option value="terbaru" <?= $sort==='terbaru'?'selected':'' ?>>Terbaru</option><option value="termurah" <?= $sort==='termurah'?'selected':'' ?>>Harga termurah</option><option value="termahal" <?= $sort==='termahal'?'selected':'' ?>>Harga termahal</option></select></label><div class="button-row"><a class="btn btn-outline" href="kos.php">Hapus Filter</a><button class="btn btn-primary" type="submit">Terapkan Filter</button></div></div>
  </form>
  <div class="results-heading"><div><h2>Daftar kos</h2><p><?= count($kosList) ?> hasil ditemukan</p></div></div>
  <?php if ($kosList): ?><div class="kos-grid"><?php foreach ($kosList as $item): ?><article class="kos-card"><a class="kos-photo" href="detail-kos.php?id=<?= (int)$item['id'] ?>"><?php if ($item['foto']): ?><img src="<?= e($item['foto']) ?>" alt="<?= e($item['nama_kos']) ?>"><?php else: ?><div class="photo-placeholder"><span>⌂</span><small>Foto kos belum tersedia</small></div><?php endif; ?><span class="status-badge <?= (int)$item['jumlah_kamar_tersedia']>0?'available':'full' ?>"><?= (int)$item['jumlah_kamar_tersedia']>0?'Tersedia':'Penuh' ?></span></a><div class="kos-card-body"><span class="location-label"><?= e($item['nama_lokasi']) ?></span><h3><a href="detail-kos.php?id=<?= (int)$item['id'] ?>"><?= e($item['nama_kos']) ?></a></h3><p class="price">Rp<?= number_format((float)$item['harga'],0,',','.') ?><small> / bulan</small></p><div class="card-meta"><span><?= (int)$item['jumlah_kamar_tersedia'] ?> kamar tersedia</span></div></div></article><?php endforeach; ?></div>
  <?php else: ?><div class="empty-state"><div class="empty-icon">⌕</div><h3>Kos tidak ditemukan</h3><p>Coba ubah kata kunci atau hapus filter untuk melihat semua pilihan kos.</p><a class="btn btn-primary" href="kos.php">Hapus Filter</a></div><?php endif; ?>
</div></main>
<?php require __DIR__ . '/includes/footer.php'; ?>