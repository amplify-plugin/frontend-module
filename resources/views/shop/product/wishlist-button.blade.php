<x-wishlist-button :product="$product" class="btn btn-sm btn-wishlist">
    <x-slot:add-label>
        <i class="icon-heart" data-toggle="tooltip" title="Add to Wishlist" data-placement="top"></i>
    </x-slot>
    <x-slot:remove-label>
        <i class="icon-heart" data-toggle="tooltip" title="Remove to Wishlist" data-placement="top"></i>
    </x-slot>
</x-wishlist-button>