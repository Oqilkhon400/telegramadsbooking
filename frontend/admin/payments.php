<?php
/**
 * Payments Page
 * To'lovlar sahifasi
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

$page_title = 'To\'lovlar';
$page_script = 'payments.js';

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
        <div class="mb-6">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">To'lovlar Tarixi</h2>
                <p class="text-gray-600">To'lovlar tarixi va statistika</p>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            
            <!-- Bugungi to'lovlar -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-green-100 text-sm">Bugungi To'lovlar</p>
                    <i class="fas fa-calendar-day text-2xl opacity-50"></i>
                </div>
                <h3 class="text-2xl font-bold" id="todayAmount">0 so'm</h3>
                <p class="text-green-100 text-xs mt-1"><span id="todayCount">0</span> ta to'lov</p>
            </div>
            
            <!-- Oylik to'lovlar -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-blue-100 text-sm">Shu Oylik</p>
                    <i class="fas fa-calendar-alt text-2xl opacity-50"></i>
                </div>
                <h3 class="text-2xl font-bold" id="monthAmount">0 so'm</h3>
                <p class="text-blue-100 text-xs mt-1"><span id="monthCount">0</span> ta to'lov</p>
            </div>
            
            <!-- Jami to'lovlar -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-purple-100 text-sm">Jami To'lovlar</p>
                    <i class="fas fa-money-bill-wave text-2xl opacity-50"></i>
                </div>
                <h3 class="text-2xl font-bold" id="totalAmount">0 so'm</h3>
                <p class="text-purple-100 text-xs mt-1"><span id="totalCount">0</span> ta to'lov</p>
            </div>
            
            <!-- O'rtacha to'lov -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-orange-100 text-sm">O'rtacha To'lov</p>
                    <i class="fas fa-chart-line text-2xl opacity-50"></i>
                </div>
                <h3 class="text-2xl font-bold" id="avgAmount">0 so'm</h3>
                <p class="text-orange-100 text-xs mt-1">Har bir to'lov</p>
            </div>
            
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search mr-2 text-purple-500"></i>Qidirish
                    </label>
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Mijoz nomi, paket..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                </div>
                
                <!-- Payment Method -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-credit-card mr-2 text-purple-500"></i>To'lov turi
                    </label>
                    <select id="methodFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="all">Hammasi</option>
                        <option value="naqd">Naqd</option>
                        <option value="karta">Karta</option>
                        <option value="click">Click</option>
                        <option value="payme">Payme</option>
                    </select>
                </div>
                
                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-2 text-purple-500"></i>Dan
                    </label>
                    <input 
                        type="date" 
                        id="dateFrom"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                </div>
                
                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Gacha
                    </label>
                    <input 
                        type="date" 
                        id="dateTo"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    >
                </div>
                
            </div>
            
            <!-- Filter Buttons -->
            <div class="flex space-x-2 mt-4">
                <button onclick="applyFilters()" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <button onclick="resetFilters()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-redo mr-2"></i>Tozalash
                </button>
            </div>
        </div>
        
        <!-- Payments Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            
            <!-- Table Header -->
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 text-white px-6 py-4">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-receipt mr-2"></i>
                    To'lovlar tarixi
                </h3>
            </div>
            
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Mijoz</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Paket</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Summa</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">To'lov turi</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Sana</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Qabul qildi</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto mb-4"></div>
                                <p class="text-gray-500">Yuklanmoqda...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
                <div class="text-sm text-gray-600">
                    <span id="paginationInfo">0 dan 0 gacha ko'rsatilmoqda</span>
                </div>
                <div id="paginationButtons" class="flex space-x-2"></div>
            </div>
            
        </div>
        
    </main>
    
</div>

<script>
let currentPage = 1;
let searchTimeout;

// To'lovlarni yuklash
async function loadPayments(page = 1) {
    const search = document.getElementById('searchInput').value;
    const method = document.getElementById('methodFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    try {
        const url = new URL('../../backend/payments/read.php', window.location.origin);
        url.searchParams.append('page', page);
        url.searchParams.append('search', search);
        url.searchParams.append('payment_method', method);
        if (dateFrom) url.searchParams.append('date_from', dateFrom);
        if (dateTo) url.searchParams.append('date_to', dateTo);
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            renderPayments(result.data.payments);
            renderPagination(result.data.pagination);
            updateStats(result.data.statistics);
        }
    } catch (error) {
        console.error('Load payments error:', error);
        showToast('To\'lovlarni yuklashda xatolik', 'error');
    }
}

// Statistikani yangilash
function updateStats(stats) {
    // Bu statistika read.php dan keladi, lekin biz umumiy statistika uchun dashboard.php dan olamiz
    loadDashboardStats();
}

async function loadDashboardStats() {
    try {
        const response = await fetch('../../backend/statistics/dashboard.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            document.getElementById('todayAmount').textContent = data.payments.today_amount;
            document.getElementById('todayCount').textContent = data.payments.today_count;
            document.getElementById('monthAmount').textContent = data.payments.month_amount;
            document.getElementById('monthCount').textContent = data.payments.month_count;
            document.getElementById('totalAmount').textContent = data.payments.total_amount;
            document.getElementById('totalCount').textContent = data.payments.total_count;
            
            // O'rtacha
            const avg = data.payments.total_count > 0 
                ? Math.round(parseFloat(data.payments.total_amount.replace(/[^\d]/g, '')) / data.payments.total_count)
                : 0;
            document.getElementById('avgAmount').textContent = avg.toLocaleString('uz-UZ') + ' so\'m';
        }
    } catch (error) {
        console.error('Stats loading error:', error);
    }
}

// To'lovlarni render qilish
function renderPayments(payments) {
    const tbody = document.getElementById('paymentsTableBody');
    
    if (payments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-12 text-gray-500">
                    <i class="fas fa-receipt text-5xl mb-3 opacity-50"></i>
                    <p class="text-lg">To'lovlar topilmadi</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = payments.map(payment => `
        <tr class="border-b hover:bg-gray-50 transition">
            <td class="py-4 px-6">
                <div>
                    <p class="font-semibold text-gray-800">${payment.customer_name}</p>
                    <p class="text-xs text-gray-500">${payment.customer_phone}</p>
                </div>
            </td>
            <td class="py-4 px-6">
                <span class="text-gray-700">${payment.package_name}</span>
            </td>
            <td class="py-4 px-6">
                <span class="font-bold text-green-600">${payment.amount_formatted}</span>
            </td>
            <td class="py-4 px-6">
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                    payment.payment_method === 'naqd' ? 'bg-green-100 text-green-800' :
                    payment.payment_method === 'karta' ? 'bg-blue-100 text-blue-800' :
                    payment.payment_method === 'click' ? 'bg-purple-100 text-purple-800' :
                    'bg-orange-100 text-orange-800'
                }">
                    ${payment.payment_method.toUpperCase()}
                </span>
            </td>
            <td class="py-4 px-6 text-sm text-gray-600">
                ${payment.payment_date}
            </td>
            <td class="py-4 px-6 text-sm text-gray-600">
                ${payment.received_by_name || '-'}
            </td>
        </tr>
    `).join('');
}

// Pagination
function renderPagination(pagination) {
    const container = document.getElementById('paginationButtons');
    const info = document.getElementById('paginationInfo');
    
    const start = (pagination.current_page - 1) * pagination.items_per_page + 1;
    const end = Math.min(start + pagination.items_per_page - 1, pagination.total_items);
    info.textContent = `${start} dan ${end} gacha, jami ${pagination.total_items} ta`;
    
    let html = '';
    
    html += `
        <button 
            onclick="loadPayments(${pagination.current_page - 1})" 
            ${!pagination.has_prev ? 'disabled' : ''}
            class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `
                <button 
                    onclick="loadPayments(${i})"
                    class="px-4 py-2 ${i === pagination.current_page ? 'bg-purple-500 text-white' : 'bg-white border hover:bg-gray-50'} rounded-lg"
                >
                    ${i}
                </button>
            `;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += '<span class="px-2">...</span>';
        }
    }
    
    html += `
        <button 
            onclick="loadPayments(${pagination.current_page + 1})" 
            ${!pagination.has_next ? 'disabled' : ''}
            class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    container.innerHTML = html;
}

// Filters
function applyFilters() {
    currentPage = 1;
    loadPayments(1);
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('methodFilter').value = 'all';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    currentPage = 1;
    loadPayments(1);
}

// Search
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadPayments(1);
    }, 500);
});

// Load on page ready
document.addEventListener('DOMContentLoaded', function() {
    loadPayments(1);
    loadDashboardStats();
});
</script>

<?php include '../components/footer.php'; ?>