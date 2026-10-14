
<?php
session_start();

// Pastikan admin sudah login.
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Hanya menerima permintaan POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: postingan.php");
    exit;
}

include 'include/koneksi.php';

// Validasi ID postingan.
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: postingan.php?status=hapus_gagal");
    exit;
}

// Ambil data media sebelum menghapus postingan.
$query = "SELECT media FROM postingan WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    header("Location: postingan.php?status=hapus_gagal");
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$post = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Pastikan postingan ditemukan.
if (!$post) {
    header("Location: postingan.php?status=tidak_ditemukan");
    exit;
}

// Simpan informasi file media lokal.
$media = trim($post['media'] ?? '');
$namaFile = '';

// URL eksternal tidak boleh diperlakukan sebagai file lokal.
$scheme = strtolower(
    parse_url($media, PHP_URL_SCHEME) ?? ''
);

if (
    $media !== '' &&
    !in_array($scheme, ['http', 'https'], true)
) {
    // Mendukung format media lama.
    if (strpos($media, 'gambar:') === 0) {
        $media = substr($media, 7);
    } elseif (strpos($media, 'video:') === 0) {
        $media = substr($media, 6);
    }

    $namaFile = basename(
        str_replace('\\', '/', trim($media))
    );
}

// Hapus data postingan dari database.
$queryHapus = "DELETE FROM postingan WHERE id = ?";
$stmtHapus = mysqli_prepare($conn, $queryHapus);

if (!$stmtHapus) {
    header("Location: postingan.php?status=hapus_gagal");
    exit;
}

mysqli_stmt_bind_param($stmtHapus, 'i', $id);

if (mysqli_stmt_execute($stmtHapus)) {

    mysqli_stmt_close($stmtHapus);

    // Hapus file lokal hanya setelah database berhasil.
    if (
        $namaFile !== '' &&
        $namaFile !== '.' &&
        $namaFile !== '..' &&
        basename($namaFile) === $namaFile
    ) {
        $folderUpload = realpath(__DIR__ . '/postingan');

        if ($folderUpload !== false) {
            $pathFile = $folderUpload . DIRECTORY_SEPARATOR . $namaFile;

            if (
                is_file($pathFile) &&
                realpath($pathFile) !== false &&
                dirname(realpath($pathFile)) === $folderUpload
            ) {
                unlink($pathFile);
            }
        }
    }

    header("Location: postingan.php?status=hapus_berhasil");
    exit;

} else {

    mysqli_stmt_close($stmtHapus);

    header("Location: postingan.php?status=hapus_gagal");
    exit;
}
?>
