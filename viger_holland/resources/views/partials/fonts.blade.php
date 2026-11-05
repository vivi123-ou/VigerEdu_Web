{{-- Font lấy từ Cài đặt (mặc định giống web chính: Work Sans + Playfair Display), chỉ nhận font trong danh sách cho phép. --}}
@php
    $allowedFonts = config('holland.fonts');
    $bodyFont = array_key_exists((string) setting('font_body'), $allowedFonts) ? setting('font_body') : 'Work Sans';
    $headingFont = array_key_exists((string) setting('font_heading'), $allowedFonts) ? setting('font_heading') : 'Playfair Display';
    $families = collect([$bodyFont, $headingFont])->unique()
        ->map(fn ($font) => 'family='.str_replace(' ', '+', $font).':wght@'.$allowedFonts[$font][1])
        ->implode('&');
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ $families }}&display=swap">
<style>
    :root {
        --font-body: "{{ $bodyFont }}", {{ $allowedFonts[$bodyFont][0] }};
        --font-heading: "{{ $headingFont }}", {{ $allowedFonts[$headingFont][0] }};
    }
</style>
