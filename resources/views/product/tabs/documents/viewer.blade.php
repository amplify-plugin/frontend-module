@php
    $mediaType = $document->documentType->media_type ?? '';
@endphp

@switch($mediaType)
    @case('pdf')
        <object class="iframe-style" data="{{ external_asset($document->file_path) }}"
                type="application/pdf">
            <embed src="{{ external_asset($document->file_path) }}" type="application/pdf"
                   style="width: 100% !important;"/>
        </object>
        @break

    @case('image')
        <div class="text-center w-100">
            <img class="img-style" src="{{ assets_image($document->file_path) }}"
                 alt="{{ $document->documentType->name }}">
        </div>
        @break

    @case('video')
        <div class="text-center">
            <video width="320" height="240" controls>
                <source src="{{ asset($document->file_path) }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        @break

    @case('embedded')
        @if ($document->documentType->name === '360 Image')
            @foreach (json_decode($document->content, true) as $index => $viewer)
                <style>
                    .viewer-container {
                        margin-bottom: 20px;
                        padding: 10px;
                    }

                    .viewer-container h3 {
                        margin-top: 0;
                        text-align: center;
                    }

                    .viewer {
                        max-width: 600px;
                        margin: auto;
                        overflow: hidden;
                        cursor: grab;
                    }

                    .viewer img {
                        width: 100%;
                        user-drag: none;
                        user-select: none;
                        pointer-events: none;
                        border: 1px solid #eee;
                        border-radius: 10px;
                    }
                </style>
                <div class="viewer-container">
                    <h3>{{ $viewer['display_name'] }}</h3>
                    <div id="viewer-{{ $index }}" class="viewer"></div>
                </div>
                <script>
                    function image360Viewer(data, containerId) {
                        const cols = parseInt(data.cols);
                        const rows = parseInt(data.rows);
                        const initialImage = data.initial_image;
                        const filenameTemplate = data.initial_image.substring(0, data.initial_image.lastIndexOf('/') + 1) + data
                            .filename;

                        const viewer = document.getElementById(containerId);
                        let currentImageIndex = 0;

                        const initialImg = document.createElement('img');
                        initialImg.src = initialImage;
                        initialImg.style.userSelect = 'none';
                        viewer.appendChild(initialImg);

                        const images = [];
                        for (let row = 1; row <= rows; row++) {
                            for (let col = 1; col <= cols; col++) {
                                const url = filenameTemplate.replace('{row}', row.toString().padStart(2, '0')).replace('{col}', col
                                    .toString().padStart(2, '0'));
                                images.push(url);
                            }
                        }

                        let isDragging = false;
                        let startX;

                        viewer.addEventListener('mousedown', (e) => {
                            isDragging = true;
                            startX = e.clientX;
                            viewer.style.cursor = 'grabbing';
                        });

                        viewer.addEventListener('mousemove', (e) => {
                            if (isDragging) {
                                const diff = e.clientX - startX;
                                if (Math.abs(diff) > 10) {
                                    startX = e.clientX;
                                    currentImageIndex = (currentImageIndex + (diff > 0 ? 1 : -1) + images.length) % images
                                        .length;
                                    initialImg.src = images[currentImageIndex];
                                }
                            }
                        });

                        viewer.addEventListener('mouseup', () => {
                            isDragging = false;
                            viewer.style.cursor = 'grab';
                        });

                        viewer.addEventListener('mouseleave', () => {
                            isDragging = false;
                            viewer.style.cursor = 'grab';
                        });

                        viewer.addEventListener('dragstart', (e) => {
                            e.preventDefault();
                        });
                    }

                    image360Viewer(@json($viewer), 'viewer-{{ $index }}');
                </script>
            @endforeach
        @else
            <div class="p-3">
                {!! $document->content !!}
            </div>
        @endif
        @break

    @case('google_doc')
    @case('google_sheet')
        <div class="mb-2">
            <a href="{{ external_asset($document->file_path) }}" target="_blank">
                View on Google Docs <i class="pe-7s-exapnd2"></i>
            </a>
        </div>
        @break

    @case('doc')
    @case('xls')
    @case('octet-stream')
        <a href="{{ external_asset($document->file_path) }}" class="btn btn-primary"
           download="{{ $document->documentType->name }}">Download</a>
        @break

    @default
        @if (!empty($document->file_path))
            <a href="{{ external_asset($document->file_path) }}" class="btn btn-primary"
               download="{{ $document->documentType->name }}">Download</a>
        @elseif (!empty($document->content))
            <div class="p-3">
                {!! $document->content !!}
            </div>
        @endif
@endswitch
