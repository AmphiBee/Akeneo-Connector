<div class="wrap">
    <h2>Configuration Akeneo Connector</h2>
    <p></p>

    @if (!empty($errors))
        @foreach ($errors as $error)
            <div class="notice notice-error"><p>{{ $error }}</p></div>
        @endforeach
    @endif

    @php
        settings_errors();

        $active_tab = $_GET['tab'] ?? 'synchronization';

        $tabs = [
            'synchronization' => __('Synchronization', 'akeneo-connector'),
            'credentials'     => __('Credentials', 'akeneo-connector'),
        ];
    @endphp

    {{-- Menu : Tabs   --}}
    <h2 class="nav-tab-wrapper">
        @foreach ($tabs as $tab => $name)
            <a href="{{ $base_url }}&tab={{ $tab }}" class="nav-tab {{ $active_tab === $tab ? 'nav-tab-active' : '' }}">
                {{ $name }}
            </a>
        @endforeach
    </h2>

    {{-- Include the current tab --}}
    @includeIf("tabs.$active_tab")
</div>
