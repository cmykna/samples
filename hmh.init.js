(function ($, H) {
  var figures = function () {
      $('img[class|="image"], img[class|="video"]').each(function () {
        var class_params = this.className.split('-');
        var class_string, figure_attrs, fancybox_anchor, wrapper, fb_href;
        var $img = $(this);

        // Build a new class string for figure elements. Pretty simple: 
        // 'image-whatever' is now 'feature-image pull-whatever', 'fancybox'
        // is the same, and 's2l' is now 'small-to-large'.
        class_string = [
          'feature-' + class_params[0]
        , 'pull-' + class_params[1]
        , class_params[2] && 'fancybox'
        , class_params[3] && 'small-to-large'
        ].join(' ').replace(/^\s+|\s+$/g,'');

        figure_attrs = [
          'class="' + class_string + '"'
        , 'width="' + this.width + '"'
        , 'height="' + this.height + '"'
        ].join(' ');

        // If we found .fancybox or .s2l, we need to wrap the image in a
        // fancybox anchor in addition to a figure tag.
        if (class_params.length > 2) {
          // Small-to-large fancybox images need '-large' appended to the src.
          fb_href = (class_params[3] === 's2l') ? 
            this.src.replace(/\.(gif|jpg|png)$/, "-large.$1") : this.src;

          fancybox_anchor = '<a class="fancybox" href="' + fb_href + '"/>';

          wrapper = [
            '<figure '
          , figure_attrs, '>'
          , fancybox_anchor
          , '<span class="enlarge"></span></figure>'
          ].join('');
        // Otherwise, slap a figure tag around it and we're dunzo.
        } else { wrapper = '<figure ' + figure_attrs + ' />'; }

        // Wrap with whatever we just put together, then 
        // ditch any old classes that might still be there.
        $img.wrap(wrapper).removeClass();
      });

    $(".fancybox .fancybox, .fancybox-small-large .fancybox").fancybox();
  };

  var targets = function () {
    var $slides = $('.coda-slider')
      , $buckets = $('.three-space-bucket');

    // Construct click event for a bucket or slide, using the
    // href from the first anchor tag found. Subsequent anchor 
    // elements are ignored.
    function makeTarget () {
      var $el = $(this)
        , $a = $el.find('a').first()
        , href = $a.attr('href')
        , ext = ($a.attr('rel') !== 'external');

      return (ext && (window.location.href = href)) || window.open(href);
    }

    // Since we're using event delegation here, we no longer
    // have to remove the original <a> element in the slide/bucket
    // to avoid double-dipping into rel="external" behavior.
    // Any clicks are handled by the containers
    // that we're delegating to instead.
    $slides.on('click', '.panel', makeTarget);
    $buckets.on('click', 'ul li', makeTarget);

    $('#coda-slider').codaSlider(H.config.slide_config);
  };

  return HMH.init = {
    figures: figures
  , targets: targets
  }

})(jQuery, HMH);