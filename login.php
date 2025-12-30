<?php
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: user/my-bookings.php');
    }
    exit();
}

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    
    $conn = getConnection();
    $sql = "SELECT * FROM pengguna WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'Admin') {
                header('Location: admin/index.php');
            } else {
                // Redirect back to previous page if exists
                if (isset($_POST['return_url']) && !empty($_POST['return_url'])) {
                    header('Location: ' . $_POST['return_url']);
                } else {
                    header('Location: index.php');
                }
            }
            exit();
        } else {
            $error = 'Email atau password salah!';
        }
    } else {
        $error = 'Email atau password salah!';
    }
    
    $conn->close();
}

$page_title = 'Login';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($redirect): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Silakan login terlebih dahulu untuk melanjutkan.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?php if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'login.php') === false): ?>
                            <input type="hidden" name="return_url" value="<?php echo $_SERVER['HTTP_REFERER']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required autofocus 
                                       placeholder="contoh@email.com">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" required 
                                       placeholder="Masukkan password">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Demo Accounts -->
                    <div class="alert alert-light border">
                        <h6 class="mb-2"><i class="bi bi-info-circle"></i> Demo Account:</h6>
                        <small>
                            <strong>Admin:</strong> admin@hotel.com / admin123<br>
                            <strong>User:</strong> john@gmail.com / admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
