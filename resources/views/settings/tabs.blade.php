<div class="mb-5 border-b border-sky-100 bg-white/70 backdrop-blur-xs rounded-2xl p-2 border shadow-xs">
    <nav class="flex flex-wrap items-center gap-2" aria-label="Tabs Cài đặt">
        <!-- Tab: Nhân viên -->
        <a href="{{ route('settings.employees.index') }}"
           class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ ($activeTab ?? '') === 'employees' ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>Nhân viên</span>
            @if(isset($employeesCount) || isset($employees))
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($activeTab ?? '') === 'employees' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700' }}">
                    {{ isset($employees) ? $employees->total() : ($employeesCount ?? 0) }}
                </span>
            @endif
        </a>

        <!-- Placeholder for future tabs (extensible) -->
    </nav>
</div>
