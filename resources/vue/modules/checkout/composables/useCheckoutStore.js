import {defineStore} from 'pinia';
import getters from './store/getters.js';
import actions from './store/actions.js';
import {useValidate} from "@/composables/useValidate";


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
            addresses: [],
            countries: [],
            states: [],
            shipOptions: {},
            paymentMethod: 'on_account',
            creditCardToken: '',
            orderNotes: '',
            internalNotes: '',
            validationError: '',
            verifyPoNumber: false,
            guestCheckout: false,
            editable: false,
            allowCreateShipping: false,
            allowChooseShipping: false,
            allowRequestQuote: false,
            allowCreateOrderList: false,
            backUrl: window.location.origin,
            orderListTitle: 'Order List',
            purchaseOrder: null, //if object already verified
            brandColor: '#0da9ef',

            //Account Step
            account: {
                name: '',
                email: '',
                phone: '',
                poNumber: '',
                company: '',
                addressLine1: '',
                addressLine2: '',
                addressLine3: '',
                city: '',
                state: '',
                country: '',
                zipCode: ''
            },

            //Shipping Step
            shipping: {
                name: '',
                number: '',
                addressLine1: '',
                addressLine2: '',
                addressLine3: '',
                country: '',
                state: '',
                city: '',
                zipCode: '',
                method: '',
                freightAccountNumber: '',
                contact: '',
                phone: '',
                instructions: '',
            },

            review: {
                sub_total: null,
                tax_amount: null,
                ship_charge: null,
                hazmat_charge : null,
                wire_transfer_fee : null,
                total: null,
                lines: [],
                notes: '',
                coupon: '',
            }

        }
    },
    getters,
    actions,
});
