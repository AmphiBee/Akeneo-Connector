@php
    use AmphiBee\AkeneoConnector\Admin\Settings;
@endphp

<select
        name="{{ $option_name }}[attribute_mapping][{{ $attribute_code }}]"
        id="map_{{ $attribute_code }}"
    >

    <option value="">{!! $default !!}</option>

    @foreach ($options as $group_name => $selections)
        <optgroup label="{{ $group_name }}">
            @foreach ($selections as $value => $option_name)
                <option
                        value="{!! $value !!}"
                        {{ Settings::getMappingValue($attribute_code) === $value ? 'selected' : '' }}
                    >
                {!! $option_name !!}
                </option>
            @endforeach
        </optgroup>
    @endforeach
</select>

