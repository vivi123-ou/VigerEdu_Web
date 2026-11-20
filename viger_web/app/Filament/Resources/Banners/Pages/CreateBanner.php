<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Banners\Schemas\BannerForm;
use Filament\Resources\Pages\CreateRecord;

class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return BannerForm::saveImageFields($data);
    }
}
