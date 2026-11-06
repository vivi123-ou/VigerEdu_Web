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
                    <thead><tr><th></th><th>Khối</th><th>Loại</th><th>Trạng thái</th><th></th></tr></thead>
                    <tbody data-sortable>
                        @foreach ($blocks as $block)
                            <tr data-id="{{ $block['id'] }}" @class(['is-off' => ! $block['enabled']])>
                                <td class="drag" draggable="true" title="Kéo để đổi thứ tự" aria-label="Kéo để đổi thứ tự">⋮⋮</td>
                                <td><strong>{{ $block['data']['title'] ?: '(chưa có tiêu đề)' }}</strong></td>
                                <td>{{ \App\Support\IntroBlocks::typeLabel($block['type']) }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.intro-blocks.toggle', $block['id']) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" @class(['switch', 'is-on' => $block['enabled']]) title="Bấm để {{ $block['enabled'] ? 'tắt' : 'bật' }}">{{ $block['enabled'] ? 'Đang hiện' : 'Đang tắt' }}</button>
                                    </form>
                                </td>
                                <td class="actions">
                                    <a href="{{ route('admin.intro-blocks.edit', $block['id']) }}">Sửa</a>
                                    <form method="post" action="{{ route('admin.intro-blocks.destroy', $block['id']) }}" data-confirm="Xóa khối này? Có thể lấy lại bằng Lịch sử thay đổi.">
                                        @csrf @method('DELETE')
                                        <button class="link-danger" type="submit">Xóa</button>
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
