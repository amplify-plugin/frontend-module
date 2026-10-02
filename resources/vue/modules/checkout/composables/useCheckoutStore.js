import {defineStore} from 'pinia';
import getters from './store/getters.js';
import actions from './store/actions.js';


export const useCheckoutStore = defineStore('checkout', {
    state: () => {
        return {
            loading: false,
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
            creditCardToken: '',
            validationError: '',
            verifyPoNumber: false,
            guestCheckout: false,
            editable: false,
            allowCreateShipping: false,
            allowChooseShipping: false,
            allowChooseBilling: false,
            allowRequestQuote: false,
            allowCreateOrderList: false,
            hasShipInstruction: false,
            backUrl: window.location.origin,
            orderListTitle: 'Order List',
            purchaseOrder: null, //if object already verified
            brandColor: '#0da9ef',
            paymentAddressChanged: false,

            paymentTerms: {
                TermsType: null
            },

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
            newShipping : false,

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

            //Review  Step
            review: {
                sub_total: null,
                tax_amount: null,
                ship_charge: null,
                hazmat_charge: null,
                wire_transfer_fee: null,
                total: null,
                lines: [],
                notes: '',
                coupon: '',
            },

            //Payment Step
            payment : {
                biller : '',
                address : '',
                city : '',
                state : '',
                zipCode : '',
                country : '',
                method: 'on_account',
                driver: 'default',
                config: {},
                credentials: {}
            }

        }
    },
    getters,
    actions,
});
