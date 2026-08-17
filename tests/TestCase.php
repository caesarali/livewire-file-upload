<?php

namespace Caesarali\LivewireFileUpload\Tests;

use Caesarali\LivewireFileUpload\LivewireFileUploadServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LivewireFilepond\LivewireFilepondServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            LivewireFilepondServiceProvider::class,
            LivewireFileUploadServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('livewire.temporary_file_upload.disk', 'local');
    }
}
