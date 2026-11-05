@extends('layouts.admin', ['menuKey' => 'groups'])

@section('title', 'Mô tả 6 nhóm Holland')

@section('content')
    <p class="lead">Tên và đoạn giải thích của từng nhóm hiện trên trang kết quả (mã 3 chữ và biểu đồ) và trong email gửi khách. Chữ cái R, I, A, S, E, C là cố định.</p>

    <form method="post" action="{{ route('admin.groups.update') }}" class="form">
        @csrf
        @method('PUT')

        @foreach ($groups as $group)
            @php $letter = $group['letter']; @endphp
            <section class="card">
                <h2 class="card__title">{{ $letter }}</h2>
                <div class="field">
                    <label for="name-{{ $letter }}">Tên nhóm</label>
                    <input id="name-{{ $letter }}" name="name[{{ $letter }}]" value="{{ old('name.'.$letter, $group['name']) }}" maxlength="50" required>
                    @error('name.'.$letter)<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="desc-{{ $letter }}">Giải thích ngắn</label>
                    <textarea id="desc-{{ $letter }}" name="description[{{ $letter }}]" rows="2" maxlength="500">{{ old('description.'.$letter, $group['description']) }}</textarea>
                    <p class="field__hint">Một hai câu, ví dụ: "Thích làm việc thực hành, với máy móc, dụng cụ."</p>
                    @error('description.'.$letter)<p class="field__error">{{ $message }}</p>@enderror
                </div>
            </section>
        @endforeach

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Lưu</button>
        </div>
    </form>

    @include('admin.partials.history', ['activities' => $activities])
@endsection
