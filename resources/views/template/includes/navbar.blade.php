<nav id="sidebarMenu" class="sidebar ams-sidebar d-lg-block collapse" data-simplebar>
    @php
        $company = $shell['company'] ?? config('ams.product_name');
        $isAccounts = request()->routeIs('customers.*') || request()->routeIs('salesmen.*') || request()->routeIs('suppliers.*') || request()->routeIs('bank-accounts.*');
        $isSales = request()->routeIs('invoices.*') || request()->routeIs('sales-returns.*');
        $isPurchases = request()->routeIs('purchase-orders.*') || request()->routeIs('purchase-returns.*');
        $isAccounting = request()->routeIs('cash-vouchers.*') || request()->routeIs('journal-vouchers.*');
        $isStock = request()->routeIs('stock-categories.*') || request()->routeIs('stock-items.*');
        $isSettings = request()->routeIs('settings.*') || request()->routeIs('users.*');
    @endphp
    <div class="sidebar-inner px-3 pt-3 pb-4 d-flex flex-column" style="min-height:100%">
        <a href="{{ route('dashboard') }}" class="ams-brand mb-3">
            <span class="ams-brand-mark">{{ mb_strtoupper(mb_substr(config('ams.product_name'), 0, 1)) }}</span>
            <span class="ams-brand-name">{{ config('ams.product_name') }}</span>
        </a>
        <div class="ams-org-switch mb-3">{{ $company }}</div>
        <div class="ams-side-search mb-3">
            <input type="search" class="form-control form-control-sm" placeholder="Search…" readonly onclick="window.location='{{ route('reports.index') }}'">
        </div>

        <ul class="nav flex-column flex-grow-1">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#stockMenu" data-bs-toggle="collapse" class="nav-link {{ $isStock ? 'active' : '' }}" aria-expanded="{{ $isStock ? 'true' : 'false' }}">
                    <span class="sidebar-text">{{ __('Inventory') }}</span>
                    <span class="link-arrow ms-auto">▸</span>
                </a>
                <div class="multi-level collapse {{ $isStock ? 'show' : '' }}" id="stockMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('stock-categories.*') ? 'active' : '' }}" href="{{ route('stock-categories.index') }}"><span class="sidebar-text">Categories</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('stock-items.*') ? 'active' : '' }}" href="{{ route('stock-items.index') }}"><span class="sidebar-text">Items</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#accountsMenu" data-bs-toggle="collapse" class="nav-link {{ $isAccounts ? 'active' : '' }}" aria-expanded="{{ $isAccounts ? 'true' : 'false' }}">
                    <span class="sidebar-text">Accounts</span>
                    <span class="link-arrow ms-auto">▸</span>
                </a>
                <div class="multi-level collapse {{ $isAccounts ? 'show' : '' }}" id="accountsMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><span class="sidebar-text">Customers</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('salesmen.*') ? 'active' : '' }}" href="{{ route('salesmen.index') }}"><span class="sidebar-text">Salesmen</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><span class="sidebar-text">Suppliers</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('bank-accounts.*') ? 'active' : '' }}" href="{{ route('bank-accounts.index') }}"><span class="sidebar-text">Bank Accounts</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="{{ route('expense-accounts.index') }}" class="nav-link {{ request()->routeIs('expense-accounts.*') ? 'active' : '' }}">
                    <span class="sidebar-text">Expenses</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <span class="sidebar-text">{{ __('Reports') }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#salesMenu" data-bs-toggle="collapse" class="nav-link {{ $isSales ? 'active' : '' }}" aria-expanded="{{ $isSales ? 'true' : 'false' }}">
                    <span class="sidebar-text">{{ __('Sales') }}</span>
                    <span class="link-arrow ms-auto">▸</span>
                </a>
                <div class="multi-level collapse {{ $isSales ? 'show' : '' }}" id="salesMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}"><span class="sidebar-text">Sales invoices</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}" href="{{ route('sales-returns.index') }}"><span class="sidebar-text">Sales returns</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#purchasesMenu" data-bs-toggle="collapse" class="nav-link {{ $isPurchases ? 'active' : '' }}" aria-expanded="{{ $isPurchases ? 'true' : 'false' }}">
                    <span class="sidebar-text">{{ __('Purchases') }}</span>
                    <span class="link-arrow ms-auto">▸</span>
                </a>
                <div class="multi-level collapse {{ $isPurchases ? 'show' : '' }}" id="purchasesMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}"><span class="sidebar-text">Purchase orders</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}" href="{{ route('purchase-returns.index') }}"><span class="sidebar-text">Purchase returns</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a href="#accountingMenu" data-bs-toggle="collapse" class="nav-link {{ $isAccounting ? 'active' : '' }}" aria-expanded="{{ $isAccounting ? 'true' : 'false' }}">
                    <span class="sidebar-text">{{ __('Accounting') }}</span>
                    <span class="link-arrow ms-auto">▸</span>
                </a>
                <div class="multi-level collapse {{ $isAccounting ? 'show' : '' }}" id="accountingMenu">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('cash-vouchers.*') ? 'active' : '' }}" href="{{ route('cash-vouchers.index') }}"><span class="sidebar-text">Cash vouchers</span></a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('journal-vouchers.*') ? 'active' : '' }}" href="{{ route('journal-vouchers.index') }}"><span class="sidebar-text">Journals</span></a></li>
                    </ul>
                </div>
            </li>
        </ul>

        <ul class="nav flex-column mt-auto">
            <li class="nav-item">
                <a href="{{ route('settings.company') }}" class="nav-link {{ $isSettings ? 'active' : '' }}">
                    <span class="sidebar-text">{{ __('Settings') }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <span class="sidebar-text">{{ __('Users') }}</span>
                </a>
            </li>
            @if(auth()->user()?->isPlatformAdmin())
                <li class="nav-item">
                    <a href="{{ route('platform.home') }}" class="nav-link">
                        <span class="sidebar-text">{{ __('Platform admin') }}</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</nav>
