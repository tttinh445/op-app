@extends('layouts.app')

@section('title', 'Quản lý Sản phẩm')
@section('page_title', 'Danh sách Sản phẩm')
@section('page_subtitle', 'Quản lý danh mục sản phẩm, mã, danh mục, ĐVT, nhà cung cấp và hãng sản xuất')

@section('header_actions')
    <!-- Google Sheet Sync Action (Pull) -->
    <form action="{{ route('products.sync') }}" method="POST" class="inline-block">
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
    <form action="{{ route('products.sync') }}" method="POST" class="inline-block">
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
    <form action="{{ route('products.sync') }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn đẩy toàn bộ danh mục Sản phẩm hiện tại lên ghi đè vào Google Sheet?')">
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
            <form action="{{ route('products.sync') }}" method="POST" class="shrink-0 flex items-center gap-2">
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
        <form method="GET" action="{{ route('products.index') }}" class="w-full flex flex-col sm:flex-row flex-wrap items-center gap-2 sm:gap-3">
            <!-- Search input -->
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Tìm mã, tên SP, NCC/NPP, hãng..." class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent bg-slate-50/50">
            </div>

            <!-- Filter NCC/NPP (Server-side Search & Autocomplete) -->
            <div class="relative w-full sm:w-60">
                <input type="hidden" name="supplier_code" id="filter_supplier_code" value="{{ $currentSupplier ?? '' }}">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                    <input type="text"
                           id="filter_supplier_search"
                           value="{{ $currentSupplierDisplay ?? '' }}"
                           placeholder="Chọn NCC/NPP (nhập tên)..."
                           autocomplete="off"
                           class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                    <button type="button"
                            id="filter_supplier_clear"
                            class="{{ empty($currentSupplier) ? 'hidden ' : '' }}absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-rose-600 transition-colors"
                            title="Xoá chọn NCC">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Dropdown kết quả tìm kiếm NCC server-side -->
                <div id="filter_supplier_results" class="hidden absolute z-30 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl shadow-xl border border-sky-100 divide-y divide-slate-100 text-xs"></div>
            </div>

            <!-- Filter ĐVT -->
            <div class="w-full sm:w-36">
                <select name="unit" class="w-full py-2 px-3 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                    <option value="">-- Tất cả ĐVT --</option>
                    @foreach(['Bộ', 'Cái', 'Chiếc', 'Cuộn', 'Thùng', 'Mét', 'Bịch', 'bịch', 'Gói'] as $u)
                        <option value="{{ $u }}" {{ ($currentUnit ?? '') === $u ? 'selected' : '' }}>{{ $u }}</option>
                    @endforeach
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

            @if($currentSearch || !empty($currentSupplier) || !empty($currentUnit) || $currentSortDir !== 'desc')
                <a href="{{ route('products.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                    Đặt lại
                </a>
            @endif
        </form>

        <div class="shrink-0 text-xs text-slate-500">
            Tổng: <strong class="text-slate-900 font-semibold">{{ $products->total() }}</strong> sản phẩm
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-sky-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-sky-50/70 via-sky-50/30 to-slate-50/70 border-b border-sky-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-3 sm:px-4">Mã</th>
                        <th class="py-3 px-3 sm:px-4">Mã danh mục</th>
                        <th class="py-3 px-3 sm:px-4">Tên Sản phẩm</th>
                        <th class="py-3 px-3 sm:px-4">ĐVT</th>
                        <th class="py-3 px-3 sm:px-4">NCC/NPP</th>
                        <th class="py-3 px-3 sm:px-4">Hãng sản xuất</th>
                        <th class="py-3 px-3 sm:px-4">Tên khác</th>
                        <th class="py-3 px-3 sm:px-4">Trạng thái Sheet</th>
                        <th class="py-3 px-3 sm:px-4">
                            <a href="{{ route('products.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort_dir' => $currentSortDir === 'desc' ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
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
                    @forelse($products as $product)
                        <tr class="transition-colors group {{ $product->sync_status === 'pending_create' ? 'bg-amber-50/40 border-l-4 border-amber-400' : ($product->sync_status === 'pending_update' ? 'bg-blue-50/30 border-l-4 border-blue-400' : 'border-l-4 border-transparent hover:bg-sky-50/40') }}">
                            <!-- Mã (Mã SP) -->
                            <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                <button type="button"
                                        onclick='openEditModal(@json($product))'
                                        class="font-mono font-bold text-sky-700 hover:text-sky-900 hover:underline cursor-pointer text-left focus:outline-none"
                                        title="Nhấn để sửa sản phẩm {{ $product->code }}">
                                    {{ $product->code }}
                                </button>
                            </td>

                            <!-- Mã danh mục -->
                            <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                @if($product->category_code || $product->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 font-mono">
                                        {{ $product->category_code ?: $product->category }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Tên Sản phẩm -->
                            <td class="py-3 px-3 sm:px-4 font-semibold text-slate-900 min-w-[220px]">
                                {{ $product->name }}
                            </td>

                            <!-- ĐVT -->
                            <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $product->unit ?: 'Cái' }}
                                </span>
                            </td>

                            <!-- NCC/NPP (Lưu Mã NCC, hiển thị mã + tên) -->
                            <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                @if($product->supplier_code)
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono font-bold text-slate-800 text-xs">{{ $product->supplier_code }}</span>
                                            @if($product->supplier)
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold {{ $product->supplier->type === 'NPP' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ $product->supplier->type }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($product->supplier)
                                            <span class="text-[11px] text-slate-500 truncate max-w-[170px]" title="{{ $product->supplier->name }}">
                                                {{ $product->supplier->name }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Hãng sản xuất -->
                            <td class="py-3 px-3 sm:px-4 text-slate-700 whitespace-nowrap">
                                {{ $product->brand ?: '—' }}
                            </td>

                            <!-- Tên khác -->
                            <td class="py-3 px-3 sm:px-4 text-slate-500 italic max-w-[160px] truncate" title="{{ $product->other_name }}">
                                {{ $product->other_name ?: '—' }}
                            </td>

                            <!-- Trạng thái Sheet -->
                            <td class="py-3 px-3 sm:px-4 whitespace-nowrap">
                                @if($product->sync_status === 'pending_create')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-300" title="Chưa đẩy lên Google Sheet">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Mới
                                    </span>
                                @elseif($product->sync_status === 'pending_update')
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
                                {{ $product->created_at ? $product->created_at->format('d/m/Y H:i') : '—' }}
                            </td>

                            <!-- Thao tác: Sửa & Xoá -->
                            <td class="py-3 px-3 sm:px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Button Sửa -->
                                    <button type="button"
                                            onclick='openEditModal(@json($product))'
                                            class="p-1.5 text-sky-600 hover:text-sky-800 hover:bg-sky-50 rounded-lg transition-colors"
                                            title="Sửa sản phẩm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Button Xoá -->
                                    <button type="button"
                                            onclick="confirmDelete('{{ route('products.destroy', $product) }}', '{{ $product->name }} ({{ $product->code }})')"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Xoá sản phẩm">
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
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p class="text-sm font-semibold text-slate-600">Không tìm thấy sản phẩm nào</p>
                                <p class="text-xs text-slate-400 mt-1">Bấm nút "Thêm+" hoặc "Lấy từ Sheet" để cập nhật dữ liệu</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="p-4 border-t border-sky-100 bg-slate-50/50">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Thêm Mới Sản Phẩm -->
<div id="create-product-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Thêm mới Sản phẩm</h3>
                    <p class="text-xs text-slate-500">Nhập thông tin sản phẩm và phân loại hàng hóa</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('create-product-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="create-product-form" method="POST" action="{{ route('products.store') }}" class="mt-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã sản phẩm <span class="text-rose-500">*</span></label>
                    <input type="text" id="create_code" name="code" required placeholder="VD: SP0001" value="{{ old('code') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="create_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Duy nhất, không được trùng, bắt buộc nhập</p>
                </div>

                <!-- Mã danh mục -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã danh mục</label>
                    <input type="text" name="category_code" placeholder="VD: DM_DIEN, PHU_KIEN..." value="{{ old('category_code') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <!-- Tên Sản phẩm (*) -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên Sản phẩm <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Nhập tên chi tiết sản phẩm..." value="{{ old('name') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- ĐVT (chọn trong danh sách) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ĐVT (Đơn vị tính) <span class="text-rose-500">*</span></label>
                    <select name="unit" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach(['Bộ', 'Cái', 'Chiếc', 'Cuộn', 'Thùng', 'Mét', 'Bịch', 'bịch', 'Gói'] as $u)
                            <option value="{{ $u }}" {{ old('unit', 'Cái') === $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Hãng sản xuất -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hãng sản xuất</label>
                    <input type="text" name="brand" placeholder="VD: Panasonic, Sony, OP..." value="{{ old('brand') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- NCC/NPP (Server-side Search & Autocomplete) -->
                <div class="sm:col-span-2 relative">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">NCC/NPP (Nhà cung cấp)</label>
                    <input type="hidden" name="supplier_code" id="create_supplier_code" value="{{ old('supplier_code') }}">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                               id="create_supplier_search"
                               placeholder="Nhập tên hoặc mã Nhà cung cấp để tìm kiếm..."
                               autocomplete="off"
                               class="w-full pl-9 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <button type="button"
                                id="create_supplier_clear"
                                class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-rose-600 transition-colors"
                                title="Xoá chọn NCC">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Dropdown danh sách kết quả tìm kiếm server-side -->
                    <div id="create_supplier_results" class="hidden absolute z-30 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl shadow-xl border border-sky-100 divide-y divide-slate-100 text-xs"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Tìm kiếm trực tiếp từ máy chủ Nhà cung cấp, lưu Mã NCC vào sản phẩm</p>
                </div>

                <!-- Tên khác -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên khác</label>
                    <input type="text" name="other_name" placeholder="Tên gọi khác, model phụ, tên tiếng Anh..." value="{{ old('other_name') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('create-product-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="create_product_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Lưu Sản phẩm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Sản Phẩm -->
<div id="edit-product-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sửa Sản phẩm</h3>
                    <p class="text-xs text-slate-500">Chỉnh sửa thông số sản phẩm</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-product-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-product-form" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Mã (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã sản phẩm <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_code" name="code" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="edit_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Duy nhất, không được trùng, bắt buộc nhập</p>
                </div>

                <!-- Mã danh mục -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã danh mục</label>
                    <input type="text" id="edit_category_code" name="category_code"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <!-- Tên Sản phẩm (*) -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên Sản phẩm <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_name" name="name" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- ĐVT (chọn trong danh sách) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ĐVT (Đơn vị tính) <span class="text-rose-500">*</span></label>
                    <select id="edit_unit" name="unit" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        @foreach(['Bộ', 'Cái', 'Chiếc', 'Cuộn', 'Thùng', 'Mét', 'Bịch', 'bịch', 'Gói'] as $u)
                            <option value="{{ $u }}">{{ $u }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Hãng sản xuất -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hãng sản xuất</label>
                    <input type="text" id="edit_brand" name="brand"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- NCC/NPP (Server-side Search & Autocomplete) -->
                <div class="sm:col-span-2 relative">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">NCC/NPP (Nhà cung cấp)</label>
                    <input type="hidden" name="supplier_code" id="edit_supplier_code" value="">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text"
                               id="edit_supplier_search"
                               placeholder="Nhập tên hoặc mã Nhà cung cấp để tìm kiếm..."
                               autocomplete="off"
                               class="w-full pl-9 pr-8 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <button type="button"
                                id="edit_supplier_clear"
                                class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-rose-600 transition-colors"
                                title="Xoá chọn NCC">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Dropdown danh sách kết quả tìm kiếm server-side -->
                    <div id="edit_supplier_results" class="hidden absolute z-30 left-0 right-0 mt-1 max-h-56 overflow-y-auto bg-white rounded-xl shadow-xl border border-sky-100 divide-y divide-slate-100 text-xs"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Tìm kiếm trực tiếp từ máy chủ Nhà cung cấp, lưu Mã NCC vào sản phẩm</p>
                </div>

                <!-- Tên khác -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên khác</label>
                    <input type="text" id="edit_other_name" name="other_name"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('edit-product-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="edit_product_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentEditingProductId = null;

    function setupSupplierAutocomplete(inputId, hiddenId, clearBtnId, resultsId) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const clearBtn = document.getElementById(clearBtnId);
        const results = document.getElementById(resultsId);
        if (!input || !hidden || !results) return;

        let debounceTimer = null;

        function refreshClearBtn() {
            if (hidden.value || input.value) {
                clearBtn?.classList.remove('hidden');
            } else {
                clearBtn?.classList.add('hidden');
            }
        }

        async function fetchSuppliers(query) {
            results.innerHTML = `
                <div class="p-3 text-center text-slate-400 flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-sky-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Đang tải danh sách NCC...</span>
                </div>
            `;
            results.classList.remove('hidden');

            try {
                const res = await fetch(`{{ route('suppliers.search') }}?q=${encodeURIComponent(query)}`);
                const json = await res.json();
                const list = json.data || [];

                if (list.length === 0) {
                    results.innerHTML = `
                        <div class="p-3 text-center text-slate-400">
                            Không tìm thấy nhà cung cấp nào
                        </div>
                    `;
                    return;
                }

                results.innerHTML = '';
                list.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'px-3 py-2.5 hover:bg-sky-50 cursor-pointer flex items-center justify-between transition-colors';
                    row.innerHTML = `
                        <div class="flex-1 min-w-0 pr-2">
                            <div class="font-semibold text-slate-800 truncate">${item.name}</div>
                            <div class="text-[11px] text-slate-500 font-mono flex items-center gap-2 mt-0.5">
                                <span>Mã: <strong class="text-sky-700 font-bold">${item.code}</strong></span>
                                ${item.tax_id ? `<span>• MST: ${item.tax_id}</span>` : ''}
                            </div>
                        </div>
                        <span class="shrink-0 px-2 py-0.5 text-[10px] font-semibold rounded-full ${item.type === 'NPP' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-100 text-blue-800 border border-blue-200'}">
                            ${item.type}
                        </span>
                    `;
                    row.onclick = () => {
                        hidden.value = item.code;
                        input.value = `${item.name} (${item.code})`;
                        results.classList.add('hidden');
                        refreshClearBtn();
                    };
                    results.appendChild(row);
                });
            } catch (err) {
                results.innerHTML = `<div class="p-3 text-center text-rose-500">Lỗi nạp dữ liệu NCC</div>`;
            }
        }

        input.addEventListener('focus', () => {
            fetchSuppliers(input.value.trim());
        });

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            hidden.value = '';
            refreshClearBtn();
            debounceTimer = setTimeout(() => {
                fetchSuppliers(input.value.trim());
            }, 250);
        });

        clearBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            input.value = '';
            hidden.value = '';
            refreshClearBtn();
            results.classList.add('hidden');
            input.focus();
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !results.contains(e.target) && !clearBtn?.contains(e.target)) {
                results.classList.add('hidden');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupSupplierAutocomplete('filter_supplier_search', 'filter_supplier_code', 'filter_supplier_clear', 'filter_supplier_results');
        setupSupplierAutocomplete('create_supplier_search', 'create_supplier_code', 'create_supplier_clear', 'create_supplier_results');
        setupSupplierAutocomplete('edit_supplier_search', 'edit_supplier_code', 'edit_supplier_clear', 'edit_supplier_results');

        setupCodeDuplicateCheck({
            input: document.getElementById('create_code'),
            errorEl: document.getElementById('create_code_error'),
            submitBtn: document.getElementById('create_product_submit_btn'),
            form: document.getElementById('create-product-form'),
            url: "{{ route('products.check_code') }}"
        });

        setupCodeDuplicateCheck({
            input: document.getElementById('edit_code'),
            errorEl: document.getElementById('edit_code_error'),
            submitBtn: document.getElementById('edit_product_submit_btn'),
            form: document.getElementById('edit-product-form'),
            url: "{{ route('products.check_code') }}",
            getIgnoreId: () => currentEditingProductId
        });
    });

    function openCreateModal() {
        document.getElementById('create_supplier_code').value = '';
        document.getElementById('create_supplier_search').value = '';
        document.getElementById('create_supplier_clear')?.classList.add('hidden');
        document.getElementById('create_supplier_results')?.classList.add('hidden');
        openModal('create-product-modal');
    }

    function openEditModal(product) {
        currentEditingProductId = product.id;
        const form = document.getElementById('edit-product-form');
        form.action = `/products/${product.id}`;

        document.getElementById('edit_code').value = product.code || '';
        document.getElementById('edit_category_code').value = product.category_code || product.category || '';
        document.getElementById('edit_name').value = product.name || '';
        document.getElementById('edit_unit').value = product.unit || 'Cái';
        document.getElementById('edit_brand').value = product.brand || '';
        document.getElementById('edit_other_name').value = product.other_name || '';

        const supplierCode = product.supplier_code || '';
        document.getElementById('edit_supplier_code').value = supplierCode;

        const searchInput = document.getElementById('edit_supplier_search');
        if (product.supplier && product.supplier.name) {
            searchInput.value = `${product.supplier.name} (${supplierCode})`;
        } else if (supplierCode) {
            searchInput.value = supplierCode;
        } else {
            searchInput.value = '';
        }

        const clearBtn = document.getElementById('edit_supplier_clear');
        if (supplierCode) {
            clearBtn?.classList.remove('hidden');
        } else {
            clearBtn?.classList.add('hidden');
        }
        document.getElementById('edit_supplier_results')?.classList.add('hidden');

        // Reset error message state if previously shown
        const editCodeErr = document.getElementById('edit_code_error');
        if (editCodeErr) {
            editCodeErr.classList.add('hidden');
            editCodeErr.innerHTML = '';
        }
        const editCodeInput = document.getElementById('edit_code');
        if (editCodeInput) {
            editCodeInput.classList.remove('border-rose-500', 'focus:ring-rose-500');
        }
        const editSubmitBtn = document.getElementById('edit_product_submit_btn');
        if (editSubmitBtn) {
            editSubmitBtn.disabled = false;
            editSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        openModal('edit-product-modal');
    }
</script>
@endpush
