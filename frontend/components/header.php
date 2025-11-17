<?php
/**
 * Header Component
 * Barcha admin sahifalar uchun header
 */

// Session tekshirish
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'admin';
$is_superadmin = $user_role === 'superadmin';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - Reklama Booking</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="flex items-center justify-between px-6 py-4">
            
            <!-- Logo va Menu Toggle -->
            <div class="flex items-center space-x-4">
                <button id="menuToggle" class="text-gray-600 hover:text-gray-900 lg:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white w-10 h-10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Reklama Booking</h1>
                        <p class="text-xs text-gray-500">Boshqaruv Paneli</p>
                    </div>
                </div>
            </div>
            
            <!-- User Info -->
            <div class="flex items-center space-x-4">
                
                <!-- Notifications (keyinchalik qo'shiladi) -->
                <button class="relative text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">3</span>
                </button>
                
                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 hover:bg-gray-100 rounded-lg px-3 py-2 transition">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                        </div>
                        <div class="text-left hidden md:block">
                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="text-xs text-gray-500 capitalize"><?php echo $user_role; ?></p>
                        </div>
                        <i class="fas fa-chevron-down text-gray-500 text-sm"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i> Profil
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2"></i> Sozlamalar
                        </a>
                        <hr class="my-2">
                        <a href="#" onclick="logout()" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2"></i> Chiqish
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <script>
        // Logout funksiyasi
        function logout() {
            if (confirm('Tizimdan chiqmoqchimisiz?')) {
                fetch('../../backend/auth/logout.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../login.php';
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    window.location.href = '../login.php';
                });
            }
        }
    </script>
</body>
</html>