{{--
    Ô nhập sinh từ khai báo Lead::sections().
    @param string $name   @param array $def   @param mixed $value   @param Collection $consultants
--}}
@php
    $val = old($name, $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value);
    $options = ($def['options'] ?? null) === 'consultants' ? $consultants : ($def['options'] ?? []);
@endphp

<label class="field {{ ! empty($def['full']) ? 'field-full' : '' }} @error($name) has-error @enderror">
    <span>
        {{ $def['label'] }} @if (! empty($def['required']))<i>*</i>@endif
        @if (! empty($def['hint']) && $def['type'] !== 'score')<small class="muted">· {{ $def['hint'] }}</small>@endif
    </span>

    @switch($def['type'])
        @case('select')
            <select name="{{ $name }}" @required(! empty($def['required']))>
                @if (empty($def['required']))
                    <option value="">{{ ($def['options'] ?? null) === 'consultants' ? '— Chưa gán —' : '— Chọn —' }}</option>
                @endif
                @foreach ($options as $k => $label)
                    <option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $label }}</option>
                @endforeach
            </select>
            @break

        @case('textarea')
            <textarea name="{{ $name }}" rows="4">{{ $val }}</textarea>
            @break

        @case('score')
            <div class="score-input">
                <input type="number" name="{{ $name }}" value="{{ $val }}" min="0" max="100" step="1" placeholder="0–100">
                <small class="muted">{{ $def['hint'] }}</small>
            </div>
            @break

        @default
            <input
                type="{{ ['gpa' => 'number', 'tel' => 'tel', 'email' => 'email', 'date' => 'date'][$def['type']] ?? 'text' }}"
                name="{{ $name }}" value="{{ $val }}"
                @if ($def['type'] === 'gpa') min="0" max="10" step="0.01" @endif
                @if ($def['type'] === 'date') max="{{ now()->subDay()->format('Y-m-d') }}" @endif
                @required(! empty($def['required']))>
    @endswitch

    @error($name)<small class="error">{{ $message }}</small>@enderror
</label>
