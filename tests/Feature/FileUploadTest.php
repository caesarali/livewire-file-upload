<?php

use Caesarali\LivewireFileUpload\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('registers the file upload component', function () {
    expect(Livewire::new('file-upload'))->toBeInstanceOf(FileUpload::class);
});

it('uses the FilePond default placeholder when no slot is given', function () {
    expect(Livewire::mount('file-upload'))
        ->not->toContain('pond.setOptions({ labelIdle:');
});

it('keeps a valid FilePond name and removes its redundant form field on submit', function () {
    expect(Livewire::mount('file-upload'))
        ->toContain('name\\u0022:\\u0022filepond-')
        ->toContain('x-on:formdata.capture.window=')
        ->toContain('formData.delete');
});

it('uses the default slot as a custom placeholder', function () {
    $html = Livewire::mount('file-upload', slots: [
        'default' => '<span>Choose your document</span>',
    ]);

    expect($html)
        ->toContain('pond.setOptions({ labelIdle:')
        ->toContain('Choose your document');
});

it('renders a signed reference for a single temporary upload', function () {
    Storage::fake('local');

    Livewire::test(FileUpload::class, ['name' => 'document'])
        ->set('file', UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'))
        ->assertSee('_livewire_uploads[document]', escape: false);
});

it('renders signed references for multiple temporary uploads', function () {
    Storage::fake('local');

    Livewire::test(FileUpload::class, ['name' => 'documents', 'multiple' => true])
        ->set('file', [
            UploadedFile::fake()->create('first.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('second.pdf', 10, 'application/pdf'),
        ])
        ->assertSee('_livewire_uploads[documents][]', escape: false);
});

it('initializes multiple uploads as an array', function () {
    Livewire::test(FileUpload::class, ['multiple' => true])
        ->assertSet('file', []);
});
