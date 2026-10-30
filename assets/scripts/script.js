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

// Data awal ucapan dinamis dari LocalStorage (Kosong secara default jika belum ada input)
let dataUcapan = [];

if (localStorage.getItem('ucapanPernikahan')) {
  dataUcapan = JSON.parse(localStorage.getItem('ucapanPernikahan'));
}

function tampilkanUcapan() {
  if (dataUcapan.length === 0) {
    daftarUcapan.innerHTML = '<p class="teks-kosong" style="text-align:center; font-style:italic; opacity:0.7;">Belum ada ucapan & konfirmasi kehadiran. Jadilah yang pertama!</p>';
    return;
  }

  let htmlUcapan = '';
  dataUcapan.forEach(function(item) {
    htmlUcapan += `
      <div class="kartu-ucapan">
        <div class="baris-nama">
          <strong>${item.nama}</strong>
          <span class="badge-rsvp-item">${item.rsvp} (${item.jumlah})</span>
        </div>
        <p>${item.pesan}</p>
        <small style="font-size: 0.7rem; color: #777; display:block; margin-top:0.4rem;">${item.tanggal}</small>
      </div>
    `;
  });

  daftarUcapan.innerHTML = htmlUcapan;
}

// Buka Undangan & Autoplay Musik
tombolBuka.addEventListener('click', function() {
  sampul.style.transform = 'translateY(-100%)';

  if (musikLatar) {
    musikLatar.play().catch(e => console.log('Autoplay ditahan browser:', e));
    tombolMusik.classList.remove('hidden');
  }

  setTimeout(function() {
    sampul.style.display = 'none';
    konten.style.display = 'block';
    document.body.classList.remove('kunci-scroll');
    window.scrollTo(0, 0);

    jalankanAnimasiMuncul();
  }, 700);
});

// Play/Pause Musik Latar
tombolMusik.addEventListener('click', function() {
  if (musikLatar.paused) {
    musikLatar.play();
    tombolMusik.classList.remove('paused');
  } else {
    musikLatar.pause();
    tombolMusik.classList.add('paused');
  }
});

// Toast Notifikasi
function tampilkanToast(pesan) {
  toastBox.textContent = pesan;
  toastBox.classList.add('tampil');
  setTimeout(() => {
    toastBox.classList.remove('tampil');
  }, 3000);
}

// Salin Teks Rekening
function salinTeks(elementId, pesanSukses) {
  const teks = document.getElementById(elementId).innerText;
  navigator.clipboard.writeText(teks).then(() => {
    tampilkanToast(pesanSukses);
  });
}

// Fitur Save The Date (Google Calendar + Download .ics)
tombolSaveDate.addEventListener('click', function() {
  // Google Calendar URL
  const gCalUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=Pernikahan+Welling+%26+Sheila&dates=20271212T080000/20271212T160000&details=Acara+Pernikahan+Welling+%26+Sheila.&location=Hotel+Pangeran+Pekanbaru";
  window.open(gCalUrl, '_blank');

  tampilkanToast('Membuka Kalender Google...');
});

// Form Submit RSVP & Ucapan
formulirUcapan.addEventListener('submit', function(e) {
  e.preventDefault();

  const nama = inputNama.value.trim();
  const pesan = inputPesan.value.trim();
  const jumlah = inputJumlah.value;
  const rsvp = document.querySelector('input[name="rsvp"]:checked').value;

  if (nama !== '' && pesan !== '') {
    const tanggalHariIni = new Date().toLocaleDateString('id-ID', {
      day: 'numeric', month: 'short', year: 'numeric'
    });

    dataUcapan.unshift({
      nama: nama,
      pesan: pesan,
      rsvp: rsvp,
      jumlah: jumlah,
      tanggal: tanggalHariIni
    });

    localStorage.setItem('ucapanPernikahan', JSON.stringify(dataUcapan));

    tampilkanUcapan();
    formulirUcapan.reset();

    tampilkanToast('Konfirmasi & ucapan Anda berhasil dikirim!');
  }
});

// Countdown Timer Logic
function jalankanCountdown() {
  const tglAcara = new Date('December 12, 2027 08:00:00').getTime();

  setInterval(function() {
    const sekarang = new Date().getTime();
    const selisih = tglAcara - sekarang;

    if (selisih > 0) {
      const hari = Math.floor(selisih / (1000 * 60 * 60 * 24));
      const jam = Math.floor((selisih % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const menit = Math.floor((selisih % (1000 * 60 * 60)) / (1000 * 60));
      const detik = Math.floor((selisih % (1000 * 60)) / 1000);

      document.getElementById('cd-hari').textContent = hari < 10 ? '0' + hari : hari;
      document.getElementById('cd-jam').textContent = jam < 10 ? '0' + jam : jam;
      document.getElementById('cd-menit').textContent = menit < 10 ? '0' + menit : menit;
      document.getElementById('cd-detik').textContent = detik < 10 ? '0' + detik : detik;
    }
  }, 1000);
}

// Scroll Reveal Observer
function jalankanAnimasiMuncul() {
  const elemenAnimasi = document.querySelectorAll('.animasi-muncul');

  const observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('tampil');
      }
    });
  }, { threshold: 0.1 });

  elemenAnimasi.forEach(function(el) {
    observer.observe(el);
  });
}

// Lightbox Gallery Slider
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
  img.parentElement.addEventListener('click', () => {
    openLightbox(index);
  });
});

function openLightbox(index) {
  currentImgIndex = index;
  lightboxImg.src = imageList[currentImgIndex];
  lightboxModal.classList.add('aktif');
}

function closeLightbox() {
  lightboxModal.classList.remove('aktif');
}

lightboxClose.addEventListener('click', closeLightbox);
lightboxNext.addEventListener('click', () => {
  currentImgIndex = (currentImgIndex + 1) % imageList.length;
  lightboxImg.src = imageList[currentImgIndex];
});
lightboxPrev.addEventListener('click', () => {
  currentImgIndex = (currentImgIndex - 1 + imageList.length) % imageList.length;
  lightboxImg.src = imageList[currentImgIndex];
});

jalankanCountdown();
tampilkanUcapan();