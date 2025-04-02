/*global VuFind */
VuFind.register('hanembed', function HanEmbed() {

  function _loadHanId($target, json, alt) {
    $target.addClass('ajax_availability');
    var url = VuFind.path + '/AJAX/JSON?' + $.param({
      method: 'getHanId',
      params: json,
      alternative: alt
    });
    $.ajax({
      dataType: 'json',
      url: url
    })
      .done(function getHanIdDone(response) {
        $target.removeClass('ajax_availability').empty().append(response.data.html);
      })
      .fail(function getHanIdFail(response, textStatus) {
        $target.removeClass('ajax_availability').addClass('text-danger').empty();
        if (textStatus === 'abort' || typeof response.responseJSON == 'undefined') { return; }
        $target.append(response.responseJSON.data);
      });
  }

  function embedHanId(el) {
    var element = $(el);
    // Extract the OpenURL associated with the clicked element:
    var params = element.children('span.hanInfo:first').attr('title');
    var alternative = element.children('span.hanAlternative:first').attr('title');

    // Hide the controls now that something has been clicked:
    var controls = element.parents('.hanControls');
    controls.removeClass('hanEmbed').addClass('hidden');

    // Locate the target area for displaying the results:
    var target = controls.next('div.resolver');

    // If the target is already visible, a previous click has populated it;
    // don't waste time doing redundant work.
    if (target.hasClass('hidden')) {
      _loadHanId(target.removeClass('hidden'), params, alternative);
    }
  }

  // Assign actions to the OpenURL links. This can be called with a container e.g. when
  // combined results fetched with AJAX are loaded.
  function init(_container) {
    var container = $(_container || 'body');
    // assign action to the openUrlEmbed link class
    container.find('.hanEmbed a').off('click').on("click", function hanIdEmbedClick() {
      embedHanId(this);
      return false;
    });

    if (VuFind.isPrinting()) {
      container.find('.hanEmbed.hanAutoload a').trigger('click');
    } else {
      VuFind.observerManager.createIntersectionObserver(
        'hanEmbed',
        embedHanId,
        container.find('.hanEmbed.hanAutoload a').toArray()
      );
    }
  }

  return {
    init: init,
    embedHanId: embedHanId
  };
});
