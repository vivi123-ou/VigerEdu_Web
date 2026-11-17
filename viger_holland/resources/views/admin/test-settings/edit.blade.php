@extends('layouts.admin', ['menuKey' => 'test-settings'])

@section('title', 'Cài đặt bài test')

@php
    use App\Modules\Assessment\Support\TestConfig;

    /** Giá trị đang hiện trên form: dữ liệu vừa gõ (khi có lỗi) hoặc cài đặt đang dùng. */
    $value = fn (string $key) => old($key, $settings[$key] ?? null);
    $number = fn ($v) => is_float($v) ? rtrim(rtrim(number_format($v, 4, ',', ''), '0'), ',') : $v;
    $tables = [
        'bands' => ['max', 'Điểm trung bình đến (≤)', 'Nhãn hiện cho khách'],
        'c_labels' => ['min', 'Chỉ số C từ (≥)', 'Nhãn hiện cho khách'],
        'academic_labels' => ['min', 'Chỉ số từ (≥, thang 10)', 'Nhãn cho tư vấn viên'],
    ];
@endphp

@section('content')
    <section class="card">
        <h2 class="card__title">Trang này làm gì?</h2>
        <p class="lead">Đây là các con số quyết định cách <strong>rút đề</strong>, cách <strong>đọc mã Holland</strong> và cách <strong>gợi ý ngành</strong>. Mỗi ô có giải thích ngay bên dưới. Bấm <strong>"Lưu"</strong> thì lượt làm bài mới dùng ngay cài đặt mới; lượt đã làm vẫn giữ kết quả cũ. Lưu nhầm thì bấm <strong>"Quay lại bản này"</strong> ở Lịch sử cuối trang.</p>
        <p class="muted">Các ngưỡng hiện là <strong>ngưỡng thử nghiệm</strong> theo tài liệu của Thầy, chưa được kiểm định với học sinh Việt Nam; sẽ hiệu chỉnh sau thí điểm.</p>
    </section>

    @if ($errors->any())
        <div class="alert alert--error" role="alert">Có {{ $errors->count() }} ô chưa đúng, xem chữ đỏ bên dưới. Chưa có gì được lưu.</div>
    @endif

    <form method="post" action="{{ route('admin.test-settings.update') }}" class="form form--wide" novalidate>
        @csrf
        @method('PUT')

        @foreach (TestConfig::FIELDS as $section => $fields)
            <section class="card">
                <h2 class="card__title">{{ $section }}</h2>

                @foreach ($fields as $key => $field)
                    @php [$label, $help, $type] = $field; $unit = $field[3] ?? null; $id = 'f-'.$key; @endphp

                    @if ($type === 'bool')
                        <label class="check" for="{{ $id }}">
                            <input type="checkbox" id="{{ $id }}" name="{{ $key }}" value="1" @checked($value($key))>
                            <span><strong>{{ $label }}</strong></span>
                        </label>
                        <p class="field__hint check-hint">{{ $help }}</p>
                        @continue
                    @endif

                    <div class="field">
                        @if (in_array($type, ['int', 'decimal'], true))
                            <label for="{{ $id }}">{{ $label }}</label>
                            <span>
                                <input id="{{ $id }}" name="{{ $key }}" value="{{ $number($value($key)) }}" class="input--short"
                                       inputmode="{{ $type === 'int' ? 'numeric' : 'decimal' }}" @error($key) aria-invalid="true" @enderror>
                                @if ($unit) <span class="muted">{{ $unit }}</span> @endif
                            </span>
                        @elseif ($type === 'labels5')
                            <p class="field__label-text">{{ $label }}</p>
                            <div class="grid-2">
                                @foreach (range(0, 4) as $i)
                                    <label class="field">
                                        <span class="muted">{{ $i + 1 }} sao</span>
                                        <input name="{{ $key }}[{{ $i }}]" value="{{ old($key.'.'.$i, $settings[$key][$i] ?? '') }}" maxlength="60" @error($key.'.'.$i) aria-invalid="true" @enderror>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            @php [$column, $columnLabel, $textLabel] = $tables[$type]; $rows = array_values((array) $value($key)); $rows[] = [$column => '', 'label' => '']; @endphp
                            <p class="field__label-text">{{ $label }}</p>
                            <table class="table table--form">
                                <thead><tr><th>{{ $columnLabel }}</th><th>{{ $textLabel }}</th></tr></thead>
                                <tbody>
                                    @foreach ($rows as $i => $row)
                                        <tr>
                                            <td><input name="{{ $key }}[{{ $i }}][{{ $column }}]" value="{{ $number($row[$column] ?? '') }}" class="input--short" inputmode="decimal" aria-label="{{ $columnLabel }}"></td>
                                            <td><input name="{{ $key }}[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" maxlength="60" aria-label="{{ $textLabel }}"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="field__hint">Dòng trống cuối bảng để thêm mốc mới. Muốn bỏ một mốc thì xóa trống cả hai ô của dòng đó.</p>
                        @endif

                        <p class="field__hint">{{ $help }}</p>
                        @foreach ($errors->get($key) + $errors->get($key.'.*') as $messages)
                            @foreach ((array) $messages as $message)
                                <p class="field__error">{{ $message }}</p>
                            @endforeach
                        @endforeach
                    </div>
                @endforeach
            </section>
        @endforeach

        <div class="form__actions form__actions--sticky">
            <button class="btn btn--primary" type="submit">Lưu</button>
        </div>
    </form>

    <section class="card">
        <h2 class="card__title">Lịch sử các phiên bản cài đặt</h2>
        @if ($versions->isEmpty())
            <p class="muted">Chưa có phiên bản nào.</p>
        @else
            <ul class="history">
                @foreach ($versions as $version)
                    <li class="history__item">
                        <div>
                            <strong>Phiên bản {{ $version->id }}</strong>@if ($version->id === $currentId) <span class="badge">Đang dùng</span>@endif · {{ $version->note }}
                            <p class="muted history__meta">{{ $version->created_at->format('H:i d/m/Y') }} · {{ $version->user?->name ?? 'Hệ thống' }}</p>
                        </div>
                        @if ($version->id !== $currentId)
                            <form method="post" action="{{ route('admin.test-settings.restore', $version) }}" data-confirm="Quay lại cài đặt của phiên bản {{ $version->id }}?">
                                @csrf
                                <button class="btn btn--small" type="submit">Quay lại bản này</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
