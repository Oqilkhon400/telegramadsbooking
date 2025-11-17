<?php
/**
 * Packages Page (Yangi versiya - Paket + To'lov birlashgan)
 * Paketlar va to'lovlar bir oynada
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

$page_title = 'Paketlar';
$page_script = 'packages.js';

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
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Paketlar</h2>
                <p class="text-gray-600">Mijozga paket biriktirish va to'lov qabul qilish</p>
            </div>
            <button onclick="openNewPackageModal()" class="bg-gradient-to-r from-green-500 to-teal-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-green-600 hover:to-teal-700 transition transform hover:scale-105 shadow-lg">
                <i class="fas fa-plus mr-2"></i>Yangi Paket
            </button>
        </div>
        
        <!-- Active Packages List -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-box text-green-500 mr-2"></i>
                Aktiv Paketlar
            </h3>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Mijoz</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Paket</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Qoldiq</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">To'lov</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Sana</th>
                            <th class="text-right py-3 px-4 font-semibold text-gray-700">Amal</th>
                        </tr>
                    </thead>
                    <tbody id="activePackagesTable">
                        <tr>
                            <td colspan="6" class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500 mx-auto"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </main>
    
</div>

<!-- New Package Modal (Paket + To'lov birlashgan) -->
<div id="packageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold">Yangi Paket va To'lov</h3>
        </div>
        
        <!-- Modal Body -->
        <form id="packageForm" class="p-6 space-y-6">
            
            <!-- Mijoz tanlash -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <label class="block text-sm font-bold text-gray-800 mb-3">
                    1️⃣ Mijozni tanlang <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-3">
                    <select 
                        id="selectedCustomer" 
                        name="customer_id" 
                        required
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg"
                    >
                        <option value="">Mijozni tanlang...</option>
                    </select>
                    <button type="button" onclick="openQuickAddCustomer()" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition whitespace-nowrap">
                        <i class="fas fa-user-plus mr-2"></i>Yangi mijoz
                    </button>
                </div>
            </div>
            
            <!-- Paket ma'lumotlari -->
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <h4 class="text-sm font-bold text-gray-800 mb-3">2️⃣ Paket ma'lumotlari</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Paket nomi <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="packageName" 
                            name="package_name" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            placeholder="Masalan: 5 ta reklama"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Reklama soni <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="adsCount" 
                            name="ads_count" 
                            min="1"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                            placeholder="5"
                        >
                    </div>
                </div>
            </div>
            
            <!-- To'lov ma'lumotlari -->
            <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
                <h4 class="text-sm font-bold text-gray-800 mb-3">3️⃣ To'lov ma'lumotlari</h4>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            To'lov summasi (so'm) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="paymentAmount" 
                            name="amount" 
                            min="0"
                            step="1000"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                            placeholder="150000"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            To'lov turi <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="paymentMethod" 
                            name="payment_method" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                        >
                            <option value="">Tanlang...</option>
                            <option value="naqd">💵 Naqd</option>
                            <option value="karta">💳 Karta</option>
                            <option value="nasiya">📋 Nasiya</option>
                        </select>
                    </div>
                </div>
                
                <!-- Izoh -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Izoh (ixtiyoriy)
                    </label>
                    <textarea 
                        id="packageNotes" 
                        name="notes" 
                        rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                        placeholder="Qo'shimcha ma'lumotlar..."
                    ></textarea>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="flex space-x-3 pt-4 border-t">
                <button type="submit" name="action" value="save" class="flex-1 bg-gradient-to-r from-green-500 to-teal-600 text-white py-3 rounded-lg font-semibold hover:from-green-600 hover:to-teal-700 transition">
                    <i class="fas fa-save mr-2"></i>Saqlash
                </button>
                <button type="button" onclick="saveAndBook()" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition">
                    <i class="fas fa-calendar-plus mr-2"></i>Bron qilish
                </button>
                <button type="button" onclick="closePackageModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Orqaga
                </button>
            </div>
        </form>
        
    </div>
</div>

<!-- Quick Add Customer Modal -->
<div id="quickCustomerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold">Yangi Mijoz Qo'shish</h3>
        </div>
        
        <form id="quickCustomerForm" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ism-Familiya <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="quickCustomerName" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Ism-Familiyani kiriting"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Telefon raqam <span class="text-red-500">*</span>
                </label>
                <input 
                    type="tel" 
                    id="quickCustomerPhone" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="+998901234567"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Izoh
                </label>
                <textarea 
                    id="quickCustomerNotes" 
                    rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Qo'shimcha ma'lumotlar..."
                ></textarea>
            </div>
            
            <div class="flex space-x-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-500 text-white py-3 rounded-lg font-semibold hover:bg-blue-600 transition">
                    <i class="fas fa-check mr-2"></i>Saqlash
                </button>
                <button type="button" onclick="closeQuickCustomerModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Bekor qilish
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let savedPackageId = null;

// Load active packages
async function loadActivePackages() {
    try {
        const response = await fetch('../../backend/customers/read.php?status=active&limit=1000');
        const result = await response.json();
        
        if (result.success) {
            renderActivePackages(result.data.customers);
        }
    } catch (error) {
        console.error('Load error:', error);
    }
}

function renderActivePackages(customers) {
    const tbody = document.getElementById('activePackagesTable');
    
    // Faqat aktiv paketli mijozlar
    const withPackages = customers.filter(c => c.active_packages > 0);
    
    if (withPackages.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-8 text-gray-500">
                    Aktiv paketlar yo'q
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = withPackages.map(customer => `
        <tr class="border-b hover:bg-gray-50">
            <td class="py-3 px-4">
                <p class="font-semibold">${customer.full_name}</p>
                <p class="text-xs text-gray-500">${customer.phone}</p>
            </td>
            <td class="py-3 px-4">
                <span class="text-gray-700">${customer.active_packages} ta paket</span>
            </td>
            <td class="py-3 px-4">
                <span class="font-bold text-green-600">${customer.total_remaining_ads} reklama</span>
            </td>
            <td class="py-3 px-4 text-sm text-gray-600">
                <i class="fas fa-check-circle text-green-500"></i> To'langan
            </td>
            <td class="py-3 px-4 text-sm text-gray-600">
                ${customer.created_at}
            </td>
            <td class="py-3 px-4 text-right">
                <button onclick="viewCustomerPackages(${customer.id})" class="text-blue-600 hover:text-blue-700">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Open modal
async function openNewPackageModal() {
    try {
        // Load customers
        const response = await fetch('../../backend/customers/read.php?status=active&limit=1000');
        const result = await response.json();
        
        if (result.success) {
            const select = document.getElementById('selectedCustomer');
            select.innerHTML = '<option value="">Mijozni tanlang...</option>' +
                result.data.customers.map(c => 
                    `<option value="${c.id}">${c.full_name} - ${c.phone}</option>`
                ).join('');
        }
        
        document.getElementById('packageForm').reset();
        document.getElementById('packageModal').classList.remove('hidden');
    } catch (error) {
        showToast('Mijozlarni yuklashda xatolik', 'error');
    }
}

function closePackageModal() {
    document.getElementById('packageModal').classList.add('hidden');
}

// Quick add customer
function openQuickAddCustomer() {
    document.getElementById('quickCustomerForm').reset();
    document.getElementById('quickCustomerModal').classList.remove('hidden');
}

function closeQuickCustomerModal() {
    document.getElementById('quickCustomerModal').classList.add('hidden');
}

document.getElementById('quickCustomerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('full_name', document.getElementById('quickCustomerName').value);
    formData.append('phone', document.getElementById('quickCustomerPhone').value);
    formData.append('notes', document.getElementById('quickCustomerNotes').value);
    
    try {
        showLoading();
        const response = await fetch('../../backend/customers/create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
            showToast('Mijoz qo\'shildi!', 'success');
            closeQuickCustomerModal();
            
            // Add to select
            const select = document.getElementById('selectedCustomer');
            const option = new Option(
                `${result.data.full_name} - ${result.data.phone}`,
                result.data.id
            );
            select.add(option);
            select.value = result.data.id;
        } else {
            showToast(result.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('Xatolik yuz berdi', 'error');
    }
});

// Save package
document.getElementById('packageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await savePackage();
});

async function savePackage() {
    const formData = new FormData(document.getElementById('packageForm'));
    
    // Validatsiya
    if (!formData.get('customer_id')) {
        showToast('Mijozni tanlang', 'warning');
        return;
    }
    if (!formData.get('package_name')) {
        showToast('Paket nomini kiriting', 'warning');
        return;
    }
    if (!formData.get('ads_count') || formData.get('ads_count') <= 0) {
        showToast('Reklama sonini kiriting', 'warning');
        return;
    }
    if (!formData.get('amount') || formData.get('amount') <= 0) {
        showToast('To\'lov summasini kiriting', 'warning');
        return;
    }
    if (!formData.get('payment_method')) {
        showToast('To\'lov turini tanlang', 'warning');
        return;
    }
    
    try {
        showLoading('Saqlanmoqda...');
        
        // 1. Paket yaratish
        const pkgData = new FormData();
        pkgData.append('name', formData.get('package_name'));
        pkgData.append('ads_count', formData.get('ads_count'));
        pkgData.append('price', formData.get('amount')); // Narx = to'lov summasi
        pkgData.append('description', formData.get('notes') || '');
        
        console.log('Creating package...', Object.fromEntries(pkgData));
        
        const pkgResponse = await fetch('../../backend/packages/create.php', {
            method: 'POST',
            body: pkgData
        });
        
        const pkgText = await pkgResponse.text();
        console.log('Package response:', pkgText);
        
        let pkgResult;
        try {
            pkgResult = JSON.parse(pkgText);
        } catch (e) {
            hideLoading();
            showToast('Server xatosi: ' + pkgText.substring(0, 100), 'error');
            return;
        }
        
        if (!pkgResult.success) {
            hideLoading();
            showToast(pkgResult.error || 'Paket yaratishda xatolik', 'error');
            return;
        }
        
        const packageId = pkgResult.data.id;
        console.log('Package created:', packageId);
        
        // 2. Paketni mijozga biriktirish
        const assignData = new FormData();
        assignData.append('customer_id', formData.get('customer_id'));
        assignData.append('package_id', packageId);
        assignData.append('notes', formData.get('notes') || '');
        
        console.log('Assigning package...', Object.fromEntries(assignData));
        
        const assignResponse = await fetch('../../backend/packages/assign_to_customer.php', {
            method: 'POST',
            body: assignData
        });
        
        // Status code tekshirish
        console.log('Assign status:', assignResponse.status);
        
        const assignText = await assignResponse.text();
        console.log('Assign response TEXT:', assignText);
        
        if (assignResponse.status !== 200) {
            hideLoading();
            alert('Assign xatosi (Status ' + assignResponse.status + '):\n\n' + assignText);
            return;
        }
        
        let assignResult;
        try {
            assignResult = JSON.parse(assignText);
        } catch (e) {
            hideLoading();
            showToast('Server xatosi: ' + assignText.substring(0, 100), 'error');
            return;
        }
        
        if (!assignResult.success) {
            hideLoading();
            showToast(assignResult.error || 'Paket biriktirishda xatolik', 'error');
            return;
        }
        
        const customerPackageId = assignResult.data.id;
        console.log('Package assigned:', customerPackageId);
        
        // 3. To'lov qabul qilish
        const payData = new FormData();
        payData.append('customer_id', formData.get('customer_id'));
        payData.append('package_id', packageId);
        payData.append('customer_package_id', customerPackageId);
        payData.append('amount', formData.get('amount'));
        payData.append('payment_method', formData.get('payment_method'));
        payData.append('notes', formData.get('notes') || '');
        
        console.log('Creating payment...', Object.fromEntries(payData));
        
        const payResponse = await fetch('../../backend/payments/create.php', {
            method: 'POST',
            body: payData
        });
        
        const payText = await payResponse.text();
        console.log('Payment response:', payText);
        
        let payResult;
        try {
            payResult = JSON.parse(payText);
        } catch (e) {
            hideLoading();
            showToast('Server xatosi: ' + payText.substring(0, 100), 'error');
            return;
        }
        
        hideLoading();
        
        if (payResult.success) {
            savedPackageId = customerPackageId;
            showToast('Paket va to\'lov muvaffaqiyatli saqlandi!', 'success');
            closePackageModal();
            loadActivePackages();
        } else {
            showToast(payResult.error || 'To\'lov qabul qilishda xatolik', 'error');
        }
        
    } catch (error) {
        hideLoading();
        showToast('Xatolik yuz berdi: ' + error.message, 'error');
        console.error('Error:', error);
    }
}

// Save and book
async function saveAndBook() {
    await savePackage();
    
    if (savedPackageId) {
        setTimeout(() => {
            window.location.href = 'calendar.php';
        }, 1000);
    }
}

function viewCustomerPackages(customerId) {
    window.location.href = `customers.php?id=${customerId}`;
}

// Load on ready
document.addEventListener('DOMContentLoaded', function() {
    loadActivePackages();
});
</script>

<?php include '../components/footer.php'; ?>