jQuery(function($) {
   
    window.quotePopup = quotePopup = {
        init() {
            setTimeout(function() {
                if($('a.display-list-link').length) {
                    let productQuoteCon = $('.poduct-quote-container'),
                    items = $(productQuoteCon).find('.product-quote-items');
                    $('a.display-list-link').on('click', function(e) {
                        $(items).find('ul').addClass('open');
                        e.preventDefault();
                    });
                }
            }, 2500);
        },
        close() {
            
            $('.close-quote-gen-overlay').click(function(event) {
                if ($('.quote-gen-overlay').length) {
                    $('body').find('.quote-gen-overlay').hide();
                    $('body').find('#k-overlay').hide();
                }
            });
        }
    }

});