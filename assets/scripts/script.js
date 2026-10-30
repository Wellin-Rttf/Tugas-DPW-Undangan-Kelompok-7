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

function tampilkanUcapan() {
  if (dataUcapan.length === 0) {
    daftarUcapan.innerHTML = '<p class="teks-kosong" style="text-align:center; font-style:italic; opacity:0.7;">Belum ada ucapan & konfirmasi kehadiran. Jadilah yang pertama!</p>';
    return;
  }

  daftarUcapan.innerHTML = dataUcapan.map(function (item) {
    return `
      <div class="kartu-ucapan">
        <div class="baris-nama">
          <strong>${bersihkanTeks(item.nama)}</strong>
          <span class="badge-rsvp-item">${bersihkanTeks(item.rsvp)} (${bersihkanTeks(item.jumlah)})</span>
        </div>
        <p>${bersihkanTeks(item.pesan)}</p>
        <small style="font-size: 0.7rem; color: #777; display:block; margin-top:0.4rem;">${bersihkanTeks(item.tanggal)}</small>
      </div>
    `;
  }).join('');
}


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
  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('tampil');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.animasi-muncul').forEach((el) => observer.observe(el));
}

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
