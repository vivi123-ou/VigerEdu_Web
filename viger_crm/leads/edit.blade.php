@extends('crm::layouts.app')

@section('title', 'Chỉnh sửa · '.$lead->full_name)
@section('breadcrumb', $lead->full_name.' · Chỉnh sửa')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('crm.leads.show', $lead) }}" class="back-link">← {{ $lead->full_name }} · {{ $lead->code() }}</a>
            <h1>{{ isset($section['no']) ? $section['no'].'. ' : '' }}{{ $section['title'] }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ route('crm.leads.update', [$lead, $key]) }}" class="card form-card">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert alert-danger">Vui lòng kiểm tra lại các trường được đánh dấu.</div>
        @endif

        <div class="form-grid {{ $key === 'holland' ? 'form-grid-3' : '' }}">
            @foreach ($section['fields'] as $name => $def)
                @continue($name === 'assigned_to' && ! auth()->user()->crmCan('leads.assign'))
                @include('crm::partials.field', ['value' => $lead->{$name}])
            @endforeach
        </div>

        <div class="form-actions">
            <a href="{{ route('crm.leads.show', $lead) }}#{{ $key }}" class="btn btn-outline">Huỷ</a>
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
    </form>
@endsection
