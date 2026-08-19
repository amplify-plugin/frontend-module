@php
    $documentLayout = $documentLayout ?? ($tab['document_layout'] ?? 'flat');

    $documentFileLabel = static function ($document): string {
        if (! empty($document->file_path)) {
            $path = parse_url($document->file_path, PHP_URL_PATH) ?: $document->file_path;

            return rawurldecode(basename($path));
        }

        $typeName = $document->documentType->name ?? 'Document';

        return $typeName.' '.($document->order ?? $document->id);
    };
@endphp

@if ($documentLayout === 'grouped')
    @foreach ($product->documents->groupBy('document_type_id') as $typeId => $documents)
        @php
            $documentType = $documents->first()->documentType;
            $paneId = Str::slug($documentType->name ?? 'document').'-'.$typeId;
            $hasSubtabs = $documents->count() > 1;
        @endphp

        @push('title')
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#{{ $paneId }}" role="tab">
                    {{ __($documentType->name ?? 'Document') }}
                </a>
            </li>
        @endpush

        @push('content')
            <div class="tab-pane fade{{ $hasSubtabs ? ' has-document-subtabs' : '' }}" id="{{ $paneId }}" role="tabpanel" aria-labelledby="{{ $paneId }}">
                @if ($hasSubtabs)
                    <div class="nav document-subtabs" role="tablist">
                        @foreach ($documents as $index => $document)
                            @php $subId = $paneId.'-file-'.$document->id; @endphp
                            <button
                                type="button"
                                class="document-subtab {{ $index === 0 ? 'active' : '' }}"
                                data-toggle="tab"
                                data-target="#{{ $subId }}"
                                role="tab"
                                aria-controls="{{ $subId }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                            >
                                {{ $documentFileLabel($document) }}
                            </button>
                        @endforeach
                    </div>
                    <div class="tab-content document-subtab-content">
                        @foreach ($documents as $index => $document)
                            @php $subId = $paneId.'-file-'.$document->id; @endphp
                            <div
                                class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                id="{{ $subId }}"
                                role="tabpanel"
                            >
                                @include('widget::product.tabs.documents.viewer')
                            </div>
                        @endforeach
                    </div>
                @else
                    @php $document = $documents->first(); @endphp
                    @include('widget::product.tabs.documents.viewer')
                @endif
            </div>
        @endpush
    @endforeach
@else
    @foreach ($product->documents as $document)
        @php
            $id = Str::slug($document->documentType->name).'-'.$document->id;
            $viewType = str_replace('-', '_', $document->documentType->media_type);
        @endphp
        @include("widget::product.tabs.documents.{$viewType}")
    @endforeach
@endif
