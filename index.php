<?php
session_start();
require_once __DIR__ . '/config.php';

// Logout handler
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Login handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $login_error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
    }
}

// Check if logged in
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_logged_in ? 'Admin Dashboard - Quản Trị Xổ Số' : 'Đăng Nhập - Quản Trị Xổ Số'; ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Mobile menu animation */
        @media (max-width: 768px) {
            .mobile-menu {
                transition: transform 0.3s ease-in-out;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

<?php if (!$is_logged_in): ?>
    <!-- Login Page -->
    <div class="min-h-screen flex items-center justify-center px-4 py-12 bg-gradient-to-br from-blue-600 via-blue-700 to-purple-700">
        <div class="w-full max-w-md">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="bg-white w-20 h-20 mx-auto rounded-full flex items-center justify-center shadow-lg mb-4">
                    <i class="fas fa-ticket-alt text-blue-600 text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Quản Trị Xổ Số</h1>
                <p class="text-blue-200">Hệ thống quản lý kết quả & người dùng</p>
            </div>

            <!-- Login Form -->
            <div class="bg-white rounded-lg shadow-2xl p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Đăng Nhập</h2>

                <?php if (isset($login_error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                            <i class="fas fa-user mr-2"></i>Tên đăng nhập
                        </label>
                        <input type="text"
                               id="username"
                               name="username"
                               required
                               autocomplete="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nhập tên đăng nhập">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            <i class="fas fa-lock mr-2"></i>Mật khẩu
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               autocomplete="current-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nhập mật khẩu">
                    </div>

                    <button type="submit"
                            name="login"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>Đăng Nhập
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-gray-600">
                    <p>Default: admin / admin123</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-blue-100 text-sm">
                <p>&copy; <?php echo date('Y'); ?> Hệ Thống Quản Trị Xổ Số</p>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Admin Dashboard -->
    <div x-data="appData()" x-init="init()">

        <!-- Mobile Header -->
        <div class="lg:hidden bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-ticket-alt text-2xl"></i>
                    <span class="font-bold text-lg">Admin Panel</span>
                </div>
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-white focus:outline-none">
                    <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'" class="text-2xl"></i>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen"
                 x-cloak
                 @click.away="mobileMenuOpen = false"
                 class="bg-blue-700 px-4 pb-4 space-y-2">
                <button @click="currentTab = 'dashboard'; mobileMenuOpen = false"
                        :class="currentTab === 'dashboard' ? 'bg-white text-blue-600' : 'bg-blue-600 hover:bg-blue-500'"
                        class="w-full text-left px-4 py-3 rounded-lg transition flex items-center">
                    <i class="fas fa-chart-line mr-3 w-5"></i>Dashboard
                </button>
                <button @click="currentTab = 'lottery'; mobileMenuOpen = false"
                        :class="currentTab === 'lottery' ? 'bg-white text-blue-600' : 'bg-blue-600 hover:bg-blue-500'"
                        class="w-full text-left px-4 py-3 rounded-lg transition flex items-center">
                    <i class="fas fa-trophy mr-3 w-5"></i>Kết Quả XS
                </button>
                <button @click="currentTab = 'users'; mobileMenuOpen = false"
                        :class="currentTab === 'users' ? 'bg-white text-blue-600' : 'bg-blue-600 hover:bg-blue-500'"
                        class="w-full text-left px-4 py-3 rounded-lg transition flex items-center">
                    <i class="fas fa-users mr-3 w-5"></i>Người Dùng
                </button>
                <a href="?logout=1"
                   class="w-full text-left px-4 py-3 rounded-lg transition flex items-center bg-red-600 hover:bg-red-500 text-white">
                    <i class="fas fa-sign-out-alt mr-3 w-5"></i>Đăng Xuất
                </a>
            </div>
        </div>

        <!-- Desktop Header -->
        <nav class="hidden lg:block bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-ticket-alt text-3xl"></i>
                        <div>
                            <h1 class="text-2xl font-bold">Quản Trị Xổ Số</h1>
                            <p class="text-sm text-blue-200">Xin chào, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="currentTab = 'dashboard'"
                                :class="currentTab === 'dashboard' ? 'bg-white text-blue-600' : 'bg-blue-700 hover:bg-blue-600'"
                                class="px-4 py-2 rounded-lg transition">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard
                        </button>
                        <button @click="currentTab = 'lottery'"
                                :class="currentTab === 'lottery' ? 'bg-white text-blue-600' : 'bg-blue-700 hover:bg-blue-600'"
                                class="px-4 py-2 rounded-lg transition">
                            <i class="fas fa-trophy mr-2"></i>Kết Quả XS
                        </button>
                        <button @click="currentTab = 'users'"
                                :class="currentTab === 'users' ? 'bg-white text-blue-600' : 'bg-blue-700 hover:bg-blue-600'"
                                class="px-4 py-2 rounded-lg transition">
                            <i class="fas fa-users mr-2"></i>Người Dùng
                        </button>
                        <a href="?logout=1"
                           class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Đăng Xuất
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-4 lg:py-8">

            <!-- Dashboard Tab -->
            <div x-show="currentTab === 'dashboard'" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-6 lg:mb-8">
                    <!-- Stats Cards -->
                    <div class="bg-white rounded-lg shadow-md p-4 lg:p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs lg:text-sm font-medium">Tổng Người Dùng</p>
                                <h3 class="text-2xl lg:text-3xl font-bold text-gray-800 mt-2" x-text="stats.totalUsers"></h3>
                            </div>
                            <div class="bg-blue-100 rounded-full p-3 lg:p-4">
                                <i class="fas fa-users text-blue-600 text-xl lg:text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4 lg:p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs lg:text-sm font-medium">Users Còn Hạn</p>
                                <h3 class="text-2xl lg:text-3xl font-bold text-gray-800 mt-2" x-text="stats.activeUsers"></h3>
                            </div>
                            <div class="bg-green-100 rounded-full p-3 lg:p-4">
                                <i class="fas fa-user-check text-green-600 text-xl lg:text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-4 lg:p-6 border-l-4 border-red-500 sm:col-span-2 lg:col-span-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs lg:text-sm font-medium">Users Hết Hạn</p>
                                <h3 class="text-2xl lg:text-3xl font-bold text-gray-800 mt-2" x-text="stats.expiredUsers"></h3>
                            </div>
                            <div class="bg-red-100 rounded-full p-3 lg:p-4">
                                <i class="fas fa-user-times text-red-600 text-xl lg:text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-4 lg:p-6">
                    <h2 class="text-lg lg:text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>Thao Tác Nhanh
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:gap-4">
                        <button @click="currentTab = 'lottery'"
                                class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 lg:px-6 py-3 lg:py-4 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-md flex items-center justify-center text-sm lg:text-base">
                            <i class="fas fa-download mr-2 lg:mr-3 text-lg lg:text-xl"></i>
                            <span class="font-semibold">Lấy Kết Quả XS</span>
                        </button>
                        <button @click="currentTab = 'users'"
                                class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 lg:px-6 py-3 lg:py-4 rounded-lg hover:from-green-600 hover:to-green-700 transition shadow-md flex items-center justify-center text-sm lg:text-base">
                            <i class="fas fa-user-plus mr-2 lg:mr-3 text-lg lg:text-xl"></i>
                            <span class="font-semibold">Thêm User Mới</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lottery Results Tab -->
            <div x-show="currentTab === 'lottery'" x-cloak>
                <div class="bg-white rounded-lg shadow-md p-4 lg:p-6 mb-4 lg:mb-6">
                    <h2 class="text-lg lg:text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i>Lấy Kết Quả Xổ Số
                    </h2>

                    <form @submit.prevent="fetchLotteryResults()" class="space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Miền</label>
                                <select x-model="lotteryForm.region"
                                        class="w-full px-4 py-2 lg:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="mb">Miền Bắc</option>
                                    <option value="mt">Miền Trung</option>
                                    <option value="mn">Miền Nam</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ngày</label>
                                <input type="date"
                                       x-model="lotteryForm.date"
                                       :max="new Date().toISOString().split('T')[0]"
                                       class="w-full px-4 py-2 lg:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div class="flex items-end">
                                <button type="submit"
                                        :disabled="loading"
                                        class="w-full bg-blue-600 text-white px-6 py-2 lg:py-3 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-sync-alt mr-2" :class="{'fa-spin': loading}"></i>
                                    <span x-text="loading ? 'Đang xử lý...' : 'Lấy Kết Quả'"></span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Result Message -->
                    <div x-show="lotteryResult" x-cloak class="mt-4">
                        <div :class="lotteryResult?.success ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'"
                             class="border-l-4 p-4 rounded">
                            <div class="flex items-start">
                                <i :class="lotteryResult?.success ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'"
                                   class="fas text-xl mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="font-semibold text-sm lg:text-base" x-show="lotteryResult?.success">
                                        Thành công! Đã thêm <span x-text="lotteryResult?.inserted"></span> kết quả mới,
                                        cập nhật <span x-text="lotteryResult?.updated"></span> kết quả
                                    </p>
                                    <p class="font-semibold text-sm lg:text-base" x-show="!lotteryResult?.success" x-text="lotteryResult?.error"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div x-show="currentTab === 'users'" x-cloak>

                <!-- Add/Edit User Form -->
                <div class="bg-white rounded-lg shadow-md p-4 lg:p-6 mb-4 lg:mb-6">
                    <h2 class="text-lg lg:text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user-edit text-blue-500 mr-2"></i>
                        <span x-text="editingUser ? 'Cập Nhật User' : 'Thêm User Mới'"></span>
                    </h2>

                    <form @submit.prevent="saveUser()" class="space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">User ID *</label>
                                <input type="text"
                                       x-model="userForm.userId"
                                       :disabled="editingUser"
                                       required
                                       placeholder="Nhập User ID"
                                       class="w-full px-4 py-2 lg:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ngày Hết Hạn *</label>
                                <input type="datetime-local"
                                       x-model="userForm.expirationDate"
                                       required
                                       class="w-full px-4 py-2 lg:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                            <button type="submit"
                                    :disabled="loading"
                                    class="bg-green-600 text-white px-6 py-2 lg:py-3 rounded-lg hover:bg-green-700 transition disabled:opacity-50 w-full sm:w-auto">
                                <i class="fas fa-save mr-2"></i>
                                <span x-text="editingUser ? 'Cập Nhật' : 'Thêm Mới'"></span>
                            </button>

                            <button type="button"
                                    @click="cancelEdit()"
                                    x-show="editingUser"
                                    class="bg-gray-500 text-white px-6 py-2 lg:py-3 rounded-lg hover:bg-gray-600 transition w-full sm:w-auto">
                                <i class="fas fa-times mr-2"></i>Hủy
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Users List -->
                <div class="bg-white rounded-lg shadow-md p-4 lg:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 space-y-2 sm:space-y-0">
                        <h2 class="text-lg lg:text-xl font-bold text-gray-800">
                            <i class="fas fa-list text-purple-500 mr-2"></i>Danh Sách Users
                        </h2>
                        <button @click="loadUsers()"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm lg:text-base">
                            <i class="fas fa-sync-alt mr-2" :class="{'fa-spin': loading}"></i>Làm Mới
                        </button>
                    </div>

                    <!-- Search Box -->
                    <div class="mb-4">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Tìm kiếm User ID..."
                               class="w-full px-4 py-2 lg:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Users Table - Desktop -->
                    <div class="hidden lg:block overflow-x-auto custom-scrollbar" style="max-height: 500px; overflow-y: auto;">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày Hết Hạn</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng Thái</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="user in filteredUsers" :key="user.userId">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-circle text-gray-400 text-xl mr-3"></i>
                                                <span class="font-medium text-gray-900" x-text="user.userId"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.expirationDate"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="isUserActive(user) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                  class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                <i :class="isUserActive(user) ? 'fa-check-circle' : 'fa-times-circle'" class="fas mr-1"></i>
                                                <span x-text="isUserActive(user) ? 'Còn hạn' : 'Hết hạn'"></span>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                            <button @click="editUser(user)"
                                                    class="text-blue-600 hover:text-blue-900 font-medium">
                                                <i class="fas fa-edit mr-1"></i>Sửa
                                            </button>
                                            <button @click="deleteUser(user.userId)"
                                                    class="text-red-600 hover:text-red-900 font-medium">
                                                <i class="fas fa-trash mr-1"></i>Xóa
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="filteredUsers.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>Không có người dùng nào</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Users Cards - Mobile -->
                    <div class="lg:hidden space-y-3 custom-scrollbar" style="max-height: 500px; overflow-y: auto;">
                        <template x-for="user in filteredUsers" :key="user.userId">
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-user-circle text-gray-400 text-2xl"></i>
                                        <div>
                                            <p class="font-semibold text-gray-900" x-text="user.userId"></p>
                                            <p class="text-xs text-gray-500" x-text="user.expirationDate"></p>
                                        </div>
                                    </div>
                                    <span :class="isUserActive(user) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          class="px-2 py-1 text-xs font-semibold rounded-full whitespace-nowrap">
                                        <i :class="isUserActive(user) ? 'fa-check-circle' : 'fa-times-circle'" class="fas"></i>
                                        <span x-text="isUserActive(user) ? 'Còn hạn' : 'Hết hạn'"></span>
                                    </span>
                                </div>
                                <div class="flex space-x-2">
                                    <button @click="editUser(user)"
                                            class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                                        <i class="fas fa-edit mr-1"></i>Sửa
                                    </button>
                                    <button @click="deleteUser(user.userId)"
                                            class="flex-1 bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition text-sm">
                                        <i class="fas fa-trash mr-1"></i>Xóa
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredUsers.length === 0" class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có người dùng nào</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Toast Notification -->
        <div x-show="toast.show"
             x-transition
             @click="toast.show = false"
             :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
             class="fixed bottom-4 right-4 left-4 sm:left-auto sm:w-96 text-white px-4 lg:px-6 py-3 lg:py-4 rounded-lg shadow-lg cursor-pointer z-50"
             x-cloak>
            <div class="flex items-center">
                <i :class="toast.type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"
                   class="fas text-lg lg:text-xl mr-3"></i>
                <span class="text-sm lg:text-base" x-text="toast.message"></span>
            </div>
        </div>
    </div>

    <script>
        function appData() {
            return {
                currentTab: 'dashboard',
                loading: false,
                mobileMenuOpen: false,

                // Stats
                stats: {
                    totalUsers: 0,
                    activeUsers: 0,
                    expiredUsers: 0
                },

                // Lottery Form
                lotteryForm: {
                    region: 'mn',
                    date: new Date().toISOString().split('T')[0]
                },
                lotteryResult: null,

                // Users
                users: [],
                searchQuery: '',
                editingUser: false,
                userForm: {
                    userId: '',
                    expirationDate: ''
                },

                // Toast
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },

                // Initialize
                init() {
                    this.loadUsers();
                },

                // Show toast notification
                showToast(message, type = 'success') {
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },

                // Fetch lottery results
                async fetchLotteryResults() {
                    this.loading = true;
                    this.lotteryResult = null;

                    try {
                        const response = await fetch('api_fetch.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(this.lotteryForm)
                        });

                        const data = await response.json();
                        this.lotteryResult = data;

                        if (data.success) {
                            this.showToast('Lấy kết quả xổ số thành công!', 'success');
                        } else {
                            this.showToast(data.error || 'Có lỗi xảy ra', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showToast('Lỗi kết nối đến server', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                // Load users
                async loadUsers() {
                    this.loading = true;

                    try {
                        const response = await fetch('api_users.php');
                        const data = await response.json();

                        if (data.success) {
                            this.users = data.users || [];
                            this.updateStats();
                        } else {
                            this.showToast('Không thể tải danh sách người dùng', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showToast('Lỗi kết nối đến server', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                // Save user (create or update)
                async saveUser() {
                    this.loading = true;

                    try {
                        const response = await fetch('api_users.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(this.userForm)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showToast(
                                this.editingUser ? 'Cập nhật người dùng thành công!' : 'Thêm người dùng thành công!',
                                'success'
                            );
                            this.cancelEdit();
                            this.loadUsers();
                        } else {
                            this.showToast(data.error || 'Có lỗi xảy ra', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showToast('Lỗi kết nối đến server', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                // Edit user
                editUser(user) {
                    this.editingUser = true;
                    this.userForm.userId = user.userId;

                    // Convert to datetime-local format
                    const date = new Date(user.expirationDate);
                    const offset = date.getTimezoneOffset();
                    const localDate = new Date(date.getTime() - (offset * 60 * 1000));
                    this.userForm.expirationDate = localDate.toISOString().slice(0, 16);

                    // Scroll to form
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                // Cancel edit
                cancelEdit() {
                    this.editingUser = false;
                    this.userForm.userId = '';
                    this.userForm.expirationDate = '';
                },

                // Delete user
                async deleteUser(userId) {
                    if (!confirm(`Bạn có chắc muốn xóa người dùng "${userId}"?`)) {
                        return;
                    }

                    this.loading = true;

                    try {
                        const response = await fetch(`api_users.php?userId=${encodeURIComponent(userId)}`, {
                            method: 'DELETE'
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showToast('Xóa người dùng thành công!', 'success');
                            this.loadUsers();
                        } else {
                            this.showToast(data.error || 'Có lỗi xảy ra', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showToast('Lỗi kết nối đến server', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                // Check if user is active
                isUserActive(user) {
                    return new Date(user.expirationDate) > new Date();
                },

                // Update stats
                updateStats() {
                    this.stats.totalUsers = this.users.length;
                    this.stats.activeUsers = this.users.filter(u => this.isUserActive(u)).length;
                    this.stats.expiredUsers = this.users.filter(u => !this.isUserActive(u)).length;
                },

                // Filtered users based on search
                get filteredUsers() {
                    if (!this.searchQuery) {
                        return this.users;
                    }

                    const query = this.searchQuery.toLowerCase();
                    return this.users.filter(user =>
                        user.userId.toLowerCase().includes(query)
                    );
                }
            }
        }
    </script>

<?php endif; ?>

</body>
</html>
