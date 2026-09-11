import {
    mockAddresses,
    mockCart,
    mockContact,
    mockCountries,
    mockCustomer,
    mockShipOptions,
    mockStates,
    mockSteps,
} from '../../mock';
export default {
    init(props) {
        this.staticMode = props.cart == null;
        this.steps = props.steps ?? mockSteps;
        // Canonical order always starts at the first step; the incoming
        // `active` flags are inconsistent (multiple steps marked active).
        this.activeStep = this.orderedSteps[0]?.component ?? 'account';
        this.cartId = props.cart ?? null;
        this.cart = props.cart ?? mockCart;
        this.customer = props.customer ?? mockCustomer;
        this.contact = props.contact ?? mockContact;
        // Blade always passes these props (possibly as empty ERP results),
        // so fall back to fixtures on null AND empty — not just nullish.
        this.addresses = (Array.isArray(props.addresses) && props.addresses.length > 0)
            ? props.addresses : mockAddresses;
        this.countries = (Array.isArray(props.countries) && props.countries.length > 0)
            ? props.countries : mockCountries;
        this.states = (Array.isArray(props.states) && props.states.length > 0)
            ? props.states : mockStates;
        const freightRate = props.shipOptions?.FreightRate ?? null;
        this.shipOptions = (freightRate && Object.keys(freightRate).length > 0)
            ? props.shipOptions : mockShipOptions;
        this.guestCheckout = props.guestCheckout ?? false;
        this.editable = props.editable ?? false;
        this.allowCreateShipping = props.allowCreateShipping ?? false;
        this.allowChooseShipping = props.allowChooseShipping ?? false;
        this.allowRequestQuote = props.allowRequestQuote ?? false;
        this.allowCreateOrderList = props.createFavouriteFromCart ?? false;
        this.backUrl = props.backToShoppingUrl ?? null;

        if (this.shippingGroups.length > 0) {
            this.shippingGroup = this.shippingGroups[0];
        }
    },

    goBack() {

        if (this.isFirstStep) {
            window.location.href = this.backUrl;
            return;
        }

        this.validationError = '';
        this.activeStep = this.orderedSteps[this.currentIndex - 1].component;
    },

    goNext() {
        if (!this.validateCurrentStep()) return;
        if (this.isLastStep) {
            // Static mode: no order submission.
            this.validationError = '';
            this.notifyStaticSubmit();
            return;
        }
        this.validationError = '';
        this.activeStep = this.orderedSteps[this.currentIndex + 1].component;
    },

    /**
     * Header click navigation rules:
     * - current step: no-op
     * - previous step: allowed
     * - immediately next step: allowed only if the current step validates
     * - further ahead: blocked (no skipping intermediate validation)
     */
    goToStep(component) {
        const targetIndex = this.orderedSteps.findIndex((step) => step.component === component);
        if (targetIndex === -1 || targetIndex === this.currentIndex) return;

        if (targetIndex < this.currentIndex) {
            this.validationError = '';
            this.activeStep = component;
            return;
        }

        if (!this.validateCurrentStep()) return;

        if (targetIndex > this.currentIndex + 1) {
            this.validationError = 'Please complete the previous steps before jumping ahead.';
            return;
        }

        this.validationError = '';
        this.activeStep = component;
    },

    validateCurrentStep() {
        switch (this.activeStep) {
            case 'shipping':
                if (!this.selectedShippingMethod) {
                    this.validationError = 'Please, Select a Shipping Method!';
                    return false;
                }
                if (
                    this.selectedShippingMethod.frttermscd === 'C' &&
                    !this.freightAccountNumber
                ) {
                    this.validationError = 'Enter your freight account number.';
                    return false;
                }
                return true;
            case 'review':
                // The reference review page has no PO field; PO/notes stay
                // in state for future backend use.
                return true;
            default:
                return true;
        }
    },

    selectShippingMethod(group, method) {
        this.shippingGroup = group;
        this.selectedShippingMethod = method;
        this.validationError = '';
    },

    selectPaymentMethod(method) {
        this.paymentMethod = method;
        this.validationError = '';
    },

    /**
     * The `cart` prop is a Laravel Cart model JSON without item rows.
     * Load them from the existing /carts/show endpoint (CartResource:
     * products with product_name/qty/price/subtotal + formatted totals).
     */
    async loadCartItems() {
        if (this.cartItems.length > 0) return;
        if (this.staticMode) return;
        try {
            const res = await fetch('/carts/show', {
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            }).then((res) => res.json());
            const cart = res?.data ?? res;
            if (cart && typeof cart === 'object') {
                this.cart = {...this.cart, ...cart};
            }
        } catch {
            // keep existing cart state on failure
        }
    },

    notifyStaticSubmit() {
        if (typeof window !== 'undefined' && typeof window.ShowNotification === 'function') {
            window.ShowNotification('info', 'Order', 'Static checkout preview — order submission is disabled.');
        } else {
            alert('Static checkout preview — order submission is disabled.');
        }
    },

    priceFormatter(price) {
        const value = parseFloat(String(price ?? '').replace(/[^0-9.\-]/g, ''));
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(Number.isFinite(value) ? value : 0);
    },
}