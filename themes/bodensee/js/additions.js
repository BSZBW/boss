function   performMark() {
    var lookfor = '';
    var input_simple = $('#searchForm_lookfor').val();
    var input_adv = $('span.adv_lookfor').text();
    if (typeof input_simple !== 'undefined' && input_simple.trim() !== '') {
        lookfor = input_simple;
    } else if (typeof input_adv !== 'undefined'  && input_adv.trim() !== '') {
        lookfor = input_adv;
        var mapObj = {
            "Alle Felder:":"", "All Fields:":"",
            "Titel:":"", "Title:":"",
            "Verfasser:":"", "Author:":"",
            "Schlagwort:":"", "Subject:":"",
            "Verlag:":"", "Publisher:":"",
            "Serie:":"", "Series:":"",            
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
    $('a.title,a.author,span[property]').mark(terms, {        
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

function moreChildren(id) {
  $('.' + id).removeClass('hidden');
  $('#more-' + id).addClass('hidden');
  return false;
}

function lessChildren(id) {
  $('.' + id).addClass('hidden');
  $('#more-' + id).removeClass('hidden');
  return false;
}  

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

      $('[data-toggle="tooltip"]').tooltip({
          delay: {
              'show': 500,
              'hide': 100
          }
      });    
}

/*
* view covers in modal popup
*/
function modalPopup() {
    
    // prevent default cover placeholders from being clickable
    var img = $('.modal-popup.cover').find('img');
    if (img.innerWidth() === 60 || img.innerHeight() === 60) {
        img.parent().removeClass('modal-popup');
        img.parent().css('cursor', 'default');
    }
    
    $('.modal-popup.cover').click(function(e) {        
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
        } else {
            href = href.replace('Home', 'Results')+'&lookfor='+lookfor;     
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

function inputLength(selector) {
    var val = '';
    $(selector).each(function() {
        val += $(this).val().replace( /[\*\s]/gi,"" );        
    });
    return val.length;
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
   
  })
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
    $('.datepicker').datepicker({
        language: $('html').attr('lang'),
        weekStart: 1,
        format: 'dd.mm.yyyy',
        allowInputToggle: true,
        orientation: 'bottom'
    });
    // workaround: Addon does not open the datepicker by default
    $('.input-group.date .input-group-addon').click(function(){
       $(this).parent().find('input.datepicker').datepicker('show'); 
    });      

}

function typeaheadLibraries() {    
    var baseurl = VuFind.path + '/AJAX/JSON?method=';
    $('.typeahead').typeahead({
        items: 'all',        
        source: function (val, process) {
            return $.ajax({
                url: baseurl+'librariesTypeahead&q='+val,
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
                    window.location = VuFind.path;        
                }
            });
        }

    });    
    
}

function librarySelect() {
    $('.library-selector a').click(function(e) {
        var isil = $(this).attr('data-isil');
        $.ajax({
                url:   VuFind.path + '/AJAX/JSON?method=saveIsil&isil='+isil,
                method: 'GET',
                dataType: 'json',                
                success: function() {
                    window.location = VuFind.path;            
                }
        });
        e.preventDefault();
        
    });
}

/*
* this is executed after site is loaded
* main loop
*/

$(document).ready(function() {
    avoidEmptySearch();
    externalLinks();
    bootstrapTooltip();
    modalPopup();
    if ($.fn.typeahead) {
        typeaheadLibraries();      
    }
    librarySelect();
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
});
