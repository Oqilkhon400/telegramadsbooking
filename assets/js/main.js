/**
 * Main JavaScript File
 * Umumiy JavaScript funksiyalar
 */

'use strict';

// ========================================
// Global Variables
// ========================================

const API_BASE_URL = '../../backend';
let currentUser = null;

// ========================================
// Initialization
// ========================================

document.addEventListener('DOMContentLoaded', function () {
    initializeApp();
});

function initializeApp() {
    // Check session
    checkUserSession();

    // Initialize tooltips
    initializeTooltips();

    // Initialize date inputs
    initializeDateInputs();

    // Initialize phone inputs
    initializePhoneInputs();

    // Add keyboard shortcuts
    initializeKeyboardShortcuts();

    console.log('App initialized successfully');
}

// ========================================
// Session Management
// ========================================

async function checkUserSession() {
    try {
        const response = await fetch(`${API_BASE_URL}/auth/check_session.php`);
        const result = await response.json();

        if (result.success) {
            currentUser = result.data.user;
            updateUserInfo();
        } else {
            // Session expired
            if (window.location.pathname.indexOf('login.php') === -1) {
                window.location.href = '../login.php';
            }
        }
    } catch (error) {
        console.error('Session check error:', error);
    }
}

function updateUserInfo() {
    // Update user info in UI if needed
    console.log('Current user:', currentUser);
}

// ========================================
// API Helpers
// ========================================

async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {}
    };

    if (data) {
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API request error:', error);
        throw error;
    }
}

// ========================================
// UI Helpers
// ========================================

function showToast(message, type = 'success', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `fixed top-20 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${type === 'success' ? 'bg-green-500' :
            type === 'error' ? 'bg-red-500' :
                type === 'warning' ? 'bg-yellow-500' :
                    'bg-blue-500'
        } text-white`;

    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            <i class="fas ${type === 'success' ? 'fa-check-circle' :
            type === 'error' ? 'fa-times-circle' :
                type === 'warning' ? 'fa-exclamation-triangle' :
                    'fa-info-circle'
        } text-xl"></i>
            <span class="font-medium">${message}</span>
        </div>
    `;

    document.body.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 10);

    // Animate out and remove
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, duration);
}

function showLoading(message = 'Yuklanmoqda...') {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
    overlay.innerHTML = `
        <div class="bg-white rounded-lg p-8 flex flex-col items-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            <p class="text-gray-700 font-medium">${message}</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        document.body.removeChild(overlay);
    }
}

function showConfirm(message, onConfirm, onCancel = null) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl max-w-md w-full p-6 animate-fadeIn">
            <div class="text-center mb-6">
                <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle text-3xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Tasdiqlash</h3>
                <p class="text-gray-600">${message}</p>
            </div>
            <div class="flex space-x-3">
                <button id="confirmBtn" class="flex-1 bg-blue-500 text-white py-3 rounded-lg font-semibold hover:bg-blue-600 transition">
                    Ha
                </button>
                <button id="cancelBtn" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                    Yo'q
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('confirmBtn').addEventListener('click', () => {
        document.body.removeChild(modal);
        if (onConfirm) onConfirm();
    });

    document.getElementById('cancelBtn').addEventListener('click', () => {
        document.body.removeChild(modal);
        if (onCancel) onCancel();
    });

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
            if (onCancel) onCancel();
        }
    });
}

// ========================================
// Format Helpers
// ========================================

function formatMoney(amount) {
    if (!amount) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatPhone(phone) {
    if (!phone) return '';
    // +998901234567 -> +998 90 123 45 67
    phone = phone.replace(/\D/g, '');
    if (phone.length === 12) {
        return `+${phone.substr(0, 3)} ${phone.substr(3, 2)} ${phone.substr(5, 3)} ${phone.substr(8, 2)} ${phone.substr(10, 2)}`;
    }
    return phone;
}

function parseFormattedMoney(formatted) {
    if (!formatted) return 0;
    return parseInt(formatted.replace(/\D/g, '')) || 0;
}

// ========================================
// Validation Helpers
// ========================================

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\+]?[0-9]{9,15}$/;
    return re.test(phone.replace(/\s/g, ''));
}

function validateRequired(value) {
    return value && value.trim() !== '';
}

function validateNumber(value, min = null, max = null) {
    const num = parseFloat(value);
    if (isNaN(num)) return false;
    if (min !== null && num < min) return false;
    if (max !== null && num > max) return false;
    return true;
}

// ========================================
// Date Helpers
// ========================================

function getToday() {
    return new Date().toISOString().split('T')[0];
}

function addDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result.toISOString().split('T')[0];
}

function getWeekStart(date = new Date()) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    d.setDate(diff);
    return d.toISOString().split('T')[0];
}

function getMonthStart(date = new Date()) {
    const d = new Date(date);
    return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().split('T')[0];
}

function getMonthEnd(date = new Date()) {
    const d = new Date(date);
    return new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().split('T')[0];
}

// ========================================
// DOM Helpers
// ========================================

function createElement(tag, className = '', innerHTML = '') {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (innerHTML) element.innerHTML = innerHTML;
    return element;
}

function removeElement(element) {
    if (element && element.parentNode) {
        element.parentNode.removeChild(element);
    }
}

function toggleClass(element, className) {
    if (element) {
        element.classList.toggle(className);
    }
}

// ========================================
// Local Storage Helpers
// ========================================

function saveToStorage(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
        return true;
    } catch (error) {
        console.error('Storage save error:', error);
        return false;
    }
}

function getFromStorage(key, defaultValue = null) {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
    } catch (error) {
        console.error('Storage get error:', error);
        return defaultValue;
    }
}

function removeFromStorage(key) {
    try {
        localStorage.removeItem(key);
        return true;
    } catch (error) {
        console.error('Storage remove error:', error);
        return false;
    }
}

function clearStorage() {
    try {
        localStorage.clear();
        return true;
    } catch (error) {
        console.error('Storage clear error:', error);
        return false;
    }
}

// ========================================
// Debounce & Throttle
// ========================================

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function (...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ========================================
// Copy to Clipboard
// ========================================

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Nusxa olindi', 'success');
        }).catch(err => {
            console.error('Copy error:', err);
            showToast('Nusxa olishda xatolik', 'error');
        });
    } else {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = 0;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showToast('Nusxa olindi', 'success');
        } catch (err) {
            showToast('Nusxa olishda xatolik', 'error');
        }
        document.body.removeChild(textarea);
    }
}

// ========================================
// Tooltips
// ========================================

function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.classList.add('tooltip');
    });
}

// ========================================
// Date Input Helpers
// ========================================

function initializeDateInputs() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            // Set min date to today
            input.min = getToday();
        }
    });
}

// ========================================
// Phone Input Formatting
// ========================================

function initializePhoneInputs() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('998')) {
                value = '+' + value;
            } else if (!value.startsWith('+')) {
                value = '+998' + value;
            }
            e.target.value = value;
        });
    });
}

// ========================================
// Keyboard Shortcuts
// ========================================

function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
        // Ctrl/Cmd + K: Quick search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.focus();
        }

        // Escape: Close modals
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.fixed.inset-0:not(.hidden)');
            modals.forEach(modal => {
                if (modal.id && modal.id.includes('Modal')) {
                    const closeButton = modal.querySelector('[onclick*="close"]');
                    if (closeButton) closeButton.click();
                }
            });
        }
    });
}

// ========================================
// Export Data
// ========================================

function exportToCSV(data, filename) {
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showToast('Fayl yuklandi', 'success');
}

function convertToCSV(data) {
    if (!data || data.length === 0) return '';

    const headers = Object.keys(data[0]);
    const csv = [
        headers.join(','),
        ...data.map(row => headers.map(header =>
            JSON.stringify(row[header] || '')
        ).join(','))
    ].join('\n');

    return csv;
}

// ========================================
// Print Helper
// ========================================

function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<link rel="stylesheet" href="../../assets/css/style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(element.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// ========================================
// Network Status
// ========================================

window.addEventListener('online', () => {
    showToast('Internet aloqasi qayta tiklandi', 'success');
});

window.addEventListener('offline', () => {
    showToast('Internet aloqasi yo\'q', 'warning', 5000);
});

// ========================================
// Performance Monitoring
// ========================================

if ('performance' in window) {
    window.addEventListener('load', () => {
        const perfData = performance.timing;
        const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        console.log(`Page load time: ${pageLoadTime}ms`);
    });
}

// ========================================
// Global Error Handler
// ========================================

window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    // You can send error to logging service here
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    // You can send error to logging service here
});

console.log('Main.js loaded successfully');