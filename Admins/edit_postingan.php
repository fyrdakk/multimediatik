
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

include 'include/koneksi.php';

$active_page = 'postingan';
$pesan = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: postingan.php");
    exit;
}

// Ambil data postingan.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, judul, deskripsi, media, jenis_media, tanggal
     FROM postingan
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$post) {
    header("Location: postingan.php?status=tidak_ditemukan");
    exit;
}

// Data awal.
$judul = $post['judul'];
$deskripsi = $post['deskripsi'];
$tanggal = $post['tanggal'];
$mediaLama = $post['media'];
$jenisMedia = $post['jenis_media'];

// Mendukung format data media lama.
if (strpos($mediaLama, 'gambar:') === 0) {
    $jenisMedia = 'gambar';
    $mediaLama = substr($mediaLama, 7);
} elseif (strpos($mediaLama, 'video:') === 0) {
    $jenisMedia = 'video';
    $mediaLama = substr($mediaLama, 6);
}

$mediaLama = trim($mediaLama);

$schemeLama = strtolower(
    parse_url($mediaLama, PHP_URL_SCHEME) ?? ''
);

$sumberAwal = (
    filter_var($mediaLama, FILTER_VALIDATE_URL) &&
    in_array($schemeLama, ['http', 'https'], true)
) ? 'url' : 'upload';

if (empty($_SESSION['csrf_edit_postingan'])) {
    $_SESSION['csrf_edit_postingan'] = bin2hex(random_bytes(32));
}

// Proses perubahan postingan.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $sumberMedia = $_POST['sumber_media'] ?? '';
    $jenisMedia = $_POST['jenis_media'] ?? '';
    $urlMedia = trim($_POST['url_media'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    $tanggalObj = DateTime::createFromFormat('!Y-m-d', $tanggal);

    $mediaBaru = $mediaLama;
    $fileBaru = '';
    $valid = false;

    if (
        !hash_equals($_SESSION['csrf_edit_postingan'], $token)
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
                $mediaBaru = $urlMedia;
                $valid = true;
            } else {
                $pesan = 'Masukkan URL media HTTP atau HTTPS yang valid.';
            }

        /*
         * MEDIA DARI UPLOAD
         */
        } elseif ($sumberMedia === 'upload') {

            $file = $_FILES['media'] ?? null;

            // Jika tidak memilih file baru, pertahankan media lama.
            if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {

                $mediaBaru = $mediaLama;
                $valid = true;

            } elseif ($file['error'] !== UPLOAD_ERR_OK) {

                if (
                    $file['error'] === UPLOAD_ERR_INI_SIZE ||
                    $file['error'] === UPLOAD_ERR_FORM_SIZE
                ) {
                    $pesan = 'Ukuran file maksimal 10 MB.';
                } else {
                    $pesan = 'Upload gagal. Periksa konfigurasi hosting.';
                }

            } else {

                $tmpFile = $file['tmp_name'];
                $ukuran = (int) $file['size'];

                $ekstensi = strtolower(
                    pathinfo($file['name'], PATHINFO_EXTENSION)
                );

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
                                $mediaBaru = $namaFile;
                                $fileBaru = $pathTujuan;
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
     * SIMPAN PERUBAHAN KE DATABASE
     */
    if ($valid) {

        $query = "UPDATE postingan
                  SET judul = ?,
                      deskripsi = ?,
                      media = ?,
                      jenis_media = ?,
                      tanggal = ?
                  WHERE id = ?";

        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                'sssssi',
                $judul,
                $deskripsi,
                $mediaBaru,
                $jenisMedia,
                $tanggal,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                // Hapus file lama hanya setelah perubahan berhasil.
                if ($fileBaru !== '' && $mediaLama !== '') {

                    $namaLama = basename(
                        str_replace('\\', '/', $mediaLama)
                    );

                    $pathLama = __DIR__ . '/postingan/' . $namaLama;

                    if (
                        $sumberAwal === 'upload' &&
                        $namaLama !== '' &&
                        $namaLama !== '.' &&
                        $namaLama !== '..' &&
                        is_file($pathLama) &&
                        realpath(dirname($pathLama)) ===
                            realpath(__DIR__ . '/postingan')
                    ) {
                        unlink($pathLama);
                    }
                }

                unset($_SESSION['csrf_edit_postingan']);

                header('Location: postingan.php?status=edit_berhasil');
                exit;

            } else {

                $pesan = 'Postingan gagal diperbarui di database.';
                mysqli_stmt_close($stmt);
            }

        } else {
            $pesan = 'Gagal menyiapkan query database.';
        }

        // Bersihkan file baru jika database gagal diperbarui.
        if (
            $pesan !== '' &&
            $fileBaru !== '' &&
            is_file($fileBaru)
        ) {
            unlink($fileBaru);
        }
    }
}

include 'include/header.php';
?>

<link rel="stylesheet" href="assets/css/postingan.css">

<section class="postingan halaman-tambah-postingan">

    <div class="form-header">
        <div>
            <h2>Edit Postingan</h2>
            <p>Perbarui informasi kegiatan dan media Lab Multimedia PTIK.</p>
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
        action="edit_postingan.php?id=<?= $id; ?>"
        method="POST"
        enctype="multipart/form-data"
        class="form-postingan"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_edit_postingan'],
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
                <option value="upload" <?= $sumberAwal === 'upload' ? 'selected' : ''; ?>>
                    Upload File
                </option>
                <option value="url" <?= $sumberAwal === 'url' ? 'selected' : ''; ?>>
                    URL Media
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="jenis_media">Jenis Media</label>

            <select id="jenis_media" name="jenis_media" required>
                <option value="gambar" <?= $jenisMedia === 'gambar' ? 'selected' : ''; ?>>
                    Gambar
                </option>
                <option value="video" <?= $jenisMedia === 'video' ? 'selected' : ''; ?>>
                    Video
                </option>
            </select>
        </div>

        <div class="form-group" id="grup_upload">
            <label for="media">Pilih Gambar atau Video Baru</label>

            <input
                type="file"
                id="media"
                name="media"
                accept="image/jpeg,image/png,image/gif,image/webp"
            >

            <small id="keterangan_upload">
                Kosongkan jika ingin mempertahankan media lama.
                Ukuran maksimal 10 MB.
            </small>
        </div>

        <div class="form-group" id="grup_url" hidden>
            <label for="url_media">URL Gambar atau Video</label>

            <input
                type="url"
                id="url_media"
                name="url_media"
                value="<?= $sumberAwal === 'url'
                    ? htmlspecialchars($mediaLama, ENT_QUOTES, 'UTF-8')
                    : ''; ?>"
                placeholder="https://contoh.com/media"
            >

            <small>
                Masukkan URL media langsung atau tautan yang didukung.
                Tautan Instagram, Google, dan YouTube memerlukan
                dukungan embed pada halaman penampil media.
            </small>
        </div>

        <div class="form-aksi">
            <button type="submit" class="btn-tambah">
                <i class="fa-solid fa-floppy-disk"></i>
                Simpan Perubahan
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

    // File baru tidak wajib karena media lama dapat dipertahankan.
    inputMedia.required = false;
    inputUrl.required = gunakanUrl;

    inputMedia.accept = gambar
        ? 'image/jpeg,image/png,image/gif,image/webp'
        : 'video/mp4,video/webm,video/ogg';

    inputUrl.placeholder = gambar
        ? 'https://contoh.com/gambar.jpg'
        : 'https://contoh.com/video.mp4';

    keteranganUpload.textContent = gambar
        ? 'Format JPG, PNG, GIF, atau WEBP. Maksimal 10 MB. Kosongkan jika tidak mengganti media.'
        : 'Format MP4, WEBM, atau OGG. Maksimal 10 MB. Kosongkan jika tidak mengganti media.';
}

sumberMedia.addEventListener('change', aturFormMedia);
jenisMedia.addEventListener('change', aturFormMedia);

aturFormMedia();
</script>

<?php
include 'include/footer.php';
?>
