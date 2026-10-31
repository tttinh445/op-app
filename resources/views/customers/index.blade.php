@extends('layouts.app')

@section('title', 'Quản lý Khách hàng')
@section('page_title', 'Danh sách Khách hàng')
@section('page_subtitle', 'Quản lý thông tin khách hàng đồng bộ dữ liệu Google Sheets')

@section('header_actions')
    <!-- Google Sheet Sync Action (Pull) -->
    <form action="{{ route('customers.sync') }}" method="POST" class="inline-block">
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
    <form action="{{ route('customers.sync') }}" method="POST" class="inline-block">
        @csrf
        <input type="hidden" name="direction" value="push_marked">
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ ($pendingChanges['total'] ?? 0) > 0 ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-xs' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200' }} transition-all" title="Chỉ đẩy các dữ liệu có thay đổi (thêm/sửa/xoá) lên Google Sheet">
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
    <form action="{{ route('customers.sync') }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn đẩy toàn bộ danh sách Khách hàng hiện tại lên ghi đè vào Google Sheet?')">
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
        <span>Thêm+</span>
    </button>
@endsection

@section('content')
<div class="space-y-4">
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
                        <span>Có {{ $pendingChanges['total'] }} thay đổi chưa đẩy lên Google Sheet:</span>
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
                        Các dòng có thay đổi được đánh dấu màu bên dưới. Bấm nút bên cạnh để đẩy cập nhật lên Sheet (chỉ đẩy dữ liệu có đánh dấu, không tạo dòng trùng).
                        @if($pendingChanges['deletes'] > 0)
                            <span class="text-rose-600 font-medium">(Đã xoá dưới DB: {{ $pendingChanges['pending_deletes']->pluck('code')->take(5)->implode(', ') }}{{ $pendingChanges['pending_deletes']->count() > 5 ? '...' : '' }})</span>
                        @endif
                    </p>
                </div>
            </div>
            <form action="{{ route('customers.sync') }}" method="POST" class="shrink-0 flex items-center gap-2">
                @csrf
                <input type="hidden" name="direction" value="push_marked">
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white shadow-md shadow-amber-500/20 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <span>Đẩy thay đổi lên Google Sheet ({{ $pendingChanges['total'] }})</span>
                </button>
            </form>
        </div>
    @endif

    <!-- Filter and Search Bar -->
    <div class="p-4 bg-white rounded-2xl border border-sky-100 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('customers.index') }}" class="w-full flex flex-col sm:flex-row items-center gap-2 sm:gap-3">
            <!-- Search input -->
            <div class="relative w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Tìm mã, tên, MST, SĐT..." class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-slate-50/50">
            </div>

            <!-- Filter Type -->
            <div class="w-full sm:w-44">
                <select name="type" class="w-full py-2 px-3 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                    <option value="">-- Tất cả loại --</option>
                    <option value="Công ty" {{ $currentType === 'Công ty' ? 'selected' : '' }}>Công ty</option>
                    <option value="Khách vãng lai" {{ $currentType === 'Khách vãng lai' ? 'selected' : '' }}>Khách vãng lai</option>
                </select>
            </div>

            <!-- Sort By Dấu thời gian -->
            <div class="w-full sm:w-48">
                <select name="sort_dir" class="w-full py-2 px-3 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                    <option value="desc" {{ $currentSortDir === 'desc' ? 'selected' : '' }}>Mới nhất (Dấu thời gian ↓)</option>
                    <option value="asc" {{ $currentSortDir === 'asc' ? 'selected' : '' }}>Cũ nhất (Dấu thời gian ↑)</option>
                </select>
            </div>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors">
                Lọc
            </button>

            @if($currentSearch || $currentType || $currentSortDir !== 'desc')
                <a href="{{ route('customers.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                    Đặt lại
                </a>
            @endif
        </form>

        <div class="shrink-0 text-xs text-slate-500">
            Tổng: <strong class="text-slate-900 font-semibold">{{ $customers->total() }}</strong> khách hàng
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-sky-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-sky-50/70 via-sky-50/30 to-slate-50/70 border-b border-sky-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-3 sm:px-4">Mã</th>
                        <th class="py-3 px-3 sm:px-4">Tên khách hàng</th>
                        <th class="py-3 px-3 sm:px-4">Loại</th>
                        <th class="py-3 px-3 sm:px-4">MST</th>
                        <th class="py-3 px-3 sm:px-4">Điện thoại</th>
                        <th class="py-3 px-3 sm:px-4">Email</th>
                        <th class="py-3 px-3 sm:px-4">Số TK</th>
                        <th class="py-3 px-3 sm:px-4">Ngân hàng</th>
                        <th class="py-3 px-3 sm:px-4">Ghi chú</th>
                        <th class="py-3 px-3 sm:px-4">Trạng thái Sheet</th>
                        <th class="py-3 px-3 sm:px-4">
                            <a href="{{ route('customers.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort_dir' => $currentSortDir === 'desc' ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Dấu thời gian</span>
                                @if($currentSortBy === 'created_at')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3 px-3 sm:px-4 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="transition-colors group {{ $customer->sync_status === 'pending_create' ? 'bg-amber-50/40 border-l-4 border-amber-400' : ($customer->sync_status === 'pending_update' ? 'bg-blue-50/30 border-l-4 border-blue-400' : 'border-l-4 border-transparent hover:bg-sky-50/40') }}">
                            <!-- Mã -->
                            <td class="py-3 px-3 sm:px-4 font-mono font-bold text-sky-700 whitespace-nowrap">
                                {{ $customer->code }}
                            </td>

                            <!-- Tên khách hàng -->
                            <td class="py-3 px-3 sm:px-4 font-semibold text-slate-900 min-w-[180px]">
                                {{ $customer->name }}
                            </td>

                            <!-- Loại -->
                            <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                @if($customer->type === 'Công ty')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Công ty
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                        Khách vãng lai
                                    </span>
                                @endif
                            </td>

                            <!-- MST -->
                            <td class="py-3 px-3 sm:px-4 font-mono text-slate-700 whitespace-nowrap font-medium">
                                {{ $customer->tax_id }}
                            </td>

                            <!-- Điện thoại -->
                            <td class="py-3 px-3 sm:px-4 text-slate-600 whitespace-nowrap">
                                {{ $customer->phone ?: '—' }}
                            </td>

                            <!-- Email -->
                            <td class="py-3 px-3 sm:px-4 text-slate-600 whitespace-nowrap">
                                {{ $customer->email ?: '—' }}
                            </td>

                            <!-- Số TK -->
                            <td class="py-3 px-3 sm:px-4 font-mono text-slate-600 whitespace-nowrap">
                                {{ $customer->bank_account ?: '—' }}
                            </td>

                            <!-- Ngân hàng -->
                            <td class="py-3 px-3 sm:px-4 text-slate-600 whitespace-nowrap">
                                {{ $customer->bank_name ?: '—' }}
                            </td>

                            <!-- Ghi chú -->
                            <td class="py-3 px-3 sm:px-4 text-slate-500 max-w-[160px] truncate" title="{{ $customer->note }}">
                                {{ $customer->note ?: '—' }}
                            </td>

                            <!-- Trạng thái Sheet -->
                            <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                @if($customer->sync_status === 'pending_create')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-300" title="Chưa đẩy lên Google Sheet">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Mới
                                    </span>
                                @elseif($customer->sync_status === 'pending_update')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 border border-blue-300" title="Đã chỉnh sửa, chưa đẩy lên Google Sheet">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Đã sửa
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200" title="Đã đồng bộ với Google Sheet">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Đã đồng bộ
                                    </span>
                                @endif
                            </td>

                            <!-- Dấu thời gian -->
                            <td class="py-3 px-3 sm:px-4 text-slate-500 text-xs whitespace-nowrap">
                                {{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '—' }}
                            </td>

                            <!-- Thao tác: Sửa & Xoá -->
                            <td class="py-3 px-3 sm:px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Button Sửa -->
                                    <button type="button"
                                            onclick='openEditModal(@json($customer))'
                                            class="p-1.5 text-sky-600 hover:text-sky-800 hover:bg-sky-50 rounded-lg transition-colors"
                                            title="Sửa khách hàng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Button Xoá -->
                                    <button type="button"
                                            onclick="confirmDelete('{{ route('customers.destroy', $customer) }}', '{{ $customer->name }} ({{ $customer->code }})')"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Xoá khách hàng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-sm font-semibold text-slate-600">Không tìm thấy khách hàng nào</p>
                                <p class="text-xs text-slate-400 mt-1">Bấm nút "Thêm+" hoặc "Đồng bộ Sheet" để cập nhật dữ liệu</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($customers->hasPages())
            <div class="p-4 border-t border-sky-100 bg-slate-50/50">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Thêm Mới Khách Hàng (Popup) -->
<div id="create-customer-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Thêm mới Khách hàng</h3>
                    <p class="text-xs text-slate-500">Nhập đầy đủ các thông tin bắt buộc (*)</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('create-customer-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="create-customer-form" method="POST" action="{{ route('customers.store') }}" class="mt-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã khách hàng (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã khách hàng <span class="text-rose-500">*</span></label>
                    <input type="text" id="create_code" name="code" required placeholder="VD: KH0006" value="{{ old('code') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="create_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng lặp</p>
                </div>

                <!-- Loại select box (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Loại khách hàng <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="Khách vãng lai" {{ old('type') === 'Khách vãng lai' ? 'selected' : '' }}>Khách vãng lai</option>
                        <option value="Công ty" {{ old('type') === 'Công ty' ? 'selected' : '' }}>Công ty</option>
                    </select>
                </div>

                <!-- Tên khách hàng (*) -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên khách hàng <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Nhập họ tên hoặc tên doanh nghiệp..." value="{{ old('name') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- MST (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã số thuế (MST) <span class="text-rose-500">*</span></label>
                    <input type="text" name="tax_id" required placeholder="VD: 0317829401" value="{{ old('tax_id') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">MST không được trùng lặp</p>
                </div>

                <!-- Điện thoại -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Điện thoại</label>
                    <input type="text" name="phone" placeholder="VD: 0903124567" value="{{ old('phone') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" placeholder="VD: ketoan@congty.vn" value="{{ old('email') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <p class="text-[11px] text-slate-400 mt-1">Đúng định dạng email (user@domain.com)</p>
                </div>

                <!-- Số TK -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số tài khoản (Số TK)</label>
                    <input type="text" name="bank_account" placeholder="VD: 1029485729" value="{{ old('bank_account') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <!-- Ngân hàng -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ngân hàng</label>
                    <input type="text" name="bank_name" placeholder="VD: Vietcombank - CN Tân Bình" value="{{ old('bank_name') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Ghi chú -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú</label>
                    <textarea name="note" rows="2" placeholder="Ghi chú thêm về khách hàng..."
                              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">{{ old('note') }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('create-customer-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="create_customer_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Lưu Khách hàng
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Khách Hàng (Popup) -->
<div id="edit-customer-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sửa thông tin Khách hàng</h3>
                    <p class="text-xs text-slate-500">Chỉnh sửa thông tin dòng đã chọn</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-customer-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-customer-form" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã khách hàng (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã khách hàng <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_code" name="code" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="edit_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng lặp</p>
                </div>

                <!-- Loại select box (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Loại khách hàng <span class="text-rose-500">*</span></label>
                    <select id="edit_type" name="type" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="Khách vãng lai">Khách vãng lai</option>
                        <option value="Công ty">Công ty</option>
                    </select>
                </div>

                <!-- Tên khách hàng (*) -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên khách hàng <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_name" name="name" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- MST (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã số thuế (MST) <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_tax_id" name="tax_id" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">MST không được trùng lặp</p>
                </div>

                <!-- Điện thoại -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Điện thoại</label>
                    <input type="text" id="edit_phone" name="phone"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" id="edit_email" name="email"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Số TK -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số tài khoản (Số TK)</label>
                    <input type="text" id="edit_bank_account" name="bank_account"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <!-- Ngân hàng -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ngân hàng</label>
                    <input type="text" id="edit_bank_name" name="bank_name"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Ghi chú -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú</label>
                    <textarea id="edit_note" name="note" rows="2"
                              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('edit-customer-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="edit_customer_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentEditingCustomerId = null;
    let createCustomerCodeCheck = null;
    let editCustomerCodeCheck = null;

    document.addEventListener('DOMContentLoaded', () => {
        createCustomerCodeCheck = setupCodeDuplicateCheck({
            input: '#create_code',
            errorEl: '#create_code_error',
            submitBtn: '#create_customer_submit_btn',
            form: '#create-customer-form',
            url: '{{ route('customers.check_code') }}',
        });

        editCustomerCodeCheck = setupCodeDuplicateCheck({
            input: '#edit_code',
            errorEl: '#edit_code_error',
            submitBtn: '#edit_customer_submit_btn',
            form: '#edit-customer-form',
            url: '{{ route('customers.check_code') }}',
            getIgnoreId: () => currentEditingCustomerId,
        });
    });

    function openCreateModal() {
        createCustomerCodeCheck?.clear();
        openModal('create-customer-modal');
    }

    function openEditModal(customer) {
        currentEditingCustomerId = customer.id;
        editCustomerCodeCheck?.clear();

        const form = document.getElementById('edit-customer-form');
        form.action = `/customers/${customer.id}`;

        document.getElementById('edit_code').value = customer.code || '';
        document.getElementById('edit_name').value = customer.name || '';
        document.getElementById('edit_type').value = customer.type || 'Khách vãng lai';
        document.getElementById('edit_tax_id').value = customer.tax_id || '';
        document.getElementById('edit_phone').value = customer.phone || '';
        document.getElementById('edit_email').value = customer.email || '';
        document.getElementById('edit_bank_account').value = customer.bank_account || '';
        document.getElementById('edit_bank_name').value = customer.bank_name || '';
        document.getElementById('edit_note').value = customer.note || '';

        openModal('edit-customer-modal');
    }
</script>
@endpush
