const csrf = document.body.dataset.csrf || '';
const urlUndangan = document.body.dataset.urlUndangan || '';

// ---------- Bantuan umum ----------
function tampilkanToast(pesan) {
    if (typeof M !== 'undefined' && M.toast) {
        M.toast({ html: pesan, displayLength: 2500 });
    } else {
        alert(pesan);
    }
}

function showSection(sectionId) {
    document.querySelectorAll('#loginSection, #dashboardSection, .page-section').forEach(function (bagian) {
        bagian.classList.add('hidden');
    });

    const target = document.getElementById(sectionId);
    if (!target) return;
    target.classList.remove('hidden');

    // hash dipakai supaya halaman yang sama terbuka lagi setelah muat ulang
    history.replaceState(null, '', sectionId === 'dashboardSection' ? location.pathname : '#' + sectionId);
    window.scrollTo(0, 0);
}

// Muat ulang halaman (agar angka & daftar terbaru), lalu tampilkan toast
function muatUlang(pesan) {
    sessionStorage.setItem('toastDashboard', pesan);
    location.reload();
}

async function kirimAksi(data) {
    const form = new FormData();
    form.append('csrf', csrf);
    Object.keys(data).forEach((kunci) => form.append(kunci, data[kunci]));

    try {
        const respons = await fetch('api_dashboard.php', { method: 'POST', body: form, credentials: 'same-origin' });
        if (respons.status === 401) { location.reload(); return { ok: false, pesan: 'Sesi berakhir.' }; }
        return await respons.json();
    } catch (e) {
        return { ok: false, pesan: 'Gagal terhubung ke server. Periksa koneksi internet.' };
    }
}

// ---------- Navigasi menu ----------
document.addEventListener('click', function (e) {
    const pemicu = e.target.closest('[data-buka]');
    if (!pemicu) return;
    e.preventDefault();
    showSection(pemicu.dataset.buka);
});

// ---------- Kelola ucapan: sembunyikan / hapus ----------
document.addEventListener('click', async function (e) {
    const tombol = e.target.closest('[data-aksi]');
    if (!tombol) return;

    if (tombol.dataset.aksi === 'hapus' && !confirm('Hapus ucapan ini secara permanen? Data RSVP-nya juga ikut terhapus.')) return;

    tombol.disabled = true;
    const hasil = await kirimAksi({ aksi: tombol.dataset.aksi, id: tombol.dataset.id, tampil: tombol.dataset.tampil || 0 });

    if (hasil.ok) {
        muatUlang(hasil.pesan);
    } else {
        tampilkanToast(hasil.pesan);
        tombol.disabled = false;
    }
});

// ---------- Rekap RSVP: filter ----------
const tombolFilter = document.querySelectorAll('.chip-filter');
tombolFilter.forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        tombolFilter.forEach((t) => t.classList.toggle('aktif', t === tombol));

        const filter = tombol.dataset.filter;
        let tampil = 0;
        document.querySelectorAll('.tabel-rsvp tbody tr').forEach(function (baris) {
            const cocok = filter === 'semua' || baris.dataset.status === filter;
            baris.classList.toggle('hidden', !cocok);
            if (cocok) tampil++;
        });
        document.getElementById('rsvpKosong').classList.toggle('hidden', tampil > 0);
    });
});

// ---------- Generator link undangan ----------
const formGenerator = document.getElementById('formGenerator');
if (formGenerator) {
    const inputNamaTamu = document.getElementById('inputNamaTamu');
    const hasilLink = document.getElementById('hasilLink');
    const outputLink = document.getElementById('outputLink');
    const btnKirimWa = document.getElementById('btnKirimWa');

    formGenerator.addEventListener('submit', function (e) {
        e.preventDefault();
        const nama = inputNamaTamu.value.replace(/\s+/g, ' ').trim();
        if (!nama) { tampilkanToast('Masukkan nama tamu terlebih dahulu.'); return; }

        // URLSearchParams menghasilkan format ?to=Budi+Sudarsono
        const link = urlUndangan + '?' + new URLSearchParams({ to: nama }).toString();
        outputLink.value = link;

        const pesan =
            'Assalamu\'alaikum Warahmatullahi Wabarakatuh.\n\n' +
            'Kepada Yth. ' + nama + ',\n' +
            'Dengan penuh rasa syukur, kami mengundang Bapak/Ibu/Saudara/i untuk hadir di pernikahan Welling & Sheila pada Minggu, 12 Desember 2027.\n\n' +
            'Detail undangan dapat dilihat pada tautan berikut:\n' + link + '\n\n' +
            'Merupakan kehormatan bagi kami apabila Anda berkenan hadir. Terima kasih.';
        btnKirimWa.href = 'https://wa.me/?text=' + encodeURIComponent(pesan);

        hasilLink.classList.remove('hidden');
    });

    document.getElementById('btnSalinLink').addEventListener('click', function () {
        const teks = outputLink.value;
        const berhasil = () => tampilkanToast('Link berhasil disalin!');
        const cadangan = function () {
            outputLink.select();
            document.execCommand('copy') ? berhasil() : tampilkanToast('Gagal menyalin, salin manual.');
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(teks).then(berhasil).catch(cadangan);
        } else {
            cadangan();
        }
    });
}

// ---------- Pengaturan akun ----------
const formAkun = document.getElementById('formAkun');
if (formAkun) {
    formAkun.addEventListener('submit', async function (e) {
        e.preventDefault();
        const tombol = formAkun.querySelector('button[type="submit"]');
        tombol.disabled = true;

        const hasil = await kirimAksi({
            aksi: 'ubah_akun',
            username: document.getElementById('akunUsername').value,
            password_lama: document.getElementById('akunPasswordLama').value,
            password_baru: document.getElementById('akunPasswordBaru').value,
            konfirmasi: document.getElementById('akunKonfirmasi').value
        });

        tampilkanToast(hasil.pesan);
        tombol.disabled = false;

        if (hasil.ok) {
            document.getElementById('namaAdmin').textContent = hasil.username;
            ['akunPasswordLama', 'akunPasswordBaru', 'akunKonfirmasi'].forEach((id) => { document.getElementById(id).value = ''; });
        }
    });
}

// ---------- Saat halaman dimuat ----------
document.addEventListener('DOMContentLoaded', function () {
    const bagianDariHash = location.hash.slice(1);
    if (bagianDariHash && document.getElementById(bagianDariHash)?.classList.contains('page-section')) {
        showSection(bagianDariHash);
    }

    const toastTertunda = sessionStorage.getItem('toastDashboard');
    if (toastTertunda) {
        sessionStorage.removeItem('toastDashboard');
        tampilkanToast(toastTertunda);
    }
});
