@extends('layouts.admin', ['menuKey' => 'intro'])

@section('title', 'Trang giới thiệu – Các khối nội dung')

@section('content')
    @include('admin.intro-blocks.tabs', ['active' => 'blocks'])

    <p class="lead">Các khối hiện bên dưới banner, theo đúng thứ tự trong danh sách. Kéo biểu tượng ⋮⋮ để đổi thứ tự, thả ra là tự lưu. Khối đang tắt không hiện cho khách.</p>

    <section class="card">
        @if (empty($blocks))
            <p class="muted">Chưa có khối nào. Thêm khối ở bên dưới.</p>
        @else
            <div class="table-wrap">
                <table class="table table--wrap">
                    <thead><tr><th class="center">Thứ tự</th><th>Tên khối</th><th>Loại khối</th><th class="center">Hiển thị</th><th class="center">Tác vụ</th></tr></thead>
                    <tbody data-sortable>
                        @foreach ($blocks as $block)
                            <tr data-id="{{ $block['id'] }}" @class(['is-off' => ! $block['enabled']])>
                                <td class="center drag" draggable="true" title="Kéo để đổi thứ tự" aria-label="Kéo để đổi thứ tự">⋮⋮ {{ $loop->iteration }}</td>
                                <td><a href="{{ route('admin.intro-blocks.edit', $block['id']) }}">{{ $block['data']['title'] ?: '(chưa có tiêu đề)' }}</a></td>
                                <td>{{ \App\Support\IntroBlocks::typeLabel($block['type']) }}</td>
                                <td class="center">
                                    <form method="post" action="{{ route('admin.intro-blocks.toggle', $block['id']) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" @class(['toggle', 'is-on' => $block['enabled']]) title="{{ $block['enabled'] ? 'Đang hiện – bấm để ẩn' : 'Đang ẩn – bấm để hiện' }}" aria-label="{{ $block['enabled'] ? 'Đang hiện' : 'Đang ẩn' }}"></button>
                                    </form>
                                </td>
                                <td class="center actions">
                                    <a class="icon-btn icon-btn--edit" href="{{ route('admin.intro-blocks.edit', $block['id']) }}" title="Sửa" aria-label="Sửa">@include('partials.icon', ['name' => 'edit', 'size' => 16])</a>
                                    <form method="post" action="{{ route('admin.intro-blocks.destroy', $block['id']) }}" style="display:inline" data-confirm="Xóa khối này? Có thể lấy lại bằng Lịch sử thay đổi.">
                                        @csrf @method('DELETE')
                                        <button class="icon-btn icon-btn--delete" type="submit" title="Xóa" aria-label="Xóa">@include('partials.icon', ['name' => 'trash', 'size' => 16])</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <form method="post" action="{{ route('admin.intro-blocks.reorder') }}" data-reorder-form hidden>
                @csrf
                <button class="btn btn--primary" type="submit">Lưu thứ tự</button>
                <span class="muted">Thứ tự đã thay đổi, chưa lưu.</span>
            </form>
        @endif
    </section>

    <section class="card">
        <h2 class="card__title">Thêm khối</h2>
        <ul class="block-types">
            @foreach (\App\Support\IntroBlocks::TYPES as $type => [$label, $description])
                <li class="block-types__item">
                    <div>
                        <strong>{{ $label }}</strong>
                        <p class="muted">{{ $description }}</p>
                    </div>
                    <form method="post" action="{{ route('admin.intro-blocks.store') }}">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <button class="btn btn--small" type="submit">+ Thêm</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </section>

    @include('admin.partials.history', ['activities' => $activities])
@endsection
