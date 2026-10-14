
<?php
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


if (!$result) {
    die("Gagal mengambil data postingan.");
}

$active_page = 'postingan';

include 'include/header.php';
?>

<section class="postingan">

    <div class="postingan-header">
        <h2>Galeri & Postingan</h2>
    </div>

    <p class="postingan-deskripsi">
        Ikuti kegiatan praktikum, riset teknologi, dan karya kreatif
        mahasiswa Lab Multimedia PTIK.
    </p>

    <div class="postingan-grid">

        <?php if (mysqli_num_rows($result) > 0): ?>

            <?php while ($post = mysqli_fetch_assoc($result)): ?>

                <?php
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

                // Mendukung format media lama.
                if (strpos($media, 'gambar:') === 0) {
                    $jenisMedia = 'gambar';
                    $media = substr($media, 7);
                } elseif (strpos($media, 'video:') === 0) {
                    $jenisMedia = 'video';
                    $media = substr($media, 6);
                } elseif (strpos($media, 'link:') === 0) {
                    $jenisMedia = 'link';
                    $media = substr($media, 5);
                }

                $media = trim($media);

                $adaMedia = $media !== '';
                $urlMedia = '';
                $urlEmbed = '';
                $pesanMedia = '';

                $tipeSumber = 'lokal';

                /*
                 * Periksa apakah media berupa URL eksternal.
                 */
                if ($adaMedia) {

                    $scheme = strtolower(
                        parse_url($media, PHP_URL_SCHEME) ?? ''
                    );

                    $host = strtolower(
                        parse_url($media, PHP_URL_HOST) ?? ''
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
                                '/(^|\.)youtube\.com$/',
                                $host
                            ) ||
                            preg_match(
                                '/(^|\.)youtube-nocookie\.com$/',
                                $host
                            )
                        ) {

                            $videoId = '';

                            if ($host === 'youtu.be') {

                                $path = trim(
                                    parse_url(
                                        $media,
                                        PHP_URL_PATH
                                    ) ?? '',
                                    '/'
                                );

                                $videoId = explode('/', $path)[0] ?? '';

                            } elseif (preg_match(
                                '~/(?:shorts|embed|live)/([a-zA-Z0-9_-]+)~',
                                parse_url(
                                    $media,
                                    PHP_URL_PATH
                                ) ?? '',
                                $match
                            )) {

                                $videoId = $match[1];

                            } else {

                                parse_str(
                                    parse_url(
                                        $media,
                                        PHP_URL_QUERY
                                    ) ?? '',
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
                            preg_match('/\.instagram\.com$/', $host)
                        ) {

                            $path = trim(
                                parse_url(
                                    $media,
                                    PHP_URL_PATH
                                ) ?? '',
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
                                    'Tautan Instagram harus berupa postingan atau Reel.';
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
                                parse_url(
                                    $media,
                                    PHP_URL_PATH
                                ) ?? '',
                                $match
                            )) {

                                $fileId = $match[1];

                            } else {

                                parse_str(
                                    parse_url(
                                        $media,
                                        PHP_URL_QUERY
                                    ) ?? '',
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
                         * GOOGLE PHOTOS / SHARE.GOOGLE
                         */
                        elseif (
                            $host === 'photos.google.com' ||
                            $host === 'photos.app.goo.gl' ||
                            $host === 'share.google'
                        ) {

                            $pesanMedia =
                                'Tautan berbagi Google tidak dapat langsung ditampilkan. Gunakan URL file langsung atau unggah media ke server.';
                        }

                        /*
                         * URL GAMBAR ATAU VIDEO LANGSUNG
                         */
                        else {
                            $urlMedia = $media;
                        }

                    } else {

                        /*
                         * FILE LOKAL YANG DIUNGGAH ADMIN.
                         * Folder upload berada di dalam Admins.
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
                                'Admins/postingan/' .
                                rawurlencode($namaFile);
                        } else {
                            $adaMedia = false;
                        }
                    }
                }

                /*
                 * Deteksi gambar/video dari ekstensi URL langsung.
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

                    $formatGambar = [
                        'jpg', 'jpeg', 'png',
                        'gif', 'webp', 'avif'
                    ];

                    $formatVideo = [
                        'mp4', 'webm', 'ogg', 'ogv', 'mov'
                    ];

                    if (in_array($ekstensi, $formatGambar, true)) {
                        $jenisMedia = 'gambar';
                    } elseif (
                        in_array($ekstensi, $formatVideo, true)
                    ) {
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

                        <?php elseif (
                            $urlMedia !== '' &&
                            $jenisValid &&
                            $jenisMedia === 'gambar'
                        ): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $urlMedia,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                alt="<?= $judul; ?>"
                                loading="lazy"
                            >

                        <?php elseif (
                            $urlMedia !== '' &&
                            $jenisValid &&
                            $jenisMedia === 'video'
                        ): ?>

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
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        Buka Media
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

                            <?= htmlspecialchars(
                                $tanggal,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </div>

                    </div>

                </article>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="postingan-kosong">
                <i class="fa-regular fa-newspaper"></i>
                <p>Belum ada postingan.</p>
            </div>

        <?php endif; ?>

    </div>

</section>

<?php
mysqli_free_result($result);
include 'include/footer.php';
?>