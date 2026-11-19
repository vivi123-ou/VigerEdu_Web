<?php

namespace App\Enums;

enum PostStatus: string
{
    case Published = 'PUBLISHED'; // Đã xuất bản (hiển thị công khai)
    case Draft = 'DRAFT';         // Nháp (chỉ admin thấy)
}
