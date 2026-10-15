<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth.php';
require_admin();

$page = $_GET['page'] ?? 'dashboard';$allowed = ['dashboard', 'kos', 'lokasi', 'fasilitas', 'pengguna'];

if (!in_array($page, $allowed, true)) {$page = 'dashboard';
}

$error = '';$success = '';

function admin_redirect($page = 'dashboard')
{
    redirect('admin.php?page=' . urlencode($page));
}

function clean_upload_name($name)
{
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action =$_POST['action'] ?? '';

    try {
        if ($action === 'kos_save') {
            $id = (int)($_POST['id'] ?? 0);
            $nama = trim($_POST['nama_kos'] ?? '');
            $lokasi = (int)($_POST['lokasi_id'] ?? 0);
            $harga = filter_var($_POST['harga'] ?? null, FILTER_VALIDATE_INT);
            $desc = trim($_POST['deskripsi'] ?? '');
            $kontak = preg_replace('/\D/', '',$_POST['kontak'] ?? '');
            $total = filter_var($_POST['jumlah_kamar_total'] ?? null, FILTER_VALIDATE_INT);
            $tersedia = filter_var($_POST['jumlah_kamar_tersedia'] ?? null, FILTER_VALIDATE_INT);
            $aktif = isset($_POST['status_aktif']) ? 1 : 0;
            $rekom = isset($_POST['is_rekomendasi']) ? 1 : 0;

            if ($nama === '' \vert{}\vert{}$lokasi < 1 || $harga === false \vert{}\vert{}$harga < 0 || $desc === '' \vert{}\vert{}$kontak === '' || $total === false \vert{}\vert{}$tersedia === false || $total < 0 \vert{}\vert{}$tersedia < 0 || $tersedia >$total) {
                throw new RuntimeException('Periksa kembali data kos. Harga dan jumlah kamar harus valid.');
            }

            $st =$pdo->prepare("SELECT id FROM lokasi WHERE id=? AND status_aktif=1");
            $st->execute([$lokasi]);
            if (!$st->fetch()) {
                throw new RuntimeException('Pilih lokasi yang aktif.');
            }

            if ($rekom) {
                $st =$pdo->prepare("SELECT COUNT(*) FROM kos WHERE is_rekomendasi=1 AND status_aktif=1 AND id<>?");
                $st->execute([$id]);
                if ((int)$st->fetchColumn() >= 4 &&$aktif) {
                    throw new RuntimeException('Maksimal empat kos aktif dapat menjadi rekomendasi.');
                }
            }

            if ($id) {
                $st =$pdo->prepare("UPDATE kos SET nama_kos=?,lokasi_id=?,harga=?,deskripsi=?,kontak=?,jumlah_kamar_total=?,jumlah_kamar_tersedia=?,status_aktif=?,is_rekomendasi=?,updated_at=NOW() WHERE id=?");
                $st->execute([$nama,$lokasi, $harga,$desc, $kontak,$total, $tersedia,$aktif, $rekom,$id]);
            } else {
                $st =$pdo->prepare("INSERT INTO kos (nama_kos,lokasi_id,harga,deskripsi,kontak,jumlah_kamar_total,jumlah_kamar_tersedia,status_aktif,is_rekomendasi) VALUES (?,?,?,?,?,?,?,?,?)");
                $st->execute([$nama, $lokasi,$harga, $desc,$kontak, $total,$tersedia, $aktif,$rekom]);
                $id = (int)$pdo->lastInsertId();
            }

            $fac = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['fasilitas'] ?? [])))));
            $pdo->prepare("DELETE FROM kos_fasilitas WHERE kos_id=?")->execute([$id]);

            foreach ($fac as $fid) {$pdo->prepare("INSERT INTO kos_fasilitas (kos_id,fasilitas_id) SELECT ?,id FROM fasilitas WHERE id=? AND status_aktif=1")->execute([$id,$fid]);
            }

            if (!empty($_FILES['foto']['name'][0])) {
                $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];$uploadDir = __DIR__ . '/uploads/kos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $count = count($_FILES['foto']['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['foto']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if ($_FILES['foto']['error'][$i] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Ada foto yang gagal diunggah.');
                    }
                    if ($_FILES['foto']['size'][$i] > 5 * 1024 * 1024) {
                        throw new RuntimeException('Ukuran setiap foto maksimal 5 MB.');
                    }

                    $tmp = $_FILES['foto']['tmp_name'][$i];
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);

                    if (!isset($allowedMime[$mime]) \vert{}\vert{} !getimagesize($tmp)) {
                        throw new RuntimeException('Foto harus berformat JPG, PNG, atau WEBP.');
                    }

                    $filename = bin2hex(random_bytes(12)) . '.' . $allowedMime[$mime];
                    if (!move_uploaded_file($tmp, $uploadDir .$filename)) {
                        throw new RuntimeException('Foto gagal disimpan.');
                    }

                    $path = 'uploads/kos/' .$filename;
                    $pdo->prepare("INSERT INTO foto_kos (kos_id,path_file,is_utama) VALUES (?,?,?)")->execute([$id,$path, 0]);
                }
            }

            $st =$pdo->prepare("SELECT COUNT(*) FROM foto_kos WHERE kos_id=?");
            $st->execute([$id]);
            if ((int)$st->fetchColumn() < 1) {
                throw new RuntimeException('Setiap kos wajib memiliki minimal satu foto. Tambahkan foto sebelum menyimpan kos.');
            }

            $pdo->prepare("UPDATE foto_kos SET is_utama=0 WHERE kos_id=?")->execute([$id]);
            $st =$pdo->prepare("UPDATE foto_kos SET is_utama=1 WHERE kos_id=? ORDER BY id ASC LIMIT 1");
            $st->execute([$id]);

            admin_redirect('kos');
        } elseif ($action === 'kos_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $st =$pdo->prepare("SELECT path_file FROM foto_kos WHERE kos_id=?");
            $st->execute([$id]);
            $photos =$st->fetchAll();

            $pdo->prepare("DELETE FROM kos WHERE id=?")->execute([$id]);

            foreach ($photos as$photo) {
                $path = __DIR__ . '/' .$photo['path_file'];
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            admin_redirect('kos');
        } elseif ($action === 'kos_status') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE kos SET status_aktif=1-status_aktif,updated_at=NOW() WHERE id=?")->execute([$id]);
            admin_redirect('kos');
        } elseif ($action === 'kos_foto_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $st =$pdo->prepare("SELECT kos_id,path_file,is_utama FROM foto_kos WHERE id=?");
            $st->execute([$id]);
            $ph =$st->fetch();

            if ($ph) {
                $st =$pdo->prepare("SELECT COUNT(*) FROM foto_kos WHERE kos_id=
