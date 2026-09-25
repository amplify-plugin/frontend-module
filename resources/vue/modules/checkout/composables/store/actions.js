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

import {useValidate} from "@/composables/useValidate";
import axios from 'axios';
import Swal from "sweetalert2";
import {error} from "../../../../../../../../public/packages/places.js/dist/cdn/places";

const validator = useValidate();

export default {
    init(props) {
        this.staticMode = props.cart == null;
        this.steps = props.steps ?? mockSteps;
        // Canonical order always starts at the first step; the incoming
        // `active` flags are inconsistent (multiple steps marked active).
        this.activeStep = this.orderedSteps[0]?.component ?? 'account';
        this.cartId = props.cart ?? null;
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
        this.shipOptions = {};
        this.guestCheckout = props.guestCheckout ?? false;
        this.editable = props.editable ?? false;
        this.allowCreateShipping = props.allowCreateShipping ?? false;
        this.allowChooseShipping = props.allowChooseShipping ?? false;
        this.allowRequestQuote = props.allowRequestQuote ?? false;
        this.allowCreateOrderList = props.createFavouriteFromCart ?? false;
        this.hasShipInstruction = props.hasShipInstruction ?? false;
        this.orderListTitle = props.orderListTitle ?? 'Order List';
        this.backUrl = props.backToShoppingUrl ?? null;
        this.verifyPoNumber = props.verifyPoNumber ?? false;
        this.brandColor = props.templateBrandColor ?? '#0da9ef';

        this.fillAccountData();

        this.selectAddressSelected(this.customer.DefaultShipTo);
    },

    fillAccountData(data = {}) {

        data = JSON.parse(JSON.stringify(data));

        this.account.name = this.contact.name ?? '';
        this.account.email = this.contact.email ?? '';
        this.account.phone = this.contact.phone ?? '';
        this.account.company = this.customer.CustomerName ?? '';
        this.account.addressLine1 = this.customer.CustomerAddress1 ?? '';
        this.account.addressLine2 = this.customer.CustomerAddress2 ?? '';
        this.account.addressLine3 = this.customer.CustomerAddress3 ?? '';
        this.account.city = this.customer.CustomerCity ?? '';
        this.account.state = this.customer.CustomerState ?? '';
        this.account.country = this.customer.CustomerCountry ?? '';
        this.account.zipCode = this.customer.CustomerZipCode ?? '';

        //@TODO Dynamic entries

        this.account.errors = validator.make();
    },

    fillShippingData(data = {}) {

        data = JSON.parse(JSON.stringify(data));

        this.shipping.name = data.ShipToName ?? '';
        this.shipping.number = data.ShipToNumber ?? '';
        this.shipping.addressLine1 = data.ShipToAddress1 ?? '';
        this.shipping.addressLine2 = data.ShipToAddress2 ?? '';
        this.shipping.addressLine3 = data.ShipToAddress3 ?? '';
        this.shipping.country = data.ShipToCountryCode ?? '';
        this.shipping.state = data.ShipToState ?? '';
        this.shipping.city = data.ShipToCity ?? '';
        this.shipping.zipCode = data.ShipToZipCode ?? '';
        this.shipping.method = data.CarrierCode ?? this.customer?.CarrierCode ?? '';
        this.shipping.contact = data.ShipToContact ?? this.account.name ?? '';
        this.shipping.phone = data.ShipToPhoneNumber ?? this.account.phone ?? '';

        //@TODO Dynamic entries

        this.shipping.errors = validator.make();

        if (data?.ShipToNumber) {
            this.fetchShippingOptions();
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
            this.validationError = '';
            this.submitRequest('order');
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
            case 'account':

                this.account.errors = validator.make(
                    this.account, {
                        name: ['required', 'min:2', 'max:255'],
                        email: ['required', 'min:5', 'max:255', 'email'],
                        phone: ['required', 'min:10', 'max:17'],
                        company: ['required', 'min:2', 'max:255'],
                        addressLine1: ['required', 'max:255'],
                        addressLine2: ['nullable', 'max:255'],
                        addressLine3: ['nullable', 'max:255'],
                        country: ['required', 'max:255'],
                        state: ['required', 'max:255'],
                        city: ['required', 'max:255'],
                        zipCode: ['required'],
                        poNumber: [this.customer?.PoRequired === 'Y' ? 'required' : 'nullable'],
                    }, {},
                    {
                        addressLine1: 'Address Line 1',
                        addressLine2: 'Address Line 2',
                        addressLine3: 'Address Line 3',
                        zipCode: 'ZIP Code',
                        poNumber: 'PO Number',
                    });

                if (this.account.errors.failed()) {
                    this.validationError =
                        this.account.errors.errors().length > 1
                            ? 'The given data is invalid.'
                            : this.account.errors.message;

                    return false;
                }

                if (this.verifyPoNumber && this.account.poNumber !== '') {
                    return this.validatePurchaseNumber();
                }

                return true;

            case 'shipping':
                this.shipping.errors = validator.make(
                    this.shipping, {
                        name: ['required', 'max:255'],
                        number: ['required', 'max:255'],
                        addressLine1: ['required', 'max:255'],
                        addressLine2: ['nullable', 'max:255'],
                        addressLine3: ['nullable', 'max:255'],
                        country: ['required', 'max:255'],
                        state: ['required', 'max:255'],
                        city: ['required', 'max:255'],
                        zipCode: ['required'],
                        method: ['required'],
                        freightAccountNumber: ['nullable', 'max:255'],
                        contact: ['required', 'max:255'],
                        phone: ['required', 'min:10', 'max:17'],
                        instructions: ['nullable', 'max:255'],
                    }, {},
                    {
                        addressLine1: 'Address Line 1',
                        addressLine2: 'Address Line 2',
                        addressLine3: 'Address Line 3',
                        zipCode: 'ZIP Code',
                        method: 'Delivery Method',
                        instructions: 'Shipping Instructions',
                        freightAccountNumber: 'Freight Account Number',
                    });

                if (this.shipping.errors.failed()) {
                    this.validationError =
                        this.shipping.errors.errors().length > 1
                            ? 'The given data is invalid.'
                            : this.shipping.errors.message;

                    return false;
                }

                return this.shipping.errors.passed();
            //
            // if (!this.selectedShippingMethod) {
            //     this.validationError = 'Please, Select a Shipping Method!';
            //     return false;
            // }
            // if (
            //     this.selectedShippingMethod.frttermscd === 'C' &&
            //     !this.freightAccountNumber
            // ) {
            //     this.validationError = 'Enter your freight account number.';
            //     return false;
            // }
            // return true;

            case 'review':
                // The reference review page has no PO field; PO/notes stay
                // in state for future backend use.
                return true;

            default:
                return true;
        }
    },

    flatShipOptions(methods) {
        return methods.map(item => {
            const [name, details] = Object.entries(item)[0];
            return {
                name,
                ...details
            };
        });
    },

    fetchShippingOptions() {

        let payload = {
            shipping_method: this.shipping.method,
            shipping_name: this.shipping.name,
            customer_order_ref: this.account.poNumber,
            ship_to_number: this.shipping.number,
            customer_address_one: this.shipping.addressLine1,
            customer_address_two: this.shipping.addressLine2,
            customer_address_three: this.shipping.addressLine3,
            customer_city: this.shipping.city,
            customer_country_code: this.shipping.country,
            customer_state: this.shipping.state,
            customer_zipcode: this.shipping.zipCode,
            customer_phone: this.shipping.phone,
        };

        window.Amplify.confirm('Retrieving Shipping Options...', 'Checkout', '', {
            allowEscapeKey: false,
            showCancelButton: false,
            showCloseButton: false,
            backdrop: true,
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => window.swal.clickConfirm(),
            allowOutsideClick: () => !window.swal.isLoading(),
            preConfirm: async () => {
                try {
                    const response = await axios.post(
                        '/get/shipping/option',
                        payload,
                        {
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            }
                        }
                    );

                    return {
                        success: true,
                        data: response.data,
                        error: null
                    };

                } catch (error) {

                    return {
                        success: false,
                        data: error.response.data,
                        error: error.response?.data?.message ?? error.message
                    };
                }
            },
        })
            .then((result) => {

                if (!result.value.success) {
                    window.Amplify.alert(result.value.error, 'Checkout', {icon: 'error'});
                    return;
                }
                let response = result.value.data;
                let shipOptions = response.FreightRate ?? {};

                this.review.ship_charge = response.FreightAmount ?? null;
                this.review.hazmat_charge = response.HazMatCharge ?? null;
                this.review.tax_amount = response.SalesTaxAmount ?? null;
                this.review.sub_total = response.TotalLineAmount ?? null;
                this.review.total = response.TotalOrderValue ?? null;
                this.review.wire_transfer_fee = response.WireTrasnsferFee ?? null;
                this.review.errors = validator.make();

                for (const [name, methods] of Object.entries(shipOptions)) {
                    this.shipOptions[name] = this.flatShipOptions(methods);
                }

                return result.value.success;
            });
    },

    async validatePurchaseNumber() {
        return await window.Amplify.confirm('Validating Purchase Order Number', 'Checkout', '', {
            allowEscapeKey: false,
            showCancelButton: false,
            showCloseButton: false,
            backdrop: true,
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => window.swal.clickConfirm(),
            allowOutsideClick: () => !window.swal.isLoading(),
            preConfirm: async () => {
                try {
                    const response = await axios.post(
                        '/validate/po-number',
                        {po_number: this.account.poNumber,}, {
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',

                            }
                        }
                    );

                    return {
                        success: true,
                        data: response.data,
                        error: null
                    };

                } catch (error) {
                    return {
                        success: false,
                        data: error.response.data,
                        error: error.response?.data?.message ?? error.message
                    };
                }
            }
        }).then((result) => {

            if (!result.value.success) {
                window.Amplify.alert(result.value.error, 'Checkout', {icon: 'error'});
                return;
            }

            return result.value.success;
        });
    },

    selectAddressSelected(shipToNumber) {

        let addressFound = false

        for (const address of this.addresses) {
            if (address.ShipToNumber === shipToNumber) {
                this.fillShippingData(address);
                addressFound = true;
                break;
            }
        }

        if (addressFound === false) {
            this.fillShippingData();
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
     * Submit Request To Server
     *
     * 1. Draft Order
     * 2. Quote
     * 3. Order
     * @param type
     */
    submitRequest(type = 'order') {
        const messages = {
            order: 'Order Processing...',
            quotation: 'Request For Quote Processing...',
            draft: 'Draft Order Processing...',
        };

        const urls = {
            order: '/carts/submit-order',
            quotation: '/carts/submit-quote',
            draft: '/drafts',
        };

        let payload = {};

        window.Amplify.confirm(messages[type], 'Checkout', '', {
            icon: 'info',
            showConfirmButton: false,
            allowEscapeKey: false,
            showCancelButton: false,
            showCloseButton: false,
            backdrop: true,
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => window.swal.clickConfirm(),
            allowOutsideClick: () => !window.swal.isLoading(),
            preConfirm: async () => {
                try {

                    const response = await axios
                        .post(urls[type], payload);

                    return {
                        success: true,
                        data: response.data,
                        error: null
                    };
                } catch (e) {
                    return {
                        success: false,
                        data: error.response.data,
                        error: error.response?.data?.message ?? error.message
                    }
                }
            }
        }).then((result) => {

            if (!result.value.success) {
                window.Amplify.alert(result.value.error, 'Checkout', {icon: 'error'});
                return;
            }

            let response = result.value.data;

            window.Amplify.confirm(response.message, 'Checkout', 'Continue Shopping', {
                icon: 'success',
                cancelButtonText: 'Review ' + this.capitalizeFirstLetter(type),
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.replace(this.backUrl);
                    return;
                }

                if (result.isDismissed) {
                    window.location.href = (result.dismiss === 'cancel')
                        ? response.redirect_to
                        : this.backUrl;
                }
            });
        });
    },

    priceFormatter(price) {
        const value = parseFloat(String(price ?? '').replace(/[^0-9.\-]/g, ''));
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: window.Amplify?.config?.currency ?? 'USD',
        }).format(Number.isFinite(value) ? value : 0);
    },

    capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1)
    },
}