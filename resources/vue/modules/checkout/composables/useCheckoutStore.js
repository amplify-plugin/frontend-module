import { defineStore } from 'pinia';
import {
    mockAddresses,
    mockCart,
    mockContact,
    mockCountries,
    mockCustomer,
    mockShipOptions,
    mockStates,
    mockSteps,
} from '../mock';

export const useCheckoutStore = defineStore('checkout', {
    state: () => ({
        staticMode: true,
        steps: [],
        activeStep: '',
        cart: null,
        customer: null,
        contact: null,
        addresses: [],
        countries: [],
        states: [],
        shipOptions: {},
        selectedShippingMethod: null,
        shippingGroup: '',
        freightAccountNumber: '',
        paymentMethod: 'on_account',
        creditCardToken: '',
        poNumber: '',
        orderNotes: '',
        internalNotes: '',
        validationError: '',
    }),

    getters: {
        // Canonical checkout step sequence. Incoming blade data may be reversed
        // or carry duplicate/contradictory indexes — ordering is enforced here.
        orderedSteps(state) {
            const canonical = ['account', 'shipping', 'review', 'payment'];
            const seen = new Set();
            return [...state.steps]
                .sort((a, b) => {
                    const posA = canonical.indexOf(a.component);
                    const posB = canonical.indexOf(b.component);
                    if (posA !== -1 && posB !== -1) return (posA - posB) || (a.index - b.index);
                    if (posA !== -1) return -1;
                    if (posB !== -1) return 1;
                    return a.index - b.index;
                })
                .filter((step) => {
                    if (seen.has(step.component)) return false;
                    seen.add(step.component);
                    return true;
                });
        },

        currentIndex() {
            return this.orderedSteps.findIndex((step) => step.component === this.activeStep);
        },

        currentStep() {
            return this.orderedSteps[this.currentIndex] ?? null;
        },

        isFirstStep() {
            return this.currentIndex <= 0;
        },

        isLastStep() {
            return this.currentIndex === this.orderedSteps.length - 1;
        },

        shippingGroups(state) {
            return Object.keys(state.shipOptions?.FreightRate ?? {});
        },

        cartItems(state) {
            return state.cart?.products ?? state.cart?.items ?? [];
        },

        orderSubtotal(state) {
            const toNumber = (value) => {
                const parsed = parseFloat(String(value ?? '').replace(/[^0-9.\-]/g, ''));
                return Number.isFinite(parsed) ? parsed : 0;
            };

            return toNumber(
                state.cart?.total_price
                ?? state.cart?.sub_total
                ?? state.cart?.total
                ?? state.cart?.total_amount
            ) || this.cartItems.reduce(
                (sum, item) => sum + toNumber(item.subtotal ?? (item.unit_price ?? item.price ?? 0) * item.qty),
                0
            );
        },

        salesTax(state) {
            const toNumber = (value) => {
                const parsed = parseFloat(String(value ?? '').replace(/[^0-9.\-]/g, ''));
                return Number.isFinite(parsed) ? parsed : 0;
            };
            // Prefer the cart's own tax (CartResource) over the ERP quote value.
            return toNumber(state.cart?.tax_amount)
                || toNumber(this.shipOptions?.SalesTaxAmount);
        },

        wireTransferFee() {
            return Number(this.shipOptions?.WireTransferFee ?? this.shipOptions?.WireTrasnsferFee ?? 0);
        },

        shippingAmount() {
            const parsed = parseFloat(String(this.selectedShippingMethod?.amount ?? '').replace(/[^0-9.\-]/g, ''));
            return Number.isFinite(parsed) ? parsed : 0;
        },

        orderTotal(state) {
            const toNumber = (value) => {
                const parsed = parseFloat(String(value ?? '').replace(/[^0-9.\-]/g, ''));
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const subtotal = this.orderSubtotal;
            const shipping = this.shippingAmount;
            const tax = this.salesTax;

            if (subtotal === 0 && shipping === 0 && tax === 0) {
                return toNumber(state.cart?.total_amount)
                    ?? toNumber(state.cart?.total)
                    ?? toNumber(state.shipOptions?.TotalOrderValue);
            }

            return subtotal + shipping + tax;
        },
    },

    actions: {
        initFromProps(props) {
            this.staticMode = props.cart == null;
            this.steps = props.steps ?? mockSteps;
            // Canonical order always starts at the first step; the incoming
            // `active` flags are inconsistent (multiple steps marked active).
            this.activeStep = this.orderedSteps[0]?.component ?? 'account';
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

            if (this.shippingGroups.length > 0) {
                this.shippingGroup = this.shippingGroups[0];
            }
        },

        goBack() {
            if (this.isFirstStep) return;
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
                    this.cart = { ...this.cart, ...cart };
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
    },
});
