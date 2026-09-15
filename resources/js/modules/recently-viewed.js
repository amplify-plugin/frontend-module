export const RecentlyViewed = {

    init(amplify) {
        if (this.isEnabled()) {
            this.mergeGuestHistory();
            this.initProductDetailTracking();
            this.initCarouselWidgets();
            this.initPageWidgets();
        }

        return this;
    },

    storageKey() {
        return Amplify.config.recentlyViewedStorageKey
            || Amplify.config.url?.recentlyViewedStorageKey
            || 'amplify-rv';
    },

    maxItems() {
        return parseInt(
            Amplify.config.recentlyViewedMaxItems
            || Amplify.config.url?.recentlyViewedMaxItems
            || 20,
            10,
        );
    },

    isEnabled() {
        const enabled = Amplify.config.recentlyViewedEnabled ?? Amplify.config.url?.recentlyViewedEnabled;

        return enabled !== false;
    },

    isAuthenticated() {
        return Amplify.config.isCustomerAuthenticated === true;
    },

    urls() {
        return Amplify.config.url?.recentlyViewed || {};
    },

    getAll() {
        try {
            const stored = window.localStorage.getItem(this.storageKey());
            const parsed = stored ? JSON.parse(stored) : [];

            return Array.isArray(parsed) ? parsed.map((id) => parseInt(id, 10)).filter(Boolean) : [];
        } catch (error) {
            return [];
        }
    },

    save(ids) {
        window.localStorage.setItem(this.storageKey(), JSON.stringify(ids.slice(0, this.maxItems())));
    },

    add(productId) {
        productId = parseInt(productId, 10);

        if (!productId) {
            return this.getAll();
        }

        const ids = this.getAll().filter((id) => id !== productId);
        ids.unshift(productId);

        this.save(ids);

        return ids;
    },

    remove(productId) {
        productId = parseInt(productId, 10);
        const ids = this.getAll().filter((id) => id !== productId);
        this.save(ids);

        return ids;
    },

    clearStorage() {
        window.localStorage.removeItem(this.storageKey());
    },

    record(productId) {
        if (!this.isEnabled()) {
            return;
        }

        productId = parseInt(productId, 10);

        if (!productId) {
            return;
        }

        if (this.isAuthenticated()) {
            return;
        }

        this.add(productId);
    },

    mergeGuestHistory() {
        if (!this.isEnabled() || !this.isAuthenticated()) {
            return;
        }

        const productIds = this.getAll();

        if (!productIds.length || !this.urls().merge) {
            return;
        }

        $.ajax({
            url: this.urls().merge,
            method: 'POST',
            dataType: 'json',
            data: {
                product_ids: productIds,
                _token: this.getCsrfTokenFromMeta(),
            },
        }).done((response) => {
            if (response?.success) {
                this.clearStorage();
            }
        });
    },

    parseSettings(raw) {
        try {
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            return {};
        }
    },

    buildRequestSettings(settings = {}) {
        return {
            layout: settings.layout || 'card',
            products_limit: settings.productsLimit,
            show_cart_btn: settings.showCartBtn,
            cart_button_label: settings.cartButtonLabel,
            detail_button_label: settings.detailButtonLabel,
            show_price: settings.showPrice,
            show_guest_price: settings.showGuestPrice,
            show_top_discount_badge: settings.showTopDiscountBadge,
            show_order_list: settings.showOrderList,
            order_list_label: settings.orderListLabel,
            show_navigation: settings.showNavigation,
            slider_item_gap: settings.sliderItemGap,
            display_product_code: settings.displayProductCode,
            display_short_description: settings.displayShortDescription,
            display_manufacturer: settings.displayManufacturer,
            allow_remove: settings.allowRemove === true,
        };
    },

    fetchProducts(settings, productIds = null) {
        const ids = productIds || this.getAll();

        if (!ids.length || !this.urls().products) {
            return $.Deferred().resolve({success: true, count: 0, html: ''}).promise();
        }

        const data = {
            product_ids: ids,
            ...this.buildRequestSettings(settings),
            _token: this.getCsrfTokenFromMeta(),
        };

        return $.ajax({
            url: this.urls().products,
            method: 'POST',
            dataType: 'json',
            data: data,
        });
    },

    getCsrfTokenFromMeta() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    },

    mountCarousel($carousel, html) {
        if (!html) {
            $carousel.closest('.recently-viewed-section').addClass('d-none');
            return;
        }

        const section = $carousel.closest('.recently-viewed-section');
        section.removeClass('d-none');
        $carousel.removeClass('d-none');
        $carousel.trigger('destroy.owl.carousel');
        $carousel.html(html);

        if (typeof window.initOwlCarousel === 'function') {
            window.initOwlCarousel($carousel);
        } else if ($carousel.data('owl.carousel')) {
            $carousel.owlCarousel('refresh');
        } else {
            $carousel.owlCarousel(JSON.parse($carousel.attr('data-owl-carousel') || '{}'));
        }
    },

    initCarouselWidgets() {
        document.querySelectorAll('[data-recently-viewed-widget="carousel"]').forEach((widget) => {
            if (widget.dataset.recentlyViewedLoaded === '1') {
                return;
            }

            const settings = this.parseSettings(widget.dataset.recentlyViewedSettings || '{}');
            const isAuthenticated = widget.dataset.recentlyViewedAuthenticated === '1';

            if (isAuthenticated && widget.querySelector('.owl-carousel .grid-item')) {
                widget.dataset.recentlyViewedLoaded = '1';
                return;
            }

            if (!isAuthenticated) {
                widget.dataset.recentlyViewedLoaded = '1';

                const $carousel = $(widget).find('.recently-viewed-guest-carousel');

                this.fetchProducts(settings).done((response) => {
                    if (response?.html) {
                        this.mountCarousel($carousel, response.html);
                    } else {
                        this.mountCarousel($carousel, '');
                    }
                }).fail(() => {
                    widget.dataset.recentlyViewedLoaded = '0';
                });
            }
        });
    },

    initPageWidgets() {
        document.querySelectorAll('[data-recently-viewed-widget="page"]').forEach((widget) => {
            this.bindPageActions(widget);

            if (widget.dataset.recentlyViewedAuthenticated === '1') {
                return;
            }

            if (widget.dataset.recentlyViewedLoaded === '1') {
                return;
            }

            widget.dataset.recentlyViewedLoaded = '1';

            const settings = this.parseSettings(widget.dataset.recentlyViewedSettings || '{}');
            const ids = this.getAll();
            const grid = widget.querySelector('.recently-viewed-guest-grid');
            const guestPage = widget.querySelector('.recently-viewed-guest-page');
            const emptyState = widget.querySelector('.recently-viewed-guest-empty');
            const clearButton = widget.querySelector('.recently-viewed-clear-btn');

            if (!ids.length) {
                guestPage?.classList.add('d-none');
                emptyState?.classList.remove('d-none');
                clearButton?.classList.add('d-none');
                return;
            }

            this.fetchProducts(settings, ids).done((response) => {
                if (!response?.html || !grid) {
                    guestPage?.classList.add('d-none');
                    emptyState?.classList.remove('d-none');
                    clearButton?.classList.add('d-none');
                    return;
                }

                grid.innerHTML = response.html;
                guestPage?.classList.remove('d-none');
                emptyState?.classList.add('d-none');
                clearButton?.classList.remove('d-none');
                this.bindPageActions(widget);
            }).fail(() => {
                widget.dataset.recentlyViewedLoaded = '0';
            });
        });
    },

    bindPageActions(widget) {
        widget.querySelectorAll('.recently-viewed-remove-btn').forEach((button) => {
            if (button.dataset.bound === '1') {
                return;
            }

            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                const productId = parseInt(button.dataset.productId, 10);

                if (!productId) {
                    return;
                }

                if (this.isAuthenticated()) {
                    $.ajax({
                        url: `${this.urls().destroy}/${productId}`,
                        method: 'DELETE',
                        dataType: 'json',
                    }).done((response) => {
                        if (response?.success) {
                            button.closest('.recently-viewed-grid-item')?.remove();
                            Amplify.notify('success', response.message);
                        }
                    });
                } else {
                    this.remove(productId);
                    button.closest('.recently-viewed-grid-item')?.remove();
                    this.initPageWidgets();
                }
            });
        });

        widget.querySelectorAll('.recently-viewed-clear-btn').forEach((button) => {
            if (button.dataset.bound === '1') {
                return;
            }

            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                swal.fire({
                    title: 'Clear recently viewed?',
                    text: 'This will remove all recently viewed products from your history.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Clear All',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    if (this.isAuthenticated()) {
                        $.ajax({
                            url: this.urls().clear,
                            method: 'DELETE',
                            dataType: 'json',
                        }).done((response) => {
                            if (response?.success) {
                                window.location.reload();
                            }
                        });
                    } else {
                        this.clearStorage();
                        window.location.reload();
                    }
                });
            });
        });
    },

    initProductDetailTracking() {
        document.querySelectorAll('[data-recently-viewed-product-id]').forEach((element) => {
            this.record(element.dataset.recentlyViewedProductId);
        });
    },
}