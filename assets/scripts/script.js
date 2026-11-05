const tombolBuka = document.getElementById('tombol-buka');
const sampul = document.getElementById('sampul');
const konten = document.getElementById('konten');
const formulirUcapan = document.getElementById('formulir-ucapan');
const inputNama = document.getElementById('input-nama');
const inputPesan = document.getElementById('input-pesan');
const inputJumlah = document.getElementById('input-jumlah');
const daftarUcapan = document.getElementById('daftar-ucapan');
const musikLatar = document.getElementById('musik-latar');
const tombolMusik = document.getElementById('tombol-musik');
const toastBox = document.getElementById('toast-box');

let dataUcapan = Array.isArray(window.DATA_UCAPAN) ? window.DATA_UCAPAN : [];

function bersihkanTeks(teks) {
  const el = document.createElement('div');
  el.textContent = teks;
  return el.innerHTML;
}

const UCAPAN_PER_HALAMAN = 5;
let halamanUcapan = 1;
const paginasiUcapan = document.getElementById('paginasi-ucapan');

function tampilkanUcapan() {
  const total = Math.max(1, Math.ceil(dataUcapan.length / UCAPAN_PER_HALAMAN));
  halamanUcapan = Math.min(halamanUcapan, total);

  if (dataUcapan.length === 0) {
    daftarUcapan.innerHTML = '<p class="teks-kosong">Belum ada ucapan & konfirmasi kehadiran. Jadilah yang pertama!</p>';
  } else {
    const awal = (halamanUcapan - 1) * UCAPAN_PER_HALAMAN;
    daftarUcapan.innerHTML = dataUcapan.slice(awal, awal + UCAPAN_PER_HALAMAN).map(function (item) {
      return `
      <div class="kartu-ucapan">
        <div class="baris-nama">
          <strong>${bersihkanTeks(item.nama)}</strong>
          <span class="badge-rsvp-item">${bersihkanTeks(item.rsvp)} (${bersihkanTeks(item.jumlah)})</span>
        </div>
        <p>${bersihkanTeks(item.pesan)}</p>
        <small class="tanggal-ucapan">${bersihkanTeks(item.tanggal)}</small>
      </div>
    `;
    }).join('');
  }

  tampilkanPaginasi(total);
}

function tampilkanPaginasi(total) {
  if (total === 1) {
    paginasiUcapan.innerHTML = '<button type="button" class="nonaktif" disabled aria-label="Halaman 1">1</button>';
    return;
  }

  let html = `<button type="button" data-halaman="${halamanUcapan - 1}" aria-label="Sebelumnya" ${halamanUcapan === 1 ? 'disabled class="nonaktif"' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
  for (let i = 1; i <= total; i++) {
    html += `<button type="button" data-halaman="${i}" ${i === halamanUcapan ? 'class="aktif" aria-current="page"' : ''}>${i}</button>`;
  }
  html += `<button type="button" data-halaman="${halamanUcapan + 1}" aria-label="Berikutnya" ${halamanUcapan === total ? 'disabled class="nonaktif"' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
  paginasiUcapan.innerHTML = html;
}

paginasiUcapan.addEventListener('click', function (e) {
  const tombol = e.target.closest('button[data-halaman]');
  if (!tombol || tombol.disabled) return;
  halamanUcapan = Number(tombol.dataset.halaman);
  tampilkanUcapan();
  daftarUcapan.parentElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
});

tombolBuka.addEventListener('click', function () {
  sessionStorage.setItem('undanganTerbuka', '1');
  sampul.style.transform = 'translateY(-100%)';

  if (musikLatar) {
    musikLatar.play().catch(() => {});
    tombolMusik.classList.remove('hidden');
  }

  setTimeout(function () {
    sampul.style.display = 'none';
    konten.style.display = 'block';
    document.body.classList.remove('kunci-scroll');
    window.scrollTo(0, 0);
    jalankanAnimasiMuncul();
  }, 700);
});

if (sessionStorage.getItem('undanganTerbuka') === '1') {
  sampul.style.display = 'none';
  konten.style.display = 'block';
  document.body.classList.remove('kunci-scroll');

  jalankanAnimasiMuncul();

  if (location.hash === '#formulir-ucapan') {
    setTimeout(() => {
      document.getElementById('formulir-ucapan')
        .scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 150);
  }
}

tombolMusik.addEventListener('click', function () {
  if (musikLatar.paused) {
    musikLatar.play().then(() => tombolMusik.classList.remove('paused')).catch(() => {});
  } else {
    musikLatar.pause();
    tombolMusik.classList.add('paused');
  }
});

const dropdownJumlah = document.getElementById('dropdown-jumlah');
const dropdownTombol = dropdownJumlah.querySelector('.dropdown-tombol');
const dropdownTeks = dropdownJumlah.querySelector('.dropdown-teks');
const opsiJumlah = dropdownJumlah.querySelectorAll('.dropdown-opsi');

function aturDropdown(buka) {
  dropdownJumlah.classList.toggle('terbuka', buka);
  dropdownTombol.setAttribute('aria-expanded', buka);
  if (buka) {
    setTimeout(() => dropdownJumlah.querySelector('.dropdown-daftar').scrollIntoView({ block: 'nearest', behavior: 'smooth' }), 60);
  }
}

function pilihJumlah(opsi) {
  inputJumlah.value = opsi.dataset.nilai;
  dropdownTeks.textContent = opsi.dataset.nilai;
  opsiJumlah.forEach(function (o) {
    o.classList.toggle('aktif', o === opsi);
    o.setAttribute('aria-selected', o === opsi);
  });
  aturDropdown(false);
}

dropdownTombol.addEventListener('click', () => aturDropdown(!dropdownJumlah.classList.contains('terbuka')));
opsiJumlah.forEach(function (opsi) {
  opsi.addEventListener('click', () => pilihJumlah(opsi));
  opsi.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pilihJumlah(opsi); }
    if (e.key === 'ArrowDown') { e.preventDefault(); (opsi.nextElementSibling || opsi).focus(); }
    if (e.key === 'ArrowUp') { e.preventDefault(); (opsi.previousElementSibling || opsi).focus(); }
  });
});
document.addEventListener('click', (e) => { if (!dropdownJumlah.contains(e.target)) aturDropdown(false); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') aturDropdown(false); });
formulirUcapan.addEventListener('reset', () => setTimeout(() => pilihJumlah(opsiJumlah[0]), 0));

function tampilkanToast(pesan) {
  toastBox.textContent = pesan;
  toastBox.classList.add('tampil');
  setTimeout(() => toastBox.classList.remove('tampil'), 3000);
}

async function salinKeClipboard(teks) {
  if (navigator.clipboard && window.isSecureContext) {
    try {
      await navigator.clipboard.writeText(teks);
      return true;
    } catch (e) {}
  }

  if (typeof document.execCommand !== 'function') return false;

  const area = document.createElement('textarea');
  area.value = teks;
  area.setAttribute('readonly', '');
  area.style.position = 'fixed';
  area.style.left = '-9999px';
  area.style.top = '0';
  document.body.appendChild(area);
  area.select();
  area.setSelectionRange(0, area.value.length);

  let berhasil = false;
  try { berhasil = document.execCommand('copy'); } catch (e) { berhasil = false; }
  area.remove();
  return berhasil;
}

async function salinTeks(elementId, pesanSukses) {
  const elemen = document.getElementById(elementId);
  if (!elemen) {
    tampilkanToast('Teks yang akan disalin tidak ditemukan.');
    return;
  }
  const teks = (elemen.textContent || '').trim();
  const berhasil = await salinKeClipboard(teks);
  tampilkanToast(berhasil ? pesanSukses : 'Gagal menyalin. Silakan salin teks secara manual.');
}

function simpanKeGoogleCalendar(jenis) {
  const acara = {
    akad: {
      judul: 'Akad Nikah Welling & Sheila',
      mulai: '20271212T080000',
      selesai: '20271212T100000',
      lokasi: 'Masjid Agung Ar-Rahman, Pekanbaru'
    },
    resepsi: {
      judul: 'Resepsi Pernikahan Welling & Sheila',
      mulai: '20271212T130000',
      selesai: '20271212T160000',
      lokasi: 'Hotel Pangeran, Pekanbaru'
    }
  };

  const data = acara[jenis];
  if (!data) return;

  const params = new URLSearchParams({
    action: 'TEMPLATE',
    text: data.judul,
    dates: `${data.mulai}/${data.selesai}`,
    details: 'Kami mengundang Anda untuk menghadiri acara pernikahan Welling & Sheila.',
    location: data.lokasi,
    ctz: 'Asia/Jakarta'
  });

  window.open(
    `https:`,
    '_blank',
    'noopener,noreferrer'
  );
}

function jalankanCountdown() {
  const tglAcara = new Date('2027-12-12T08:00:00+07:00').getTime();
  const elHari = document.getElementById('cd-hari');
  const elJam = document.getElementById('cd-jam');
  const elMenit = document.getElementById('cd-menit');
  const elDetik = document.getElementById('cd-detik');
  const dua = (n) => (n < 10 ? '0' + n : n);

  function hitung() {
    const selisih = tglAcara - Date.now();
    if (selisih <= 0) return;
    elHari.textContent = dua(Math.floor(selisih / 86400000));
    elJam.textContent = dua(Math.floor((selisih % 86400000) / 3600000));
    elMenit.textContent = dua(Math.floor((selisih % 3600000) / 60000));
    elDetik.textContent = dua(Math.floor((selisih % 60000) / 1000));
  }

  hitung();
  setInterval(hitung, 1000);
}

function jalankanAnimasiMuncul() {
  const daftar = document.querySelectorAll('.animasi-muncul');

  daftar.forEach(function (el) {
    const induk = el.parentElement;
    if (induk.id === 'konten' || induk.tagName === 'SECTION') return;
    const saudara = Array.from(induk.children).filter((c) => c.classList.contains('animasi-muncul'));
    el.style.setProperty('--jeda', Math.min(saudara.indexOf(el), 4) * 0.12 + 's');
  });

  if (!('IntersectionObserver' in window)) {
    daftar.forEach((el) => el.classList.add('tampil'));
    return;
  }

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('tampil');
      } else if (entry.target.getBoundingClientRect().top > 0) {
        entry.target.classList.remove('tampil');
      }
    });
  }, { threshold: 0, rootMargin: '0px 0px -8% 0px' });

  daftar.forEach((el) => observer.observe(el));
}

const tombolHadiah = document.getElementById('tombol-hadiah');
const panelHadiah = document.getElementById('panel-hadiah');
const teksTombolHadiah = document.getElementById('teks-tombol-hadiah');

let timerTutupHadiah;
tombolHadiah.addEventListener('click', function () {
  const buka = !panelHadiah.classList.contains('terbuka');
  clearTimeout(timerTutupHadiah);

  tombolHadiah.classList.toggle('aktif', buka);
  tombolHadiah.setAttribute('aria-expanded', buka);
  teksTombolHadiah.textContent = buka ? 'Sembunyikan Rekening' : 'Tampilkan Rekening';

  if (buka) {
    panelHadiah.hidden = false;
    panelHadiah.classList.remove('menutup');
    void panelHadiah.offsetWidth;
    panelHadiah.classList.add('terbuka');
  } else {
    panelHadiah.classList.remove('terbuka');
    panelHadiah.classList.add('menutup');
    timerTutupHadiah = setTimeout(function () {
      panelHadiah.hidden = true;
      panelHadiah.classList.remove('menutup');
    }, 350);
  }
});

const lightboxModal = document.getElementById('lightbox-modal');
const lightboxImg = document.getElementById('lightbox-img');
const lightboxClose = document.getElementById('lightbox-close');
const lightboxPrev = document.getElementById('lightbox-prev');
const lightboxNext = document.getElementById('lightbox-next');
const galleryItems = document.querySelectorAll('#galeri-foto img');

let currentImgIndex = 0;
let elemenPemicuLightbox = null;

function tampilkanFoto() {
  const img = galleryItems[currentImgIndex];
  lightboxImg.src = img.src;
  lightboxImg.alt = img.alt;
}

function openLightbox(index, pemicu) {
  if (galleryItems.length === 0) return;

  currentImgIndex = index;
  elemenPemicuLightbox = pemicu || document.activeElement;
  tampilkanFoto();
  lightboxModal.inert = false;
  lightboxModal.setAttribute('aria-hidden', 'false');
  lightboxModal.classList.add('aktif');
  lightboxClose.focus({ preventScroll: true });
}

function closeLightbox() {
  lightboxModal.classList.remove('aktif');
  lightboxModal.setAttribute('aria-hidden', 'true');
  lightboxModal.inert = true;

  if (elemenPemicuLightbox && elemenPemicuLightbox.isConnected) {
    elemenPemicuLightbox.focus({ preventScroll: true });
  }
  elemenPemicuLightbox = null;
}

function fotoBerikutnya() {
  if (galleryItems.length === 0) return;
  currentImgIndex = (currentImgIndex + 1) % galleryItems.length;
  tampilkanFoto();
}

function fotoSebelumnya() {
  if (galleryItems.length === 0) return;
  currentImgIndex = (currentImgIndex - 1 + galleryItems.length) % galleryItems.length;
  tampilkanFoto();
}

galleryItems.forEach((img, index) => {
  const pemicu = img.parentElement;

  pemicu.addEventListener('click', () => openLightbox(index, pemicu));
  pemicu.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      openLightbox(index, pemicu);
    }
  });
});

lightboxClose.addEventListener('click', closeLightbox);
lightboxNext.addEventListener('click', fotoBerikutnya);
lightboxPrev.addEventListener('click', fotoSebelumnya);

lightboxModal.addEventListener('click', (e) => {
  if (e.target === lightboxModal) closeLightbox();
});

document.addEventListener('keydown', (e) => {
  if (!lightboxModal.classList.contains('aktif')) return;

  if (e.key === 'Escape') { e.preventDefault(); closeLightbox(); return; }
  if (e.key === 'ArrowRight') { e.preventDefault(); fotoBerikutnya(); return; }
  if (e.key === 'ArrowLeft') { e.preventDefault(); fotoSebelumnya(); return; }

  if (e.key === 'Tab') {
    const elemenFokus = Array.from(lightboxModal.querySelectorAll('button:not([disabled])'));
    if (elemenFokus.length === 0) return;

    const pertama = elemenFokus[0];
    const terakhir = elemenFokus[elemenFokus.length - 1];
    if (e.shiftKey && document.activeElement === pertama) {
      e.preventDefault();
      terakhir.focus();
    } else if (!e.shiftKey && document.activeElement === terakhir) {
      e.preventDefault();
      pertama.focus();
    }
  }
});

let sentuhAwalX = 0;
lightboxModal.addEventListener('touchstart', (e) => {
  sentuhAwalX = e.changedTouches[0].clientX;
}, { passive: true });

lightboxModal.addEventListener('touchend', (e) => {
  const selisih = e.changedTouches[0].clientX - sentuhAwalX;
  if (Math.abs(selisih) < 50) return;
  if (selisih < 0) fotoBerikutnya(); else fotoSebelumnya();
}, { passive: true });

jalankanCountdown();
tampilkanUcapan();

if (window.PESAN_STATUS) tampilkanToast(window.PESAN_STATUS);

const TARGET_SENTUH = '.acara-card, .profil-mempelai-card, .timeline-item, .amplop-card, .radio-card, .galeri-item, .ig-mempelai, .tombol, .tombol-salin, .paginasi button';
let elemenSentuh = null;
let timerSentuh;

function lepasSentuh(segera) {
  const el = elemenSentuh;
  elemenSentuh = null;
  if (!el) return;
  clearTimeout(timerSentuh);
  if (segera) el.classList.remove('sentuh');
  else timerSentuh = setTimeout(() => el.classList.remove('sentuh'), 450);
}

document.addEventListener('touchstart', function (e) {
  const el = e.target.closest(TARGET_SENTUH);
  if (!el) return;
  lepasSentuh(true);
  elemenSentuh = el;
  el.classList.add('sentuh');
}, { passive: true });

document.addEventListener('touchmove', () => lepasSentuh(true), { passive: true });
document.addEventListener('touchend', () => lepasSentuh(false), { passive: true });
document.addEventListener('touchcancel', () => lepasSentuh(true), { passive: true });