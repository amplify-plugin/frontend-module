/**
 * events.js
 *
 * Standardized E-commerce Event Library
 * Event names follow GA4 naming conventions where possible.
 */

/*
|--------------------------------------------------------------------------
| Abstract Event Class
|--------------------------------------------------------------------------
*/

export class SystemEvent extends CustomEvent {
    constructor(type, data = {}) {
        super(type, {
            detail: data
        });
    }
}

export class EcommerceEvent extends CustomEvent {
    constructor(type, data = {}) {
        super(type, {
            detail: data,
            bubbles: true,
            composed: true
        });
    }
}

/*
|--------------------------------------------------------------------------
| System Events
|--------------------------------------------------------------------------
*/

export class InitEvent extends SystemEvent {
    static EVENT_NAME = 'amplify.init';

    constructor(data) {
        super(InitEvent.EVENT_NAME, data);
    }
}


export class QuantityChangeEvent extends SystemEvent {
    static EVENT_NAME = 'qty_change';

    constructor(data) {
        super(QuantityChangeEvent.EVENT_NAME, data);
    }
}

export class QuantityRemovedEvent extends SystemEvent {
    static EVENT_NAME = 'qty_removed';

    constructor(data) {
        super(QuantityRemovedEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Product Discovery Events
|--------------------------------------------------------------------------
*/

export class ViewItemListEvent extends EcommerceEvent {
    static EVENT_NAME = 'view_item_list';

    constructor(data) {
        super(ViewItemListEvent.EVENT_NAME, data);
    }
}

export class SelectItemEvent extends EcommerceEvent {
    static EVENT_NAME = 'select_item';

    constructor(data) {
        super(SelectItemEvent.EVENT_NAME, data);
    }
}

export class ViewItemEvent extends EcommerceEvent {
    static EVENT_NAME = 'view_item';

    constructor(data) {
        super(ViewItemEvent.EVENT_NAME, data);
    }
}

export class SearchEvent extends EcommerceEvent {
    static EVENT_NAME = 'search';

    constructor(data) {
        super(SearchEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Cart Events
|--------------------------------------------------------------------------
*/

export class AddToCartEvent extends EcommerceEvent {
    static EVENT_NAME = 'add_to_cart';

    constructor(data) {
        super(AddToCartEvent.EVENT_NAME, data);
    }
}

export class RemoveFromCartEvent extends EcommerceEvent {
    static EVENT_NAME = 'remove_from_cart';

    constructor(data) {
        super(RemoveFromCartEvent.EVENT_NAME, data);
    }
}

export class ViewCartEvent extends EcommerceEvent {
    static EVENT_NAME = 'view_cart';

    constructor(data) {
        super(ViewCartEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Checkout Events
|--------------------------------------------------------------------------
*/

export class BeginCheckoutEvent extends EcommerceEvent {
    static EVENT_NAME = 'begin_checkout';

    constructor(data) {
        super(BeginCheckoutEvent.EVENT_NAME, data);
    }
}

export class AddShippingInfoEvent extends EcommerceEvent {
    static EVENT_NAME = 'add_shipping_info';

    constructor(data) {
        super(AddShippingInfoEvent.EVENT_NAME, data);
    }
}

export class RequestQuoteEvent extends EcommerceEvent {
    static EVENT_NAME = 'request_quote';

    constructor(data) {
        super(RequestQuoteEvent.EVENT_NAME, data);
    }
}

export class AddPaymentInfoEvent extends EcommerceEvent {
    static EVENT_NAME = 'add_payment_info';

    constructor(data) {
        super(AddPaymentInfoEvent.EVENT_NAME, data);
    }
}

export class PurchaseEvent extends EcommerceEvent {
    static EVENT_NAME = 'purchase';

    constructor(data) {
        super(PurchaseEvent.EVENT_NAME, data);
    }
}

export class RefundEvent extends EcommerceEvent {
    static EVENT_NAME = 'refund';

    constructor(data) {
        super(RefundEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Customer Events
|--------------------------------------------------------------------------
*/

export class LoginEvent extends EcommerceEvent {
    static EVENT_NAME = 'login';

    constructor(data) {
        super(LoginEvent.EVENT_NAME, data);
    }
}

export class SignUpEvent extends EcommerceEvent {
    static EVENT_NAME = 'sign_up';

    constructor(data) {
        super(SignUpEvent.EVENT_NAME, data);
    }
}

export class GenerateLeadEvent extends EcommerceEvent {
    static EVENT_NAME = 'generate_lead';

    constructor(data) {
        super(GenerateLeadEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Promotion Events
|--------------------------------------------------------------------------
*/

export class ViewPromotionEvent extends EcommerceEvent {
    static EVENT_NAME = 'view_promotion';

    constructor(data) {
        super(ViewPromotionEvent.EVENT_NAME, data);
    }
}

export class SelectPromotionEvent extends EcommerceEvent {
    static EVENT_NAME = 'select_promotion';

    constructor(data) {
        super(SelectPromotionEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Wishlist Events
|--------------------------------------------------------------------------
*/

export class AddToWishlistEvent extends EcommerceEvent {
    static EVENT_NAME = 'add_to_wishlist';

    constructor(data) {
        super(AddToWishlistEvent.EVENT_NAME, data);
    }
}

export class RemoveFromWishlistEvent extends EcommerceEvent {
    static EVENT_NAME = 'remove_from_wishlist';

    constructor(data) {
        super(RemoveFromWishlistEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Marketing Events
|--------------------------------------------------------------------------
*/

export class NewsletterSignupEvent extends EcommerceEvent {
    static EVENT_NAME = 'newsletter_signup';

    constructor(data) {
        super(NewsletterSignupEvent.EVENT_NAME, data);
    }
}

export class CouponAppliedEvent extends EcommerceEvent {
    static EVENT_NAME = 'coupon_applied';

    constructor(data) {
        super(CouponAppliedEvent.EVENT_NAME, data);
    }
}

export class CouponRemovedEvent extends EcommerceEvent {
    static EVENT_NAME = 'coupon_removed';

    constructor(data) {
        super(CouponRemovedEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Review Events
|--------------------------------------------------------------------------
*/

export class ProductReviewSubmittedEvent extends EcommerceEvent {
    static EVENT_NAME = 'product_review_submitted';

    constructor(data) {
        super(ProductReviewSubmittedEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Customer Address Events
|--------------------------------------------------------------------------
*/

export class AddressValidatedEvent extends EcommerceEvent {
    static EVENT_NAME = 'address_validated';

    constructor(data) {
        super(AddressValidatedEvent.EVENT_NAME, data);
    }
}

/*
|--------------------------------------------------------------------------
| Order Events
|--------------------------------------------------------------------------
*/

export class OrderCancelledEvent extends EcommerceEvent {
    static EVENT_NAME = 'order_cancelled';

    constructor(data) {
        super(OrderCancelledEvent.EVENT_NAME, data);
    }
}

export class OrderExportedEvent extends EcommerceEvent {
    static EVENT_NAME = 'order_exported';

    constructor(data) {
        super(OrderExportedEvent.EVENT_NAME, data);
    }
}