<?php
/**
 * Konten undangan yang dapat diedit lewat dashboard.
 *
 * - konten_default()  : isi undangan persis seperti sebelum fitur edit ada.
 * - muat_konten()     : default digabung dengan data tersimpan di tb_pengaturan.
 * - bersihkan_konten(): memvalidasi & merapikan kiriman formulir dashboard.
 */

const DIR_UNGGAH      = 'assets/images/unggahan';
const BATAS_UNGGAH    = 8 * 1024 * 1024; // 8 MB (setelah dikompres di browser biasanya < 1 MB)
const MAKS_GALERI     = 24;
const MAKS_PERJALANAN = 12;
const MAKS_REKENING   = 6;

function konten_default(): array
{
    return [
        'mempelai' => [
            'pria' => [
                'panggilan' => 'Welling',
                'nama'      => 'Wellington',
                'anak_dari' => 'Putra Pertama dari',
                'orang_tua' => 'Bapak Agung & Ibu Fabriani',
                'ig_nama'   => '@wellin_rttf',
                'ig_link'   => 'https://www.instagram.com/wellin_rttf/',
                'foto'      => 'assets/images/welling.jpg',
            ],
            'wanita' => [
                'panggilan' => 'Sheila',
                'nama'      => 'Sheila',
                'anak_dari' => 'Putri Pertama dari',
                'orang_tua' => 'Bapak Budi & Ibu Tia',
                'ig_nama'   => '@idiotic_isg14',
                'ig_link'   => 'https://www.instagram.com/idiotic_isg14/',
                'foto'      => 'assets/images/sheila.jpg',
            ],
        ],
        'acara' => [
            'tanggal' => '2027-12-12',
            'akad' => [
                'judul'           => 'Akad Nikah',
                'judul_kalender'  => 'Akad Nikah',
                'jam_mulai'       => '08:00',
                'jam_selesai'     => '10:00',
                'tempat'          => 'Masjid Agung Ar-Rahman',
                'alamat'          => 'Jl. Jend. Sudirman No.482, Jadirejo, Sukajadi, Pekanbaru',
                'link_maps'       => 'https://maps.app.goo.gl/KANwJdkuEMhPcTgu9',
                'lokasi_kalender' => 'Masjid Agung Ar-Rahman, Pekanbaru',
            ],
            'resepsi' => [
                'judul'           => 'Resepsi',
                'judul_kalender'  => 'Resepsi Pernikahan',
                'jam_mulai'       => '13:00',
                'jam_selesai'     => '16:00',
                'tempat'          => 'Hotel Pangeran Pekanbaru',
                'alamat'          => 'Jl. Jend. Sudirman No. 371-373, Cinta Raja, Sail, Pekanbaru',
                'link_maps'       => 'https://maps.app.goo.gl/EVSfCEsKQkcV4xCF9',
                'lokasi_kalender' => 'Hotel Pangeran, Pekanbaru',
            ],
        ],
        'foto' => [
            'latar'    => 'assets/images/1.jpg',
            'pasangan' => 'assets/images/2.jpg',
            'acara'    => 'assets/images/8.jpg',
        ],
        'galeri' => [
            'assets/images/3.jpg',
            'assets/images/4.jpg',
            'assets/images/5.jpg',
            'assets/images/6.jpg',
            'assets/images/7.jpg',
        ],
        'perjalanan' => [
            [
                'tahun' => '2024',
                'judul' => 'Awal Pertemuan',
                'teks'  => 'Kami bertemu pertama kali di sebuah acara kampus. Sapaan singkat yang sederhana tumbuh menjadi ketertarikan untuk saling mengenal lebih dekat.',
                'ikon'  => 'fa-heart',
            ],
            [
                'tahun' => '2025',
                'judul' => 'Menjalin Hubungan',
                'teks'  => 'Kami resmi berkomitmen untuk berjalan bersama. Saling mendukung dalam suka dan duka, serta tumbuh menjadi versi terbaik diri masing-masing.',
                'ikon'  => 'fa-hand-holding-heart',
            ],
            [
                'tahun' => '2026',
                'judul' => 'Langkah Menuju Halal',
                'teks'  => 'Dengan restu kedua orang tua, kami mantap mengikat janji suci untuk melangkah ke jenjang pernikahan dan mengarungi masa depan bersama.',
                'ikon'  => 'fa-ring',
            ],
        ],
        'hadiah' => [
            'tampil'    => true,
            'pengantar' => 'Doa restu Anda adalah hadiah terindah. Bagi yang ingin memberikan tanda kasih, dapat mengirimkannya melalui:',
            'rekening'  => [
                ['bank' => 'Bank BCA',     'nomor' => '1234 5678 90', 'atas_nama' => 'Welling Simangunsong'],
                ['bank' => 'Bank Mandiri', 'nomor' => '0987 6543 21', 'atas_nama' => 'Sheila Medina'],
            ],
        ],
    ];
}

/** Ikon yang boleh dipilih untuk perjalanan kami (kelas Font Awesome => label). */
function daftar_ikon(): array
{
    return [
        'fa-heart'              => 'Hati',
        'fa-hand-holding-heart' => 'Tangan & hati',
        'fa-ring'               => 'Cincin',
        'fa-star'               => 'Bintang',
        'fa-handshake'          => 'Jabat tangan',
        'fa-comments'           => 'Obrolan',
        'fa-plane'              => 'Pesawat',
        'fa-house'              => 'Rumah',
        'fa-graduation-cap'     => 'Toga',
        'fa-cake-candles'       => 'Kue ulang tahun',
        'fa-gift'               => 'Hadiah',
        'fa-calendar-check'     => 'Kalender',
    ];
}

/* ------------------------------------------------------------------ */
/* Muat & simpan                                                       */
/* ------------------------------------------------------------------ */

function konten_gabung(array $dasar, array $baru): array
{
    foreach ($baru as $kunci => $nilai) {
        if (is_array($nilai) && isset($dasar[$kunci]) && is_array($dasar[$kunci])
            && !array_is_list($nilai) && !array_is_list($dasar[$kunci])) {
            $dasar[$kunci] = konten_gabung($dasar[$kunci], $nilai);
        } else {
            $dasar[$kunci] = $nilai;
        }
    }
    return $dasar;
}

function muat_konten(?mysqli $db): array
{
    $konten = konten_default();
    if (!$db) {
        return $konten;
    }
    try {
        $hasil = $db->query("SELECT nilai FROM tb_pengaturan WHERE kunci = 'konten'");
        $baris = $hasil ? $hasil->fetch_assoc() : null;
        if ($baris) {
            $tersimpan = json_decode($baris['nilai'], true);
            if (is_array($tersimpan)) {
                $konten = konten_gabung($konten, $tersimpan);
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Gagal memuat konten: ' . $e->getMessage());
    }
    return $konten;
}

function simpan_konten(mysqli $db, array $konten): void
{
    $json = json_encode($konten, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $db->prepare("INSERT INTO tb_pengaturan (kunci, nilai) VALUES ('konten', ?)
                          ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    $stmt->bind_param('s', $json);
    $stmt->execute();
}

/* ------------------------------------------------------------------ */
/* Pembersihan input                                                   */
/* ------------------------------------------------------------------ */

function k_array($nilai): array
{
    return is_array($nilai) ? $nilai : [];
}

function k_teks($nilai, int $maks, bool $banyakBaris = false): string
{
    if (!is_string($nilai)) {
        return '';
    }
    $nilai = str_replace("\r\n", "\n", $nilai);
    $nilai = preg_replace($banyakBaris ? '/[^\P{C}\n]+/u' : '/[\p{C}]+/u', ' ', $nilai) ?? '';
    $nilai = $banyakBaris ? preg_replace('/[ \t]+/u', ' ', $nilai) : preg_replace('/\s+/u', ' ', $nilai);
    return mb_substr(trim((string) $nilai), 0, $maks);
}

function k_url($nilai): string
{
    $nilai = is_string($nilai) ? trim($nilai) : '';
    if ($nilai === '' || mb_strlen($nilai) > 300) {
        return '';
    }
    if (!filter_var($nilai, FILTER_VALIDATE_URL)) {
        return '';
    }
    $skema = strtolower((string) parse_url($nilai, PHP_URL_SCHEME));
    return in_array($skema, ['http', 'https'], true) ? $nilai : '';
}

function k_jam($nilai): string
{
    $nilai = is_string($nilai) ? trim($nilai) : '';
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $nilai) ? $nilai : '';
}

function k_tanggal($nilai): string
{
    $nilai = is_string($nilai) ? trim($nilai) : '';
    $d = DateTime::createFromFormat('!Y-m-d', $nilai);
    $galat = DateTime::getLastErrors();
    $bersih = !$galat || ($galat['warning_count'] === 0 && $galat['error_count'] === 0);
    return ($d && $bersih && $d->format('Y-m-d') === $nilai) ? $nilai : '';
}

function path_foto_valid($path): bool
{
    return is_string($path)
        && preg_match('#^assets/images/(unggahan/)?[A-Za-z0-9_.-]+\.(jpe?g|png|webp|gif)$#i', $path) === 1
        && strpos($path, '..') === false;
}

/**
 * Merapikan kiriman formulir (biasanya $_POST) menjadi struktur konten penuh.
 * Nilai yang tidak valid memakai nilai $lama, dan dicatat di $peringatan.
 */
function bersihkan_konten(array $in, array $lama, ?array &$peringatan = null): array
{
    $peringatan = [];
    $baru = $lama;

    $teksWajib = function ($nilai, int $maks, string $lamaNilai, string $label) use (&$peringatan): string {
        $t = k_teks($nilai, $maks);
        if ($t === '') {
            $peringatan[] = "$label tidak boleh kosong (nilai sebelumnya dipertahankan).";
            return $lamaNilai;
        }
        return $t;
    };
    $foto = function ($nilai, string $lamaNilai, string $label) use (&$peringatan): string {
        if (is_string($nilai) && trim($nilai) !== '') {
            $nilai = trim($nilai);
            if (path_foto_valid($nilai)) {
                return $nilai;
            }
            $peringatan[] = "$label tidak valid (foto sebelumnya dipertahankan).";
        }
        return $lamaNilai;
    };

    // --- Mempelai ---
    foreach (['pria' => 'pria', 'wanita' => 'wanita'] as $kunci => $etiket) {
        $inp  = k_array(k_array($in['mempelai'] ?? null)[$kunci] ?? null);
        $lm   = $lama['mempelai'][$kunci];

        $baru['mempelai'][$kunci] = [
            'panggilan' => $teksWajib($inp['panggilan'] ?? '', 40, $lm['panggilan'], "Nama panggilan mempelai $etiket"),
            'nama'      => $teksWajib($inp['nama'] ?? '', 60, $lm['nama'], "Nama lengkap mempelai $etiket"),
            'anak_dari' => k_teks($inp['anak_dari'] ?? '', 60),
            'orang_tua' => k_teks($inp['orang_tua'] ?? '', 120),
            'ig_nama'   => k_teks($inp['ig_nama'] ?? '', 60),
            'ig_link'   => k_url($inp['ig_link'] ?? ''),
            'foto'      => $foto($inp['foto'] ?? '', $lm['foto'], "Foto mempelai $etiket"),
        ];
        if (trim((string) ($inp['ig_link'] ?? '')) !== '' && $baru['mempelai'][$kunci]['ig_link'] === '') {
            $peringatan[] = "Link Instagram mempelai $etiket harus diawali http:// atau https:// dan dikosongkan.";
        }
    }

    // --- Acara ---
    $inpAcara = k_array($in['acara'] ?? null);
    $tanggal  = k_tanggal($inpAcara['tanggal'] ?? '');
    if ($tanggal === '') {
        $peringatan[] = 'Tanggal pernikahan tidak valid (tanggal sebelumnya dipertahankan).';
        $tanggal = $lama['acara']['tanggal'];
    }
    $baru['acara']['tanggal'] = $tanggal;

    foreach (['akad', 'resepsi'] as $jenis) {
        $inp = k_array($inpAcara[$jenis] ?? null);
        $lm  = $lama['acara'][$jenis];

        $mulai   = k_jam($inp['jam_mulai'] ?? '');
        $selesai = k_jam($inp['jam_selesai'] ?? '');
        if ($mulai === '') {
            $peringatan[] = "Jam mulai $jenis tidak valid (nilai sebelumnya dipertahankan).";
            $mulai = $lm['jam_mulai'];
        }
        if ($selesai === '') {
            $peringatan[] = "Jam selesai $jenis tidak valid (nilai sebelumnya dipertahankan).";
            $selesai = $lm['jam_selesai'];
        }

        $baru['acara'][$jenis] = [
            'judul'           => $teksWajib($inp['judul'] ?? '', 60, $lm['judul'], "Judul acara $jenis"),
            'judul_kalender'  => $teksWajib($inp['judul_kalender'] ?? '', 80, $lm['judul_kalender'], "Judul kalender $jenis"),
            'jam_mulai'       => $mulai,
            'jam_selesai'     => $selesai,
            'tempat'          => $teksWajib($inp['tempat'] ?? '', 120, $lm['tempat'], "Nama tempat $jenis"),
            'alamat'          => k_teks($inp['alamat'] ?? '', 250),
            'link_maps'       => k_url($inp['link_maps'] ?? ''),
            'lokasi_kalender' => k_teks($inp['lokasi_kalender'] ?? '', 150),
        ];
        if (trim((string) ($inp['link_maps'] ?? '')) !== '' && $baru['acara'][$jenis]['link_maps'] === '') {
            $peringatan[] = "Link lokasi $jenis harus diawali http:// atau https:// dan dikosongkan.";
        }
    }

    // --- Foto utama ---
    $inpFoto = k_array($in['foto'] ?? null);
    $baru['foto'] = [
        'latar'    => $foto($inpFoto['latar'] ?? '', $lama['foto']['latar'], 'Foto latar'),
        'pasangan' => $foto($inpFoto['pasangan'] ?? '', $lama['foto']['pasangan'], 'Foto pasangan'),
        'acara'    => $foto($inpFoto['acara'] ?? '', $lama['foto']['acara'], 'Foto banner acara'),
    ];

    // --- Galeri ---
    $galeri = [];
    foreach (array_slice(array_values(k_array($in['galeri'] ?? null)), 0, MAKS_GALERI) as $path) {
        $path = is_string($path) ? trim($path) : '';
        if ($path === '') {
            continue;
        }
        if (path_foto_valid($path)) {
            $galeri[] = $path;
        } else {
            $peringatan[] = 'Satu foto galeri tidak valid dan dilewati.';
        }
    }
    $baru['galeri'] = $galeri;

    // --- Perjalanan ---
    $ikonOk = daftar_ikon();
    $perjalanan = [];
    foreach (array_slice(array_values(k_array($in['perjalanan'] ?? null)), 0, MAKS_PERJALANAN) as $item) {
        $item  = k_array($item);
        $tahun = k_teks($item['tahun'] ?? '', 10);
        $judul = k_teks($item['judul'] ?? '', 80);
        $teks  = k_teks($item['teks'] ?? '', 500, true);
        if ($tahun === '' && $judul === '' && $teks === '') {
            continue;
        }
        $ikon = is_string($item['ikon'] ?? null) && isset($ikonOk[$item['ikon']]) ? $item['ikon'] : 'fa-heart';
        $perjalanan[] = ['tahun' => $tahun, 'judul' => $judul, 'teks' => $teks, 'ikon' => $ikon];
    }
    $baru['perjalanan'] = $perjalanan;

    // --- Hadiah digital ---
    $inpHadiah = k_array($in['hadiah'] ?? null);
    $rekening = [];
    foreach (array_slice(array_values(k_array($inpHadiah['rekening'] ?? null)), 0, MAKS_REKENING) as $r) {
        $r = k_array($r);
        $nomor = k_teks($r['nomor'] ?? '', 40);
        $bank  = k_teks($r['bank'] ?? '', 40);
        if ($nomor === '' && $bank === '') {
            continue;
        }
        if ($nomor === '' || $bank === '') {
            $peringatan[] = 'Rekening dengan bank atau nomor kosong dilewati.';
            continue;
        }
        $atasNama = preg_replace('/^\s*a\.?\s*n\.?\s+/iu', '', k_teks($r['atas_nama'] ?? '', 80));
        $rekening[] = ['bank' => $bank, 'nomor' => $nomor, 'atas_nama' => (string) $atasNama];
    }
    $baru['hadiah'] = [
        'tampil'    => !empty($inpHadiah['tampil']) && $inpHadiah['tampil'] !== '0',
        'pengantar' => k_teks($inpHadiah['pengantar'] ?? '', 400),
        'rekening'  => $rekening,
    ];

    return $baru;
}

/* ------------------------------------------------------------------ */
/* Format tampilan                                                     */
/* ------------------------------------------------------------------ */

function tanggal_panjang(string $ymd): string
{
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($ymd . ' 12:00:00');
    if ($t === false) {
        return $ymd;
    }
    return $hari[(int) date('w', $t)] . ', ' . date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function rentang_jam(string $mulai, string $selesai): string
{
    return str_replace(':', '.', $mulai) . ' - ' . str_replace(':', '.', $selesai) . ' WIB';
}

function nama_pasangan(array $konten): string
{
    return $konten['mempelai']['pria']['panggilan'] . ' & ' . $konten['mempelai']['wanita']['panggilan'];
}

/** Data acara untuk JavaScript (hitung mundur & tombol kalender). */
function data_acara_js(array $konten): array
{
    $tgl = $konten['acara']['tanggal'];
    $ymd = str_replace('-', '', $tgl);
    $hasil = [
        'nama'  => nama_pasangan($konten),
        'mulai' => $tgl . 'T' . $konten['acara']['akad']['jam_mulai'] . ':00+07:00',
    ];
    foreach (['akad', 'resepsi'] as $jenis) {
        $a = $konten['acara'][$jenis];
        $hasil[$jenis] = [
            'judul'   => $a['judul_kalender'] . ' ' . nama_pasangan($konten),
            'mulai'   => $ymd . 'T' . str_replace(':', '', $a['jam_mulai']) . '00',
            'selesai' => $ymd . 'T' . str_replace(':', '', $a['jam_selesai']) . '00',
            'lokasi'  => $a['lokasi_kalender'] !== '' ? $a['lokasi_kalender'] : $a['tempat'],
        ];
    }
    return $hasil;
}

/* ------------------------------------------------------------------ */
/* Foto                                                                */
/* ------------------------------------------------------------------ */

/** Menyimpan satu foto unggahan. Mengembalikan ['ok'=>bool, 'path'=>..., 'pesan'=>...]. */
function proses_unggah_foto(?array $berkas): array
{
    if (!$berkas || !isset($berkas['error']) || is_array($berkas['error'])) {
        return ['ok' => false, 'pesan' => 'Berkas tidak valid.'];
    }
    if ($berkas['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'pesan' => 'Tidak ada berkas yang dipilih.'];
    }
    if ($berkas['error'] === UPLOAD_ERR_INI_SIZE || $berkas['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'pesan' => 'Ukuran foto melebihi batas server. Gunakan foto yang lebih kecil.'];
    }
    if ($berkas['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'pesan' => 'Foto gagal diunggah. Coba lagi.'];
    }
    if ($berkas['size'] > BATAS_UNGGAH) {
        return ['ok' => false, 'pesan' => 'Ukuran foto maksimal 8 MB.'];
    }
    if (!is_uploaded_file($berkas['tmp_name'])) {
        return ['ok' => false, 'pesan' => 'Berkas tidak valid.'];
    }

    $info = @getimagesize($berkas['tmp_name']);
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($ekstensi[$info[2]])) {
        return ['ok' => false, 'pesan' => 'Format foto harus JPG, PNG, atau WebP.'];
    }

    $folder = __DIR__ . '/' . DIR_UNGGAH;
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        return ['ok' => false, 'pesan' => 'Folder unggahan tidak dapat dibuat di server.'];
    }
    if (!is_file($folder . '/index.html')) {
        @file_put_contents($folder . '/index.html', '');
    }

    $nama = 'foto_' . bin2hex(random_bytes(8)) . '.' . $ekstensi[$info[2]];
    if (!move_uploaded_file($berkas['tmp_name'], $folder . '/' . $nama)) {
        return ['ok' => false, 'pesan' => 'Foto gagal disimpan di server.'];
    }
    @chmod($folder . '/' . $nama, 0644);

    return ['ok' => true, 'path' => DIR_UNGGAH . '/' . $nama];
}

/** Menghapus foto unggahan yang tidak lagi dipakai (dan sudah lebih dari 1 jam). */
function bersihkan_foto_tak_terpakai(array $konten): void
{
    $dipakai = [];
    array_walk_recursive($konten, function ($nilai) use (&$dipakai) {
        if (is_string($nilai) && strpos($nilai, DIR_UNGGAH . '/') === 0) {
            $dipakai[basename($nilai)] = true;
        }
    });

    foreach (glob(__DIR__ . '/' . DIR_UNGGAH . '/foto_*') ?: [] as $berkas) {
        if (!isset($dipakai[basename($berkas)]) && is_file($berkas) && filemtime($berkas) < time() - 3600) {
            @unlink($berkas);
        }
    }
}
