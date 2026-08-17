<?php

namespace Caesarali\LivewireFileUpload;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\LivewireFilepond\WithFilePond;

class FileUpload extends Component
{
    use WithFilePond;

    public mixed $file = null;

    public string $name = 'files';

    public bool $multiple = false;

    public bool $required = false;

    public bool $disabled = false;

    public ?int $maxFiles = null;

    public ?string $mimetypes = null;

    /** @var array<int, mixed> */
    public array $validationRules = [];

    public string $placeholder = '
        <span class="text-sm">
            Drag & drop your file or <span class="filepond--label-action text-primary"> Browse </span>
        </span>
    ';

    public string $maxFilesMessage = 'You can upload a maximum of :max files.';

    public function rules(): array
    {
        $fileRules = ['required', 'file', ...$this->validationRules];

        if ($this->mimetypes !== null) {
            $fileRules[] = "mimetypes:{$this->mimetypes}";
        }

        if (! $this->multiple) {
            return ['file' => $fileRules];
        }

        $filesRules = ['required', 'array'];

        if ($this->maxFiles !== null) {
            $filesRules[] = "max:{$this->maxFiles}";
        }

        return [
            'file' => $filesRules,
            'file.*' => $fileRules,
        ];
    }

    public function validateUploadedFile(): bool
    {
        $this->validate();

        return true;
    }

    public function setMaxFilesError(string $message): void
    {
        $this->addError('file', $message);
    }

    /** @return array<int, string> */
    #[Computed]
    public function signedFilePaths(): array
    {
        return collect(Arr::wrap($this->file))
            ->filter(fn (mixed $file): bool => $file instanceof TemporaryUploadedFile)
            ->map(fn (TemporaryUploadedFile $file): string => TemporaryUploadedFile::signPath($file->getFilename()))
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire-file-upload::file-upload');
    }
}
