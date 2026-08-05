<?php

namespace Amplify\Frontend\Commands;

use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\CartItem;
use Illuminate\Console\Command;

class CleanCartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amplify:fnd-clean-cart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete inactive or empty carts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startAt = now();

        try {

            $this->components->info("Deleting Inactive carts.");

            CartItem::join('carts', 'carts.id', '=', 'cart_items.cart_id')
                ->where('carts.status', '=', 0)
                ->delete();

            Cart::where('status', '=', 0)
                ->delete();

            if (config('amplify.frontend.guest_add_to_cart')) {

                $this->components->info("Deleting Guest carts with empty items.");

                Cart::leftJoin('cart_items', 'cart_items.cart_id', '=', 'carts.id')
                    ->whereNull('cart_items.cart_id')
                    ->delete();

            }

            $this->components->info("Cart Cleanup finished in: ". str_replace([' after'], '', now()->diffForHumans($startAt)) . '.');

            return self::SUCCESS;

        } catch (\Throwable $e) {

            report($e);

            return self::FAILURE;
        }
    }
}
