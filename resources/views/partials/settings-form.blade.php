<form method="post" action="options.php">
    @php
        settings_fields($settings_fields);
        do_settings_sections($settings_sections);
        submit_button();
    @endphp
</form>
