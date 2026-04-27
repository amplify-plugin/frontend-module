/*
    * Type: success, error, info, warning.
    * -------------------------------------
    * This function is used to show notification toaster with title and message based on the type of notification.
    */
function ShowNotification(type = 'info', title = 'Title', message = 'Your message here') {
    Amplify.notify(type, message, title);
}

function setPositionOffCanvas(isClosed = true) {
    if (isClosed) {
        document.querySelector('#position-inherit').setAttribute('style', 'position: relative;');
    } else {
        document.querySelector('#position-inherit').setAttribute('style', 'position: inherit;');
    }
}

/**
 * Order filter helper methods
 * @see templates/template-1/components/customer/customer_profile_order_list.blade.php
 */
function orderCreatedDateRangeChange(start, end) {
    $(ORDER_DATE_RANGER + ' span').html(moment(start).format(AMPLIFY_DATE_MOMENT_FORMAT) + ' TO ' + moment(end).format(AMPLIFY_DATE_MOMENT_FORMAT));
}

function initOrderCreatedDateRangePicker(startDate, endDate) {

    $(ORDER_DATE_RANGER).daterangepicker({
        startDate: moment(startDate),
        endDate: moment(endDate),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Last 3 Months': [moment().subtract(3, 'month'), moment()],
            'Last 6 Months': [moment().subtract(6, 'month'), moment()],
            'Last 12 Months': [moment().subtract(12, 'month'), moment()],
        }
    }, orderCreatedDateRangeChange);

    orderCreatedDateRangeChange(startDate, endDate);

    $(ORDER_DATE_RANGER).on('apply.daterangepicker', function (ev, picker) {
        var startDate = picker.startDate.format('YYYY-MM-DD');
        var endDate = picker.endDate.format('YYYY-MM-DD');

        $("#created_start_date").val(startDate);
        $("#created_end_date").val(endDate);
        $("#order-search-form").submit();
    });
}

/**
 * Invoice payment page methods
 * @see templates/template-1/components/invoice-summary/index.blade.php
 */
function addInvoiceToPayment(e) {
    let preAmount = $('#will-pay').text();
    preAmount = preAmount ? parseFloat(preAmount) : 0
    if (e.checked) {
        $('#will-pay').text(parseFloat(preAmount + parseFloat(e.dataset.amount)).toFixed(2))
        invoices.push(e.value)
    } else if ((index = invoices.findIndex(item => item === e.value)) >= 0) {
        $('#will-pay').text(parseFloat(parseFloat(preAmount) - parseFloat(e.dataset.amount)).toFixed(2))

        invoices.splice(index, 1)
    }

    $('#make-pay').attr('disabled', !(invoices.length > 0))
}

/**
 * Order filter helper methods
 * @see templates/template-1/components/customer/order_list.blade.php
 */
function customerItemListDateRangeChange(start, end) {
    $(CUSTOMER_LIST_DATE_RANGE + ' span').html(moment(start).format(AMPLIFY_DATE_MOMENT_FORMAT) + ' TO ' + moment(end).format(AMPLIFY_DATE_MOMENT_FORMAT));
}

function initcustomerItemListDateRangePicker(startDate, endDate) {

    $(CUSTOMER_LIST_DATE_RANGE).daterangepicker({
        startDate: moment(startDate),
        endDate: moment(endDate),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                'month').endOf('month')]
        }
    }, customerItemListDateRangeChange);

    customerItemListDateRangeChange(startDate, endDate);

    $(CUSTOMER_LIST_DATE_RANGE).on('apply.daterangepicker', function (ev, picker) {
        var startDate = picker.startDate.format('YYYY-MM-DD');
        var endDate = picker.endDate.format('YYYY-MM-DD');

        $("#filtered_start_date").val(startDate);
        $("#filtered_end_date").val(endDate);
        $("#customer-item-list-search-form").submit();
    });
}

// Filter List Groups
//---------------------------------------------------------
// var targetList = $();
function filterList(trigger) {
    trigger.each(function () {
        var self = $(this),
            target = self.data('filter-list'),
            search = self.find('input[type=text]'),
            filters = self.find('input[type=radio]'),
            list = $(target).find('.list-group-item');

        // Search
        search.keyup(function () {
            var searchQuery = search.val();
            list.each(function () {
                var text = $(this).text().toLowerCase();
                (text.indexOf(searchQuery.toLowerCase()) == 0) ? $(this).show() : $(this).hide();
            });
        });

        // Filters
        filters.on('click', function (e) {
            var targetItem = $(this).val();
            if (targetItem !== 'all') {
                list.hide();
                $('[data-filter-item=' + targetItem + ']').show();
            } else {
                list.show();
            }

        });
    });
}

filterList($('[data-filter-list]'));

function handleFaqCounterNotification(element, showNotification = true) {
    $.get($(element).data('route'), function (data, status) {
        if (showNotification) {
            iziToast.show({
                title: 'Thank you',
                position: 'topRight',
                timeout: 3200,
                transitionIn: 'fadeInLeft',
                transitionOut: 'fadeOut',
                transitionInMobile: 'fadeIn',
                transitionOutMobile: 'fadeOut',
                class: 'iziToast-success',
                message: 'for your feedback',
                icon: 'icon-bell'
            });
        }
    });
}

function stringReplaceArray(search, replace, subject) {
    for (let i = 0; i < search.length; i++) {
        let regex = new RegExp(search[i], "g");
        subject = subject.replace(regex, replace[i]);
    }

    return subject.toString();
}

function loadFile(event, target) {
    var output = document.getElementById(target);
    var label = document.getElementById(target + '_label');
    var file = event.target.files[0];
    label.innerText = file.name;
    output.src = URL.createObjectURL(file);
    output.onload = function () {
        URL.revokeObjectURL(output.src)
    }
}

function cartShowHide(ele) {
    if (ele) {
        ele.addEventListener('click', (e) => {
            ele.classList.toggle('show-cart-cs')
        });
    }
}

function cartHide(ele, event) {
    if (ele && !ele.contains(event.target)) {
        ele.classList.remove('show-cart-cs')
    }
}

$(document).ready(function () {
    $.ajaxSetup({
        xhrFields: {
            withCredentials: true
        }
    });

    $.ajaxPrefilter(function (options, originalOptions, xhr) {

        // remove wrong header if set anywhere
        if (options.headers && options.headers['X-CSRF-TOKEN']) {
            delete options.headers['X-CSRF-TOKEN'];
        }

        // always inject correct token from cookie
        const match = document.cookie.match(/(^|;\s*)XSRF-TOKEN=([^;]+)/);
        const token = match ? decodeURIComponent(match[2]) : null;

        if (token) {
            xhr.setRequestHeader('X-XSRF-TOKEN', token);
        }
    });

    $.validator.methods.email = function (value, element) {
        return this.optional(element) || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[A-Za-z]{2,}$/.test(value);
    };

    $.validator.setDefaults({
        errorElement: 'span',
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        messages: {
            email: 'The {0} must be a valid email address.'
        },
        highlight: function (element, errorClass) {
            $(element).addClass(errorClass);
        },
        unhighlight: function (element, errorClass) {
            $(element).removeClass(errorClass);
            const name = $(element).attr('name');
            $('#' + name + '-error').text('');
        },
        errorPlacement: function (error, element) {
            const name = element.attr('name');
            const $errorEl = $('#' + name + '-error');

            if ($errorEl.length) {
                $errorEl.text(error.text()).show();
            } else {
                error.attr('id', name + '-error');
                element.after(error);
            }
        }
    });

    $.extend($.validator.messages, {
            required: "The {0} field is required.",
            remote: "Please fix this field.",
            email: "The {0} must be a valid email address.",
            url: "The {0} is not a valid URL.",
            date: "The {0} is not a valid date.",
            dateISO: "The {0} is not a valid ISO format date.",
            number: "The {0} must be a number.",
            digits: "The {0} must be an integer.",
            equalTo: "The {0} and {1} must match.",
            maxlength: $.validator.format( "The {0} must not be greater than {1} characters." ),
            minlength: $.validator.format( "The {0} must be at least {1} characters." ),
            rangelength: $.validator.format( "The {0} must be between {1} and {2} characters." ),
            range: $.validator.format( "The {0} must be between {1} and {2}." ),
            max: $.validator.format( "The {0} must not be greater than {1}." ),
            min: $.validator.format( "The {0} must be at least {1}." ),
            step: $.validator.format( "The {0} must be a multiple of {1}." )
    });

    $.extend($.validator.prototype, {
        defaultMessage: function (element, rule) {
            if (typeof rule === "string") {
                rule = {method: rule};
            }

            let message = this.findDefined(
                this.customMessage(element.name, rule.method),
                this.customDataMessage(element, rule.method),
                !this.settings.ignoreTitle && element.title || undefined,
                $.validator.messages[rule.method],
                "<strong>Warning: No message defined for " + element.name + "</strong>"
            );

            let params = rule.parameters;

            rule.parameters = [];

            rule.parameters.push((element.dataset.field || element.name || 'field')
                .toString()
                .replace(/_/g, ' '));

            rule.parameters.push(params);


            const pattern = /\$?\{(\d+)\}/g;

            if (typeof message === "function") {
                message = message.call($.validator, ...rule.parameters, element);

            } else if (pattern.test(message)) {
                message = $.validator.format(message.replace(pattern, "{$1}"), ...rule.parameters);
            }
            return message;
        },
    });

    const showCart = document.getElementById('show-cart');

    if (showCart) {
        cartShowHide(showCart);
    }

    const showAccount = document.getElementById('show-account');
    const showAccountExchange = document.getElementById('show-exchage-account');

    cartShowHide(showAccount);
    cartShowHide(showAccountExchange);

    $(document).on('click', function (event) {
        let target = event.target;
        cartHide(showCart, event);
        cartHide(showAccount, event);
        cartHide(showAccountExchange, event);
    });

    // Animated Scroll to Top Button
    //------------------------------------------------------------------------------
    var $scrollTop = $('.scroll-to-top-btn');
    if ($scrollTop.length > 0) {
        $(window).on('scroll', function () {
            if ($(this).scrollTop() > 600) {
                $scrollTop.addClass('visible');
            } else {
                $scrollTop.removeClass('visible');
            }
        });
        $scrollTop.on('click', function (e) {
            e.preventDefault();
            $('html').velocity('scroll', {
                offset: 0,
                duration: 1200,
                easing: 'easeOutExpo',
                mobileHA: false
            });
        });
    }


    // Smooth scroll to element
    //---------------------------------------------------------
    $(document).on('click', '.scroll-to', function (event) {
        var target = $(this).attr('href');
        if ('#' === target) {
            return false;
        }

        var $target = $(target);
        if ($target.length > 0) {
            var $elemOffsetTop = $target.data('offset-top') || 70;
            $('html').velocity('scroll', {
                offset: $(this.hash).offset().top - $elemOffsetTop,
                duration: 1000,
                easing: 'easeOutExpo',
                mobileHA: false
            });
        }
        event.preventDefault();
    });

    // Tooltips
    //------------------------------------------------------------------------------
    $('[data-toggle="tooltip"]').tooltip();

    // Popovers
    //------------------------------------------------------------------------------
    $('[data-toggle="popover"]').popover();

    //Select2 Defaults
    $.fn.select2.defaults.set("theme", "bootstrap4");
});
