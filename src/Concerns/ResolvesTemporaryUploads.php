<?php

namespace Caesarali\LivewireFileUpload\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** @mixin FormRequest */
trait ResolvesTemporaryUploads
{
    protected function prepareForValidation(): void
    {
        $this->resolveTemporaryUploads();
    }

    protected function resolveTemporaryUploads(): void
    {
        foreach ((array) $this->input('_livewire_uploads') as $field => $signedPaths) {
            $multiple = is_array($signedPaths);
            $files = [];

            foreach (Arr::wrap($signedPaths) as $signedPath) {
                $path = is_string($signedPath)
                    ? TemporaryUploadedFile::extractPathFromSignedPath($signedPath)
                    : false;

                if ($path === false) {
                    throw ValidationException::withMessages([
                        $field => 'File upload tidak valid.',
                    ]);
                }

                $file = TemporaryUploadedFile::createFromLivewire($path);

                if (! $file->exists()) {
                    throw ValidationException::withMessages([
                        $field => 'File upload sudah tidak tersedia.',
                    ]);
                }

                $files[] = $file;
            }

            $this->files->set(
                $field,
                $multiple ? $files : ($files[0] ?? null),
            );
        }

        $this->request->remove('_livewire_uploads');
    }
}
