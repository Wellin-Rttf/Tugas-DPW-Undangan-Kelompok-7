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
const tombolSaveDate = document.getElementById('tombol-save-date');

let dataUcapan = [];

try {
  dataUcapan = JSON.parse(localStorage.getItem('ucapanPernikahan')) || [];
} catch (e) {
  dataUcapan = [];
}

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
  // belum lebih dari 1 halaman: nomor 1 saja, diabu-abukan
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
  sampul.style.transform = 'translateY(-100%)';

  if (musikLatar) {
    musikLatar.play().catch(e => console.log('Autoplay ditahan browser:', e));
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

function salinTeks(elementId, pesanSukses) {
  const teks = document.getElementById(elementId).innerText;
  navigator.clipboard.writeText(teks)
    .then(() => tampilkanToast(pesanSukses))
    .catch(() => tampilkanToast('Gagal menyalin, silakan salin manual.'));
}

tombolSaveDate.addEventListener('click', function () {
  const gCalUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=Pernikahan+Welling+%26+Sheila&dates=20271212T080000/20271212T160000&details=Acara+Pernikahan+Welling+%26+Sheila.&location=Hotel+Pangeran+Pekanbaru";
  window.open(gCalUrl, '_blank');
  tampilkanToast('Membuka Kalender Google...');
});

formulirUcapan.addEventListener('submit', function (e) {
  e.preventDefault();

  const nama = inputNama.value.trim();
  const pesan = inputPesan.value.trim();
  const jumlah = inputJumlah.value;
  const rsvp = document.querySelector('input[name="rsvp"]:checked').value;

  if (nama !== '' && pesan !== '') {
    const tanggalHariIni = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'short', year: 'numeric'
    });

    halamanUcapan = 1;
    dataUcapan.unshift({ nama, pesan, rsvp, jumlah, tanggal: tanggalHariIni });
    localStorage.setItem('ucapanPernikahan', JSON.stringify(dataUcapan));

    tampilkanUcapan();
    formulirUcapan.reset();
    tampilkanToast('Konfirmasi & ucapan Anda berhasil dikirim!');
  }
});

function jalankanCountdown() {
  const tglAcara = new Date('December 12, 2027 08:00:00').getTime();
  const dua = (n) => (n < 10 ? '0' + n : n);

  function hitung() {
    const selisih = tglAcara - Date.now();
    if (selisih <= 0) return;

    document.getElementById('cd-hari').textContent = dua(Math.floor(selisih / 86400000));
    document.getElementById('cd-jam').textContent = dua(Math.floor((selisih % 86400000) / 3600000));
    document.getElementById('cd-menit').textContent = dua(Math.floor((selisih % 3600000) / 60000));
    document.getElementById('cd-detik').textContent = dua(Math.floor((selisih % 60000) / 1000));
  }

  hitung();
  setInterval(hitung, 1000);
}

function jalankanAnimasiMuncul() {
  const daftar = document.querySelectorAll('.animasi-muncul');

  // jeda bertahap untuk elemen bersaudara (kartu dalam satu grid)
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

  // muncul ke atas setiap kali elemen masuk dari bawah layar saat scroll
  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('tampil');
      } else if (entry.boundingClientRect.top > 0) {
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
const imageList = [];

galleryItems.forEach((img, index) => {
  imageList.push(img.src);
  img.parentElement.addEventListener('click', () => openLightbox(index));
});

function tampilkanFoto() {
  lightboxImg.src = imageList[currentImgIndex];
}

function openLightbox(index) {
  currentImgIndex = index;
  tampilkanFoto();
  lightboxModal.classList.add('aktif');
}

function closeLightbox() {
  lightboxModal.classList.remove('aktif');
}

function fotoBerikutnya() {
  currentImgIndex = (currentImgIndex + 1) % imageList.length;
  tampilkanFoto();
}

function fotoSebelumnya() {
  currentImgIndex = (currentImgIndex - 1 + imageList.length) % imageList.length;
  tampilkanFoto();
}

lightboxClose.addEventListener('click', closeLightbox);
lightboxNext.addEventListener('click', fotoBerikutnya);
lightboxPrev.addEventListener('click', fotoSebelumnya);

lightboxModal.addEventListener('click', (e) => {
  if (e.target === lightboxModal) closeLightbox();
});

document.addEventListener('keydown', (e) => {
  if (!lightboxModal.classList.contains('aktif')) return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowRight') fotoBerikutnya();
  if (e.key === 'ArrowLeft') fotoSebelumnya();
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

// Efek "hover" di layar sentuh: kelas .sentuh ditambahkan saat elemen disentuh
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
