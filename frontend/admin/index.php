<?php
/**
 * Admin Dashboard
 * Asosiy boshqaruv paneli
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

$page_title = 'Dashboard';
$page_script = 'dashboard.js';

// Header
include '../components/header.php';
?>

<!-- Main Container -->
<div class="flex">
    
    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 lg:ml-64 mt-16 p-6">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Dashboard</h2>
            <p class="text-gray-600">Xush kelibsiz, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Mijozlar -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-blue-100 text-sm mb-1">Jami Mijozlar</p>
                        <h3 class="text-3xl font-bold" id="totalCustomers">0</h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-arrow-up mr-1"></i>
                    <span id="todayCustomers">0</span>
                    <span class="ml-1">bugun</span>
                </div>
            </div>
            
            <!-- Paketlar -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-green-100 text-sm mb-1">Aktiv Paketlar</p>
                        <h3 class="text-3xl font-bold" id="activePackages">0</h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-box text-3xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm">
                    <span id="totalAdsRemaining">0</span>
                    <span class="ml-1">reklama qolgan</span>
                </div>
            </div>
            
            <!-- To'lovlar -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-purple-100 text-sm mb-1">Bugungi To'lovlar</p>
                        <h3 class="text-2xl font-bold" id="todayRevenue">0 so'm</h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-money-bill-wave text-3xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm">
                    <span id="monthRevenue">0 so'm</span>
                    <span class="ml-1">shu oy</span>
                </div>
            </div>
            
            <!-- Bookinglar -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-orange-100 text-sm mb-1">Bugungi Reklamalar</p>
                        <h3 class="text-3xl font-bold" id="todayBookings">0</h3>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-4">
                        <i class="fas fa-calendar-alt text-3xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm">
                    <span id="tomorrowBookings">0</span>
                    <span class="ml-1">ertaga</span>
                </div>
            </div>
            
        </div>
        
        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Yaqin Bookinglar (2 columns) -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-calendar-check text-blue-500 mr-2"></i>
                        Yaqin Reklamalar
                    </h3>
                    <a href="calendar.php" class="text-sm text-blue-600 hover:text-blue-700">
                        Hammasini ko'rish <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="space-y-3" id="upcomingBookings">
                    <!-- Loading -->
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                        <p class="text-gray-500 mt-2 text-sm">Yuklanmoqda...</p>
                    </div>
                </div>
            </div>
            
            <!-- Oxirgi To'lovlar (1 column) -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-receipt text-green-500 mr-2"></i>
                        Oxirgi To'lovlar
                    </h3>
                    <a href="payments.php" class="text-sm text-green-600 hover:text-green-700">
                        Barchasi <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="space-y-3" id="recentPayments">
                    <!-- Loading -->
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500 mx-auto"></div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Oxirgi Mijozlar -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-user-plus text-purple-500 mr-2"></i>
                    Yangi Mijozlar
                </h3>
                <a href="customers.php" class="text-sm text-purple-600 hover:text-purple-700">
                    Hammasini ko'rish <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full" id="recentCustomersTable">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Ism</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Telefon</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Qo'shilgan</th>
                            <th class="text-right py-3 px-4 font-semibold text-gray-700">Amal</th>
                        </tr>
                    </thead>
                    <tbody id="recentCustomers">
                        <tr>
                            <td colspan="4" class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500 mx-auto"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </main>
    
</div>

<script>
// Dashboard ma'lumotlarini yuklash
async function loadDashboard() {
    try {
        const response = await fetch('../../backend/statistics/dashboard.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Stats Cards
            document.getElementById('totalCustomers').textContent = data.customers.total;
            document.getElementById('todayCustomers').textContent = data.customers.today;
            document.getElementById('activePackages').textContent = data.packages.active;
            document.getElementById('totalAdsRemaining').textContent = data.packages.total_ads_remaining;
            document.getElementById('todayRevenue').textContent = data.payments.today_amount;
            document.getElementById('monthRevenue').textContent = data.payments.month_amount;
            document.getElementById('todayBookings').textContent = data.bookings.today;
            document.getElementById('tomorrowBookings').textContent = data.bookings.tomorrow;
            
            // Yaqin Bookinglar
            renderUpcomingBookings(data.bookings.upcoming);
            
            // Oxirgi To'lovlar
            renderRecentPayments(data.payments.recent);
            
            // Oxirgi Mijozlar
            renderRecentCustomers(data.customers.recent);
        }
    } catch (error) {
        console.error('Dashboard loading error:', error);
    }
}

// Yaqin bookinglarni render qilish
function renderUpcomingBookings(bookings) {
    const container = document.getElementById('upcomingBookings');
    
    if (bookings.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-calendar-times text-4xl mb-2"></i>
                <p>Yaqin bookinglar yo'q</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = bookings.map(booking => `
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
            <div class="flex items-center space-x-4">
                <div class="bg-blue-500 text-white rounded-lg p-3 text-center min-w-16">
                    <div class="text-xs">${booking.date.split('.')[0]}</div>
                    <div class="text-lg font-bold">${booking.time}</div>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">${booking.customer_name}</p>
                    <p class="text-sm text-gray-600 truncate max-w-md">${booking.description}</p>
                </div>
            </div>
            <button onclick="viewBooking(${booking.id})" class="text-blue-600 hover:text-blue-700">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    `).join('');
}

// Oxirgi to'lovlarni render qilish
function renderRecentPayments(payments) {
    const container = document.getElementById('recentPayments');
    
    if (payments.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-receipt text-4xl mb-2"></i>
                <p>To'lovlar yo'q</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = payments.map(payment => `
        <div class="flex items-center justify-between p-3 border-b last:border-b-0">
            <div>
                <p class="font-semibold text-gray-800 text-sm">${payment.customer_name}</p>
                <p class="text-xs text-gray-500">${payment.date}</p>
            </div>
            <div class="text-right">
                <p class="font-bold text-green-600 text-sm">${payment.amount}</p>
                <p class="text-xs text-gray-500">${payment.method}</p>
            </div>
        </div>
    `).join('');
}

// Oxirgi mijozlarni render qilish
function renderRecentCustomers(customers) {
    const tbody = document.getElementById('recentCustomers');
    
    if (customers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-8 text-gray-500">
                    <i class="fas fa-users text-4xl mb-2"></i>
                    <p>Yangi mijozlar yo'q</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = customers.map(customer => `
        <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-400 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                        ${customer.name.substring(0, 2).toUpperCase()}
                    </div>
                    <span class="font-medium text-gray-800">${customer.name}</span>
                </div>
            </td>
            <td class="py-3 px-4 text-gray-600">${customer.phone}</td>
            <td class="py-3 px-4 text-gray-600 text-sm">${customer.created_at}</td>
            <td class="py-3 px-4 text-right">
                <a href="customers.php?id=${customer.id}" class="text-purple-600 hover:text-purple-700">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

// Booking ko'rish
function viewBooking(id) {
    window.location.href = `calendar.php?booking_id=${id}`;
}

// Sahifa yuklanganda
document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
    
    // Har 30 sekundda yangilash
    setInterval(loadDashboard, 30000);
});
</script>

<?php
// Footer
include '../components/footer.php';
?>