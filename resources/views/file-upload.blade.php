<div
    x-data
    x-on:formdata.capture.window="$event.formData.delete(@js('filepond-'.$this->getId()))"
>
    <x-filepond::upload
        wire:model="file"
        name="filepond-{{ $this->getId() }}"
        :multiple="$multiple"
        :required="$required"
        :disabled="$disabled"
        :max-files="$maxFiles"
        :maxfilesmsg="$maxFilesMessage"
        :placeholder="$slots->has('default') ? $slot : null"
    />

    @foreach ($this->signedFilePaths as $signedFilePath)
        <input
            type="hidden"
            name="_livewire_uploads[{{ $name }}]{{ $multiple ? '[]' : '' }}"
            value="{{ $signedFilePath }}"
        >
    @endforeach
</div>

@assets
    @filepondScripts

    <style>
        .filepond--root {
            margin-bottom: 0;
        }

        .filepond--credits {
            display: none;
        }

        .filepond--drop-label label {
            font-weight: normal !important;
        }

        .filepond--panel-root {
            background-color: transparent;
        }
    </style>
@endassets
