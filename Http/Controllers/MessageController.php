<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\User;
use Amplify\System\Message\Facades\Messenger;
use Amplify\System\Message\Http\Requests\MessageRequest;
use Amplify\System\Message\Models\Message;
use Amplify\System\Message\Models\MessageThread;
use ErrorException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;

class MessageController extends Controller
{
    use HasDynamicPage;

    /**
     * Return All Message on Customer Panel
     *
     * @return string
     *
     * @throws ErrorException
     */
    public function index()
    {
        if (! customer(true)->can('message.messaging')) {
            abort(403);
        }
        $this->loadPageByType('message');

        return $this->render();
    }

    public function store(MessageRequest $request): Redirector|Application|RedirectResponse
    {
        if (! customer(true)->can('message.messaging')) {
            abort(403);
        }
        try {
            switch ($request->user_type) {
                case 'user':
                    $receiver = User::findOrFail($request->msg_to);
                    break;
                case 'contact':
                    $receiver = Contact::findOrFail($request->msg_to);
                    break;
            }

            $sender = ($request->boolean('as_customer')) ? customer(true) : backpack_user();

            $attachmentTitle = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentTitle = $file->getClientOriginalName();
            }

            $message = Messenger::from($sender)
                ->to($receiver)
                ->attachmentTitle($attachmentTitle)
                ->message($request->msg)
                ->attachment($request->file('attachment'))
                ->send();

            ($message instanceof Message)
                ? \Alert::success('Message Send Successfully')
                : \Alert::error('Something went wrong');

            $url = ($request->boolean('as_customer')) ? route('frontend.messages.show', $message->thread_id) : backpack_url('message', $message->thread_id);

            return ($message instanceof Message)
                ? redirect($url)
                : redirect()->back()->with('error', 'Something went wrong');
        } catch (\Exception $exception) {
            \Alert::error($exception->getMessage());

            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    /**
     * @return Application|Factory|View
     *
     * @throws ErrorException
     */
    public function show($id)
    {
        if (! customer(true)->can('message.messaging')) {
            abort(403);
        }

        return $this->index();
    }

    public function update(MessageRequest $request, $id): RedirectResponse
    {
        if (! customer(true)->can('message.messaging')) {
            abort(403);
        }
        $thread = MessageThread::findOrFail($id);

        $from = $request->boolean('as_customer') ? customer(true) : backpack_user();

        $attachmentTitle = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentTitle = $file->getClientOriginalName();
        }
        Messenger::from($from)
            ->to($thread)->attachmentTitle($attachmentTitle)
            ->message($request->msg)
            ->attachment($request->file('attachment'))
            ->send();

        return back();
    }
}
