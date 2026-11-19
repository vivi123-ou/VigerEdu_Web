<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

/**
 * Trình soạn thảo CKEditor 5 (Classic build từ CDN) cho Filament.
 * Script CKEditor + Alpine component được nạp trong AdminPanelProvider (render hook HEAD_END).
 */
class CKEditor extends Field
{
    protected string $view = 'filament.forms.components.ckeditor';

    protected string|Closure|null $uploadUrl = '/admin/posts/api/upload-image';

    protected int|Closure $minHeight = 420;

    protected string|Closure|null $placeholder = null;

    public function uploadUrl(string|Closure|null $url): static
    {
        $this->uploadUrl = $url;

        return $this;
    }

    public function minHeight(int|Closure $px): static
    {
        $this->minHeight = $px;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getUploadUrl(): ?string
    {
        return $this->evaluate($this->uploadUrl);
    }

    public function getMinHeight(): int
    {
        return $this->evaluate($this->minHeight);
    }

    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder);
    }
}
