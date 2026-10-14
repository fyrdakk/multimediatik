
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Multimedia PTIK</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<section class="login-page d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="login-card card border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">

            <div class="text-center mb-4">
                <h1 class="login-title fw-bold">Login Admin</h1>
                <p class="text-secondary">Multimedia PTIK</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php
                    if ($_GET['error'] === 'kosong') {
                        echo 'Email dan password wajib diisi.';
                    } elseif ($_GET['error'] === 'login') {
                        echo 'Email atau password salah, atau akun tidak aktif.';
                    } else {
                        echo 'Terjadi kesalahan. Silakan coba lagi.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form action="verifikasi_login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan email admin" required>
                </div>

                
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>

                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>

                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>


                <button type="submit" class="btn btn-login w-100">Masuk</button>
            </form>

        </div>
    </div>
</section>

<script>
const password = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function () {
    const tampil = password.type === 'password';

    password.type = tampil ? 'text' : 'password';

    this.innerHTML = tampil
        ? '<i class="fa-solid fa-eye-slash"></i>'
        : '<i class="fa-solid fa-eye"></i>';

    this.setAttribute(
        'aria-label',
        tampil ? 'Sembunyikan password' : 'Tampilkan password'
    );
});
</script>
</body>

</body>
</html>
