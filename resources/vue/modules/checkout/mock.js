/**
 * Static-only mock data for the checkout module.
 * Used when the blade-provided props are absent (static frontend preview).
 */


export const mockCart = {
    total_price: 312.5,
    items: [
        { id: 1, product_code: 'DEMO-001', name: 'Demo Product A', qty: 2, unit_price: 75.0, subtotal: 150.0 },
        { id: 2, product_code: 'DEMO-002', name: 'Demo Product B', qty: 1, unit_price: 162.5, subtotal: 162.5 },
    ],
};

export const mockCustomer = {
    CustomerName: 'Static Demo Customer',
    CustomerNumber: '100000',
    CustomerCountry: 'US',
    CustomerState: 'CA',
    CustomerAddress1: '100 Main St',
    CustomerAddress2: '',
    CustomerAddress3: '',
    CustomerCity: 'Los Angeles',
    CustomerZipCode: '90001',
};

export const mockContact = {
    name: 'Demo Contact',
    email: 'demo@example.com',
    phone: '555-0101',
};

export const mockCountries = [
    { id: 1, name: 'United States', iso2: 'US' },
    { id: 2, name: 'Canada', iso2: 'CA' },
];

export const mockStates = [
    { id: 1, iso2: 'CA', country_id: 1, name: 'California' },
    { id: 2, iso2: 'NY', country_id: 1, name: 'New York' },
    { id: 3, iso2: 'ON', country_id: 2, name: 'Ontario' },
];

export const mockShipOptions = {
    FreightRate: {
        'Freight Rate': [
            { FDX: { shipvia: 'FDX', name: 'FedEx Ground', amount: 12.95, frttermscd: 'PP' } },
            { UPS: { shipvia: 'UPS', name: 'UPS Ground', amount: 10.5, frttermscd: 'PP' } },
        ],
        'Freight Collect': [
            { FDX: { shipvia: 'FDX-FC', name: 'FedEx Freight Collect', amount: 0, frttermscd: 'C' } },
        ],
    },
    TotalOrderValue: 335,
    SalesTaxAmount: 12.5,
    WireTransferFee: 0,
};
