@props([
    'id',
    'title' => null,
    'open' => false,
    'maxWidth' => 'max-w-lg',
    'closeAction' => null,
])

<dialog
    id="{{ $id }}"
    {{ $attributes->merge(['class' => 'modal']) }}
    data-modal @if($open)open @endif
>
    <div class="modal-box {{ $maxWidth }} p-0 overflow-hidden">
        @if($title || isset($header))
            <div class="modal-header">
                <div>
                    @if($title)
                        <h2 class="text-lg font-extrabold text-main">{{ $title }}</h2>
                    @endif
                    @isset($header)
                        {{ $header }}
                    @endisset
                </div>
                <button type="button" class="btn btn-circle btn-ghost" data-modal-close @if($closeAction) wire:click="{{ $closeAction }}" @endif aria-label="Close dialog"><i class="ri-close-line" aria-hidden="true"></i></button>
            </div>
        @endif

        <div class="modal-content">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="modal-footer">
                {{ $footer }}
            </div>
        @endisset
    </div>
</dialog>
