<div class="mb-5 border-b border-sky-100 bg-white/70 backdrop-blur-xs rounded-2xl p-2 border shadow-xs">
    <nav class="flex flex-wrap items-center gap-2" aria-label="Tabs Đơn hàng và Hợp đồng">
        <!-- Tab: Đơn hàng -->
        <a href="{{ route('orders.index') }}"
           class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ ($activeTab ?? '') === 'orders' ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <span>Đơn hàng</span>
            @if(isset($ordersCount) || isset($orders))
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($activeTab ?? '') === 'orders' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700' }}">
                    {{ isset($orders) ? $orders->total() : ($ordersCount ?? 0) }}
                </span>
            @endif
        </a>

        <!-- Tab: Hợp đồng -->
        <a href="{{ route('contracts.index') }}"
           class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-150 {{ ($activeTab ?? '') === 'contracts' ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' }}">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Hợp đồng</span>
            @if(isset($contractsCount) || isset($contracts))
                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($activeTab ?? '') === 'contracts' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700' }}">
                    {{ isset($contracts) ? $contracts->total() : ($contractsCount ?? 0) }}
                </span>
            @endif
        </a>
    </nav>
</div>
