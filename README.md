# Livewire File Upload

A FilePond-powered Livewire component for files that are uploaded through Livewire and submitted to a Laravel controller using a standard HTML form.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- Livewire 4.1+

## Installation

Install the package via Composer:

```bash
composer require caesarali/livewire-file-upload
```

The package registers the component and loads the required FilePond assets automatically.

## Usage

Add the component to a standard HTML form:

```blade
<form method="POST" action="{{ route('documents.store') }}">
    @csrf

    <livewire:file-upload
        name="documents"
        multiple
        mimetypes="application/pdf"
        :validation-rules="['max:10240']"
    />

    <button type="submit">Save</button>
</form>
```

The form itself is submitted normally. Livewire only handles the temporary upload and places signed file references in the submitted form.

### Custom placeholder

Pass custom placeholder markup through the default slot:

```blade
<livewire:file-upload name="documents" multiple>
    <span>
        Drop your documents here or
        <span class="filepond--label-action">browse</span>
    </span>
</livewire:file-upload>
```

When no slot is provided, the component uses FilePond's default placeholder.

Use `ResolvesTemporaryUploads` in the Form Request that handles the form:

```php
<?php

namespace App\Http\Requests;

use Caesarali\LivewireFileUpload\Concerns\ResolvesTemporaryUploads;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    use ResolvesTemporaryUploads;

    public function rules(): array
    {
        return [
            'documents' => ['required', 'array'],
            'documents.*' => ['file', 'mimetypes:application/pdf', 'max:10240'],
        ];
    }
}
```

The controller receives regular Laravel uploaded files:

```php
public function store(StoreDocumentRequest $request): RedirectResponse
{
    foreach ($request->file('documents') as $document) {
        $document->store('documents');
    }

    return back();
}
```

### Single file

Omit `multiple` for a single file:

```blade
<livewire:file-upload
    name="avatar"
    mimetypes="image/jpeg,image/png"
    :validation-rules="['max:5120']"
/>
```

```php
public function rules(): array
{
    return [
        'avatar' => ['required', 'file', 'mimetypes:image/jpeg,image/png', 'max:5120'],
    ];
}
```

Access it with `$request->file('avatar')`.

## Component properties

| Property | Default | Description |
| --- | --- | --- |
| `name` | `files` | Field name received by the Form Request. |
| `multiple` | `false` | Allows multiple files. |
| `required` | `false` | Marks the FilePond input as required. |
| `disabled` | `false` | Disables the FilePond input. |
| `max-files` | `null` | Maximum number of files for a multiple upload. |
| `mimetypes` | `null` | Comma-separated MIME types accepted by validation. |
| `validation-rules` | `[]` | Additional Laravel rules applied to each uploaded file. |
| `max-files-message` | Package default | Custom message when `max-files` is exceeded. |

The final validation rules should still be defined in the Form Request. Component validation provides immediate upload feedback, while the Form Request remains the authoritative validation before the controller processes the files.

## Existing `prepareForValidation()` method

If the Form Request already defines `prepareForValidation()`, call the trait's resolver from that method:

```php
protected function prepareForValidation(): void
{
    $this->resolveTemporaryUploads();

    $this->merge([
        'title' => trim((string) $this->input('title')),
    ]);
}
```

## How it works

1. FilePond uploads files to Livewire's temporary upload storage.
2. The component adds signed temporary-file references to the HTML form.
3. `ResolvesTemporaryUploads` verifies those references and restores them as `TemporaryUploadedFile` instances before Form Request validation.

Invalid, modified, expired, or missing temporary upload references are rejected. Always validate file type and size in the Form Request before storing a file.

## Testing

```bash
composer install
composer test
```

## License

The MIT License.
