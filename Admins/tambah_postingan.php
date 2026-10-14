<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

include 'include/koneksi.php';

$active_page = 'postingan';
$pesan = '';

$judul = '';
$deskripsi = '';
$tanggal = date('Y-m-d');

if (empty($_SESSION['csrf_postingan'])) {
    $_SESSION['csrf_postingan'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $sumberMedia = $_POST['sumber_media'] ?? '';
    $jenisMedia = $_POST['jenis_media'] ?? '';
    $urlMedia = trim($_POST['url_media'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    $tanggalObj = DateTime::createFromFormat('!Y-m-d', $tanggal);

    if (
        !hash_equals($_SESSION['csrf_postingan'], $token)
    ) {
        $pesan = 'Permintaan tidak valid. Muat ulang halaman.';

    } elseif (
        $judul === '' ||
        $deskripsi === '' ||
        $tanggal === ''
    ) {
        $pesan = 'Judul, deskripsi, dan tanggal wajib diisi.';

    } elseif (
        !$tanggalObj ||
        $tanggalObj->format('Y-m-d') !== $tanggal
    ) {
        $pesan = 'Tanggal postingan tidak valid.';

    } elseif (
        !in_array($sumberMedia, ['upload', 'url'], true) ||
        !in_array($jenisMedia, ['gambar', 'video'], true)
    ) {
        $pesan = 'Pilihan sumber atau jenis media tidak valid.';

    } else {

        $media = '';
        $fileTersimpan = '';
        $valid = false;

        /*
         * MEDIA DARI URL
         */
        if ($sumberMedia === 'url') {

            $scheme = strtolower(
                parse_url($urlMedia, PHP_URL_SCHEME) ?? ''
            );

            if (
                filter_var($urlMedia, FILTER_VALIDATE_URL) &&
                in_array($scheme, ['http', 'https'], true)
            ) {
                $media = $urlMedia;
                $valid = true;
            } else {
                $pesan = 'Masukkan URL media HTTP atau HTTPS yang valid.';
            }

        /*
         * MEDIA DARI UPLOAD
         */
        } elseif ($sumberMedia === 'upload') {

            if (!isset($_FILES['media'])) {
                $pesan = 'Silakan pilih file media.';

            } else {

                $file = $_FILES['media'];

                if ($file['error'] !== UPLOAD_ERR_OK) {

                    if ($file['error'] === UPLOAD_ERR_INI_SIZE ||
                        $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                        $pesan = 'Ukuran file maksimal 10 MB.';
                    } elseif ($file['error'] === UPLOAD_ERR_NO_FILE) {
                        $pesan = 'Silakan pilih gambar atau video.';
                    } else {
                        $pesan = 'Upload gagal. Periksa konfigurasi hosting.';
                    }

                } else {

                    $tmpFile = $file['tmp_name'];
                    $ukuran = (int) $file['size'];
                    $ekstensi = strtolower(
                        pathinfo($file['name'], PATHINFO_EXTENSION)
                    );

                    // Maksimal 10 MB untuk gambar dan video.
                    $maksUkuran = 10 * 1024 * 1024;

                    $formatGambar = [
                        'jpg'  => ['image/jpeg'],
                        'jpeg' => ['image/jpeg'],
                        'png'  => ['image/png'],
                        'gif'  => ['image/gif'],
                        'webp' => ['image/webp']
                    ];

                    $formatVideo = [
                        'mp4'  => ['video/mp4', 'application/mp4'],
                        'webm' => ['video/webm'],
                        'ogg'  => ['video/ogg', 'application/ogg']
                    ];

                    $formatDiizinkan = $jenisMedia === 'gambar'
                        ? $formatGambar
                        : $formatVideo;

                    if (
                        !isset($formatDiizinkan[$ekstensi]) ||
                        !is_uploaded_file($tmpFile)
                    ) {
                        $pesan = 'Format file tidak sesuai dengan jenis media.';

                    } elseif (
                        $ukuran <= 0 ||
                        $ukuran > $maksUkuran
                    ) {
                        $pesan = 'Ukuran gambar atau video maksimal 10 MB.';

                    } else {

                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($tmpFile);

                        if (
                            !in_array(
                                $mime,
                                $formatDiizinkan[$ekstensi],
                                true
                            )
                        ) {
                            $pesan = 'Isi file tidak sesuai dengan formatnya.';

                        } else {

                            // Folder upload berada di dalam folder Admins.
                            $folderUpload = __DIR__ . '/postingan/';

                            if (
                                !is_dir($folderUpload) &&
                                !mkdir($folderUpload, 0755, true) &&
                                !is_dir($folderUpload)
                            ) {
                                $pesan = 'Folder upload gagal dibuat.';

                            } else {

                                $namaFile = bin2hex(random_bytes(16))
                                    . '.' . $ekstensi;

                                $pathTujuan = $folderUpload . $namaFile;

                                if (
                                    move_uploaded_file(
                                        $tmpFile,
                                        $pathTujuan
                                    )
                                ) {
                                    $media = $namaFile;
                                    $fileTersimpan = $pathTujuan;
                                    $valid = true;
                                } else {
                                    $pesan = 'File gagal disimpan ke hosting.';
                                }
                            }
                        }
                    }
                }
            }
        }

        /*
         * SIMPAN KE DATABASE
         */
        if ($valid) {

            $query = "INSERT INTO postingan (judul, deskripsi, media, jenis_media, tanggal) VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $query);

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssss',
                    $judul,
                    $deskripsi,
                    $media,
                    $jenisMedia,
                    $tanggal
                );

                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);
                    unset($_SESSION['csrf_postingan']);

                    header('Location: postingan.php?status=berhasil');
                    exit;

                } else {

                    $pesan = 'Postingan gagal disimpan ke database.';
                    mysqli_stmt_close($stmt);

                    if (
                        $fileTersimpan !== '' &&
                        is_file($fileTersimpan)
                    ) {
                        unlink($fileTersimpan);
                    }
                }

            } else {

                $pesan = 'Gagal menyiapkan query database.';

                if (
                    $fileTersimpan !== '' &&
                    is_file($fileTersimpan)
                ) {
                    unlink($fileTersimpan);
                }
            }
        }
    }
}

include 'include/header.php';
?>

<link rel="stylesheet" href="assets/css/postingan.css">

<section class="postingan halaman-tambah-postingan">

    <div class="form-header">
        <div>
            <h2>Tambah Postingan</h2>
            <p>Tambahkan informasi kegiatan dan media Lab Multimedia PTIK.</p>
        </div>

        <a href="postingan.php" class="btn-kembali">
            <i class="fa-solid fa-arrow-left"></i>
            Kembali
        </a>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="pesan-error" role="alert">
            <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form
        action=""
        method="POST"
        enctype="multipart/form-data"
        class="form-postingan"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_postingan'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
        >

        <div class="form-group">
            <label for="judul">Judul Postingan</label>

            <input
                type="text"
                id="judul"
                name="judul"
                maxlength="255"
                value="<?= htmlspecialchars(
                    $judul,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                placeholder="Masukkan judul postingan"
                required
            >
        </div>

        <div class="form-group">
            <label for="deskripsi">Deskripsi</label>

            <textarea
                id="deskripsi"
                name="deskripsi"
                rows="6"
                placeholder="Tuliskan deskripsi postingan"
                required
            ><?= htmlspecialchars(
                $deskripsi,
                ENT_QUOTES,
                'UTF-8'
            ); ?></textarea>
        </div>

        <div class="form-group">
            <label for="tanggal">Tanggal Postingan</label>

            <input
                type="date"
                id="tanggal"
                name="tanggal"
                value="<?= htmlspecialchars(
                    $tanggal,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="sumber_media">Sumber Media</label>

            <select id="sumber_media" name="sumber_media" required>
                <option value="upload">Upload File</option>
                <option value="url">URL Media</option>
            </select>
        </div>

        <div class="form-group">
            <label for="jenis_media">Jenis Media</label>

            <select id="jenis_media" name="jenis_media" required>
                <option value="gambar">Gambar</option>
                <option value="video">Video</option>
            </select>
        </div>

        <div class="form-group" id="grup_upload">
            <label for="media">Pilih Gambar atau Video</label>

            <input
                type="file"
                id="media"
                name="media"
                accept="image/jpeg,image/png,image/gif,image/webp"
            >

            <small id="keterangan_upload">
                Format gambar JPG, PNG, GIF, atau WEBP.
                Ukuran maksimal 10 MB.
            </small>
        </div>

        <div class="form-group" id="grup_url" hidden>
            <label for="url_media">URL Gambar atau Video</label>

            <input
                type="url"
                id="url_media"
                name="url_media"
                placeholder="https://contoh.com/gambar.jpg"
            >

            <small>
                Masukkan URL langsung menuju file gambar atau video.
            </small>
        </div>

        <div class="form-aksi">
            <button type="submit" class="btn-tambah">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan Postingan
            </button>

            <a href="postingan.php" class="btn-edit">
                Batal
            </a>
        </div>

    </form>

</section>

<script>
const sumberMedia = document.getElementById('sumber_media');
const jenisMedia = document.getElementById('jenis_media');
const grupUpload = document.getElementById('grup_upload');
const grupUrl = document.getElementById('grup_url');
const inputMedia = document.getElementById('media');
const inputUrl = document.getElementById('url_media');
const keteranganUpload = document.getElementById('keterangan_upload');

function aturFormMedia() {
    const gunakanUrl = sumberMedia.value === 'url';
    const gambar = jenisMedia.value === 'gambar';

    grupUpload.hidden = gunakanUrl;
    grupUrl.hidden = !gunakanUrl;

    inputMedia.required = !gunakanUrl;
    inputUrl.required = gunakanUrl;

    inputMedia.accept = gambar
        ? 'image/jpeg,image/png,image/gif,image/webp'
        : 'video/mp4,video/webm,video/ogg';

    inputUrl.placeholder = gambar
        ? 'https://contoh.com/gambar.jpg'
        : 'https://contoh.com/video.mp4';

    keteranganUpload.textContent = gambar
        ? 'Format gambar JPG, PNG, GIF, atau WEBP. Maksimal 10 MB.'
        : 'Format video MP4, WEBM, atau OGG. Maksimal 10 MB.';
}

sumberMedia.addEventListener('change', aturFormMedia);
jenisMedia.addEventListener('change', aturFormMedia);

aturFormMedia();
</script>

<?php
include 'include/footer.php';
?>