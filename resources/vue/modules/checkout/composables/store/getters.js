export default {
    // Canonical checkout step sequence. Incoming blade data may be reversed
    // or carry duplicate/contradictory indexes — ordering is enforced here.
    orderedSteps() {

        let steps = this.steps ?? [];

        const canonical = steps.map((step, i) => step.component);

        const seen = new Set();
        return [...steps]
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

    cartItems(state) {
        return state.cart?.products ?? state.cart?.items ?? [];
    },


    paymentMethod() {
        let labels = {
            credit_card: 'Credit Card',
            on_account: 'Standard Account',
        }

        return labels[this.payment.method] ?? null;
    }
}