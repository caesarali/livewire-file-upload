<?php

use Caesarali\LivewireFileUpload\Concerns\ResolvesTemporaryUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

function temporaryUploadRequest(array $uploads): FormRequest
{
    return new class($uploads) extends FormRequest
    {
        use ResolvesTemporaryUploads;

        public function __construct(array $uploads)
        {
            parent::__construct(
                request: ['_livewire_uploads' => $uploads],
                server: ['REQUEST_METHOD' => 'POST'],
            );
        }

        public function resolveUploads(): void
        {
            $this->resolveTemporaryUploads();
        }
    };
}

it('resolves signed temporary uploads into the request file bag', function () {
    Storage::fake('tmp-for-tests');
    Storage::disk('tmp-for-tests')->put('livewire-tmp/document.pdf', 'content');

    $request = temporaryUploadRequest([
        'documents' => [TemporaryUploadedFile::signPath('document.pdf')],
    ]);

    $request->resolveUploads();

    expect($request->file('documents'))
        ->toHaveCount(1)
        ->and($request->file('documents.0'))
        ->toBeInstanceOf(TemporaryUploadedFile::class);
});

it('rejects a modified temporary upload reference', function () {
    Storage::fake('tmp-for-tests');

    $request = temporaryUploadRequest([
        'documents' => ['invalid:document.pdf'],
    ]);

    $request->resolveUploads();
})->throws(ValidationException::class, 'File upload tidak valid.');

it('rejects a missing temporary upload', function () {
    Storage::fake('tmp-for-tests');

    $request = temporaryUploadRequest([
        'documents' => [TemporaryUploadedFile::signPath('missing.pdf')],
    ]);

    $request->resolveUploads();
})->throws(ValidationException::class, 'File upload sudah tidak tersedia.');
