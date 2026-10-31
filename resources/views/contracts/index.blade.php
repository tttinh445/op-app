@extends('layouts.app')

@section('title', 'Quản lý Hợp đồng')
@section('page_title', 'Đơn hàng & Hợp đồng')
@section('page_subtitle', 'Quản lý danh sách hợp đồng mua bán và tình trạng ký kết của doanh nghiệp')

@section('header_actions')
    <!-- Google Sheet Sync Action (Pull) -->
    <form action="{{ route('contracts.sync') }}" method="POST" class="inline-block">
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
    <form action="{{ route('contracts.sync') }}" method="POST" class="inline-block">
        @csrf
        <input type="hidden" name="direction" value="push_marked">
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ ($pendingChanges['total'] ?? 0) > 0 ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-xs' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200' }} transition-all" title="Chỉ đẩy các hợp đồng có thay đổi (thêm/sửa/xoá) lên Google Sheet">
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
    <form action="{{ route('contracts.sync') }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn đẩy toàn bộ danh sách Hợp đồng hiện tại lên ghi đè vào Google Sheet?')">
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
        <span>Thêm Hợp đồng+</span>
    </button>
@endsection

@section('content')
<div class="space-y-4">
    <!-- Tabs Đơn hàng & Hợp đồng -->
    @include('orders.tabs', ['activeTab' => 'contracts'])

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
                        <span>Có {{ $pendingChanges['total'] }} thay đổi hợp đồng chưa đẩy lên Google Sheet:</span>
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
            <form action="{{ route('contracts.sync') }}" method="POST" class="shrink-0 flex items-center gap-2">
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
        <form method="GET" action="{{ route('contracts.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Mã -->
            <div>
                <label for="filter_code" class="block text-xs font-semibold text-slate-600 mb-1">Mã hợp đồng</label>
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

            <!-- Đối tác (Searchable dropdown with server-side search) -->
            <div class="relative">
                <label for="filter_partner" class="block text-xs font-semibold text-slate-600 mb-1">Đối tác</label>
                <div class="relative">
                    <input type="text" id="filter_partner" name="partner" value="{{ $currentPartner }}" placeholder="Mã hoặc tên đối tác..." autocomplete="off"
                           class="w-full pl-8 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 partner-autocomplete-input"
                           data-target-dropdown="#filter_partner_dropdown">
                    <span class="absolute left-2.5 top-2.5 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                </div>
                <div id="filter_partner_dropdown" class="partner-dropdown-menu hidden absolute left-0 right-0 top-full mt-1 bg-white border border-sky-100 rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto"></div>
            </div>

            <!-- Trạng thái -->
            <div>
                <label for="filter_status" class="block text-xs font-semibold text-slate-600 mb-1">Trạng thái</label>
                <select id="filter_status" name="status" class="w-full px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                    <option value="">-- Tất cả trạng thái --</option>
                    @foreach($contractStatuses as $st)
                        <option value="{{ $st }}" {{ $currentStatus === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
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
                    <option value="">-- Tất cả --</option>
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
                <a href="{{ route('contracts.index') }}" class="p-2 text-xs sm:text-sm font-medium text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors" title="Đặt lại bộ lọc">
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
            Tổng số: <strong class="text-slate-900 font-bold text-sm">{{ $contracts->total() }}</strong> hợp đồng
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
                            <a href="{{ route('contracts.index', array_merge(request()->query(), ['sort_by' => 'contract_date', 'sort_dir' => ($currentSortBy === 'contract_date' && $currentSortDir === 'desc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Ngày</span>
                                @if($currentSortBy === 'contract_date')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <!-- Mã -->
                        <th class="py-3 px-3.5 whitespace-nowrap">
                            <a href="{{ route('contracts.index', array_merge(request()->query(), ['sort_by' => 'code', 'sort_dir' => ($currentSortBy === 'code' && $currentSortDir === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Mã</span>
                                @if($currentSortBy === 'code')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <!-- Số HĐ -->
                        <th class="py-3 px-3.5 whitespace-nowrap">Số HĐ</th>
                        <!-- Đối tác -->
                        <th class="py-3 px-3.5 whitespace-nowrap">Đối tác</th>
                        <!-- Số tiền -->
                        <th class="py-3 px-3.5 whitespace-nowrap text-right">
                            <a href="{{ route('contracts.index', array_merge(request()->query(), ['sort_by' => 'amount', 'sort_dir' => ($currentSortBy === 'amount' && $currentSortDir === 'desc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700 justify-end w-full">
                                <span>Số tiền</span>
                                @if($currentSortBy === 'amount')
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
                    @forelse($contracts as $contract)
                        <tr class="transition-colors hover:bg-sky-50/40">
                            <!-- Ngày -->
                            <td class="py-3 px-3.5 text-slate-600 whitespace-nowrap text-xs">
                                {{ $contract->contract_date ? $contract->contract_date->format('d/m/Y H:i') : '—' }}
                            </td>

                            <!-- Mã & Loại -->
                            <td class="py-3 px-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            onclick='openEditModal(@json($contract))'
                                            class="font-mono font-bold text-sky-700 hover:text-sky-900 hover:underline cursor-pointer text-left focus:outline-none"
                                            title="Nhấn để sửa hợp đồng {{ $contract->code }}">
                                        {{ $contract->code }}
                                    </button>
                                    @if($contract->sync_status === 'pending_create')
                                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300" title="Chưa đẩy lên Google Sheet">+Mới</span>
                                    @elseif($contract->sync_status === 'pending_update')
                                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300" title="Đã chỉnh sửa, chưa đẩy lên Google Sheet">✎Sửa</span>
                                    @endif
                                </div>
                                <div class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-medium {{ $contract->type === 'Mua hàng' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ $contract->type }}
                                </div>
                            </td>

                            <!-- Số HĐ -->
                            <td class="py-3 px-3.5 font-mono text-slate-700 text-xs whitespace-nowrap">
                                {{ $contract->contract_number ?: '—' }}
                            </td>

                            <!-- Đối tác -->
                            <td class="py-3 px-3.5">
                                <div class="font-medium text-slate-900">{{ $contract->partner_display_name }}</div>
                                @if($contract->email)
                                    <div class="text-[11px] text-slate-500 font-mono flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ $contract->email }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center gap-2 mt-1">
                                    @if($contract->link)
                                        <a href="{{ $contract->link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[11px] text-sky-600 hover:text-sky-800 hover:underline">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            <span>Link HĐ</span>
                                        </a>
                                    @endif
                                    @if($contract->quote_link)
                                        <a href="{{ $contract->quote_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[11px] text-teal-600 hover:text-teal-800 hover:underline">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>Báo giá</span>
                                        </a>
                                    @endif
                                </div>
                            </td>

                            <!-- Số tiền -->
                            <td class="py-3 px-3.5 text-right font-semibold text-slate-900 whitespace-nowrap">
                                {{ $contract->formatted_amount }}
                            </td>

                            <!-- Trạng Thái -->
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                @php
                                    $statusClasses = match($contract->status) {
                                        'Mới' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'Đã tạo HD online' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'Đã ký Online' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Đã ký tay' => 'bg-teal-50 text-teal-700 border-teal-200',
                                        default => 'bg-slate-50 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusClasses }}">
                                    {{ $contract->status }}
                                </span>
                            </td>

                            <!-- Ghi chú -->
                            <td class="py-3 px-3.5 text-slate-500 text-xs">
                                <span class="line-clamp-2" title="{{ $contract->note }}">{{ $contract->note ?: '—' }}</span>
                            </td>

                            <!-- NV Sale -->
                            <td class="py-3 px-3.5 whitespace-nowrap">
                                <div class="font-medium text-slate-800">{{ $contract->salesperson ?: '—' }}</div>
                                @if($contract->sale_discount !== null)
                                    <div class="text-[11px] text-slate-500">CK: {{ $contract->formatted_sale_discount }}</div>
                                @endif
                            </td>

                            <!-- Thanh toán cho Sale -->
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                @php
                                    $payClasses = match($contract->sale_payment_status) {
                                        'Đã thanh toán' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $payClasses }}">
                                    {{ $contract->sale_payment_status }}
                                </span>
                            </td>

                            <!-- Thao tác: Sửa, Xoá -->
                            <td class="py-3 px-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Sửa -->
                                    <button type="button"
                                            onclick='openEditModal(@json($contract))'
                                            class="p-1.5 text-sky-600 hover:text-sky-800 hover:bg-sky-50 rounded-lg transition-colors"
                                            title="Sửa hợp đồng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Xoá -->
                                    <button type="button"
                                            onclick="confirmDelete('{{ route('contracts.destroy', $contract) }}', 'Hợp đồng {{ $contract->code }}')"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Xoá hợp đồng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Không tìm thấy hợp đồng nào</p>
                                <p class="text-xs text-slate-400 mt-1">Bấm nút "Thêm Hợp đồng+" phía trên để tạo hợp đồng mới</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (30 items/page server side) -->
        @if($contracts->hasPages())
            <div class="p-4 border-t border-sky-100 bg-slate-50/50">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Thêm Hợp Đồng -->
<div id="create-contract-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Thêm hợp đồng mới</h3>
                    <p class="text-xs text-slate-500">Tạo mới hợp đồng mua hoặc bán hàng</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('create-contract-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="create-contract-form" method="POST" action="{{ route('contracts.store') }}" class="mt-4 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã hợp đồng <span class="text-rose-500">*</span></label>
                    <input type="text" id="create_code" name="code" required placeholder="Ví dụ: HD-001, HD-2026..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="create_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng, không được trống</p>
                </div>

                <!-- Số HĐ -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số HĐ</label>
                    <input type="text" id="create_contract_number" name="contract_number" placeholder="Số hiệu văn bản HĐ..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Ngày (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ngày <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" id="create_contract_date" name="contract_date" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="create_contract_date_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng chọn ngày hợp đồng.</div>
                </div>

                <!-- Loại -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Loại</label>
                    <select id="create_type" name="type" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($contractTypes as $type)
                            <option value="{{ $type }}" {{ $type === 'Bán hàng' ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Đối tác (*) (Server-side search + free text) -->
                <div class="sm:col-span-2 relative">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Đối tác <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="create_partner" name="partner" required autocomplete="off"
                               placeholder="Gõ để tìm kiếm đối tác trong database hoặc nhập tên mới..."
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 partner-autocomplete-input"
                               data-target-dropdown="#create_partner_dropdown"
                               data-target-email="#create_email">
                        <div id="create_partner_dropdown" class="partner-dropdown-menu hidden absolute left-0 right-0 top-full mt-1 bg-white border border-sky-100 rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto"></div>
                    </div>
                    <div id="create_partner_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng nhập hoặc chọn đối tác.</div>
                    <p class="text-[11px] text-slate-400 mt-1">Lưu mã khách hàng từ dữ liệu KH, hoặc nhập tên đối tác mới</p>
                </div>

                <!-- Số tiền -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số tiền (VNĐ)</label>
                    <div class="relative">
                        <input type="text" inputmode="numeric" id="create_amount" name="amount" value="0" placeholder="0"
                               class="w-full pl-3 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono text-right font-semibold text-slate-800">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 pointer-events-none">₫</span>
                    </div>
                </div>

                <!-- Trạng Thái -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Trạng Thái</label>
                    <select id="create_status" name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($contractStatuses as $status)
                            <option value="{{ $status }}" {{ $status === 'Mới' ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" id="create_email" name="email" placeholder="email@example.com"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
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

                <!-- Link HĐ -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link hợp đồng</label>
                    <input type="text" id="create_link" name="link" placeholder="https://..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="create_link_error" class="hidden text-xs text-rose-500 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Link hợp đồng phải là định dạng URL hợp lệ (ví dụ: https://example.com).</span>
                    </div>
                </div>

                <!-- Link báo giá -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link báo giá</label>
                    <input type="text" id="create_quote_link" name="quote_link" placeholder="https://..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="create_quote_link_error" class="hidden text-xs text-rose-500 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Link báo giá phải là định dạng URL hợp lệ (ví dụ: https://example.com).</span>
                    </div>
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
                <div>
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
                    <textarea id="create_note" name="note" rows="3" placeholder="Nhập ghi chú cho hợp đồng..."
                              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('create-contract-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="create_contract_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Lưu Hợp đồng
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Hợp Đồng -->
<div id="edit-contract-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sửa hợp đồng</h3>
                    <p class="text-xs text-slate-500">Cập nhật thông tin chi tiết hợp đồng</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-contract-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-contract-form" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã hợp đồng <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_code" name="code" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="edit_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng, không được trống</p>
                </div>

                <!-- Số HĐ -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số HĐ</label>
                    <input type="text" id="edit_contract_number" name="contract_number"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Ngày (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ngày <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" id="edit_contract_date" name="contract_date" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="edit_contract_date_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng chọn ngày hợp đồng.</div>
                </div>

                <!-- Loại -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Loại</label>
                    <select id="edit_type" name="type" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($contractTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Đối tác (*) -->
                <div class="sm:col-span-2 relative">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Đối tác <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="edit_partner" name="partner" required autocomplete="off"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 partner-autocomplete-input"
                               data-target-dropdown="#edit_partner_dropdown"
                               data-target-email="#edit_email">
                        <div id="edit_partner_dropdown" class="partner-dropdown-menu hidden absolute left-0 right-0 top-full mt-1 bg-white border border-sky-100 rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto"></div>
                    </div>
                    <div id="edit_partner_error" class="hidden text-xs text-rose-500 font-medium mt-1">Vui lòng nhập hoặc chọn đối tác.</div>
                </div>

                <!-- Số tiền -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số tiền (VNĐ)</label>
                    <div class="relative">
                        <input type="text" inputmode="numeric" id="edit_amount" name="amount" placeholder="0"
                               class="w-full pl-3 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono text-right font-semibold text-slate-800">
                        <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400 pointer-events-none">₫</span>
                    </div>
                </div>

                <!-- Trạng Thái -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Trạng Thái</label>
                    <select id="edit_status" name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach($contractStatuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" id="edit_email" name="email" placeholder="email@example.com"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
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

                <!-- Link HĐ -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link hợp đồng</label>
                    <input type="text" id="edit_link" name="link" placeholder="https://..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="edit_link_error" class="hidden text-xs text-rose-500 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Link hợp đồng phải là định dạng URL hợp lệ (ví dụ: https://example.com).</span>
                    </div>
                </div>

                <!-- Link báo giá -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link báo giá</label>
                    <input type="text" id="edit_quote_link" name="quote_link" placeholder="https://..."
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <div id="edit_quote_link_error" class="hidden text-xs text-rose-500 font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span>Link báo giá phải là định dạng URL hợp lệ (ví dụ: https://example.com).</span>
                    </div>
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
                <div>
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
                <button type="button" onclick="closeModal('edit-contract-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="edit_contract_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Cập nhật Hợp đồng
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentEditingContractId = null;
    let createContractCodeCheck = null;
    let editContractCodeCheck = null;

    document.addEventListener('DOMContentLoaded', () => {
        // Setup blur duplicate check
        createContractCodeCheck = setupCodeDuplicateCheck({
            input: '#create_code',
            errorEl: '#create_code_error',
            submitBtn: '#create_contract_submit_btn',
            form: '#create-contract-form',
            url: '{{ route('contracts.check_code') }}',
        });

        editContractCodeCheck = setupCodeDuplicateCheck({
            input: '#edit_code',
            errorEl: '#edit_code_error',
            submitBtn: '#edit_contract_submit_btn',
            form: '#edit-contract-form',
            url: '{{ route('contracts.check_code') }}',
            getIgnoreId: () => currentEditingContractId,
        });

        // Initialize partner autocomplete
        initPartnerAutocomplete();

        // Initialize currency inputs & auto discount calculation
        initContractCurrencySync();

        // Initialize form client-side validations
        setupContractFormValidation('create-contract-form', 'create_');
        setupContractFormValidation('edit-contract-form', 'edit_');
    });

    function openCreateModal() {
        currentEditingContractId = null;
        createContractCodeCheck?.clear();
        clearContractFormErrors('create_');

        document.getElementById('create-contract-form').reset();
        // Set default datetime to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('create_contract_date').value = now.toISOString().slice(0, 16);
        document.getElementById('create_type').value = 'Bán hàng';
        document.getElementById('create_status').value = 'Mới';
        document.getElementById('create_sale_payment_status').value = 'Chưa thanh toán';
        document.getElementById('create_amount').value = '0';
        document.getElementById('create_sale_discount').value = '0';

        openModal('create-contract-modal');
    }

    function openEditModal(contract) {
        currentEditingContractId = contract.id;
        editContractCodeCheck?.clear();
        clearContractFormErrors('edit_');

        const form = document.getElementById('edit-contract-form');
        form.action = `/contracts/${contract.id}`;

        document.getElementById('edit_code').value = contract.code || '';
        document.getElementById('edit_contract_number').value = contract.contract_number || '';

        // Format datetime-local
        if (contract.contract_date) {
            const d = new Date(contract.contract_date);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            document.getElementById('edit_contract_date').value = d.toISOString().slice(0, 16);
        } else {
            document.getElementById('edit_contract_date').value = '';
        }

        document.getElementById('edit_partner').value = contract.partner || '';
        document.getElementById('edit_amount').value = formatVnd(contract.amount ?? 0);
        document.getElementById('edit_status').value = contract.status || 'Mới';
        document.getElementById('edit_type').value = contract.type || 'Bán hàng';
        document.getElementById('edit_email').value = contract.email || '';
        document.getElementById('edit_link').value = contract.link || '';
        document.getElementById('edit_quote_link').value = contract.quote_link || '';
        document.getElementById('edit_salesperson').value = contract.salesperson || '';
        document.getElementById('edit_sale_discount').value = contract.sale_discount !== null && contract.sale_discount !== undefined ? formatVnd(contract.sale_discount) : '';
        document.getElementById('edit_sale_payment_status').value = contract.sale_payment_status || 'Chưa thanh toán';
        document.getElementById('edit_note').value = contract.note || '';

        openModal('edit-contract-modal');
    }

    /**
     * Partner Autocomplete with server-side debounce search.
     * Allows selecting an existing partner (saving code & filling email) or entering any custom text.
     */
    function initPartnerAutocomplete() {
        const inputs = document.querySelectorAll('.partner-autocomplete-input');

        inputs.forEach(input => {
            const dropdownSelector = input.dataset.targetDropdown;
            const emailSelector = input.dataset.targetEmail;
            const dropdown = document.querySelector(dropdownSelector);
            const emailInput = emailSelector ? document.querySelector(emailSelector) : null;
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
                                <p class="font-medium text-slate-600">Không tìm thấy đối tác trong DB</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Giá trị nhập "<strong>${escapeHtml(query)}</strong>" sẽ được lưu trực tiếp.</p>
                            </div>
                        `;
                    } else {
                        let html = '<div class="py-1 divide-y divide-slate-50">';
                        customers.forEach(c => {
                            html += `
                                <button type="button" class="w-full text-left px-3 py-2 hover:bg-sky-50 flex items-start justify-between gap-2 transition-colors partner-item-btn"
                                        data-code="${escapeHtml(c.code)}" data-name="${escapeHtml(c.name)}" data-email="${escapeHtml(c.email || '')}">
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
                        dropdown.querySelectorAll('.partner-item-btn').forEach(btn => {
                            btn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                const code = btn.dataset.code;
                                const email = btn.dataset.email;
                                input.value = code;
                                if (emailInput && !emailInput.value && email) {
                                    emailInput.value = email;
                                }
                                dropdown.classList.add('hidden');
                            });
                        });
                    }
                } catch (err) {
                    console.error('Error fetching partners:', err);
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

    function initContractCurrencySync() {
        const createAmount = document.getElementById('create_amount');
        const createDiscount = document.getElementById('create_sale_discount');
        const editAmount = document.getElementById('edit_amount');
        const editDiscount = document.getElementById('edit_sale_discount');

        setupCurrencyInput(createAmount, (amount) => {
            const discount = calculateSaleDiscount(amount);
            createDiscount.value = formatVnd(discount);
        });

        setupCurrencyInput(createDiscount);

        setupCurrencyInput(editAmount, (amount) => {
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
     * Setup full client-side validation for create and edit contract forms
     */
    function setupContractFormValidation(formId, prefix) {
        const form = document.getElementById(formId);
        if (!form) return;

        const checkLink = setupUrlValidation(`${prefix}link`, `${prefix}link_error`);
        const checkQuoteLink = setupUrlValidation(`${prefix}quote_link`, `${prefix}quote_link_error`);

        const dateInput = document.getElementById(`${prefix}contract_date`);
        const dateError = document.getElementById(`${prefix}contract_date_error`);
        dateInput?.addEventListener('input', () => {
            if (dateInput.value.trim()) {
                dateError?.classList.add('hidden');
                dateInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
                dateInput.classList.add('border-slate-200', 'focus:ring-sky-500');
            }
        });

        const partnerInput = document.getElementById(`${prefix}partner`);
        const partnerError = document.getElementById(`${prefix}partner_error`);
        partnerInput?.addEventListener('input', () => {
            if (partnerInput.value.trim()) {
                partnerError?.classList.add('hidden');
                partnerInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
                partnerInput.classList.add('border-slate-200', 'focus:ring-sky-500');
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

            // Validate URLs
            if (checkLink && !checkLink()) {
                isValid = false;
                firstInvalidEl = firstInvalidEl || document.getElementById(`${prefix}link`);
            }

            if (checkQuoteLink && !checkQuoteLink()) {
                isValid = false;
                firstInvalidEl = firstInvalidEl || document.getElementById(`${prefix}quote_link`);
            }

            // Validate Code
            if (codeInput && !codeInput.value.trim()) {
                isValid = false;
                codeInput.classList.add('border-rose-500');
                firstInvalidEl = firstInvalidEl || codeInput;
            }

            // Validate Contract Date
            if (dateInput && !dateInput.value.trim()) {
                isValid = false;
                dateInput.classList.add('border-rose-500', 'focus:ring-rose-500');
                dateInput.classList.remove('border-slate-200', 'focus:ring-sky-500');
                dateError?.classList.remove('hidden');
                firstInvalidEl = firstInvalidEl || dateInput;
            }

            // Validate Partner
            if (partnerInput && !partnerInput.value.trim()) {
                isValid = false;
                partnerInput.classList.add('border-rose-500', 'focus:ring-rose-500');
                partnerInput.classList.remove('border-slate-200', 'focus:ring-sky-500');
                partnerError?.classList.remove('hidden');
                firstInvalidEl = firstInvalidEl || partnerInput;
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
    function clearContractFormErrors(prefix) {
        document.getElementById(`${prefix}link_error`)?.classList.add('hidden');
        document.getElementById(`${prefix}quote_link_error`)?.classList.add('hidden');
        document.getElementById(`${prefix}contract_date_error`)?.classList.add('hidden');
        document.getElementById(`${prefix}partner_error`)?.classList.add('hidden');

        const linkInput = document.getElementById(`${prefix}link`);
        if (linkInput) {
            linkInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
            linkInput.classList.add('border-slate-200', 'focus:ring-sky-500');
        }

        const quoteLinkInput = document.getElementById(`${prefix}quote_link`);
        if (quoteLinkInput) {
            quoteLinkInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
            quoteLinkInput.classList.add('border-slate-200', 'focus:ring-sky-500');
        }

        const dateInput = document.getElementById(`${prefix}contract_date`);
        if (dateInput) {
            dateInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
            dateInput.classList.add('border-slate-200', 'focus:ring-sky-500');
        }

        const partnerInput = document.getElementById(`${prefix}partner`);
        if (partnerInput) {
            partnerInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
            partnerInput.classList.add('border-slate-200', 'focus:ring-sky-500');
        }

        const codeInput = document.getElementById(`${prefix}code`);
        if (codeInput) {
            codeInput.classList.remove('border-rose-500');
        }
    }
</script>
@endpush
