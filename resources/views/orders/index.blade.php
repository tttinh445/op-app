@extends('layouts.app')

@section('title', 'Quản lý Đơn hàng')
@section('page_title', 'Đơn hàng & Hợp đồng')
@section('page_subtitle', 'Quản lý danh sách đơn hàng và hợp đồng hoạt động của doanh nghiệp')

@section('header_actions')
    <!-- Google Sheet Sync Action (Pull) -->
    <form action="{{ route('orders.sync') }}" method="POST" class="inline-block">
        @csrf
        <input type="hidden" name="direction" value="pull">
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-all shadow-xs" title="Lấy dữ liệu mới nhất từ Google Sheet về máy">
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span class="hidden sm:inline">Lấy từ Sheet</span>
        </button>
    </form>

    <!-- Google Sheet Push Action (Push Marked Changes) -->
    <form action="{{ route('orders.sync') }}" method="POST" class="inline-block">
        @csrf
        <input type="hidden" name="direction" value="push_marked">
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ ($pendingChanges['total'] ?? 0) > 0 ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-xs' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200' }} transition-all" title="Chỉ đẩy các đơn hàng có thay đổi (thêm/sửa/xoá) lên Google Sheet">
            <svg class="w-4 h-4 {{ ($pendingChanges['total'] ?? 0) > 0 ? 'text-white' : 'text-cyan-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span class="hidden sm:inline">Đẩy thay đổi</span>
            @if(($pendingChanges['total'] ?? 0) > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-white text-amber-700">{{ $pendingChanges['total'] }}</span>
            @endif
        </button>
    </form>

    <!-- Google Sheet Push All Action (Overwrite) -->
    <form action="{{ route('orders.sync') }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn đẩy toàn bộ danh sách Đơn hàng hiện tại lên ghi đè vào Google Sheet?')">
        @csrf
        <input type="hidden" name="direction" value="push_all">
        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 border border-slate-200 transition-all" title="Đẩy toàn bộ dữ liệu hiện tại lên ghi đè Google Sheet">
            <span class="hidden md:inline text-[11px]">Đẩy toàn bộ</span>
        </button>
    </form>

    <!-- Thêm+ Button -->
    <button type="button" onclick="openCreateModal()" class="inline-flex items-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-700 hover:to-sky-600 text-white shadow-md shadow-sky-500/25 transition-all">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Thêm Đơn hàng+</span>
    </button>
@endsection

@section('content')
<div class="space-y-4">
    <!-- Tabs Đơn hàng & Hợp đồng -->
    @include('orders.tabs', ['activeTab' => 'orders'])

    <!-- Pending Changes Alert Banner -->
    @if(($pendingChanges['total'] ?? 0) > 0)
        <div class="p-3.5 sm:p-4 bg-gradient-to-r from-amber-50/90 via-sky-50/80 to-blue-50/90 border border-amber-200 rounded-2xl shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/15 border border-amber-400/30 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs sm:text-sm font-semibold text-slate-800 flex flex-wrap items-center gap-1.5">
                        <span>Có {{ $pendingChanges['total'] }} thay đổi đơn hàng chưa đẩy lên Google Sheet:</span>
                        @if($pendingChanges['creates'] > 0)
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                                +{{ $pendingChanges['creates'] }} mới
                            </span>
                        @endif
                        @if($pendingChanges['updates'] > 0)
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 border border-blue-300">
                                ✎ {{ $pendingChanges['updates'] }} sửa
                            </span>
                        @endif
                        @if($pendingChanges['deletes'] > 0)
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800 border border-rose-300">
                                ✕ {{ $pendingChanges['deletes'] }} đã xoá
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Các dòng có thay đổi được đánh dấu màu bên dưới. Bấm nút bên cạnh để đẩy cập nhật lên Google Sheet.
                    </p>
                </div>
            </div>
            <form action="{{ route('orders.sync') }}" method="POST" class="shrink-0 flex items-center gap-2">
                @csrf
                <input type="hidden" name="direction" value="push_marked">
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white shadow-md shadow-amber-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <span>Đẩy thay đổi lên Sheet ({{ $pendingChanges['total'] }})</span>
                </button>
            </form>
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="p-4 sm:p-5 bg-white rounded-2xl border border-sky-100 shadow-xs">
        <form method="GET" action="{{ route('orders.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Mã -->
            <div>
                <label for="filter_code" class="block text-xs font-semibold text-slate-600 mb-1">Mã đơn hàng</label>
                <div class="relative">
                    <input type="text" id="filter_code" name="code" value="{{ $currentCode }}" placeholder="Nhập mã..."
                           class="w-full pl-8 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <span class="absolute left-2.5 top-2.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </div>
            </div>

            <!-- Khách hàng (Searchable dropdown with server-side search) -->
            <div class="relative">
                <label for="filter_customer" class="block text-xs font-semibold text-slate-600 mb-1">Khách hàng</label>
                <div class="relative">
                    <input type="text" id="filter_customer" name="customer" value="{{ $currentCustomer }}" placeholder="Mã hoặc tên KH..." autocomplete="off"
                           class="w-full pl-8 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 customer-autocomplete-input"
                           data-target-dropdown="#filter_customer_dropdown">
                    <span class="absolute left-2.5 top-2.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                </div>
                <div id="filter_customer_dropdown" class="customer-dropdown-menu hidden absolute left-0 right-0 top-full mt-1 bg-white border border-sky-100 rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto"></div>
            </div>

            <!-- NV Sale (Select from employees) -->
            <div>
                <label for="filter_salesperson" class="block text-xs font-semibold text-slate-600 mb-1">NV Sale</label>
                <select id="filter_salesperson" name="salesperson" class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                    <option value="">-- Tất cả NV Sale --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->name }}" {{ $currentSalesperson === $employee->name ? 'selected' : '' }}>
                            {{ $employee->name }} ({{ $employee->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Thanh toán cho Sale -->
            <div>
                <label for="filter_sale_payment_status" class="block text-xs font-semibold text-slate-600 mb-1">Thanh toán Sale</label>
                <select id="filter_sale_payment_status" name="sale_payment_status" class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                    <option value="">-- Tất cả trạng thái --</option>
                    @foreach($salePaymentStatuses as $st)
                        <option value="{{ $st }}" {{ $currentSalePaymentStatus === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Lọc</span>
                </button>
                <a href="{{ route('orders.index') }}" class="p-2 text-xs sm:text-sm font-medium text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors" title="Đặt lại bộ lọc">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </a>
            </div>
        </form>
    </div>

    <!-- Info Bar -->
    <div class="p-3 bg-white rounded-2xl border border-sky-100 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs text-slate-500">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                <span>Phân trang 30 dòng/trang</span>
            </span>
            <span>Mặc định sắp xếp theo <strong class="text-slate-700">Ngày mới nhất</strong></span>
        </div>
        <div>
            Tổng số: <strong class="text-slate-900 font-bold text-sm">{{ $orders->total() }}</strong> đơn hàng
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-sky-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-sky-50/70 via-sky-50/30 to-slate-50/70 border-b border-sky-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <!-- Ngày -->
                        <th class="py-3 px-3.5 whitespace-nowrap">
                            <a href="{{ route('orders.index', array_merge(request()->query(), ['sort_by' => 'order_date', 'sort_dir' => ($currentSortBy === 'order_date' && $currentSortDir === 'desc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Ngày</span>
                                @if($currentSortBy === 'order_date')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <!-- Mã -->
                        <th class="py-3 px-3.5 whitespace-nowrap">
                            <a href="{{ route('orders.index', array_merge(request()->query(), ['sort_by' => 'code', 'sort_dir' => ($currentSortBy === 'code' && $currentSortDir === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Mã</span>
                                @if($currentSortBy === 'code')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <!-- Khách hàng -->
                        <th class="py-3 px-3.5 whitespace-nowrap">Khách hàng</th>
                        <!-- Tổng tiền -->
                        <th class="py-3 px-3.5 whitespace-nowrap text-right">
                            <a href="{{ route('orders.index', array_merge(request()->query(), ['sort_by' => 'total_amount', 'sort_dir' => ($currentSortBy === 'total_amount' && $currentSortDir === 'desc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700 justify-end w-full">
                                <span>Tổng tiền</span>
                                @if($currentSortBy === 'total_amount')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <!-- Trạng Thái -->
                        <th class="py-3 px-3.5 whitespace-nowrap text-center">Trạng Thái</th>
                        <!-- Ghi chú -->
                        <th class="py-3 px-3.5 min-w-[150px]">Ghi chú</th>
                        <!-- NV Sale -->
                        <th class="py-3 px-3.5 whitespace-nowrap">NV Sale</th>
                        <!-- Thanh toán cho Sale -->
                        <th class="py-3 px-3.5 whitespace-nowrap text-center">Thanh toán Sale</th>
                        <!-- Thao tác -->
                        <th class="py-3 px-3.5 text-center whitespace-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="transition-colors hover:bg-sky-50/40">
                            <!-- Ngày -->
                            <td class="py-3 px-3.5 text-slate-600 whitespace-nowrap text-xs">
                                {{ $order->order_date ? $order->order_date->format('d/m/Y H:i') : '—' }}
                            </td>

                            <!-- Mã & Tên đơn/dự án -->
                            <td class="py-3 px-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            onclick='openEditModal(@json($order))'
                                            class="font-mono font-bold text-sky-700 hover:text-sky-900 hover:underline cursor-pointer text-left focus:outline-none"
                                            title="Nhấn để sửa đơn hàng {{ $order->code }}">
                                        {{ $order->code }}
                                    </button>
                                    @if($order->sync_status === 'pending_create')
                                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300" title="Chưa đẩy lên Google Sheet">+Mới</span>
                                    @elseif($order->sync_status === 'pending_update')
                                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300" title="Đã chỉnh sửa, chưa đẩy lên Google Sheet">✎Sửa</span>
                                    @endif
                                </div>
                                @if($order->name)
                                    <div class="text-[11px] text-slate-500 font-normal truncate max-w-[180px]" title="{{ $order->name }}">
                                        {{ $order->name }}
                                    </div>
                                @endif
                                <div class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-medium {{ $order->type === 'Dự án' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $order->type }}
                                </div>
                            </td>

                            <!-- Khách hàng -->
                            <td class="py-3 px-3.5">
                                <div class="font-medium text-slate-900">{{ $order->customer_display_name }}</div>
                                @if($order->quote_url)
                                    <a href="{{ $order->quote_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[11px] text-sky-600 hover:text-sky-800 hover:underline mt-0.5">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        <span>Xem báo giá</span>
                                    </a>
                                @endif
                            </td>

                            <!-- Tổng tiền -->
                            <td class="py-3 px-3.5 text-right font-semibold text-slate-900 whitespace-nowrap">
                                {{ $order->formatted_total_amount }}
                            </td>

                            <!-- Trạng Thái -->
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                @php
                                    $statusClasses = match($order->status) {
                                        'Mới' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'Đang xử lý' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'Đang giao hàng' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'Hoàn thành' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Đã giao hàng' => 'bg-teal-50 text-teal-700 border-teal-200',
                                        'Đang thi công' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusClasses }}">
                                    {{ $order->status }}
                                </span>
                            </td>

                            <!-- Ghi chú -->
                            <td class="py-3 px-3.5 text-slate-500 text-xs">
                                <span class="line-clamp-2" title="{{ $order->note }}">{{ $order->note ?: '—' }}</span>
                            </td>

                            <!-- NV Sale -->
                            <td class="py-3 px-3.5 whitespace-nowrap">
                                <div class="font-medium text-slate-800">{{ $order->salesperson ?: '—' }}</div>
                                @if($order->sale_discount !== null)
                                    <div class="text-[11px] text-slate-500">CK: {{ $order->formatted_sale_discount }}</div>
                                @endif
                            </td>

                            <!-- Thanh toán cho Sale -->
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                @php
                                    $payClasses = match($order->sale_payment_status) {
                                        'Đã thanh toán' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $payClasses }}">
                                    {{ $order->sale_payment_status }}
                                </span>
                            </td>

                            <!-- Thao tác: Sửa, Xoá -->
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Sửa -->
                                    <button type="button"
                                            onclick='openEditModal(@json($order))'
                                            class="p-1.5 text-sky-600 hover:text-sky-800 hover:bg-sky-50 rounded-lg transition-colors"
                                            title="Sửa đơn hàng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Xoá -->
                                    <button type="button"
                                            onclick="confirmDelete('{{ route('orders.destroy', $order) }}', 'Đơn hàng {{ $order->code }}')"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Xoá đơn hàng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Không tìm thấy đơn hàng nào</p>
                                <p class="text-xs text-slate-400 mt-1">Bấm nút "Thêm Đơn hàng+" phía trên để tạo đơn hàng mới</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (30 items/page server side) -->
        @if($orders->hasPages())
            <div class="p-4 border-t border-sky-100 bg-slate-50/50">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Thêm Đơn Hàng -->
<div id="create-order-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Thêm đơn hàng mới</h3>
                    <p class="text-xs text-slate-500">Tạo mới đơn hàng hoặc dự án trong hệ thống</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('create-order-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="create-order-form" method="POST" action="{{ route('orders.store') }}" class="mt-4 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã đơn hàng <span class="text-rose-500">*</span></label>
                    <input type="text" id="create_code" name="code" required placeholder="Ví dụ: DH-001, PRJ-2026..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="create_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng, không được trống</p>
                </div>

                <!-- Ngày (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ngày <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" id="create_order_date" name="order_date" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="create_order_date_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng chọn ngày đơn hàng.</div>
                </div>

                <!-- Tên đơn/dự án -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên đơn/dự án</label>
                    <input type="text" id="create_name" name="name" placeholder="Nhập tên đơn hàng hoặc dự án (nếu có)..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Khách hàng (*) (Server-side search + free text) -->
                <div class="sm:col-span-2 relative">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Khách hàng <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="create_customer" name="customer" required autocomplete="off"
                               placeholder="Gõ để tìm kiếm KH trong database hoặc nhập tên mới..."
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 customer-autocomplete-input"
                               data-target-dropdown="#create_customer_dropdown">
                        <div id="create_customer_dropdown" class="customer-dropdown-menu hidden absolute left-0 right-0 top-full mt-1 bg-white border border-sky-100 rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto"></div>
                    </div>
                    <div id="create_customer_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng nhập hoặc chọn khách hàng.</div>
                    <p class="text-[11px] text-slate-400 mt-1">Lưu mã khách hàng từ dữ liệu KH, hoặc nhập tên khách hàng mới</p>
                </div>

                <!-- Loại -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Loại</label>
                    <select id="create_type" name="type" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($orderTypes as $type)
                            <option value="{{ $type }}" {{ $type === 'Đơn hàng' ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tổng tiền -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tổng tiền (VNĐ)</label>
                    <div class="relative">
                        <input type="text" inputmode="numeric" id="create_total_amount" name="total_amount" value="0" placeholder="0"
                               class="w-full pl-3 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono text-right font-semibold text-slate-800">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 pointer-events-none">₫</span>
                    </div>
                </div>

                <!-- Trạng Thái -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Trạng Thái</label>
                    <select id="create_status" name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($orderStatuses as $status)
                            <option value="{{ $status }}" {{ $status === 'Mới' ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Link báo giá -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link báo giá</label>
                    <input type="text" id="create_quote_url" name="quote_url" placeholder="https://..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="create_quote_url_error" class="hidden text-xs text-rose-500 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Link báo giá phải là định dạng URL hợp lệ (ví dụ: https://example.com).</span>
                    </div>
                </div>

                <!-- NV Sale -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">NV Sale</label>
                    <select id="create_salesperson" name="salesperson" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="">-- Chọn NV Sale --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->name }}">{{ $employee->name }} ({{ $employee->code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Chiết khấu NV Sale -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700">Chiết khấu NV Sale (VNĐ)</label>
                        <span class="text-[10px] text-sky-600 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-100 font-medium" title="Tự động tính theo công thức Google Sheet">Tự động tính</span>
                    </div>
                    <div class="relative">
                        <input type="text" inputmode="numeric" id="create_sale_discount" name="sale_discount" placeholder="0"
                               class="w-full pl-3 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono text-right text-slate-800">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 pointer-events-none">₫</span>
                    </div>
                </div>

                <!-- Thanh toán cho Sale -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Thanh toán cho Sale</label>
                    <select id="create_sale_payment_status" name="sale_payment_status" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($salePaymentStatuses as $status)
                            <option value="{{ $status }}" {{ $status === 'Chưa thanh toán' ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Ghi chú -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú</label>
                    <textarea id="create_note" name="note" rows="3" placeholder="Nhập ghi chú cho đơn hàng..."
                              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('create-order-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="create_order_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Lưu Đơn hàng
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Đơn Hàng -->
<div id="edit-order-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sửa đơn hàng</h3>
                    <p class="text-xs text-slate-500">Cập nhật thông tin chi tiết đơn hàng</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-order-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-order-form" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã đơn hàng <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_code" name="code" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="edit_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng, không được trống</p>
                </div>

                <!-- Ngày (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ngày <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" id="edit_order_date" name="order_date" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="edit_order_date_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng chọn ngày đơn hàng.</div>
                </div>

                <!-- Tên đơn/dự án -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên đơn/dự án</label>
                    <input type="text" id="edit_name" name="name"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Khách hàng (*) -->
                <div class="sm:col-span-2 relative">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Khách hàng <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="edit_customer" name="customer" required autocomplete="off"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 customer-autocomplete-input"
                               data-target-dropdown="#edit_customer_dropdown">
                        <div id="edit_customer_dropdown" class="customer-dropdown-menu hidden absolute left-0 right-0 top-full mt-1 bg-white border border-sky-100 rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto"></div>
                    </div>
                    <div id="edit_customer_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng nhập hoặc chọn khách hàng.</div>
                </div>

                <!-- Loại -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Loại</label>
                    <select id="edit_type" name="type" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($orderTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tổng tiền -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tổng tiền (VNĐ)</label>
                    <div class="relative">
                        <input type="text" inputmode="numeric" id="edit_total_amount" name="total_amount" placeholder="0"
                               class="w-full pl-3 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono text-right font-semibold text-slate-800">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 pointer-events-none">₫</span>
                    </div>
                </div>

                <!-- Trạng Thái -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Trạng Thái</label>
                    <select id="edit_status" name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($orderStatuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Link báo giá -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link báo giá</label>
                    <input type="text" id="edit_quote_url" name="quote_url" placeholder="https://..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="edit_quote_url_error" class="hidden text-xs text-rose-500 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Link báo giá phải là định dạng URL hợp lệ (ví dụ: https://example.com).</span>
                    </div>
                </div>

                <!-- NV Sale -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">NV Sale</label>
                    <select id="edit_salesperson" name="salesperson" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="">-- Chọn NV Sale --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->name }}">{{ $employee->name }} ({{ $employee->code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Chiết khấu NV Sale -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700">Chiết khấu NV Sale (VNĐ)</label>
                        <span class="text-[10px] text-sky-600 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-100 font-medium" title="Tự động tính theo công thức Google Sheet">Tự động tính</span>
                    </div>
                    <div class="relative">
                        <input type="text" inputmode="numeric" id="edit_sale_discount" name="sale_discount" placeholder="0"
                               class="w-full pl-3 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono text-right text-slate-800">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 pointer-events-none">₫</span>
                    </div>
                </div>

                <!-- Thanh toán cho Sale -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Thanh toán cho Sale</label>
                    <select id="edit_sale_payment_status" name="sale_payment_status" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($salePaymentStatuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Ghi chú -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú</label>
                    <textarea id="edit_note" name="note" rows="3"
                              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('edit-order-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="edit_order_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Cập nhật Đơn hàng
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentEditingOrderId = null;
    let createOrderCodeCheck = null;
    let editOrderCodeCheck = null;

    document.addEventListener('DOMContentLoaded', () => {
        // Setup blur duplicate check
        createOrderCodeCheck = setupCodeDuplicateCheck({
            input: '#create_code',
            errorEl: '#create_code_error',
            submitBtn: '#create_order_submit_btn',
            form: '#create-order-form',
            url: '{{ route('orders.check_code') }}',
        });

        editOrderCodeCheck = setupCodeDuplicateCheck({
            input: '#edit_code',
            errorEl: '#edit_code_error',
            submitBtn: '#edit_order_submit_btn',
            form: '#edit-order-form',
            url: '{{ route('orders.check_code') }}',
            getIgnoreId: () => currentEditingOrderId,
        });

        // Initialize customer autocomplete on inputs
        initCustomerAutocomplete();

        // Initialize currency inputs & auto discount calculation
        initOrderCurrencySync();

        // Initialize form client-side validations
        setupOrderFormValidation('create-order-form', 'create_');
        setupOrderFormValidation('edit-order-form', 'edit_');
    });

    function openCreateModal() {
        currentEditingOrderId = null;
        createOrderCodeCheck?.clear();
        clearOrderFormErrors('create_');

        document.getElementById('create-order-form').reset();
        // Set default datetime to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('create_order_date').value = now.toISOString().slice(0, 16);
        document.getElementById('create_type').value = 'Đơn hàng';
        document.getElementById('create_status').value = 'Mới';
        document.getElementById('create_sale_payment_status').value = 'Chưa thanh toán';
        document.getElementById('create_total_amount').value = '0';
        document.getElementById('create_sale_discount').value = '0';

        openModal('create-order-modal');
    }

    function openEditModal(order) {
        currentEditingOrderId = order.id;
        editOrderCodeCheck?.clear();
        clearOrderFormErrors('edit_');

        const form = document.getElementById('edit-order-form');
        form.action = `/orders/${order.id}`;

        document.getElementById('edit_code').value = order.code || '';

        // Format datetime-local
        if (order.order_date) {
            const d = new Date(order.order_date);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            document.getElementById('edit_order_date').value = d.toISOString().slice(0, 16);
        } else {
            document.getElementById('edit_order_date').value = '';
        }

        document.getElementById('edit_name').value = order.name || '';
        document.getElementById('edit_customer').value = order.customer || '';
        document.getElementById('edit_type').value = order.type || 'Đơn hàng';
        document.getElementById('edit_total_amount').value = formatVnd(order.total_amount ?? 0);
        document.getElementById('edit_status').value = order.status || 'Mới';
        document.getElementById('edit_quote_url').value = order.quote_url || '';
        document.getElementById('edit_salesperson').value = order.salesperson || '';
        document.getElementById('edit_sale_discount').value = order.sale_discount !== null && order.sale_discount !== undefined ? formatVnd(order.sale_discount) : '';
        document.getElementById('edit_sale_payment_status').value = order.sale_payment_status || 'Chưa thanh toán';
        document.getElementById('edit_note').value = order.note || '';

        openModal('edit-order-modal');
    }

    /**
     * Customer Autocomplete with server-side debounce search.
     * Allows selecting an existing customer (saving code) or entering any custom text.
     */
    function initCustomerAutocomplete() {
        const inputs = document.querySelectorAll('.customer-autocomplete-input');

        inputs.forEach(input => {
            const dropdownSelector = input.dataset.targetDropdown;
            const dropdown = document.querySelector(dropdownSelector);
            if (!dropdown) return;

            let debounceTimer = null;

            async function performSearch(query) {
                dropdown.innerHTML = `
                    <div class="p-3 text-xs text-slate-400 text-center flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5 animate-spin text-sky-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Đang tìm kiếm...</span>
                    </div>
                `;
                dropdown.classList.remove('hidden');

                try {
                    const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(query)}`);
                    const json = await res.json();
                    const customers = json.data || [];

                    if (customers.length === 0) {
                        dropdown.innerHTML = `
                            <div class="p-2.5 text-xs text-slate-500">
                                <p class="font-medium text-slate-600">Không tìm thấy khách hàng trong DB</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Giá trị nhập "<strong>${escapeHtml(query)}</strong>" sẽ được lưu trực tiếp.</p>
                            </div>
                        `;
                    } else {
                        let html = '<div class="py-1 divide-y divide-slate-50">';
                        customers.forEach(c => {
                            html += `
                                <button type="button" class="w-full text-left px-3 py-2 hover:bg-sky-50 flex items-start justify-between gap-2 transition-colors customer-item-btn"
                                        data-code="${escapeHtml(c.code)}" data-name="${escapeHtml(c.name)}" data-phone="${escapeHtml(c.phone || '')}">
                                    <div>
                                        <div class="text-xs font-semibold text-slate-800">${escapeHtml(c.name)}</div>
                                        <div class="text-[11px] text-slate-500 font-mono">Mã: ${escapeHtml(c.code)} ${c.phone ? '• ' + escapeHtml(c.phone) : ''}</div>
                                    </div>
                                    <span class="text-[10px] font-bold text-sky-600 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-100">Chọn</span>
                                </button>
                            `;
                        });
                        html += '</div>';
                        dropdown.innerHTML = html;

                        // Add click listeners to items
                        dropdown.querySelectorAll('.customer-item-btn').forEach(btn => {
                            btn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                const code = btn.dataset.code;
                                input.value = code;
                                dropdown.classList.add('hidden');
                            });
                        });
                    }
                } catch (err) {
                    console.error('Error fetching customers:', err);
                    dropdown.classList.add('hidden');
                }
            }

            input.addEventListener('focus', () => {
                const val = input.value.trim();
                performSearch(val);
            });

            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const val = input.value.trim();
                    performSearch(val);
                }, 250);
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Calculate sales commission/discount according to Google Sheet formula:
     * LET(
     *   calc; ROUNDDOWN(amount/55 - 3);
     *   FLOOR(calc; 10000) + IFS(
     *     MOD(calc; 10000) <= 3000; 0;
     *     MOD(calc; 10000) <= 7000; 5000;
     *     TRUE; 10000
     *   )
     * )
     */
    function calculateSaleDiscount(amount) {
        if (!amount || isNaN(amount) || amount <= 0) return 0;
        const calc = Math.floor((amount / 55) - 3);
        if (calc <= 0) return 0;

        const floor10k = Math.floor(calc / 10000) * 10000;
        const mod10k = calc % 10000;

        let bonus = 0;
        if (mod10k <= 3000) {
            bonus = 0;
        } else if (mod10k <= 7000) {
            bonus = 5000;
        } else {
            bonus = 10000;
        }

        return floor10k + bonus;
    }

    /**
     * Format a number or numeric string as Vietnamese currency formatted string (e.g. 1.000.000)
     */
    function formatVnd(val) {
        if (val === null || val === undefined || val === '') return '';
        const clean = String(val).replace(/[^\d]/g, '');
        if (clean === '') return '';
        const num = parseInt(clean, 10);
        if (isNaN(num)) return '';
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    /**
     * Parse formatted currency string into integer
     */
    function parseVnd(val) {
        if (!val) return 0;
        const clean = String(val).replace(/[^\d]/g, '');
        const num = parseInt(clean, 10);
        return isNaN(num) ? 0 : num;
    }

    /**
     * Setup currency inputs with dynamic formatting and auto-discount calculation.
     */
    function setupCurrencyInput(inputEl, onChangeCallback) {
        if (!inputEl) return;

        inputEl.addEventListener('input', function() {
            const rawVal = this.value;
            const num = parseVnd(rawVal);
            if (rawVal.trim() === '') {
                this.value = '';
            } else {
                this.value = formatVnd(num);
            }
            if (onChangeCallback) {
                onChangeCallback(num);
            }
        });
    }

    function initOrderCurrencySync() {
        const createTotal = document.getElementById('create_total_amount');
        const createDiscount = document.getElementById('create_sale_discount');
        const editTotal = document.getElementById('edit_total_amount');
        const editDiscount = document.getElementById('edit_sale_discount');

        setupCurrencyInput(createTotal, (amount) => {
            const discount = calculateSaleDiscount(amount);
            createDiscount.value = formatVnd(discount);
        });

        setupCurrencyInput(createDiscount);

        setupCurrencyInput(editTotal, (amount) => {
            const discount = calculateSaleDiscount(amount);
            editDiscount.value = formatVnd(discount);
        });

        setupCurrencyInput(editDiscount);
    }

    /**
     * Check if a string is a valid HTTP/HTTPS URL
     */
    function isValidUrl(str) {
        if (!str || str.trim() === '') return true;
        let val = str.trim();
        if (!val.match(/^https?:\/\//i)) {
            val = 'https://' + val;
        }
        try {
            const parsed = new URL(val);
            return (parsed.protocol === 'http:' || parsed.protocol === 'https:') &&
                   parsed.hostname.length > 0 &&
                   parsed.hostname.includes('.') &&
                   !parsed.hostname.endsWith('.');
        } catch (_) {
            return false;
        }
    }

    /**
     * Setup real-time URL validation with auto-prefixing https:// and inline error display
     */
    function setupUrlValidation(inputId, errorId) {
        const input = document.getElementById(inputId);
        const errorEl = document.getElementById(errorId);
        if (!input || !errorEl) return null;

        function check() {
            let val = input.value.trim();
            if (val !== '' && !val.match(/^https?:\/\//i) && val.includes('.')) {
                val = 'https://' + val;
                input.value = val;
            }
            if (val !== '' && !isValidUrl(val)) {
                errorEl.classList.remove('hidden');
                input.classList.add('border-rose-500', 'focus:ring-rose-500');
                input.classList.remove('border-slate-200', 'focus:ring-sky-500');
                return false;
            } else {
                errorEl.classList.add('hidden');
                input.classList.remove('border-rose-500', 'focus:ring-rose-500');
                input.classList.add('border-slate-200', 'focus:ring-sky-500');
                return true;
            }
        }

        input.addEventListener('blur', check);
        input.addEventListener('input', () => {
            if (!errorEl.classList.contains('hidden')) {
                check();
            }
        });

        return check;
    }

    /**
     * Setup full client-side validation for create and edit order forms
     */
    function setupOrderFormValidation(formId, prefix) {
        const form = document.getElementById(formId);
        if (!form) return;

        const checkUrl = setupUrlValidation(`${prefix}quote_url`, `${prefix}quote_url_error`);

        const dateInput = document.getElementById(`${prefix}order_date`);
        const dateError = document.getElementById(`${prefix}order_date_error`);
        dateInput?.addEventListener('input', () => {
            if (dateInput.value.trim()) {
                dateError?.classList.add('hidden');
                dateInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
                dateInput.classList.add('border-slate-200', 'focus:ring-sky-500');
            }
        });

        const custInput = document.getElementById(`${prefix}customer`);
        const custError = document.getElementById(`${prefix}customer_error`);
        custInput?.addEventListener('input', () => {
            if (custInput.value.trim()) {
                custError?.classList.add('hidden');
                custInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
                custInput.classList.add('border-slate-200', 'focus:ring-sky-500');
            }
        });

        const codeInput = document.getElementById(`${prefix}code`);
        codeInput?.addEventListener('input', () => {
            if (codeInput.value.trim()) {
                codeInput.classList.remove('border-rose-500');
            }
        });

        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalidEl = null;

            // Validate URL
            if (checkUrl && !checkUrl()) {
                isValid = false;
                firstInvalidEl = firstInvalidEl || document.getElementById(`${prefix}quote_url`);
            }

            // Validate Code
            if (codeInput && !codeInput.value.trim()) {
                isValid = false;
                codeInput.classList.add('border-rose-500');
                firstInvalidEl = firstInvalidEl || codeInput;
            }

            // Validate Order Date
            if (dateInput && !dateInput.value.trim()) {
                isValid = false;
                dateInput.classList.add('border-rose-500', 'focus:ring-rose-500');
                dateInput.classList.remove('border-slate-200', 'focus:ring-sky-500');
                dateError?.classList.remove('hidden');
                firstInvalidEl = firstInvalidEl || dateInput;
            }

            // Validate Customer
            if (custInput && !custInput.value.trim()) {
                isValid = false;
                custInput.classList.add('border-rose-500', 'focus:ring-rose-500');
                custInput.classList.remove('border-slate-200', 'focus:ring-sky-500');
                custError?.classList.remove('hidden');
                firstInvalidEl = firstInvalidEl || custInput;
            }

            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                if (firstInvalidEl) {
                    firstInvalidEl.focus();
                }
            }
        });
    }

    /**
     * Clear validation errors and reset styles when modal is opened
     */
    function clearOrderFormErrors(prefix) {
        document.getElementById(`${prefix}quote_url_error`)?.classList.add('hidden');
        document.getElementById(`${prefix}order_date_error`)?.classList.add('hidden');
        document.getElementById(`${prefix}customer_error`)?.classList.add('hidden');

        const quoteInput = document.getElementById(`${prefix}quote_url`);
        if (quoteInput) {
            quoteInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
            quoteInput.classList.add('border-slate-200', 'focus:ring-sky-500');
        }

        const dateInput = document.getElementById(`${prefix}order_date`);
        if (dateInput) {
            dateInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
            dateInput.classList.add('border-slate-200', 'focus:ring-sky-500');
        }

        const custInput = document.getElementById(`${prefix}customer`);
        if (custInput) {
            custInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
            custInput.classList.add('border-slate-200', 'focus:ring-sky-500');
        }

        const codeInput = document.getElementById(`${prefix}code`);
        if (codeInput) {
            codeInput.classList.remove('border-rose-500');
        }
    }
</script>
@endpush
