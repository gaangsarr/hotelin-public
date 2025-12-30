<?php
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = cleanInput($_POST['nama_lengkap']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nomor_telepon = cleanInput($_POST['nomor_telepon']);
    
    // Validation
    if ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        $conn = getConnection();
        
        // Check if email exists
        $sql = "SELECT id_pengguna FROM pengguna WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO pengguna (nama_lengkap, email, password, nomor_telepon, role) 
                    VALUES (?, ?, ?, ?, 'User')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nama, $email, $hashed_password, $nomor_telepon);
            
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
                // Auto redirect after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = 'Registrasi gagal! Coba lagi.';
            }
        }
        
        $conn->close();
    }
}

$page_title = 'Register';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4">
                        <i class="bi bi-person-plus"></i> Register
                    </h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                            <br><small>Mengalihkan ke halaman login...</small>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="nama_lengkap" class="form-control" required 
                                       placeholder="John Doe"
                                       value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required 
                                       placeholder="contoh@email.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" name="nomor_telepon" class="form-control" required 
                                       placeholder="081234567890"
                                       value="<?php echo isset($_POST['nomor_telepon']) ? htmlspecialchars($_POST['nomor_telepon']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" minlength="6" required 
                                       placeholder="Minimal 6 karakter">
                            </div>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" class="form-control" required 
                                       placeholder="Ulangi password">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-person-plus"></i> Daftar Sekarang
                        </button>
                        
                        <div class="text-center">
                            <p class="mb-0">Sudah punya akun? <a href="login.php">Login di sini</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
