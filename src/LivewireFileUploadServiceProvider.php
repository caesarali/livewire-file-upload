<?php

namespace Caesarali\LivewireFileUpload;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireFileUploadServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'livewire-file-upload');

        Livewire::addComponent(
            name: 'file-upload',
            class: FileUpload::class,
        );
    }
}
