function   performMark(selector = '.markjs') {
    var lookfor = '';
    var input_simple = $('#searchForm_lookfor').val();
    var input_adv = $('li.adv_lookfor').text();
    if (typeof input_simple !== 'undefined' && input_simple.trim() !== '') {
        // remove boole expressions with AND OR NOT when capitalletters and seperated from words
        lookfor = input_simple.replace(/((\sOR\s))|((\sAND\s))|((\sNOT\s))/g, ' ');
        // remove single numbers - they will appear everywhere
        lookfor = lookfor.replace(/\s\d\s/g, ' ');
    } else if (typeof input_adv !== 'undefined'  && input_adv.trim() !== '') {
        lookfor = input_adv;
        var mapObj = {
            "Alle Felder:":"", "All Fields:":"",
            "Titel:":"", "Title:":"",
            "Verfasser:":"", "Author:":"",
            "Schlagwort:":"", "Subject:":"",
            "Verlag:":"", "Publisher:":"",
            "UND":"", "AND":"",
            "NICHT":"", "NOT":"",
            "ODER":"", "OR":""
        };
        var re = new RegExp(Object.keys(mapObj).join("|"),"g");
        lookfor = lookfor.replace(re, function(matched){
            return mapObj[matched];
        });
    }
    lookfor = lookfor.replace(/[\/\[;\.,\\\-\–\—\‒_\(\)\{\}\[\]\!'\"=]/g, ' ');
    terms = lookfor.split(' ').filter(function(el) { return el; });
    $(selector).mark(terms, {
        "wildcards": "enabled",
        "accuracy": "partially",
        "synonyms": {
            "ss": "ß",
            "ö": "oe",
            "ü": "ue",
            "ä": "ae"
        }
    });
}

/**
 * Deprecated, don't use this anymore, use bootstrap collapse
 */
function showmore() {
    $('.showmore').click(function(e) {
        var id = $(this).attr('id').split('-')[1];
        $('#showmore-items-'+id+' .showmore-item').removeClass('hidden');
        $(this).remove();
        e.preventDefault();
        return false;
    });
}

function bootstrapTooltip() {
      $('body').tooltip({
          delay: {
              'show': 500,
              'hide': 100
          },
          selector: '[data-toggle="tooltip"]'
      });
}

/*
* view covers in modal popup
*/
function modalPopup() {

    $('.record').on('click', '.modal-popup.cover', function(e) {
        var imgurl = $(this).attr('data-img-url');
        var $modal = $('#modal .modal-body');
        var imghtml = '<div class="text-center"><img src="'+imgurl+'" class="img-responsive center-block" alt="Large Preview" /></div>';
        $('#modalTitle').remove();
        $modal.empty().append(imghtml);
        $('#modal').modal('show');
});
}

/*
* Open a remote url inside a modal
* take care of non https sites which break https on boss
*/
function remoteModal() {
    $('body').on('click', '.modal-remote', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var size = $(this).attr('data-size');
        if(size === undefined) {
            size = 'lg';
        }
        var name = $(this).attr('data-name');
        if(name === undefined) {
            name = "BOSS Modal";
        }

        var html = '<iframe width="100%" style="min-height: 600px;" src="'+url+'" seamless="seamless" name="'+name+'"></iframe>';
        $('#modal .modal-body').empty().append(html);
        $('#modal .modal-dialog').addClass('modal-'+size);
        $('#modal').modal('show');
        //This prevents the default Link behavior (open new tab)
        return false;
    });
}

function externalLinks() {
    $(document).on("click", "a.extern, a.external, .external a, .extern a,  .authorbox a, .Access.URL a", function() {
            window.open($(this).attr("href"), $(this).attr('target'));
            return false;
    });
}

/**
 * Handle arrow keys to jump to next record
 * @returns {undefined}
 */
function keyboardShortcuts() {
    var $searchform = $('#searchForm_lookfor');
    if ($('.pager').length > 0) {
        $(window).keydown(function(e) {
            if (!$searchform.is(':focus')) {
            var $target = null;
            switch (e.keyCode) {
              case 37: // left arrow key
                $target = $('.pager').find('a.previous');
                if ($target.length > 0) {
                    $target[0].click();
                    return;
                }
                break;
              case 38: // up arrow key
                if (e.ctrlKey) {
                    $target = $('.pager').find('a.backtosearch');
                    if ($target.length > 0) {
                        $target[0].click();
                        return;
                    }
                }
                break;
              case 39: //right arrow key
                $target = $('.pager').find('a.next');
                if ($target.length > 0) {
                    $target[0].click();
                    return;
                }
                break;
              case 40: // down arrow key
                break;
            }
          }
        });
      }
    }
/**
 * Prevent the searchbox from triggering an empty search which is slow.
 * Add a popover to let the user know
 * @returns {undefined}
 */
function avoidEmptySearch() {

     var $tabs = $('#searchForm .nav-tabs');
     var $input = $('#searchForm_lookfor');

     // limit to stop search
     var limit = 2;

     $tabs.find('a').click(function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var lookfor = $input.val();

        if (lookfor.length === 0) {
            href = href.replace('Results', 'Home');
            href = href.replace('/EDS/Search', '/EDS/Home');
            href = href.replace('/Summon/Search', '/Summon/Home');
        } else {
            href = href.replace('Home', 'Results');
        }
        // this is like clicking the manipulated link
        window.location.href = href;

     });
     $('#searchForm').submit(function(e) {
        if ($input.val().replace( /[\*\s]/gi,"" ).length <= limit) {
             $input.attr('data-placement', 'bottom');

             $input.popover('show');
             return false;
        } else {
             $input.popover('hide');
             return true;
        }

     });
     $input.on('change keydown paste input', function(e) {
         if ($input.val().replace( /\W*/gi,"" ).length > limit) {
             $input.popover('hide');
         }
     });

}

function inputLength(selector) {
    var val = '';
    $(selector).each(function() {
        val += $(this).val().replace( /[\*\s]/gi,"" );
    });
    return val.length;
}

function checkAdvSearch() {
    var limit = 2;
    var selector = '.adv-term-input.no-empty-search';
    if ($(selector).length === 0) return true;
    $('#advSearchForm').on('submit', function(e) {
        if (inputLength(selector) <= limit ) {
            return false;
        }
        return true;
    });
}
/*
* Duplicatea button
*/
function duplicates() {
    $('.duplicates-toggle').click(function(e){
       $(this).parent().toggleClass('active');
       $(this).children('i').toggleClass('fa-arrow-down');
       $(this).children('i').toggleClass('fa-arrow-up');
    });

    // handle checkbox to enable/disable grouping
    $('#dedup-checkbox').change(function(e) {
        var status = this.checked;
        $.ajax({
           dataType: 'json',
           method: 'POST',
           url: VuFind.path + '/AJAX/JSON?method=dedupCheckbox',
           data: { 'status': status },
           success: function() {
               // reload the page
               window.location.reload(true);
           }

  });
     });
 }

/*
* Tooltips for OpenURL links
*/
function openUrlTooltip() {

    var htmlcontent = '<p style="text-align: left; margin-bottom: 0">';
    htmlcontent += '<img src="/themes/bodensee/images/jop_online.png" alt="JOP nline"/>&nbsp;'+ VuFind.translate('openurl_tooltip_left')+'<br/>';
    htmlcontent += '<img src="/themes/bodensee/images/jop_print.png" alt="JOP nline"/>&nbsp;'+ VuFind.translate('openurl_tooltip_right')+'<br/>';
    htmlcontent += '<i class="fa fa-square text-success"></i> '+VuFind.translate('openurl_tooltip_green')+'<br/>';
    htmlcontent += '<i class="fa fa-square text-warning"></i> '+VuFind.translate('openurl_tooltip_yellow')+'<br/>';
    htmlcontent += '<i class="fa fa-square text-danger"></i> '+VuFind.translate('openurl_tooltip_red')+'<br/>';
    htmlcontent += '</p>';

    $('.openUrlControls .imagebased').tooltip({
        title: htmlcontent,
        html: true,
        placement: 'right',
        toggle: 'hover focus',
        show: 500,
        hide: 100,
    });
 }

 function searchclear() {
     $('.searchclear').click(function() {
        $(this).prev().val('');
     });
 }

 /**
  * bootstrap datepicker
  * depends on two js files -  you need to add then in the templates
  *
  */
function datepicker() {
    var date = new Date().toLocaleDateString();
    console.log(date);
    $('.datepicker').datepicker({
        language: $('html').attr('lang'),
        weekStart: 1,
        format: 'dd.mm.yyyy',
        allowInputToggle: true,
        orientation: 'bottom'
    });
    $('.input-daterange').datepicker({
            language: $('html').attr('lang'),
            weekStart: 1,
            format: 'dd.mm.yyyy',
            endDate: date,
            allowInputToggle: true,
            orientation: 'bottom',
            maxViewMode: 'years',
            keepEmptyValues: false,
        forceParse: true,
    });
    // workaround: Addon does not open the datepicker by default
    $('.input-group.date .input-group-addon').click(function(){
       $(this).parent().find('input.datepicker').datepicker('show');
    });

}

function manageActiveTab() {

    var id = $('.searchForm .nav-tabs li.active').attr('id');

    if (id === 'solr' || id === 'solr:filtered2') {
        console.info('ILL tab is active');
        if ($('.record-tabs a.interlibraryloan').length > 0) {
            // jquery can't trigger a click on a bare text node.
            // Pure javaSCript can.
            //$('.record-tabs a.interlibraryloan')[0].click();
            var tabid = 'interlibraryloan';
            var setHash = !$('.record-tabs').parent().hasClass('initiallyActive')
            var newTab = getNewRecordTab(tabid).addClass('active');
            ajaxLoadTab(newTab, tabid, setHash);
        }
    } else if (id === 'solr:filtered1' || id === 'solr:unfiltered' ) {
        console.info('Local tab is active');
        $('.record-tabs a.interlibraryloan').parent().hide();
    }
}

/**
 * Typeahead selection of libraries in home page of ill portal.
 *
 */
function typeaheadLibraries() {

    if ($.fn.typeahead) {

        var baseurl = VuFind.path + '/AJAX/JSON?method=';
        // Workaround for a bug in typeahead.js
        setTimeout(function () {$('.typeahead').focus();}, 100);

        $('.typeahead').typeahead({
            items: 'all',
            minLength: 1,
            source: function (val, process) {
                return $.ajax({
                    url: baseurl+'librariesTypeahead&boss=0&q='+val,
                    method: "GET",
                    dataType: 'json',
                    success: function(data) {
                        return process(data.data);
                    }
                });

            },
            afterSelect: function(item) {
                $.ajax({
                    url: baseurl + 'saveIsil&isil='+item.id,
                    method: 'GET',
                    dataType: 'json',
                    success: function() {
                        window.location = $('#typeahead-referer').val();
                    }
                });
            }
        });
        // if the typeahead is hidden and the button is clicked, set the focus
        $('#library-typeahead').on('shown.bs.collapse', function() {
            $('.typeahead').focus();
        });
    }


}

/**
 * Switches between texts on collapse control buttons
 * Alternative text should be in the data-alttext attribute
 *
 */
function textToggle() {
    $(document).on('click', '.text-toggle', function(e) {
        var oldtext = $(this).find('.text').text();
        var $icon = $(this).find('.fa').toggleClass('rotate');
        var newtext = $(this).attr('data-alttext');
        $(this).find('.text').text(newtext);
        $(this).attr('data-alttext', oldtext);
        e.preventDefault()
    });
}

/**
 * Opens links in a popup window. Name and width/height can be set via data
 * attributes
 *
 */

function openInPopup() {
    $(document).on('click', '.open-popup', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');

        var name = $(this).attr('data-name');
        name = name == 'undefined' ? name : 'BOSS';

        var width = $(this).attr('data-width');
        width = width == 'undefined' ? width : '1024';

        var height = $(this).attr('data-height');
        height = height == 'undefined' ? height : '580';

        var options = [
            'width='+width,
            'height='+height,
            'location=no',
            'menubar=no',
            'toolbar=no',
            'status=no',
            'scrollbars=yes',
            'directories=no',
            'resizable=yes',
            'alwaysRaised=yes',
            'hotkeys=no',
            'top=0',
            'left=200',
            'screenY=0',
            'screenX=200'
        ];

        window.open(href, name, options.join(',')).focus();

    });
}

function tableSorter() {
    if ($.fn.tablesorter) {
        $('.tablesorter').tablesorter({
             sortList : [[1,1]]
        });
    }
}

function bootstrapSwitch() {
    if ($.fn.bootstrapSwitch) {
        $('[data-toggle="switch"]').bootstrapSwitch();
    }
}

/**
 * Copy text to clipboard
 */
function copyToClipboard() {

    var clipboard = new ClipboardJS('.copy-clipboard-toggle');
    clipboard.on('success', function(e) {
        console.info(e.text + ' copied to clipboard');

        var icon = $('#' + e.trigger.id + ' i').attr('class');
        var icons = icon.split(' ');
        $('#' + e.trigger.id + ' i').removeClass('fa-check text-success').addClass(icons[1]);
        $('#' + e.trigger.id + ' i').removeClass(icons[1]).addClass('fa-check text-success');

        setTimeout(function () {
            $('#' + e.trigger.id + ' i').removeClass('fa-check text-success').addClass(icons[1]);
        }, 2000)

        e.clearSelection();
    });
}

function deleteInput() {
    $('.delete-input').click(function() {
        var target = $(this).attr('data-target');
        $(target).val('');
    })
}

function recordCoverAjax() {
    var $covers = $('.cover-container').each(function() {
        var $container = $(this);
        var url = $container.attr('data-cover');

        if (url.length > 0 && Utils.isScrolledIntoView($container)) {
           // remove attribute to avoid duplicate loading
            $container.attr('data-cover', '');
            $.ajax({
                method: 'GET',
                accepts: 'image/jpeg',
                dataType: 'text',
                url: VuFind.path + url+'&base64=true',
                cache: true,
                success: function (imagedata) {
                    // recognize 1x1 px placeholder gif
                    if (imagedata.length > 56) {
                        $container.find('svg').attr('style', 'display: none');
                        var base64 = 'data:image/jpeg;base64,'+imagedata;
                        $container.find('img').attr('src', base64).removeClass('hidden');
                        // on detail view set the modal popup
                        if ($('body').hasClass('template-dir-record') && $container.parent().hasClass('cover')) {
                            $container.parent().addClass('modal-popup');
                            $container.parent().attr('href', '#');

                        }
                    }
                },
            });
        }
    });
}


class Utils {
    static isScrolledIntoView(elem) {
        var docViewTop = $(window).scrollTop();
        var docViewBottom = docViewTop + $(window).height();

        var elemTop = $(elem).offset().top;
        var elemBottom = elemTop + $(elem).height();

        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    }
}


/*
* this is executed after site is loaded
* main loop
*/

$(document).ready(function() {
    recordCoverAjax();
    manageActiveTab();
    avoidEmptySearch();
    externalLinks();
    bootstrapTooltip();
    modalPopup();
    typeaheadLibraries();
    tableSorter();
    bootstrapSwitch();
    keyboardShortcuts();
    remoteModal();
    duplicates();
    showmore();
    searchclear();
    $('[data-toggle="popover"]').popover({
        trigger: 'click focus'
    });
    if ($.fn.mark) {
        performMark();
    }
    openUrlTooltip();
    checkAdvSearch();
    textToggle();
    openInPopup();
    copyToClipboard();
    deleteInput();

    $(document).on('scroll', function() {
        //console.log('scroll');
        recordCoverAjax();
    });
});
