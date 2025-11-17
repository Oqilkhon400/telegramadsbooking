<?php
/**
 * Sidebar Navigation
 * Admin panel navigatsiya menu
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$is_superadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
?>

<!-- Sidebar -->
<aside id="sidebar" class="bg-white shadow-lg fixed left-0 top-16 bottom-0 w-64 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 overflow-y-auto">
    
    <nav class="py-6 px-4">
        
        <!-- Dashboard -->
        <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $current_page === 'index' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-home text-lg w-5"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        
        <!-- Mijozlar -->
        <a href="customers.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $current_page === 'customers' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-users text-lg w-5"></i>
            <span class="font-medium">Mijozlar</span>
        </a>
        
        <!-- Paketlar -->
        <a href="packages.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $current_page === 'packages' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-box text-lg w-5"></i>
            <span class="font-medium">Paketlar</span>
        </a>
        
        <!-- To'lovlar -->
        <a href="payments.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $current_page === 'payments' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-money-bill-wave text-lg w-5"></i>
            <span class="font-medium">To'lovlar</span>
        </a>
        
        <!-- Kalendar / Booking -->
        <a href="calendar.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $current_page === 'calendar' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-calendar-alt text-lg w-5"></i>
            <span class="font-medium">Kalendar</span>
        </a>
        
        <?php if ($is_superadmin): ?>
        <!-- Divider -->
        <div class="border-t border-gray-200 my-4"></div>
        
        <!-- Statistika (faqat superadmin) -->
        <div class="mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase px-4 mb-2">Superadmin</p>
            
            <a href="statistics.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 transition <?php echo $current_page === 'statistics' ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-chart-line text-lg w-5"></i>
                <span class="font-medium">Statistika</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Divider -->
        <div class="border-t border-gray-200 my-4"></div>
        
        <!-- Sozlamalar -->
        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 hover:bg-gray-100 transition">
            <i class="fas fa-cog text-lg w-5"></i>
            <span class="font-medium">Sozlamalar</span>
        </a>
        
        <!-- Yordam -->
        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg mb-2 text-gray-700 hover:bg-gray-100 transition">
            <i class="fas fa-question-circle text-lg w-5"></i>
            <span class="font-medium">Yordam</span>
        </a>
        
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-r from-blue-50 to-purple-50 border-t">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white">
                <i class="fas fa-headset"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-700">Yordam kerakmi?</p>
                <p class="text-xs text-gray-500">Bog'laning</p>
            </div>
        </div>
    </div>
    
</aside>

<!-- Sidebar Backdrop (mobil uchun) -->
<div id="sidebarBackdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

<script>
    // Sidebar toggle (mobil)
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        });
    }
    
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        });
    }
</script>