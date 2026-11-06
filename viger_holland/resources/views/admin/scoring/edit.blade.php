@extends('layouts.admin', ['menuKey' => 'scoring'])

@section('title', 'Cấu hình chấm điểm')

@php
    $pct = fn (float $value) => rtrim(rtrim(number_format($value * 100, 1, '.', ''), '0'), '.');
    $labels = old('labels', array_map(fn ($row) => ['min' => $pct($row['min']), 'label' => $row['label']], $config->labels));
    $labels = array_values($labels);
    while (count($labels) < 6) {
        $labels[] = ['min' => '', 'label' => ''];
    }
    $trial = session('trial');
@endphp

@section('content')
    <p class="lead">Công thức chấm điểm giữ nguyên; ở đây chỉ đổi các con số. Mỗi ngành có điểm phù hợp (FIT) từ 0 đến 100%, gồm hai phần: <strong>khớp mã</strong> (mã Holland của bạn giống mã ngành tới đâu) và <strong>khớp điểm</strong> (điểm 6 nhóm của bạn hợp với ngành tới đâu). Khách không thấy con số FIT, chỉ thấy tên nhãn.</p>

    <form method="post" action="{{ route('admin.scoring.update') }}" class="form">
        @csrf
        @method('PUT')

        <section class="card">
            <h2 class="card__title">Trọng số</h2>
            <div class="grid-2">
                <div class="field">
                    <label for="weight_code_match">Khớp mã (%)</label>
                    <input id="weight_code_match" name="weight_code_match" value="{{ old('weight_code_match', $pct($config->weightCodeMatch)) }}" inputmode="decimal" class="input--short">
                    <p class="field__hint">Ban đầu: {{ $pct($defaults->weightCodeMatch) }}%.</p>
                    @error('weight_code_match')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="weight_score_match">Khớp điểm (%)</label>
                    <input id="weight_score_match" name="weight_score_match" value="{{ old('weight_score_match', $pct($config->weightScoreMatch)) }}" inputmode="decimal" class="input--short">
                    <p class="field__hint">Ban đầu: {{ $pct($defaults->weightScoreMatch) }}%. Hai ô cộng lại phải bằng 100.</p>
                    @error('weight_score_match')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card">
            <h2 class="card__title">Nhãn phù hợp</h2>
            <p class="muted">Ngành có FIT từ ngưỡng trở lên nhận nhãn đó. Cần một nhãn ngưỡng 0 cho mọi ngành còn lại. Để trống cả hai ô của một dòng để bỏ dòng đó.</p>
            @error('labels')<p class="field__error">{{ $message }}</p>@enderror
            <table class="table table--form">
                <thead><tr><th>Từ (%)</th><th>Tên nhãn khách thấy</th></tr></thead>
                <tbody>
                    @foreach ($labels as $index => $row)
                        <tr>
                            <td><input name="labels[{{ $index }}][min]" value="{{ $row['min'] }}" inputmode="decimal" class="input--short" aria-label="Ngưỡng dòng {{ $index + 1 }}">
                                @error("labels.{$index}.min")<p class="field__error">{{ $message }}</p>@enderror</td>
                            <td><input name="labels[{{ $index }}][label]" value="{{ $row['label'] }}" maxlength="50" aria-label="Tên nhãn dòng {{ $index + 1 }}">
                                @error("labels.{$index}.label")<p class="field__error">{{ $message }}</p>@enderror</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="field__hint">Ban đầu: {{ collect($defaults->labels)->map(fn ($row) => $row['label'].' từ '.$pct($row['min']).'%')->implode(' · ') }}.</p>
        </section>

        <section class="card">
            <h2 class="card__title">Số ngành gợi ý và cảnh báo</h2>
            <div class="grid-2">
                <div class="field">
                    <label for="top_university">Số ngành đại học hiện cho khách</label>
                    <input id="top_university" name="top_university" type="number" min="0" max="20" value="{{ old('top_university', $config->topUniversity) }}" class="input--short">
                    @error('top_university')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="top_vocational">Số nghề (học nghề) hiện cho khách</label>
                    <input id="top_vocational" name="top_vocational" type="number" min="0" max="20" value="{{ old('top_vocational', $config->topVocational) }}" class="input--short">
                    @error('top_vocational')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="di3_threshold">Ngưỡng "hồ sơ phẳng" (%)</label>
                    <input id="di3_threshold" name="di3_threshold" value="{{ old('di3_threshold', $pct($config->di3Threshold)) }}" inputmode="decimal" class="input--short">
                    <p class="field__hint">Nhóm cao nhất hơn nhóm 2 và 4 dưới mức này thì trang kết quả nhắc "sở thích của bạn khá đều". Ban đầu: {{ $pct($defaults->di3Threshold) }}%.</p>
                    @error('di3_threshold')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="reason_subject">Cách xưng hô trong câu lý do</label>
                    <input id="reason_subject" name="reason_subject" value="{{ old('reason_subject', $config->reasonSubject) }}" maxlength="20" class="input--short">
                    <p class="field__hint">Ví dụ: "AIS ↔ IAS · <strong>bạn</strong> đạt I 67%…" (gửi cho CRM và chatbot).</p>
                    @error('reason_subject')<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="card">
            <h2 class="card__title">Chạy thử (không lưu)</h2>
            <p class="muted">Nhập mức hứng thú (%) ở 6 nhóm của một học sinh bất kỳ, bấm "Chạy thử" để so sánh ngành được gợi ý theo cấu hình hiện tại và theo các con số bạn vừa gõ. Không lưu gì.</p>
            <div class="trial-inputs">
                @foreach ($trialPercent as $group => $value)
                    <label class="field">
                        <span>{{ $group }} – {{ setting("group_name_{$group}", $group) }} (%)</span>
                        <input name="p[{{ $group }}]" type="number" min="0" max="100" value="{{ $value }}" class="input--short" data-trial-group="{{ $group }}">
                    </label>
                @endforeach
            </div>
            <button class="btn" type="submit" name="action" value="trial">Chạy thử</button>

            @if ($trial)
                <div class="trial-result">
                    @foreach (['current' => 'Cấu hình đang dùng', 'trial' => 'Với các con số vừa gõ'] as $key => $title)
                        <div class="trial-result__col">
                            <h3>{{ $title }} – mã {{ $trial[$key]['code'] }}</h3>
                            <p class="muted">Đại học</p>
                            <ol>
                                @foreach ($trial[$key]['university'] as $major)
                                    <li>{{ $major['name'] }} – <strong>{{ $major['label'] }}</strong> <span class="muted">(FIT {{ $pct((float) $major['fit']) }}%)</span></li>
                                @endforeach
                            </ol>
                            <p class="muted">Học nghề</p>
                            <ol>
                                @foreach ($trial[$key]['vocational'] as $major)
                                    <li>{{ $major['name'] }} – <strong>{{ $major['label'] }}</strong> <span class="muted">(FIT {{ $pct((float) $major['fit']) }}%)</span></li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="form__actions">
            <button class="btn btn--primary" type="submit" name="action" value="save" data-confirm="Lưu cấu hình mới? Các lượt làm bài từ giờ sẽ được chấm theo cấu hình này.">Lưu cấu hình</button>
        </div>
    </form>

    <form method="post" action="{{ route('admin.scoring.reset') }}" data-confirm="Đặt lại mọi con số như bản chốt ban đầu?" class="form__actions">
        @csrf
        <button class="btn btn--plain" type="submit">Đặt lại như ban đầu</button>
    </form>

    @include('admin.partials.history', ['activities' => $activities])
@endsection
