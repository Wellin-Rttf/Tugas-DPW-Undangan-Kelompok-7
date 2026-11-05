<?php
require_once __DIR__ . '/koneksi.php';

$namaTamu = $_GET['to'] ?? '';
$namaTamu = is_string($namaTamu) ? strip_tags($namaTamu) : '';
$namaTamu = potong_teks(trim(preg_replace('/[\p{C}\s]+/u', ' ', $namaTamu)), 60);
$adaNamaTamu = $namaTamu !== '';
$actionForm = 'index.php' . ($adaNamaTamu ? '?to=' . rawurlencode($namaTamu) : '') . '#formulir-ucapan';
if (!$adaNamaTamu) $namaTamu = 'Tamu Undangan';

$pesanStatus = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim(ambil_post_string('nama'));
    $pesan  = trim(ambil_post_string('pesan'));
    $rsvp   = ambil_post_string('rsvp');
    $jumlah = PILIHAN_JUMLAH[ambil_post_string('jumlah')] ?? 0;
    $galat  = null;

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
            $stmt = $koneksi->prepare('INSERT INTO tb_ucapan (nama, rsvp, jumlah_tamu, pesan, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->bind_param('ssis', $nama, $rsvp, $jumlah, $pesan);
            $stmt->execute();
            $_SESSION['ucapan_terakhir'] = time();
        }
    }

    if ($galat === null) {
        $_SESSION['flash'] = 'Konfirmasi & ucapan Anda berhasil dikirim!';
        header('Location: ' . $actionForm, true, 303);
        exit;
    }
    $pesanStatus = $galat;
}

$dataUcapan = [];
if ($koneksi) {
    $hasil = $koneksi->query('SELECT nama, rsvp, jumlah_tamu, pesan, created_at FROM tb_ucapan WHERE tampil = 1 ORDER BY created_at DESC, id DESC LIMIT 500');
    while ($u = $hasil->fetch_assoc()) {
        $dataUcapan[] = [
            'nama'    => $u['nama'],
            'pesan'   => $u['pesan'],
            'rsvp'    => $u['rsvp'],
            'jumlah'  => label_jumlah($u['jumlah_tamu']),
            'tanggal' => tanggal_indo($u['created_at']),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#4E6151">
  <title>Undangan Pernikahan - Welling & Sheila</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">

  <script>
    window.DATA_UCAPAN = <?= json_encode($dataUcapan, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.PESAN_STATUS = <?= json_encode($pesanStatus, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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
        <h1 class="nama-sampul nama-sampul-script">Welling <span class="ampersand">&amp;</span> Sheila</h1>
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
            <span class="nama">Welling</span>
            <span class="simbol">&amp;</span>
            <span class="nama">Sheila</span>
          </div>

          <div class="badge-tanggal">
            <i class="fa-regular fa-calendar-check"></i> Minggu, 12 Desember 2027
          </div>

          <div class="foto-mempelai-wrapper">
            <img class="foto-mempelai" src="assets/images/2.jpg" alt="Welling dan Sheila">
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
          <article class="profil-mempelai-card kaca animasi-muncul">
            <div class="foto-box">
              <img loading="lazy" src="assets/images/sheila.jpg" alt="Sheila">
              <span class="badge-peran">Mempelai Wanita</span>
            </div>
            <div class="info-mempelai">
              <h3>Sheila</h3>
              <p class="anak-dari">Putri Pertama dari<br><strong>Bapak Budi &amp; Ibu Tia</strong></p>
              <a class="ig-mempelai" href="https://www.instagram.com/idiotic_isg14/" target="_blank">
                <i class="fa-brands fa-instagram"></i> @idiotic_isg14
              </a>
            </div>
          </article>

          <article class="profil-mempelai-card kaca animasi-muncul">
            <div class="foto-box">
              <img loading="lazy" src="assets/images/welling.jpg" alt="Welling">
              <span class="badge-peran">Mempelai Pria</span>
            </div>
            <div class="info-mempelai">
              <h3>Wellington</h3>
              <p class="anak-dari">Putra Pertama dari<br><strong>Bapak Agung &amp; Ibu Fabriani</strong></p>
              <a class="ig-mempelai" href="https://www.instagram.com/wellin_rttf/" target="_blank">
                <i class="fa-brands fa-instagram"></i> @wellin_rttf
              </a>
            </div>
          </article>
        </div>
      </section>

      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Acara Pernikahan</h2>

        <div class="acara-grid">
          <div class="acara-card kaca animasi-muncul">
            <div class="icon-acara"><i class="fa-solid fa-hands-praying"></i></div>
            <h3 class="judul-acara">Akad Nikah</h3>
            <div class="detail-acara-item"><i class="fa-regular fa-calendar"></i> Minggu, 12 Desember 2027</div>
            <div class="detail-acara-item"><i class="fa-regular fa-clock"></i> 08.00 - 10.00 WIB</div>
            <p class="tempat-acara">Masjid Agung Ar-Rahman</p>
            <p class="alamat-acara">Jl. Jend. Sudirman No.482, Jadirejo, Sukajadi, Pekanbaru</p>
            <a class="tombol tombol-outline" href="https://maps.app.goo.gl/KANwJdkuEMhPcTgu9" target="_blank">
              <i class="fa-solid fa-diamond-turn-right"></i> Petunjuk Lokasi
            </a>
          </div>

          <div class="acara-card highlight kaca animasi-muncul">
            <div class="icon-acara"><i class="fa-solid fa-champagne-glasses"></i></div>
            <h3 class="judul-acara">Resepsi</h3>
            <div class="detail-acara-item"><i class="fa-regular fa-calendar"></i> Minggu, 12 Desember 2027</div>
            <div class="detail-acara-item"><i class="fa-regular fa-clock"></i> 13.00 - 16.00 WIB</div>
            <p class="tempat-acara">Hotel Pangeran Pekanbaru</p>
            <p class="alamat-acara">Jl. Jend. Sudirman No. 371-373, Cinta Raja, Sail, Pekanbaru</p>
            <a class="tombol tombol-outline" href="https://maps.app.goo.gl/EVSfCEsKQkcV4xCF9" target="_blank">
              <i class="fa-solid fa-diamond-turn-right"></i> Petunjuk Lokasi
            </a>
          </div>
        </div>

        <div class="banner-acara animasi-muncul">
          <img loading="lazy" class="foto-acara" src="assets/images/8.jpg" alt="Tukar Cincin">
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

      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Galeri Kami</h2>
        <p class="petunjuk-galeri animasi-muncul"><i class="fa-solid fa-hand-pointer"></i> Klik foto untuk memperbesar &amp; melihat slide</p>

        <div class="galeri-foto-modern" id="galeri-foto">
          <div class="galeri-item item-besar animasi-muncul" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="lightbox-modal" aria-label="Perbesar foto galeri 1">
            <img loading="lazy" src="assets/images/3.jpg" alt="Galeri Momen 1">
            <div class="overlay-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
          </div>
          <div class="galeri-item animasi-muncul" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="lightbox-modal" aria-label="Perbesar foto galeri 2">
            <img loading="lazy" src="assets/images/4.jpg" alt="Galeri Momen 2">
            <div class="overlay-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
          </div>
          <div class="galeri-item animasi-muncul" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="lightbox-modal" aria-label="Perbesar foto galeri 3">
            <img loading="lazy" src="assets/images/5.jpg" alt="Galeri Momen 3">
            <div class="overlay-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
          </div>
          <div class="galeri-item animasi-muncul" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="lightbox-modal" aria-label="Perbesar foto galeri 4">
            <img loading="lazy" src="assets/images/6.jpg" alt="Galeri Momen 4">
            <div class="overlay-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
          </div>
          <div class="galeri-item animasi-muncul" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="lightbox-modal" aria-label="Perbesar foto galeri 5">
            <img loading="lazy" src="assets/images/7.jpg" alt="Galeri Momen 5">
            <div class="overlay-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
          </div>
        </div>
      </section>

      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Perjalanan Kami</h2>
        <div class="timeline">

          <div class="timeline-item left">
            <div class="timeline-dot animasi-muncul"><i class="fa-solid fa-heart"></i></div>
            <div class="timeline-content kaca animasi-muncul">
              <span class="timeline-year">2024</span>
              <h3>Awal Pertemuan</h3>
              <p>Kami bertemu pertama kali di sebuah acara kampus. Sapaan singkat yang sederhana tumbuh menjadi ketertarikan untuk saling mengenal lebih dekat.</p>
            </div>
          </div>

          <div class="timeline-item right">
            <div class="timeline-dot animasi-muncul"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <div class="timeline-content kaca animasi-muncul">
              <span class="timeline-year">2025</span>
              <h3>Menjalin Hubungan</h3>
              <p>Kami resmi berkomitmen untuk berjalan bersama. Saling mendukung dalam suka dan duka, serta tumbuh menjadi versi terbaik diri masing-masing.</p>
            </div>
          </div>

          <div class="timeline-item left">
            <div class="timeline-dot animasi-muncul"><i class="fa-solid fa-ring"></i></div>
            <div class="timeline-content kaca animasi-muncul">
              <span class="timeline-year">2026</span>
              <h3>Langkah Menuju Halal</h3>
              <p>Dengan restu kedua orang tua, kami mantap mengikat janji suci untuk melangkah ke jenjang pernikahan dan mengarungi masa depan bersama.</p>
            </div>
          </div>

        </div>
      </section>

      <section class="bagian">
        <h2 class="judul-bagian animasi-muncul">Hadiah Digital</h2>
        <p class="subjudul-bagian animasi-muncul">Doa restu Anda adalah hadiah terindah. Bagi yang ingin memberikan tanda kasih, dapat mengirimkannya melalui:</p>

        <button type="button" class="tombol tombol-std tombol-toggle animasi-muncul" id="tombol-hadiah" aria-expanded="false" aria-controls="panel-hadiah">
          <i class="fa-solid fa-gift"></i>
          <span id="teks-tombol-hadiah">Tampilkan Rekening</span>
          <i class="fa-solid fa-chevron-down ikon-panah"></i>
        </button>

        <div class="panel-hadiah" id="panel-hadiah" hidden>
         <div class="panel-hadiah-isi">
        <div class="amplop-grid">
          <div class="amplop-card kaca">
            <div class="logo-bank"><i class="fa-solid fa-building-columns"></i> Bank BCA</div>
            <p class="no-rek" id="rek-bca">1234 5678 90</p>
            <p class="pemilik-rek">a.n. Welling Simangunsong</p>
            <button class="tombol-salin" onclick="salinTeks('rek-bca', 'Nomor Rekening BCA Berhasil Disalin!')">
              <i class="fa-regular fa-copy"></i> Salin Rekening
            </button>
          </div>

          <div class="amplop-card kaca">
            <div class="logo-bank"><i class="fa-solid fa-building-columns"></i> Bank Mandiri</div>
            <p class="no-rek" id="rek-mandiri">0987 6543 21</p>
            <p class="pemilik-rek">a.n. Sheila Nurhaliza</p>
            <button class="tombol-salin" onclick="salinTeks('rek-mandiri', 'Nomor Rekening Mandiri Berhasil Disalin!')">
              <i class="fa-regular fa-copy"></i> Salin Rekening
            </button>
          </div>
        </div>
         </div>
        </div>
      </section>

      <section class="bagian bagian-ucapan">
        <h2 class="judul-bagian animasi-muncul">Konfirmasi &amp; Ucapan</h2>
        <p class="pengantar-ucapan subjudul-bagian animasi-muncul">Konfirmasi kehadiran Anda &amp; berikan ucapan hangat untuk kedua mempelai</p>

        <form id="formulir-ucapan" class="formulir-ucapan kaca animasi-muncul" action="<?= h($actionForm) ?>" method="post">
          <input type="text" name="website" class="jebakan" tabindex="-1" autocomplete="off" aria-hidden="true">
          <div class="baris-input">
            <label for="input-nama">Nama Lengkap</label>
            <input type="text" id="input-nama" name="nama" maxlength="100" placeholder="Masukkan nama Anda..." value="<?= $adaNamaTamu ? h($namaTamu) : '' ?>" required>
          </div>

          <div class="baris-input">
            <label>Konfirmasi Kehadiran</label>
            <div class="opsi-rsvp">
              <label class="radio-card">
                <input type="radio" name="rsvp" value="Hadir" checked>
                <span><i class="fa-solid fa-circle-check"></i> Hadir</span>
              </label>
              <label class="radio-card">
                <input type="radio" name="rsvp" value="Tidak Hadir">
                <span><i class="fa-solid fa-circle-xmark"></i> Tidak Hadir</span>
              </label>
              <label class="radio-card">
                <input type="radio" name="rsvp" value="Masih Ragu">
                <span><i class="fa-solid fa-circle-question"></i> Masih Ragu</span>
              </label>
            </div>
          </div>

          <div class="baris-input">
            <label id="label-jumlah">Jumlah Tamu Hadir</label>
            <div class="dropdown" id="dropdown-jumlah">
              <input type="hidden" id="input-jumlah" name="jumlah" value="1 Orang">
              <button type="button" class="dropdown-tombol" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="label-jumlah">
                <span class="dropdown-teks">1 Orang</span>
                <i class="fa-solid fa-chevron-down"></i>
              </button>
              <ul class="dropdown-daftar" role="listbox" aria-labelledby="label-jumlah">
                <li class="dropdown-opsi aktif" role="option" tabindex="0" aria-selected="true" data-nilai="1 Orang">1 Orang</li>
                <li class="dropdown-opsi" role="option" tabindex="0" aria-selected="false" data-nilai="2 Orang">2 Orang</li>
                <li class="dropdown-opsi" role="option" tabindex="0" aria-selected="false" data-nilai="3+ Orang">3+ Orang</li>
              </ul>
            </div>
          </div>

          <div class="baris-input">
            <label for="input-pesan">Pesan &amp; Doa Restu</label>
            <textarea id="input-pesan" name="pesan" maxlength="1000" rows="4" placeholder="Tuliskan pesan & doa terbaik Anda..." required></textarea>
          </div>

          <button type="submit" class="tombol tombol-isi-mewah">
            <i class="fa-solid fa-paper-plane"></i> Kirim Konfirmasi &amp; Ucapan
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
          <p class="penutup-nama">Welling <span class="ampersand">&amp;</span> Sheila</p>
        </div>
      </section>

      <footer class="catatan-bawah">
        <p>Dibuat dengan <i class="fa-solid fa-heart pulse"></i> untuk Welling &amp; Sheila</p>
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