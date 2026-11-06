@extends('layouts.admin', ['menuKey' => 'info-form'])

@section('title', $question->exists ? 'Sửa câu hỏi' : 'Thêm câu vào form thông tin')

@section('content')
    <p><a href="{{ route('admin.info-questions.index') }}">← Form thông tin khách</a></p>

    <form method="post" action="{{ $question->exists ? route('admin.info-questions.update', $question) : route('admin.info-questions.store') }}" class="card form" data-type-form>
        @csrf
        @if ($question->exists) @method('PUT') @endif

        @include('admin.info-questions.fields', ['question' => $question, 'conditionQuestions' => $conditionQuestions, 'uid' => 'page'])

        <div class="form__actions">
            <button class="btn btn--primary" type="submit">Lưu</button>
            <a class="btn btn--plain" href="{{ route('admin.info-questions.index') }}">Hủy</a>
        </div>
    </form>

    @if ($question->exists)
        @include('admin.partials.history', ['activities' => $activities])
    @endif
@endsection
