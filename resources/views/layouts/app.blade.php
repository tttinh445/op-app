<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OP Management') - OP Web Application</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased font-sans">
    <div id="app" class="min-h-full flex flex-col lg:flex-row">
        <!-- Sidebar Navigation -->
        <aside id="main-sidebar" class="w-64 bg-white border-r border-sky-100 flex flex-col shrink-0 transition-all duration-300 ease-in-out fixed inset-y-0 left-0 z-30 lg:static -translate-x-full lg:translate-x-0 shadow-sm">
            <!-- Sidebar Header / Logo -->
            <div class="h-16 px-4 flex items-center justify-between border-b border-sky-100/80 bg-gradient-to-r from-sky-50/50 via-white to-white">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group overflow-hidden">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-sky-500 to-cyan-400 flex items-center justify-center text-white font-extrabold text-lg tracking-wider shadow-md shadow-sky-500/25 group-hover:scale-105 transition-transform duration-200 shrink-0">
                        OP
                    </div>
                    <div class="sidebar-text flex flex-col whitespace-nowrap overflow-hidden transition-opacity duration-200">
                        <span class="font-bold text-slate-900 tracking-tight text-base leading-tight">OP SYSTEM</span>
                        <span class="text-xs text-sky-600 font-medium">Google Sheets App</span>
                    </div>
                </a>

                <!-- Collapse Toggle Button on Desktop -->
                <button type="button" id="sidebar-toggle-btn" class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-sky-600 hover:bg-sky-50 transition-colors" title="Thu gọn/Mở rộng menu">
                    <svg id="collapse-icon" class="w-5 h-5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 py-4 px-3 space-y-1.5 overflow-y-auto">
                <div class="sidebar-text px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    Danh mục quản lý
                </div>

                 <!-- Đơn hàng -->
                <a href="{{ route('orders.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ (request()->routeIs('orders.*') || request()->routeIs('contracts.*')) ? 'bg-sky-50 text-sky-700 shadow-sm border border-sky-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                   title="Đơn hàng & Hợp đồng">
                    <span class="p-1.5 rounded-lg {{ (request()->routeIs('orders.*') || request()->routeIs('contracts.*')) ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'bg-slate-100 text-slate-500' }} shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </span>
                    <span class="sidebar-text whitespace-nowrap">Đơn hàng</span>
                    @if(request()->routeIs('orders.*') || request()->routeIs('contracts.*'))
                        <span class="sidebar-text ml-auto w-1.5 h-4 bg-sky-500 rounded-full"></span>
                    @endif
                </a>

                <!-- Khách hàng -->
                <a href="{{ route('customers.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('customers.*') ? 'bg-sky-50 text-sky-700 shadow-sm border border-sky-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                   title="Khách hàng">
                    <span class="p-1.5 rounded-lg {{ request()->routeIs('customers.*') ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'bg-slate-100 text-slate-500' }} shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                    <span class="sidebar-text whitespace-nowrap">Khách hàng</span>
                    @if(request()->routeIs('customers.*'))
                        <span class="sidebar-text ml-auto w-1.5 h-4 bg-sky-500 rounded-full"></span>
                    @endif
                </a>

                <!-- Nhà cung cấp -->
                <a href="{{ route('suppliers.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('suppliers.*') ? 'bg-sky-50 text-sky-700 shadow-sm border border-sky-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                   title="Nhà cung cấp">
                    <span class="p-1.5 rounded-lg {{ request()->routeIs('suppliers.*') ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'bg-slate-100 text-slate-500' }} shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                    <span class="sidebar-text whitespace-nowrap">Nhà cung cấp</span>
                    @if(request()->routeIs('suppliers.*'))
                        <span class="sidebar-text ml-auto w-1.5 h-4 bg-sky-500 rounded-full"></span>
                    @endif
                </a>

                <!-- Sản phẩm -->
                <a href="{{ route('products.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('products.*') ? 'bg-sky-50 text-sky-700 shadow-sm border border-sky-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                   title="Sản phẩm">
                    <span class="p-1.5 rounded-lg {{ request()->routeIs('products.*') ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'bg-slate-100 text-slate-500' }} shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </span>
                    <span class="sidebar-text whitespace-nowrap">Sản phẩm</span>
                    @if(request()->routeIs('products.*'))
                        <span class="sidebar-text ml-auto w-1.5 h-4 bg-sky-500 rounded-full"></span>
                    @endif
                </a>

                <div class="sidebar-text pt-4 px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    Tích hợp Dữ liệu
                </div>

                <!-- Cấu hình Google Sheets -->
                <a href="{{ route('sheets.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('sheets.*') ? 'bg-sky-50 text-sky-700 shadow-sm border border-sky-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                   title="Cấu hình Google Sheets">
                    <span class="p-1.5 rounded-lg {{ request()->routeIs('sheets.*') ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/30' : 'bg-slate-100 text-emerald-600' }} shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                    <span class="sidebar-text whitespace-nowrap">Google Sheets</span>
                    <span class="sidebar-text ml-auto px-2 py-0.5 text-[11px] font-bold bg-emerald-100 text-emerald-700 rounded-full">API</span>
                </a>

                <div class="sidebar-text pt-4 px-3 py-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    Hệ thống
                </div>

                <!-- Cài đặt -->
                <a href="{{ route('settings.employees.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('settings.*') ? 'bg-sky-50 text-sky-700 shadow-sm border border-sky-200/60' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                   title="Cài đặt">
                    <span class="p-1.5 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'bg-slate-100 text-slate-500' }} shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <span class="sidebar-text whitespace-nowrap">Cài đặt</span>
                    @if(request()->routeIs('settings.*'))
                        <span class="sidebar-text ml-auto w-1.5 h-4 bg-sky-500 rounded-full"></span>
                    @endif
                </a>
            </div>

            <!-- Sidebar Footer Status -->
            <div class="p-3 border-t border-sky-100/80 bg-slate-50/50">
                <div class="flex items-center gap-3 p-2 rounded-xl bg-white border border-sky-100/60 shadow-xs">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></div>
                    <div class="sidebar-text text-xs text-slate-600 overflow-hidden">
                        <p class="font-medium truncate">Google Sheets Sync</p>
                        <p class="text-[11px] text-emerald-600 font-semibold truncate">Sẵn sàng kết nối</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Backdrop for mobile sidebar -->
        <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 z-20 backdrop-blur-xs hidden lg:hidden"></div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Navbar Header -->
            <header class="h-16 bg-white/95 glass-header border-b border-sky-100/80 sticky top-0 z-10 px-4 sm:px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger -->
                    <button type="button" id="mobile-toggle-btn" class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page Header Title -->
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            @yield('page_title', 'Hệ thống Quản lý')
                        </h1>
                        <p class="text-xs text-slate-500 hidden sm:block">
                            @yield('page_subtitle', 'Ứng dụng quản trị kết nối trực tiếp Google Sheets')
                        </p>
                    </div>
                </div>

                <!-- Top Right Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    @yield('header_actions')

                    <a href="{{ route('sheets.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors border border-sky-200/60" title="Cấu hình liên kết Google Sheet">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="hidden md:inline">Google Sheets</span>
                    </a>
                </div>
            </header>

            <!-- Notification Messages -->
            @if(session('success'))
                <div class="mx-4 sm:mx-6 mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start justify-between shadow-xs animate-modal-in" role="alert">
                    <div class="flex items-center gap-3">
                        <span class="p-1 rounded-lg bg-emerald-500 text-white">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Thành công!</p>
                            <p class="text-xs text-emerald-700">{{ session('success') }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-4 sm:mx-6 mt-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start justify-between shadow-xs animate-modal-in" role="alert">
                    <div class="flex items-center gap-3">
                        <span class="p-1 rounded-lg bg-rose-500 text-white">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Có lỗi xảy ra!</p>
                            <p class="text-xs text-rose-700">{{ session('error') }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div class="mx-4 sm:mx-6 mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 shadow-xs animate-modal-in">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-sm font-semibold">Vui lòng kiểm tra lại dữ liệu nhập:</p>
                    </div>
                    <ul class="list-disc list-inside text-xs space-y-0.5 text-amber-700 pl-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Main Page Content -->
            <main class="flex-1 p-4 sm:p-6 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Universal Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-100 p-6 animate-modal-in">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Xác nhận xoá dữ liệu</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Hành động này không thể hoàn tác.</p>
                </div>
            </div>

            <p class="mt-4 text-sm text-slate-600">
                Bạn có chắc chắn muốn xoá mục <strong id="delete-item-title" class="text-slate-900">...</strong> này khỏi hệ thống không?
            </p>

            <form id="delete-form" method="POST" action="" class="mt-6 flex items-center justify-end gap-3">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors">
                    Huỷ bỏ
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-md shadow-rose-600/20 transition-all">
                    Xác nhận Xoá
                </button>
            </form>
        </div>
    </div>

    <!-- UI Scripts -->
    <script>
        // Collapsible Sidebar logic
        const sidebar = document.getElementById('main-sidebar');
        const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
        const collapseIcon = document.getElementById('collapse-icon');
        const mobileToggleBtn = document.getElementById('mobile-toggle-btn');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');

        // Restore collapsed state
        const isCollapsed = localStorage.getItem('op_sidebar_collapsed') === 'true';
        if (isCollapsed) {
            applySidebarState(true);
        }

        function applySidebarState(collapsed) {
            if (collapsed) {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                document.querySelectorAll('.sidebar-text').forEach(el => el.classList.add('hidden'));
                if (collapseIcon) collapseIcon.style.transform = 'rotate(180deg)';
            } else {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
                document.querySelectorAll('.sidebar-text').forEach(el => el.classList.remove('hidden'));
                if (collapseIcon) collapseIcon.style.transform = 'rotate(0deg)';
            }
        }

        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', () => {
                const currentlyCollapsed = sidebar.classList.contains('w-20');
                applySidebarState(!currentlyCollapsed);
                localStorage.setItem('op_sidebar_collapsed', !currentlyCollapsed);
            });
        }

        // Mobile drawer toggle
        if (mobileToggleBtn) {
            mobileToggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebarBackdrop.classList.toggle('hidden');
            });
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarBackdrop.classList.add('hidden');
            });
        }

        // Modal open / close helpers
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('hidden');
                // Only remove overflow-hidden if no other modals are open
                const remainingOpenModals = document.querySelectorAll('.fixed.inset-0.z-50:not(.hidden)');
                if (remainingOpenModals.length === 0) {
                    document.body.classList.remove('overflow-hidden');
                }
            }
        }

        // Delete Confirmation Modal
        function confirmDelete(url, title) {
            const modal = document.getElementById('delete-confirm-modal');
            const form = document.getElementById('delete-form');
            const titleEl = document.getElementById('delete-item-title');

            form.action = url;
            titleEl.textContent = title || 'dòng dữ liệu';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeDeleteModal() {
            closeModal('delete-confirm-modal');
        }

        // Close Modal / Popup on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' || e.key === 'Esc') {
                // 1. Close any open dropdown popups (autocomplete, dropdown menus, etc.)
                const openDropdowns = document.querySelectorAll(
                    '.customer-dropdown-menu:not(.hidden), .partner-dropdown-menu:not(.hidden), [data-dropdown-menu]:not(.hidden)'
                );
                if (openDropdowns.length > 0) {
                    openDropdowns.forEach(dd => dd.classList.add('hidden'));
                    return;
                }

                // 2. Close active modal dialog (including delete confirmation or form modals)
                const openModals = document.querySelectorAll('.fixed.inset-0.z-50:not(.hidden)');
                if (openModals.length > 0) {
                    const activeModal = openModals[openModals.length - 1];
                    closeModal(activeModal.id);
                    return;
                }

                // 3. Close mobile sidebar drawer if open
                if (sidebarBackdrop && !sidebarBackdrop.classList.contains('hidden')) {
                    sidebar?.classList.add('-translate-x-full');
                    sidebarBackdrop.classList.add('hidden');
                }
            }
        });

        // Close modal when clicking on the backdrop area outside the modal content
        document.addEventListener('click', (e) => {
            if (e.target && e.target.classList && e.target.classList.contains('fixed') && e.target.classList.contains('inset-0') && e.target.classList.contains('z-50') && !e.target.classList.contains('hidden')) {
                closeModal(e.target.id);
            }
        });

        /**
         * Real-time duplicate code validation on blur.
         */
        function setupCodeDuplicateCheck(options) {
            const input = typeof options.input === 'string' ? document.querySelector(options.input) : options.input;
            const errorEl = typeof options.errorEl === 'string' ? document.querySelector(options.errorEl) : options.errorEl;
            const submitBtn = typeof options.submitBtn === 'string' ? document.querySelector(options.submitBtn) : options.submitBtn;
            const form = typeof options.form === 'string' ? document.querySelector(options.form) : options.form;
            if (!input || !errorEl) return null;

            let isDuplicate = false;

            async function validateCode() {
                const code = input.value.trim();
                if (!code) {
                    clearError();
                    return false;
                }

                const ignoreId = options.getIgnoreId ? options.getIgnoreId() : '';
                const query = new URLSearchParams({ code });
                if (ignoreId) {
                    query.append('ignore_id', ignoreId);
                }

                try {
                    const res = await fetch(`${options.url}?${query.toString()}`);
                    const data = await res.json();

                    if (data.exists) {
                        showError(data.message || 'Mã này đã tồn tại trên hệ thống, vui lòng chọn mã khác.');
                        return true;
                    } else {
                        clearError();
                        return false;
                    }
                } catch (e) {
                    console.error('Error checking code duplication:', e);
                    return false;
                }
            }

            function showError(msg) {
                isDuplicate = true;
                errorEl.innerHTML = `
                    <span class="inline-flex items-center gap-1 text-rose-500 font-semibold text-xs mt-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>${msg}</span>
                    </span>
                `;
                errorEl.classList.remove('hidden');
                input.classList.add('border-rose-500', 'focus:ring-rose-500');
                input.classList.remove('border-slate-200', 'focus:ring-sky-500');

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }

            function clearError() {
                isDuplicate = false;
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
                input.classList.remove('border-rose-500', 'focus:ring-rose-500');
                input.classList.add('border-slate-200', 'focus:ring-sky-500');

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }

            input.addEventListener('blur', validateCode);
            input.addEventListener('input', () => {
                if (isDuplicate) {
                    clearError();
                }
            });

            if (form) {
                form.addEventListener('submit', (e) => {
                    if (isDuplicate) {
                        e.preventDefault();
                        input.focus();
                    }
                });
            }

            return {
                validate: validateCode,
                clear: clearError,
                isDuplicate: () => isDuplicate,
            };
        }
    </script>
    @stack('scripts')
</body>
</html>
