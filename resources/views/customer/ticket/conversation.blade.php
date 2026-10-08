@pushonce('plugin-style')
    <link rel="stylesheet" type="text/css" href="{{ asset('packages/lightbox2/css/lightbox.min.css') }}">
@endpushonce
@pushonce('internal-style')
    <style>
        .min-h-200 {
            min-height: 200px;
        }

        .min-h-100 {
            min-height: 100px;
        }

        .ticket-conversation {
            display: flex;
            flex-direction: column;
            height: clamp(420px, calc(100vh - 220px), 720px);
            min-height: 0;
        }

        .ticket-conversation > header,
        .ticket-conversation > footer {
            flex: 0 0 auto;
        }

        .ticket-conversation .chat-stage {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .ticket-conversation [data-ticket-scroll] {
            flex: 1 1 0%;
            height: 0;
            min-height: 0;
            overflow-y: auto;
            overflow-anchor: none;
            overscroll-behavior: contain;
        }
        .chat-boot {
            display: none;
            position: absolute;
            inset: 0;
            z-index: 3;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            color: #C31E1E;
        }
        .chat-stage.is-loading .chat-boot { display: flex; }
        .chat-booting > * { visibility: hidden; }
        .chat-boot__spinner {
            width: 28px;
            height: 28px;
            border: 3px solid rgba(195, 30, 30, 0.2);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: chat-boot-spin .7s linear infinite;
        }
        @keyframes chat-boot-spin { to { transform: rotate(360deg); } }

        .ticket-composer {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ticket-composer .ticket-composer__btn {
            flex: 0 0 44px;
            width: 44px !important;
            height: 44px !important;
            min-height: 44px;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
            letter-spacing: 0;
            border-radius: 4px !important;
        }
        .ticket-composer .btn-primary:focus,
        .ticket-composer .btn-primary:focus-visible,
        .ticket-composer .btn-primary:active,
        .ticket-composer .btn-primary:disabled,
        .ticket-composer .btn-primary.disabled {
            outline: none !important;
            border-color: #C31E1E !important;
            box-shadow: 0 0 0 0.2rem rgba(195, 30, 30, 0.35) !important;
        }
        .ticket-composer textarea.ticket-composer__input {
            flex: 1 1 auto;
            width: 100%;
            height: 44px !important;
            min-height: 44px !important;
            max-height: 44px !important;
            margin: 0 !important;
            padding: 4px 14px !important;
            line-height: 16px !important;
            font-size: 14px !important;
            box-sizing: border-box;
            border-radius: 4px !important;
            overflow-y: auto;
            resize: none;
        }
        .chat-picks { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
        .chat-picks:empty { display: none; }
        .chat-pick {
            display: flex; align-items: center; gap: 8px; max-width: 260px;
            padding: 4px 6px 4px 4px; border: 1px solid #e4e6eb; border-radius: 10px; background: #f8f9fb;
        }
        .chat-pick img, .chat-pick__icon {
            width: 36px; height: 36px; flex: 0 0 36px; border-radius: 6px; object-fit: cover;
        }
        .chat-pick__icon {
            display: inline-flex; align-items: center; justify-content: center;
            background: #e6e9ef; color: #3d4d6a;
        }
        .chat-pick__icon i { font-size: 14px; line-height: 1; }
        .chat-pick__meta { min-width: 0; }
        .chat-pick__name {
            display: block; max-width: 150px; overflow: hidden; text-overflow: ellipsis;
            white-space: nowrap; font-size: 13px; font-weight: 600;
        }
        .chat-pick__size { display: block; font-size: 11px; color: #6c757d; }
        .chat-pick__remove {
            border: 0; background: transparent; color: #6c757d; font-size: 18px;
            line-height: 1; padding: 0 2px; cursor: pointer;
        }
        .chat-file-input {
            position: absolute !important; width: 1px !important; height: 1px !important;
            padding: 0 !important; margin: -1px !important; overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important;
        }
        .chat-spinner {
            width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.35);
            border-top-color: #fff; border-radius: 50%; display: inline-block;
            animation: chat-spin .7s linear infinite;
        }
        .chat-spinner[hidden], [data-send-icon][hidden] { display: none !important; }
        @keyframes chat-spin { to { transform: rotate(360deg); } }

        @media (max-width: 767.98px) {
            .ticket-conversation {
                height: clamp(360px, calc(100vh - 160px), 640px);
            }
        }
    </style>
@endpushonce
@pushonce('plugin-script')
    <script src="{{ asset('packages/lightbox2/js/lightbox.min.js') }}"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('packages/lightbox2/css/lightbox.min.css') }}">
@endpushonce
<div {!! $htmlAttributes !!}>
<div class="card border-0 shadow h-100">
    <div class="d-flex flex-column h-100 bg-white rounded overflow-hidden">
        @if ($threadMsg)
            @php
                $receiver = $threadMsg->participants->where('model', "Amplify\System\Backend\Models\User")->first();
            @endphp

            <div id="ticket-chat"
                 class="ticket-conversation"
                 data-mode="customer"
                 data-poll-url="{{ route('frontend.tickets.messages', $threadMsg->id) }}"
                 data-send-url="{{ route('frontend.tickets.messages.store', $threadMsg->id) }}">
            <!-- Chat Header -->
            <header class="px-4 py-3 border-bottom bg-white sticky-top">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-primary text-white mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 50%;">
                            <i class="fa fa-headset"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 font-weight-bold text-dark text-truncate" style="max-width: 250px;">{{ $threadMsg->title }}</h6>
                            <small class="text-muted text-bold">
                                <span class="d-inline-block bg-success rounded-circle mr-1" style="width: 8px; height: 8px;"></span>
                                {{ $receiver ? optional(optional($receiver)->user)->name : 'Waiting for support agent' }}
                            </small>
                        </div>
                    </div>
                    <div class="text-right d-none d-md-block ">
                        <span class="badge badge-light px-3 py-2 text-black">
                            <i class="fa fa-ticket-alt mr-1"></i> Ticket #{{ $threadMsg->id }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Chat Messages Area -->
            <div class="chat-stage is-loading">
            <div class="chat-boot" role="status" aria-label="Loading messages">
                <span class="chat-boot__spinner"></span>
            </div>
            <section data-ticket-scroll class="chat-booting flex-grow-1 overflow-auto p-4" style="background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);">
                @if ($threadMsg->tickets?->count())
                    <div data-ticket-list class="chat-messages">
                        @foreach ($threadMsg->tickets as $message)
                            @php
                                $contact = customer(true);
                                $isMine = $message->model === \Amplify\System\Backend\Models\Contact::class
                                    && (int) $message->sender_id === (int) ($contact->id ?? 0);
                                $attachments = collect(\Amplify\System\Ticket\TicketService::listValues($message->attachments));
                                $attachmentTitles = collect(\Amplify\System\Ticket\TicketService::listValues($message->attachment_title));
                            @endphp

                            <div data-message-id="{{ $message->id }}" class="d-flex mb-4 {{ $isMine ? 'justify-content-end' : 'justify-content-start' }}">
                                <div class="message-content" style="max-width: 75%;">
                                    <div class="message-bubble p-3 text-left {{ $isMine ? 'bg-primary text-white' : 'bg-white border' }}" style="border-radius: {{ $isMine ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }}; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                        <p class="mb-0 text-left" style="margin: 0; line-height: 1.45; word-break: break-word;">{!! nl2br(e($message->message)) !!}</p>

                                        @if ($attachments->isNotEmpty())
                                            <!-- Image Attachments -->
                                            @php
                                                $imageAttachments = $attachments->filter(fn($a) => preg_match('/\.(jpe?g|png|gif|webp)$/i', $a));
                                                $fileAttachments = $attachments->filter(fn($a) => !preg_match('/\.(jpe?g|png|gif|webp)$/i', $a));
                                            @endphp

                                            @if ($imageAttachments->isNotEmpty())
                                                <div class="mt-3 d-flex flex-wrap" style="gap: 8px;">
                                                    @foreach ($imageAttachments as $index => $attachment)
                                                        <a href="{{ $attachment }}"
                                                           data-lightbox="message-{{ $message->id }}"
                                                           data-title="{{ $message->message }}"
                                                           class="d-inline-block rounded overflow-hidden border"
                                                           style="line-height: 0;">
                                                            <img src="{{ $attachment }}" alt="Attachment" class="img-fluid" style="max-height: 140px; max-width: 200px; object-fit: cover;">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- File Attachments -->
                                            @if ($fileAttachments->isNotEmpty())
                                                @foreach ($fileAttachments as $index => $attachment)
                                                    <a href="{{ $attachment }}"
                                                       target="_blank"
                                                       download
                                                       class="d-flex align-items-center p-2 mt-2 rounded text-decoration-none {{ $isMine ? '' : 'bg-light' }}"
                                                       style="{{ $isMine ? 'background: rgba(255,255,255,0.15);' : '' }}">
                                                        <span class="mr-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 6px; background: #dfe3ea; color: #3d4d6a; flex: 0 0 32px;">
                                                            <i class="fa fa-file-alt" style="font-size: 14px;"></i>
                                                        </span>
                                                        <span class="text-truncate small {{ $isMine ? 'text-white' : 'text-dark' }}">
                                                            {{ $attachmentTitles->get($index, basename($attachment)) }}
                                                        </span>
                                                    </a>
                                                @endforeach
                                            @endif
                                        @endif
                                    </div>
                                    <small class="text-muted d-block mt-1 px-2" style="font-size: 11px;"
                                           data-ticket-time="{{ $message->created_at?->toIso8601String() }}">{{ \Amplify\System\Ticket\TicketService::humanTime($message->created_at) }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div data-ticket-empty class="min-h-200 d-flex flex-column align-items-center justify-content-center text-muted py-5">
                        <div class="mb-3" style="width: 80px; height: 80px; border-radius: 50%; background: rgba(0,0,0,0.05);">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="fa fa-comments" style="font-size: 32px; opacity: 0.5;"></i>
                            </div>
                        </div>
                        <p class="mb-0">No messages yet</p>
                        <small>Start the conversation by sending a message below</small>
                    </div>
                @endif
            </section>
            </div>

            <!-- Chat Input Footer -->
            <footer class="border-top bg-white p-3">
                <form data-ticket-form data-compose data-compose-max="10" data-compose-max-kb="10240" data-compose-icon="fa fa-file-alt" action="{{ route('frontend.tickets.messages.store', $threadMsg->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div data-compose-picks class="chat-picks"></div>
                    <div class="ticket-composer">
                        <button type="button" data-compose-attach class="btn btn-light rounded-circle d-flex align-items-center justify-content-center ticket-composer__btn" aria-label="Attach a file">
                            <i class="fa fa-paperclip text-muted"></i>
                        </button>
                        <textarea name="message"
                                  class="form-control border ticket-composer__input"
                                  rows="1"
                                  placeholder="Type your message...">{{ old('message') }}</textarea>
                        <button type="submit"
                                id="send-msg"
                                class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center ticket-composer__btn"
                                aria-label="Send">
                            <i class="fa fa-paper-plane" data-send-icon></i>
                            <span class="chat-spinner" data-send-spinner hidden></span>
                        </button>
                    </div>
                    <div data-ticket-errors class="text-danger small d-block mt-1 px-3"></div>
                    @error('message')
                        <small class="text-danger d-block mt-1 px-3">{{ $message }}</small>
                    @enderror
                    @if ($errors->has('attachments') || $errors->has('attachments.*'))
                        <small class="text-danger d-block mt-1 px-3">
                            {{ $errors->first('attachments') }}
                            {{ $errors->first('attachments.*') }}
                        </small>
                    @endif
                    <input type="file" class="chat-file-input" data-compose-file name="attachments[]" multiple tabindex="-1" aria-hidden="true" accept="{{ \Amplify\System\Ticket\Requests\TicketRequest::acceptAttribute() }}">
                </form>
            </footer>
            </div>
            <script src="{{ asset('vendor/ticket/js/ticket-chat.js') }}"></script>
        @endif
    </div>
</div>
</div>
