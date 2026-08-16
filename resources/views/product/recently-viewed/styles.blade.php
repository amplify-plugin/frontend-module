<style>
    .x-recently-viewed .recently-viewed-section,
    .x-customer-recently-viewed-list .recently-viewed-page-section {
        position: relative;
    }

    .x-customer-recently-viewed-list .recently-viewed-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .x-customer-recently-viewed-list .recently-viewed-grid {
        display: grid;
        gap: 1.25rem;
        align-items: stretch;
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    @media (min-width: 576px) {
        .x-customer-recently-viewed-list .recently-viewed-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 992px) {
        .x-customer-recently-viewed-list .recently-viewed-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 1200px) {
        .x-customer-recently-viewed-list .recently-viewed-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .x-customer-recently-viewed-list .recently-viewed-grid-item {
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        min-width: 0;
    }

    .x-customer-recently-viewed-list .recently-viewed-remove-btn {
        position: absolute;
        top: 0.625rem;
        right: 0.625rem;
        z-index: 3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        padding: 0;
        margin: 0;
        border: 1px solid rgba(74, 74, 74, 0.22);
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 2px 8px rgba(31, 53, 103, 0.12);
        color: #374250;
        font-size: 1.25rem;
        font-weight: 500;
        line-height: 1;
        cursor: pointer;
        appearance: none;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .x-customer-recently-viewed-list .recently-viewed-remove-btn:hover,
    .x-customer-recently-viewed-list .recently-viewed-remove-btn:focus {
        background: #fff;
        border-color: #b5121b;
        color: #b5121b;
        box-shadow: 0 4px 12px rgba(181, 18, 27, 0.18);
        transform: scale(1.06);
        outline: none;
    }

    .x-customer-recently-viewed-list .recently-viewed-remove-btn:focus-visible {
        outline: 2px solid rgba(181, 18, 27, 0.45);
        outline-offset: 2px;
    }

    /* Card layout (default) */
    .x-recently-viewed .purchased-together-card,
    .x-customer-recently-viewed-list .purchased-together-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        border: 1px solid rgba(74, 74, 74, 0.35);
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        transition: box-shadow 0.25s ease, border-color 0.25s ease, transform 0.25s ease;
    }

    .x-recently-viewed .purchased-together-card:hover,
    .x-customer-recently-viewed-list .purchased-together-card:hover {
        border-color: rgba(181, 18, 27, 0.18);
        box-shadow: 0 10px 28px rgba(31, 53, 103, 0.08);
        transform: translateY(-2px);
    }

    .x-recently-viewed .purchased-together-thumb,
    .x-customer-recently-viewed-list .purchased-together-thumb {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 180px;
        max-height: 180px;
        padding: 1rem 1.25rem;
        margin: 0;
        border-bottom: 1px solid rgba(74, 74, 74, 0.25);
        background: #fafafa;
    }

    .x-recently-viewed .purchased-together-thumb .product-image,
    .x-customer-recently-viewed-list .purchased-together-thumb .product-image {
        width: 100%;
        height: 148px;
        object-fit: contain;
    }

    .x-recently-viewed .purchased-together-body,
    .x-customer-recently-viewed-list .purchased-together-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem 1.125rem 1.25rem;
    }

    .x-recently-viewed .purchased-together-info,
    .x-customer-recently-viewed-list .purchased-together-info {
        flex: 1;
        text-align: left;
    }

    .x-recently-viewed .purchased-together-name,
    .x-customer-recently-viewed-list .purchased-together-name {
        margin-bottom: 0.375rem !important;
        font-size: 0.9375rem;
        font-weight: 600;
        line-height: 1.45;
        text-align: left !important;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
        min-height: 2.85em;
    }

    .x-recently-viewed .purchased-together-name > a,
    .x-customer-recently-viewed-list .purchased-together-name > a {
        color: #374250;
        text-decoration: none;
    }

    .x-recently-viewed .purchased-together-name > a:hover,
    .x-customer-recently-viewed-list .purchased-together-name > a:hover {
        color: #b5121b;
    }

    .x-recently-viewed .purchased-together-code,
    .x-customer-recently-viewed-list .purchased-together-code {
        margin-bottom: 0.5rem !important;
        font-size: 0.8125rem;
        color: #475467;
        text-align: left !important;
    }

    .x-recently-viewed .purchased-together-code span,
    .x-customer-recently-viewed-list .purchased-together-code span {
        font-weight: 500;
    }

    .x-recently-viewed .purchased-together-price,
    .x-customer-recently-viewed-list .purchased-together-price {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #b5121b;
        text-align: left !important;
        justify-content: flex-start !important;
    }

    .x-recently-viewed .purchased-together-actions,
    .x-customer-recently-viewed-list .purchased-together-actions {
        margin-top: auto;
        padding-top: 0.25rem;
    }

    .x-recently-viewed .purchased-together-actions .x-product-quick-action,
    .x-customer-recently-viewed-list .purchased-together-actions .x-product-quick-action {
        width: 100%;
    }

    .x-recently-viewed .purchased-together-actions .d-grid,
    .x-customer-recently-viewed-list .purchased-together-actions .d-grid {
        gap: 0.75rem !important;
    }

    .x-recently-viewed .purchased-together-actions .x-cart-quantity-update,
    .x-customer-recently-viewed-list .purchased-together-actions .x-cart-quantity-update {
        width: 100%;
        justify-content: center;
        margin-bottom: 0;
    }

    .x-recently-viewed .purchased-together-actions .btn-primary,
    .x-customer-recently-viewed-list .purchased-together-actions .btn-primary {
        width: 100%;
        min-height: 38px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: none;
        letter-spacing: 0.01em;
        border-radius: 8px;
    }

    /* Minimal layout */
    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-card-minimal,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-card-minimal {
        display: flex;
        align-items: stretch;
        gap: 0.875rem;
        height: 100%;
        padding: 0.875rem;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        background: #fff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-card-minimal:hover,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-card-minimal:hover {
        border-color: rgba(181, 18, 27, 0.22);
        box-shadow: 0 6px 18px rgba(31, 53, 103, 0.07);
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-thumb,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-thumb {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 84px;
        height: 84px;
        border: 1px solid #eef0f3;
        border-radius: 8px;
        background: #fafafa;
        overflow: hidden;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-thumb .product-image,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-thumb .product-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 0.375rem;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-content,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-content {
        display: flex;
        flex: 1;
        align-items: center;
        justify-content: space-between;
        gap: 0.875rem;
        min-width: 0;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-info,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-info {
        flex: 1;
        min-width: 0;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-brand,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-brand {
        display: block;
        margin-bottom: 0.125rem;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #667085;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-name,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-name {
        margin: 0 0 0.375rem !important;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.35;
        text-align: left !important;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-name > a,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-name > a {
        color: #1f2937;
        text-decoration: none;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-name > a:hover,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-name > a:hover {
        color: #b5121b;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-meta,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-code,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-code {
        font-size: 0.75rem;
        font-weight: 500;
        color: #667085;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-divider,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-divider {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #cbd5e1;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-price,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-price {
        font-size: 0.875rem;
        font-weight: 700;
        color: #b5121b;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-actions,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-actions {
        flex-shrink: 0;
        width: 148px;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-actions .d-grid,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-actions .d-grid {
        gap: 0.5rem !important;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-actions .x-cart-quantity-update,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-actions .x-cart-quantity-update {
        justify-content: center;
    }

    .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-actions .btn-primary,
    .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-actions .btn-primary {
        min-height: 34px;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        white-space: nowrap;
    }

    @media (max-width: 991px) {
        .x-recently-viewed .purchased-together-thumb,
        .x-customer-recently-viewed-list .purchased-together-thumb {
            min-height: 150px;
            max-height: 150px;
            padding: 0.875rem 1rem;
        }

        .x-recently-viewed .purchased-together-thumb .product-image,
        .x-customer-recently-viewed-list .purchased-together-thumb .product-image {
            height: 118px;
        }

        .x-recently-viewed .purchased-together-body,
        .x-customer-recently-viewed-list .purchased-together-body {
            padding: 0.875rem 1rem 1rem;
            gap: 0.875rem;
        }

        .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-card-minimal,
        .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-card-minimal {
            flex-direction: column;
        }

        .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-thumb,
        .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-thumb {
            width: 100%;
            height: 72px;
        }

        .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-content,
        .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-content {
            flex-direction: column;
            align-items: stretch;
        }

        .x-recently-viewed.recently-viewed-layout-minimal .purchased-together-minimal-actions,
        .x-customer-recently-viewed-list.recently-viewed-layout-minimal .purchased-together-minimal-actions {
            width: 100%;
        }
    }
</style>
