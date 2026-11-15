<?php
const TAB_DASHBOARD = ['statistik', 'konten', 'tamu', 'ucapan', 'akun'];
const PER_HALAMAN = 20;
const MAKS_BARIS_CSV = 2000;
const KUNCI_URL = ['qt', 'ft', 'ht', 'qu', 'fu', 'hu'];

function atur_notif(string $pesan, string $jenis = 'sukses', ?array $undo = null): void
{
    $_SESSION['notif'] = ['pesan' => $pesan, 'jenis' => $jenis, 'undo' => $undo !== null];
    if ($undo !== null) {
        $_SESSION['undo'] = $undo + ['waktu' => time()];
    }
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

function kirim_csv(string $nama, array $judul, iterable $baris): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nama . '-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $judul, ';', '"', '\\');
    foreach ($baris as $b) {
        fputcsv($out, $b, ';', '"', '\\');
    }
    fclose($out);
    exit;
}

function get_string(string $nama): string
{
    $nilai = $_GET[$nama] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
}

function ids_post(): array
{
    $mentah = $_POST['ids'] ?? ($_POST['id'] ?? []);
    $ids = [];
    foreach ((array) $mentah as $nilai) {
        $id = filter_var($nilai, FILTER_VALIDATE_INT);
        if ($id !== false && $id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_slice(array_values($ids), 0, 500);
}

function tanda_tanya(array $ids): string
{
    return implode(',', array_fill(0, count($ids), '?'));
}

function tipe_int(array $ids): string
{
    return str_repeat('i', count($ids));
}

function bangun_where(array $kondisi): string
{
    return $kondisi ? 'WHERE ' . implode(' AND ', $kondisi) : '';
}

function pola_like(string $teks): string
{
    return '%' . addcslashes($teks, '%_\\') . '%';
}

function url_dashboard(array $ubah, string $tab): string
{
    $q = array_merge(array_filter(array_intersect_key($_GET, array_flip(KUNCI_URL)), 'is_string'), $ubah);
    foreach ($q as $kunci => $nilai) {
        if ($nilai === '' || $nilai === 'semua' || (in_array($kunci, ['ht', 'hu'], true) && (int) $nilai <= 1)) {
            unset($q[$kunci]);
        }
    }
    return 'dashboard.php' . ($q ? '?' . http_build_query($q) : '') . '#tab-' . $tab;
}

function halaman_data(mysqli $db, string $dari, string $kolom, string $where, string $tipe, array $param, string $urut, int $halaman): array
{
    $total = (int) jalankan($db, "SELECT COUNT(*) FROM $dari $where", $tipe, $param)->get_result()->fetch_row()[0];
    $maks = max(1, (int) ceil($total / PER_HALAMAN));
    $halaman = max(1, min($halaman, $maks));
    $baris = jalankan($db, "SELECT $kolom FROM $dari $where ORDER BY $urut LIMIT ? OFFSET ?", $tipe . 'ii',
        [...$param, PER_HALAMAN, ($halaman - 1) * PER_HALAMAN])->get_result()->fetch_all(MYSQLI_ASSOC);
    return [$baris, $total, $halaman, $maks];
}

function render_paginasi(int $halaman, int $maks, string $kunci, string $tab): void
{
    if ($maks <= 1) {
        return;
    }
    $butir = fn(int $h, string $isi, string $aria, string $kelas = '', bool $mati = false): string => $mati
        ? '<span class="halaman mati" aria-hidden="true">' . $isi . '</span>'
        : '<a class="halaman ' . $kelas . '" href="' . h(url_dashboard([$kunci => $h], $tab)) . '" aria-label="' . h($aria) . '" data-skeleton>' . $isi . '</a>';

    $awal = max(1, min($halaman - 2, $maks - 4));
    $akhir = min($maks, $awal + 4);
    echo '<nav class="paginasi-dash" aria-label="Halaman data">';
    echo $butir($halaman - 1, '<i class="fa-solid fa-chevron-left"></i>', 'Sebelumnya', '', $halaman === 1);
    for ($i = $awal; $i <= $akhir; $i++) {
        echo $i === $halaman
            ? '<span class="halaman aktif" aria-current="page">' . $i . '</span>'
            : $butir($i, (string) $i, 'Halaman ' . $i);
    }
    echo $butir($halaman + 1, '<i class="fa-solid fa-chevron-right"></i>', 'Berikutnya', '', $halaman === $maks);
    echo '<span class="halaman-info">Hal. ' . $halaman . ' dari ' . $maks . '</span></nav>';
}

function waktu_relatif(string $waktu): string
{
    $selisih = time() - strtotime($waktu);
    return match (true) {
        $selisih < 60 => 'baru saja',
        $selisih < 3600 => floor($selisih / 60) . ' menit lalu',
        $selisih < 86400 => floor($selisih / 3600) . ' jam lalu',
        $selisih < 604800 => floor($selisih / 86400) . ' hari lalu',
        default => tanggal_indo($waktu),
    };
}

function tanggal_waktu_indo(string $waktu): string
{
    return tanggal_indo($waktu) . ', ' . date('H.i', strtotime($waktu));
}

function ikon_log(string $jenis): string
{
    return ['ucapan' => 'fa-comment-dots', 'tamu' => 'fa-user-group', 'konten' => 'fa-pen-ruler', 'akun' => 'fa-user-gear'][$jenis] ?? 'fa-circle-info';
}

function pesan_wa(string $nama, string $link, array $konten): string
{
    return "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n"
         . "Kepada Yth. {$nama},\n"
         . 'Dengan penuh rasa syukur, kami mengundang Bapak/Ibu/Saudara/i untuk hadir di pernikahan '
         . nama_pasangan($konten) . ' pada ' . tanggal_panjang($konten['acara']['tanggal']) . ".\n\n"
         . "Detail undangan dapat dilihat pada tautan berikut:\n{$link}\n\n"
         . 'Merupakan kehormatan bagi kami apabila Anda berkenan hadir. Terima kasih.';
}

function nama_tamu_ada(mysqli $db, string $nama, int $kecuali = 0): bool
{
    return jalankan($db, 'SELECT 1 FROM tb_tamu WHERE nama = ? AND id <> ? LIMIT 1', 'si', [$nama, $kecuali])->get_result()->num_rows > 0;
}

function ke_utf8(string $isi, ?string &$asal = null): string
{
    if (str_starts_with($isi, "\xEF\xBB\xBF")) {
        return substr($isi, 3);
    }
    foreach (["\xFF\xFE" => 'UTF-16LE', "\xFE\xFF" => 'UTF-16BE'] as $bom => $encoding) {
        if (str_starts_with($isi, $bom)) {
            $asal = $encoding;
            return mb_convert_encoding(substr($isi, 2), 'UTF-8', $encoding);
        }
    }
    if (mb_check_encoding($isi, 'UTF-8')) {
        return $isi;
    }
    $asal = 'Windows-1252';
    return mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
}

function ekspor_tamu(mysqli $db, array $ids = []): never
{
    $where = $ids ? 'WHERE t.id IN (' . tanda_tanya($ids) . ')' : '';
    $hasil = jalankan($db, "SELECT t.id, t.nama, u.rsvp, u.jumlah_tamu, u.pesan
                            FROM tb_tamu t LEFT JOIN tb_ucapan u ON u.tamu_id = t.id $where ORDER BY t.id", tipe_int($ids), $ids)->get_result();
    $baris = (function () use ($hasil) {
        $base = url_undangan();
        foreach ($hasil as $b) {
            yield [
                $b['id'],
                csv_aman($b['nama']),
                $base . '?to=' . $b['id'],
                $b['rsvp'] ?? 'Belum merespons',
                $b['rsvp'] ? label_jumlah($b['jumlah_tamu']) : '',
                csv_aman((string) ($b['pesan'] ?? '')),
            ];
        }
    })();
    kirim_csv('daftar-tamu', ['Nomor', 'Nama', 'Link Undangan', 'Status Kehadiran', 'Jumlah Tamu', 'Pesan & Doa'], $baris);
}

function ekspor_ucapan(mysqli $db, array $ids): never
{
    $hasil = jalankan($db, 'SELECT nama, rsvp, jumlah_tamu, pesan, tampil, created_at FROM tb_ucapan WHERE id IN (' . tanda_tanya($ids) . ') ORDER BY created_at DESC',
        tipe_int($ids), $ids)->get_result();
    $baris = (function () use ($hasil) {
        foreach ($hasil as $b) {
            yield [csv_aman($b['nama']), $b['rsvp'], label_jumlah($b['jumlah_tamu']), csv_aman($b['pesan']), $b['tampil'] ? 'Ya' : 'Tidak', $b['created_at']];
        }
    })();
    kirim_csv('ucapan', ['Nama', 'Status Kehadiran', 'Jumlah Tamu', 'Pesan & Doa', 'Ditampilkan', 'Tanggal'], $baris);
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
            <button type="button" class="tombol-kecil gagang" data-gagang aria-label="Geser untuk mengubah urutan" title="Geser atau tekan panah atas/bawah"><i class="fa-solid fa-grip-vertical"></i></button>
            <button type="button" class="tombol-kecil" data-aksi="ganti" title="Ganti foto"><i class="fa-solid fa-image"></i> Ganti</button>
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
            <button type="button" class="tombol-kecil gagang" data-gagang aria-label="Geser untuk mengubah urutan" title="Geser atau tekan panah atas/bawah"><i class="fa-solid fa-grip-vertical"></i> Urutkan</button>
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
            <button type="button" class="tombol-kecil gagang" data-gagang aria-label="Geser untuk mengubah urutan" title="Geser atau tekan panah atas/bawah"><i class="fa-solid fa-grip-vertical"></i> Urutkan</button>
            <button type="button" class="tombol-kecil bahaya" data-aksi="hapus"><i class="fa-solid fa-trash"></i> Hapus</button>
        </div>
    </div>
    <?php
}

function aksi_simpan_konten(mysqli $db): string
{
    $lama = muat_konten($db);
    $baru = bersihkan_konten($_POST, $lama, $peringatan);
    simpan_konten($db, $baru);
    bersihkan_foto_tak_terpakai($baru);
    $pesan = 'Konten undangan berhasil disimpan.';
    if ($peringatan) {
        $pesan .= ' Catatan: ' . implode(' ', array_slice(array_unique($peringatan), 0, 4));
    }
    catat_log($db, 'konten', 'Konten undangan diperbarui');
    atur_notif($pesan, $peringatan ? 'galat' : 'sukses');
    return 'konten';
}

function aksi_tamu_tambah(mysqli $db): string
{
    $nama = bersihkan_nama_tamu(ambil_post_string('nama'));
    if ($nama === '') {
        atur_notif('Nama tamu tidak boleh kosong.', 'galat');
    } elseif (nama_tamu_ada($db, $nama)) {
        atur_notif('Tamu "' . $nama . '" sudah ada di daftar.', 'galat');
    } else {
        jalankan($db, 'INSERT INTO tb_tamu (nama) VALUES (?)', 's', [$nama]);
        catat_log($db, 'tamu', 'Tamu ditambahkan: ' . $nama);
        atur_notif('Tamu "' . $nama . '" ditambahkan dengan nomor ' . $db->insert_id . '.');
    }
    return 'tamu';
}

function aksi_tamu_ubah(mysqli $db): string
{
    $id = ids_post()[0] ?? 0;
    $nama = bersihkan_nama_tamu(ambil_post_string('nama'));
    if ($id < 1 || $nama === '') {
        atur_notif('Data tamu tidak valid.', 'galat');
    } elseif (nama_tamu_ada($db, $nama, $id)) {
        atur_notif('Tamu "' . $nama . '" sudah ada di daftar.', 'galat');
    } else {
        jalankan($db, 'UPDATE tb_tamu SET nama = ? WHERE id = ?', 'si', [$nama, $id]);
        catat_log($db, 'tamu', 'Nama tamu nomor ' . $id . ' diubah menjadi ' . $nama);
        atur_notif('Nama tamu nomor ' . $id . ' diperbarui.');
    }
    return 'tamu';
}

function aksi_tamu_hapus(mysqli $db): string
{
    $ids = ids_post();
    if (!$ids) {
        atur_notif('Pilih tamu terlebih dahulu.', 'galat');
        return 'tamu';
    }
    $in = tanda_tanya($ids);
    $tipe = tipe_int($ids);
    $tamu = jalankan($db, "SELECT id, nama FROM tb_tamu WHERE id IN ($in)", $tipe, $ids)->get_result()->fetch_all(MYSQLI_ASSOC);
    $relasi = jalankan($db, "SELECT id, tamu_id FROM tb_ucapan WHERE tamu_id IN ($in)", $tipe, $ids)->get_result()->fetch_all(MYSQLI_ASSOC);
    jalankan($db, "UPDATE tb_ucapan SET tamu_id = NULL WHERE tamu_id IN ($in)", $tipe, $ids);
    jalankan($db, "DELETE FROM tb_tamu WHERE id IN ($in)", $tipe, $ids);
    $jumlah = count($tamu);
    catat_log($db, 'tamu', $jumlah . ' tamu dihapus');
    atur_notif($jumlah . ' tamu dihapus. Ucapan yang sudah dikirim tetap tersimpan.', 'sukses', ['jenis' => 'tamu', 'tamu' => $tamu, 'relasi' => $relasi]);
    return 'tamu';
}

function aksi_tamu_impor(mysqli $db): string
{
    $berkas = $_FILES['csv'] ?? null;
    if (!$berkas || ($berkas['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($berkas['tmp_name'])) {
        atur_notif('Pilih berkas CSV terlebih dahulu.', 'galat');
        return 'tamu';
    }
    if ($berkas['size'] > 1024 * 1024) {
        atur_notif('Ukuran berkas CSV maksimal 1 MB.', 'galat');
        return 'tamu';
    }

    $isi = ke_utf8((string) file_get_contents($berkas['tmp_name']), $encodingAsal);
    $baris = array_values(array_filter(preg_split('/\R/u', $isi) ?: [], fn($b) => trim($b) !== ''));
    if (!$baris) {
        atur_notif('Berkas CSV kosong.', 'galat');
        return 'tamu';
    }

    $kandidat = [';' => substr_count($baris[0], ';'), ',' => substr_count($baris[0], ','), "\t" => substr_count($baris[0], "\t")];
    arsort($kandidat);
    $pemisah = key($kandidat);
    $terpotong = max(0, count($baris) - (MAKS_BARIS_CSV + 1));
    $rows = array_map(fn($b) => str_getcsv($b, $pemisah, '"', '\\'), array_slice($baris, 0, MAKS_BARIS_CSV + 1));

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
    foreach ($db->query('SELECT nama FROM tb_tamu') as $b) {
        $ada[mb_strtolower($b['nama'])] = true;
    }

    $tambah = 0;
    $lewat = 0;
    $db->begin_transaction();
    $stmt = $db->prepare('INSERT INTO tb_tamu (nama) VALUES (?)');
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
    $db->commit();

    $pesan = "Impor selesai: $tambah tamu ditambahkan";
    if ($lewat > 0) {
        $pesan .= ", $lewat dilewati (nama kosong atau sudah ada)";
    }
    $pesan .= '.';
    if ($terpotong > 0) {
        $pesan .= ' Maksimal ' . MAKS_BARIS_CSV . " baris diproses, $terpotong baris dilewati.";
    }
    if ($encodingAsal) {
        $pesan .= " Encoding $encodingAsal dikonversi ke UTF-8.";
    }
    catat_log($db, 'tamu', "Impor CSV: $tambah tamu ditambahkan");
    atur_notif($pesan, $tambah > 0 ? 'sukses' : 'galat');
    return 'tamu';
}

function aksi_ucapan_tampil(mysqli $db, string $aksi): string
{
    $ids = ids_post();
    if (!$ids) {
        atur_notif('Pilih ucapan terlebih dahulu.', 'galat');
        return 'ucapan';
    }
    $tampil = $aksi === 'ucapan_tampil' ? 1 : 0;
    jalankan($db, 'UPDATE tb_ucapan SET tampil = ? WHERE id IN (' . tanda_tanya($ids) . ')', 'i' . tipe_int($ids), [$tampil, ...$ids]);
    atur_notif(count($ids) . ' ucapan ' . ($tampil ? 'ditampilkan kembali.' : 'disembunyikan.'), 'sukses',
        ['jenis' => 'tampil', 'ids' => $ids, 'tampil' => 1 - $tampil]);
    return 'ucapan';
}

function aksi_ucapan_hapus(mysqli $db): string
{
    $ids = ids_post();
    if (!$ids) {
        atur_notif('Pilih ucapan terlebih dahulu.', 'galat');
        return 'ucapan';
    }
    $in = tanda_tanya($ids);
    $baris = jalankan($db, "SELECT * FROM tb_ucapan WHERE id IN ($in)", tipe_int($ids), $ids)->get_result()->fetch_all(MYSQLI_ASSOC);
    jalankan($db, "DELETE FROM tb_ucapan WHERE id IN ($in)", tipe_int($ids), $ids);
    atur_notif(count($baris) . ' ucapan & RSVP dihapus.', 'sukses', ['jenis' => 'ucapan', 'baris' => $baris]);
    return 'ucapan';
}

function aksi_ubah_akun(mysqli $db): string
{
    $adminId = (int) $_SESSION['admin_id'];
    $usernameBaru = trim(ambil_post_string('username'));
    $passwordLama = ambil_post_string('password_lama');
    $passwordBaru = ambil_post_string('password_baru');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $usernameBaru)) {
        atur_notif('Username 3-50 karakter: huruf, angka, titik, strip, atau garis bawah.', 'galat');
    } elseif ($passwordBaru !== '' && strlen($passwordBaru) < 8) {
        atur_notif('Password baru minimal 8 karakter.', 'galat');
    } elseif ($passwordBaru !== '' && $passwordBaru !== ambil_post_string('konfirmasi')) {
        atur_notif('Konfirmasi password baru tidak cocok.', 'galat');
    } else {
        $admin = jalankan($db, 'SELECT password FROM tb_admin WHERE id = ?', 'i', [$adminId])->get_result()->fetch_assoc();
        if (!$admin || !password_verify($passwordLama, $admin['password'])) {
            atur_notif('Password saat ini salah.', 'galat');
        } else {
            $hash = $passwordBaru !== '' ? password_hash($passwordBaru, PASSWORD_DEFAULT) : $admin['password'];
            try {
                jalankan($db, 'UPDATE tb_admin SET username = ?, password = ? WHERE id = ?', 'ssi', [$usernameBaru, $hash, $adminId]);
                $_SESSION['admin_username'] = $usernameBaru;
                catat_log($db, 'akun', 'Pengaturan akun admin diperbarui');
                atur_notif('Pengaturan akun berhasil diperbarui!');
            } catch (mysqli_sql_exception $e) {
                atur_notif($e->getCode() === 1062 ? 'Username sudah digunakan.' : 'Gagal memperbarui akun.', 'galat');
            }
        }
    }
    return 'akun';
}

function aksi_urungkan(mysqli $db): string
{
    $undo = $_SESSION['undo'] ?? null;
    unset($_SESSION['undo']);
    if (!$undo || time() - $undo['waktu'] > 120) {
        atur_notif('Waktu untuk mengurungkan sudah habis.', 'galat');
        return 'statistik';
    }

    if ($undo['jenis'] === 'tampil') {
        jalankan($db, 'UPDATE tb_ucapan SET tampil = ? WHERE id IN (' . tanda_tanya($undo['ids']) . ')', 'i' . tipe_int($undo['ids']), [$undo['tampil'], ...$undo['ids']]);
        $tab = 'ucapan';
    } elseif ($undo['jenis'] === 'ucapan') {
        foreach ($undo['baris'] as $b) {
            jalankan($db, 'INSERT IGNORE INTO tb_ucapan (id, tamu_id, nama, rsvp, jumlah_tamu, pesan, tampil, created_at, diubah_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                'iissisiss', [$b['id'], $b['tamu_id'], $b['nama'], $b['rsvp'], $b['jumlah_tamu'], $b['pesan'], $b['tampil'], $b['created_at'], $b['diubah_at']]);
        }
        $tab = 'ucapan';
    } else {
        foreach ($undo['tamu'] as $t) {
            jalankan($db, 'INSERT IGNORE INTO tb_tamu (id, nama) VALUES (?, ?)', 'is', [$t['id'], $t['nama']]);
        }
        foreach ($undo['relasi'] as $r) {
            jalankan($db, 'UPDATE tb_ucapan SET tamu_id = ? WHERE id = ? AND tamu_id IS NULL', 'ii', [$r['tamu_id'], $r['id']]);
        }
        $tab = 'tamu';
    }
    atur_notif('Perubahan dibatalkan.');
    return $tab;
}

function aksi_ekspor_terpilih(mysqli $db, string $aksi): void
{
    $ids = ids_post();
    if (!$ids) {
        atur_notif('Pilih data yang akan diekspor.', 'galat');
        alihkan($aksi === 'tamu_ekspor' ? 'tamu' : 'ucapan');
    }
    $aksi === 'tamu_ekspor' ? ekspor_tamu($db, $ids) : ekspor_ucapan($db, $ids);
}
