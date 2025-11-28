<?php

namespace App\Nova\Fields;

class Files extends \Faradele\Files\Files
{
    public function disablePreviewModal(bool $value = true): static
    {
        return $this->withMeta(['disablePreviewModal' => $value]);
    }

    public function withLogViewHistory(bool $value = true): static
    {
        return $this->withMeta(['withLogViewHistory' => $value]);
    }

    public function maskUnopenedImages(bool $value = true): static
    {
        return $this->withMeta(['maskUnopenedImages' => $value]);
    }
}