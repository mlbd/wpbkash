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
                $(document).on('submit', '#wpbkash__search_form', wpbkash_admin.formSubmit);
            },

        
            formSubmit: function (e) {
                e.preventDefault();
                var current = $(this),
                    getData = current.serializeArray();

                getData.push({
                    name: 'action',
                    value: current.attr('action')
                });

                current.find('.wpbkash__submit_btn').addClass('loading');
                wpbkash_admin.searchEl.html('');
                $.ajax({
                    url: wpbkash_params.ajax_url,
                    type: 'POST',
                    data: getData,
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
                        // current[0].reset();
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