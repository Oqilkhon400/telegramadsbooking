<?php
/**
 * Customers Page
 * Mijozlarni boshqarish sahifasi
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

$page_title = 'Mijozlar';
$page_script = 'customers.js';

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
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Mijozlar</h2>
                <p class="text-gray-600">Barcha mijozlarni ko'rish va boshqarish</p>
            </div>
            <button onclick="openAddCustomerModal()" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition transform hover:scale-105 shadow-lg">
                <i class="fas fa-plus mr-2"></i>Mijoz Qo'shish
            </button>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-search mr-2 text-blue-500"></i>Qidirish
                    </label>
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Ism, telefon raqam..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-filter mr-2 text-blue-500"></i>Status
                    </label>
                    <select id="statusFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all">Hammasi</option>
                        <option value="active">Faol</option>
                        <option value="inactive">Faol emas</option>
                    </select>
                </div>
                
                <!-- Reset -->
                <div class="flex items-end">
                    <button onclick="resetFilters()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        <i class="fas fa-redo mr-2"></i>Tozalash
                    </button>
                </div>
                
            </div>
        </div>
        
        <!-- Customers Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            
            <!-- Table Header -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">
                        <i class="fas fa-users mr-2"></i>
                        Mijozlar ro'yxati
                    </h3>
                    <span id="totalCount" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm">0 ta</span>
                </div>
            </div>
            
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Mijoz</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Telefon</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Paketlar</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Qoldiq</th>
                            <th class="text-left py-4 px-6 font-semibold text-gray-700">Status</th>
                            <th class="text-right py-4 px-6 font-semibold text-gray-700">Amal</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
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
                <div id="paginationButtons" class="flex space-x-2">
                    <!-- Pagination tugmalari bu yerda -->
                </div>
            </div>
            
        </div>
        
    </main>
    
</div>

<!-- Add/Edit Customer Modal -->
<div id="customerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold" id="modalTitle">Mijoz Qo'shish</h3>
        </div>
        
        <!-- Modal Body -->
        <form id="customerForm" class="p-6 space-y-4">
            <input type="hidden" id="customerId" name="id">
            
            <!-- Ism-Familiya -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ism-Familiya <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="customerName" 
                    name="full_name" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Ism-Familiyani kiriting"
                >
            </div>
            
            <!-- Telefon -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Telefon raqam <span class="text-red-500">*</span>
                </label>
                <input 
                    type="tel" 
                    id="customerPhone" 
                    name="phone" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="+998901234567"
                >
            </div>
            
            <!-- Telegram maydonlari olib tashlandi - Bot orqali to'ldiriladi -->
            
            <!-- Izoh -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Izoh
                </label>
                <textarea 
                    id="customerNotes" 
                    name="notes" 
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Qo'shimcha ma'lumotlar..."
                ></textarea>
            </div>
            
            <!-- Status (Edit uchun) -->
            <div id="statusField" class="hidden">
                <label class="flex items-center space-x-3">
                    <input type="checkbox" id="customerActive" name="is_active" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Faol</span>
                </label>
            </div>
            
            <!-- Buttons -->
            <div class="flex space-x-3 pt-4">
                <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition">
                    <i class="fas fa-save mr-2"></i>Saqlash
                </button>
                <button type="button" onclick="closeCustomerModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Bekor qilish
                </button>
            </div>
        </form>
        
    </div>
</div>

<script>
let currentPage = 1;
let searchTimeout;

// Mijozlarni yuklash
async function loadCustomers(page = 1) {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    
    try {
        const url = new URL('../../backend/customers/read.php', window.location.origin);
        url.searchParams.append('page', page);
        url.searchParams.append('search', search);
        url.searchParams.append('status', status);
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            renderCustomers(result.data.customers);
            renderPagination(result.data.pagination);
            document.getElementById('totalCount').textContent = result.data.pagination.total_items + ' ta';
        }
    } catch (error) {
        console.error('Load customers error:', error);
        showToast('Mijozlarni yuklashda xatolik', 'error');
    }
}

// Mijozlarni render qilish
function renderCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    
    if (customers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-12 text-gray-500">
                    <i class="fas fa-users text-5xl mb-3 opacity-50"></i>
                    <p class="text-lg">Mijozlar topilmadi</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = customers.map(customer => `
        <tr class="border-b hover:bg-gray-50 transition">
            <td class="py-4 px-6">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        ${customer.full_name.substring(0, 2).toUpperCase()}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">${customer.full_name}</p>
                        <p class="text-xs text-gray-500">${customer.created_at}</p>
                    </div>
                </div>
            </td>
            <td class="py-4 px-6 text-gray-700">${customer.phone}</td>
            <td class="py-4 px-6">
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                    ${customer.total_packages} paket
                </span>
            </td>
            <td class="py-4 px-6">
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                    ${customer.total_remaining_ads} reklama
                </span>
            </td>
            <td class="py-4 px-6">
                ${customer.is_active 
                    ? '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">Faol</span>'
                    : '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold">Faol emas</span>'
                }
            </td>
            <td class="py-4 px-6">
                <div class="flex items-center justify-end space-x-2">
                    <button onclick="viewCustomer(${customer.id})" class="text-blue-600 hover:text-blue-700 p-2" title="Ko'rish">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editCustomer(${customer.id})" class="text-green-600 hover:text-green-700 p-2" title="Tahrirlash">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteCustomer(${customer.id}, '${customer.full_name}')" class="text-red-600 hover:text-red-700 p-2" title="O'chirish">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
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
    
    // Previous
    html += `
        <button 
            onclick="loadCustomers(${pagination.current_page - 1})" 
            ${!pagination.has_prev ? 'disabled' : ''}
            class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `
                <button 
                    onclick="loadCustomers(${i})"
                    class="px-4 py-2 ${i === pagination.current_page ? 'bg-blue-500 text-white' : 'bg-white border hover:bg-gray-50'} rounded-lg"
                >
                    ${i}
                </button>
            `;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += '<span class="px-2">...</span>';
        }
    }
    
    // Next
    html += `
        <button 
            onclick="loadCustomers(${pagination.current_page + 1})" 
            ${!pagination.has_next ? 'disabled' : ''}
            class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    container.innerHTML = html;
}

// Modal functions
function openAddCustomerModal() {
    document.getElementById('modalTitle').textContent = 'Mijoz Qo\'shish';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('statusField').classList.add('hidden');
    document.getElementById('customerModal').classList.remove('hidden');
}

function closeCustomerModal() {
    document.getElementById('customerModal').classList.add('hidden');
}

async function editCustomer(id) {
    try {
        showLoading();
        const response = await fetch(`../../backend/customers/get_by_id.php?id=${id}`);
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
            const customer = result.data.customer;
            
            document.getElementById('modalTitle').textContent = 'Mijozni Tahrirlash';
            document.getElementById('customerId').value = customer.id;
            document.getElementById('customerName').value = customer.full_name;
            document.getElementById('customerPhone').value = customer.phone;
            document.getElementById('telegramId').value = customer.telegram_id || '';
            document.getElementById('telegramUsername').value = customer.telegram_username || '';
            document.getElementById('customerNotes').value = customer.notes || '';
            document.getElementById('customerActive').checked = customer.is_active;
            document.getElementById('statusField').classList.remove('hidden');
            document.getElementById('customerModal').classList.remove('hidden');
        }
    } catch (error) {
        hideLoading();
        showToast('Mijoz ma\'lumotlarini olishda xatolik', 'error');
    }
}

async function deleteCustomer(id, name) {
    if (!confirm(`${name} ni o'chirmoqchimisiz?`)) return;
    
    try {
        showLoading();
        const formData = new FormData();
        formData.append('id', id);
        
        const response = await fetch('../../backend/customers/delete.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
            showToast('Mijoz o\'chirildi', 'success');
            loadCustomers(currentPage);
        } else {
            showToast(result.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('O\'chirishda xatolik', 'error');
    }
}

function viewCustomer(id) {
    window.location.href = `customer-detail.php?id=${id}`;
}

// Form submit
document.getElementById('customerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const id = document.getElementById('customerId').value;
    const url = id ? '../../backend/customers/update.php' : '../../backend/customers/create.php';
    
    try {
        showLoading();
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
            showToast(id ? 'Mijoz yangilandi' : 'Mijoz qo\'shildi', 'success');
            closeCustomerModal();
            loadCustomers(currentPage);
        } else {
            showToast(result.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('Xatolik yuz berdi', 'error');
    }
});

// Search
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadCustomers(1);
    }, 500);
});

// Filter
document.getElementById('statusFilter').addEventListener('change', function() {
    currentPage = 1;
    loadCustomers(1);
});

// Reset filters
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = 'all';
    currentPage = 1;
    loadCustomers(1);
}

// Load on page ready
document.addEventListener('DOMContentLoaded', function() {
    loadCustomers(1);
});
</script>

<?php include '../components/footer.php'; ?>