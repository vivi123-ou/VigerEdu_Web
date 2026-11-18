<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        x-data="ckeditorField({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            initialState: @js($getState()),
            uploadUrl: @js($getUploadUrl()),
            placeholder: @js($getPlaceholder()),
            disabled: @js($isDisabled()),
        })"
        class="vg-ckeditor"
        style="--vg-ck-min-height: {{ $getMinHeight() }}px"
    >
        <div x-ref="editor"></div>
    </div>
</x-dynamic-component>
