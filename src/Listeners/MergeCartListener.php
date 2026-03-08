<?php

namespace Amplify\Frontend\Listeners;

use Amplify\Frontend\Events\CartUpdated;
use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\System\Backend\Models\Cart;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;

class MergeCartListener
{
    /**
     * Handle the event.
     */
    public function handle(ContactLoggedIn $event): void
    {
        $payload = [
            'meta' => [],
            'errors' => [],
            'items' => [],
        ];

        $guestItems = $event->guestCart?->cartItems?->toArray() ?? [];

        $event->guestCart?->update(['status' => false]);

        if (empty($guestItems)) {
            return;
        }

        $loggedCart = Cart::firstOrCreate(['contact_id' => $event->contact->getKey(), 'status' => true]);

        $cartItems = $loggedCart->wasRecentlyCreated
            ? []
            : $loggedCart->cartItems->toArray();

        foreach ($cartItems as $index => $item) {
            foreach ($guestItems as $guestIndex => $guestItem) {
                if ($guestItem['product_id'] == $item['product_id']) {
                    $item['quantity'] = max($item['quantity'], $guestItem['quantity']);
                    unset($guestItems[$guestIndex]);
                    break;
                }
            }
            $cartItems[$index] = $item;
        }

        try {

            $payload['items'] = array_merge($cartItems, $guestItems);

            $data = app(Pipeline::class)->send($payload)
                ->through(config('amplify.add_to_cart_pipeline', []))
                ->then(function ($data) {
                    foreach ($data['items'] as $index => $item) {
                        $data['items'][$index]['error'] = isset($data['errors'][$index]) ? implode("\n", $data['errors'][$index]) : null;
                    }
                    return $data;
                });

            $loggedCart->cartItems()->delete();

            $loggedCart->cartItems()->createMany($data['items']);

        } catch (\Throwable $th) {
            Log::error($th);
        }

        \event(new CartUpdated($loggedCart));
    }
}
