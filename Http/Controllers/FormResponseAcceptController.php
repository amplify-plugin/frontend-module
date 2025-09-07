<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\FormResponseRequest;
use Amplify\System\Cms\Models\Form;
use Amplify\System\Cms\Models\FormResponse;
use Amplify\System\Factories\NotificationFactory;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class FormResponseAcceptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(FormResponseRequest $request, Form $form_code): RedirectResponse
    {
        $fields = $form_code->formFields->pluck('name')->map(fn ($item) => '__'.$item.'__')->toArray();

        $inputs = [
            'event_code' => $form_code->event_code,
            'value' => $this->formInputFormat($request->only($fields)),
        ];

        $responseTable = [];

        $form_code->formFields->each(function ($item) use (&$responseTable, &$inputs) {
            if ($item->type == 'rImage' || $item->type == 'rFile') {
                $file = $inputs['value']["__{$item->name}__"];
                $fullUrl = '';
                if ($file && $file->isValid()) {
                    $fileName = $file->getClientOriginalName();
                    $filePath = $file->storeAs('uploads/form-attachment', $fileName, 'public');
                    $fullUrl = url(Storage::url($filePath));
                }

                $inputs['value']["__{$item->name}__"] = $fullUrl;
                $responseTable[] = [
                    'field' => $item->label ?? 'N/A',
                    'value' => $fullUrl,
                ];
            } else {
                $responseTable[] = [
                    'field' => $item->label ?? 'N/A',
                    'value' => ($inputs['value']["__{$item->name}__"] ?? null),
                ];
            }
        });

        // $inputs['value'] = $responseTable;

        FormResponse::create([
            'form_id' => $form_code->getKey(),
            'contact_id' => customer_check() ? customer(true)->getKey() : null,
            'response' => json_encode($responseTable),
        ]);

        NotificationFactory::call(Event::FORM_SUBMITTED, $inputs);

        session()->flash('success', 'Thank you for your request.');

        return redirect()->to($request->input('return_url', url()->previous()));
    }

    private function formInputFormat($values)
    {
        return array_map(function ($item) {
            return (is_array($item)) ? implode(',', array_values($item)) : $item;
        }, $values);
    }
}
