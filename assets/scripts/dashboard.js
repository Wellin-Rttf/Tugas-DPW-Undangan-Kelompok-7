(function () {
    'use strict';

    const csrf = document.body.dataset.csrf || '';
    const MAKS_GALERI = 24;
    const MAKS_PERJALANAN = 12;
    const MAKS_REKENING = 6;
    const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const $ = (id) => document.getElementById(id);
    const simpan = (kunci, nilai) => { try { localStorage.setItem(kunci, nilai); } catch (e) {} };
    const baca = (kunci) => { try { return localStorage.getItem(kunci); } catch (e) { return null; } };

    const tombolTema = $('tombolTema');
    if (tombolTema) {
        const terapkanTema = (tema) => {
            document.documentElement.dataset.tema = tema;
            const meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.content = tema === 'gelap' ? '#1C1C1E' : '#4E6151';
        };
        terapkanTema(document.documentElement.dataset.tema || 'terang');
        tombolTema.addEventListener('click', () => {
            const tema = document.documentElement.dataset.tema === 'gelap' ? 'terang' : 'gelap';
            terapkanTema(tema);
            simpan('temaDashboard', tema);
        });
    }

    const tombolTab = document.querySelectorAll('.tab-tombol');
    const panelTab = document.querySelectorAll('.tab-panel');

    function tampilkanTab(nama) {
        if (!$('tab-' + nama)) nama = 'statistik';
        panelTab.forEach((p) => { p.hidden = p.id !== 'tab-' + nama; });
        tombolTab.forEach((t) => {
            const aktif = t.dataset.tab === nama;
            t.classList.toggle('aktif', aktif);
            t.setAttribute('aria-selected', aktif ? 'true' : 'false');
        });
    }

    if (tombolTab.length) {
        const dariHash = () => (location.hash || '').replace('#tab-', '') || 'statistik';
        tampilkanTab(dariHash());
        tombolTab.forEach((t) => t.addEventListener('click', () => {
            tampilkanTab(t.dataset.tab);
            history.replaceState(null, '', location.pathname + location.search + '#tab-' + t.dataset.tab);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }));
        window.addEventListener('hashchange', () => tampilkanTab(dariHash()));
    }

    document.addEventListener('submit', (e) => {
        const teks = (e.submitter && e.submitter.dataset.konfirmasi) || e.target.dataset.konfirmasi;
        if (teks && !confirm(teks)) {
            e.preventDefault();
            return;
        }
        const tombol = e.submitter || e.target.querySelector('button[type="submit"]');
        if (e.target.hasAttribute('data-memuat') && tombol) {
            setTimeout(() => { tombol.classList.add('memuat-tombol'); tombol.disabled = true; }, 0);
        }
        if (e.target.hasAttribute('data-skeleton')) mulaiSkeleton();
    });

    document.addEventListener('click', (e) => {
        const tautan = e.target.closest('a[data-skeleton]');
        if (tautan && !e.ctrlKey && !e.metaKey && !e.shiftKey && tautan.target !== '_blank') mulaiSkeleton();
    });

    document.querySelectorAll('[data-otomatis]').forEach((el) => el.addEventListener('change', () => el.form.requestSubmit()));

    function mulaiSkeleton() {
        document.querySelectorAll('.tab-panel:not([hidden]) .tabel-bungkus, .tab-panel:not([hidden]) .statistik-grid').forEach((el) => el.classList.add('memuat'));
    }
    window.addEventListener('pageshow', () => document.querySelectorAll('.memuat').forEach((el) => el.classList.remove('memuat')));

    async function salin(teks) {
        if (navigator.clipboard && window.isSecureContext) {
            try { await navigator.clipboard.writeText(teks); return true; } catch (e) {}
        }
        const area = document.createElement('textarea');
        area.value = teks;
        area.setAttribute('readonly', '');
        area.style.cssText = 'position:fixed;left:-9999px;top:0';
        document.body.appendChild(area);
        area.select();
        area.setSelectionRange(0, area.value.length);
        let ok = false;
        try { ok = document.execCommand('copy'); } catch (e) {}
        area.remove();
        return ok;
    }

    async function salinDenganUmpanBalik(tombol, teks) {
        if (await salin(teks)) {
            const awal = tombol.innerHTML;
            tombol.classList.add('berhasil');
            tombol.innerHTML = '<i class="fa-solid fa-check"></i> Tersalin';
            setTimeout(() => { tombol.classList.remove('berhasil'); tombol.innerHTML = awal; }, 1600);
        } else {
            window.prompt('Salin link ini secara manual:', teks);
        }
    }

    const toast = $('toastDash');
    if (toast) {
        requestAnimationFrame(() => toast.classList.add('tampil'));
        const tutup = () => toast.classList.remove('tampil');
        const timer = setTimeout(tutup, toast.querySelector('form') ? 9000 : 5000);
        $('tutupToast').addEventListener('click', () => { clearTimeout(timer); tutup(); });
    }

    const tip = $('tipGrafik');
    if (tip) {
        const geser = (e) => {
            const lebar = tip.offsetWidth;
            tip.style.left = Math.max(8, Math.min(innerWidth - lebar - 8, e.clientX + 12)) + 'px';
            tip.style.top = Math.max(8, e.clientY - tip.offsetHeight - 12) + 'px';
        };
        document.addEventListener('mouseover', (e) => {
            const el = e.target.closest('[data-tip]');
            if (!el) return;
            tip.textContent = el.dataset.tip;
            tip.hidden = false;
            geser(e);
        });
        document.addEventListener('mousemove', (e) => { if (!tip.hidden) geser(e); });
        document.addEventListener('mouseout', (e) => { if (e.target.closest('[data-tip]')) tip.hidden = true; });
        document.addEventListener('focusin', (e) => {
            const el = e.target.closest('[data-tip]');
            if (!el) return;
            const k = el.getBoundingClientRect();
            tip.textContent = el.dataset.tip;
            tip.hidden = false;
            geser({ clientX: k.left + k.width / 2, clientY: k.top });
        });
        document.addEventListener('focusout', () => { tip.hidden = true; });
    }

    const wadahGrafik = $('kolomGrafik');
    if (wadahGrafik) {
        const data = window.DATA_HARIAN || {};
        const kunci = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const keTanggal = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
        const hariIni = keTanggal(window.HARI_INI);
        const geserHari = (d, n) => { const t = new Date(d); t.setDate(t.getDate() + n); return t; };
        const inputDari = $('rentangDari');
        const inputSampai = $('rentangSampai');
        const barisKustom = $('barisKustom');
        const ket = $('ketGrafik');
        inputSampai.max = kunci(hariIni);
        inputDari.min = kunci(geserHari(hariIni, -365));

        function gambarGrafik(dari, sampai) {
            const hari = [];
            for (let d = new Date(dari); d <= sampai; d = geserHari(d, 1)) hari.push(new Date(d));
            const maks = Math.max(1, ...hari.map((d) => data[kunci(d)] || 0));
            const langkah = Math.ceil(hari.length / 15);
            wadahGrafik.innerHTML = '';
            let total = 0;
            hari.forEach((d, i) => {
                const n = data[kunci(d)] || 0;
                total += n;
                const kolom = document.createElement('div');
                kolom.className = 'kolom';
                kolom.dataset.tip = d.getDate() + ' ' + BULAN[d.getMonth()] + ' ' + d.getFullYear() + ': ' + n + ' ucapan';
                kolom.innerHTML = '<span class="kolom-nilai">' + (n > 0 ? n : '') + '</span>'
                    + '<div class="kolom-batang" style="height:' + (n > 0 ? Math.max(6, Math.round(n / maks * 100)) : 2) + '%"></div>'
                    + '<span class="kolom-label">' + (i % langkah === 0 ? d.getDate() : '') + '</span>';
                wadahGrafik.appendChild(kolom);
            });
            ket.textContent = total + ' ucapan dalam rentang ' + hari.length + ' hari (angka di bawah bar = tanggal).';
        }

        function pilihRentang(nilai) {
            document.querySelectorAll('[data-rentang]').forEach((t) => t.classList.toggle('aktif', t.dataset.rentang === nilai));
            barisKustom.hidden = nilai !== 'kustom';
            if (nilai === 'kustom') {
                if (!inputSampai.value) inputSampai.value = kunci(hariIni);
                if (!inputDari.value) inputDari.value = kunci(geserHari(hariIni, -13));
                terapkanKustom();
            } else {
                gambarGrafik(geserHari(hariIni, -(Number(nilai) - 1)), hariIni);
            }
        }

        function terapkanKustom() {
            if (!inputDari.value || !inputSampai.value) return;
            let dari = keTanggal(inputDari.value);
            const sampai = keTanggal(inputSampai.value);
            if (dari > sampai) { ket.textContent = 'Tanggal awal harus sebelum tanggal akhir.'; return; }
            if ((sampai - dari) / 86400000 > 89) {
                dari = geserHari(sampai, -89);
                inputDari.value = kunci(dari);
            }
            gambarGrafik(dari, sampai);
        }

        document.querySelectorAll('[data-rentang]').forEach((t) => t.addEventListener('click', () => pilihRentang(t.dataset.rentang)));
        $('terapkanRentang').addEventListener('click', terapkanKustom);
        pilihRentang('14');
    }

    document.querySelectorAll('.bar-massal').forEach((bar) => {
        const tabel = bar.parentElement.querySelector('table');
        const pilihan = () => Array.from(tabel.querySelectorAll('[data-pilih]'));
        const semua = tabel.querySelector('[data-pilih-semua]');
        const perbarui = () => {
            const n = pilihan().filter((c) => c.checked).length;
            bar.querySelector('[data-jumlah-dipilih]').textContent = n + ' dipilih';
            bar.querySelectorAll('[data-butuh-pilihan]').forEach((b) => { b.disabled = n === 0; });
            semua.checked = n > 0 && n === pilihan().length;
            semua.indeterminate = n > 0 && n < pilihan().length;
            pilihan().forEach((c) => c.closest('tr').classList.toggle('baris-dipilih', c.checked));
        };
        semua.addEventListener('change', () => { pilihan().forEach((c) => { c.checked = semua.checked; }); perbarui(); });
        tabel.addEventListener('change', (e) => { if (e.target.matches('[data-pilih]')) perbarui(); });
        window.addEventListener('pageshow', perbarui);
        perbarui();
    });

    const tabelTamu = $('tabelTamu');
    const drawer = $('drawerTamu');
    if (tabelTamu) {
        tabelTamu.addEventListener('click', (e) => {
            const tombolEdit = e.target.closest('[data-edit-tamu]');
            if (tombolEdit) {
                const sel = tombolEdit.closest('.sel-nama');
                sel.querySelector('.nama-tampil').hidden = true;
                const formEdit = sel.querySelector('.form-edit-nama');
                formEdit.hidden = false;
                const input = formEdit.querySelector('input[name="nama"]');
                input.focus();
                input.select();
            } else if (e.target.closest('[data-batal-edit]')) {
                const sel = e.target.closest('.sel-nama');
                sel.querySelector('.form-edit-nama').hidden = true;
                sel.querySelector('.nama-tampil').hidden = false;
            } else if (e.target.closest('[data-detail]')) {
                bukaDrawer(JSON.parse(e.target.closest('[data-detail]').dataset.detail));
            }
        });
    }

    document.addEventListener('click', (e) => {
        const tombol = e.target.closest('[data-salin]');
        if (tombol) salinDenganUmpanBalik(tombol, tombol.dataset.salin);
    });

    function muatQr() {
        if (window.QRCode) return Promise.resolve();
        return new Promise((selesai, gagal) => {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
            s.onload = selesai;
            s.onerror = gagal;
            document.head.appendChild(s);
        });
    }

    function bukaDrawer(d) {
        $('drawerNo').textContent = 'Tamu No. ' + d.id;
        $('drawerNama').textContent = d.nama;
        const status = $('drawerStatus');
        status.innerHTML = '';
        const badge = document.createElement('span');
        badge.className = 'badge-status ' + (d.rsvp === 'Hadir' ? 'hijau' : d.rsvp === 'Tidak Hadir' ? 'merah' : d.rsvp ? 'kuning' : 'abu');
        badge.textContent = d.rsvp ? d.rsvp + ' (' + d.jumlah + ')' : 'Belum merespons';
        status.appendChild(badge);
        const pesan = $('drawerPesan');
        pesan.hidden = !d.pesan;
        pesan.textContent = d.pesan;
        $('drawerLink').value = d.link;
        $('drawerSalin').dataset.salin = d.link;
        $('drawerWa').href = d.wa;
        const riwayat = $('drawerRiwayat');
        riwayat.innerHTML = '';
        d.riwayat.forEach(([judul, waktu]) => {
            const li = document.createElement('li');
            li.innerHTML = '<span class="linimasa-ikon"><i class="fa-solid fa-circle-dot"></i></span><div><span></span><small></small></div>';
            li.querySelector('span:not(.linimasa-ikon)').textContent = judul;
            li.querySelector('small').textContent = waktu;
            riwayat.appendChild(li);
        });
        const qr = $('drawerQr');
        qr.innerHTML = '';
        muatQr().then(() => new QRCode(qr, { text: d.link, width: 168, height: 168, colorDark: '#1C1C1E', colorLight: '#ffffff' })).catch(() => { qr.textContent = 'QR code tidak dapat dimuat.'; });
        $('drawerLatar').hidden = false;
        drawer.hidden = false;
        requestAnimationFrame(() => { drawer.classList.add('buka'); $('drawerLatar').classList.add('buka'); });
        $('drawerTutup').focus();
    }

    function tutupDrawer() {
        drawer.classList.remove('buka');
        $('drawerLatar').classList.remove('buka');
        setTimeout(() => { drawer.hidden = true; $('drawerLatar').hidden = true; }, 250);
    }

    if (drawer) {
        $('drawerTutup').addEventListener('click', tutupDrawer);
        $('drawerLatar').addEventListener('click', tutupDrawer);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !drawer.hidden) tutupDrawer(); });
    }

    const form = $('formKonten');
    if (!form) return;

    const subTombol = form.querySelectorAll('.sub-tab-tombol');
    const subPanel = form.querySelectorAll('.sub-tab-panel');
    function tampilkanSub(nama) {
        subTombol.forEach((t) => t.classList.toggle('aktif', t.dataset.subtab === nama));
        subPanel.forEach((p) => { p.hidden = p.dataset.subpanel !== nama; });
        simpan('subTabKonten', nama);
    }
    subTombol.forEach((t) => t.addEventListener('click', () => tampilkanSub(t.dataset.subtab)));
    tampilkanSub(baca('subTabKonten') || 'mempelai');

    const daftarGaleri = $('listGaleri');
    const daftarPerjalanan = $('listPerjalanan');
    const daftarRekening = $('listRekening');
    const semuaDaftar = [daftarGaleri, daftarPerjalanan, daftarRekening];
    const jumlahBaris = (daftar) => daftar.querySelectorAll(':scope > [data-baris]').length;

    function nomori(daftar) {
        const prefix = daftar.dataset.list;
        daftar.querySelectorAll(':scope > [data-baris]').forEach((baris, i) => {
            baris.querySelectorAll('[data-f]').forEach((el) => {
                el.name = el.dataset.f === '_' ? prefix + '[' + i + ']' : prefix + '[' + i + '][' + el.dataset.f + ']';
            });
        });
    }
    semuaDaftar.forEach(nomori);

    const badgeBelumSimpan = $('badgeBelumSimpan');
    const potret = () => JSON.stringify(Array.from(new FormData(form)).filter(([k]) => k !== 'csrf' && k !== 'aksi'));
    let potretAwal = potret();
    let sedangMengirim = false;
    function periksaPerubahan() {
        const kotor = potret() !== potretAwal;
        badgeBelumSimpan.hidden = !kotor;
        form.dataset.kotor = kotor ? '1' : '';
    }
    window.addEventListener('beforeunload', (e) => {
        if (form.dataset.kotor && !sedangMengirim) { e.preventDefault(); e.returnValue = ''; }
    });

    const tataLetak = $('editLayout');
    const bingkai = $('framePratinjau');
    const panelPratinjau = $('panelPratinjau');
    const tombolPratinjau = $('tombolPratinjau');
    const aksiAwal = form.getAttribute('action');
    const aksiPratinjau = form.dataset.aksiPratinjau;
    let tampilan = 'isi';
    let timerPratinjau = null;
    let posisiScroll = 0;
    const pratinjauTerbuka = () => tataLetak.classList.contains('pratinjau-buka');

    function kirimPratinjau() {
        if (!pratinjauTerbuka()) return;
        try { posisiScroll = bingkai.contentWindow.scrollY || 0; } catch (e) { posisiScroll = 0; }
        semuaDaftar.forEach(nomori);
        panelPratinjau.classList.add('memuat');
        form.target = 'framePratinjau';
        form.setAttribute('action', aksiPratinjau + tampilan);
        form.submit();
        form.target = '';
        form.setAttribute('action', aksiAwal);
    }

    function jadwalPratinjau() {
        if (!pratinjauTerbuka()) return;
        clearTimeout(timerPratinjau);
        timerPratinjau = setTimeout(kirimPratinjau, 800);
    }

    function perubahan() {
        semuaDaftar.forEach(nomori);
        periksaPerubahan();
        jadwalPratinjau();
    }

    bingkai.addEventListener('load', () => {
        panelPratinjau.classList.remove('memuat');
        if (posisiScroll > 0) {
            try { bingkai.contentWindow.scrollTo(0, posisiScroll); } catch (e) {}
        }
    });

    tombolPratinjau.addEventListener('click', () => {
        const buka = !pratinjauTerbuka();
        tataLetak.classList.toggle('pratinjau-buka', buka);
        tombolPratinjau.classList.toggle('aktif', buka);
        if (buka) { posisiScroll = 0; kirimPratinjau(); }
    });
    $('tombolTutupPratinjau').addEventListener('click', () => {
        tataLetak.classList.remove('pratinjau-buka');
        tombolPratinjau.classList.remove('aktif');
    });
    $('tombolSegarkan').addEventListener('click', () => { posisiScroll = 0; kirimPratinjau(); });
    document.querySelectorAll('[data-tampilan]').forEach((tombol) => tombol.addEventListener('click', () => {
        tampilan = tombol.dataset.tampilan;
        document.querySelectorAll('[data-tampilan]').forEach((t) => t.classList.toggle('aktif', t === tombol));
        posisiScroll = 0;
        kirimPratinjau();
    }));

    form.addEventListener('input', () => { periksaPerubahan(); jadwalPratinjau(); });
    form.addEventListener('change', () => { periksaPerubahan(); jadwalPratinjau(); });
    form.addEventListener('submit', () => { sedangMengirim = true; semuaDaftar.forEach(nomori); });

    function tambahBaris(daftar, idTemplate, maks, pesanMaks) {
        if (jumlahBaris(daftar) >= maks) {
            if (pesanMaks) alert(pesanMaks);
            return null;
        }
        const baris = $(idTemplate).content.firstElementChild.cloneNode(true);
        daftar.appendChild(baris);
        nomori(daftar);
        return baris;
    }

    [[daftarPerjalanan, 'tombolTambahPerjalanan', 'tplPerjalanan', MAKS_PERJALANAN, 'tahap'],
     [daftarRekening, 'tombolTambahRekening', 'tplRekening', MAKS_REKENING, 'rekening']].forEach(([daftar, idTombol, idTpl, maks, satuan]) => {
        $(idTombol).addEventListener('click', () => {
            const baris = tambahBaris(daftar, idTpl, maks, 'Maksimal ' + maks + ' ' + satuan + '.');
            if (baris) { baris.querySelector('input').focus(); perubahan(); }
        });
    });

    function urutkan(daftar) {
        daftar.addEventListener('pointerdown', (e) => {
            const gagang = e.target.closest('[data-gagang]');
            const baris = gagang && gagang.closest('[data-baris]');
            if (!baris || baris.parentElement !== daftar || e.button > 0) return;
            e.preventDefault();
            baris.classList.add('diseret');
            gagang.setPointerCapture(e.pointerId);
            const gerak = (ev) => {
                const sasaran = Array.from(daftar.children).find((el) => {
                    const k = el.getBoundingClientRect();
                    return el !== baris && ev.clientY > k.top && ev.clientY < k.bottom;
                });
                if (!sasaran) return;
                const k = sasaran.getBoundingClientRect();
                daftar.insertBefore(baris, ev.clientY > k.top + k.height / 2 ? sasaran.nextElementSibling : sasaran);
            };
            const selesai = () => {
                gagang.removeEventListener('pointermove', gerak);
                baris.classList.remove('diseret');
                perubahan();
            };
            gagang.addEventListener('pointermove', gerak);
            gagang.addEventListener('pointerup', selesai, { once: true });
            gagang.addEventListener('pointercancel', selesai, { once: true });
        });
        daftar.addEventListener('keydown', (e) => {
            const gagang = e.target.closest('[data-gagang]');
            if (!gagang || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) return;
            const baris = gagang.closest('[data-baris]');
            const tetangga = e.key === 'ArrowUp' ? baris.previousElementSibling : baris.nextElementSibling;
            if (!tetangga) return;
            e.preventDefault();
            daftar.insertBefore(baris, e.key === 'ArrowUp' ? tetangga : tetangga.nextElementSibling);
            gagang.focus();
            perubahan();
        });
    }
    semuaDaftar.forEach(urutkan);

    form.addEventListener('click', (e) => {
        const tombol = e.target.closest('[data-aksi]');
        if (!tombol) return;
        const aksi = tombol.dataset.aksi;
        if (aksi === 'hapus') {
            tombol.closest('[data-baris]').remove();
            perubahan();
        } else if (aksi === 'ganti') {
            tombol.closest('[data-baris], .foto-field').querySelector('[data-foto-berkas]').click();
        } else if (aksi === 'default') {
            const kolom = tombol.closest('.foto-field');
            aturFoto(kolom, kolom.dataset.default);
            statusFoto(kolom, 'Dikembalikan ke foto awal.', 'sukses');
            perubahan();
        }
    });

    function statusFoto(wadah, teks, jenis) {
        const el = wadah.querySelector('.foto-status');
        if (!el) return;
        el.textContent = teks;
        el.className = 'foto-status' + (jenis ? ' ' + jenis : '');
    }

    function aturFoto(wadah, path) {
        wadah.querySelector('[data-foto-nilai]').value = path;
        wadah.querySelector('.foto-thumb img').src = path;
    }

    function adaTransparansi(ctx, w, h) {
        const piksel = ctx.getImageData(0, 0, w, h).data;
        for (let i = 3; i < piksel.length; i += 4) if (piksel[i] < 255) return true;
        return false;
    }

    function kompres(berkas) {
        return new Promise((selesai) => {
            if (!/^image\/(jpeg|png|webp)$/.test(berkas.type)) { selesai(berkas); return; }
            const url = URL.createObjectURL(berkas);
            const gambar = new Image();
            gambar.onload = () => {
                URL.revokeObjectURL(url);
                const skala = Math.min(1, 1920 / Math.max(gambar.naturalWidth, gambar.naturalHeight));
                const w = Math.round(gambar.naturalWidth * skala);
                const h = Math.round(gambar.naturalHeight * skala);
                const kanvas = document.createElement('canvas');
                kanvas.width = w;
                kanvas.height = h;
                const ctx = kanvas.getContext('2d');
                ctx.drawImage(gambar, 0, 0, w, h);
                const transparan = berkas.type !== 'image/jpeg' && adaTransparansi(ctx, w, h);
                if (!transparan) {
                    ctx.globalCompositeOperation = 'destination-over';
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                }
                kanvas.toBlob((blob) => {
                    if (!blob || (blob.size >= berkas.size && skala === 1)) { selesai(berkas); return; }
                    const ekstensi = { 'image/webp': 'webp', 'image/png': 'png' }[blob.type] || 'jpg';
                    selesai(new File([blob], berkas.name.replace(/\.[^.]+$/, '') + '.' + ekstensi, { type: blob.type }));
                }, transparan ? 'image/webp' : 'image/jpeg', 0.85);
            };
            gambar.onerror = () => { URL.revokeObjectURL(url); selesai(berkas); };
            gambar.src = url;
        });
    }

    async function unggah(berkas, saatProgres) {
        const data = new FormData();
        data.append('aksi', 'unggah_foto');
        data.append('csrf', csrf);
        data.append('foto', await kompres(berkas));
        return new Promise((selesai) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'dashboard.php');
            xhr.upload.onprogress = (e) => { if (e.lengthComputable) saatProgres(Math.round(e.loaded / e.total * 100)); };
            xhr.onerror = () => selesai({ ok: false, pesan: 'Koneksi gagal. Coba lagi.' });
            xhr.onload = () => {
                try { selesai(JSON.parse(xhr.responseText)); } catch (err) { selesai({ ok: false, pesan: 'Respons server tidak dikenali.' }); }
            };
            xhr.send(data);
        });
    }

    function barProgres(wadah) {
        const bar = document.createElement('progress');
        bar.className = 'progres-unggah';
        bar.max = 100;
        bar.value = 0;
        const status = wadah.querySelector('.foto-status');
        status.after(bar);
        return bar;
    }

    form.addEventListener('change', async (e) => {
        const input = e.target.closest('[data-foto-berkas]');
        if (!input || !input.files.length) return;
        const wadah = input.closest('[data-baris], .foto-field');
        const berkas = input.files[0];
        input.value = '';
        const bar = barProgres(wadah);
        statusFoto(wadah, 'Mengunggah...', '');
        const hasil = await unggah(berkas, (p) => { bar.value = p; statusFoto(wadah, 'Mengunggah... ' + p + '%', ''); });
        bar.remove();
        if (hasil.ok) {
            aturFoto(wadah, hasil.path);
            statusFoto(wadah, 'Foto diganti. Klik Simpan untuk menerapkan.', 'sukses');
            perubahan();
        } else {
            statusFoto(wadah, hasil.pesan || 'Gagal mengunggah.', 'galat');
        }
    });

    const berkasGaleri = $('berkasTambahGaleri');
    const statusGaleri = $('statusTambahGaleri');
    $('tombolTambahGaleri').addEventListener('click', () => berkasGaleri.click());
    berkasGaleri.addEventListener('change', async () => {
        const daftar = Array.from(berkasGaleri.files);
        berkasGaleri.value = '';
        const galat = [];
        let berhasil = 0;
        const bar = document.createElement('progress');
        bar.className = 'progres-unggah';
        bar.max = daftar.length * 100;
        bar.value = 0;
        statusGaleri.after(bar);

        for (let i = 0; i < daftar.length; i++) {
            if (jumlahBaris(daftarGaleri) >= MAKS_GALERI) {
                galat.push('Batas ' + MAKS_GALERI + ' foto tercapai.');
                break;
            }
            statusGaleri.className = 'foto-status';
            const hasil = await unggah(daftar[i], (p) => {
                bar.value = i * 100 + p;
                statusGaleri.textContent = 'Mengunggah ' + (i + 1) + ' dari ' + daftar.length + '... ' + p + '%';
            });
            if (!hasil.ok) { galat.push(daftar[i].name + ': ' + (hasil.pesan || 'gagal')); continue; }
            const baris = tambahBaris(daftarGaleri, 'tplGaleri', MAKS_GALERI, '');
            if (baris) { aturFoto(baris, hasil.path); berhasil++; }
        }

        bar.remove();
        statusGaleri.className = 'foto-status ' + (galat.length ? 'galat' : 'sukses');
        statusGaleri.textContent = berhasil + ' foto ditambahkan.' + (galat.length ? ' ' + galat.join(' ') : ' Klik Simpan untuk menerapkan.');
        if (berhasil) perubahan();
    });
})();
