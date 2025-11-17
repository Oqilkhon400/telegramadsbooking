<?php
/**
 * Footer Component
 * Barcha admin sahifalar uchun footer
 */
?>

    <!-- Main JavaScript -->
    <script src="../../assets/js/main.js"></script>
    
    <!-- Toast Notification Script -->
    <script>
        // Toast notification funksiyasi
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-20 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 
                'bg-blue-500'
            } text-white`;
            
            toast.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' : 
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
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
        
        // Loading overlay
        function showLoading() {
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            overlay.innerHTML = `
                <div class="bg-white rounded-lg p-8 flex flex-col items-center space-y-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    <p class="text-gray-700 font-medium">Yuklanmoqda...</p>
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
        
        // Confirm dialog
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // Format money
        function formatMoney(amount) {
            return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('uz-UZ', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
        
        // Format datetime
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('uz-UZ', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Check session
        function checkSession() {
            fetch('../../backend/auth/check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        window.location.href = '../login.php';
                    }
                })
                .catch(error => {
                    console.error('Session check error:', error);
                });
        }
        
        // Session tekshirish har 5 daqiqada
        setInterval(checkSession, 300000); // 5 minutes
    </script>
    
    <!-- Page specific scripts -->
    <?php if (isset($page_script)): ?>
        <script src="../../assets/js/<?php echo $page_script; ?>"></script>
    <?php endif; ?>

</body>
</html>