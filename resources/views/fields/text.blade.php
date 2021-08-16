@php
    use AmphiBee\AkeneoConnector\Admin\Settings;
@endphp

<input
    type="text"
    name="{{ $option_name }}[{{ $id }}]"
    value="{{ Settings::getAkeneoSettings($id) ?: '' }}"
    class="regular-text" />
