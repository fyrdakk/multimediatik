<?php 
$active_page = 'home';
include 'include/header.php'; 
?>

<section class="home-section">
    <div class="container">
        <div class="slider-container">
            <div class="slider" id="slider">
                <div class="slide active">
                    <img src="asset/img/podcast.jpeg" alt="Podcast Room">
                    <div class="slide-caption">Podcast Room</div>
                </div>
                <div class="slide">
                    <img src="asset/img/control_room.jpeg" alt="Control Room">
                    <div class="slide-caption">Control Room</div>
                </div>
                <div class="slide">
                    <img src="asset/img/studio_photo.jpeg" alt="Studio Photo">
                    <div class="slide-caption">Studio Photo</div>
                </div>
                <div class="slide">
                    <img src="asset/img/leader_room.jpeg" alt="LAB Head's Room">
                    <div class="slide-caption">LAB Head's Room</div>
                </div>
            </div>

            <button class="slider-btn prev" id="prevBtn"><i class="fa-solid fa-chevron-left"></i></button>
            <button class="slider-btn next" id="nextBtn"><i class="fa-solid fa-chevron-right"></i></button>

            <div class="slider-dots" id="sliderDots"></div>
        </div>

        <div class="rules-section">
            <h2>TATA TERTIB PENGGUNAAN LAB MULTIMEDIA PTIK</h2>
            <hr class="divider">
            <ul class="rules-list">
                <li><i class="fa-solid fa-check-circle"></i> Tata tertib berlaku bagi semua pengguna Laboratorium termasuk dosen dan mahasiswa.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna yang meminjam peralatan Laboratorium harus memenuhi ketentuan peminjaman dan pengembalian.</li>
                <li><i class="fa-solid fa-check-circle"></i> Mahasiswa wajib menggunakan baju praktikum saat masuk ke Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Mahasiswa dilarang masuk ke Laboratorium jika dosen atau asisten lab tidak berada di dalam Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna harus menjaga ketenangan dengan tidak menimbulkan suara yang mengganggu sekitarnya.</li>
                <li><i class="fa-solid fa-check-circle"></i> Dilarang makan, minum, dan/atau merokok di dalam Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna harus menjaga kebersihan di dalam ruangan Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna dilarang mengubah setting jenis apapun yang menyangkut setting komputer yang ada di dalam Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna dilarang memasukkan jenis data atau program apapun ke dalam komputer tanpa seijin pihak Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna dilarang menghapus atau memindahkan data atau software apapun di komputer.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna dilarang memainkan game apapun di dalam Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna wajib merapikan kembali semua peralatan Laboratorium yang digunakan.</li>
                <li><i class="fa-solid fa-check-circle"></i> Pengguna harus melapor kepada petugas Laboratorium setiap kali selesai menggunakan fasilitas Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Dilarang membawa peralatan Laboratorium keluar ruangan Laboratorium tanpa izin dari Laboran atau Ketua Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Dilarang membawa pulang peralatan Laboratorium.</li>
                <li><i class="fa-solid fa-check-circle"></i> Jika ada kehilangan atau kerusakan sebagian atau seluruh perangkat Laboratorium maka pengunjung yang merusak atau menghilangkan wajib melapor ke laboran dan pengguna harus mengganti dengan perangkat yang sama.</li>
                <li><i class="fa-solid fa-check-circle"></i> Jika tidak ada yang melapor telah menghilangkan atau merusak perangkat Laboratorium, maka semua mahasiswa yang mengunjungi Laboratorium wajib mengganti 2 kali lipatnya.</li>
                <li><i class="fa-solid fa-check-circle"></i> Petugas Laboratorium Komputer tidak bertanggungjawab atas kehilangan barang-barang pribadi pengguna selama berada di Laboratorium Komputer.</li>
            </ul>
        </div>
    </div>
</section>

<?php include 'include/footer.php'; ?>