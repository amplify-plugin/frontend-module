<style>
    #product-compare-bar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1040;
        background: #fff;
        border-top: 1px solid #d9dde3;
        box-shadow: 0 -8px 24px rgba(0, 0, 0, .08);
    }

    #product-compare-bar[hidden] {
        display: none !important;
    }

    .product-compare-bar__inner {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .product-compare-bar__items {
        display: flex;
        gap: 8px;
        flex: 1;
        min-width: 0;
        overflow-x: auto;
    }

    .product-compare-bar__item,
    .product-compare-bar__slot {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 180px;
        max-width: 240px;
        padding: 6px 8px;
        border: 1px solid #e4e7eb;
        border-radius: 6px;
        background: #fafbfc;
    }

    .product-compare-bar__slot {
        justify-content: center;
        color: #6c757d;
        border-style: dashed;
        background: transparent;
    }

    .product-compare-bar__item img {
        width: 42px;
        height: 42px;
        object-fit: contain;
        flex: 0 0 auto;
        background: #fff;
    }

    .product-compare-bar__name {
        font-size: 13px;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-compare-bar__remove {
        border: 0;
        background: transparent;
        color: #dc3545;
        font-size: 20px;
        line-height: 1;
        padding: 0 4px;
        margin-left: auto;
    }

    .product-compare-bar__actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
    }

    body.has-product-compare-bar {
        padding-bottom: 92px;
    }

    @media (max-width: 767px) {
        .product-compare-bar__inner {
            flex-wrap: wrap;
        }

        .product-compare-bar__actions {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>
<div id="product-compare-bar"
     data-max="{{ $max }}"
     @if($items === []) hidden @endif>
    <div class="product-compare-bar__inner">
        <div class="product-compare-bar__items" data-compare-items>
            @foreach($items as $item)
                <div class="product-compare-bar__item" data-compare-id="{{ $item['id'] }}">
                    <img src="{{ $item['image'] }}" alt="">
                    <span class="product-compare-bar__name">{{ $item['name'] }}</span>
                    <button type="button" class="product-compare-bar__remove" aria-label="{{ __('Remove') }}"
                            onclick="AmplifyCompare.request(this, {{ $item['id'] }}, 'remove'); return false;">&times;</button>
                </div>
            @endforeach
            @if(count($items) === 1)
                <div class="product-compare-bar__slot">{{ __('Add more') }}</div>
            @endif
        </div>
        <div class="product-compare-bar__actions">
            <span class="text-muted small text-nowrap">
                <span data-compare-count>{{ count($items) }}</span>/{{ $max }}
            </span>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="AmplifyCompare.request(this, null, 'clear'); return false;">
                {{ __('Clear All') }}
            </button>
            <a class="btn btn-sm btn-primary {{ count($items) < 2 ? 'disabled' : '' }}"
               data-compare-open
               href="{{ $compareUrl }}"
               @if(count($items) < 2) aria-disabled="true" tabindex="-1" @endif
               onclick="if (this.classList.contains('disabled')) { event.preventDefault(); }">
                {{ __('Compare') }}
            </a>
        </div>
    </div>
</div>
@if($items !== [])
    <script>
        document.body.classList.add('has-product-compare-bar');
    </script>
@endif
<script>
(function () {
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'})[character];
        });
    }

    function renderBar(items) {
        var bar = document.getElementById('product-compare-bar');
        if (!bar) {
            return;
        }

        items = Array.isArray(items) ? items : [];
        var max = parseInt(bar.getAttribute('data-max') || '4', 10);
        var list = bar.querySelector('[data-compare-items]');
        var count = bar.querySelector('[data-compare-count]');
        var open = bar.querySelector('[data-compare-open]');

        if (count) {
            count.textContent = String(items.length);
        }

        bar.hidden = items.length === 0;
        document.body.classList.toggle('has-product-compare-bar', items.length > 0);

        if (open) {
            var ready = items.length >= 2;
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

        var html = items.map(function (item) {
            return '<div class="product-compare-bar__item" data-compare-id="' + Number(item.id) + '">'
                + '<img src="' + escapeHtml(item.image) + '" alt="">'
                + '<span class="product-compare-bar__name">' + escapeHtml(item.name) + '</span>'
                + '<button type="button" class="product-compare-bar__remove" aria-label="Remove" onclick="AmplifyCompare.request(this, ' + Number(item.id) + ', \'remove\'); return false;">&times;</button>'
                + '</div>';
        }).join('');

        if (items.length === 1 && items.length < max) {
            html += '<div class="product-compare-bar__slot">Add more</div>';
        }

        list.innerHTML = html;
    }

    function syncButtons(items) {
        var ids = new Set((items || []).map(function (item) {
            return String(item.id);
        }));

        document.querySelectorAll('[data-compare-product]').forEach(function (button) {
            var selected = ids.has(String(button.getAttribute('data-compare-product')));
            button.setAttribute('data-compare-state', selected ? 'compared' : 'idle');
            button.classList.toggle('is-compared', selected);
            var label = button.querySelector('[data-compare-label]');
            if (label) {
                label.textContent = selected ? 'Compared' : 'Compare';
            }
        });
    }

    function readBarItems() {
        var bar = document.getElementById('product-compare-bar');
        if (!bar) {
            return [];
        }

        return Array.from(bar.querySelectorAll('[data-compare-id]')).map(function (node) {
            var image = node.querySelector('img');
            var name = node.querySelector('.product-compare-bar__name');

            return {
                id: node.getAttribute('data-compare-id'),
                image: image ? image.getAttribute('src') : '',
                name: name ? name.textContent : ''
            };
        });
    }

    function updateComparison(items) {
        var root = document.getElementById('product-comparison');
        if (!root) {
            return;
        }

        var ids = (items || []).map(function (item) {
            return String(item.id);
        });
        var selected = new Set(ids);

        root.querySelectorAll('[data-compare-column]').forEach(function (cell) {
            if (!selected.has(String(cell.getAttribute('data-compare-column')))) {
                cell.remove();
            }
        });

        var differences = document.getElementById('compare-differences-only');
        if (differences) {
            differences.closest('label').hidden = ids.length < 2;
        }

        if (ids.length === 0) {
            var body = root.querySelector('.card-body');
            if (body) {
                body.innerHTML = '<p class="text-center text-muted mb-0">Your product comparison list is empty.</p>';
            }
            return;
        }

        root.querySelectorAll('tbody tr').forEach(function (row) {
            var values = Array.from(row.querySelectorAll('[data-compare-column]')).map(function (cell) {
                return cell.textContent.trim();
            });
            var differs = new Set(values).size > 1;
            row.setAttribute('data-compare-differs', differs ? '1' : '0');
            row.hidden = !!(differences && differences.checked && !differs);
        });
    }

    function applyLocal(action, product) {
        if (action !== 'remove' && action !== 'clear') {
            return;
        }

        var items = action === 'clear'
            ? []
            : readBarItems().filter(function (item) {
                return String(item.id) !== String(product);
            });

        syncButtons(items);
        renderBar(items);
        updateComparison(items);
    }

    window.AmplifyCompare = {
        sync: function (response) {
            if (!response || !Array.isArray(response.items)) {
                return;
            }

            syncButtons(response.items);
            renderBar(response.items);
            updateComparison(response.items);
        },
        request: function (target, product, action, attach) {
            var snapshot = readBarItems();
            applyLocal(action, product);

            var button = target && target.closest ? target.closest('button, a') : target;
            if (button) {
                button.disabled = true;
            }

            if (action === 'list' && attach) {
                var holder = document.querySelector(attach);
                if (holder) {
                    holder.innerHTML = '';
                }
            }

            $.ajax(Amplify.config.url.productCompare, {
                method: 'POST',
                dataType: 'json',
                data: {
                    product: product ?? null,
                    action: action
                },
                success: function (response) {
                    if (action === 'list') {
                        var holder = document.querySelector(attach);
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
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    if (Array.isArray(response.items)) {
                        window.AmplifyCompare.sync(response);
                    } else {
                        syncButtons(snapshot);
                        renderBar(snapshot);
                        updateComparison(snapshot);
                    }
                    Amplify.alert(
                        response.message || 'Something went wrong. Please try again later.',
                        'Product Comparison',
                        {timer: 2500, timerProgressBar: true}
                    );
                },
                complete: function () {
                    if (button) {
                        button.disabled = false;
                    }
                }
            });
        }
    };

    function install() {
        if (!window.Amplify || typeof Amplify.compareProducts !== 'function' || Amplify.compareProducts.delegatesToCompare) {
            return;
        }

        Amplify.compareProducts = function (target, product, action, attach) {
            return window.AmplifyCompare.request(target, product, action, attach);
        };
        Amplify.compareProducts.synced = true;
        Amplify.compareProducts.delegatesToCompare = true;
    }

    install();
    document.addEventListener('DOMContentLoaded', install);
    window.addEventListener('load', install);
})();
</script>
