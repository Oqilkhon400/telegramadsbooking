<?php
/**
 * Login Page
 * Tizimga kirish sahifasi
 */

session_start();

// Agar allaqachon login bo'lsa, dashboardga yo'naltirish
if (isset($_SESSION['user_id'])) {
    header('Location: admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirish - Reklama Booking Platform</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    
    <!-- Background Decoration -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-20 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-purple-300 opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 w-80 h-80 bg-blue-300 opacity-10 rounded-full blur-3xl"></div>
    </div>
    
    <div class="w-full max-w-md relative z-10">
        
        <!-- Logo and Title -->
        <div class="text-center mb-8 float-animation">
            <div class="inline-block bg-white rounded-2xl p-6 shadow-2xl mb-4">
                <i class="fas fa-bullhorn text-6xl text-purple-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">Reklama Booking</h1>
            <p class="text-purple-200">Boshqaruv Paneliga Kirish</p>
        </div>
        
        <!-- Login Form -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8">
            
            <form id="loginForm" class="space-y-6">
                
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-purple-600"></i>Username
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="Username ni kiriting"
                    >
                </div>
                
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-purple-600"></i>Parol
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                            placeholder="Parolni kiriting"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                        >
                            <i id="passwordIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me (keyinchalik) -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-600">Eslab qolish</span>
                    </label>
                    <a href="#" class="text-sm text-purple-600 hover:text-purple-700">Parolni unutdingizmi?</a>
                </div>
                
                <!-- Error Message -->
                <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span id="errorText"></span>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    id="loginButton"
                    class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition transform hover:scale-105 shadow-lg"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Kirish
                </button>
                
            </form>
            
            <!-- Footer Info -->
            <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-500">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Xavfsiz ulanish
                </p>
            </div>
            
        </div>
        
        <!-- Copyright -->
        <div class="text-center mt-6 text-white text-sm">
            <p>&copy; 2024 Reklama Booking Platform. Barcha huquqlar himoyalangan.</p>
        </div>
        
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Show error message
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            
            errorText.textContent = message;
            errorDiv.classList.remove('hidden');
            
            setTimeout(() => {
                errorDiv.classList.add('hidden');
            }, 5000);
        }
        
        // Login form submit
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loginButton = document.getElementById('loginButton');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // Validation
            if (!username || !password) {
                showError('Username va parol kiritilishi shart!');
                return;
            }
            
            // Disable button
            loginButton.disabled = true;
            loginButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Yuklanmoqda...';
            
            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                
                const response = await fetch('/backend/auth/login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Success - redirect to dashboard
                    loginButton.innerHTML = '<i class="fas fa-check mr-2"></i>Muvaffaqiyatli!';
                    loginButton.classList.remove('from-purple-600', 'to-blue-600');
                    loginButton.classList.add('from-green-500', 'to-green-600');
                    
                    setTimeout(() => {
                        window.location.href = 'admin/index.php';
                    }, 500);
                } else {
                    // Error
                    showError(data.error || 'Login muvaffaqiyatsiz. Qayta urinib ko\'ring.');
                    loginButton.disabled = false;
                    loginButton.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Kirish';
                }
                
            } catch (error) {
                console.error('Login error:', error);
                showError('Serverga ulanishda xatolik. Qayta urinib ko\'ring.');
                loginButton.disabled = false;
                loginButton.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Kirish';
            }
        });
        
        // Enter key submit
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
    
</body>
</html>