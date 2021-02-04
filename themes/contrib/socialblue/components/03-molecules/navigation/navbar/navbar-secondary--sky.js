(function ($) {

  /*
  ** Behavior when the number of items in the secondary navigation
  * is too big.
   */
  Drupal.behaviors.navbarSecondaryScrollable = {
    attach: function (context, settings) {

      $(window).on('load', function () {
        if($('.layout--with-complementary')) {
          var navScroll = $('.navbar-secondary .navbar-scrollable');
          var navSecondary = navScroll.find('.nav');
          var items = navSecondary.find('li');

          if($(window).width() >= 900) {
            if (navSecondary.width() > navScroll.width()) {
              var total = 0;

              for(var i = 0; i < items.length; ++i) {
                total += $(items[i]).width();

                if((navScroll.width() -50) <= total) {
                  break;
                }

                $(items[i]).addClass('visible-item');
              }

              navSecondary.each(function () {
                var $this = $(this);

                // Create wrapper for visible items.
                $this.find('li.visible-item')
                  .wrapAll('<div class="visible-list"></div>');

                // Create wrapper for hidden items.
                $this.find('li:not(.visible-item)')
                  .wrapAll('<div class="hidden-list card" />');

                // Add caret.
                $this.append('<span class="caret"></span>');

                var hiddenList = $this.find('.hidden-list');
                var cart = $this.find('.caret');

                cart.on('click', function () {
                  hiddenList.slideToggle(300);
                  $(this).toggleClass('active');
                });

                $(document).on('click', function(event) {
                  if ($(event.target).closest('.navbar-secondary').length) return;
                  hiddenList.slideUp(300);
                  cart.removeClass('active');
                  event.stopPropagation();
                });
              });
            } else {
              navSecondary.css('display', 'flex');
            }
          }
        }
      });

    }

  };

})(jQuery);
