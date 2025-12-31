<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
            <i class="bi bi-building"></i> <strong>HotelIn</strong>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    
                    <?php if (isAdmin()): ?>
                        <!-- Admin Menu -->
                        <?php 
                        // Get hotel_id from URL if available
                        $current_hotel_id = getHotelIdFromURL();
                        
                        if ($current_hotel_id > 0):
                            $current_hotel = getHotelById($current_hotel_id);
                        ?>
                            <!-- Hotel Info in Navbar -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="hotelDropdown" role="button" 
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-building"></i> 
                                    <strong><?php echo $current_hotel['nama_hotel']; ?></strong>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="hotelDropdown">
                                    <li><h6 class="dropdown-header">Current Hotel</h6></li>
                                    <li><span class="dropdown-item-text">
                                        <strong><?php echo $current_hotel['nama_hotel']; ?></strong><br>
                                        <small class="text-muted"><?php echo $current_hotel['nama_kota']; ?></small>
                                    </span></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/index.php">
                                        <i class="bi bi-arrow-left-right"></i> Switch Hotel
                                    </a></li>
                                </ul>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo adminURL('dashboard.php', $current_hotel_id); ?>">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo adminURL('rooms.php', $current_hotel_id); ?>">
                                    <i class="bi bi-door-open"></i> Rooms
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo adminURL('orders.php', $current_hotel_id); ?>">
                                    <i class="bi bi-list-check"></i> Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo adminURL('hotel-edit.php', $current_hotel_id); ?>">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- No hotel selected - show hotel selection link -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/index.php">
                                    <i class="bi bi-buildings"></i> Select Hotel
                                </a>
                            </li>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- User Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/user/my-bookings.php">
                                <i class="bi bi-calendar-check"></i> My Bookings
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text">
                                    <small class="text-muted">Email: <?php echo $_SESSION['email']; ?></small>
                                </span>
                            </li>
                            <li>
                                <span class="dropdown-item-text">
                                    <small class="text-muted">Role: <span class="badge bg-<?php echo isAdmin() ? 'danger' : 'primary'; ?>"><?php echo $_SESSION['role']; ?></span></small>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (isAdmin()): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/index.php">
                                        <i class="bi bi-buildings"></i> Hotel Management
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                <?php else: ?>
                    <!-- Guest Menu -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-2 px-3" href="<?php echo BASE_URL; ?>/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
/* Navbar Enhancements */
.navbar-brand strong {
    font-size: 1.3rem;
}

.navbar-nav .nav-link {
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.navbar-nav .nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 5px;
}

.dropdown-menu {
    min-width: 250px;
}

.dropdown-header {
    color: #0d6efd;
    font-weight: bold;
}

.dropdown-item {
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #e9ecef;
    padding-left: 1.5rem;
}
</style>
