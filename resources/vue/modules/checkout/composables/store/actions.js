import {mockContact, mockCountries, mockCustomer, mockStates,} from '../../mock';

import {useValidate} from "@/composables/useValidate";
import axios from 'axios';

const validator = useValidate();

export default {
    init(props) {
        this.steps = props.steps ?? [];
        // Canonical order always starts at the first step; the incoming
        // `active` flags are inconsistent (multiple steps marked active).
        this.activeStep = this.orderedSteps[0]?.component ?? 'account';
        this.cartId = props.cart ?? null;
        this.customer = props.customer ?? mockCustomer;
        this.contact = props.contact ?? mockContact;
        // Blade always passes these props (possibly as empty ERP results),
        // so fall back to fixtures on null AND empty — not just nullish.
        this.addresses = (Array.isArray(props.addresses) && props.addresses.length > 0)
            ? props.addresses : [];
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
        this.allowChooseBilling = props.allowChooseBilling ?? false;
        this.hasShipInstruction = props.hasShipInstruction ?? false;
        this.orderListTitle = props.orderListTitle ?? 'Order List';
        this.backUrl = props.backToShoppingUrl ?? null;
        this.verifyPoNumber = props.verifyPoNumber ?? false;
        this.brandColor = props.templateBrandColor ?? '#0da9ef';
        this.shipTo = props.shipTo ?? this.customer.DefaultShipTo;
        this.paymentTerms = props.paymentTerms ?? {
            TermsType: null
        };
        this.fillAccountData();

        this.selectAddressSelected(this.shipTo);
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

        // Payment Gateway Information
        this.payment.biller = this.account.company;
        this.payment.address = `${this.account.addressLine1} ${this.account.addressLine2} ${this.account.addressLine3}`.toString().trim();
        this.payment.city = this.account.city;
        this.payment.state = this.account.state;
        this.payment.zipCode = this.account.zipCode;
        this.payment.country = this.account.country;
        this.payment.phone = this.account.phone;
        this.payment.errors = validator.make();

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
                        addressLine1: 'address line 1',
                        addressLine2: 'address line 2',
                        addressLine3: 'address line 3',
                        zipCode: 'zip code',
                        poNumber: 'po number',
                    });

                if (this.account.errors.failed()) {
                    this.validationError = 'The given data is invalid.';
                    return false;
                }

                if (this.verifyPoNumber && this.account.poNumber !== '') {
                    return this.validatePurchaseNumber();
                }

                return true;

            case 'shipping':
                return this.validateShippingAddress();

            case 'payment':
                if(Object.entries(this.payment.credentials).length === 0);

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

        return window.Amplify.confirm('Retrieving Shipping Options...', 'Checkout', '', {
            icon: 'info',
            allowEscapeKey: false,
            showCancelButton: false,
            showCloseButton: false,
            backdrop: true,
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => window.swal.clickConfirm(),
            allowOutsideClick: () => !window.swal.isLoading(),
            preConfirm: async () => {
                try {

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

    validateShippingAddress() {
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
                contact: ['nullable', 'max:255'],
                phone: ['nullable', 'min:10', 'max:17'],
                instructions: ['nullable', 'max:255'],
            }, {},
            {
                addressLine1: 'address line 1',
                addressLine2: 'address line 2',
                addressLine3: 'address line 3',
                zipCode: 'zip code',
                method: 'delivery method',
                number: this.newShipping ? 'code' : 'address',
                instructions: 'shipping instructions',
                freightAccountNumber: 'freight account number',
            });

        if (this.shipping.errors.failed()) {
            this.validationError = 'The given data is invalid.';
            return false;
        }

        return this.shipping.errors.passed();
    },

    async saveNewShippingAddress() {

        if (!this.validateShippingAddress()) {
            return false;
        }

        return await window.Amplify.confirm('Creating Shipping Address...', 'Checkout', '', {
            icon: 'info',
            allowEscapeKey: false,
            showCancelButton: false,
            showCloseButton: false,
            backdrop: true,
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => window.swal.clickConfirm(),
            allowOutsideClick: () => !window.swal.isLoading(),
            preConfirm: async () => {
                try {

                    let payload = {
                        address_code: this.shipping.number,
                        address_name: this.shipping.name,
                        address_1: this.shipping.addressLine1,
                        address_2: this.shipping.addressLine2,
                        address_3: this.shipping.addressLine3,
                        state: this.shipping.state,
                        city: this.shipping.city,
                        zip_code: this.shipping.zipCode,
                        country_code: this.shipping.country,
                        phone: this.shipping.phone,
                    };

                    const response = await axios.post(
                        '/addresses',
                        payload, {
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',

                            }
                        }
                    );

                    return {
                        success: true,
                        code: response.status,
                        data: response.data,
                        error: null
                    };

                } catch (error) {
                    return {
                        success: false,
                        code: error?.response.status ?? 500,
                        data: error?.response.data ?? {},
                        error: error?.response?.data?.message ?? error.message
                    };
                }
            }
        }).then((result) => {

            if (!result.value.success) {
                window.Amplify.alert(result.value.error, 'Checkout', {icon: 'error'});
                return;
            }

            let newEntry = result.value?.data?.erp ?? null;

            if (newEntry) {

                this.addresses.push(newEntry);

                window.Amplify.notify('success', result.value.data.message, 'Checkout');

                this.newShipping = false;

                this.selectAddressSelected(newEntry.ShipToNumber);
            }

            return result.value.success;
        });
    },

    selectAddressSelected(shipToNumber) {

        this.validationError = '';

        let addressFound = false;

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
        this.selectedShippingMethod = method;
        this.validationError = '';
    },

    selectPaymentMethod(method) {
        this.payment.method = method;
    },

    fillPaymentGateway(data) {
        this.payment.driver = data.driver ?? 'default';
        this.payment.config = data.config ?? {};
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

    async initPaymentGateway() {
        if (!this.validateShippingAddress()) {
            return false;
        }

        return await window.Amplify.confirm('Loading Payment Gateway...', 'Checkout', '', {
            icon: 'info',
            allowEscapeKey: false,
            showCancelButton: false,
            showCloseButton: false,
            backdrop: true,
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => window.swal.clickConfirm(),
            allowOutsideClick: () => !window.swal.isLoading(),
            preConfirm: async () => {
                try {
                    const response = await axios.get('/api/payment/initialize')

                    return {
                        success: true,
                        data: response.data.data,
                        error: null
                    };

                } catch (error) {
                    return {
                        success: false,
                        data: '',
                        error: error?.response?.data?.message ?? error.message
                    };
                }
            }
        }).then(async (result) => {

            if (!result.value.success) {
                window.Amplify.alert(result.value.error, 'Checkout', {icon: 'error'});
                return;
            }

            let gateway = result.value?.data ?? null;

            if (gateway) {
                let configuration = await this.decryptPaymentConfig(gateway);

                this.fillPaymentGateway(configuration);
            }

            return result.value.success;
        });


    },

    base64UrlDecode(value) {
        value += '='.repeat((4 - value.length % 4) % 4);

        const binary = atob(
            value
                .replace(/-/g, '+')
                .replace(/_/g, '/')
        );

        return Uint8Array.from(
            binary,
            char => char.charCodeAt(0)
        );
    },

    async deriveKey(secret) {
        const hash = await crypto.subtle.digest(
            'SHA-256',
            new TextEncoder().encode(secret)
        );

        return crypto.subtle.importKey(
            'raw',
            hash,
            {
                name: 'AES-GCM'
            },
            false,
            ['decrypt']
        );
    },

    async decryptPaymentConfig(token) {

        let secret = window.Amplify.clientCode();

        const parts = token.split('.');

        if (parts.length !== 4 || parts[0] !== 'pg') {
            throw new Error('Invalid payment configuration.');
        }

        const [, ivPart, ciphertextPart, tagPart] = parts;

        const iv = this.base64UrlDecode(ivPart);
        const ciphertext = this.base64UrlDecode(ciphertextPart);
        const tag = this.base64UrlDecode(tagPart);

        const key = await this.deriveKey(secret);

        const encryptedData = new Uint8Array(
            ciphertext.length + tag.length
        );

        encryptedData.set(ciphertext);
        encryptedData.set(tag, ciphertext.length);

        const decrypted = await crypto.subtle.decrypt(
            {
                name: 'AES-GCM',
                iv,
                tagLength: 128
            },
            key,
            encryptedData
        );

        const json = new TextDecoder().decode(decrypted);

        return JSON.parse(json);
    },

    /**
     * Submit Request To Server
     *
     * 1. Draft Order
     * 2. Quote
     * 3. Order
     * @param type
     */
    async submitRequest(type = 'order') {

        this.validationError = '';

        const messages = {
            order: 'Order Processing...',
            quotation: 'Request For Quote Processing...',
            draft: 'Draft Order Processing...',
        };

        const urls = {
            order: '/checkout',
            quotation: '/carts/submit-quote',
            draft: '/drafts',
        };

        return window.Amplify.confirm(messages[type], 'Checkout', '', {
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
                        .post(urls[type], this.getCheckoutRequestPayload(type));

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

    getCheckoutRequestPayload(type = 'order', data = {}) {
        return {
            amounts: {
                subtotal: this.review.sub_total,
                shipping: this.review.ship_charge,
                tax: this.review.tax_amount,
                total: this.review.tax_amount,
                additional: [
                    {
                        field: 'hazmat_charge',
                        label: 'Hazmat Charge',
                        value: this.review.hazmat_charge,
                        source: 'erp',
                        metadata: {}
                    },
                    {
                        field: 'wire_transfer_fee',
                        label: 'Wire Transfer Fee',
                        value: this.review.wire_transfer_fee,
                        source: 'erp',
                        metadata: {}
                    }
                ]
            },
            checkout: {
                version: 1,
                channel: 'web',
                type: type,
            },
            contact: {
                id: null, //will be overwritten on server
                name: this.account.name,
                email: this.account.email,
                phone: this.account.phone
            },
            customer: {
                id: null, //will be overwritten on server
                number: this.customer.CustomerNumber ?? null,
                name: this.account.company,
                address1: this.account.addressLine1,
                address2: this.account.addressLine2,
                address3: this.account.addressLine3,
                city: this.account.city,
                state: this.account.state,
                zip_code: this.account.zipCode,
                country: this.account.country
            },
            shipping: {
                id: null, //will be overwritten on server
                name: this.shipping.name,
                number: this.shipping.number,
                address1: this.shipping.addressLine1,
                address2: this.shipping.addressLine2,
                address3: this.shipping.addressLine3,
                city: this.shipping.city,
                state: this.shipping.state,
                zip_code: this.shipping.zipCode,
                country: this.shipping.country,
                method: {
                    code: this.selectedShippingMethod.shipvia ?? null,
                    label: this.selectedShippingMethod.name ?? null,
                    amount: this.selectedShippingMethod.amount ?? null,
                },
                instructions: this.shipping.instructions ?? null,
                additional: [
                    // {
                    //     field: '',
                    //     label: '',
                    //     value: '',
                    //     source: '',
                    //     metadata: {}
                    // }
                ]
            },
            items: [],
            payment: {
                gateway: this.payment.driver,
                method: this.payment.method,
                credentials: JSON.parse(JSON.stringify(this.payment.credentials)),
                name: this.payment.biller,
                address: this.payment.address,
                city: this.payment.city,
                state: this.payment.state,
                zip_code: this.payment.zipCode,
                country: this.payment.country,
                phone: this.payment.phone,
                metadata: {}
            }
        };
    }
}