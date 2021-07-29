function log() {
    // Log Halcyon Boilerplate.
    setTimeout(
        console.log.bind(console, "%c </> with <3 by Halcyon Web Design Philippines", "background: #0399d5;color:#FFF;padding:5px;border-radius: 5px;line-height: 26px;")
    );
}

log();


/**
 * @file
 * Global utilities.
 *
 */
(($, Drupal) => {
  'use strict';

  const $win = $(window);
  const $doc = $(document);
  
  /*
   * Drupal behaviors
   */
  Drupal.behaviors.bootstrap_sub = {
    attach: (context, settings) => {
      
    }
  };

  /*
   * Window onLoad
   */
  $win.on('load', () => {
    setTimeout(() => {
      $('body').addClass('is-page-loaded').removeClass('is-page-loading');
    }, 1000);

    backToTop('#back2top');
  });

  /*
   * Window Scroll
   */
  $win.scroll(function () {
    backToTop('#back2top');
  });

  
  // Hide show back to top links.
  const backToTop = (el) => {
    if ($win.scrollTop() > 300) {
      $(el).fadeIn();
    } else {
      $(el).fadeOut();
    }

    $(el).once('backtotop').each(function () {
      $(this).click(function () {
        $('html, body').bind('scroll mousedown DOMMouseScroll mousewheel keyup', function () {
          $('html, body').stop();
        });
        $('html,body').animate({scrollTop: 0}, 1200, 'easeOutQuart', function () {
          $('html, body').unbind('scroll mousedown DOMMouseScroll mousewheel keyup');
        });
        return false;
      });
    });
  }

})(jQuery, Drupal);