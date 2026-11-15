# Undangan Pernikahan Digital

Website undangan pernikahan digital dengan gaya *glassmorphism*. Halaman utama berisi cover, *countdown*, profil mempelai, acara, galeri, perjalanan kisah, hadiah digital, dan *form* konfirmasi kehadiran beserta ucapan. Tersedia juga halaman *dashboard* admin untuk melakukan administrasi terkait halaman undangan.

## Tugas Kelompok 7

**Mata Kuliah:** Desain dan Pemrograman Web
**Dosen Pengampu:** Edi Susilo, M.Kom., M.Eng.

**Anggota:**

1. Callista Hana Elvaretta (25071104091)
2. Raziq Muflih (25071101697)
3. Sheila Medina Hartono (25071103589)
4. Wellington Covernikus Simangunsong (25071102434)

## Fitur

### Halaman Undangan
- Cover penuh layar dengan latar foto yang di-*blur* dan animasi buka undangan
- Nama tamu otomatis tampil di *cover* lewat link bernomor (`index.php?to=1`)
- *Countdown* menuju hari akad, mengikuti tanggal dan jam dari *dashboard*
- Profil mempelai lengkap dengan orang tua dan tautan Instagram
- Informasi acara akad dan resepsi, lengkap dengan tombol petunjuk lokasi (Google Maps)
- Tombol simpan ke Google Calendar untuk akad dan resepsi
- Galeri foto dengan *lightbox* (klik, panah keyboard, dan geser di HP)
- Perjalanan kisah dalam bentuk *timeline* responsif
- Hadiah digital dengan tombol salin nomor rekening (bisa ditampilkan atau disembunyikan)
- Musik latar dengan tombol putar/jeda
- Form konfirmasi kehadiran (RSVP) dan ucapan yang tersimpan di database MySQL
- Tamu dapat memperbarui kehadiran dan doanya lewat link undangan masing-masing
- Daftar ucapan tamu dengan paginasi
- Perlindungan form: *honeypot* anti-bot dan jeda antar pengiriman
- Tampilan responsif untuk PC dan HP dengan gaya *glassmorphism*

### Dashboard Admin
- Login admin dengan CSRF token, pembatasan percobaan login, dan sesi aman
- Menu dibagi ke beberapa tab: Statistik, Edit Konten, Tamu, Ucapan, dan Akun
- Tema terang/gelap (tersimpan di browser) dan notifikasi *toast* dengan tombol Urungkan untuk sembunyikan/hapus tamu dan ucapan

**Statistik visual**
- Kartu ringkasan: tamu diundang, sudah/belum merespons, total ucapan, estimasi tamu hadir
- Diagram lingkaran komposisi konfirmasi (Hadir, Tidak Hadir, Masih Ragu)
- Bar progres respons dari daftar tamu
- Grafik jumlah orang per konfirmasi hadir
- Grafik *bar* ucapan masuk dengan rentang 7/14/30 hari atau kustom, plus *tooltip* di semua grafik
- Diagram, legenda, dan kartu ringkasan bisa diklik untuk memfilter tabel tamu/ucapan
- Linimasa aktivitas terbaru (ucapan masuk, perubahan tamu, konten, akun)

**Edit konten**
- Nama panggilan dan nama lengkap kedua mempelai
- Keterangan anak, nama orang tua, serta nama dan tautan Instagram
- Tanggal pernikahan, jam, nama lokasi, alamat, dan link lokasi untuk akad dan resepsi
- Judul dan lokasi untuk Google Calendar
- Ganti semua foto: latar, pasangan, kedua mempelai, dan banner acara
- Galeri foto: tambah banyak sekaligus (dengan progres unggah), ganti, urutkan dengan *drag-and-drop*, dan hapus
- Perjalanan kisah: tahun, judul, cerita, dan ikon, bisa ditambah, diurutkan, dan dihapus
- Hadiah digital: saklar tampil/sembunyi, kalimat pengantar, dan daftar rekening
- Pratinjau langsung (tampilan isi dan sampul) sebelum disimpan, serta penanda perubahan yang belum disimpan
- Foto dikompres otomatis di browser sebelum diunggah dan divalidasi di server

**Manajemen tamu**
- Daftar tamu tersimpan di *database* (50 tamu awal dari *array* `data_tamu_awal.php`)
- Tambah tamu (nama ganda ditolak), edit nama langsung di baris, dan hapus
- Pencarian, filter status, dan paginasi di sisi server (20 data per halaman) dengan *header* tabel yang menempel
- Aksi massal: pilih beberapa baris untuk hapus, sembunyikan/tampilkan ucapan, atau ekspor terpilih
- Klik nama tamu untuk membuka *drawer* detail: link, tombol WhatsApp, QR code, pesan, dan riwayat
- Impor CSV mendeteksi encoding (UTF-8, UTF-16, Windows-1252) dan melaporkan baris yang dilewati
- Salin *link* undangan berbasis nomor tamu dari setiap baris
- Kirim undangan lewat WhatsApp dengan pesan siap pakai
- Pencarian nama atau nomor dan filter status respons
- Ekspor daftar tamu ke CSV (beserta status kehadiran dan pesan)
- Impor tamu dari CSV (mengenali pemisah koma atau titik koma, melewati nama ganda)

**Ucapan dan RSVP**
- Tabel ucapan dengan nomor tamu, status, jumlah, pesan, dan tanggal (termasuk tanda diperbarui)
- Sembunyikan atau tampilkan ucapan di halaman undangan
- Hapus ucapan secara permanen

**Akun**
- Ubah *username* dan *password* admin
- *Logout*

### Teknis
- Konten undangan disimpan di *database* dengan nilai bawaan sesuai isi awal
- Validasi dan sanitasi semua input (URL, tanggal, jam, path foto) serta *escape output*
- Proteksi ekspor CSV dari *CSV injection*
- *Deploy* otomatis ke cPanel lewat GitHub Actions

## Catatan

Akun admin demo:

- **Username:** `admin`
- **Password:** `admin123`
