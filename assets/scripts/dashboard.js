const loginForm = document.getElementById('loginForm');
const usernameInput = document.getElementById('username');
const welcomeMessage = document.getElementById('welcomeMessage');
const btnLogout = document.getElementById('btnLogout');

const STORAGE_KEY = 'Username';

function showSection(sectionId) {
    const allSections = document.querySelectorAll('#loginSection, #dashboardSection, .page-section');
    allSections.forEach(section => {
        section.classList.add('hidden');
    });

    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.remove('hidden');
    }
}

function checkLoginStatus() {
    const savedUser = localStorage.getItem(STORAGE_KEY);

    if (savedUser) {
        welcomeMessage.textContent = `Selamat datang, Admin ${savedUser}`;
        showSection('dashboardSection');
    } else {
        showSection('loginSection');
    }
}

loginForm.addEventListener('submit', function(event) {
    event.preventDefault();
    const username = usernameInput.value.trim();

    if (username) {
        localStorage.setItem(STORAGE_KEY, username);
        loginForm.reset();
        checkLoginStatus();
    }
});

btnLogout.addEventListener('click', function() {
    localStorage.removeItem(STORAGE_KEY);
    checkLoginStatus();
});

function saveAndReturn() {
    if (typeof M !== 'undefined' && M.toast) {
        M.toast({ html: 'Perubahan berhasil disimpan!', displayLength: 2000 });
    } else {
        alert('Perubahan berhasil disimpan!');
    }
    showSection('dashboardSection');
}

function updateFileName(input) {
    const label = input.previousElementSibling;
    if (input.files && input.files[0] && label) {
        label.textContent = input.files[0].name;
        label.style.color = '#4E6151';
    }
}

document.addEventListener('DOMContentLoaded', checkLoginStatus);