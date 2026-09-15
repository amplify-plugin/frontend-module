@php
    $accordionId = $paneId.'-accordion';
    $isSingle = $documents->count() === 1;
@endphp

<div class="accordion document-accordion" id="{{ $accordionId }}">
    @foreach ($documents as $document)
        @php
            $headingId = $paneId.'-heading-'.$document->id;
            $collapseId = $paneId.'-file-'.$document->id;
            $fileLabel = $documentFileLabel($document);
            $openHint = __('Click to open :label', ['label' => $fileLabel]);
            $closeHint = __('Click to close :label', ['label' => $fileLabel]);
        @endphp
        <div class="card">
            <div class="card-header" id="{{ $headingId }}">
                <h2 class="mb-0">
                    <button
                        class="btn btn-link btn-block text-left{{ $isSingle ? '' : ' collapsed' }}"
                        type="button"
                        data-toggle="collapse"
                        data-target="#{{ $collapseId }}"
                        data-open-hint="{{ $openHint }}"
                        data-close-hint="{{ $closeHint }}"
                        title="{{ $isSingle ? $closeHint : $openHint }}"
                        aria-expanded="{{ $isSingle ? 'true' : 'false' }}"
                        aria-controls="{{ $collapseId }}"
                    >
                        {{ $documentFileLabel($document) }}
                    </button>
                </h2>
            </div>
            <div
                id="{{ $collapseId }}"
                class="collapse{{ $isSingle ? ' show' : '' }}"
                aria-labelledby="{{ $headingId }}"
                data-parent="#{{ $accordionId }}"
            >
                <div class="card-body">
                    @include('widget::product.tabs.documents.viewer')
                </div>
            </div>
        </div>
    @endforeach
</div>
