{{-- Khung "Báo cáo đã đóng": thẻ không có chìa khóa xem (đã đóng thẻ, mở thẻ mới) hoặc đã làm bài lượt mới. --}}
<h1 class="uf__title">{{ setting('report_closed_title', 'Báo cáo đã đóng') }}</h1>
<p class="muted">{{ setting('report_closed_text') }}</p>
<form method="post" action="{{ route('test.start') }}" style="margin-top:22px">
    @csrf
    <button class="kbtn kbtn--red" type="submit">{{ setting('report_closed_button', 'Làm bài lại') }} <svg class="ic"><use href="#i-arrow-right"/></svg></button>
</form>
