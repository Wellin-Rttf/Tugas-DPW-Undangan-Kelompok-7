(function () {
    'use strict';

    const csrf = document.body.dataset.csrf || '';
    const MAKS_GALERI = 24;
    const MAKS_PERJALANAN = 12;
    const MAKS_REKENING = 6;

    const tombolTab = document.querySelectorAll('.tab-tombol');
    const panelTab = document.querySelectorAll('.tab-panel');

    function tampilkanTab(nama) {
        if (!document.getElementById('tab-' + nama)) nama = 'statistik';
        panelTab.forEach(function (p) { p.hidden = p.id !== 'tab-' + nama; });
        tombolTab.forEach(function (t) {
            const aktif = t.dataset.tab === nama;
            t.classList.toggle('aktif', aktif);
            t.setAttribute('aria-selected', aktif ? 'true' : 'false');
        });
    }

    if (tombolTab.length) {
        tampilkanTab((location.hash || '').replace('#tab-', '') || 'statistik');
        tombolTab.forEach(function (t) {
            t.addEventListener('click', function () {
                tampilkanTab(t.dataset.tab);
                history.replaceState(null, '', '#tab-' + t.dataset.tab);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
        window.addEventListener('hashchange', function () {
            tampilkanTab((location.hash || '').replace('#tab-', '') || 'statistik');
        });
    }

    document.addEventListener('submit', function (e) {
        const teks = e.target.dataset && e.target.dataset.konfirmasi;
        if (teks && !confirm(teks)) e.preventDefault();
    });

    async function salin(teks) {
        if (navigator.clipboard && window.isSecureContext) {
            try { await navigator.clipboard.writeText(teks); return true; } catch (e) {  }
        }
        const area = document.createElement('textarea');
        area.value = teks;
        area.setAttribute('readonly', '');
        area.style.cssText = 'position:fixed;left:-9999px;top:0';
        document.body.appendChild(area);
        area.select();
        area.setSelectionRange(0, area.value.length);
        let ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        area.remove();
        return ok;
    }

    const tabelTamu = document.getElementById('tabelTamu');
    if (tabelTamu) {
        tabelTamu.addEventListener('click', async function (e) {
            const tombolSalin = e.target.closest('[data-salin]');
            if (tombolSalin) {
                const ok = await salin(tombolSalin.dataset.salin);
                if (ok) {
                    const awal = tombolSalin.innerHTML;
                    tombolSalin.classList.add('berhasil');
                    tombolSalin.innerHTML = '<i class="fa-solid fa-check"></i> Tersalin';
                    setTimeout(function () {
                        tombolSalin.classList.remove('berhasil');
                        tombolSalin.innerHTML = awal;
                    }, 1600);
                } else {
                    window.prompt('Salin link ini secara manual:', tombolSalin.dataset.salin);
                }
                return;
            }

            const tombolEdit = e.target.closest('[data-edit-tamu]');
            if (tombolEdit) {
                const sel = tombolEdit.closest('.sel-nama');
                sel.querySelector('.nama-tampil').hidden = true;
                const formEdit = sel.querySelector('.form-edit-nama');
                formEdit.hidden = false;
                const input = formEdit.querySelector('input[name="nama"]');
                input.focus();
                input.select();
                return;
            }

            if (e.target.closest('[data-batal-edit]')) {
                const sel = e.target.closest('.sel-nama');
                sel.querySelector('.form-edit-nama').hidden = true;
                sel.querySelector('.nama-tampil').hidden = false;
            }
        });

        const cari = document.getElementById('cariTamu');
        const filter = document.getElementById('filterTamu');
        const kosong = document.getElementById('tamuKosong');

        const terapkanFilter = function () {
            const q = cari.value.trim().toLowerCase();
            const status = filter.value;
            let tampil = 0;
            tabelTamu.querySelectorAll('tbody tr').forEach(function (tr) {
                const cocok = tr.dataset.cari.indexOf(q) !== -1 && (status === 'semua' || tr.dataset.status === status);
                tr.classList.toggle('baris-cocok-sembunyi', !cocok);
                if (cocok) tampil++;
            });
            kosong.classList.toggle('hidden', tampil > 0);
        };
        cari.addEventListener('input', terapkanFilter);
        filter.addEventListener('change', terapkanFilter);
    }

    const form = document.getElementById('formKonten');
    if (!form) return;

    const subTombol = form.querySelectorAll('.sub-tab-tombol');
    const subPanel = form.querySelectorAll('.sub-tab-panel');
    function tampilkanSub(nama) {
        subTombol.forEach(function (t) { t.classList.toggle('aktif', t.dataset.subtab === nama); });
        subPanel.forEach(function (p) { p.hidden = p.dataset.subpanel !== nama; });
        try { sessionStorage.setItem('subTabKonten', nama); } catch (e) {  }
    }
    subTombol.forEach(function (t) {
        t.addEventListener('click', function () { tampilkanSub(t.dataset.subtab); });
    });
    let subAwal = 'mempelai';
    try { subAwal = sessionStorage.getItem('subTabKonten') || 'mempelai'; } catch (e) {  }
    tampilkanSub(subAwal);

    function nomori(daftar) {
        const prefix = daftar.dataset.list;
        daftar.querySelectorAll(':scope > [data-baris]').forEach(function (baris, i) {
            baris.querySelectorAll('[data-f]').forEach(function (el) {
                const f = el.dataset.f;
                el.name = f === '_' ? prefix + '[' + i + ']' : prefix + '[' + i + '][' + f + ']';
            });
        });
    }
    const daftarGaleri = document.getElementById('listGaleri');
    const daftarPerjalanan = document.getElementById('listPerjalanan');
    const daftarRekening = document.getElementById('listRekening');
    [daftarGaleri, daftarPerjalanan, daftarRekening].forEach(nomori);

    function tambahBaris(daftar, idTemplate, maks, pesanMaks) {
        if (daftar.querySelectorAll(':scope > [data-baris]').length >= maks) {
            if (pesanMaks) alert(pesanMaks);
            return null;
        }
        const tpl = document.getElementById(idTemplate);
        const baris = tpl.content.firstElementChild.cloneNode(true);
        daftar.appendChild(baris);
        nomori(daftar);
        return baris;
    }

    document.getElementById('tombolTambahPerjalanan').addEventListener('click', function () {
        const baris = tambahBaris(daftarPerjalanan, 'tplPerjalanan', MAKS_PERJALANAN, 'Maksimal ' + MAKS_PERJALANAN + ' tahap.');
        if (baris) { baris.querySelector('input').focus(); jadwalPratinjau(); }
    });
    document.getElementById('tombolTambahRekening').addEventListener('click', function () {
        const baris = tambahBaris(daftarRekening, 'tplRekening', MAKS_REKENING, 'Maksimal ' + MAKS_REKENING + ' rekening.');
        if (baris) { baris.querySelector('input').focus(); jadwalPratinjau(); }
    });

    form.addEventListener('click', function (e) {
        const tombol = e.target.closest('[data-aksi]');
        if (!tombol) return;
        const aksi = tombol.dataset.aksi;
        const baris = tombol.closest('[data-baris]');
        const daftar = baris ? baris.parentElement : null;

        if (aksi === 'hapus' && baris) {
            baris.remove();
            nomori(daftar);
            jadwalPratinjau();
        } else if (aksi === 'naik' && baris && baris.previousElementSibling) {
            daftar.insertBefore(baris, baris.previousElementSibling);
            nomori(daftar);
            jadwalPratinjau();
        } else if (aksi === 'turun' && baris && baris.nextElementSibling) {
            daftar.insertBefore(baris.nextElementSibling, baris);
            nomori(daftar);
            jadwalPratinjau();
        } else if (aksi === 'ganti') {
            tombol.closest('[data-baris], .foto-field').querySelector('[data-foto-berkas]').click();
        } else if (aksi === 'default') {
            const kolom = tombol.closest('.foto-field');
            aturFoto(kolom, kolom.dataset.default);
            statusFoto(kolom, 'Dikembalikan ke foto awal.', 'sukses');
            jadwalPratinjau();
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

    function kompres(berkas) {
        return new Promise(function (selesai) {
            if (!/^image\/(jpeg|png|webp)$/.test(berkas.type)) { selesai(berkas); return; }
            const url = URL.createObjectURL(berkas);
            const gambar = new Image();
            gambar.onload = function () {
                URL.revokeObjectURL(url);
                const maks = 1920;
                const skala = Math.min(1, maks / Math.max(gambar.naturalWidth, gambar.naturalHeight));
                const w = Math.round(gambar.naturalWidth * skala);
                const h = Math.round(gambar.naturalHeight * skala);
                const kanvas = document.createElement('canvas');
                kanvas.width = w;
                kanvas.height = h;
                const ctx = kanvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(gambar, 0, 0, w, h);
                kanvas.toBlob(function (blob) {
                    if (!blob || (blob.size >= berkas.size && skala === 1)) { selesai(berkas); return; }
                    const nama = berkas.name.replace(/\.[^.]+$/, '') + '.jpg';
                    selesai(new File([blob], nama, { type: 'image/jpeg' }));
                }, 'image/jpeg', 0.85);
            };
            gambar.onerror = function () { URL.revokeObjectURL(url); selesai(berkas); };
            gambar.src = url;
        });
    }

    async function unggah(berkas) {
        const data = new FormData();
        data.append('aksi', 'unggah_foto');
        data.append('csrf', csrf);
        data.append('foto', await kompres(berkas));
        let respons;
        try {
            respons = await fetch('dashboard.php', { method: 'POST', body: data, credentials: 'same-origin' });
        } catch (err) {
            return { ok: false, pesan: 'Koneksi gagal. Coba lagi.' };
        }
        try { return await respons.json(); } catch (err) { return { ok: false, pesan: 'Respons server tidak dikenali.' }; }
    }

    form.addEventListener('change', async function (e) {
        const input = e.target.closest('[data-foto-berkas]');
        if (!input || !input.files.length) return;
        const wadah = input.closest('[data-baris], .foto-field');
        const berkas = input.files[0];
        input.value = '';

        statusFoto(wadah, 'Mengunggah...', '');
        const hasil = await unggah(berkas);
        if (hasil.ok) {
            aturFoto(wadah, hasil.path);
            statusFoto(wadah, 'Foto diganti. Klik Simpan untuk menerapkan.', 'sukses');
            jadwalPratinjau();
        } else {
            statusFoto(wadah, hasil.pesan || 'Gagal mengunggah.', 'galat');
        }
    });

    const berkasGaleri = document.getElementById('berkasTambahGaleri');
    const statusGaleri = document.getElementById('statusTambahGaleri');
    document.getElementById('tombolTambahGaleri').addEventListener('click', function () { berkasGaleri.click(); });
    berkasGaleri.addEventListener('change', async function () {
        const daftar = Array.from(berkasGaleri.files);
        berkasGaleri.value = '';
        let berhasil = 0;
        const galat = [];

        for (let i = 0; i < daftar.length; i++) {
            if (daftarGaleri.querySelectorAll(':scope > [data-baris]').length >= MAKS_GALERI) {
                galat.push('Batas ' + MAKS_GALERI + ' foto tercapai.');
                break;
            }
            statusGaleri.className = 'foto-status';
            statusGaleri.textContent = 'Mengunggah ' + (i + 1) + ' dari ' + daftar.length + '...';
            const hasil = await unggah(daftar[i]);
            if (!hasil.ok) { galat.push(daftar[i].name + ': ' + (hasil.pesan || 'gagal')); continue; }

            const baris = tambahBaris(daftarGaleri, 'tplGaleri', MAKS_GALERI, '');
            if (baris) { aturFoto(baris, hasil.path); berhasil++; }
        }

        statusGaleri.className = 'foto-status ' + (galat.length ? 'galat' : 'sukses');
        statusGaleri.textContent = berhasil + ' foto ditambahkan.' + (galat.length ? ' ' + galat.join(' ') : ' Klik Simpan untuk menerapkan.');
        if (berhasil) jadwalPratinjau();
    });

    form.addEventListener('submit', function () {
        [daftarGaleri, daftarPerjalanan, daftarRekening].forEach(nomori);
    });

    const tataLetak = document.getElementById('editLayout');
    const bingkai = document.getElementById('framePratinjau');
    const tombolPratinjau = document.getElementById('tombolPratinjau');
    const aksiAwal = form.getAttribute('action');
    const aksiPratinjau = form.dataset.aksiPratinjau;
    let tampilan = 'isi';
    let timerPratinjau = null;
    let posisiScroll = 0;

    function pratinjauTerbuka() { return tataLetak.classList.contains('pratinjau-buka'); }

    function kirimPratinjau() {
        if (!pratinjauTerbuka()) return;
        try { posisiScroll = bingkai.contentWindow.scrollY || 0; } catch (e) { posisiScroll = 0; }
        [daftarGaleri, daftarPerjalanan, daftarRekening].forEach(nomori);
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

    bingkai.addEventListener('load', function () {
        if (posisiScroll > 0) {
            try { bingkai.contentWindow.scrollTo(0, posisiScroll); } catch (e) {  }
        }
    });

    tombolPratinjau.addEventListener('click', function () {
        const buka = !pratinjauTerbuka();
        tataLetak.classList.toggle('pratinjau-buka', buka);
        tombolPratinjau.classList.toggle('aktif', buka);
        if (buka) { posisiScroll = 0; kirimPratinjau(); }
    });

    document.getElementById('tombolTutupPratinjau').addEventListener('click', function () {
        tataLetak.classList.remove('pratinjau-buka');
        tombolPratinjau.classList.remove('aktif');
    });
    document.getElementById('tombolSegarkan').addEventListener('click', function () {
        posisiScroll = 0;
        kirimPratinjau();
    });

    document.querySelectorAll('[data-tampilan]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            tampilan = tombol.dataset.tampilan;
            document.querySelectorAll('[data-tampilan]').forEach(function (t) {
                t.classList.toggle('aktif', t === tombol);
            });
            posisiScroll = 0;
            kirimPratinjau();
        });
    });

    form.addEventListener('input', jadwalPratinjau);
    form.addEventListener('change', jadwalPratinjau);
})();
