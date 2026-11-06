{{-- Form sửa trong thẻ câu hỏi (trả về khi bấm mở thẻ). Tự lưu khi sửa (data-autosave).
     Biến: $action, $fieldsView, $fieldsData --}}
<form method="post" action="{{ $action }}" class="form qcard__form" data-type-form data-autosave novalidate>
    @csrf
    @method('PUT')
    @include($fieldsView, $fieldsData)
    <div class="qcard__errors" data-qcard-errors hidden></div>
</form>
