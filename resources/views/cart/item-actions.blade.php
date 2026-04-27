<div {!! $htmlAttributes !!}>
    @if($updateStyle == 'line')
        <button
                id="discard-button-{cart_item_id}"
                type="button"
                style="display: none"
                class="btn btn-sm discard-from-cart"
                onclick="Amplify.discardQtyChange(event, '#cart-item-{cart_item_id}');">
            <i class="icon-reload"></i> {{ __('Revert') }}
        </button>
        <button
                id="update-button-{cart_item_id}"
                type="button"
                style="display: none"
                class="btn btn-sm update-from-cart"
                onclick="Amplify.updateCartItem(event, '#cart-item-{cart_item_id}', {cart_item_id});">
            <i class="icon-check"></i> {{ __('Update') }}
        </button>
    @endif

    <button
            type="button"
            class="btn btn-sm remove-from-cart"
            onclick="Amplify.removeCartItem({cart_item_id});">
        <i class="icon-cross"></i> {{ __('Remove') }}
    </button>
</div>