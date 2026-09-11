import {defineStore} from 'pinia';
import getters from './store/getters.js';
import actions from './store/actions.js';


export const useCheckoutStore = defineStore('checkout', {
    state: () => {
        return {
            staticMode: true,
            steps: [],
            activeStep: '',
            cartId: null,
            cart: {},
            customer: {},
            contact: {},
            shipping: {
                ShipToNumber: ''
            },
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
            guestCheckout: false,
            editable: false,
            allowCreateShipping: false,
            allowChooseShipping: false,
            allowRequestQuote : false,
            allowCreateOrderList : false,
            backUrl : window.location.origin,
        }
    },
    getters,
    actions,
});
