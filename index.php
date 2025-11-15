<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị Hệ Thống Xổ Số</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
        .table-scroll {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-100" x-data="appData()" x-init="init()">

    <!-- Navbar -->
    <nav class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-ticket-alt text-3xl"></i>
                    <div>
                        <h1 class="text-2xl font-bold">Quản Trị Xổ Số</h1>
                        <p class="text-sm text-blue-200">Hệ thống quản lý kết quả & người dùng</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
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
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">

        <!-- Dashboard Tab -->
        <div x-show="currentTab === 'dashboard'" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Stats Cards -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Tổng Người Dùng</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2" x-text="stats.totalUsers"></h3>
                        </div>
                        <div class="bg-blue-100 rounded-full p-4">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Users Còn Hạn</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2" x-text="stats.activeUsers"></h3>
                        </div>
                        <div class="bg-green-100 rounded-full p-4">
                            <i class="fas fa-user-check text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Users Hết Hạn</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-2" x-text="stats.expiredUsers"></h3>
                        </div>
                        <div class="bg-red-100 rounded-full p-4">
                            <i class="fas fa-user-times text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>Thao Tác Nhanh
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button @click="currentTab = 'lottery'"
                            class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4 rounded-lg hover:from-blue-600 hover:to-blue-700 transition shadow-md flex items-center justify-center">
                        <i class="fas fa-download mr-3 text-xl"></i>
                        <span class="font-semibold">Lấy Kết Quả Xổ Số Mới</span>
                    </button>
                    <button @click="currentTab = 'users'"
                            class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-4 rounded-lg hover:from-green-600 hover:to-green-700 transition shadow-md flex items-center justify-center">
                        <i class="fas fa-user-plus mr-3 text-xl"></i>
                        <span class="font-semibold">Thêm Người Dùng Mới</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Lottery Results Tab -->
        <div x-show="currentTab === 'lottery'" x-cloak>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-trophy text-yellow-500 mr-2"></i>Lấy Kết Quả Xổ Số
                </h2>

                <form @submit.prevent="fetchLotteryResults()" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Miền</label>
                            <select x-model="lotteryForm.region"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div class="flex items-end">
                            <button type="submit"
                                    :disabled="loading"
                                    class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
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
                        <div class="flex items-center">
                            <i :class="lotteryResult?.success ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'"
                               class="fas text-xl mr-3"></i>
                            <div>
                                <p class="font-semibold" x-show="lotteryResult?.success">
                                    Thành công! Đã thêm <span x-text="lotteryResult?.inserted"></span> kết quả mới,
                                    cập nhật <span x-text="lotteryResult?.updated"></span> kết quả
                                </p>
                                <p class="font-semibold" x-show="!lotteryResult?.success" x-text="lotteryResult?.error"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Tab -->
        <div x-show="currentTab === 'users'" x-cloak>

            <!-- Add/Edit User Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-user-edit text-blue-500 mr-2"></i>
                    <span x-text="editingUser ? 'Cập Nhật Người Dùng' : 'Thêm Người Dùng Mới'"></span>
                </h2>

                <form @submit.prevent="saveUser()" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">User ID *</label>
                            <input type="text"
                                   x-model="userForm.userId"
                                   :disabled="editingUser"
                                   required
                                   placeholder="Nhập User ID"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ngày Hết Hạn *</label>
                            <input type="datetime-local"
                                   x-model="userForm.expirationDate"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <button type="submit"
                                :disabled="loading"
                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition disabled:opacity-50">
                            <i class="fas fa-save mr-2"></i>
                            <span x-text="editingUser ? 'Cập Nhật' : 'Thêm Mới'"></span>
                        </button>

                        <button type="button"
                                @click="cancelEdit()"
                                x-show="editingUser"
                                class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Hủy
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users List -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-list text-purple-500 mr-2"></i>Danh Sách Người Dùng
                    </h2>
                    <button @click="loadUsers()"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-sync-alt mr-2" :class="{'fa-spin': loading}"></i>Làm Mới
                    </button>
                </div>

                <!-- Search Box -->
                <div class="mb-4">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Tìm kiếm User ID..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Users Table -->
                <div class="table-scroll overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
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
            </div>
        </div>

    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show"
         x-transition
         @click="toast.show = false"
         :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
         class="fixed bottom-4 right-4 text-white px-6 py-4 rounded-lg shadow-lg cursor-pointer z-50"
         x-cloak>
        <div class="flex items-center">
            <i :class="toast.type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"
               class="fas text-xl mr-3"></i>
            <span x-text="toast.message"></span>
        </div>
    </div>

    <script>
        function appData() {
            return {
                currentTab: 'dashboard',
                loading: false,

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
</body>
</html>
