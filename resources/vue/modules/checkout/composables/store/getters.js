export default {
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

    isEditable(state) {
        if (state.isGuestCheckout) {
            return true;
        }

        return false;
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
}