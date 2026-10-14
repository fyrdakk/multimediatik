
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}
include 'include/koneksi.php';

$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $kataKunci = '%' . $q . '%';

    $query = "SELECT id, judul, deskripsi, media, jenis_media, tanggal FROM postingan WHERE judul LIKE ? OR deskripsi LIKE ? ORDER BY tanggal DESC, id DESC";

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        die("Gagal menyiapkan pencarian postingan.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ss',
        $kataKunci,
        $kataKunci
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

} else {
    $query = "SELECT id, judul, deskripsi, media, jenis_media, tanggal FROM postingan ORDER BY tanggal DESC, id DESC";

    $result = mysqli_query($conn, $query);
}

if (!$result) {
    die("Gagal mengambil data postingan.");
}

$active_page = 'postingan';
include 'include/header.php';
?>

<link rel="stylesheet" href="assets/css/postingan.css">

<section class="postingan">

    <div class="postingan-header">
        <h2>Kelola Postingan</h2>

        <a href="tambah_postingan.php" class="btn-tambah">
            <i class="fa-solid fa-plus"></i>
            Tambah Postingan
        </a>
    </div>

    <div class="postingan-grid">

        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($post = mysqli_fetch_assoc($result)): ?>

                <?php
                $id = (int) $post['id'];

                $judul = htmlspecialchars(
                    $post['judul'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );

                $deskripsi = htmlspecialchars(
                    $post['deskripsi'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );

                $media = trim($post['media'] ?? '');
                $jenisMedia = strtolower(
                    trim($post['jenis_media'] ?? '')
                );

                $tanggal = !empty($post['tanggal'])
                    ? date('d M Y', strtotime($post['tanggal']))
                    : '-';

                // Mendukung format data media lama.
                if (strpos($media, 'gambar:') === 0) {
                    $jenisMedia = 'gambar';
                    $media = substr($media, 7);
                } elseif (strpos($media, 'video:') === 0) {
                    $jenisMedia = 'video';
                    $media = substr($media, 6);
                }

                $media = trim($media);

                $adaMedia = $media !== '';
                $urlMedia = '';
                $urlEmbed = '';
                $tipeSumber = 'lokal';
                $pesanMedia = '';

                /*
                 * Memeriksa apakah media berupa URL HTTP/HTTPS.
                 */
                if ($adaMedia) {
                    $parsed = parse_url($media);

                    $scheme = strtolower(
                        $parsed['scheme'] ?? ''
                    );

                    $host = strtolower(
                        $parsed['host'] ?? ''
                    );

                    $urlValid = filter_var(
                        $media,
                        FILTER_VALIDATE_URL
                    ) !== false && in_array(
                        $scheme,
                        ['http', 'https'],
                        true
                    );

                    if ($urlValid) {
                        $tipeSumber = 'eksternal';

                        /*
                         * YOUTUBE
                         */
                        if (
                            $host === 'youtu.be' ||
                            preg_match(
                                '/(^|\.)youtube\.com$/i',
                                $host
                            ) ||
                            preg_match(
                                '/(^|\.)youtube-nocookie\.com$/i',
                                $host
                            )
                        ) {
                            $videoId = '';

                            if ($host === 'youtu.be') {
                                $videoId = trim(
                                    $parsed['path'] ?? '',
                                    '/'
                                );

                                $videoId = explode(
                                    '/',
                                    $videoId
                                )[0] ?? '';
                            } elseif (preg_match(
                                '~/(?:shorts|embed|live)/([a-zA-Z0-9_-]+)~',
                                $parsed['path'] ?? '',
                                $match
                            )) {
                                $videoId = $match[1];
                            } else {
                                parse_str(
                                    $parsed['query'] ?? '',
                                    $queryParams
                                );

                                $videoId = $queryParams['v'] ?? '';
                            }

                            if (preg_match(
                                '/^[a-zA-Z0-9_-]{11}$/',
                                $videoId
                            )) {
                                $urlEmbed =
                                    'https://www.youtube-nocookie.com/embed/' .
                                    $videoId;

                                $jenisMedia = 'video';
                            } else {
                                $pesanMedia =
                                    'Tautan YouTube tidak valid.';
                            }
                        }

                        /*
                         * INSTAGRAM
                         */
                        elseif (
                            $host === 'instagram.com' ||
                            preg_match(
                                '/\.instagram\.com$/',
                                $host
                            )
                        ) {
                            $path = trim(
                                $parsed['path'] ?? '',
                                '/'
                            );

                            if (preg_match(
                                '~^(p|reel|reels|tv)/([a-zA-Z0-9_-]+)~',
                                $path,
                                $match
                            )) {
                                $urlEmbed =
                                    'https://www.instagram.com/' .
                                    $match[1] . '/' .
                                    $match[2] . '/embed/';

                            } else {
                                $pesanMedia =
                                    'Gunakan tautan postingan atau Reel Instagram.';
                            }
                        }

                        /*
                         * GOOGLE DRIVE
                         */
                        elseif (
                            $host === 'drive.google.com' ||
                            $host === 'docs.google.com'
                        ) {
                            $fileId = '';

                            if (preg_match(
                                '~/(?:file/d|document/d|spreadsheets/d|presentation/d)/([^/]+)~',
                                $parsed['path'] ?? '',
                                $match
                            )) {
                                $fileId = $match[1];
                            } else {
                                parse_str(
                                    $parsed['query'] ?? '',
                                    $queryParams
                                );

                                $fileId = $queryParams['id'] ?? '';
                            }

                            if (
                                $fileId !== '' &&
                                preg_match(
                                    '/^[a-zA-Z0-9_-]+$/',
                                    $fileId
                                )
                            ) {
                                $urlEmbed =
                                    'https://drive.google.com/file/d/' .
                                    rawurlencode($fileId) .
                                    '/preview';
                            } else {
                                $pesanMedia =
                                    'ID file Google Drive tidak ditemukan.';
                            }
                        }

                        /*
                         * GOOGLE PHOTOS ATAU TAUTAN BERBAGI GOOGLE
                         */
                        elseif (
                            $host === 'photos.google.com' ||
                            $host === 'photos.app.goo.gl' ||
                            $host === 'share.google' ||
                            $host === 'drive.google.com'
                        ) {
                            $pesanMedia =
                                'Tautan berbagi Google tidak dapat langsung ditampilkan. ' .
                                'Gunakan tautan file Google Drive atau unggah medianya.';
                        }

                        /*
                         * URL MEDIA LANGSUNG
                         */
                        else {
                            $urlMedia = $media;
                        }

                    } else {
                        /*
                         * FILE YANG DIUNGGAH KE SERVER
                         */
                        $namaFile = basename(
                            str_replace('\\', '/', $media)
                        );

                        if (
                            $namaFile !== '' &&
                            $namaFile !== '.' &&
                            $namaFile !== '..'
                        ) {
                            $urlMedia =
                                'postingan/' .
                                rawurlencode($namaFile);
                        } else {
                            $adaMedia = false;
                        }
                    }
                }

                /*
                 * Menentukan media langsung berdasarkan ekstensi.
                 */
                if (
                    $urlMedia !== '' &&
                    $tipeSumber === 'eksternal'
                ) {
                    $pathMedia = parse_url(
                        $urlMedia,
                        PHP_URL_PATH
                    ) ?? '';

                    $ekstensi = strtolower(
                        pathinfo($pathMedia, PATHINFO_EXTENSION)
                    );

                    $ekstensiGambar = [
                        'jpg', 'jpeg', 'png',
                        'gif', 'webp', 'avif'
                    ];

                    $ekstensiVideo = [
                        'mp4', 'webm', 'ogg', 'ogv', 'mov'
                    ];

                    if (in_array(
                        $ekstensi,
                        $ekstensiGambar,
                        true
                    )) {
                        $jenisMedia = 'gambar';
                    } elseif (in_array(
                        $ekstensi,
                        $ekstensiVideo,
                        true
                    )) {
                        $jenisMedia = 'video';
                    }
                }

                $jenisValid = in_array(
                    $jenisMedia,
                    ['gambar', 'video'],
                    true
                );
                ?>

                <article class="postingan-card">

                    <div class="postingan-media">

                        <?php if (!$adaMedia): ?>

                            <div class="media-kosong">
                                <i class="fa-regular fa-image"></i>
                                <span>Tidak ada media</span>
                            </div>

                        <?php elseif ($urlEmbed !== ''): ?>

                            <iframe
                                src="<?= htmlspecialchars(
                                    $urlEmbed,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                title="<?= $judul; ?>"
                                loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                referrerpolicy="strict-origin-when-cross-origin"
                            ></iframe>

                        <?php elseif ($urlMedia !== '' && $jenisValid && $jenisMedia === 'gambar'): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $urlMedia,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                alt="<?= $judul; ?>"
                                loading="lazy"
                            >

                        <?php elseif ($urlMedia !== '' && $jenisValid && $jenisMedia === 'video'): ?>

                            <video controls preload="metadata">
                                <source
                                    src="<?= htmlspecialchars(
                                        $urlMedia,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                >
                                Browser Anda tidak mendukung pemutaran video.
                            </video>

                        <?php else: ?>

                            <div class="media-kosong">
                                <i class="fa-solid fa-link"></i>

                                <span>
                                    <?= htmlspecialchars(
                                        $pesanMedia !== ''
                                            ? $pesanMedia
                                            : 'Media tidak dapat ditampilkan.',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <?php if (
                                    $tipeSumber === 'eksternal' &&
                                    filter_var(
                                        $media,
                                        FILTER_VALIDATE_URL
                                    )
                                ): ?>
                                    <a
                                        href="<?= htmlspecialchars(
                                            $media,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Buka tautan media
                                    </a>
                                <?php endif; ?>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="postingan-body">

                        <h3><?= $judul; ?></h3>

                        <p><?= nl2br($deskripsi); ?></p>

                        <div class="postingan-tanggal">
                            <i class="fa-regular fa-calendar"></i>
                            Tanggal:
                            <?= htmlspecialchars(
                                $tanggal,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </div>

                        <div class="postingan-jenis">
                            Jenis media:
                            <?= $adaMedia
                                ? htmlspecialchars(
                                    ucfirst($jenisMedia),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : 'Tidak ada'; ?>
                        </div>

                        <div class="postingan-aksi">

                            <a
                                href="edit_postingan.php?id=<?= $id; ?>"
                                class="btn-edit"
                            >
                                <i class="fa-solid fa-pen"></i>
                                Edit
                            </a>

                            <form
                                action="hapus_postingan.php"
                                method="POST"
                                onsubmit="return confirm(
                                    'Yakin ingin menghapus postingan ini?'
                                );"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $id; ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-hapus"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                    Hapus
                                </button>
                            </form>

                        </div>

                    </div>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="postingan-kosong">
                <i class="fa-regular fa-newspaper"></i>
                <p>Belum ada postingan.</p>

                <a href="tambah_postingan.php" class="btn-tambah">
                    Tambah Postingan
                </a>
            </div>

        <?php endif; ?>

    </div>

</section>

<?php
mysqli_free_result($result);
include 'include/footer.php';
?>