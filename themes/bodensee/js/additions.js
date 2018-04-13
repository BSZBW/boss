/*
 * showmore links
 * @returns {undefined}
 */
function showmore() {
    $('.showmore').click(function(e) {
        var id = $(this).attr('id').split('-')[1];
        $('#showmore-items-'+id+' .showmore-item').removeClass('hidden');
        $(this).remove();
        e.preventDefault();
        return false;
    })
}

//function sidebarOffcanvas() {
//    $('#sidebar-offcanvas-trigger').click(function(){
//        $(this).toggleClass('active');
//        $('.container .sidebar').toggleClass('offcanvas-active');
//        $('body').toggleClass('overflow');
//    });
//}

function bootstrapTooltip() {

       $("body").tooltip({ 
          selector: '[data-toggle=tooltip]',
          delay: {
              'show': 500,
              'hide': 100,
          }
      });    
//    }
}

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
            var imghtml = '<div class="text-center"><img src="'+imgurl+'" alt="Large Preview" /></div>';
            $('#modalTitle').remove();
            $modal.empty().append(imghtml);
            $('#modal').modal('show');
});    
}
function remoteModal() {
    $('.modal-remote').click(function(e) {
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

function illFormLogic() {
     //switch places when changing library
    $('input[name=Sigel]').change(function() {
        var attrId = $(this).attr('id').split('-');
        var libid = attrId[2];
        // Hide all radios
        $('.library-places').find('.place').addClass('hidden').find('input')
            .prop('checked', false);
        // show the correct ones
        $('.library-places').find('#library-places-'+libid)
            .removeClass('hidden').find('input').first().prop('checked', true);      
        
        
    });
    // open/close panel according to radio button
    $('input[name=Bestellform]').change(function(e) {
        if ($(this).attr('id') == 'ill-lend') {
            $('#panel-paperdata .panel-collapse').collapse('hide');            
        } else {
            $('#panel-paperdata .panel-collapse').collapse('show');          
    
        }
    });    
    // switch radio button according to panel
    $('#panel-paperdata').on('hidden.bs.collapse', function (e) {
        $('#ill-lend').prop('checked', true);
    })
    $('#panel-paperdata').on('show.bs.collapse', function (e) {
        $('#ill-copy').prop('checked', true);         
    })
    
    
    $('.form-ill').validator({
        disable: false,
        focus: true,
        custom: {
            costs: function($el) {
                var costs = $el.val();
                if((costs < 8 && costs > 0) || costs < 0 ) {
                    return false;            
                } else {
                   return true;        
                } 
            }
        },
        errors: {
            costs: 'Costs must not be between 0 and 8. ',
        }
    }).on('submit', function (e) {

        if (!e.isDefaultPrevented()) {
            // everything is validated, form to be submitted
            $('#collapseFour').collapse('show');
            $('#collapseThree').collapse('show');
            $(this).find('[type=submit]').addClass('disabled')
                    .parent().append('<i class="fa fa-spinner fa-spin"></i>');           
        }
            
       
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
 * @returns {undefined}
 */
function avoidEmptySearch() {
     var $tabs = $('#searchForm .nav-tabs');
     $tabs.find('a').click(function(e) {
        e.preventDefault()
        var lookfor = $('#searchForm_lookfor').val();
        var href = $(this).attr('href');
        if (lookfor.length === 0) {
            href = href.replace('Results', 'Home');             
        } else {
            href = href.replace('Home', 'Results')+'&lookfor='+lookfor;     
        }
        // this is like clicking the manipulated link
        window.location.href = href;    
        
     });

 }
 function duplicates() {
     $('.duplicates-toggle').click(function(e){
        $(this).parent().toggleClass('active');
        $(this).children('i').toggleClass('fa-arrow-down');
        $(this).children('i').toggleClass('fa-arrow-up');
     }); 
 }

$(document).ready(function() {
  avoidEmptySearch();
  externalLinks();
  bootstrapTooltip();
//  sidebarOffcanvas();
  modalPopup();
  keyboardShortcuts();
  remoteModal();
  showmore();  
  duplicates();
});