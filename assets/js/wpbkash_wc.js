jQuery(
    function ($) {

        const wpbkash = {
            bodyEl: $('body'),
            checkoutFormSelector: 'form.checkout',
            orderReview: 'form#order_review',
            $checkoutFormSelector: $('form.checkout'),
            trigger: '#bkash_trigger',
            onTrigger: '#bkash_on_trigger',

            // Order notes.
            orderNotesValue: '',
            orderNotesSelector: 'textarea#order_comments',
            orderNotesEl: $('textarea#order_comments'),

            // Payment method
            paymentMethodEl: $('input[name="payment_method"]:checked'),
            paymentMethod: '',
            selectAnotherSelector: '#paysoncheckout-select-other',

            // Address data.
            accessToken: '',
            scriptloaded: false,
            checkoutProcess: false,
            singleton: false,

            /*
             * Check if Payson is the selected gateway.
             */
            checkIfbKashSelected: function () {
                if ($(wpbkash.checkoutFormSelector).length && $('input[name="payment_method"]').val().length > 0) {
                    wpbkash.paymentMethod = $('input[name="payment_method"]:checked').val();
                    if ('wpbkash' === wpbkash.paymentMethod) {
                        return true;
                    }
                }
                return false;
            },

            /**
             * Initialize bKash trigger
             */
            wcbkashTrigger: function () {

                if (!wpbkash.checkIfbKashSelected()) {
                    return false;
                }

                if (!wpbkash.scriptloaded) {
                    window.$ = $.noConflict();
                    $.getScript( wpbkash_params.scriptUrl, function( data, textStatus, jqxhr ) {
                        wpbkash.scriptloaded = true;
                        wpbkash.wcbkashInit();
                    });
                } else {
                    wpbkash.wcbkashInit();
                }

                return false;
            },

            /**
             * bKash initialize
             * 
             * @param {*} order_id 
             * @param {*} redirect 
             */
            wcbkashInit: async function (order_id = '', redirect = '') {
                
                wpbkash.getTrigger();

                var paymentRequest,
                    paymentID;

                paymentRequest = await wpbkash.getOrderData(order_id);

                // return false;
                bKash.init({
                    paymentMode: 'checkout',
                    paymentRequest: paymentRequest,
                    createRequest: function (request) {
                        wpbkash.createPayment(order_id);
                    },
                    executeRequestOnAuthorization: function () {
                        wpbkash.executePayment(order_id);
                    },
                    onClose: function () {
                        if( $(wpbkash.checkoutFormSelector).length ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        wpbkash.popupMessage('error', 'Payment process cancelled');
                        if( $('#bkash_on_trigger').length > 0 && $('#bkash_on_trigger').hasClass('wpbkash_processing') ) {
                            $('#bkash_on_trigger').removeClass('wpbkash_processing');
                        }
                        if( $('#bKashFrameWrapper').length ) {
                            $('#bKashFrameWrapper').remove();
                        }
                        if (redirect && redirect.length) {
                            window.location.href = redirect;
                        }
                    }
                });
            },

            /**
             * Create Payment api request for bKash
             * 
             * @param {*} order_id 
             */
            createPayment: function (order_id = '') {
                if( wpbkash.singleton ) {
                    return false;
                }
                wpbkash.singleton = true;
                $.ajax({
                    url: wpbkash_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpbkash_createpayment',
                        order_id: order_id,
                        nonce: $('#wpbkash_nonce').val()
                    },
                    success: function (result) {
                        wpbkash.singleton = false;
                        try {
                            if (result) {
                                var obj = JSON.parse(result);
                                if( obj.paymentID != null ) {
                                    paymentID = obj.paymentID;
                                    bKash.create().onSuccess(obj);
                                } else {
                                    if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                        $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                                    }
                                    if( $(wpbkash.orderReview).length !== 0 ) {
                                        $(wpbkash.orderReview).removeClass('processing').unblock();
                                    }
                                    wpbkash.popupMessage('error', wpbkash.errorMessage(result));
                                    bKash.execute().onError();
                                    throw 'Invalid response';
                                }
                            } else {
                                if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                    $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                                }
                                if( $(wpbkash.orderReview).length !== 0 ) {
                                    $(wpbkash.orderReview).removeClass('processing').unblock();
                                }
                                wpbkash.popupMessage('error', wpbkash.errorMessage(result));
                                bKash.execute().onError();
                                throw 'Failed response';
                            }
                        } catch (err) {
                            if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                            }
                            if( $(wpbkash.orderReview).length !== 0 ) {
                                $(wpbkash.orderReview).removeClass('processing').unblock();
                            }
                            wpbkash.popupMessage('error', wpbkash.errorMessage(result));
                            bKash.execute().onError();
                            if( $('#bKashFrameWrapper').length ) {
                                $('#bKashFrameWrapper').remove();
                            }
                        }
                    },
                    error: function () {
                        wpbkash.singleton = false;
                        if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        if( $(wpbkash.orderReview).length !== 0 ) {
                            $(wpbkash.orderReview).removeClass('processing').unblock();
                        }
                        wpbkash.popupMessage('error', wpbkash.errorMessage(result));
                        bKash.create().onError();
                    }
                });
            },

            /**
             * Execute Payment api request for bkash api
             * 
             * @param {*} order_id 
             */
            executePayment: function (order_id = '') {
                $.ajax({
                    url: wpbkash_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpbkash_executepayment',
                        paymentid: paymentID,
                        order_id: order_id,
                        nonce: $('#wpbkash_nonce').val()
                    },
                    success: function (result) {
                        if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        if( $(wpbkash.orderReview).length !== 0 ) {
                            $(wpbkash.orderReview).removeClass('processing').unblock();
                        }
                        if (result && true === result.success && result.data.transactionStatus != null && result.data.transactionStatus === 'completed') {
                            wpbkash.popupMessage('success', wpbkash_params.success_msg);
                            $('#bkash_checkout_valid').val('2').change();
                            if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                $(wpbkash.checkoutFormSelector).trigger('submit');
                            }
                            if( $(wpbkash.orderReview).length !== 0 ) {
                                $(wpbkash.orderReview).trigger('submit');
                            }
                            bKash.execute().onError();

                        } else if (result && result.error && result.data.order_url) {
                            if( result.data.message ) {
                                wpbkash.popupMessage('error', result.data.message);
                            }
                            bKash.execute().onError();
                        } else {
                            if( result.data.message  ) {
                                wpbkash.popupMessage('error', result.data.message);
                            }
                            bKash.execute().onError();
                        }
                    },
                    error: function () {
                        if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        wpbkash.popupMessage('error', wpbkash_params.common_error);
                        bKash.execute().onError();
                    }
                });
            },

            /**
             * Get order amount and other data
             * 
             * @param {*} order_id 
             */
            getOrderData: async function(order_id = '') {
                return await wpbkash.getPayData(order_id).then(response => {
                  if (response.success) {
                    return {
                      amount: response.data.amount,
                      intent: "sale",
                      merchantInvoiceNumber: response.data.invoice
                    }
                  }
                });
            },

            /**
             * Trigger bkash hidden button
             */
            getTrigger: function () {
                $('#bKash_button').removeAttr('disabled');
                setTimeout(
                    function () {
                        $('#bKash_button').trigger('click');
                    }, 1000
                )
            },

            /**
             * Get order amount ajax request
             * 
             * @param {*} order_id 
             */
            getPayData: function(order_id = '') {          
                return $.ajax({
                  url: wc_checkout_params.ajax_url,
                  data: {
                    action: "wpbkash_get_orderdata",
                    order_id: order_id,
                    nonce: $('#wpbkash_nonce').val()
                  },
                  method: "POST",
                });
            },

            /**
             * Trigger bKash process if WooCommerce cart are valid for submit then stop the checkout form and
             * start bKash process programmatically.
             * 
             */
            errorTrigger: function() {
                var error_count = $('.woocommerce-error li').length,
                    bkash_method = $('#payment_method_wpbkash');

                if ( bkash_method.is(':checked') && error_count == 1 && $('.woocommerce-error li[data-id="bkash-payment-required"]').length ) { // Validation Passed (Just the Fake Error I Created Exists)
                    $('.woocommerce-error li[data-id="bkash-payment-required"]').closest('div').hide();
                    $( 'html, body' ).stop();
                    alertify.success(wpbkash_params.process_msg);
                    $(wpbkash.checkoutFormSelector).addClass('processing');
                    wpbkash.wcbkashTrigger();
                }
            },

            /**
             * Error message 
             * 
             * @param {*} msg 
             */
            errorMessage: function(msg) {
                return ( msg !== undefined && msg.errorMessage ) ? msg.errorMessage : wpbkash_params.common_error;
            },

            /**
             * Popup message modal for bKash payment method
             * 
             * @param {*} type 
             * @param {*} message 
             */
            popupMessage: function(type = 'error', message) {
                var thumbnail = wpbkash_params.assets + type + '.png',
                    btn_title = wpbkash_params.btn_title,
                    modal_title = wpbkash_params.modal_title[type];
                
                if( $('.wpbkash--modal-wrap').length !== 0 ) {
                    $('.wpbkash--modal-wrap').remove();
                }
                var modal = '<div class="wpbkash--modal-wrap wpbkash--'+type+'-modal">';
                    modal += '<div class="wpbkash--modal-inner">';
                    modal += '<img src="'+ thumbnail +'" />';
                    modal += '<div class="wpbkash--modal-content">';
                    modal += '<h2>'+ modal_title +'</h2>';
                    modal += '<p>'+ message +'</p>';
                    modal += '</div>';
                    modal += '<button class="wpbkash--modal-btn" type="button">'+ btn_title +'</button>';
                    modal += '</div>';
                    modal += '</div>';

                wpbkash.bodyEl.append( modal );
                $('.wpbkash--modal-wrap').show();

            },

            /**
             * WooCommerce checkout form overlow for ajax
             * 
             * @param {*} $form 
             */
            blockOnSubmit: function ($form) {
                var form_data = $form.data();

                if (1 !== form_data['blockUI.isBlocked']) {
                    $form.block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },

            /**
             * Hide popup message modal when click outside of the modal.
             * 
             * @param {*} e 
             */
            hideModal: function( e ) {
                if ( $(e.target).closest('.wpbkash--modal-inner').length === 0 ) {
                    if( $(this).hasClass('wpbkash--error-modal') ) {
                        window.location.reload();
                    }
                    $(this).removeClass('open').hide().remove();
                }
            },

            /**
             * Hide, remove and reload when user close error modal
             * 
             * @param {*} e 
             */
            modalHandler: function( e ) {
                e.preventDefault();
                if( $(this).closest('.wpbkash--modal-wrap').hasClass('wpbkash--error-modal') ) {
                    window.location.reload();
                }
                console.log( $(this).closest('.wpbkash--modal-wrap') );
                $(this).closest('.wpbkash--modal-wrap').removeClass('open').hide().remove();
                return false;
            },

            checkoutUpdate: function(){
                $('body').trigger('update_checkout');
            },

            orderReviewSubmit: function (e) {
                var $form = $(this).closest('form');
                var method = $form.find('input[name="payment_method"]:checked').val();
                
                if ('wpbkash' === method && $('#bkash_checkout_valid').val() === '1') {
                    e.preventDefault();

                    wpbkash.blockOnSubmit($form);

                    var redirect = $form.find('input[name="_wp_http_referer"]').val().match(/^.*\/(\d+)\/.*$/),
                        order_id = redirect[1];

                    if (!wpbkash.scriptloaded) {
                        window.$ = $.noConflict();
                        $.getScript( wpbkash_params.scriptUrl, function( data, textStatus, jqxhr ) {
                            wpbkash.scriptloaded = true;
                            wpbkash.wcbkashInit(parseInt(order_id), redirect[0]);
                        });
                    } else {
                        wpbkash.wcbkashInit(parseInt(order_id), redirect[0]);
                    }

                    return false;
                }
            },

            /*
             * Initiates the script and sets the triggers for the functions.
             */
            init: function () {
                $(document.body).on('checkout_error', wpbkash.errorTrigger);
                $(document.body).on('click', '.wpbkash--modal-btn', wpbkash.modalHandler);
                $(document.body).on('click', '.wpbkash--modal-wrap', wpbkash.hideModal);
                $( 'form.checkout, form#order_review' ).on( 'change', 'input[name^="payment_method"]', wpbkash.checkoutUpdate);
                $('form#order_review').on('submit', wpbkash.orderReviewSubmit);
            },
        }
        wpbkash.init();

    }
);