/**
 *
 * main method for ill form
 */

function illFormLogic() {

    $('input[name=Bestellform]').change(function() {
        changeRequiredCopy($(this));
        $('#form-ill').validator('update');
    });

    // set the first place to checked
    if (!$("input[name='AusgabeOrt']:checked").val()) {
        $('.place input').first().prop('checked', true);
    }

    // checks on submit
    $('#form-ill').on('submit', function (e) {

        $('#form-ill').validator('update');
        changeRequiredCopy($('input[name=Bestellform]:checked'));


        var $errors = $(this).find('.has-error');
        if ($errors.length > 0) {
            // open panels with errors
            $errors.parent().parent().collapse('show');
            window.scrollTo(0, 0);

            if ($('.flash-message').length === 0) {
                $('#form-ill').prepend($('<div>', {
                    class: 'flash-message alert alert-danger',
                    text: VuFind.translate('illFormError')
                }));
            }
        }
        if (!e.isDefaultPrevented()) {

            // clear the paper data fields if borrowing an item
            if ($('input[name=Bestellform]:checked').val() === 'Leihen') {
                $('#panel-paperdata').find('input').val("");
            }

            // everything is validated, form to be submitted
            $(this).find('[type=submit]').addClass('disabled')
                    .parent().append('<i class="fa fa-spinner fa-spin"></i>');
        }
    });
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

}
/*
 * This method switched the required state of copy form fields
 * it must be called at
 * - document ready
 * - change at the radios
 * - before submit
 * @param $actor the element that was clicked (the radio)
 */

function changeRequiredCopy($actor) {

    if ($actor.attr('id') === 'ill-lend') {
        $('#panel-paperdata').hide('slow');
    } else {
        $('#panel-paperdata').removeClass('hidden').show('slow');
    }

    var requiredCopy = [
        'AufsatzAutor',
        'AufsatzTitel',
        'Seitenangabe'
    ];

    requiredCopy.forEach(function (name) {
        // get the form group div surrounding the input
        var $required = $('input[name='+name+']').parent().parent();
        if ($required.length > 0) {
                if ($actor.attr('id') === 'ill-lend') {
                $required.removeClass('required show').find('input')
                                .removeAttr('required')
                                .attr('data-validate', 'false');
                } else if($actor.attr('id') === 'ill-copy') {
                $required.addClass('required show').find('input')
                            .attr('required', 'true')
                            .attr('data-validate', 'true');
                }
        }
    });
}

function appendValidator() {
    $('#form-ill').validator({
        disable: false,
        focus: false,
        custom: {
            seitenangabe: function($el) {
                return validateCopy($el);
            },
            bestellform: function($el) {
                return validateCopy($el);
            },
            costs: function($el) {
                var costs = $el.val();
                if((costs < 8 && costs > 0) || costs < 0 ) {
                    return VuFind.translate('illCostsError');
                }
            },
            ejahr: function($el) {
                return validateYear($el);
            },
            jahrgang: function($el) {
                return validateYear($el);
            },
            jahr: function($el) {
                return validateYear($el);
            },
        }
    });
}

function validateYear($el) {1;
    var year = $el.val();
    if (!/^\d\d\d\d$/g.test(year)) {
        return VuFind.translate('illYearError')
    }
}

function validateCopy($el) {
    // For copies, we must enter something in the copies sectio
    if ($('input[name=Bestellform]:checked').val() === 'Kopie') {

        // we don't need this if there are required fields
        var $required = $('#panel-paperdata').find('.form-group.required');
        if ($required-length === 0) {

            // count sum of input lengths
            var copyInputLength = 0;

            $('#panel-paperdata input').each(function(k){
                copyInputLength = copyInputLength + $(this).val().length;
            });
            if (copyInputLength === 0 ) {
                $('#panel-paperdata .form-group').addClass('has-error');
                $('#panel-paperdata .panel-collapse').collapse('show');
                return $el.attr('data-error');
            }
        }
    }
}

/**
 * Bestellform is not yet initialized, then choose radio according to the
 * format
 */
function copyLend() {

    // if there is only one radio available, select this one
    if ($('input[name=Bestellform]').length === 1) {
        $('input[name=Bestellform]').prop('checked', true);
    }

    // if there are two radios and nothing selected yet
    if ($('input[name=Bestellform]:checked').length === 0
        && $('input[name=Bestellform]').length === 2) {
        var format = $('#form-ill').attr('data-format');
        if ($.inArray(format, ['book']) > -1) {
            $('#ill-lend').prop('checked', true);
        } else if ($.inArray(format, ['journal', 'article-book', 'article', 'ebook']) > -1){
            $('#ill-copy').prop('checked', true);
        }

    }
}

function resigningForm() {
    $('#reset-network-selection').click(function(e){
        var $elems = $(this).parent().parent().find('.active');
        $elems.each(function(index){
            setTimeout(function() {
                $(this).removeClass('active').find('input').prop('checked', false);
            }.bind(this), 10);
        });
        e.preventDefault();
    });
    $('input[type=reset]').click(function(e) {
        $('form input[type=text]').val('');
        $('#reset-network-selection').trigger('click');
        e.preventDefault();
    });
}


$(document).ready(function() {

    appendValidator();
    datepicker();
    copyLend();
    illFormLogic();
    resigningForm();
    changeRequiredCopy($("input[name='Bestellform']:checked"));

});