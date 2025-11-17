<?php
/**
 * Calendar Page
 * Kalendar va Booking sahifasi
 */

require_once '../../config/database.php';
require_once '../../config/settings.php';

$page_title = 'Kalendar';
$page_script = 'calendar.js';

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
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Kalendar</h2>
                <p class="text-gray-600">Reklama vaqtlarini bron qilish</p>
            </div>
            <button onclick="openAddBookingModal()" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition transform hover:scale-105 shadow-lg">
                <i class="fas fa-plus mr-2"></i>Booking Qilish
            </button>
        </div>
        
        <!-- Calendar Navigation -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
                
                <!-- Date Selector -->
                <div class="flex items-center space-x-4">
                    <button onclick="changeDate(-1)" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-chevron-left text-gray-600"></i>
                    </button>
                    <input 
                        type="date" 
                        id="selectedDate" 
                        value="<?php echo date('Y-m-d'); ?>"
                        onchange="loadCalendar()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <button onclick="changeDate(1)" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-chevron-right text-gray-600"></i>
                    </button>
                    <button onclick="setToday()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                        <i class="fas fa-calendar-day mr-2"></i>Bugun
                    </button>
                </div>
                
                <!-- Search -->
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="calendarSearch" 
                            placeholder="Mijoz yoki reklama izlash..."
                            class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onkeyup="searchBookings()"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="flex items-center space-x-4">
                    <div class="text-center px-4 py-2 bg-green-50 rounded-lg">
                        <p class="text-xs text-gray-600">Bo'sh</p>
                        <p class="text-lg font-bold text-green-600" id="availableCount">0</p>
                    </div>
                    <div class="text-center px-4 py-2 bg-blue-50 rounded-lg">
                        <p class="text-xs text-gray-600">Band</p>
                        <p class="text-lg font-bold text-blue-600" id="bookedCount">0</p>
                    </div>
                    <div class="text-center px-4 py-2 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-600">O'tgan</p>
                        <p class="text-lg font-bold text-gray-600" id="pastCount">0</p>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Calendar Grid -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            
            <!-- Date Header -->
            <div class="mb-6 pb-4 border-b">
                <h3 class="text-2xl font-bold text-gray-800" id="dateTitle">Loading...</h3>
                <p class="text-gray-600 text-sm" id="dateSubtitle"></p>
            </div>
            
            <!-- Time Slots Grid -->
            <div id="timeSlotsGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                
                <!-- Loading -->
                <div class="col-span-full text-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                    <p class="text-gray-500">Yuklanmoqda...</p>
                </div>
                
            </div>
            
        </div>
        
    </main>
    
</div>

<!-- Add Booking Modal -->
<div id="bookingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold">Reklama Booking Qilish</h3>
        </div>
        
        <!-- Modal Body -->
        <form id="bookingForm" class="p-6 space-y-4">
            
            <!-- Mijoz -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Mijoz <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-3">
                    <select 
                        id="bookingCustomer" 
                        name="customer_id" 
                        required
                        onchange="loadCustomerPackages()"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Mijozni tanlang...</option>
                    </select>
                    <button type="button" onclick="openQuickAddCustomerFromCalendar()" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition whitespace-nowrap">
                        <i class="fas fa-user-plus mr-2"></i>Yangi
                    </button>
                </div>
            </div>
            
            <!-- Paket -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Paket <span class="text-red-500">*</span>
                </label>
                <select 
                    id="bookingPackage" 
                    name="customer_package_id" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Avval mijozni tanlang...</option>
                </select>
                <p class="text-xs text-gray-500 mt-1" id="packageInfo"></p>
            </div>
            
            <!-- Sana va Vaqt -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sana <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="bookingDate" 
                        name="slot_date" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Vaqt <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="bookingTime" 
                        name="slot_time" 
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Vaqtni tanlang...</option>
                        <option value="07:00">07:00</option>
                        <option value="08:00">08:00</option>
                        <option value="09:00">09:00</option>
                        <option value="10:00">10:00</option>
                        <option value="11:00">11:00</option>
                        <option value="12:00">12:00</option>
                        <option value="13:00">13:00</option>
                        <option value="14:00">14:00</option>
                        <option value="15:00">15:00</option>
                        <option value="16:00">16:00</option>
                        <option value="17:00">17:00</option>
                        <option value="18:00">18:00</option>
                        <option value="19:00">19:00</option>
                        <option value="20:00">20:00</option>
                        <option value="21:00">21:00</option>
                        <option value="22:00">22:00</option>
                    </select>
                </div>
            </div>
            
            <!-- Reklama tavsifi -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reklama tavsifi <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="bookingDescription" 
                    name="ad_description" 
                    rows="3"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Qanday reklama ekanligi haqida qisqacha..."
                ></textarea>
            </div>
            
            <!-- Izoh -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Izoh
                </label>
                <textarea 
                    id="bookingNotes" 
                    name="notes" 
                    rows="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Qo'shimcha ma'lumotlar..."
                ></textarea>
            </div>
            
            <!-- Buttons -->
            <div class="flex space-x-3 pt-4">
                <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition">
                    <i class="fas fa-check mr-2"></i>Booking Qilish
                </button>
                <button type="button" onclick="closeBookingModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Bekor qilish
                </button>
            </div>
        </form>
        
    </div>
</div>

<!-- View Booking Modal -->
<div id="viewBookingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full">
        
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold">Booking Ma'lumotlari</h3>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-4" id="bookingDetails">
            <!-- Details will be loaded here -->
        </div>
        
        <!-- Modal Footer -->
        <div class="p-6 bg-gray-50 rounded-b-2xl flex space-x-3">
            <button onclick="editBooking()" class="flex-1 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition">
                <i class="fas fa-edit mr-2"></i>Tahrirlash
            </button>
            <button onclick="deleteBooking()" class="flex-1 bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-trash mr-2"></i>O'chirish
            </button>
            <button onclick="closeViewBookingModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Yopish
            </button>
        </div>
        
    </div>
</div>

<!-- Quick Add Customer Modal -->
<div id="quickCustomerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold">Tezkor Mijoz Qo'shish</h3>
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
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
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
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    placeholder="+998901234567"
                >
            </div>
            
            <div class="flex space-x-3 pt-4">
                <button type="submit" class="flex-1 bg-green-500 text-white py-3 rounded-lg font-semibold hover:bg-green-600 transition">
                    <i class="fas fa-check mr-2"></i>Qo'shish
                </button>
                <button type="button" onclick="closeQuickCustomerModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Bekor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedBooking = null;
let allBookingsData = [];

// Kalendar yuklash
async function loadCalendar() {
    const date = document.getElementById('selectedDate').value;
    
    try {
        const response = await fetch(`../../backend/bookings/get_by_date.php?date=${date}`);
        const result = await response.json();
        
        if (result.success) {
            allBookingsData = result.data.time_slots; // Qidirish uchun saqlash
            renderCalendar(result.data);
            updateDateTitle(result.data.date);
        }
    } catch (error) {
        console.error('Load calendar error:', error);
        showToast('Kalendar yuklashda xatolik', 'error');
    }
}

// Kalendar render qilish
function renderCalendar(data) {
    const grid = document.getElementById('timeSlotsGrid');
    
    // Stats
    document.getElementById('availableCount').textContent = data.statistics.available;
    document.getElementById('bookedCount').textContent = data.statistics.booked;
    document.getElementById('pastCount').textContent = data.statistics.past;
    
    if (data.time_slots.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12 text-gray-500">
                <i class="fas fa-calendar-times text-5xl mb-3 opacity-50"></i>
                <p class="text-lg">Bu kun uchun vaqt slotlari yo'q</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = data.time_slots.map(slot => {
        let bgColor = '';
        let textColor = '';
        let icon = '';
        let cursorClass = 'cursor-pointer';
        let clickEvent = '';
        
        if (slot.is_booked) {
            bgColor = 'bg-blue-500';
            textColor = 'text-white';
            icon = '<i class="fas fa-check-circle"></i>';
            clickEvent = `onclick="viewBookingDetails(${slot.booking.id})"`;
        } else if (slot.status === 'available' || slot.status === 'past') {
            // O'tgan vaqtga ham booking mumkin!
            bgColor = slot.status === 'past' ? 'bg-gray-100 border-2 border-gray-300' : 'bg-green-50 border-2 border-green-300';
            textColor = slot.status === 'past' ? 'text-gray-600' : 'text-green-800';
            icon = '<i class="fas fa-calendar-plus"></i>';
            clickEvent = `onclick="quickBooking('${slot.time}')"`;
        }
        
        return `
            <div ${clickEvent} class="${bgColor} ${textColor} ${cursorClass} rounded-xl p-4 transition transform hover:scale-105 shadow-md">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-2xl font-bold">${slot.time}</span>
                    ${icon}
                </div>
                ${slot.is_booked ? `
                    <div class="mt-2">
                        <p class="text-sm font-semibold truncate">${slot.booking.customer_name}</p>
                        <p class="text-xs opacity-90 truncate">${slot.booking.ad_description}</p>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="bg-white bg-opacity-20 px-2 py-1 rounded">${slot.booking.package_name}</span>
                            <span class="bg-white bg-opacity-20 px-2 py-1 rounded">${slot.booking.remaining_ads} qoldi</span>
                        </div>
                    </div>
                ` : slot.status === 'available' ? `
                    <p class="text-sm mt-2">Bo'sh</p>
                ` : `
                    <p class="text-sm mt-2">O'tgan</p>
                `}
            </div>
        `;
    }).join('');
}

// Sana titleni yangilash
function updateDateTitle(dateStr) {
    document.getElementById('dateTitle').textContent = dateStr;
    const today = new Date().toISOString().split('T')[0];
    const selected = document.getElementById('selectedDate').value;
    
    if (selected === today) {
        document.getElementById('dateSubtitle').textContent = 'Bugun';
    } else if (selected === new Date(Date.now() + 86400000).toISOString().split('T')[0]) {
        document.getElementById('dateSubtitle').textContent = 'Ertaga';
    } else {
        document.getElementById('dateSubtitle').textContent = '';
    }
}

// Date navigation
function changeDate(days) {
    const dateInput = document.getElementById('selectedDate');
    const currentDate = new Date(dateInput.value);
    currentDate.setDate(currentDate.getDate() + days);
    dateInput.value = currentDate.toISOString().split('T')[0];
    loadCalendar();
}

function setToday() {
    document.getElementById('selectedDate').value = new Date().toISOString().split('T')[0];
    loadCalendar();
}

// Quick booking
function quickBooking(time) {
    const date = document.getElementById('selectedDate').value;
    document.getElementById('bookingDate').value = date;
    document.getElementById('bookingTime').value = time;
    openAddBookingModal();
}

// Booking details
async function viewBookingDetails(bookingId) {
    try {
        showLoading();
        const response = await fetch(`../../backend/bookings/read.php?limit=1000`);
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
            const booking = result.data.bookings.find(b => b.id == bookingId);
            if (booking) {
                selectedBooking = booking;
                renderBookingDetails(booking);
                document.getElementById('viewBookingModal').classList.remove('hidden');
            }
        }
    } catch (error) {
        hideLoading();
        showToast('Ma\'lumotlarni olishda xatolik', 'error');
    }
}

function renderBookingDetails(booking) {
    const container = document.getElementById('bookingDetails');
    container.innerHTML = `
        <div class="space-y-3">
            <div class="flex items-center justify-between pb-3 border-b">
                <span class="text-sm text-gray-600">Mijoz</span>
                <span class="font-semibold">${booking.customer_name}</span>
            </div>
            <div class="flex items-center justify-between pb-3 border-b">
                <span class="text-sm text-gray-600">Telefon</span>
                <span class="font-semibold">${booking.customer_phone}</span>
            </div>
            <div class="flex items-center justify-between pb-3 border-b">
                <span class="text-sm text-gray-600">Paket</span>
                <span class="font-semibold">${booking.package_name}</span>
            </div>
            <div class="flex items-center justify-between pb-3 border-b">
                <span class="text-sm text-gray-600">Qoldiq</span>
                <span class="font-semibold text-green-600">${booking.package_progress.remaining} / ${booking.package_progress.total}</span>
            </div>
            <div class="flex items-center justify-between pb-3 border-b">
                <span class="text-sm text-gray-600">Sana va Vaqt</span>
                <span class="font-semibold">${booking.slot_date} ${booking.slot_time}</span>
            </div>
            <div class="flex items-center justify-between pb-3 border-b">
                <span class="text-sm text-gray-600">Status</span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold ${
                    booking.status === 'scheduled' ? 'bg-blue-100 text-blue-800' :
                    booking.status === 'published' ? 'bg-green-100 text-green-800' :
                    'bg-red-100 text-red-800'
                }">
                    ${booking.status.toUpperCase()}
                </span>
            </div>
            <div class="pt-3">
                <span class="text-sm text-gray-600 block mb-2">Reklama tavsifi</span>
                <p class="text-gray-800">${booking.ad_description}</p>
            </div>
            ${booking.notes ? `
                <div class="pt-3">
                    <span class="text-sm text-gray-600 block mb-2">Izoh</span>
                    <p class="text-gray-800">${booking.notes}</p>
                </div>
            ` : ''}
        </div>
    `;
}

function closeViewBookingModal() {
    document.getElementById('viewBookingModal').classList.add('hidden');
}

async function deleteBooking() {
    if (!selectedBooking) return;
    
    if (!confirm(`${selectedBooking.customer_name} ning bookingini o'chirmoqchimisiz?`)) return;
    
    try {
        showLoading();
        const formData = new FormData();
        formData.append('id', selectedBooking.id);
        
        const response = await fetch('../../backend/bookings/delete.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
            showToast('Booking o\'chirildi', 'success');
            closeViewBookingModal();
            loadCalendar();
        } else {
            showToast(result.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('O\'chirishda xatolik', 'error');
    }
}

// Modal functions
async function openAddBookingModal() {
    try {
        const customersResponse = await fetch('../../backend/customers/read.php?status=active&limit=1000');
        const customersResult = await customersResponse.json();
        
        if (customersResult.success) {
            const customerSelect = document.getElementById('bookingCustomer');
            
            customerSelect.innerHTML = '<option value="">Mijozni tanlang...</option>' +
                customersResult.data.customers.map(c => 
                    `<option value="${c.id}">${c.full_name} - ${c.phone}</option>`
                ).join('');
            
            // Set default date if not set
            if (!document.getElementById('bookingDate').value) {
                document.getElementById('bookingDate').value = document.getElementById('selectedDate').value;
            }
            
            document.getElementById('bookingModal').classList.remove('hidden');
        }
    } catch (error) {
        showToast('Ma\'lumotlarni yuklashda xatolik', 'error');
    }
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
    document.getElementById('bookingForm').reset();
}

async function loadCustomerPackages() {
    const customerId = document.getElementById('bookingCustomer').value;
    const packageSelect = document.getElementById('bookingPackage');
    const infoText = document.getElementById('packageInfo');
    
    if (!customerId) {
        packageSelect.innerHTML = '<option value="">Avval mijozni tanlang...</option>';
        infoText.textContent = '';
        return;
    }
    
    try {
        const response = await fetch(`../../backend/customers/get_by_id.php?id=${customerId}`);
        const result = await response.json();
        
        if (result.success) {
            const activePackages = result.data.packages.filter(p => p.status === 'active' && p.remaining_ads > 0);
            
            if (activePackages.length === 0) {
                packageSelect.innerHTML = '<option value="">Bu mijozda aktiv paket yo\'q</option>';
                infoText.textContent = 'Avval paket biriktiring va to\'lov qabul qiling.';
                return;
            }
            
            packageSelect.innerHTML = '<option value="">Paketni tanlang...</option>' +
                activePackages.map(p => 
                    `<option value="${p.id}" data-remaining="${p.remaining_ads}" data-total="${p.total_ads}">
                        ${p.package_name} - ${p.remaining_ads}/${p.total_ads} qolgan
                    </option>`
                ).join('');
            
            packageSelect.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                if (option.value) {
                    infoText.textContent = `Qoldiq: ${option.dataset.remaining} ta reklama`;
                } else {
                    infoText.textContent = '';
                }
            });
        }
    } catch (error) {
        showToast('Paketlarni yuklashda xatolik', 'error');
    }
}

// Booking Form submit
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        showLoading();
        const response = await fetch('../../backend/bookings/create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        hideLoading();
        
        if (result.success) {
            showToast('Booking muvaffaqiyatli qilindi!', 'success');
            closeBookingModal();
            loadCalendar();
        } else {
            showToast(result.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('Xatolik yuz berdi', 'error');
    }
});

// Load on page ready
document.addEventListener('DOMContentLoaded', function() {
    loadCalendar();
});

// Qidirish funksiyasi
function searchBookings() {
    const searchTerm = document.getElementById('calendarSearch').value.toLowerCase();
    
    if (!searchTerm) {
        renderCalendar({ time_slots: allBookingsData, statistics: {} });
        return;
    }
    
    const filtered = allBookingsData.filter(slot => {
        if (!slot.is_booked) return false;
        
        const customerName = (slot.booking.customer_name || '').toLowerCase();
        const description = (slot.booking.ad_description || '').toLowerCase();
        const phone = (slot.booking.customer_phone || '').toLowerCase();
        
        return customerName.includes(searchTerm) || 
               description.includes(searchTerm) || 
               phone.includes(searchTerm);
    });
    
    if (filtered.length === 0) {
        document.getElementById('timeSlotsGrid').innerHTML = `
            <div class="col-span-full text-center py-12 text-gray-500">
                <i class="fas fa-search text-5xl mb-3 opacity-50"></i>
                <p class="text-lg">Hech narsa topilmadi: "${searchTerm}"</p>
                <button onclick="document.getElementById('calendarSearch').value=''; searchBookings();" class="mt-4 text-blue-600 hover:text-blue-700">
                    Tozalash
                </button>
            </div>
        `;
    } else {
        renderCalendar({ time_slots: filtered, statistics: {} });
    }
}

// Quick customer modal
function openQuickAddCustomerFromCalendar() {
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
            
            // Booking modalidagi selectga qo'shish
            const select = document.getElementById('bookingCustomer');
            const option = new Option(
                `${result.data.full_name} - ${result.data.phone}`,
                result.data.id
            );
            select.add(option);
            select.value = result.data.id;
            
            // Paketlarni yuklash
            loadCustomerPackages();
        } else {
            showToast(result.error, 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('Xatolik yuz berdi', 'error');
    }
});
</script>

<?php include '../components/footer.php'; ?>