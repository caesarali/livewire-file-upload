<?php

namespace Caesarali\LivewireFileUpload\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ResolvesTemporaryUploads
{
    protected function prepareForValidation(): void
    {
        $this->resolveTemporaryUploads();
    }

    /** @param array<int|string, mixed> $parameters */
    public function callAction(mixed $method, mixed $parameters): mixed
    {
        $this->resolveTemporaryUploads(request());

        return $this->{$method}(...array_values($parameters));
    }

    protected function resolveTemporaryUploads(?Request $request = null): void
    {
        $request ??= $this instanceof Request ? $this : request();

        foreach ((array) $request->input('_livewire_uploads') as $field => $signedPaths) {
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

            $request->files->set(
                $field,
                $multiple ? $files : ($files[0] ?? null),
            );
        }

        $request->request->remove('_livewire_uploads');
    }
}
