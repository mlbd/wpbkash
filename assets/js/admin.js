jQuery(
    function ($) {

        const wpbkash_admin = {
            bodyEl: $('body'),
            searchEl: $('.wpbkash--search-result'),
            error: true,

            /*
             * Document ready function. 
             * Runs on the $(document).ready event.
             */
            documentReady: function () {
                $(document).on('submit', '#wpbkash__search_trx', wpbkash_admin.formSubmit);
            },

        
            formSubmit: function (e) {
                e.preventDefault();
                console.log('sub');
                var current = $(this),
                    trx_field = current.find('input[name="wpbkash_trx"]'),
                    action = current.attr('action');

                if( trx_field.length === 0 || trx_field.val().length === 0 ) {
                    return false;
                }

                current.find('.wpbkash__submit_btn').addClass('loading');
                wpbkash_admin.searchEl.html('');
                $.ajax({
                    url: wpbkash_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: action,
                        trx: trx_field.val(),
                        nonce: $('#_trx_wpnonce').val()
                    },
                    success: function (response) {
                        current.find('.wpbkash__submit_btn').removeClass('loading');

                        var transaction = '<div class="wpbkash--single-trx">';
                        if( response.data && response.data.transaction ) {
                            for (var index in response.data.transaction) {
                                transaction += '<div class="wpbkash--trx-line"><strong>' + index + ':</strong> ' + response.data.transaction[index] + '</div>';
                            }
                        } else {
                            transaction += '<div class="wpbkash--trx-error">'+ response.data.message +'</div>';
                        }
                        transaction += '</div>';
                        wpbkash_admin.searchEl.append( transaction );
                    },
                    error: function () {
                        current.find('.wpbkash__submit_btn').removeClass('loading');
                    }
                });

                
            },

            /*
            * Initiates the script and sets the triggers for the functions.
            */
            init: function () {
                $(document).ready(wpbkash_admin.documentReady());
            },
        }
        wpbkash_admin.init();

    }
);