<nav id="sidebarMenu" class="sidebar ams-sidebar d-lg-block collapse" data-simplebar>
    @php
        $company = $shell['company'] ?? config('ams.product_name');
    @endphp
    <div class="sidebar-inner px-3 pt-3 pb-4 d-flex flex-column" style="min-height:100%">
        <a href="{{ route('salesman.dashboard') }}" class="ams-brand mb-3">
            <span class="ams-brand-mark">{{ mb_strtoupper(mb_substr(config('ams.product_name'), 0, 1)) }}</span>
            <span class="ams-brand-name">{{ config('ams.product_name') }}</span>
        </a>
        <div class="ams-org-switch mb-3">{{ $company }}</div>

        <ul class="nav flex-column flex-grow-1">
            <li class="nav-item">
                <a href="{{ route('salesman.dashboard') }}" class="nav-link {{ request()->routeIs('salesman.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('salesman.orders.index') }}" class="nav-link {{ request()->routeIs('salesman.orders.*') ? 'active' : '' }}">
                    <span class="sidebar-text">My orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('salesman.orders.create') }}" class="nav-link {{ request()->routeIs('salesman.orders.create') ? 'active' : '' }}">
                    <span class="sidebar-text">Create order</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('salesman.invoices.index') }}" class="nav-link {{ request()->routeIs('salesman.invoices.*') ? 'active' : '' }}">
                    <span class="sidebar-text">My invoices</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('salesman.commission.index') }}" class="nav-link {{ request()->routeIs('salesman.commission.*') ? 'active' : '' }}">
                    <span class="sidebar-text">Commission</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
