@php
    $documentLayout = $entry['layout'] ?? ($tab['layout'] ?? 'flat');
    if (! in_array($documentLayout, ['flat', 'grouped'], true)) {
        $documentLayout = 'flat';
    }

    $documentFileLabel = static function ($document): string {
        $customLabel = trim((string) ($document->label ?? ''));
        if ($customLabel !== '') {
            return $customLabel;
        }

        if (! empty($document->file_path)) {
            $path = parse_url($document->file_path, PHP_URL_PATH) ?: $document->file_path;
            $filename = rawurldecode(basename($path));

            return pathinfo($filename, PATHINFO_FILENAME) ?: $filename;
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
        @endphp

        @push('title')
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#{{ $paneId }}" role="tab">
                    {{ __($documentType->name ?? 'Document') }}
                </a>
            </li>
        @endpush

        @push('content')
            <div class="tab-pane fade" id="{{ $paneId }}" role="tabpanel" aria-labelledby="{{ $paneId }}">
                @include('widget::product.tabs.documents.accordion', [
                    'documents' => $documents,
                    'paneId' => $paneId,
                ])
            </div>
        @endpush
    @endforeach
@else
    @foreach ($product->documents as $document)
        @php
            $paneId = Str::slug($document->documentType->name ?? 'document').'-'.$document->id;
        @endphp

        @push('title')
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#{{ $paneId }}" role="tab">
                    {{ __($document->documentType->name ?? 'Document') }}
                </a>
            </li>
        @endpush

        @push('content')
            <div class="tab-pane fade" id="{{ $paneId }}" role="tabpanel" aria-labelledby="{{ $paneId }}">
                @include('widget::product.tabs.documents.accordion', [
                    'documents' => collect([$document]),
                    'paneId' => $paneId,
                ])
            </div>
        @endpush
    @endforeach
@endif
