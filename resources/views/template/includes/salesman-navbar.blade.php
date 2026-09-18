<nav id="sidebarMenu" class="sidebar ams-sidebar d-lg-block collapse" data-simplebar>
    @php
        $product = (string) config('ams.product_name');
        $brandParts = preg_split('/\s+/', $product, 2) ?: [$product];
        $brandTop = strtoupper($brandParts[0] ?? $product);
        $brandBottom = strtoupper($brandParts[1] ?? '');
    @endphp
    <div class="sidebar-inner px-3 pt-3 pb-4 d-flex flex-column" style="min-height:100%">
        <a href="{{ route('salesman.dashboard') }}" class="ams-brand ams-brand-stack mb-3">
            <span class="ams-brand-text">{{ $brandTop }}</span>
            @if($brandBottom !== '')
                <span class="ams-brand-sub">{{ $brandBottom }}</span>
            @endif
        </a>

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
