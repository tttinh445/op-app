@extends('layouts.app')

@section('title', 'Cài đặt - Quản lý Nhân viên')
@section('page_title', 'Cài đặt hệ thống')
@section('page_subtitle', 'Quản lý thông tin danh mục nhân viên và cấu hình hệ thống')

@section('header_actions')
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
    <!-- Settings Tabs -->
    @include('settings.tabs', ['activeTab' => 'employees'])

    <!-- Top Info Bar (No search as requested, only status and summary) -->
    <div class="p-4 bg-white rounded-2xl border border-sky-100 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                <span>Chỉ quản lý nội bộ (không sync Google Sheet)</span>
            </span>
            <span class="text-xs text-slate-500">
                Sắp xếp mặc định theo <strong class="text-slate-700 font-medium">Ngày tạo (mới nhất)</strong>
            </span>
        </div>

        <div class="shrink-0 text-xs text-slate-500">
            Tổng: <strong class="text-slate-900 font-bold text-sm">{{ $employees->total() }}</strong> nhân viên
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-sky-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-sky-50/70 via-sky-50/30 to-slate-50/70 border-b border-sky-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">
                            <a href="{{ route('settings.employees.index', array_merge(request()->query(), ['sort_by' => 'code', 'sort_dir' => ($currentSortBy === 'code' && $currentSortDir === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Mã NV</span>
                                @if($currentSortBy === 'code')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ route('settings.employees.index', array_merge(request()->query(), ['sort_by' => 'name', 'sort_dir' => ($currentSortBy === 'name' && $currentSortDir === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Tên NV</span>
                                @if($currentSortBy === 'name')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3 px-4">
                            <a href="{{ route('settings.employees.index', array_merge(request()->query(), ['sort_by' => 'created_at', 'sort_dir' => ($currentSortBy === 'created_at' && $currentSortDir === 'desc') ? 'asc' : 'desc'])) }}" class="inline-flex items-center gap-1 hover:text-sky-700">
                                <span>Ngày tạo</span>
                                @if($currentSortBy === 'created_at')
                                    <span>{{ $currentSortDir === 'desc' ? '↓' : '↑' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3 px-4 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($employees as $employee)
                        <tr class="transition-colors hover:bg-sky-50/40">
                            <!-- Mã NV -->
                            <td class="py-3 px-4 font-mono font-bold text-sky-700 whitespace-nowrap">
                                {{ $employee->code }}
                            </td>

                            <!-- Tên NV -->
                            <td class="py-3 px-4 font-semibold text-slate-900">
                                {{ $employee->name }}
                            </td>

                            <!-- Ngày tạo -->
                            <td class="py-3 px-4 text-slate-500 text-xs whitespace-nowrap">
                                {{ $employee->created_at ? $employee->created_at->format('d/m/Y H:i') : '—' }}
                            </td>

                            <!-- Thao tác: Sửa & Xoá -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Button Sửa -->
                                    <button type="button"
                                            onclick='openEditModal(@json($employee))'
                                            class="p-1.5 text-sky-600 hover:text-sky-800 hover:bg-sky-50 rounded-lg transition-colors"
                                            title="Sửa nhân viên">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Button Xoá -->
                                    <button type="button"
                                            onclick="confirmDelete('{{ route('settings.employees.destroy', $employee) }}', '{{ $employee->name }} ({{ $employee->code }})')"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Xoá nhân viên">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Chưa có nhân viên nào</p>
                                <p class="text-xs text-slate-400 mt-1">Bấm nút "Thêm+" phía trên để tạo mới nhân viên đầu tiên</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (30 items/page server side) -->
        @if($employees->hasPages())
            <div class="p-4 border-t border-sky-100 bg-slate-50/50">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Thêm Nhân Viên -->
<div id="create-employee-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Thêm nhân viên mới</h3>
                    <p class="text-xs text-slate-500">Thông tin lưu trữ cục bộ, không đồng bộ Google Sheet</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('create-employee-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="create-employee-form" method="POST" action="{{ route('settings.employees.store') }}" class="mt-5 space-y-4">
            @csrf

            <div class="space-y-4">
                <!-- Mã NV (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã nhân viên <span class="text-rose-500">*</span></label>
                    <input type="text" id="create_code" name="code" required placeholder="Ví dụ: NV001, NV002..." value="{{ old('code') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="create_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng lặp trong hệ thống</p>
                </div>

                <!-- Tên NV (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên nhân viên <span class="text-rose-500">*</span></label>
                    <input type="text" id="create_name" name="name" required placeholder="Nhập họ và tên nhân viên..." value="{{ old('name') }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('create-employee-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="create_employee_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Lưu Nhân viên
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Nhân Viên -->
<div id="edit-employee-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-100 p-6 animate-modal-in max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-sky-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Sửa thông tin nhân viên</h3>
                    <p class="text-xs text-slate-500">Cập nhật mã hoặc tên nhân viên</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('edit-employee-modal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-employee-form" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <!-- Mã NV (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mã nhân viên <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_code" name="code" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono">
                    <div id="edit_code_error" class="hidden"></div>
                    <p class="text-[11px] text-slate-400 mt-1">Mã không được trùng lặp trong hệ thống</p>
                </div>

                <!-- Tên NV (*) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên nhân viên <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_name" name="name" required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('edit-employee-modal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-100 transition-colors">
                    Huỷ
                </button>
                <button type="submit" id="edit_employee_submit_btn" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentEditingEmployeeId = null;
    let createEmployeeCodeCheck = null;
    let editEmployeeCodeCheck = null;

    document.addEventListener('DOMContentLoaded', () => {
        createEmployeeCodeCheck = setupCodeDuplicateCheck({
            input: '#create_code',
            errorEl: '#create_code_error',
            submitBtn: '#create_employee_submit_btn',
            form: '#create-employee-form',
            url: '{{ route('settings.employees.check_code') }}',
        });

        editEmployeeCodeCheck = setupCodeDuplicateCheck({
            input: '#edit_code',
            errorEl: '#edit_code_error',
            submitBtn: '#edit_employee_submit_btn',
            form: '#edit-employee-form',
            url: '{{ route('settings.employees.check_code') }}',
            getIgnoreId: () => currentEditingEmployeeId,
        });
    });

    function openCreateModal() {
        createEmployeeCodeCheck?.clear();
        document.getElementById('create_code').value = '';
        document.getElementById('create_name').value = '';
        openModal('create-employee-modal');
    }

    function openEditModal(employee) {
        currentEditingEmployeeId = employee.id;
        editEmployeeCodeCheck?.clear();

        const form = document.getElementById('edit-employee-form');
        form.action = `/settings/employees/${employee.id}`;

        document.getElementById('edit_code').value = employee.code || '';
        document.getElementById('edit_name').value = employee.name || '';

        openModal('edit-employee-modal');
    }
</script>
@endpush
