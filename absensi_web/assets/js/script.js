// script.js

// Fungsi toggle sidebar (untuk admin desktop)
function toggleSidebar() {
    // Pastikan ID 'adminSidebar' digunakan di admin_sidebar.php
    const sidebar = document.getElementById('adminSidebar'); 
    const content = document.querySelector('.content'); 
    
    if (!sidebar || !content) return;

    sidebar.classList.toggle('closed');
    
    // Sesuaikan margin konten agar cocok dengan CSS .closed
    if (sidebar.classList.contains('closed')) {
        content.style.marginLeft = '50px';
    } else {
        content.style.marginLeft = '270px'; // Lebar sidebar 250px + 20px padding
    }
}

// Fungsionalitas Absensi Karyawan (GPS dan Kamera)
function initAbsensi() {
    const video = document.getElementById('webcam_video');
    const canvas = document.getElementById('webcam_canvas');
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const photoInput = document.getElementById('photo_data');
    const gpsStatus = document.getElementById('gps_status');
    
    // 1. Akses Kamera
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && video) {
        navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } })
            .then((stream) => {
                video.srcObject = stream;
            })
            .catch((err) => {
                console.error("Gagal akses kamera: ", err);
                alert("Akses kamera ditolak atau perangkat tidak mendukung.");
            });
    }

    // 2. Ambil Lokasi GPS
    function getGeolocation() {
        if (navigator.geolocation && gpsStatus) {
            gpsStatus.innerText = 'Mencari lokasi...';
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    if (latInput) latInput.value = position.coords.latitude;
                    if (lngInput) lngInput.value = position.coords.longitude;
                    gpsStatus.innerText = `Lokasi ditemukan! Lat: ${position.coords.latitude.toFixed(4)}, Lng: ${position.coords.longitude.toFixed(4)}`;
                },
                (error) => {
                    gpsStatus.innerText = `Gagal akses lokasi: ${error.message}. Mohon aktifkan GPS Anda.`;
                },
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        } else if (gpsStatus) {
            gpsStatus.innerText = 'Geolocation tidak didukung browser ini.';
        }
    }
    
    // Panggil Geolocation
    if (video) getGeolocation();
    
    // 3. Fungsi Ambil Foto dan Submit
    window.takePhotoAndSubmit = function(formId) {
        // Cek apakah lokasi sudah didapat
        if (!latInput || !lngInput || !latInput.value || !lngInput.value) {
            alert("Harap tunggu hingga lokasi GPS terdeteksi.");
            getGeolocation(); // Coba lagi
            return;
        }

        // Ambil frame dari video ke canvas
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Konversi canvas ke base64 (DataURL)
        const photoDataURL = canvas.toDataURL('image/png');
        photoInput.value = photoDataURL;
        
        // Submit Form
        document.getElementById(formId).submit();
    }
}

// Inisialisasi
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi Absensi jika elemen ada
    if (document.getElementById('webcam_video')) {
        initAbsensi();
    }
    
    // Update Jam Realtime di dashboard karyawan
    const clockElement = document.getElementById('realtime_clock');
    if (clockElement) {
        setInterval(() => {
            const now = new Date();
            clockElement.innerText = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }, 1000);
    }

    // Panggil inisialisasi awal toggle (untuk memastikan margin konten benar saat load)
    const sidebar = document.getElementById('adminSidebar');
    const content = document.querySelector('.content');

    if (sidebar && content) {
        // Atur margin awal konten (jika tidak ada kelas 'closed')
        if (!sidebar.classList.contains('closed')) {
            content.style.marginLeft = '270px';
        } else {
             content.style.marginLeft = '50px';
        }
    }
    // Disable absen buttons until ready
    const absenButtons = document.querySelectorAll('button[data-video]');
    absenButtons.forEach(b => { b.disabled = true; b.classList.add('disabled'); });
    // initial check in case initWebcam/initGPS already set readiness
    setTimeout(updateAbsensiButtons, 500);
});



// Function untuk mengambil foto dan submit form
function takePhotoAndSubmit(formId, videoId, canvasId, photoDataId, latId, lngId, gpsStatusId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);
    const latEl = document.getElementById(latId);
    const lngEl = document.getElementById(lngId);
    const gpsStatus = document.getElementById(gpsStatusId);

    console.log('takePhotoAndSubmit called', { formId, videoId, canvasId, photoDataId, latId, lngId });

    if (!video) {
        alert('Elemen video tidak ditemukan di halaman.');
        return;
    }
    if (!canvas) {
        alert('Elemen canvas tidak ditemukan di halaman.');
        return;
    }

    // Pastikan GPS tersedia
    if (!latEl || !lngEl || !latEl.value || !lngEl.value) {
        if (gpsStatus) gpsStatus.innerText = 'Menunggu GPS...';
        // Coba inisialisasi GPS sekali
        try { initGPS(latId, lngId, gpsStatusId); } catch (e) { console.warn('initGPS error', e); }
        alert('Harap tunggu hingga lokasi GPS terdeteksi, lalu coba lagi.');
        return;
    }

    // Jika belum ada stream, coba inisialisasi webcam lalu ulangi setelah delay singkat
    if (!video.srcObject) {
        console.log('Video srcObject kosong — mencoba inisialisasi webcam.');
        try { initWebcam(videoId); } catch (e) { console.warn('initWebcam error', e); }

        // Tunggu sejenak agar getUserMedia bisa memberikan stream
        setTimeout(() => {
            if (video.srcObject) {
                console.log('Stream tersedia setelah inisialisasi, lanjut capture.');
                takePhotoAndSubmit(formId, videoId, canvasId, photoDataId, latId, lngId, gpsStatusId);
            } else {
                alert('Gagal mengakses kamera. Pastikan browser mengizinkan akses kamera dan tidak ada aplikasi lain yang menggunakannya.');
            }
        }, 1200);
        return;
    }

    // Ambil foto dari video stream
    canvas.width = video.videoWidth || 320;
    canvas.height = video.videoHeight || 240;
    const ctx = canvas.getContext('2d');
    try {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    } catch (err) {
        console.error('Gagal drawImage:', err);
        alert('Gagal mengambil gambar dari kamera. Coba refresh halaman dan pastikan kamera tersedia.');
        return;
    }

    // Simpan data foto ke hidden field
    const dataUrl = canvas.toDataURL('image/png');
    const photoField = document.getElementById(photoDataId);
    if (photoField) photoField.value = dataUrl;

    // Hentikan stream kamera setelah selesai (opsional)
    try { if (video.srcObject && video.srcObject.getTracks) video.srcObject.getTracks().forEach(track => track.stop()); } catch (e) { console.warn('Stop stream failed', e); }

    // Submit form
    const form = document.getElementById(formId);
    if (form) form.submit();
    else alert('Form tidak ditemukan, gagal submit.');
}

// Function untuk inisialisasi webcam
function initWebcam(videoId) {
    const video = document.getElementById(videoId);
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && video) {
        navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } })
            .then((stream) => {
                video.srcObject = stream;
                // tandai video siap
                try { video.dataset.ready = '1'; } catch (e) { /* noop */ }
                updateAbsensiButtons();
            })
            .catch((err) => {
                console.error("Gagal akses webcam:", err);
                // Tambahkan fallback UI jika perlu
            });
    }
}

// Function untuk inisialisasi GPS
function initGPS(latId, lngId, gpsStatusId) {
    const gpsStatus = document.getElementById(gpsStatusId);
    if (navigator.geolocation && gpsStatus) {
        navigator.geolocation.getCurrentPosition((pos) => {
            document.getElementById(latId).value = pos.coords.latitude;
            document.getElementById(lngId).value = pos.coords.longitude;
            gpsStatus.innerText = 'Lokasi ditemukan!';
            gpsStatus.style.color = 'green';
            // tandai GPS siap
            try { document.getElementById(latId).dataset.ready = '1'; } catch (e) { }
            updateAbsensiButtons();
        }, (err) => {
            console.error("Gagal akses lokasi:", err);
            gpsStatus.innerText = 'Gagal akses lokasi!';
            gpsStatus.style.color = 'red';
        });
    }
}

// Update semua tombol absensi berdasarkan kesiapan video+GPS
function updateAbsensiButtons() {
    const buttons = document.querySelectorAll('button[data-video]');
    buttons.forEach(btn => {
        const videoId = btn.getAttribute('data-video');
        const latId = btn.getAttribute('data-lat');
        const videoEl = document.getElementById(videoId);
        const latEl = document.getElementById(latId);

        const videoReady = videoEl && videoEl.dataset && videoEl.dataset.ready === '1' && videoEl.srcObject;
        const latReady = latEl && (latEl.value && latEl.value.length > 0);

        if (videoReady && latReady) {
            btn.disabled = false;
            btn.classList.remove('disabled');
        } else {
            btn.disabled = true;
            btn.classList.add('disabled');
        }
    });
}

    // --- Modal Izin Sakit ---
    function openIzinModal() {
        const overlay = document.getElementById('izinModalOverlay');
        if (overlay) overlay.classList.add('active');
    }

    function closeIzinModal() {
        const overlay = document.getElementById('izinModalOverlay');
        if (overlay) overlay.classList.remove('active');
    }

    // Validasi sederhana sebelum submit
    function validateIzinForm() {
        const mulai = document.getElementById('tanggal_mulai');
        const akhir = document.getElementById('tanggal_akhir');
        const keterangan = document.getElementById('keterangan_izin');
        if (!mulai.value) { alert('Pilih tanggal mulai izin.'); mulai.focus(); return false; }
        if (!akhir.value) { alert('Pilih tanggal akhir izin.'); akhir.focus(); return false; }
        if (new Date(akhir.value) < new Date(mulai.value)) { alert('Tanggal akhir tidak boleh sebelum tanggal mulai.'); akhir.focus(); return false; }
        if (!keterangan.value.trim()) { alert('Tuliskan alasan / keterangan izin.'); keterangan.focus(); return false; }
        return true;
    }

// Expose functions to global scope for inline handlers
try {
    window.openIzinModal = openIzinModal;
    window.closeIzinModal = closeIzinModal;
    window.validateIzinForm = validateIzinForm;
    window.takePhotoAndSubmit = takePhotoAndSubmit;
} catch (e) {
    console.warn('Could not attach functions to window:', e);
}


// --- INISIALISASI WEBCAM ---
function initWebcam(videoId, canvasId) {
    const video = document.getElementById(videoId);

    navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => {
        video.srcObject = stream;
    })
    .catch(err => {
        alert("Gagal mengakses kamera: " + err);
    });
}

// --- INISIALISASI GPS ---
function initGPS(latId, lngId, statusId) {
    const status = document.getElementById(statusId);

    if (!navigator.geolocation) {
        status.textContent = "GPS tidak tersedia di browser ini.";
        status.style.color = "red";
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            document.getElementById(latId).value = pos.coords.latitude;
            document.getElementById(lngId).value = pos.coords.longitude;
            status.textContent = "GPS Terdeteksi ✓";
            status.style.color = "green";
        },
        (err) => {
            status.textContent = "Gagal mendeteksi GPS!";
            status.style.color = "red";
        }
    );
}

// --- AMBIL FOTO & SUBMIT FORM ---
function takePhotoAndSubmit(formId, videoId, canvasId, photoId, latId, lngId, gpsStatusId) {
    const video = document.getElementById(videoId);
    const canvas = document.getElementById(canvasId);
    const photoInput = document.getElementById(photoId);

    // Cek GPS dulu
    if (document.getElementById(latId).value === "" ||
        document.getElementById(lngId).value === "") {
        alert("GPS belum terdeteksi! Tunggu beberapa detik.");
        return;
    }

    // Ambil foto dari webcam
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);

    // Convert foto ke Base64
    const dataURL = canvas.toDataURL("image/png");
    photoInput.value = dataURL;

    // Submit form
    document.getElementById(formId).submit();
}
