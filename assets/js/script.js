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

    // util: hitung jarak Haversine (meter)
    function computeDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // meter
        const toRad = (v) => v * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // 2. Ambil Lokasi GPS
    function getGeolocation() {
        if (navigator.geolocation && gpsStatus) {
            gpsStatus.innerText = 'Mencari lokasi...';
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    if (latInput) latInput.value = position.coords.latitude;
                    if (lngInput) lngInput.value = position.coords.longitude;
                    let msg = `Lokasi ditemukan! Lat: ${position.coords.latitude.toFixed(4)}, Lng: ${position.coords.longitude.toFixed(4)}`;
                    // Jika konstanta kantor tersedia, hitung jarak dan tampilkan
                    if (typeof LAT_KANTOR !== 'undefined' && typeof LNG_KANTOR !== 'undefined' && typeof RADIUS_TOLERANSI_METER !== 'undefined') {
                        const dist = computeDistance(position.coords.latitude, position.coords.longitude, LAT_KANTOR, LNG_KANTOR);
                        msg += ` (Jarak ke kantor: ${dist.toFixed(1)} m)`;
                        if (dist > RADIUS_TOLERANSI_METER) {
                            gpsStatus.style.color = 'red';
                            msg += ' — Diluar area kantor';
                        } else {
                            gpsStatus.style.color = 'green';
                            msg += ' — Dalam area kantor';
                        }
                    }
                    gpsStatus.innerText = msg;
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
});