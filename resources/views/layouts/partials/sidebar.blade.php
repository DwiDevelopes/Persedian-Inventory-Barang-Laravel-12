@php
    $role = auth()->user()->role->role ?? '';

    $dashboard = ['label' => 'Dashboard', 'icon' => 'fa-th-large', 'href' => '/', 'match' => ['/', 'dashboard']];

    $dataMaster = [
        ['label' => 'Data Barang', 'icon' => 'fa-cubes', 'children' => [
            ['label' => 'Nama Barang', 'href' => '/barang'],
            ['label' => 'Label Barcode', 'href' => '/barang/label'],
            ['label' => 'Jenis', 'href' => '/jenis-barang'],
            ['label' => 'Satuan', 'href' => '/satuan-barang'],
        ]],
        ['label' => 'Perusahaan', 'icon' => 'fa-building', 'children' => [
            ['label' => 'Supplier', 'href' => '/supplier'],
            ['label' => 'Customer', 'href' => '/customer'],
        ]],
    ];

    $transaksi = [
        ['label' => 'Barang Masuk', 'icon' => 'fa-arrow-down', 'href' => '/barang-masuk'],
        ['label' => 'Barang Keluar', 'icon' => 'fa-arrow-up', 'href' => '/barang-keluar'],
        ['label' => 'Scan Barcode', 'icon' => 'fa-barcode', 'href' => '/scan'],
        ['label' => 'Penyesuaian Stok', 'icon' => 'fa-balance-scale', 'href' => '/penyesuaian-stok'],
    ];

    $laporan = [
        ['label' => 'Stok', 'icon' => 'fa-clipboard-list', 'href' => '/laporan-stok'],
        ['label' => 'Kartu Stok', 'icon' => 'fa-stream', 'href' => '/kartu-stok'],
        ['label' => 'Barang Masuk', 'icon' => 'fa-file-import', 'href' => '/laporan-barang-masuk'],
        ['label' => 'Barang Keluar', 'icon' => 'fa-file-export', 'href' => '/laporan-barang-keluar'],
    ];

    $absenSaya = ['label' => 'Absen Saya', 'icon' => 'fa-fingerprint', 'href' => '/absen-saya'];

    $aktivitas = ['label' => 'Aktivitas User', 'icon' => 'fa-history', 'href' => '/aktivitas-user'];

    $sections = [];
    if ($role === 'superadmin') {
        $sections = [
            'Data Master' => $dataMaster,
            'Transaksi'   => $transaksi,
            'Laporan'     => $laporan,
            'Manajemen User' => [
                ['label' => 'Data Pengguna', 'icon' => 'fa-users', 'href' => '/data-pengguna'],
                ['label' => 'Hak Akses / Role', 'icon' => 'fa-user-lock', 'href' => '/hak-akses'],
                $aktivitas,
            ],
            'Absensi' => [
                ['label' => 'Kelola Absensi', 'icon' => 'fa-user-check', 'href' => '/absensi'],
            ],
        ];
    } elseif ($role === 'admin gudang') {
        $sections = [
            'Data Master' => $dataMaster,
            'Transaksi'   => $transaksi,
            'Laporan'     => $laporan,
            'Absensi'     => [$absenSaya],
        ];
    } elseif ($role === 'kepala gudang') {
        $sections = [
            'Laporan'        => $laporan,
            'Absensi'        => [$absenSaya],
            'Manajemen User' => [$aktivitas],
        ];
    }

    $isActive = function ($href) {
        $h = ltrim($href, '/');
        if ($h === 'barang') { return request()->is('barang') || (request()->is('barang/*') && !request()->is('barang/label')); }
        return request()->is($h, $h . '/*');
    };
@endphp

<ul class="sidebar-menu">
    <li class="{{ request()->is('/', 'dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="/"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
    </li>

    @foreach ($sections as $header => $items)
        <li class="menu-header">{{ $header }}</li>
        @foreach ($items as $item)
            @if (isset($item['children']))
                @php $open = collect($item['children'])->contains(fn ($c) => $isActive($c['href'])); @endphp
                <li class="dropdown {{ $open ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown" aria-expanded="{{ $open ? 'true' : 'false' }}"><i class="fas {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
                    <ul class="dropdown-menu" @if($open) style="display:block" @endif>
                        @foreach ($item['children'] as $child)
                            <li class="{{ $isActive($child['href']) ? 'active' : '' }}"><a class="nav-link" href="{{ $child['href'] }}">{{ $child['label'] }}</a></li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li class="{{ $isActive($item['href']) ? 'active' : '' }}">
                    <a class="nav-link" href="{{ $item['href'] }}"><i class="fas {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
                </li>
            @endif
        @endforeach
    @endforeach
</ul>
