<?php
require_once __DIR__ . '/koneksi.php';

const TAB_DASHBOARD = ['statistik', 'konten', 'tamu', 'ucapan', 'akun'];

function atur_notif(string $pesan, string $jenis = 'sukses'): void
{
    $_SESSION['notif'] = ['pesan' => $pesan, 'jenis' => $jenis];
}

function alihkan(string $tab): void
{
    $tab = in_array($tab, TAB_DASHBOARD, true) ? $tab : 'statistik';
    header('Location: dashboard.php#tab-' . $tab);
    exit;
}

function kirim_json(array $data, int $kode = 200): void
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csv_aman(string $teks): string
{
    return preg_match('/^[=+\-@\t\r]/', $teks) ? "'" . $teks : $teks;
}

function bersihkan_nama_tamu($nilai): string
{
    $nilai = is_string($nilai) ? $nilai : '';
    $nilai = strip_tags($nilai);
    $nilai = preg_replace('/[\p{C}\s]+/u', ' ', $nilai) ?? '';
    return mb_substr(trim($nilai), 0, 100);
}

function field_teks(string $label, string $nama, string $nilai, array $o = []): void
{
    $id    = 'f_' . preg_replace('/[^a-z0-9]+/i', '_', $nama);
    $tipe  = $o['tipe'] ?? 'text';
    $maks  = (int) ($o['maks'] ?? 120);
    $baris = (int) ($o['baris'] ?? 0);
    $ket   = $o['ket'] ?? '';
    echo '<div class="input-group"><label for="' . h($id) . '">' . h($label) . '</label>';
    if ($baris > 0) {
        echo '<textarea id="' . h($id) . '" name="' . h($nama) . '" class="custom-input area" rows="' . $baris . '" maxlength="' . $maks . '">' . h($nilai) . '</textarea>';
    } else {
        echo '<input id="' . h($id) . '" type="' . h($tipe) . '" name="' . h($nama) . '" class="custom-input" maxlength="' . $maks . '" value="' . h($nilai) . '"'
           . (isset($o['placeholder']) ? ' placeholder="' . h($o['placeholder']) . '"' : '') . '>';
    }
    if ($ket !== '') {
        echo '<small class="ket-field">' . h($ket) . '</small>';
    }
    echo '</div>';
}

function field_foto(string $label, string $nama, string $nilai, string $default = ''): void
{
    ?>
    <div class="foto-field" data-default="<?= h($default) ?>">
        <div class="foto-thumb"><img src="<?= h($nilai) ?>" alt=""></div>
        <div class="foto-info">
            <span class="foto-label"><?= h($label) ?></span>
            <input type="hidden" name="<?= h($nama) ?>" value="<?= h($nilai) ?>" data-foto-nilai>
            <input type="file" accept="image/jpeg,image/png,image/webp" hidden data-foto-berkas>
            <div class="baris-tombol-kecil">
                <button type="button" class="tombol-kecil" data-aksi="ganti"><i class="fa-solid fa-image"></i> Ganti Foto</button>
                <?php if ($default !== ''): ?>
                    <button type="button" class="tombol-kecil" data-aksi="default"><i class="fa-solid fa-rotate-left"></i> Foto Awal</button>
                <?php endif; ?>
            </div>
            <small class="foto-status" aria-live="polite"></small>
        </div>
    </div>
    <?php
}

function baris_galeri(string $path, int $i): void
{
    ?>
    <div class="baris-ulang" data-baris>
        <div class="foto-thumb"><img src="<?= h($path) ?>" alt=""></div>
        <input type="hidden" name="galeri[<?= $i ?>]" value="<?= h($path) ?>" data-f="_" data-foto-nilai>
        <input type="file" accept="image/jpeg,image/png,image/webp" hidden data-foto-berkas>
        <div class="baris-tombol-kecil">
            <button type="button" class="tombol-kecil" data-aksi="ganti" title="Ganti foto"><i class="fa-solid fa-image"></i> Ganti</button>
            <button type="button" class="tombol-kecil" data-aksi="naik" title="Pindah ke atas"><i class="fa-solid fa-arrow-up"></i></button>
            <button type="button" class="tombol-kecil" data-aksi="turun" title="Pindah ke bawah"><i class="fa-solid fa-arrow-down"></i></button>
            <button type="button" class="tombol-kecil bahaya" data-aksi="hapus" title="Hapus foto"><i class="fa-solid fa-trash"></i></button>
        </div>
        <small class="foto-status" aria-live="polite"></small>
    </div>
    <?php
}

function baris_perjalanan(array $p, int $i): void
{
    ?>
    <div class="baris-ulang kartu-ulang" data-baris>
        <div class="grid-2">
            <div class="input-group">
                <label>Tahun</label>
                <input type="text" class="custom-input" maxlength="10" name="perjalanan[<?= $i ?>][tahun]" data-f="tahun" value="<?= h($p['tahun']) ?>">
            </div>
            <div class="input-group">
                <label>Ikon</label>
                <select class="custom-input" name="perjalanan[<?= $i ?>][ikon]" data-f="ikon">
                    <?php foreach (daftar_ikon() as $kelas => $namaIkon): ?>
                        <option value="<?= h($kelas) ?>" <?= $p['ikon'] === $kelas ? 'selected' : '' ?>><?= h($namaIkon) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="input-group">
            <label>Judul</label>
            <input type="text" class="custom-input" maxlength="80" name="perjalanan[<?= $i ?>][judul]" data-f="judul" value="<?= h($p['judul']) ?>">
        </div>
        <div class="input-group">
            <label>Cerita</label>
            <textarea class="custom-input area" rows="3" maxlength="500" name="perjalanan[<?= $i ?>][teks]" data-f="teks"><?= h($p['teks']) ?></textarea>
        </div>
        <div class="baris-tombol-kecil">
            <button type="button" class="tombol-kecil" data-aksi="naik"><i class="fa-solid fa-arrow-up"></i> Naik</button>
            <button type="button" class="tombol-kecil" data-aksi="turun"><i class="fa-solid fa-arrow-down"></i> Turun</button>
            <button type="button" class="tombol-kecil bahaya" data-aksi="hapus"><i class="fa-solid fa-trash"></i> Hapus</button>
        </div>
    </div>
    <?php
}

function baris_rekening(array $r, int $i): void
{
    ?>
    <div class="baris-ulang kartu-ulang" data-baris>
        <div class="grid-2">
            <div class="input-group">
                <label>Nama Bank / E-Wallet</label>
                <input type="text" class="custom-input" maxlength="40" name="hadiah[rekening][<?= $i ?>][bank]" data-f="bank" value="<?= h($r['bank']) ?>" placeholder="Bank BCA">
            </div>
            <div class="input-group">
                <label>Nomor Rekening</label>
                <input type="text" class="custom-input" maxlength="40" name="hadiah[rekening][<?= $i ?>][nomor]" data-f="nomor" value="<?= h($r['nomor']) ?>" placeholder="1234 5678 90">
            </div>
        </div>
        <div class="input-group">
            <label>Atas Nama</label>
            <input type="text" class="custom-input" maxlength="80" name="hadiah[rekening][<?= $i ?>][atas_nama]" data-f="atas_nama" value="<?= h($r['atas_nama']) ?>">
        </div>
        <div class="baris-tombol-kecil">
            <button type="button" class="tombol-kecil" data-aksi="naik"><i class="fa-solid fa-arrow-up"></i> Naik</button>
            <button type="button" class="tombol-kecil" data-aksi="turun"><i class="fa-solid fa-arrow-down"></i> Turun</button>
            <button type="button" class="tombol-kecil bahaya" data-aksi="hapus"><i class="fa-solid fa-trash"></i> Hapus</button>
        </div>
    </div>
    <?php
}

$pesanNotif = '';
$jenisNotif = '';
$defaultKonten = konten_default();

if ($koneksi && sudah_login() && ($_GET['ekspor'] ?? '') === 'tamu') {
    $skemaE = $https ? 'https' : 'http';
    $baseE = $skemaE . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
           . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/index.php';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="daftar-tamu-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); 
    fputcsv($out, ['Nomor', 'Nama', 'Link Undangan', 'Status Kehadiran', 'Jumlah Tamu', 'Pesan & Doa'], ';', '"', '\\');
    $q = $koneksi->query('SELECT t.id, t.nama, u.rsvp, u.jumlah_tamu, u.pesan
                          FROM tb_tamu t LEFT JOIN tb_ucapan u ON u.tamu_id = t.id ORDER BY t.id');
    while ($b = $q->fetch_assoc()) {
        fputcsv($out, [
            $b['id'],
            csv_aman($b['nama']),
            $baseE . '?to=' . $b['id'],
            $b['rsvp'] ?? 'Belum merespons',
            $b['rsvp'] ? label_jumlah($b['jumlah_tamu']) : '',
            csv_aman((string) ($b['pesan'] ?? '')),
        ], ';', '"', '\\');
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = ambil_post_string('aksi');
    $minta_json = $aksi === 'unggah_foto';

    if (!cek_csrf(ambil_post_string('csrf'))) {
        if ($minta_json) kirim_json(['ok' => false, 'pesan' => 'Sesi tidak valid. Muat ulang halaman.'], 403);
        $pesanNotif = 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.';
        $jenisNotif = 'galat';
    } elseif (!$koneksi && $aksi !== 'keluar') {
        if ($minta_json) kirim_json(['ok' => false, 'pesan' => 'Koneksi database bermasalah.'], 500);
        $pesanNotif = 'Koneksi database bermasalah. Silakan coba lagi nanti.';
        $jenisNotif = 'galat';
    } elseif ($aksi === 'masuk') {
        $sisaKunci = ($_SESSION['kunci_sampai'] ?? 0) - time();
        if ($sisaKunci > 0) {
            $pesanNotif = "Terlalu banyak percobaan. Coba lagi dalam {$sisaKunci} detik.";
            $jenisNotif = 'galat';
        } else {
            $username = trim(ambil_post_string('username'));
            $password = ambil_post_string('password');

            $stmt = $koneksi->prepare('SELECT id, username, password FROM tb_admin WHERE username = ?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                unset($_SESSION['gagal_login'], $_SESSION['kunci_sampai']);
                header('Location: dashboard.php');
                exit;
            }

            sleep(1);
            $_SESSION['gagal_login'] = ($_SESSION['gagal_login'] ?? 0) + 1;
            if ($_SESSION['gagal_login'] >= 5) {
                $_SESSION['kunci_sampai'] = time() + 60;
                $_SESSION['gagal_login'] = 0;
            }
            $pesanNotif = 'Username atau password salah.';
            $jenisNotif = 'galat';
        }
    } elseif (!sudah_login()) {
        if ($minta_json) kirim_json(['ok' => false, 'pesan' => 'Silakan masuk kembali.'], 401);
    } else {
        if ($aksi === 'keluar') {
            $_SESSION = [];
            session_destroy();
            header('Location: dashboard.php');
            exit;
        }

        if ($aksi === 'unggah_foto') {
            $hasil = proses_unggah_foto($_FILES['foto'] ?? null);
            kirim_json($hasil, $hasil['ok'] ? 200 : 422);
        }

        if ($aksi === 'simpan_konten') {
            $lama = muat_konten($koneksi);
            $baru = bersihkan_konten($_POST, $lama, $peringatan);
            simpan_konten($koneksi, $baru);
            bersihkan_foto_tak_terpakai($baru);
            $pesan = 'Konten undangan berhasil disimpan.';
            if ($peringatan) {
                $pesan .= ' Catatan: ' . implode(' ', array_slice(array_unique($peringatan), 0, 4));
            }
            atur_notif($pesan, $peringatan ? 'galat' : 'sukses');
            alihkan('konten');
        }

        if ($aksi === 'tamu_tambah') {
            $nama = bersihkan_nama_tamu(ambil_post_string('nama'));
            if ($nama === '') {
                atur_notif('Nama tamu tidak boleh kosong.', 'galat');
            } else {
                $stmt = $koneksi->prepare('INSERT INTO tb_tamu (nama) VALUES (?)');
                $stmt->bind_param('s', $nama);
                $stmt->execute();
                atur_notif('Tamu "' . $nama . '" ditambahkan dengan nomor ' . $koneksi->insert_id . '.');
            }
            alihkan('tamu');
        }

        if ($aksi === 'tamu_ubah') {
            $id = filter_var(ambil_post_string('id'), FILTER_VALIDATE_INT);
            $nama = bersihkan_nama_tamu(ambil_post_string('nama'));
            if ($id === false || $id < 1 || $nama === '') {
                atur_notif('Data tamu tidak valid.', 'galat');
            } else {
                $stmt = $koneksi->prepare('UPDATE tb_tamu SET nama = ? WHERE id = ?');
                $stmt->bind_param('si', $nama, $id);
                $stmt->execute();
                atur_notif('Nama tamu nomor ' . $id . ' diperbarui.');
            }
            alihkan('tamu');
        }

        if ($aksi === 'tamu_hapus') {
            $id = filter_var(ambil_post_string('id'), FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                atur_notif('Nomor tamu tidak valid.', 'galat');
            } else {
                $stmt = $koneksi->prepare('UPDATE tb_ucapan SET tamu_id = NULL WHERE tamu_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt = $koneksi->prepare('DELETE FROM tb_tamu WHERE id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                atur_notif('Tamu nomor ' . $id . ' dihapus.');
            }
            alihkan('tamu');
        }

        if ($aksi === 'tamu_impor') {
            $berkas = $_FILES['csv'] ?? null;
            if (!$berkas || ($berkas['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($berkas['tmp_name'])) {
                atur_notif('Pilih berkas CSV terlebih dahulu.', 'galat');
                alihkan('tamu');
            }
            if ($berkas['size'] > 1024 * 1024) {
                atur_notif('Ukuran berkas CSV maksimal 1 MB.', 'galat');
                alihkan('tamu');
            }

            $isi = (string) file_get_contents($berkas['tmp_name']);
            if (substr($isi, 0, 3) === "\xEF\xBB\xBF") {
                $isi = substr($isi, 3);
            }
            if (!mb_check_encoding($isi, 'UTF-8')) {
                $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
            }
            $baris = preg_split('/\r\n|\n|\r/', $isi) ?: [];
            $baris = array_values(array_filter($baris, fn($b) => trim($b) !== ''));

            if (!$baris) {
                atur_notif('Berkas CSV kosong.', 'galat');
                alihkan('tamu');
            }

            $kandidat = [';' => substr_count($baris[0], ';'), ',' => substr_count($baris[0], ','), "\t" => substr_count($baris[0], "\t")];
            arsort($kandidat);
            $pemisah = key($kandidat);

            $rows = array_map(fn($b) => str_getcsv($b, $pemisah, '"', '\\'), array_slice($baris, 0, 2001));

            $kolomNama = null;
            foreach ($rows[0] as $idx => $judulKolom) {
                if (preg_match('/^\s*nama(\s+tamu)?\s*$/iu', (string) $judulKolom)) {
                    $kolomNama = $idx;
                    break;
                }
            }
            if ($kolomNama !== null) {
                array_shift($rows);
            } else {
                $kolomNama = (count($rows[0]) > 1 && ctype_digit(trim((string) $rows[0][0]))) ? 1 : 0;
            }

            $ada = [];
            $q = $koneksi->query('SELECT nama FROM tb_tamu');
            while ($b = $q->fetch_assoc()) {
                $ada[mb_strtolower($b['nama'])] = true;
            }

            $tambah = 0;
            $lewat = 0;
            $stmt = $koneksi->prepare('INSERT INTO tb_tamu (nama) VALUES (?)');
            foreach ($rows as $r) {
                $nama = bersihkan_nama_tamu(preg_replace("/^'(?=[=+\\-@])/", '', (string) ($r[$kolomNama] ?? '')));
                $kunci = mb_strtolower($nama);
                if ($nama === '' || isset($ada[$kunci])) {
                    $lewat++;
                    continue;
                }
                $ada[$kunci] = true;
                $stmt->bind_param('s', $nama);
                $stmt->execute();
                $tambah++;
            }

            $pesan = "Impor selesai: $tambah tamu ditambahkan";
            if ($lewat > 0) {
                $pesan .= ", $lewat dilewati (nama kosong atau sudah ada)";
            }
            atur_notif($pesan . '.', $tambah > 0 ? 'sukses' : 'galat');
            alihkan('tamu');
        }

        if ($aksi === 'toggle_tampil') {
            $id = filter_var(ambil_post_string('id'), FILTER_VALIDATE_INT);
            $tampilBaru = ambil_post_string('tampil_baru') === '1' ? 1 : 0;

            if ($id === false || $id < 1) {
                atur_notif('ID ucapan tidak valid.', 'galat');
            } else {
                $stmt = $koneksi->prepare('UPDATE tb_ucapan SET tampil = ? WHERE id = ?');
                $stmt->bind_param('ii', $tampilBaru, $id);
                $stmt->execute();
                atur_notif($tampilBaru ? 'Ucapan berhasil ditampilkan kembali.' : 'Ucapan berhasil disembunyikan.');
            }
            alihkan('ucapan');
        }

        if ($aksi === 'hapus') {
            $id = filter_var(ambil_post_string('id'), FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                atur_notif('ID ucapan tidak valid.', 'galat');
            } else {
                $stmt = $koneksi->prepare('DELETE FROM tb_ucapan WHERE id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                atur_notif('Ucapan & RSVP berhasil dihapus.');
            }
            alihkan('ucapan');
        }

        if ($aksi === 'ubah_akun') {
            $adminId      = (int) $_SESSION['admin_id'];
            $usernameBaru = trim(ambil_post_string('username'));
            $passwordLama = ambil_post_string('password_lama');
            $passwordBaru = ambil_post_string('password_baru');
            $konfirmasi   = ambil_post_string('konfirmasi');

            if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $usernameBaru)) {
                atur_notif('Username 3-50 karakter: huruf, angka, titik, strip, atau garis bawah.', 'galat');
            } elseif ($passwordBaru !== '' && strlen($passwordBaru) < 8) {
                atur_notif('Password baru minimal 8 karakter.', 'galat');
            } elseif ($passwordBaru !== '' && $passwordBaru !== $konfirmasi) {
                atur_notif('Konfirmasi password baru tidak cocok.', 'galat');
            } else {
                $stmt = $koneksi->prepare('SELECT password FROM tb_admin WHERE id = ?');
                $stmt->bind_param('i', $adminId);
                $stmt->execute();
                $admin = $stmt->get_result()->fetch_assoc();

                if (!$admin || !password_verify($passwordLama, $admin['password'])) {
                    atur_notif('Password saat ini salah.', 'galat');
                } else {
                    $hashBaru = $passwordBaru !== '' ? password_hash($passwordBaru, PASSWORD_DEFAULT) : $admin['password'];
                    try {
                        $stmt = $koneksi->prepare('UPDATE tb_admin SET username = ?, password = ? WHERE id = ?');
                        $stmt->bind_param('ssi', $usernameBaru, $hashBaru, $adminId);
                        $stmt->execute();
                        $_SESSION['admin_username'] = $usernameBaru;
                        atur_notif('Pengaturan akun berhasil diperbarui!');
                    } catch (mysqli_sql_exception $e) {
                        atur_notif($e->getCode() === 1062 ? 'Username sudah digunakan.' : 'Gagal memperbarui akun.', 'galat');
                    }
                }
            }
            alihkan('akun');
        }
    }
}

$login = sudah_login();

if ($login && isset($_SESSION['notif'])) {
    $pesanNotif = $_SESSION['notif']['pesan'];
    $jenisNotif = $_SESSION['notif']['jenis'];
    unset($_SESSION['notif']);
}
if ($login && !$koneksi && $pesanNotif === '') {
    $pesanNotif = 'Koneksi database bermasalah. Data dashboard belum dapat dimuat.';
    $jenisNotif = 'galat';
}
$csrf = token_csrf();

$konten = muat_konten($koneksi);
$namaPasangan = nama_pasangan($konten);

$daftarUcapan = [];
$daftarTamu = [];
$rekap = ['Hadir' => 0, 'Tidak Hadir' => 0, 'Masih Ragu' => 0];
$orangHadir = [1 => 0, 2 => 0, 3 => 0];
$estimasiTamu = 0;
$perHari = [];
for ($i = 13; $i >= 0; $i--) {
    $perHari[date('Y-m-d', strtotime("-$i day"))] = 0;
}

if ($login && $koneksi) {
    $hasil = $koneksi->query('SELECT id, tamu_id, nama, rsvp, jumlah_tamu, pesan, tampil, created_at, diubah_at FROM tb_ucapan ORDER BY created_at DESC, id DESC');
    while ($baris = $hasil->fetch_assoc()) {
        $daftarUcapan[] = $baris;
        $rekap[$baris['rsvp']]++;
        if ($baris['rsvp'] === 'Hadir') {
            $estimasiTamu += (int) $baris['jumlah_tamu'];
            $orangHadir[min(3, max(1, (int) $baris['jumlah_tamu']))]++;
        }
        $hariUcapan = date('Y-m-d', strtotime($baris['created_at']));
        if (isset($perHari[$hariUcapan])) {
            $perHari[$hariUcapan]++;
        }
    }

    $hasil = $koneksi->query('SELECT t.id, t.nama, u.id AS ucapan_id, u.rsvp, u.jumlah_tamu
                              FROM tb_tamu t LEFT JOIN tb_ucapan u ON u.tamu_id = t.id ORDER BY t.id');
    while ($baris = $hasil->fetch_assoc()) {
        $daftarTamu[] = $baris;
    }
}
$totalUcapan = count($daftarUcapan);
$totalTamu = count($daftarTamu);
$tamuMerespons = count(array_filter($daftarTamu, fn($t) => $t['ucapan_id'] !== null));
$tamuBelum = $totalTamu - $tamuMerespons;
$persenRespons = $totalTamu > 0 ? round($tamuMerespons / $totalTamu * 100) : 0;
$maksHari = max(1, max($perHari ?: [1]));
$maksOrang = max(1, max($orangHadir));

$skema = $https ? 'https' : 'http';
$urlUndangan = $skema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
             . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/index.php';

function pesan_wa(string $nama, string $link, array $konten): string
{
    return "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n"
         . "Kepada Yth. {$nama},\n"
         . 'Dengan penuh rasa syukur, kami mengundang Bapak/Ibu/Saudara/i untuk hadir di pernikahan '
         . nama_pasangan($konten) . ' pada ' . tanggal_panjang($konten['acara']['tanggal']) . ".\n\n"
         . "Detail undangan dapat dilihat pada tautan berikut:\n{$link}\n\n"
         . 'Merupakan kehormatan bagi kami apabila Anda berkenan hadir. Terima kasih.';
}

$warnaRsvp = ['Hadir' => '#4E6151', 'Tidak Hadir' => '#a33a3a', 'Masih Ragu' => '#d4a373'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4E6151">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin - <?= h($namaPasangan) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body data-url-undangan="<?= h($urlUndangan) ?>" data-csrf="<?= h($csrf) ?>">

    <div class="latar-wallpaper" aria-hidden="true"></div>

    <div class="dashboard-wrapper">
        <div class="dashboard-container">

<?php if (!$login): ?>
            <div class="login-card kaca">
                <span class="badge-wedding">Dashboard Admin</span>
                <p class="nama-script"><?= h($konten['mempelai']['pria']['panggilan']) ?> <span class="ampersand">&amp;</span> <?= h($konten['mempelai']['wanita']['panggilan']) ?></p>
                <h2>UNDANGAN PERNIKAHAN</h2>

                <?php if ($pesanNotif !== ''): ?>
                    <p class="pesan-form <?= h($jenisNotif) ?>"><?= h($pesanNotif) ?></p>
                <?php endif; ?>

                <form method="post" action="dashboard.php" autocomplete="on">
                    <input type="hidden" name="aksi" value="masuk">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

                    <div class="input-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" class="custom-input" autocomplete="username" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" class="custom-input" autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="tombol-aksi btn-login">MASUK</button>
                </form>
            </div>

<?php else: ?>
            <div class="dashboard-header">
                <span class="badge-wedding">Panel Pengelola</span>
                <p class="nama-script"><?= h($konten['mempelai']['pria']['panggilan']) ?> <span class="ampersand">&amp;</span> <?= h($konten['mempelai']['wanita']['panggilan']) ?></p>
                <div class="welcome-text">Selamat datang, Admin <strong><?= h($_SESSION['admin_username']) ?></strong></div>
            </div>

            <?php if ($pesanNotif !== ''): ?>
                <p class="pesan-form <?= h($jenisNotif) ?>" role="status"><?= h($pesanNotif) ?></p>
            <?php endif; ?>

            <nav class="tab-nav kaca" role="tablist" aria-label="Menu dashboard">
                <button type="button" class="tab-tombol" role="tab" data-tab="statistik"><i class="fa-solid fa-chart-pie"></i> <span>Statistik</span></button>
                <button type="button" class="tab-tombol" role="tab" data-tab="konten"><i class="fa-solid fa-pen-ruler"></i> <span>Edit Konten</span></button>
                <button type="button" class="tab-tombol" role="tab" data-tab="tamu"><i class="fa-solid fa-user-group"></i> <span>Tamu</span></button>
                <button type="button" class="tab-tombol" role="tab" data-tab="ucapan"><i class="fa-solid fa-comments"></i> <span>Ucapan</span></button>
                <button type="button" class="tab-tombol" role="tab" data-tab="akun"><i class="fa-solid fa-user-gear"></i> <span>Akun</span></button>
            </nav>

            <section class="tab-panel" id="tab-statistik" role="tabpanel" hidden>
                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-chart-pie"></i> Ringkasan Statistik</h2>
                    <div class="statistik-grid">
                        <div class="statistik-kartu">
                            <span class="statistik-angka"><?= $totalTamu ?></span>
                            <span class="statistik-label">Tamu Diundang</span>
                        </div>
                        <div class="statistik-kartu">
                            <span class="statistik-angka"><?= $tamuMerespons ?></span>
                            <span class="statistik-label">Sudah Merespons</span>
                        </div>
                        <div class="statistik-kartu">
                            <span class="statistik-angka"><?= $totalUcapan ?></span>
                            <span class="statistik-label">Total Ucapan</span>
                        </div>
                        <div class="statistik-kartu sorot">
                            <span class="statistik-angka"><?= $estimasiTamu ?></span>
                            <span class="statistik-label">Estimasi Tamu Hadir</span>
                        </div>
                        <div class="statistik-kartu">
                            <span class="statistik-angka"><?= $rekap['Hadir'] ?></span>
                            <span class="statistik-label">Hadir</span>
                        </div>
                        <div class="statistik-kartu">
                            <span class="statistik-angka"><?= $rekap['Tidak Hadir'] ?></span>
                            <span class="statistik-label">Tidak Hadir</span>
                        </div>
                        <div class="statistik-kartu">
                            <span class="statistik-angka"><?= $rekap['Masih Ragu'] ?></span>
                            <span class="statistik-label">Masih Ragu</span>
                        </div>
                        <div class="statistik-kartu">
                            <span class="statistik-angka"><?= $tamuBelum ?></span>
                            <span class="statistik-label">Belum Merespons</span>
                        </div>
                    </div>
                </div>

                <div class="grid-grafik">
                    <div class="dashboard-block kaca">
                        <h2 class="block-title"><i class="fa-solid fa-circle-half-stroke"></i> Komposisi Konfirmasi</h2>
                        <?php if ($totalUcapan === 0): ?>
                            <p class="teks-kosong">Belum ada konfirmasi yang masuk.</p>
                        <?php else: ?>
                            <div class="donut-wrap">
                                <svg class="donut" viewBox="0 0 42 42" role="img" aria-label="Diagram komposisi konfirmasi kehadiran">
                                    <circle class="donut-dasar" cx="21" cy="21" r="15.9155" fill="none" stroke-width="6"></circle>
                                    <?php $geser = 25; foreach ($rekap as $label => $jml):
                                        if ($jml === 0) continue;
                                        $persen = $jml / $totalUcapan * 100; ?>
                                        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="<?= $warnaRsvp[$label] ?>" stroke-width="6"
                                                stroke-dasharray="<?= round($persen, 3) ?> <?= round(100 - $persen, 3) ?>" stroke-dashoffset="<?= round($geser, 3) ?>"></circle>
                                    <?php $geser -= $persen; endforeach; ?>
                                    <text x="21" y="21.5" class="donut-angka" text-anchor="middle"><?= $totalUcapan ?></text>
                                    <text x="21" y="26" class="donut-label" text-anchor="middle">ucapan</text>
                                </svg>
                                <ul class="legenda">
                                    <?php foreach ($rekap as $label => $jml): ?>
                                        <li><span class="titik" style="background: <?= $warnaRsvp[$label] ?>"></span>
                                            <?= h($label) ?> <strong><?= $jml ?></strong>
                                            <small>(<?= round($jml / $totalUcapan * 100) ?>%)</small></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-block kaca">
                        <h2 class="block-title"><i class="fa-solid fa-envelope-circle-check"></i> Respons dari Daftar Tamu</h2>
                        <?php if ($totalTamu === 0): ?>
                            <p class="teks-kosong">Daftar tamu masih kosong.</p>
                        <?php else: ?>
                            <div class="progres" role="progressbar" aria-valuenow="<?= $persenRespons ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progres-isi" style="width: <?= $persenRespons ?>%"></div>
                            </div>
                            <p class="progres-teks"><strong><?= $tamuMerespons ?></strong> dari <strong><?= $totalTamu ?></strong> tamu sudah merespons (<?= $persenRespons ?>%)</p>
                        <?php endif; ?>

                        <h3 class="sub-judul">Jumlah orang per konfirmasi hadir</h3>
                        <?php foreach ([1 => '1 Orang', 2 => '2 Orang', 3 => '3+ Orang'] as $n => $labelOrang): ?>
                            <div class="bar-baris">
                                <span class="bar-label"><?= $labelOrang ?></span>
                                <div class="bar-latar"><div class="bar-isi" style="width: <?= round($orangHadir[$n] / $maksOrang * 100) ?>%"></div></div>
                                <span class="bar-nilai"><?= $orangHadir[$n] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-chart-column"></i> Ucapan Masuk 14 Hari Terakhir</h2>
                    <div class="kolom-grafik" role="img" aria-label="Grafik jumlah ucapan per hari">
                        <?php foreach ($perHari as $tgl => $jml): $t = strtotime($tgl); ?>
                            <div class="kolom" title="<?= h(tanggal_indo($tgl)) ?>: <?= $jml ?> ucapan">
                                <span class="kolom-nilai"><?= $jml > 0 ? $jml : '' ?></span>
                                <div class="kolom-batang" style="height: <?= $jml > 0 ? max(6, round($jml / $maksHari * 100)) : 2 ?>%"></div>
                                <span class="kolom-label"><?= date('j', $t) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="ket-field rata-tengah">Angka di bawah bar adalah tanggal (hari ke-n dalam bulan).</p>
                </div>
            </section>

            <section class="tab-panel" id="tab-konten" role="tabpanel" hidden>
                <div class="edit-layout" id="editLayout">
                    <form method="post" action="dashboard.php" id="formKonten" class="form-konten" autocomplete="off"
                          data-aksi-simpan="dashboard.php" data-aksi-pratinjau="index.php?pratinjau=">
                        <input type="hidden" name="aksi" value="simpan_konten">
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

                        <div class="toolbar-edit kaca">
                            <div class="sub-tab-nav" role="tablist" aria-label="Bagian konten">
                                <button type="button" class="sub-tab-tombol" data-subtab="mempelai">Mempelai</button>
                                <button type="button" class="sub-tab-tombol" data-subtab="acara">Acara</button>
                                <button type="button" class="sub-tab-tombol" data-subtab="foto">Foto &amp; Galeri</button>
                                <button type="button" class="sub-tab-tombol" data-subtab="perjalanan">Perjalanan</button>
                                <button type="button" class="sub-tab-tombol" data-subtab="hadiah">Hadiah Digital</button>
                            </div>
                            <div class="toolbar-aksi">
                                <button type="button" class="tombol-kecil tombol-pratinjau" id="tombolPratinjau"><i class="fa-regular fa-eye"></i> Pratinjau</button>
                                <button type="submit" class="tombol-kecil utama"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                            </div>
                        </div>

                        <div class="sub-tab-panel kaca" data-subpanel="mempelai" hidden>
                            <?php foreach ([['wanita', 'Mempelai Wanita'], ['pria', 'Mempelai Pria']] as [$kunci, $judulM]): $m = $konten['mempelai'][$kunci]; ?>
                                <fieldset class="kelompok">
                                    <legend><?= h($judulM) ?></legend>
                                    <div class="grid-2">
                                        <?php field_teks('Nama Panggilan', "mempelai[$kunci][panggilan]", $m['panggilan'], ['maks' => 40, 'ket' => 'Dipakai di sampul, judul, dan footer.']); ?>
                                        <?php field_teks('Nama di Kartu Profil', "mempelai[$kunci][nama]", $m['nama'], ['maks' => 60]); ?>
                                    </div>
                                    <div class="grid-2">
                                        <?php field_teks('Keterangan Anak', "mempelai[$kunci][anak_dari]", $m['anak_dari'], ['maks' => 60, 'ket' => 'Contoh: Putri Pertama dari']); ?>
                                        <?php field_teks('Nama Orang Tua', "mempelai[$kunci][orang_tua]", $m['orang_tua'], ['maks' => 120]); ?>
                                    </div>
                                    <div class="grid-2">
                                        <?php field_teks('Instagram (tampilan)', "mempelai[$kunci][ig_nama]", $m['ig_nama'], ['maks' => 60, 'placeholder' => '@username', 'ket' => 'Kosongkan untuk menyembunyikan Instagram.']); ?>
                                        <?php field_teks('Link Instagram', "mempelai[$kunci][ig_link]", $m['ig_link'], ['maks' => 300, 'tipe' => 'url', 'placeholder' => 'https://www.instagram.com/username/']); ?>
                                    </div>
                                    <?php field_foto('Foto ' . $judulM, "mempelai[$kunci][foto]", $m['foto'], $defaultKonten['mempelai'][$kunci]['foto']); ?>
                                </fieldset>
                            <?php endforeach; ?>
                        </div>

                        <div class="sub-tab-panel kaca" data-subpanel="acara" hidden>
                            <fieldset class="kelompok">
                                <legend>Tanggal Pernikahan</legend>
                                <?php field_teks('Tanggal (akad & resepsi)', 'acara[tanggal]', $konten['acara']['tanggal'], ['tipe' => 'date', 'maks' => 10, 'ket' => 'Dipakai untuk hitung mundur dan tombol simpan ke kalender.']); ?>
                            </fieldset>
                            <?php foreach ([['akad', 'Akad Nikah'], ['resepsi', 'Resepsi']] as [$kunci, $judulA]): $a = $konten['acara'][$kunci]; ?>
                                <fieldset class="kelompok">
                                    <legend><?= h($judulA) ?></legend>
                                    <?php field_teks('Judul Acara', "acara[$kunci][judul]", $a['judul'], ['maks' => 60]); ?>
                                    <div class="grid-2">
                                        <?php field_teks('Jam Mulai (WIB)', "acara[$kunci][jam_mulai]", $a['jam_mulai'], ['tipe' => 'time', 'maks' => 5]); ?>
                                        <?php field_teks('Jam Selesai (WIB)', "acara[$kunci][jam_selesai]", $a['jam_selesai'], ['tipe' => 'time', 'maks' => 5]); ?>
                                    </div>
                                    <?php field_teks('Nama Lokasi', "acara[$kunci][tempat]", $a['tempat'], ['maks' => 120]); ?>
                                    <?php field_teks('Alamat Lokasi', "acara[$kunci][alamat]", $a['alamat'], ['maks' => 250, 'baris' => 2]); ?>
                                    <?php field_teks('Link Lokasi (Google Maps)', "acara[$kunci][link_maps]", $a['link_maps'], ['tipe' => 'url', 'maks' => 300, 'ket' => 'Kosongkan untuk menyembunyikan tombol "Petunjuk Lokasi".']); ?>
                                    <div class="grid-2">
                                        <?php field_teks('Judul di Kalender', "acara[$kunci][judul_kalender]", $a['judul_kalender'], ['maks' => 80, 'ket' => 'Nama mempelai ditambahkan otomatis.']); ?>
                                        <?php field_teks('Lokasi di Kalender', "acara[$kunci][lokasi_kalender]", $a['lokasi_kalender'], ['maks' => 150]); ?>
                                    </div>
                                </fieldset>
                            <?php endforeach; ?>
                        </div>

                        <div class="sub-tab-panel kaca" data-subpanel="foto" hidden>
                            <fieldset class="kelompok">
                                <legend>Foto Utama</legend>
                                <?php field_foto('Foto Latar (wallpaper)', 'foto[latar]', $konten['foto']['latar'], $defaultKonten['foto']['latar']); ?>
                                <?php field_foto('Foto Pasangan (halaman pembuka)', 'foto[pasangan]', $konten['foto']['pasangan'], $defaultKonten['foto']['pasangan']); ?>
                                <?php field_foto('Foto Banner Acara', 'foto[acara]', $konten['foto']['acara'], $defaultKonten['foto']['acara']); ?>
                                <p class="ket-field">Foto kedua mempelai diubah di sub-tab Mempelai.</p>
                            </fieldset>

                            <fieldset class="kelompok">
                                <legend>Galeri Foto</legend>
                                <p class="ket-field">Foto pertama tampil besar. Maksimal <?= MAKS_GALERI ?> foto. Foto dikompres otomatis sebelum diunggah.</p>
                                <div class="daftar-ulang" id="listGaleri" data-list="galeri">
                                    <?php foreach ($konten['galeri'] as $i => $fotoG) baris_galeri($fotoG, $i); ?>
                                </div>
                                <input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden id="berkasTambahGaleri">
                                <button type="button" class="tombol-kecil utama" id="tombolTambahGaleri"><i class="fa-solid fa-plus"></i> Tambah Foto</button>
                                <small class="foto-status" id="statusTambahGaleri" aria-live="polite"></small>
                            </fieldset>
                        </div>

                        <div class="sub-tab-panel kaca" data-subpanel="perjalanan" hidden>
                            <fieldset class="kelompok">
                                <legend>Perjalanan Kami</legend>
                                <p class="ket-field">Maksimal <?= MAKS_PERJALANAN ?> tahap. Item ganjil tampil di kiri, genap di kanan (PC). Kosongkan semua untuk menyembunyikan bagian ini.</p>
                                <div class="daftar-ulang" id="listPerjalanan" data-list="perjalanan">
                                    <?php foreach ($konten['perjalanan'] as $i => $p) baris_perjalanan($p, $i); ?>
                                </div>
                                <button type="button" class="tombol-kecil utama" id="tombolTambahPerjalanan"><i class="fa-solid fa-plus"></i> Tambah Tahap</button>
                            </fieldset>
                        </div>

                        <div class="sub-tab-panel kaca" data-subpanel="hadiah" hidden>
                            <fieldset class="kelompok">
                                <legend>Hadiah Digital</legend>
                                <label class="saklar">
                                    <input type="hidden" name="hadiah[tampil]" value="0">
                                    <input type="checkbox" name="hadiah[tampil]" value="1" <?= $konten['hadiah']['tampil'] ? 'checked' : '' ?>>
                                    <span class="saklar-geser"></span>
                                    <span class="saklar-teks">Tampilkan bagian Hadiah Digital di undangan</span>
                                </label>
                                <?php field_teks('Kalimat Pengantar', 'hadiah[pengantar]', $konten['hadiah']['pengantar'], ['maks' => 400, 'baris' => 3]); ?>
                                <h3 class="sub-judul">Rekening / E-Wallet</h3>
                                <div class="daftar-ulang" id="listRekening" data-list="hadiah[rekening]">
                                    <?php foreach ($konten['hadiah']['rekening'] as $i => $r) baris_rekening($r, $i); ?>
                                </div>
                                <button type="button" class="tombol-kecil utama" id="tombolTambahRekening"><i class="fa-solid fa-plus"></i> Tambah Rekening</button>
                            </fieldset>
                        </div>

                        <div class="bar-simpan kaca">
                            <button type="submit" class="tombol-aksi"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Konten</button>
                        </div>
                    </form>

                    <aside class="panel-pratinjau" id="panelPratinjau" aria-label="Pratinjau undangan">
                        <div class="pratinjau-bar">
                            <strong><i class="fa-solid fa-mobile-screen"></i> Pratinjau</strong>
                            <div class="pratinjau-aksi">
                                <button type="button" class="tombol-kecil aktif" data-tampilan="isi">Isi</button>
                                <button type="button" class="tombol-kecil" data-tampilan="sampul">Sampul</button>
                                <button type="button" class="tombol-kecil" id="tombolSegarkan" title="Muat ulang pratinjau"><i class="fa-solid fa-rotate"></i></button>
                                <button type="button" class="tombol-kecil bahaya" id="tombolTutupPratinjau" title="Tutup pratinjau"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>
                        <iframe name="framePratinjau" id="framePratinjau" title="Pratinjau undangan" src="about:blank"></iframe>
                        <small class="ket-field">Pratinjau belum menyimpan apa pun. Klik <strong>Simpan</strong> agar perubahan tampil di undangan.</small>
                    </aside>
                </div>

                <template id="tplGaleri"><?php baris_galeri('', 0); ?></template>
                <template id="tplPerjalanan"><?php baris_perjalanan(['tahun' => '', 'judul' => '', 'teks' => '', 'ikon' => 'fa-heart'], 0); ?></template>
                <template id="tplRekening"><?php baris_rekening(['bank' => '', 'nomor' => '', 'atas_nama' => ''], 0); ?></template>
            </section>

            <section class="tab-panel" id="tab-tamu" role="tabpanel" hidden>
                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-user-plus"></i> Kelola Daftar Tamu</h2>
                    <div class="grid-2 aksi-tamu">
                        <form method="post" action="dashboard.php" class="form-tambah-tamu">
                            <input type="hidden" name="aksi" value="tamu_tambah">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <label for="namaTamuBaru">Tambah tamu</label>
                            <div class="baris-input-tombol">
                                <input type="text" id="namaTamuBaru" name="nama" class="custom-input" maxlength="100" placeholder="Nama tamu" required>
                                <button type="submit" class="tombol-kecil utama"><i class="fa-solid fa-plus"></i> Tambah</button>
                            </div>
                        </form>

                        <form method="post" action="dashboard.php" enctype="multipart/form-data" class="form-impor">
                            <input type="hidden" name="aksi" value="tamu_impor">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <label for="berkasCsv">Impor CSV</label>
                            <div class="baris-input-tombol">
                                <input type="file" id="berkasCsv" name="csv" accept=".csv,text/csv,text/plain" class="custom-input berkas" required>
                                <button type="submit" class="tombol-kecil utama"><i class="fa-solid fa-file-import"></i> Impor</button>
                            </div>
                        </form>
                    </div>
                    <div class="baris-tombol rata-kiri">
                        <a class="tombol-kecil utama" href="dashboard.php?ekspor=tamu"><i class="fa-solid fa-file-export"></i> Ekspor CSV</a>
                    </div>
                    <p class="ket-field">Impor membaca kolom <strong>Nama</strong> (atau kolom pertama bila tanpa judul); nama yang sudah ada dilewati. Pemisah koma atau titik koma sama-sama dikenali.</p>
                </div>

                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-address-book"></i> Daftar Tamu (<?= $totalTamu ?>)</h2>

                    <div class="baris-filter">
                        <input type="search" id="cariTamu" class="custom-input" placeholder="Cari nama atau nomor..." aria-label="Cari tamu">
                        <select id="filterTamu" class="custom-input" aria-label="Filter status">
                            <option value="semua">Semua status</option>
                            <option value="belum">Belum merespons</option>
                            <option value="sudah">Sudah merespons</option>
                        </select>
                    </div>

                    <?php if (!$daftarTamu): ?>
                        <p class="teks-kosong">Belum ada tamu. Tambahkan tamu atau impor dari CSV.</p>
                    <?php else: ?>
                        <div class="tabel-bungkus">
                            <table class="tabel-rsvp tabel-tamu" id="tabelTamu">
                                <thead>
                                    <tr><th class="kol-no">No</th><th>Nama Tamu</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftarTamu as $t):
                                        $linkTamu = $urlUndangan . '?to=' . (int) $t['id'];
                                        $sudah = $t['ucapan_id'] !== null; ?>
                                        <tr data-cari="<?= h(mb_strtolower($t['id'] . ' ' . $t['nama'])) ?>" data-status="<?= $sudah ? 'sudah' : 'belum' ?>">
                                            <td class="kol-no"><?= (int) $t['id'] ?></td>
                                            <td class="sel-nama">
                                                <div class="nama-tampil">
                                                    <strong><?= h($t['nama']) ?></strong>
                                                    <div class="baris-tombol-kecil">
                                                        <button type="button" class="tombol-kecil" data-salin="<?= h($linkTamu) ?>" title="Salin link undangan"><i class="fa-regular fa-copy"></i> Salin</button>
                                                        <a class="tombol-kecil" target="_blank" rel="noopener" title="Kirim lewat WhatsApp"
                                                           href="https://wa.me/?text=<?= h(rawurlencode(pesan_wa($t['nama'], $linkTamu, $konten))) ?>"><i class="fa-brands fa-whatsapp"></i> WA</a>
                                                        <button type="button" class="tombol-kecil" data-edit-tamu title="Ubah nama"><i class="fa-solid fa-pen"></i> Edit</button>
                                                        <form method="post" action="dashboard.php" class="inline" data-konfirmasi="Hapus tamu nomor <?= (int) $t['id'] ?> (<?= h($t['nama']) ?>)? Ucapan yang sudah ia kirim tetap tersimpan.">
                                                            <input type="hidden" name="aksi" value="tamu_hapus">
                                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                                            <button type="submit" class="tombol-kecil bahaya" title="Hapus tamu"><i class="fa-solid fa-trash"></i> Hapus</button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <form method="post" action="dashboard.php" class="form-edit-nama" hidden>
                                                    <input type="hidden" name="aksi" value="tamu_ubah">
                                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                                    <input type="text" name="nama" class="custom-input" maxlength="100" value="<?= h($t['nama']) ?>" required aria-label="Nama tamu nomor <?= (int) $t['id'] ?>">
                                                    <button type="submit" class="tombol-kecil utama"><i class="fa-solid fa-check"></i> Simpan</button>
                                                    <button type="button" class="tombol-kecil" data-batal-edit>Batal</button>
                                                </form>
                                            </td>
                                            <td>
                                                <?php if ($sudah): ?>
                                                    <span class="badge-status <?= $t['rsvp'] === 'Hadir' ? 'hijau' : ($t['rsvp'] === 'Tidak Hadir' ? 'merah' : 'kuning') ?>"><?= h($t['rsvp']) ?></span>
                                                    <small><?= h(label_jumlah($t['jumlah_tamu'])) ?></small>
                                                <?php else: ?>
                                                    <span class="badge-status abu">Belum merespons</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="teks-kosong hidden" id="tamuKosong">Tidak ada tamu yang cocok.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="tab-panel" id="tab-ucapan" role="tabpanel" hidden>
                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-comments"></i> Data Ucapan &amp; Konfirmasi RSVP</h2>

                    <?php if (!$daftarUcapan): ?>
                        <p class="teks-kosong">Belum ada data ucapan yang masuk.</p>
                    <?php else: ?>
                        <div class="tabel-bungkus">
                            <table class="tabel-rsvp">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>RSVP</th>
                                        <th>Jumlah</th>
                                        <th>Pesan &amp; Doa</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftarUcapan as $u): ?>
                                        <tr class="<?= $u['tampil'] ? '' : 'baris-sembunyi' ?>">
                                            <td>
                                                <strong><?= h($u['nama']) ?></strong>
                                                <?php if ($u['tamu_id'] !== null): ?>
                                                    <br><span class="badge-status abu">Tamu No. <?= (int) $u['tamu_id'] ?></span>
                                                <?php endif; ?>
                                                <?php if (!$u['tampil']): ?>
                                                    <br><span class="badge-status abu">Disembunyikan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge-status <?= $u['rsvp'] === 'Hadir' ? 'hijau' : ($u['rsvp'] === 'Tidak Hadir' ? 'merah' : 'kuning') ?>">
                                                    <?= h($u['rsvp']) ?>
                                                </span>
                                            </td>
                                            <td><?= h(label_jumlah($u['jumlah_tamu'])) ?></td>
                                            <td class="teks-pesan"><?= nl2br(h($u['pesan'])) ?></td>
                                            <td>
                                                <small><?= h(tanggal_indo($u['created_at'])) ?></small>
                                                <?php if ($u['diubah_at'] !== null): ?>
                                                    <br><small class="ket-field">diperbarui <?= h(tanggal_indo($u['diubah_at'])) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="kolom-aksi">
                                                <form method="post" action="dashboard.php" style="display:inline;">
                                                    <input type="hidden" name="aksi" value="toggle_tampil">
                                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                    <input type="hidden" name="tampil_baru" value="<?= $u['tampil'] ? 0 : 1 ?>">
                                                    <button type="submit" class="tombol-kecil">
                                                        <?= $u['tampil'] ? 'Sembunyikan' : 'Tampilkan' ?>
                                                    </button>
                                                </form>

                                                <form method="post" action="dashboard.php" style="display:inline;" data-konfirmasi="Hapus ucapan ini secara permanen?">
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                    <button type="submit" class="tombol-kecil bahaya">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="tab-panel" id="tab-akun" role="tabpanel" hidden>
                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-user-gear"></i> Pengaturan Akun Admin</h2>
                    <form method="post" action="dashboard.php" autocomplete="off">
                        <input type="hidden" name="aksi" value="ubah_akun">
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

                        <div class="input-group">
                            <label for="akunUsername">Username</label>
                            <input type="text" id="akunUsername" name="username" class="custom-input" value="<?= h($_SESSION['admin_username']) ?>" required>
                        </div>

                        <div class="input-group">
                            <label for="akunPasswordLama">Password Saat Ini</label>
                            <input type="password" id="akunPasswordLama" name="password_lama" class="custom-input" required>
                        </div>

                        <div class="input-group">
                            <label for="akunPasswordBaru">Password Baru (kosongkan jika tidak diganti)</label>
                            <input type="password" id="akunPasswordBaru" name="password_baru" class="custom-input" minlength="8">
                        </div>

                        <div class="input-group">
                            <label for="akunKonfirmasi">Ulangi Password Baru</label>
                            <input type="password" id="akunKonfirmasi" name="konfirmasi" class="custom-input" minlength="8">
                        </div>

                        <button type="submit" class="tombol-aksi">Simpan Perubahan Akun</button>
                    </form>

                    <form method="post" action="dashboard.php" style="margin-top: 15px;">
                        <input type="hidden" name="aksi" value="keluar">
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                        <button type="submit" class="tombol-aksi btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Keluar (Logout)</button>
                    </form>
                </div>
            </section>
<?php endif; ?>

        </div>
    </div>

<script src="assets/scripts/dashboard.js" defer></script>
</body>
</html>
