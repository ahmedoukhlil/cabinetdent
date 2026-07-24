<?php

namespace App\Traits;

trait HasLazyLoadingPlaceholder
{
    public function placeholder()
    {
        return <<<'HTML'
        <div class="flex flex-col items-center justify-center gap-3" style="min-height: 60dvh;">
            <div class="spinner spinner-dark"></div>
            <span class="text-gray-500 text-sm">Chargement...</span>
        </div>
        HTML;
    }
}
