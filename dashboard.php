<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/dashboard_fungsi.php';

$pesanNotif = '';
$jenisNotif = '';
$undoNotif = false;
$defaultKonten = konten_default();

if ($koneksi && sudah_login() && ($_GET['ekspor'] ?? '') === 'tamu') {
    ekspor_tamu($koneksi);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = ambil_post_string('aksi');
    $mintaJson = $aksi === 'unggah_foto';

    if (!cek_csrf(ambil_post_string('csrf'))) {
        if ($mintaJson) kirim_json(['ok' => false, 'pesan' => 'Sesi tidak valid. Muat ulang halaman.'], 403);
        $pesanNotif = 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.';
        $jenisNotif = 'galat';
    } elseif (!$koneksi && $aksi !== 'keluar') {
        if ($mintaJson) kirim_json(['ok' => false, 'pesan' => 'Koneksi database bermasalah.'], 500);
        $pesanNotif = 'Koneksi database bermasalah. Silakan coba lagi nanti.';
        $jenisNotif = 'galat';
    } elseif ($aksi === 'masuk') {
        $sisaKunci = ($_SESSION['kunci_sampai'] ?? 0) - time();
        if ($sisaKunci > 0) {
            $pesanNotif = "Terlalu banyak percobaan. Coba lagi dalam {$sisaKunci} detik.";
            $jenisNotif = 'galat';
        } else {
            $admin = jalankan($koneksi, 'SELECT id, username, password FROM tb_admin WHERE username = ?', 's', [trim(ambil_post_string('username'))])->get_result()->fetch_assoc();

            if ($admin && password_verify(ambil_post_string('password'), $admin['password'])) {
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
        if ($mintaJson) kirim_json(['ok' => false, 'pesan' => 'Silakan masuk kembali.'], 401);
    } elseif ($aksi === 'keluar') {
        $_SESSION = [];
        session_destroy();
        header('Location: dashboard.php');
        exit;
    } elseif ($mintaJson) {
        $hasil = proses_unggah_foto($_FILES['foto'] ?? null);
        kirim_json($hasil, $hasil['ok'] ? 200 : 422);
    } elseif (in_array($aksi, ['tamu_ekspor', 'ucapan_ekspor'], true)) {
        aksi_ekspor_terpilih($koneksi, $aksi);
    } else {
        $penangan = [
            'simpan_konten' => 'aksi_simpan_konten',
            'tamu_tambah' => 'aksi_tamu_tambah',
            'tamu_ubah' => 'aksi_tamu_ubah',
            'tamu_hapus' => 'aksi_tamu_hapus',
            'tamu_impor' => 'aksi_tamu_impor',
            'ucapan_tampil' => 'aksi_ucapan_tampil',
            'ucapan_sembunyi' => 'aksi_ucapan_tampil',
            'ucapan_hapus' => 'aksi_ucapan_hapus',
            'ubah_akun' => 'aksi_ubah_akun',
            'urungkan' => 'aksi_urungkan',
        ];
        if (isset($penangan[$aksi])) {
            alihkan($penangan[$aksi]($koneksi, $aksi));
        }
    }
}

$login = sudah_login();

if ($login && isset($_SESSION['notif'])) {
    ['pesan' => $pesanNotif, 'jenis' => $jenisNotif, 'undo' => $undoNotif] = $_SESSION['notif'];
    unset($_SESSION['notif']);
}
if ($login && !$koneksi && $pesanNotif === '') {
    $pesanNotif = 'Koneksi database bermasalah. Data dashboard belum dapat dimuat.';
    $jenisNotif = 'galat';
}
$csrf = token_csrf();

$konten = muat_konten($koneksi);
$namaPasangan = nama_pasangan($konten);

$rekap = ['Hadir' => 0, 'Tidak Hadir' => 0, 'Masih Ragu' => 0];
$orangRsvp = $rekap;
$orangHadir = [1 => 0, 2 => 0, 3 => 0];
$harian = [];
$aktivitas = [];
$daftarTamu = [];
$daftarUcapan = [];
$totalTamu = $tamuMerespons = $totalTamuCocok = $totalUcapanCocok = 0;
$ht = $hu = $maksHt = $maksHu = 1;
$qt = $qu = '';
$ft = $fu = 'semua';

if ($login && $koneksi) {
    foreach ($koneksi->query('SELECT rsvp, COUNT(*) AS n, COALESCE(SUM(jumlah_tamu), 0) AS orang FROM tb_ucapan GROUP BY rsvp') as $b) {
        $rekap[$b['rsvp']] = (int) $b['n'];
        $orangRsvp[$b['rsvp']] = (int) $b['orang'];
    }
    foreach ($koneksi->query("SELECT LEAST(3, GREATEST(1, jumlah_tamu)) AS j, COUNT(*) AS n FROM tb_ucapan WHERE rsvp = 'Hadir' GROUP BY j") as $b) {
        $orangHadir[(int) $b['j']] = (int) $b['n'];
    }
    foreach ($koneksi->query('SELECT DATE(created_at) AS d, COUNT(*) AS n FROM tb_ucapan WHERE created_at >= CURDATE() - INTERVAL 365 DAY GROUP BY d') as $b) {
        $harian[$b['d']] = (int) $b['n'];
    }
    $aktivitas = $koneksi->query('SELECT jenis, teks, dibuat_pada FROM tb_log ORDER BY id DESC LIMIT 12')->fetch_all(MYSQLI_ASSOC);
    $totalTamu = (int) $koneksi->query('SELECT COUNT(*) FROM tb_tamu')->fetch_row()[0];
    $tamuMerespons = (int) $koneksi->query('SELECT COUNT(*) FROM tb_ucapan WHERE tamu_id IS NOT NULL')->fetch_row()[0];

    $qt = get_string('qt');
    $ft = in_array(get_string('ft'), ['belum', 'sudah'], true) ? get_string('ft') : 'semua';
    $kondisi = [];
    $tipe = '';
    $param = [];
    if ($qt !== '') {
        $kondisi[] = '(t.nama LIKE ? OR t.id = ?)';
        $tipe .= 'si';
        array_push($param, pola_like($qt), (int) $qt);
    }
    if ($ft !== 'semua') {
        $kondisi[] = $ft === 'sudah' ? 'u.id IS NOT NULL' : 'u.id IS NULL';
    }
    [$daftarTamu, $totalTamuCocok, $ht, $maksHt] = halaman_data($koneksi,
        'tb_tamu t LEFT JOIN tb_ucapan u ON u.tamu_id = t.id',
        't.id, t.nama, t.dibuat_pada, u.id AS ucapan_id, u.rsvp, u.jumlah_tamu, u.pesan, u.created_at, u.diubah_at',
        bangun_where($kondisi), $tipe, $param, 't.id', (int) get_string('ht'));

    $qu = get_string('qu');
    $fu = in_array(get_string('fu'), [...array_keys($rekap), 'disembunyikan'], true) ? get_string('fu') : 'semua';
    $kondisi = [];
    $tipe = '';
    $param = [];
    if ($qu !== '') {
        $kondisi[] = '(nama LIKE ? OR pesan LIKE ?)';
        $tipe = 'ss';
        $param = [pola_like($qu), pola_like($qu)];
    }
    if ($fu === 'disembunyikan') {
        $kondisi[] = 'tampil = 0';
    } elseif ($fu !== 'semua') {
        $kondisi[] = 'rsvp = ?';
        $tipe .= 's';
        $param[] = $fu;
    }
    [$daftarUcapan, $totalUcapanCocok, $hu, $maksHu] = halaman_data($koneksi, 'tb_ucapan',
        'id, tamu_id, nama, rsvp, jumlah_tamu, pesan, tampil, created_at, diubah_at',
        bangun_where($kondisi), $tipe, $param, 'created_at DESC, id DESC', (int) get_string('hu'));
}

$totalUcapan = array_sum($rekap);
$estimasiTamu = $orangRsvp['Hadir'];
$tamuBelum = $totalTamu - $tamuMerespons;
$persenRespons = $totalTamu > 0 ? round($tamuMerespons / $totalTamu * 100) : 0;
$maksOrang = max(1, ...$orangHadir);
$urlUndangan = url_undangan();
$warnaRsvp = ['Hadir' => '#4E6151', 'Tidak Hadir' => '#a33a3a', 'Masih Ragu' => '#d4a373'];
$kelasRsvp = ['Hadir' => 'hijau', 'Tidak Hadir' => 'merah', 'Masih Ragu' => 'kuning'];
$jsonFlag = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4E6151">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin - <?= h($namaPasangan) ?></title>
    <script>try{var t=localStorage.getItem('temaDashboard')||(matchMedia('(prefers-color-scheme: dark)').matches?'gelap':'terang');document.documentElement.dataset.tema=t}catch(e){}</script>

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
                <button type="button" class="tombol-kecil tombol-tema" id="tombolTema" aria-label="Ganti tema terang/gelap" title="Ganti tema">
                    <i class="fa-solid fa-moon ikon-gelap"></i><i class="fa-solid fa-sun ikon-terang"></i>
                </button>
            </div>

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
                        <?php foreach ([
                            ['Tamu Diundang', $totalTamu, null, ''],
                            ['Sudah Merespons', $tamuMerespons, url_dashboard(['ft' => 'sudah', 'qt' => '', 'ht' => 1], 'tamu'), ''],
                            ['Total Ucapan', $totalUcapan, url_dashboard(['fu' => 'semua', 'qu' => '', 'hu' => 1], 'ucapan'), ''],
                            ['Estimasi Tamu Hadir', $estimasiTamu, null, 'sorot'],
                            ['Hadir', $rekap['Hadir'], url_dashboard(['fu' => 'Hadir', 'qu' => '', 'hu' => 1], 'ucapan'), ''],
                            ['Tidak Hadir', $rekap['Tidak Hadir'], url_dashboard(['fu' => 'Tidak Hadir', 'qu' => '', 'hu' => 1], 'ucapan'), ''],
                            ['Masih Ragu', $rekap['Masih Ragu'], url_dashboard(['fu' => 'Masih Ragu', 'qu' => '', 'hu' => 1], 'ucapan'), ''],
                            ['Belum Merespons', $tamuBelum, url_dashboard(['ft' => 'belum', 'qt' => '', 'ht' => 1], 'tamu'), ''],
                        ] as [$label, $angka, $url, $kelas]): $tag = $url ? 'a' : 'div'; ?>
                            <<?= $tag ?> class="statistik-kartu <?= $kelas ?>"<?= $url ? ' href="' . h($url) . '" data-skeleton' : '' ?>>
                                <span class="statistik-angka"><?= $angka ?></span>
                                <span class="statistik-label"><?= $label ?></span>
                            </<?= $tag ?>>
                        <?php endforeach; ?>
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
                                        <a href="<?= h(url_dashboard(['fu' => $label, 'qu' => '', 'hu' => 1], 'ucapan')) ?>" aria-label="<?= h($label) ?>">
                                            <circle class="donut-segmen" cx="21" cy="21" r="15.9155" fill="none" stroke="<?= $warnaRsvp[$label] ?>" stroke-width="6"
                                                    stroke-dasharray="<?= round($persen, 3) ?> <?= round(100 - $persen, 3) ?>" stroke-dashoffset="<?= round($geser, 3) ?>"
                                                    data-tip="<?= h("$label: $jml ucapan, estimasi {$orangRsvp[$label]} orang") ?>"></circle>
                                        </a>
                                    <?php $geser -= $persen; endforeach; ?>
                                    <text x="21" y="21.5" class="donut-angka" text-anchor="middle"><?= $totalUcapan ?></text>
                                    <text x="21" y="26" class="donut-label" text-anchor="middle">ucapan</text>
                                </svg>
                                <ul class="legenda">
                                    <?php foreach ($rekap as $label => $jml): ?>
                                        <li><a href="<?= h(url_dashboard(['fu' => $label, 'qu' => '', 'hu' => 1], 'ucapan')) ?>" data-tip="<?= h("$label: $jml ucapan, estimasi {$orangRsvp[$label]} orang") ?>">
                                            <span class="titik" style="background: <?= $warnaRsvp[$label] ?>"></span>
                                            <?= h($label) ?> <strong><?= $jml ?></strong>
                                            <small>(<?= round($jml / $totalUcapan * 100) ?>%)</small></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <p class="ket-field rata-tengah">Klik bagian diagram untuk memfilter tabel ucapan.</p>
                        <?php endif; ?>
                    </div>

                    <div class="dashboard-block kaca">
                        <h2 class="block-title"><i class="fa-solid fa-envelope-circle-check"></i> Respons dari Daftar Tamu</h2>
                        <?php if ($totalTamu === 0): ?>
                            <p class="teks-kosong">Daftar tamu masih kosong.</p>
                        <?php else: ?>
                            <div class="progres" role="progressbar" aria-valuenow="<?= $persenRespons ?>" aria-valuemin="0" aria-valuemax="100"
                                 data-tip="<?= h("$tamuMerespons sudah merespons, $tamuBelum belum") ?>">
                                <div class="progres-isi" style="width: <?= $persenRespons ?>%"></div>
                            </div>
                            <p class="progres-teks"><strong><?= $tamuMerespons ?></strong> dari <strong><?= $totalTamu ?></strong> tamu sudah merespons (<?= $persenRespons ?>%)</p>
                        <?php endif; ?>

                        <h3 class="sub-judul">Jumlah orang per konfirmasi hadir</h3>
                        <?php foreach ([1 => '1 Orang', 2 => '2 Orang', 3 => '3+ Orang'] as $n => $labelOrang): ?>
                            <div class="bar-baris" data-tip="<?= h("{$orangHadir[$n]} konfirmasi hadir dengan $labelOrang") ?>">
                                <span class="bar-label"><?= $labelOrang ?></span>
                                <div class="bar-latar"><div class="bar-isi" style="width: <?= round($orangHadir[$n] / $maksOrang * 100) ?>%"></div></div>
                                <span class="bar-nilai"><?= $orangHadir[$n] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dashboard-block kaca">
                    <div class="kepala-grafik">
                        <h2 class="block-title"><i class="fa-solid fa-chart-column"></i> Ucapan Masuk</h2>
                        <div class="pilih-rentang" role="group" aria-label="Rentang waktu grafik">
                            <button type="button" class="tombol-kecil" data-rentang="7">7 hari</button>
                            <button type="button" class="tombol-kecil aktif" data-rentang="14">14 hari</button>
                            <button type="button" class="tombol-kecil" data-rentang="30">30 hari</button>
                            <button type="button" class="tombol-kecil" data-rentang="kustom">Kustom</button>
                        </div>
                    </div>
                    <div class="baris-kustom" id="barisKustom" hidden>
                        <input type="date" id="rentangDari" class="custom-input" aria-label="Dari tanggal">
                        <span>s/d</span>
                        <input type="date" id="rentangSampai" class="custom-input" aria-label="Sampai tanggal">
                        <button type="button" class="tombol-kecil utama" id="terapkanRentang">Terapkan</button>
                    </div>
                    <div class="kolom-grafik" id="kolomGrafik" role="img" aria-label="Grafik jumlah ucapan per hari"></div>
                    <p class="ket-field rata-tengah" id="ketGrafik"></p>
                </div>

                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-clock-rotate-left"></i> Aktivitas Terbaru</h2>
                    <?php if (!$aktivitas): ?>
                        <p class="teks-kosong">Belum ada aktivitas tercatat.</p>
                    <?php else: ?>
                        <ol class="linimasa">
                            <?php foreach ($aktivitas as $a): ?>
                                <li>
                                    <span class="linimasa-ikon"><i class="fa-solid <?= h(ikon_log($a['jenis'])) ?>"></i></span>
                                    <div><span><?= h($a['teks']) ?></span><small><?= h(waktu_relatif($a['dibuat_pada'])) ?></small></div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
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
                                <span class="badge-belum-simpan" id="badgeBelumSimpan" hidden><i class="fa-solid fa-circle-exclamation"></i> Perubahan belum disimpan</span>
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
                        <form method="post" action="dashboard.php" class="form-tambah-tamu" data-memuat>
                            <input type="hidden" name="aksi" value="tamu_tambah">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <label for="namaTamuBaru">Tambah tamu</label>
                            <div class="baris-input-tombol">
                                <input type="text" id="namaTamuBaru" name="nama" class="custom-input" maxlength="100" placeholder="Nama tamu" required>
                                <button type="submit" class="tombol-kecil utama"><i class="fa-solid fa-plus"></i> Tambah</button>
                            </div>
                        </form>

                        <form method="post" action="dashboard.php" enctype="multipart/form-data" class="form-impor" data-memuat>
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
                        <a class="tombol-kecil utama" href="dashboard.php?ekspor=tamu"><i class="fa-solid fa-file-export"></i> Ekspor Semua CSV</a>
                    </div>
                    <p class="ket-field">Impor membaca kolom <strong>Nama</strong> (atau kolom pertama bila tanpa judul), maksimal <?= MAKS_BARIS_CSV ?> baris; nama yang sudah ada dilewati. Pemisah koma atau titik koma dikenali, dan encoding non-UTF-8 dikonversi otomatis.</p>
                </div>

                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-address-book"></i> Daftar Tamu (<?= $totalTamu ?>)</h2>

                    <form method="get" action="dashboard.php#tab-tamu" class="baris-filter" data-skeleton>
                        <input type="search" name="qt" class="custom-input" value="<?= h($qt) ?>" placeholder="Cari nama atau nomor..." aria-label="Cari tamu">
                        <select name="ft" class="custom-input" aria-label="Filter status" data-otomatis>
                            <?php foreach (['semua' => 'Semua status', 'belum' => 'Belum merespons', 'sudah' => 'Sudah merespons'] as $nilai => $teks): ?>
                                <option value="<?= $nilai ?>"<?= $ft === $nilai ? ' selected' : '' ?>><?= $teks ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="tombol-kecil utama"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                    </form>

                    <?php if (!$daftarTamu): ?>
                        <p class="teks-kosong"><?= $totalTamu === 0 ? 'Belum ada tamu. Tambahkan tamu atau impor dari CSV.' : 'Tidak ada tamu yang cocok.' ?></p>
                    <?php else: ?>
                        <form method="post" action="dashboard.php" id="formMassalTamu" class="bar-massal" data-memuat>
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <span class="jumlah-dipilih" data-jumlah-dipilih>0 dipilih</span>
                            <button type="submit" name="aksi" value="tamu_ekspor" class="tombol-kecil" data-butuh-pilihan disabled><i class="fa-solid fa-file-export"></i> Ekspor terpilih</button>
                            <button type="submit" name="aksi" value="tamu_hapus" class="tombol-kecil bahaya" data-butuh-pilihan disabled data-konfirmasi="Hapus semua tamu yang dipilih? Ucapan yang sudah dikirim tetap tersimpan."><i class="fa-solid fa-trash"></i> Hapus terpilih</button>
                        </form>

                        <div class="tabel-bungkus">
                            <table class="tabel-rsvp tabel-tamu" id="tabelTamu">
                                <thead>
                                    <tr>
                                        <th class="kol-cek"><input type="checkbox" data-pilih-semua aria-label="Pilih semua di halaman ini"></th>
                                        <th class="kol-no">No</th><th>Nama Tamu</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftarTamu as $t):
                                        $linkTamu = $urlUndangan . '?to=' . (int) $t['id'];
                                        $sudah = $t['ucapan_id'] !== null;
                                        $riwayat = [['Ditambahkan ke daftar tamu', tanggal_waktu_indo($t['dibuat_pada'])]];
                                        if ($sudah) $riwayat[] = ['Mengirim konfirmasi kehadiran', tanggal_waktu_indo($t['created_at'])];
                                        if ($t['diubah_at'] !== null) $riwayat[] = ['Memperbarui konfirmasi', tanggal_waktu_indo($t['diubah_at'])];
                                        $urlWa = 'https://wa.me/?text=' . rawurlencode(pesan_wa($t['nama'], $linkTamu, $konten));
                                        $detail = ['id' => (int) $t['id'], 'nama' => $t['nama'], 'link' => $linkTamu, 'wa' => $urlWa,
                                                   'rsvp' => $t['rsvp'], 'jumlah' => $sudah ? label_jumlah($t['jumlah_tamu']) : '',
                                                   'pesan' => $t['pesan'] ?? '', 'riwayat' => $riwayat]; ?>
                                        <tr>
                                            <td class="kol-cek"><input type="checkbox" name="ids[]" value="<?= (int) $t['id'] ?>" form="formMassalTamu" data-pilih aria-label="Pilih <?= h($t['nama']) ?>"></td>
                                            <td class="kol-no"><?= (int) $t['id'] ?></td>
                                            <td class="sel-nama">
                                                <div class="nama-tampil">
                                                    <button type="button" class="nama-tombol" data-detail="<?= h(json_encode($detail, $jsonFlag)) ?>" title="Lihat detail tamu"><?= h($t['nama']) ?></button>
                                                    <div class="baris-tombol-kecil">
                                                        <button type="button" class="tombol-kecil" data-salin="<?= h($linkTamu) ?>" title="Salin link undangan"><i class="fa-regular fa-copy"></i> Salin</button>
                                                        <a class="tombol-kecil" target="_blank" rel="noopener" title="Kirim lewat WhatsApp" href="<?= h($urlWa) ?>"><i class="fa-brands fa-whatsapp"></i> WA</a>
                                                        <button type="button" class="tombol-kecil" data-edit-tamu title="Ubah nama"><i class="fa-solid fa-pen"></i> Edit</button>
                                                        <form method="post" action="dashboard.php" class="inline" data-memuat>
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
                                                    <span class="badge-status <?= $kelasRsvp[$t['rsvp']] ?>"><?= h($t['rsvp']) ?></span>
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
                        <p class="ket-field rata-tengah">Menampilkan <?= count($daftarTamu) ?> dari <?= $totalTamuCocok ?> tamu. Klik nama untuk melihat detail dan QR code.</p>
                        <?php render_paginasi($ht, $maksHt, 'ht', 'tamu'); ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="tab-panel" id="tab-ucapan" role="tabpanel" hidden>
                <div class="dashboard-block kaca">
                    <h2 class="block-title"><i class="fa-solid fa-comments"></i> Data Ucapan &amp; Konfirmasi RSVP (<?= $totalUcapan ?>)</h2>

                    <form method="get" action="dashboard.php#tab-ucapan" class="baris-filter" data-skeleton>
                        <input type="search" name="qu" class="custom-input" value="<?= h($qu) ?>" placeholder="Cari nama atau pesan..." aria-label="Cari ucapan">
                        <select name="fu" class="custom-input" aria-label="Filter RSVP" data-otomatis>
                            <?php foreach (['semua' => 'Semua RSVP', 'Hadir' => 'Hadir', 'Tidak Hadir' => 'Tidak Hadir', 'Masih Ragu' => 'Masih Ragu', 'disembunyikan' => 'Disembunyikan'] as $nilai => $teks): ?>
                                <option value="<?= $nilai ?>"<?= $fu === $nilai ? ' selected' : '' ?>><?= $teks ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="tombol-kecil utama"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
                    </form>

                    <?php if (!$daftarUcapan): ?>
                        <p class="teks-kosong"><?= $totalUcapan === 0 ? 'Belum ada data ucapan yang masuk.' : 'Tidak ada ucapan yang cocok.' ?></p>
                    <?php else: ?>
                        <form method="post" action="dashboard.php" id="formMassalUcapan" class="bar-massal" data-memuat>
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <span class="jumlah-dipilih" data-jumlah-dipilih>0 dipilih</span>
                            <button type="submit" name="aksi" value="ucapan_sembunyi" class="tombol-kecil" data-butuh-pilihan disabled><i class="fa-regular fa-eye-slash"></i> Sembunyikan</button>
                            <button type="submit" name="aksi" value="ucapan_tampil" class="tombol-kecil" data-butuh-pilihan disabled><i class="fa-regular fa-eye"></i> Tampilkan</button>
                            <button type="submit" name="aksi" value="ucapan_ekspor" class="tombol-kecil" data-butuh-pilihan disabled><i class="fa-solid fa-file-export"></i> Ekspor</button>
                            <button type="submit" name="aksi" value="ucapan_hapus" class="tombol-kecil bahaya" data-butuh-pilihan disabled data-konfirmasi="Hapus semua ucapan yang dipilih?"><i class="fa-solid fa-trash"></i> Hapus</button>
                        </form>

                        <div class="tabel-bungkus">
                            <table class="tabel-rsvp">
                                <thead>
                                    <tr>
                                        <th class="kol-cek"><input type="checkbox" data-pilih-semua aria-label="Pilih semua di halaman ini"></th>
                                        <th>Nama</th><th>RSVP</th><th>Jumlah</th><th>Pesan &amp; Doa</th><th>Tanggal</th><th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daftarUcapan as $u): ?>
                                        <tr class="<?= $u['tampil'] ? '' : 'baris-sembunyi' ?>">
                                            <td class="kol-cek"><input type="checkbox" name="ids[]" value="<?= (int) $u['id'] ?>" form="formMassalUcapan" data-pilih aria-label="Pilih ucapan <?= h($u['nama']) ?>"></td>
                                            <td>
                                                <strong><?= h($u['nama']) ?></strong>
                                                <?php if ($u['tamu_id'] !== null): ?><br><span class="badge-status abu">Tamu No. <?= (int) $u['tamu_id'] ?></span><?php endif; ?>
                                                <?php if (!$u['tampil']): ?><br><span class="badge-status abu">Disembunyikan</span><?php endif; ?>
                                            </td>
                                            <td><span class="badge-status <?= $kelasRsvp[$u['rsvp']] ?>"><?= h($u['rsvp']) ?></span></td>
                                            <td><?= h(label_jumlah($u['jumlah_tamu'])) ?></td>
                                            <td class="teks-pesan"><?= nl2br(h($u['pesan'])) ?></td>
                                            <td>
                                                <small><?= h(tanggal_indo($u['created_at'])) ?></small>
                                                <?php if ($u['diubah_at'] !== null): ?><br><small class="ket-field">diperbarui <?= h(tanggal_indo($u['diubah_at'])) ?></small><?php endif; ?>
                                            </td>
                                            <td class="kolom-aksi">
                                                <form method="post" action="dashboard.php" class="inline" data-memuat>
                                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                    <button type="submit" name="aksi" value="<?= $u['tampil'] ? 'ucapan_sembunyi' : 'ucapan_tampil' ?>" class="tombol-kecil"><?= $u['tampil'] ? 'Sembunyikan' : 'Tampilkan' ?></button>
                                                </form>
                                                <form method="post" action="dashboard.php" class="inline" data-memuat data-konfirmasi="Hapus ucapan ini?">
                                                    <input type="hidden" name="aksi" value="ucapan_hapus">
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
                        <p class="ket-field rata-tengah">Menampilkan <?= count($daftarUcapan) ?> dari <?= $totalUcapanCocok ?> ucapan.</p>
                        <?php render_paginasi($hu, $maksHu, 'hu', 'ucapan'); ?>
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

<?php if ($login): ?>
    <div class="tooltip-grafik" id="tipGrafik" role="tooltip" hidden></div>

    <?php if ($pesanNotif !== ''): ?>
        <div class="toast-dash <?= h($jenisNotif) ?>" id="toastDash" role="status">
            <span><?= h($pesanNotif) ?></span>
            <?php if ($undoNotif): ?>
                <form method="post" action="dashboard.php">
                    <input type="hidden" name="aksi" value="urungkan">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <button type="submit" class="tombol-kecil utama">Urungkan</button>
                </form>
            <?php endif; ?>
            <button type="button" class="tutup-toast" id="tutupToast" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <div class="drawer-latar" id="drawerLatar" hidden></div>
    <aside class="drawer kaca" id="drawerTamu" role="dialog" aria-modal="true" aria-labelledby="drawerNama" hidden>
        <button type="button" class="tombol-kecil drawer-tutup" id="drawerTutup" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        <p class="drawer-no" id="drawerNo"></p>
        <h3 id="drawerNama"></h3>
        <div id="drawerStatus"></div>
        <blockquote class="drawer-pesan" id="drawerPesan" hidden></blockquote>
        <label class="drawer-label" for="drawerLink">Link undangan</label>
        <div class="baris-input-tombol">
            <input type="text" id="drawerLink" class="custom-input" readonly>
            <button type="button" class="tombol-kecil" id="drawerSalin"><i class="fa-regular fa-copy"></i> Salin</button>
        </div>
        <a class="tombol-aksi wa" id="drawerWa" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Kirim lewat WhatsApp</a>
        <div class="drawer-qr" id="drawerQr" aria-label="QR code link undangan"></div>
        <h4 class="sub-judul">Riwayat</h4>
        <ol class="linimasa" id="drawerRiwayat"></ol>
    </aside>

    <script>
        window.DATA_HARIAN = <?= json_encode($harian, $jsonFlag) ?>;
        window.HARI_INI = <?= json_encode(date('Y-m-d')) ?>;
    </script>
<?php endif; ?>
<script src="assets/scripts/dashboard.js" defer></script>
</body>
</html>
