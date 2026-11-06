@extends('layouts.admin', ['menuKey' => 'question-sets'])

@section('title', $question->exists ? 'Sửa câu hỏi' : 'Thêm câu vào bộ "'.$set->name.'"')

@section('content')
    <p><a href="{{ route('admin.question-sets.show', $set) }}">← Bộ "{{ $set->name }}"</a></p>

    <form method="post" action="{{ $question->exists ? route('admin.holland-questions.update', $question) : route('admin.question-sets.questions.store', $set) }}" class="card form" data-type-form>
        @csrf
        @if ($question->exists) @method('PUT') @endif

        @include('admin.holland-questions.fields', ['question' => $question, 'set' => $set, 'uid' => 'page'])

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Lưu</button>
            @unless ($question->exists)
                <button class="btn" type="submit" name="add_another" value="1">Lưu và thêm câu khác</button>
            @endunless
            <a class="btn btn--plain" href="{{ route('admin.question-sets.show', $set) }}">Hủy</a>
        </div>
    </form>

    @if ($question->exists)
        @include('admin.partials.history', ['activities' => $activities])
    @endif
@endsection
