@extends('layouts.app')

@section('title', 'Cấu hình Google Sheets')
@section('page_title', 'Quản lý Liên kết Google Sheets')
@section('page_subtitle', 'Cấu hình kết nối nhiều file Google Sheets và quản lý các trang tính (sheets/tabs)')

@section('header_actions')
    <!-- Thêm liên kết Google Sheet Button -->
    <button type="button" onclick="openCreateSheetModal()" class="inline-flex items-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white shadow-md shadow-emerald-500/25 transition-all">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Thêm Liên Kết Sheet+</span>
    </button>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Architecture explanation & Quick CSV Exports -->
    <div class="p-5 bg-gradient-to-r from-sky-50 via-cyan-50/40 to-white rounded-2xl border border-sky-100 shadow-xs">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800">
                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Mô hình Dữ liệu Google Sheets Đa File & Đa Sheet
                </span>
                <h2 class="text-base font-bold text-slate-900">Liên kết không giới hạn các link Google Sheets</h2>
                <p class="text-xs text-slate-600 max-w-2xl">
                    Hệ thống cho phép kết nối đến nhiều file Google Sheets khác nhau. Trên mỗi file có thể định tuyến từng sheet (tab) cụ thể tương ứng với dữ liệu Khách hàng, Nhà cung cấp hoặc Sản phẩm.
                </p>
            </div>

            <!-- Download CSV Template Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('sheets.export', ['type' => 'customer']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-xs flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV Khách hàng
                </a>
                <a href="{{ route('sheets.export', ['type' => 'supplier']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-xs flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV Nhà cung cấp
                </a>
                <a href="{{ route('sheets.export', ['type' => 'product']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-xs flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV Sản phẩm
                </a>
            </div>
        </div>
    </div>

    <!-- List of Configured Google Sheets -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($configs as $cfg)
            <div class="bg-white rounded-2xl border border-sky-100 shadow-xs hover:shadow-md transition-all p-5 flex flex-col justify-between group">
                <div>
                    <!-- Header of card -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>

                        <!-- Badge for sheet type -->
                        @if($cfg->sheet_type === 'customer')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                Khách hàng ({{ $cfg->customers_count }})
                            </span>
                        @elseif($cfg->sheet_type === 'supplier')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-cyan-50 text-cyan-700 border border-cyan-200">
                                Nhà cung cấp ({{ $cfg->suppliers_count }})
                            </span>
                        @elseif($cfg->sheet_type === 'product')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Sản phẩm ({{ $cfg->products_count }})
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                Tuỳ chỉnh
                            </span>
                        @endif
                    </div>

                    <!-- Title and Sheet Tab -->
                    <div class="mt-4">
                        <h3 class="font-bold text-slate-900 text-base group-hover:text-sky-700 transition-colors">
                            {{ $cfg->name }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 font-medium">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Tab Sheet: <span class="font-bold text-slate-800">{{ $cfg->sheet_name }}</span> (gid: {{ $cfg->gid ?: '0' }})
                        </p>
                    </div>

                    <!-- Technical metadata -->
                    <div class="mt-4 p-3 bg-slate-50 rounded-xl space-y-1.5 text-xs text-slate-600 font-mono">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Sheet ID:</span>
                            <span class="text-slate-700 truncate max-w-[150px]" title="{{ $cfg->spreadsheet_id }}">{{ substr($cfg->spreadsheet_id, 0, 16) }}...</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Chế độ:</span>
                            <span class="font-semibold text-emerald-700 uppercase text-[11px]">{{ $cfg->sync_mode }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Cột khoá chính:</span>
                            <span class="font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded text-[11px] border border-sky-200">Cột {{ $cfg->primary_key_column ?: 'A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Đồng bộ gần nhất:</span>
                            <span class="text-slate-600 text-[11px]">{{ $cfg->last_synced_at ? $cfg->last_synced_at->diffForHumans() : 'Chưa đồng bộ' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Card Actions -->
                <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ $cfg->spreadsheet_url }}" target="_blank" rel="noopener" class="text-xs text-sky-600 hover:text-sky-800 font-medium flex items-center gap-1">
                        Mở Sheet
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>

                    <div class="flex items-center gap-1.5">
                        <!-- Pull button -->
                        <form action="{{ route('sheets.sync', $cfg) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="direction" value="pull">
                            <button type="submit" class="px-2 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors" title="Lấy dữ liệu từ Sheet về Database">
                                Lấy về
                            </button>
                        </form>

                        <!-- Push button -->
                        <form action="{{ route('sheets.sync', $cfg) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn đẩy dữ liệu hiện tại lên Google Sheet?')">
                            @csrf
                            <input type="hidden" name="direction" value="push">
                            <button type="submit" class="px-2 py-1 text-xs font-semibold rounded-lg bg-cyan-50 text-cyan-700 hover:bg-cyan-100 transition-colors" title="Đẩy toàn bộ dữ liệu lên Google Sheet">
                                Đẩy lên
                            </button>
                        </form>

                        <!-- Edit button -->
                        <button type="button" onclick='openEditSheetModal(@json($cfg))' class="p-1 text-slate-400 hover:text-sky-600 rounded-lg transition-colors" title="Sửa cấu hình">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>

                        <!-- Delete button -->
                        <button type="button" onclick="confirmDelete('{{ route('sheets.destroy', $cfg) }}', 'Liên kết {{ $cfg->name }}')" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg transition-colors" title="Xoá cấu hình">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-2xl border border-sky-100">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-semibold text-slate-700">Chưa có liên kết Google Sheet nào</p>
                <p class="text-xs text-slate-400 mt-1">Bấm "Thêm Liên Kết Sheet+" ở góc trên để kết nối file Google Sheets đầu tiên</p>
            </div>
        @endforelse
    </div>

    <!-- Google Apps Script Live Sync Guide -->
    <div class="p-5 bg-white rounded-2xl border border-sky-100 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="p-1 rounded-lg bg-emerald-100 text-emerald-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Mã Google Apps Script v2.1 (Hỗ trợ Xoá theo mã & Chống trùng)</h3>
                    <p class="text-xs text-slate-500">Đồng bộ 2 chiều tức thì: Thêm mới, Sửa theo mã, Xoá theo mã từ dưới lên</p>
                </div>
            </div>
            <button type="button" onclick="copyAppsScript()" class="shrink-0 px-3.5 py-2 text-xs font-semibold rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 transition-colors flex items-center justify-center gap-1.5 shadow-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                <span id="copy-btn-text">Sao chép Code Apps Script v2.1</span>
            </button>
        </div>

        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-1.5">
            <div class="font-bold flex items-center gap-1.5 text-amber-800">
                <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Lưu ý quan trọng khi cập nhật mã trên Google Sheet:</span>
            </div>
            <p class="leading-relaxed">
                Sau khi dán mã vào tệp <strong>Code.gs</strong> và bấm <strong>Lưu (Ctrl+S)</strong>:
            </p>
            <ul class="list-disc list-inside space-y-1 pl-1 text-[11px] text-amber-800">
                <li><strong>Nếu đã có bản triển khai trước đó:</strong> Bấm <strong>Triển khai (Deploy) &gt; Quản lý bản triển khai (Manage deployments)</strong> &gt; Bấm icon <strong>Bút chì ✏️</strong> &gt; Tại mục <strong>Phiên bản (Version)</strong>, BẮT BUỘC chọn <strong>"Phiên bản mới" (New version)</strong> &gt; Bấm <strong>Triển khai</strong>. (Nếu không chọn Phiên bản mới, Google sẽ tiếp tục chạy code cũ).</li>
                <li><strong>Nếu triển khai lần đầu:</strong> Bấm <strong>Triển khai &gt; Bản triển khai mới</strong> &gt; Chọn loại <strong>Ứng dụng web (Web app)</strong> &gt; Ai có quyền truy cập: <strong>Bất kỳ ai (Anyone)</strong> &gt; Triển khai &gt; Sao chép link <code class="bg-amber-100 px-1 rounded font-mono">/exec</code>.</li>
            </ul>
        </div>

        <pre id="apps-script-snippet" class="p-4 bg-slate-900 text-slate-100 text-xs rounded-xl overflow-x-auto font-mono max-h-56 leading-relaxed">{{ $appsScriptCode }}</pre>
    </div>
</div>

<!-- Modal Thêm Liên Kết Google Sheet -->
<div id="create-sheet-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Thêm Liên Kết Google Sheet</h3>
                    <p class="text-xs text-slate-500">Kết nối file Google Sheet mới hoặc thêm 1 sheet/tab</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('create-sheet-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('sheets.store') }}" class="mt-5 space-y-4">
            @csrf

            <div class="space-y-4">
                <!-- Tên nhận diện -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên nhận diện liên kết <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="VD: File Quản Lý Khách Hàng 2026" value="{{ old('name') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Link Google Sheet -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Đường link Google Sheet (URL) <span class="text-rose-500">*</span></label>
                    <input type="text" name="spreadsheet_url" required placeholder="https://docs.google.com/spreadsheets/d/.../edit#gid=0" value="{{ old('spreadsheet_url') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Hệ thống sẽ tự động trích xuất Spreadsheet ID và GID từ link.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Tên Sheet/Tab -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tên Tab/Sheet <span class="text-rose-500">*</span></label>
                        <input type="text" name="sheet_name" required placeholder="VD: Khách hàng" value="{{ old('sheet_name', 'Khách hàng') }}"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>

                    <!-- Loại dữ liệu -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Loại dữ liệu quản lý <span class="text-rose-500">*</span></label>
                        <select name="sheet_type" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                            <option value="customer" {{ old('sheet_type') === 'customer' ? 'selected' : '' }}>Khách hàng</option>
                            <option value="supplier" {{ old('sheet_type') === 'supplier' ? 'selected' : '' }}>Nhà cung cấp / NPP</option>
                            <option value="product" {{ old('sheet_type') === 'product' ? 'selected' : '' }}>Sản phẩm</option>
                            <option value="custom" {{ old('sheet_type') === 'custom' ? 'selected' : '' }}>Tuỳ chỉnh / Khác</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Chế độ kết nối -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Chế độ đồng bộ</label>
                        <select name="sync_mode" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                            <option value="csv_public" {{ old('sync_mode') === 'csv_public' ? 'selected' : '' }}>Công khai (Public CSV Export)</option>
                            <option value="apps_script" {{ old('sync_mode') === 'apps_script' ? 'selected' : '' }}>Google Apps Script Webhook</option>
                            <option value="api_key" {{ old('sync_mode') === 'api_key' ? 'selected' : '' }}>Google Cloud API Key</option>
                        </select>
                    </div>

                    <!-- Range -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Dải ô (Range)</label>
                        <input type="text" name="range" placeholder="A1:Z" value="{{ old('range', 'A1:Z') }}"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    </div>

                    <!-- Cột khoá chính -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Cột khoá chính <span class="text-sky-600 font-normal">(Mã)</span>
                        </label>
                        <input type="text" name="primary_key_column" placeholder="A" value="{{ old('primary_key_column', 'A') }}" maxlength="5"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono uppercase text-center font-bold">
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 -mt-2">
                    💡 Cột khoá chính lưu tên cột chứa mã không trùng (VD: Cột <strong>A</strong> cho Khách hàng). Mặc định là cột <strong>A</strong>.
                </p>

                <!-- Apps Script URL -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Apps Script Web App URL (Nếu có)</label>
                    <input type="url" name="apps_script_url" placeholder="https://script.google.com/macros/s/.../exec" value="{{ old('apps_script_url') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <!-- Ghi chú -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú</label>
                    <textarea name="notes" rows="2" placeholder="Mục đích sử dụng hoặc phân quyền file này..."
                              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('create-sheet-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition-all">
                    Lưu Liên Kết
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Liên Kết Google Sheet -->
<div id="edit-sheet-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sửa Liên Kết Google Sheet</h3>
                    <p class="text-xs text-slate-500">Cập nhật thông số kết nối</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-sheet-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-sheet-form" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <!-- Tên nhận diện -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên nhận diện liên kết <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_sheet_name_title" name="name" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Link Google Sheet -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Đường link Google Sheet (URL) <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_spreadsheet_url" name="spreadsheet_url" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Tên Sheet/Tab -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tên Tab/Sheet <span class="text-rose-500">*</span></label>
                        <input type="text" id="edit_sheet_tab" name="sheet_name" required
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>

                    <!-- Loại dữ liệu -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Loại dữ liệu quản lý <span class="text-rose-500">*</span></label>
                        <select id="edit_sheet_type" name="sheet_type" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                            <option value="customer">Khách hàng</option>
                            <option value="supplier">Nhà cung cấp / NPP</option>
                            <option value="product">Sản phẩm</option>
                            <option value="custom">Tuỳ chỉnh / Khác</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Chế độ kết nối -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Chế độ đồng bộ</label>
                        <select id="edit_sync_mode" name="sync_mode" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                            <option value="csv_public">Công khai (Public CSV Export)</option>
                            <option value="apps_script">Google Apps Script Webhook</option>
                            <option value="api_key">Google Cloud API Key</option>
                        </select>
                    </div>

                    <!-- Range -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Dải ô (Range)</label>
                        <input type="text" id="edit_range" name="range"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    </div>

                    <!-- Cột khoá chính -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Cột khoá chính <span class="text-sky-600 font-normal">(Mã)</span>
                        </label>
                        <input type="text" id="edit_primary_key_column" name="primary_key_column" placeholder="A" maxlength="5"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono uppercase text-center font-bold">
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 -mt-2">
                    💡 Cột khoá chính lưu tên cột chứa mã không trùng (VD: Cột <strong>A</strong> cho Khách hàng). Mặc định là cột <strong>A</strong>.
                </p>

                <!-- Apps Script URL -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Apps Script Web App URL (Nếu có)</label>
                    <input type="url" id="edit_apps_script_url" name="apps_script_url"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                </div>

                <!-- Ghi chú -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ghi chú</label>
                    <textarea id="edit_notes" name="notes" rows="2"
                              class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('edit-sheet-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openCreateSheetModal() {
        openModal('create-sheet-modal');
    }

    function openEditSheetModal(cfg) {
        const form = document.getElementById('edit-sheet-form');
        form.action = `/sheets/${cfg.id}`;

        document.getElementById('edit_sheet_name_title').value = cfg.name || '';
        document.getElementById('edit_spreadsheet_url').value = cfg.spreadsheet_url || '';
        document.getElementById('edit_sheet_tab').value = cfg.sheet_name || '';
        document.getElementById('edit_sheet_type').value = cfg.sheet_type || 'customer';
        document.getElementById('edit_sync_mode').value = cfg.sync_mode || 'csv_public';
        document.getElementById('edit_range').value = cfg.range || 'A1:Z';
        document.getElementById('edit_primary_key_column').value = cfg.primary_key_column || 'A';
        document.getElementById('edit_apps_script_url').value = cfg.apps_script_url || '';
        document.getElementById('edit_notes').value = cfg.notes || '';

        openModal('edit-sheet-modal');
    }

    function copyAppsScript() {
        const text = document.getElementById('apps-script-snippet').innerText;
        navigator.clipboard.writeText(text).then(() => {
            const btnText = document.getElementById('copy-btn-text');
            btnText.innerText = 'Đã sao chép vào bộ nhớ tạm!';
            setTimeout(() => {
                btnText.innerText = 'Sao chép Code Apps Script';
            }, 3000);
        });
    }
</script>
@endpush
