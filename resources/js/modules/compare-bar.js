export const CompareBar = {

    minimizeKey: 'amplify-product-compare-bar-minimized',

    init(amplify) {
        window.AmplifyCompare = this;
        this.bind();
        this.installCompareProducts();
        this.applyMinimize();

        const bar = document.getElementById('product-compare-bar');
        if (bar && !bar.hidden) {
            document.body.classList.add('has-product-compare-bar');
        }

        document.addEventListener('DOMContentLoaded', () => this.installCompareProducts());
        window.addEventListener('load', () => this.installCompareProducts());

        return this;
    },

    bind() {
        document.addEventListener('click', (event) => {
            const open = event.target.closest('[data-compare-open]');
            if (open && open.classList.contains('disabled')) {
                event.preventDefault();
            }

            const remove = event.target.closest('[data-compare-remove]');
            if (remove && document.getElementById('product-compare-bar')) {
                event.preventDefault();
                this.request(remove, remove.getAttribute('data-compare-remove'), 'remove');
                return;
            }

            const clear = event.target.closest('[data-compare-clear]');
            if (clear && document.getElementById('product-compare-bar')) {
                event.preventDefault();
                this.request(clear, null, 'clear');
                return;
            }

            const toggle = event.target.closest('[data-compare-minimize]');
            if (!toggle || !document.getElementById('product-compare-bar')) {
                return;
            }

            event.preventDefault();
            this.setMinimized(!this.isMinimized());
        });
    },

    escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'})[character];
        });
    },

    renderBar(items) {
        const bar = document.getElementById('product-compare-bar');
        if (!bar) {
            return;
        }

        items = Array.isArray(items) ? items : [];
        const max = parseInt(bar.getAttribute('data-max') || '4', 10);
        const list = bar.querySelector('[data-compare-items]');
        const count = bar.querySelector('[data-compare-count]');
        const open = bar.querySelector('[data-compare-open]');

        if (count) {
            count.textContent = String(items.length);
        }

        bar.hidden = items.length === 0;
        document.body.classList.toggle('has-product-compare-bar', items.length > 0);

        if (open) {
            const ready = items.length >= 2;
            open.classList.toggle('disabled', !ready);
            open.setAttribute('aria-disabled', ready ? 'false' : 'true');
            if (ready) {
                open.removeAttribute('tabindex');
            } else {
                open.setAttribute('tabindex', '-1');
            }
        }

        if (!list) {
            return;
        }

        let html = items.map((item) => {
            return '<div class="product-compare-bar__item" data-compare-id="' + Number(item.id) + '">'
                + '<img src="' + this.escapeHtml(item.image) + '" alt="">'
                + '<span class="product-compare-bar__name">' + this.escapeHtml(item.name) + '</span>'
                + '<button type="button" class="product-compare-bar__remove" aria-label="Remove" data-compare-remove="' + Number(item.id) + '">&times;</button>'
                + '</div>';
        }).join('');

        if (items.length === 1 && items.length < max) {
            html += '<div class="product-compare-bar__slot">Add more</div>';
        }

        list.innerHTML = html;
    },

    syncButtons(items) {
        const ids = new Set((items || []).map((item) => String(item.id)));

        document.querySelectorAll('[data-compare-product]').forEach((button) => {
            const selected = ids.has(String(button.getAttribute('data-compare-product')));
            button.setAttribute('data-compare-state', selected ? 'compared' : 'idle');
            button.classList.toggle('is-compared', selected);
            const label = button.querySelector('[data-compare-label]');
            if (label) {
                label.textContent = selected ? 'Compared' : 'Compare';
            }
        });
    },

    readBarItems() {
        const bar = document.getElementById('product-compare-bar');
        if (!bar) {
            return [];
        }

        return Array.from(bar.querySelectorAll('[data-compare-id]')).map((node) => {
            const image = node.querySelector('img');
            const name = node.querySelector('.product-compare-bar__name');

            return {
                id: node.getAttribute('data-compare-id'),
                image: image ? image.getAttribute('src') : '',
                name: name ? name.textContent : ''
            };
        });
    },

    updateComparison(items) {
        const root = document.getElementById('product-comparison');
        if (!root) {
            return;
        }

        const ids = (items || []).map((item) => String(item.id));
        const selected = new Set(ids);

        root.querySelectorAll('[data-compare-column]').forEach((cell) => {
            if (!selected.has(String(cell.getAttribute('data-compare-column')))) {
                cell.remove();
            }
        });

        const differences = document.getElementById('compare-differences-only');
        if (differences) {
            differences.closest('label').hidden = ids.length < 2;
        }

        if (ids.length === 0) {
            const body = root.querySelector('.card-body');
            if (body) {
                body.innerHTML = '<p class="text-center text-muted mb-0">Your product comparison list is empty.</p>';
            }
            return;
        }

        root.querySelectorAll('tbody tr').forEach((row) => {
            const values = Array.from(row.querySelectorAll('[data-compare-column]')).map((cell) => {
                return cell.textContent.trim();
            });
            const differs = new Set(values).size > 1;
            row.setAttribute('data-compare-differs', differs ? '1' : '0');
            row.hidden = !!(differences && differences.checked && !differs);
        });
    },

    render(items) {
        this.syncButtons(items);
        this.renderBar(items);
        this.updateComparison(items);
        this.applyMinimize();
    },

    applyOptimistically(action, product) {
        if (action === 'clear') {
            this.render([]);
        } else if (action === 'remove') {
            this.render(this.readBarItems().filter((item) => {
                return String(item.id) !== String(product);
            }));
        }
    },

    request(target, product, action, attach) {
        const snapshot = this.readBarItems();
        this.applyOptimistically(action, product);

        const button = target && target.closest ? target.closest('button, a') : target;
        if (button) {
            button.disabled = true;
        }

        const holder = action === 'list' && attach ? document.querySelector(attach) : null;
        if (holder) {
            holder.innerHTML = '';
        }

        $.ajax(Amplify.config.url.productCompare, {
            method: 'POST',
            dataType: 'json',
            data: {
                product: product ?? null,
                action: action
            },
            success: (response) => {
                if (action === 'list') {
                    if (holder) {
                        holder.innerHTML = response.data?.html || '';
                    }
                    return;
                }

                window.AmplifyCompare.sync(response);
                Amplify.alert(response.message, 'Product Comparison', {
                    icon: response.success === true ? 'success' : 'error',
                    timer: 2500,
                    timerProgressBar: true
                });
            },
            error: (xhr) => {
                const response = xhr.responseJSON || {};
                this.render(Array.isArray(response.items) ? response.items : snapshot);
                Amplify.alert(
                    response.message || 'Something went wrong. Please try again later.',
                    'Product Comparison',
                    {timer: 2500, timerProgressBar: true}
                );
            },
            complete: () => {
                if (button) {
                    button.disabled = false;
                }
            }
        });
    },

    sync(response) {
        if (response && Array.isArray(response.items)) {
            this.render(response.items);
        }
    },

    installCompareProducts() {
        if (!window.Amplify || typeof Amplify.compareProducts !== 'function' || Amplify.compareProducts.delegatesToCompare) {
            return;
        }

        Amplify.compareProducts = (target, product, action, attach) => {
            return window.AmplifyCompare.request(target, product, action, attach);
        };
        Amplify.compareProducts.synced = true;
        Amplify.compareProducts.delegatesToCompare = true;
    },

    isMinimized() {
        try {
            return sessionStorage.getItem(this.minimizeKey) === '1';
        } catch (error) {
            return false;
        }
    },

    applyMinimize() {
        const bar = document.getElementById('product-compare-bar');
        if (!bar) {
            return;
        }

        const minimized = this.isMinimized() && !bar.hidden;
        bar.classList.toggle('is-minimized', minimized);
        document.body.classList.toggle('is-product-compare-bar-minimized', minimized);

        const count = bar.querySelector('[data-compare-count]');
        const minimizedCount = bar.querySelector('[data-compare-minimized-count]');
        if (count && minimizedCount) {
            minimizedCount.textContent = count.textContent;
        }

        const toggle = bar.querySelector('[data-compare-minimize]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', minimized ? 'false' : 'true');
            toggle.setAttribute('aria-label', minimized ? 'Expand comparison' : 'Minimize comparison');
        }
    },

    setMinimized(minimized) {
        try {
            sessionStorage.setItem(this.minimizeKey, minimized ? '1' : '0');
        } catch (error) {}

        this.applyMinimize();
    }
};
