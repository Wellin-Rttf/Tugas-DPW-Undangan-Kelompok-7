<?php
require_once __DIR__ . '/koneksi.php';
$kontenTersimpan = muat_konten($koneksi);
$konten = $kontenTersimpan;
$modePratinjau = isset($_GET['pratinjau']) && sudah_login();
$tampilanPratinjau = ($_GET['pratinjau'] ?? '') === 'sampul' ? 'sampul' : 'isi';
if ($modePratinjau && $_SERVER['REQUEST_METHOD'] === 'POST' && cek_csrf(ambil_post_string('csrf'))) {
    $konten = bersihkan_konten($_POST, $kontenTersimpan);
}
$pria   = $konten['mempelai']['pria'];
$wanita = $konten['mempelai']['wanita'];
$namaPasangan = nama_pasangan($konten);
$kodeTamu = $_GET['to'] ?? '';
$kodeTamu = is_string($kodeTamu) ? trim($kodeTamu) : '';
$tamuId = null;
$namaTamu = '';
$parameterTo = '';
if ($kodeTamu !== '' && ctype_digit($kodeTamu)) {
    if ($koneksi && strlen($kodeTamu) <= 9) {
        $idCari = (int) $kodeTamu;
        $stmt = $koneksi->prepare('SELECT id, nama FROM tb_tamu WHERE id = ?');
        $stmt->bind_param('i', $idCari);
        $stmt->execute();
        $tamu = $stmt->get_result()->fetch_assoc();
        if ($tamu) {
            $tamuId = (int) $tamu['id'];
            $namaTamu = $tamu['nama'];
            $parameterTo = '?to=' . $tamuId;
        }
    }
} elseif ($kodeTamu !== '') {
    $namaTamu = strip_tags($kodeTamu);
    $namaTamu = potong_teks(trim(preg_replace('/[\p{C}\s]+/u', ' ', $namaTamu)), 60);
    if ($namaTamu !== '') {
        $parameterTo = '?to=' . rawurlencode($namaTamu);
    }
}
$adaNamaTamu = $namaTamu !== '';
$actionForm = 'index.php' . $parameterTo . '#formulir-ucapan';
if (!$adaNamaTamu) $namaTamu = 'Tamu Undangan';
$ucapanSaya = null;
if ($koneksi && !$modePratinjau) {
    if ($tamuId !== null) {
        $stmt = $koneksi->prepare('SELECT id, nama, rsvp, jumlah_tamu, pesan FROM tb_ucapan WHERE tamu_id = ? LIMIT 1');
        $stmt->bind_param('i', $tamuId);
        $stmt->execute();
        $ucapanSaya = $stmt->get_result()->fetch_assoc() ?: null;
    } elseif (!empty($_SESSION['ucapan_id_saya'])) {
        $idSesi = (int) $_SESSION['ucapan_id_saya'];
        $stmt = $koneksi->prepare('SELECT id, nama, rsvp, jumlah_tamu, pesan FROM tb_ucapan WHERE id = ? AND tamu_id IS NULL LIMIT 1');
        $stmt->bind_param('i', $idSesi);
        $stmt->execute();
        $ucapanSaya = $stmt->get_result()->fetch_assoc() ?: null;
    }
}
$pesanStatus = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$kiriman = null; 
if (!$modePratinjau && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim(ambil_post_string('nama'));
    $pesan  = trim(ambil_post_string('pesan'));
    $rsvp   = ambil_post_string('rsvp');
    $labelJumlah = ambil_post_string('jumlah');
    $jumlah = PILIHAN_JUMLAH[$labelJumlah] ?? 0;
    $galat  = null;
    $diperbarui = false;
    if (trim(ambil_post_string('website')) === '') {
        if (isset($_SESSION['ucapan_terakhir']) && time() - $_SESSION['ucapan_terakhir'] < 10) {
            $galat = 'Mohon tunggu beberapa detik sebelum mengirim ucapan lagi.';
        } elseif ($nama === '' || $pesan === '') {
            $galat = 'Nama dan pesan wajib diisi.';
        } elseif (strlen($nama) > 100 || strlen($pesan) > 1000) {
            $galat = 'Nama maksimal 100 karakter dan pesan maksimal 1000 karakter.';
        } elseif (!in_array($rsvp, PILIHAN_RSVP, true) || $jumlah === 0) {
            $galat = 'Pilihan kehadiran tidak valid.';
        } elseif (!$koneksi) {
            $galat = 'Koneksi server bermasalah, silakan coba lagi nanti.';
        } else {
            if ($tamuId !== null) {
                $diperbarui = $ucapanSaya !== null;
                $stmt = $koneksi->prepare('INSERT INTO tb_ucapan (tamu_id, nama, rsvp, jumlah_tamu, pesan, created_at) VALUES (?, ?, ?, ?, ?, NOW())
                                           ON DUPLICATE KEY UPDATE nama = VALUES(nama), rsvp = VALUES(rsvp), jumlah_tamu = VALUES(jumlah_tamu), pesan = VALUES(pesan), diubah_at = NOW()');
                $stmt->bind_param('issis', $tamuId, $nama, $rsvp, $jumlah, $pesan);
                $stmt->execute();
            } elseif ($ucapanSaya !== null) {
                $diperbarui = true;
                $idUbah = (int) $ucapanSaya['id'];
                $stmt = $koneksi->prepare('UPDATE tb_ucapan SET nama = ?, rsvp = ?, jumlah_tamu = ?, pesan = ?, diubah_at = NOW() WHERE id = ? AND tamu_id IS NULL');
                $stmt->bind_param('ssisi', $nama, $rsvp, $jumlah, $pesan, $idUbah);
                $stmt->execute();
            } else {
                $stmt = $koneksi->prepare('INSERT INTO tb_ucapan (nama, rsvp, jumlah_tamu, pesan, created_at) VALUES (?, ?, ?, ?, NOW())');
                $stmt->bind_param('ssis', $nama, $rsvp, $jumlah, $pesan);
                $stmt->execute();
                $_SESSION['ucapan_id_saya'] = $koneksi->insert_id;
            }
            $_SESSION['ucapan_terakhir'] = time();
        }
    }
    if ($galat === null) {
        $_SESSION['flash'] = $diperbarui
            ? 'Konfirmasi & ucapan Anda berhasil diperbarui!'
            : 'Konfirmasi & ucapan Anda berhasil dikirim!';
        header('Location: ' . $actionForm, true, 303);
        exit;
    }
    $pesanStatus = $galat;
    $kiriman = ['nama' => $nama, 'pesan' => $pesan, 'rsvp' => $rsvp, 'jumlah' => $labelJumlah];
}
$formNama   = $kiriman['nama']   ?? ($ucapanSaya['nama'] ?? ($adaNamaTamu ? $namaTamu : ''));
$formPesan  = $kiriman['pesan']  ?? ($ucapanSaya['pesan'] ?? '');
$formRsvp   = $kiriman['rsvp']   ?? ($ucapanSaya['rsvp'] ?? 'Hadir');
$formJumlah = $kiriman['jumlah'] ?? ($ucapanSaya ? label_jumlah($ucapanSaya['jumlah_tamu']) : '1 Orang');
if (!in_array($formRsvp, PILIHAN_RSVP, true)) $formRsvp = 'Hadir';
if (!isset(PILIHAN_JUMLAH[$formJumlah])) $formJumlah = '1 Orang';
$dataUcapan = [];
if ($koneksi) {
    $hasil = $koneksi->query('SELECT nama, rsvp, jumlah_tamu, pesan, created_at, diubah_at FROM tb_ucapan WHERE tampil = 1 ORDER BY created_at DESC, id DESC LIMIT 500');
    while ($u = $hasil->fetch_assoc()) {
        $dataUcapan[] = [
            'nama'       => $u['nama'],
            'pesan'      => $u['pesan'],
            'rsvp'       => $u['rsvp'],
            'jumlah'     => label_jumlah($u['jumlah_tamu']),
            'tanggal'    => tanggal_indo($u['created_at']),
            'diperbarui' => $u['diubah_at'] !== null,
        ];
    }
}
$jsonFlag = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#4E6151">
  <title>Undangan Pernikahan - <?= h($namaPasangan) ?></title>
  <link rel="preconnect" href="https:
  <link rel="preconnect" href="https:
  <link href="https:
  <link rel="stylesheet" href="https:
  <link rel="stylesheet" href="assets/css/style.css">
<?php if ($konten['foto']['latar'] !== konten_default()['foto']['latar']): ?>
  <style>
    .latar-wallpaper {
      background:
        linear-gradient(180deg, rgba(28, 28, 30, 0.32) 0%, rgba(78, 97, 81, 0.6) 100%),
        url("<?= h($konten['foto']['latar']) ?>") center / cover no-repeat;
      background-color: var(--c-granite);
    }
  </style>
<?php endif; ?>
  <script>
    window.DATA_UCAPAN = <?= json_encode($dataUcapan, $jsonFlag) ?>;
    window.PESAN_STATUS = <?= json_encode($pesanStatus, $jsonFlag) ?>;
    window.KONTEN_ACARA = <?= json_encode(data_acara_js($konten), $jsonFlag) ?>;
    window.MODE_PRATINJAU = <?= json_encode($modePratinjau ? $tampilanPratinjau : '', $jsonFlag) ?>;
  </script>
  <script src="assets/scripts/script.js" defer></script>
</head>
<body class="kunci-scroll">
  <div class="latar-wallpaper" aria-hidden="true"></div>
  <audio id="musik-latar" loop preload="none">
    <source src="assets/music/SomethingGreaterInterlude.m4a" type="audio/mp4">
    Browser Anda tidak mendukung elemen audio.
  </audio>
  <button id="tombol-musik" class="tombol-musik hidden" aria-label="Toggle Musik">
    <i class="fa-solid fa-compact-disc putar"></i>
  </button>
  <div class="wadah-utama">
    <header class="sampul" id="sampul">
      <div class="sampul-atas animasi-float">
        <span class="badge-wedding">Undangan Pernikahan</span>
        <h1 class="nama-sampul nama-sampul-script"><?= h($pria['panggilan']) ?> <span class="ampersand">&amp;</span> <?= h($wanita['panggilan']) ?></h1>
      </div>
      <div class="sampul-bawah">
        <div class="kartu-tamu-sampul">
          <p class="kepada-label">Kepada Yth. Bapak/Ibu/Saudara/i:</p>
          <h2 class="tamu-sampul"><?= h($namaTamu) ?></h2>
          <p class="catatan-sampul">Tanpa Mengurangi Rasa Hormat, Kami Mengundang Anda Untuk Hadir</p>
          <button class="tombol tombol-buka-mewah" id="tombol-buka">
            <i class="fa-solid fa-envelope-open-text"></i> Buka Undangan
          </button>
        </div>
      </div>
    </header>
    <main id="konten" style="display: none;">
      <section class="bagian pembuka">
        <div class="pembuka-card kaca animasi-muncul">
          <div class="hiasan-bunga-top"><i class="fa-solid fa-leaf"></i></div>
          <h2 class="label-tamu">Undangan Pernikahan</h2>
          <p class="salam">
            Assalamu'alaikum Warahmatullahi Wabarakatuh.<br>
            Dengan memohon rahmat dan ridha Allah SWT, kami bermaksud mengundang Anda dalam momen bahagia pernikahan kami:
          </p>
          <div class="nama-mempelai-hero">
            <span class="nama"><?= h($pria['panggilan']) ?></span>
            <span class="simbol">&amp;</span>
            <span class="nama"><?= h($wanita['panggilan']) ?></span>
          </div>
          <div class="badge-tanggal">
            <i class="fa-regular fa-calendar-check"></i> <?= h(tanggal_panjang($konten['acara']['tanggal'])) ?>
          </div>
          <div class="foto-mempelai-wrapper">
            <img class="foto-mempelai" src="<?= h($konten['foto']['pasangan']) ?>" alt="<?= h($pria['panggilan'] . ' dan ' . $wanita['panggilan']) ?>">
          </div>
          <blockquote class="kutipan-ayat">
            <p class="ayat">"Dan di antara tanda-tanda (kebesaran)-Nya ialah Dia menciptakan pasangan-pasangan untukmu dari jenismu sendiri, agar kamu cenderung dan merasa tenteram kepadanya, dan Dia menjadikan di antaramu rasa kasih dan sayang."</p>
            <cite class="sumber-ayat">— QS. Ar-Rum: 21</cite>
          </blockquote>
        </div>
      </section>
      <section class="bagian">
        <div class="countdown-card animasi-muncul">
          <h2 class="judul-bagian">Hitung Mundur Hari Bahagia</h2>
          <p class="subjudul-bagian">Menghitung hari menuju momen bahagia kami</p>
          <div class="countdown-grid" id="countdown">
            <div class="countdown-item">
              <span id="cd-hari">00</span>
              <small>Hari</small>
            </div>
            <div class="countdown-item">
              <span id="cd-jam">00</span>
              <small>Jam</small>
            </div>
            <div class="countdown-item">
              <span id="cd-menit">00</span>
              <small>Menit</small>
            </div>
            <div class="countdown-item">
              <span id="cd-detik">00</span>
              <small>Detik</small>
            </div>
          </div>
        </div>
      </section>
      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Mempelai</h2>
        <div class="mempelai-grid">
<?php foreach ([['m' => $wanita, 'peran' => 'Mempelai Wanita'], ['m' => $pria, 'peran' => 'Mempelai Pria']] as $kartu): $m = $kartu['m']; ?>
          <article class="profil-mempelai-card kaca animasi-muncul">
            <div class="foto-box">
              <img loading="lazy" src="<?= h($m['foto']) ?>" alt="<?= h($m['panggilan']) ?>">
              <span class="badge-peran"><?= h($kartu['peran']) ?></span>
            </div>
            <div class="info-mempelai">
              <h3><?= h($m['nama']) ?></h3>
<?php if ($m['anak_dari'] !== '' || $m['orang_tua'] !== ''): ?>
              <p class="anak-dari"><?= h($m['anak_dari']) ?><br><strong><?= h($m['orang_tua']) ?></strong></p>
<?php endif; ?>
<?php if ($m['ig_nama'] !== '' && $m['ig_link'] !== ''): ?>
              <a class="ig-mempelai" href="<?= h($m['ig_link']) ?>" target="_blank" rel="noopener">
                <i class="fa-brands fa-instagram"></i> <?= h($m['ig_nama']) ?>
              </a>
<?php elseif ($m['ig_nama'] !== ''): ?>
              <span class="ig-mempelai">
                <i class="fa-brands fa-instagram"></i> <?= h($m['ig_nama']) ?>
              </span>
<?php endif; ?>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </section>
      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Acara Pernikahan</h2>
        <div class="acara-grid">
<?php foreach ([['akad', 'fa-hands-praying', ''], ['resepsi', 'fa-champagne-glasses', ' highlight']] as [$jenisAcara, $ikonAcara, $kelasAcara]): $a = $konten['acara'][$jenisAcara]; ?>
          <div class="acara-card<?= $kelasAcara ?> kaca animasi-muncul">
            <div class="icon-acara"><i class="fa-solid <?= h($ikonAcara) ?>"></i></div>
            <h3 class="judul-acara"><?= h($a['judul']) ?></h3>
            <div class="detail-acara-item"><i class="fa-regular fa-calendar"></i> <?= h(tanggal_panjang($konten['acara']['tanggal'])) ?></div>
            <div class="detail-acara-item"><i class="fa-regular fa-clock"></i> <?= h(rentang_jam($a['jam_mulai'], $a['jam_selesai'])) ?></div>
            <p class="tempat-acara"><?= h($a['tempat']) ?></p>
<?php if ($a['alamat'] !== ''): ?>
            <p class="alamat-acara"><?= h($a['alamat']) ?></p>
<?php endif; ?>
<?php if ($a['link_maps'] !== ''): ?>
            <a class="tombol tombol-outline" href="<?= h($a['link_maps']) ?>" target="_blank" rel="noopener">
              <i class="fa-solid fa-diamond-turn-right"></i> Petunjuk Lokasi
            </a>
<?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
        <div class="banner-acara animasi-muncul">
          <img loading="lazy" class="foto-acara" src="<?= h($konten['foto']['acara']) ?>" alt="Tukar Cincin">
          <div class="kalender-actions">
            <button type="button" class="tombol tombol-std"
                    onclick="simpanKeGoogleCalendar('akad')">
              <i class="fa-solid fa-calendar-plus"></i>
              Simpan Akad Nikah
            </button>
            <button type="button" class="tombol tombol-std"
                    onclick="simpanKeGoogleCalendar('resepsi')">
              <i class="fa-solid fa-calendar-plus"></i>
              Simpan Resepsi
            </button>
          </div>
        </div>
      </section>
      <?php if ($konten['galeri']): ?>
      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Galeri Kami</h2>
        <p class="petunjuk-galeri animasi-muncul"><i class="fa-solid fa-hand-pointer"></i> Klik foto untuk memperbesar &amp; melihat slide</p>
        <div class="galeri-foto-modern" id="galeri-foto">
<?php foreach ($konten['galeri'] as $i => $fotoGaleri): $no = $i + 1; ?>
          <div class="galeri-item<?= $i === 0 ? ' item-besar' : '' ?> animasi-muncul" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="lightbox-modal" aria-label="Perbesar foto galeri <?= $no ?>">
            <img loading="lazy" src="<?= h($fotoGaleri) ?>" alt="Galeri Momen <?= $no ?>">
            <div class="overlay-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
          </div>
<?php endforeach; ?>
        </div>
      </section>
<?php endif; ?>
      <?php if ($konten['perjalanan']): ?>
      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Perjalanan Kami</h2>
        <div class="timeline">
<?php foreach ($konten['perjalanan'] as $i => $p): ?>
          <div class="timeline-item <?= $i % 2 === 0 ? 'left' : 'right' ?>">
            <div class="timeline-dot animasi-muncul"><i class="fa-solid <?= h($p['ikon']) ?>"></i></div>
            <div class="timeline-content kaca animasi-muncul">
              <span class="timeline-year"><?= h($p['tahun']) ?></span>
              <h3><?= h($p['judul']) ?></h3>
              <p><?= nl2br(h($p['teks'])) ?></p>
            </div>
          </div>
<?php endforeach; ?>
        </div>
      </section>
<?php endif; ?>
      <?php if ($konten['hadiah']['tampil'] && $konten['hadiah']['rekening']): ?>
      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Hadiah Digital</h2>
<?php if ($konten['hadiah']['pengantar'] !== ''): ?>
        <p class="subjudul-bagian animasi-muncul"><?= h($konten['hadiah']['pengantar']) ?></p>
<?php endif; ?>
        <button type="button" class="tombol tombol-std tombol-toggle animasi-muncul" id="tombol-hadiah" aria-expanded="false" aria-controls="panel-hadiah">
          <i class="fa-solid fa-gift"></i>
          <span id="teks-tombol-hadiah">Tampilkan Rekening</span>
          <i class="fa-solid fa-chevron-down ikon-panah"></i>
        </button>
        <div class="panel-hadiah" id="panel-hadiah" hidden>
         <div class="panel-hadiah-isi">
        <div class="amplop-grid">
<?php foreach ($konten['hadiah']['rekening'] as $i => $rek): $idRek = 'rek-' . ($i + 1); $namaBank = preg_replace('/^bank\s+/iu', '', $rek['bank']); ?>
          <div class="amplop-card kaca">
            <div class="logo-bank"><i class="fa-solid fa-building-columns"></i> <?= h($rek['bank']) ?></div>
            <p class="no-rek" id="<?= h($idRek) ?>"><?= h($rek['nomor']) ?></p>
<?php if ($rek['atas_nama'] !== ''): ?>
            <p class="pemilik-rek">a.n. <?= h($rek['atas_nama']) ?></p>
<?php endif; ?>
            <button class="tombol-salin" onclick="salinTeks(<?= h(json_encode($idRek)) ?>, <?= h(json_encode('Nomor Rekening ' . $namaBank . ' Berhasil Disalin!', JSON_UNESCAPED_UNICODE)) ?>)">
              <i class="fa-regular fa-copy"></i> Salin Rekening
            </button>
          </div>
<?php endforeach; ?>
        </div>
         </div>
        </div>
      </section>
<?php endif; ?>
      <section class="bagian bagian-ucapan">
        <h2 class="judul-bagian animasi-muncul">Konfirmasi &amp; Ucapan</h2>
        <p class="pengantar-ucapan subjudul-bagian animasi-muncul">Konfirmasi kehadiran Anda &amp; berikan ucapan hangat untuk kedua mempelai</p>
        <form id="formulir-ucapan" class="formulir-ucapan kaca animasi-muncul" action="<?= h($actionForm) ?>" method="post">
          <input type="text" name="website" class="jebakan" tabindex="-1" autocomplete="off" aria-hidden="true">
<?php if ($ucapanSaya !== null): ?>
          <p class="catatan-edit"><i class="fa-solid fa-pen-to-square"></i> Anda sudah pernah mengirim konfirmasi. Ubah isian di bawah, lalu kirim untuk memperbarui.</p>
<?php endif; ?>
          <div class="baris-input">
            <label for="input-nama">Nama Lengkap</label>
            <input type="text" id="input-nama" name="nama" maxlength="100" placeholder="Masukkan nama Anda..." value="<?= h($formNama) ?>" required>
          </div>
          <div class="baris-input">
            <label>Konfirmasi Kehadiran</label>
            <div class="opsi-rsvp">
              <label class="radio-card">
                <input type="radio" name="rsvp" value="Hadir"<?= $formRsvp === 'Hadir' ? ' checked' : '' ?>>
                <span><i class="fa-solid fa-circle-check"></i> Hadir</span>
              </label>
              <label class="radio-card">
                <input type="radio" name="rsvp" value="Tidak Hadir"<?= $formRsvp === 'Tidak Hadir' ? ' checked' : '' ?>>
                <span><i class="fa-solid fa-circle-xmark"></i> Tidak Hadir</span>
              </label>
              <label class="radio-card">
                <input type="radio" name="rsvp" value="Masih Ragu"<?= $formRsvp === 'Masih Ragu' ? ' checked' : '' ?>>
                <span><i class="fa-solid fa-circle-question"></i> Masih Ragu</span>
              </label>
            </div>
          </div>
          <div class="baris-input">
            <label id="label-jumlah">Jumlah Tamu Hadir</label>
            <div class="dropdown" id="dropdown-jumlah">
              <input type="hidden" id="input-jumlah" name="jumlah" value="<?= h($formJumlah) ?>">
              <button type="button" class="dropdown-tombol" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="label-jumlah">
                <span class="dropdown-teks"><?= h($formJumlah) ?></span>
                <i class="fa-solid fa-chevron-down"></i>
              </button>
              <ul class="dropdown-daftar" role="listbox" aria-labelledby="label-jumlah">
<?php foreach (array_keys(PILIHAN_JUMLAH) as $opsiJumlah): $aktifJumlah = $opsiJumlah === $formJumlah; ?>
                <li class="dropdown-opsi<?= $aktifJumlah ? ' aktif' : '' ?>" role="option" tabindex="0" aria-selected="<?= $aktifJumlah ? 'true' : 'false' ?>" data-nilai="<?= h($opsiJumlah) ?>"><?= h($opsiJumlah) ?></li>
<?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="baris-input">
            <label for="input-pesan">Pesan &amp; Doa Restu</label>
            <textarea id="input-pesan" name="pesan" maxlength="1000" rows="4" placeholder="Tuliskan pesan & doa terbaik Anda..." required><?= h($formPesan) ?></textarea>
          </div>
          <button type="submit" class="tombol tombol-isi-mewah">
            <i class="fa-solid fa-paper-plane"></i> <?= $ucapanSaya !== null ? 'Perbarui Konfirmasi &amp; Ucapan' : 'Kirim Konfirmasi &amp; Ucapan' ?>
          </button>
        </form>
        <div class="daftar-ucapan-wrapper kaca animasi-muncul">
          <h3><i class="fa-solid fa-comments"></i> Ucapan &amp; Doa Tamu</h3>
          <div id="daftar-ucapan" class="daftar-ucapan"></div>
          <nav id="paginasi-ucapan" class="paginasi" aria-label="Halaman ucapan"></nav>
        </div>
      </section>
      <section class="bagian bagian-penutup">
        <div class="penutup-konten animasi-muncul">
          <div class="hiasan-bunga-top"><i class="fa-solid fa-leaf"></i></div>
          <p class="penutup-teks">Terima kasih atas doa, restu, dan kehadiran Anda. Semoga kebahagiaan ini turut menyertai kita semua.</p>
          <p class="penutup-salam">Wassalamu'alaikum Warahmatullahi Wabarakatuh</p>
          <p class="penutup-nama"><?= h($pria['panggilan']) ?> <span class="ampersand">&amp;</span> <?= h($wanita['panggilan']) ?></p>
        </div>
      </section>
      <footer class="catatan-bawah">
        <p>Dibuat dengan <i class="fa-solid fa-heart pulse"></i> untuk <?= h($namaPasangan) ?></p>
      </footer>
    </main>
  </div>
  <div id="lightbox-modal" class="lightbox-modal" role="dialog" aria-modal="true"
       aria-label="Galeri foto" aria-hidden="true" inert tabindex="-1">
    <button type="button" class="lightbox-close" id="lightbox-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
    <button type="button" class="lightbox-prev" id="lightbox-prev" aria-label="Foto sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
    <div class="lightbox-container">
      <img class="lightbox-img" id="lightbox-img" src="" alt="Foto galeri">
    </div>
    <button type="button" class="lightbox-next" id="lightbox-next" aria-label="Foto berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
  </div>
  <div id="toast-box" class="toast-box"></div>
</body>
</html>