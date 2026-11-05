<?php
require_once __DIR__ . '/koneksi.php';

$pesanNotif = '';
$jenisNotif = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = ambil_post_string('aksi');

    if (!cek_csrf(ambil_post_string('csrf'))) {
        $pesanNotif = 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.';
        $jenisNotif = 'galat';
    } elseif (!$koneksi && $aksi !== 'keluar') {
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
    } elseif (sudah_login()) {
        if ($aksi === 'keluar') {
            $_SESSION = [];
            session_destroy();
            header('Location: dashboard.php');
            exit;
        }

        if ($aksi === 'toggle_tampil') {
            $id = filter_var(ambil_post_string('id'), FILTER_VALIDATE_INT);
            $tampilBaru = ambil_post_string('tampil_baru') === '1' ? 1 : 0;

            if ($id === false || $id < 1) {
                $pesanNotif = 'ID ucapan tidak valid.';
                $jenisNotif = 'galat';
            } else {
                $stmt = $koneksi->prepare('UPDATE tb_ucapan SET tampil = ? WHERE id = ?');
                $stmt->bind_param('ii', $tampilBaru, $id);
                $stmt->execute();
                $pesanNotif = $tampilBaru ? 'Ucapan berhasil ditampilkan kembali.' : 'Ucapan berhasil disembunyikan.';
                $jenisNotif = 'sukses';
            }
        }

        if ($aksi === 'hapus') {
            $id = filter_var(ambil_post_string('id'), FILTER_VALIDATE_INT);

            if ($id === false || $id < 1) {
                $pesanNotif = 'ID ucapan tidak valid.';
                $jenisNotif = 'galat';
            } else {
                $stmt = $koneksi->prepare('DELETE FROM tb_ucapan WHERE id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $pesanNotif = 'Ucapan & RSVP berhasil dihapus.';
                $jenisNotif = 'sukses';
            }
        }

        if ($aksi === 'ubah_akun') {
            $adminId      = (int) $_SESSION['admin_id'];
            $usernameBaru = trim(ambil_post_string('username'));
            $passwordLama = ambil_post_string('password_lama');
            $passwordBaru = ambil_post_string('password_baru');
            $konfirmasi   = ambil_post_string('konfirmasi');

            if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $usernameBaru)) {
                $pesanNotif = 'Username 3-50 karakter: huruf, angka, titik, strip, atau garis bawah.';
                $jenisNotif = 'galat';
            } elseif ($passwordBaru !== '' && strlen($passwordBaru) < 8) {
                $pesanNotif = 'Password baru minimal 8 karakter.';
                $jenisNotif = 'galat';
            } elseif ($passwordBaru !== '' && $passwordBaru !== $konfirmasi) {
                $pesanNotif = 'Konfirmasi password baru tidak cocok.';
                $jenisNotif = 'galat';
            } else {
                $stmt = $koneksi->prepare('SELECT password FROM tb_admin WHERE id = ?');
                $stmt->bind_param('i', $adminId);
                $stmt->execute();
                $admin = $stmt->get_result()->fetch_assoc();

                if (!$admin || !password_verify($passwordLama, $admin['password'])) {
                    $pesanNotif = 'Password saat ini salah.';
                    $jenisNotif = 'galat';
                } else {
                    $hashBaru = $passwordBaru !== '' ? password_hash($passwordBaru, PASSWORD_DEFAULT) : $admin['password'];
                    try {
                        $stmt = $koneksi->prepare('UPDATE tb_admin SET username = ?, password = ? WHERE id = ?');
                        $stmt->bind_param('ssi', $usernameBaru, $hashBaru, $adminId);
                        $stmt->execute();
                        $_SESSION['admin_username'] = $usernameBaru;
                        $pesanNotif = 'Pengaturan akun berhasil diperbarui!';
                        $jenisNotif = 'sukses';
                    } catch (mysqli_sql_exception $e) {
                        $pesanNotif = $e->getCode() === 1062 ? 'Username sudah digunakan.' : 'Gagal memperbarui akun.';
                        $jenisNotif = 'galat';
                    }
                }
            }
        }
    }
}

$login = sudah_login();
if ($login && !$koneksi && $pesanNotif === '') {
    $pesanNotif = 'Koneksi database bermasalah. Data dashboard belum dapat dimuat.';
    $jenisNotif = 'galat';
}
$csrf  = token_csrf();

$daftarUcapan = [];
$rekap = ['Hadir' => 0, 'Tidak Hadir' => 0, 'Masih Ragu' => 0];
$estimasiTamu = 0;

if ($login && $koneksi) {
    $hasil = $koneksi->query('SELECT id, nama, rsvp, jumlah_tamu, pesan, tampil, created_at FROM tb_ucapan ORDER BY created_at DESC, id DESC');
    while ($baris = $hasil->fetch_assoc()) {
        $daftarUcapan[] = $baris;
        $rekap[$baris['rsvp']]++;
        if ($baris['rsvp'] === 'Hadir') {
            $estimasiTamu += (int) $baris['jumlah_tamu'];
        }
    }
}
$totalUcapan = count($daftarUcapan);

$skema = $https ? 'https' : 'http';
$urlUndangan = $skema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
             . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4E6151">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin - Welling & Sheila</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body data-url-undangan="<?= h($urlUndangan) ?>">
    
    <div class="latar-wallpaper" aria-hidden="true"></div>

    <div class="dashboard-wrapper">
        <div class="dashboard-container">

<?php if (!$login): ?>
            <!-- ================= HALAMAN LOGIN ================= -->
            <div class="login-card kaca">
                <span class="badge-wedding">Dashboard Admin</span>
                <p class="nama-script">Welling <span class="ampersand">&amp;</span> Sheila</p>
                <h2>UNDANGAN PERNIKAHAN</h2>

                <?php if ($pesanNotif !== ''): ?>
                    <p class="pesan-form <?= $jenisNotif ?>"><?= h($pesanNotif) ?></p>
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
            <!-- ================= DASHBOARD UTAMA ALL-IN-ONE ================= -->
            <div class="dashboard-header">
                <span class="badge-wedding">Panel Pengelola</span>
                <p class="nama-script">Welling <span class="ampersand">&amp;</span> Sheila</p>
                <div class="welcome-text">Selamat datang, Admin <strong><?= h($_SESSION['admin_username']) ?></strong></div>
            </div>

            <?php if ($pesanNotif !== ''): ?>
                <p class="pesan-form <?= $jenisNotif ?>"><?= h($pesanNotif) ?></p>
            <?php endif; ?>

            <!-- BAGIAN 1: KARTU RINGKASAN STATISTIK -->
            <section class="dashboard-block kaca">
                <h2 class="block-title"><i class="fa-solid fa-chart-pie"></i> Ringkasan Statistik</h2>
                <div class="statistik-grid">
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
                </div>
            </section>

            <!-- BAGIAN 2: GENERATOR LINK UNDANGAN TAMU -->
            <section class="dashboard-block kaca">
                <h2 class="block-title"><i class="fa-solid fa-link"></i> Generator Link Undangan Tamu</h2>
                <div class="form-content-polos">
                    <div class="input-group">
                        <label for="inputNamaTamu">Nama Tamu Undangan</label>
                        <input type="text" id="inputNamaTamu" class="custom-input" placeholder="Contoh: Budi Sudarsono">
                    </div>
                    <button type="button" id="btnBuatLink" class="tombol-aksi" onclick="buatLinkTamu()">Buat Link Undangan</button>

                    <div id="hasilLink" class="hasil-link hidden">
                        <label class="input-group-label" for="outputLink">Link Khusus Tamu:</label>
                        <input type="text" id="outputLink" class="custom-input" readonly>
                        <div class="baris-tombol">
                            <button type="button" class="tombol-aksi" onclick="salinLinkTamu()"><i class="fa-regular fa-copy"></i> Salin Link</button>
                            <a id="btnKirimWa" class="tombol-aksi wa" href="#" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Kirim WA</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- BAGIAN 3: 1 TABEL UTAMA UCAPAN & RSVP -->
            <section class="dashboard-block kaca">
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
                                        <td><small><?= h(tanggal_indo($u['created_at'])) ?></small></td>
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

                                            <form method="post" action="dashboard.php" style="display:inline;" onsubmit="return confirm('Hapus ucapan ini secara permanen?');">
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
            </section>

            <!-- BAGIAN 4: PENGATURAN AKUN & LOGOUT -->
            <section class="dashboard-block kaca">
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
            </section>
<?php endif; ?>

        </div>
    </div>

<script src="assets/scripts/dashboard.js" defer></script>
</body>
</html>
