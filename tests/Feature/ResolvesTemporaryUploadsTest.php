<?php

use Caesarali\LivewireFileUpload\Concerns\ResolvesTemporaryUploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TestUploadController extends Controller
{
    use ResolvesTemporaryUploads;

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'documents' => ['required', 'array'],
            'documents.*' => ['file', 'mimetypes:application/pdf'],
        ]);

        foreach ($validated['documents'] as $document) {
            $document->store('documents', 'local');
        }

        return response()->noContent();
    }
}

class TestUploadFormRequest extends FormRequest
{
    use ResolvesTemporaryUploads;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['documents' => ['required', 'array'], 'documents.*' => ['file']];
    }
}

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

it('resolves uploads automatically for a controller using a normal request', function () {
    Storage::fake('local');
    Storage::fake('tmp-for-tests');
    Storage::disk('tmp-for-tests')->put('livewire-tmp/document.pdf', "%PDF-1.4\n%%EOF");
    Route::post('/test-controller-upload', [TestUploadController::class, 'store']);

    $this->postJson('/test-controller-upload', [
        '_livewire_uploads' => ['documents' => [TemporaryUploadedFile::signPath('document.pdf')]],
    ])->assertNoContent();

    expect(Storage::disk('local')->files('documents'))->toHaveCount(1);
});

it('rejects an invalid reference before the controller stores a file', function () {
    Storage::fake('local');
    Storage::fake('tmp-for-tests');
    Route::post('/test-controller-upload', [TestUploadController::class, 'store']);

    $this->postJson('/test-controller-upload', [
        '_livewire_uploads' => ['documents' => ['invalid-reference']],
    ])->assertUnprocessable()->assertJsonValidationErrors('documents');

    expect(Storage::disk('local')->files('documents'))->toBe([]);
});

it('still resolves uploads before form request validation', function () {
    Storage::fake('tmp-for-tests');
    Storage::disk('tmp-for-tests')->put('livewire-tmp/document.pdf', 'content');
    Route::post('/test-form-request-upload', fn (TestUploadFormRequest $request): array => [
        'resolved' => $request->file('documents.0') instanceof TemporaryUploadedFile,
    ]);

    $this->postJson('/test-form-request-upload', [
        '_livewire_uploads' => ['documents' => [TemporaryUploadedFile::signPath('document.pdf')]],
    ])->assertOk()->assertJsonPath('resolved', true);
});
