const urlUndanganBase = document.body.dataset.urlUndangan || '';

function buatLinkTamu() {
    const input = document.getElementById('inputNamaTamu');
    const nama = input.value.trim().replace(/\s+/g, ' ');
    if (!nama) { alert('Masukkan nama tamu terlebih dahulu.'); return; }

    const link = urlUndanganBase + '?to=' + encodeURIComponent(nama);
    document.getElementById('outputLink').value = link;

    const pesanWa = 'Assalamu\'alaikum Warahmatullahi Wabarakatuh.\n\n' +
        'Kepada Yth. ' + nama + ',\n' +
        'Dengan penuh rasa syukur, kami mengundang Bapak/Ibu/Saudara/i untuk hadir di pernikahan Welling & Sheila pada Minggu, 12 Desember 2027.\n\n' +
        'Detail undangan dapat dilihat pada tautan berikut:\n' + link + '\n\n' +
        'Merupakan kehormatan bagi kami apabila Anda berkenan hadir. Terima kasih.';

    document.getElementById('btnKirimWa').href = 'https://wa.me/?text=' + encodeURIComponent(pesanWa);
    document.getElementById('hasilLink').classList.remove('hidden');
}

async function salinLinkTamu() {
    const output = document.getElementById('outputLink');
    const teks = output.value;
    let berhasil = false;

    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(teks);
            berhasil = true;
        } catch (e) {
        }
    }

    if (!berhasil && typeof document.execCommand === 'function') {
        const area = document.createElement('textarea');
        area.value = teks;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.left = '-9999px';
        area.style.top = '0';
        document.body.appendChild(area);
        area.select();
        area.setSelectionRange(0, area.value.length);
        try {
            berhasil = document.execCommand('copy');
        } catch (e) {
            berhasil = false;
        }
        area.remove();
    }

    if (berhasil) {
        alert('Link undangan berhasil disalin!');
    } else {
        output.focus();
        output.select();
        alert('Browser tidak mengizinkan penyalinan otomatis. Tekan Ctrl+C atau Salin secara manual.');
    }
}