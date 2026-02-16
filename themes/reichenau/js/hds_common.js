/**
 * Created by sebastian on 04.07.16.
 */

userIsLoggedIn = false;
let html       = $("html");

function getInstanceName() {
    return window.instanceName ?? 'instanceNotFound';
}

/*
if (document.readyState === "loading") {
    document.addEventListener('DOMContentLoaded', togglesidebar);
    document.addEventListener('DOMContentLoaded', processRVK);
} else {
    togglesidebar();
    processRVK();
}
*/

$(document).ready(function () {
    setupStickyElements();

    // initializing all popovers in the whole system:
    $('[data-toggle="popover"]')
        .popover({
            html    : true,
        });

    $('.list-group.facet .list-group-item.toggle-more').on("click", function (event) {
        event.preventDefault();
        let id = $(this).data("group");
        $('.'      + id).removeClass('hidden');
        $('#more-' + id).addClass('hidden');
        return false;
    });

    $('.list-group.facet .list-group-item.toggle-less').on("click", function (event) {
        event.preventDefault();
        let id = $(this).data("group");
        $('.'      + id).addClass('hidden');
        $('#more-' + id).removeClass('hidden');
        return false;
    });

    String.prototype.replaceAll = function (search, replacement) {
        let    target = this;
        return target.split(search).join(replacement);
    };

    // keyboard switch
    $('.keyboard-switch').on("click", function (event) {
        event.preventDefault();
        let $langKeyboard = $('#langkeyboard'),
            code          = $(this).data("keyboardcode");
        $('#mykeyboard').val(code);
        switch(code) {
            case 'none':
                $('#langkeyboard').popover('destroy');
                break;
            case 'de':
                $langKeyboard.keyboard_de();
                break;
            case 'en':
                $langKeyboard.keyboard_en();
                break;
            case 'he':
                $langKeyboard.keyboard_he();
                break;
            case 'ar':
                $langKeyboard.keyboard_ar();
                break;
            default:
        }
    });


    // support "jump menu" dropdown boxes
    $('select.jumpMenu').on("change", function () {
        let $form = $(this).parent('form');
        if ($form.length < 1) {
            $form = $(this).parentsUntil('form').parent();
        }
        $form.submit();
    });

    $('#select-search-handler li a').on("click", function (event) {
        let value = $(this).data('value'),
            label = $(this).data('label');
        $('#search-option-type').val(value);
        $('#selected-handler').text(label);
        //$('#select-search-handler li.active a span.hds-icon-check').remove();
        //$('#select-search-handler li.active a').prepend($('<span class="hds-icon-check-empty"></span>'));
        $('#select-search-handler li.active').removeClass('active');
        $(this).parent().addClass('active');
        //$(this).find('span.hds-icon-check-empty').remove();
        //$(this).prepend($('<span class="hds-icon-check"></span>'));
        return event;
    });

    let urls = {};
    $("#search-tabs > li > a").each(function(){
        let  id  = $(this).data('id');
        urls[id] = $(this).attr('href');
    });
    Object.keys(urls).forEach(function(key) {
        let url = "";
        if (typeof urls[key] != "undefined" && urls[key].match(/lookfor/i)) {
            if (key === "EDS") {
                url = urls[key].replace("Search", "ajax");
            } else if(key == "Solr") {
                url = urls[key].replace("Results", "ajax");
            } else {
                return;
            }
        }
        if (url !== "") {
            let localization = VuFind.userLang === "de"
                ? "de-DE"
                : "en-GB";
            $.getJSON(url, function (data) {
                var hitNumber = Number(data["data"]);
                var hitNumberString = formatNonEmpty(
                    hitNumber.toLocaleString(localization),
                    (input) => { return "(" + input + ")";}
                );
                addToCombined(hitNumber);
                $("#" + key + " > a > span")
                    .append(hitNumberString);
                $("#" + key + " > a > small").append(hitNumberString);
            });
        }
    });

    // reset handler that clears the advanced search form
    $('form#advSearchForm button:reset').on("click", function (event) {
        event.preventDefault();
        $('form')
            .find(':radio, :checkbox').removeAttr('checked').end()
            .find('textarea, :text').val('')
            .find('.multiselect option:selected').each(function() {
                $(this).prop('selected', false);
            });
        $('select[multiple="multiple"]')
            .multiselect('destroy')
            .multiselect({enableFiltering: true}).next()
                                .find('.multiselect-selected-text').text('---')
                                .find('.multiselect-container li.active input').prop('checked', false)
                                .find('.multiselect-container li.active').removeClass('active');
        return false;
    });

    /* ignore links of disabled tabs */
    $(".nav-tabs > li").on("click", function(event) {
        if ($(this).hasClass("disabled")) {
            event.preventDefault();
            return false;
        }
    });

    $(".search-controls")
        .children(".clearfix")
        .children(".hidden-xs.col-md-6")
        .removeClass("hidden-xs");

    // accordion for EDS abstracts + full texts, with max. one panel open at a time:
    let last;
    $(".accordion2").each(function() {
        this.onclick = function() {
            let accordActive = this.nextElementSibling.classList.contains('show');
            if (last){
                last.nextElementSibling.classList.remove("show");
            }
            if (!accordActive) {
                this.nextElementSibling.classList.add("show");
            }
            last = this;
        }
    })

/*
    // Lazy Loading
    $("img.lazy").lazyload({
        event:          "load",
        data_attribute: "src",
        // placeholder: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=",
        placeholder: " data:image/gif;base64,R0lGODlhFAAOAKEAAPTy9Pz6/PT29P///yH/C05FVFNDQVBFMi4wAwEAAAAh+QQJDQADACwAAAAAFAAOAAACQZyPqcsI/8IQEIgR6hMcSNpdGUdZ3WdamFmS3mSNsGvKJLjS4AvKo5BhvYSXHYRji6l+pUrRubI4I5OdSNPIarMFACH5BAkNAAMALAAAAAAUAA4AAAJBnI+pywj/whAQiBHqExxI2l0ZR1ndZ1qYWZLeZI2wa8okuNLgC8qjkGG9hJcdhGOLqX6lStG5sjgjk51I08hqswUAIfkECQ0AAwAsAAAAABQADgAAAkGcj6nLCP/CEBCIEeoTHEjaXRlHWd1nWphZkt5kjbBryiS40uALyqOQYb2Elx2EY4upfqVK0bmyOCOTnUjTyGqzBQAh+QQJDQADACwAAAAAFAAOAAACQZyPqcsI/8IQEIgR6hMcSNpdGUdZ3WdamFmS3mSNsGvKJLjS4AvKo5BhvYSXHYRji6l+pUrRubI4I5OdSNPIarMFACH5BAkNAAYALAAAAAAUAA4AguTi5PTy9Ozu7Pz6/OTm5PT29P///wAAAANZCKph/hACIgQoMT9QLS5BGGCDGHJCcBlg0RqD4BZWRzTzW85q6rGjHUw2AKFUmJFrBFMaOyvQrhErPgO/0JLk7KWihSIzFsaisiautmdW60ai80qTWTDomQQAIfkECQ0ABwAsAAAAABQADgCC5OLk9PL0/Pr87Ors9Pb0/P787O7s////A2RYqncDEIxG27iXOGDerJYhDtoldocQrAEBsKQzjBPR2p0RdOXcTQIDYRiYrWKm32FIENiMPBktdXvqRL2prbkzFrPKILN4FR4k2mYz1wVfUkKY6CsN22AYJEcJOiwYGxEAfQcJACH5BAkNAAgALAAAAAAUAA4Ag+Ti5PTy9Ozq7Pz6/OTm5PT29Ozu7Pz+/P///wAAAAAAAAAAAAAAAAAAAAAAAAAAAARuEMmJjr2ogL3pDEIoGEgAjAQQrOuAGHCcAbFa3IELr3BGBDDarZB7GXgk09FgG6wKpV0v8AsahsUdkKQ5CpotIxBYItSeRN0RxFVZBzj1NmkGqZ4BKAg5295ZajF0XgAeEiAibSNfhhUXFiUcGxEAIfkECQ0ABgAsAAAAABQADgCC7Ors9Pb09PL0/P787O7s/Pr8////AAAAA1doutzzr8lVgLUi52KCFkEHECQABh7noaCYESOadmdbEQIMFlko875RLsZS1YI5gSnAExiZrUBMV3uuDJXXqFCkQYM4Kig6jgrFG5omdBtOJtIL4D2BPBIAOw==",
    });
*/

    // Auto set #modalTitle from first H2 in .modal-body
    $(".modal").on("show.bs.modal", function () {
        $(this).find("#modalTitle").empty();
        $(this).find(".modal-body").one("DOMNodeInserted", function(){
            let h2 = $(this).find("h2:first-child");
            if (typeof h2 !== "undefined" && h2.text().length > 0) {
                $("#modalTitle").text(h2.text());
                // h2.remove();
            }
        });
    });

    collapseHeaderTop();
    window.onresize = function(){
        collapseHeaderTop();
        setDirection("resultNumber");
        changeFacets();
        moveSearchboxElements();
    };

    // Toggle Button in Bootstrap Collapse
    $(document).on("ajaxComplete", function() {
        $(".toggle-collapse").off("click").on("click", function() {
            $(this).children(".accordion-expand-button").text(function(i,old) {
                if (old.includes("+")) {
                    return "-";
                } else if (old.includes("-")) {
                    return "+";
                }
                return old;
            });
        });
    });

    $(document).on("ajaxComplete", function() {
        $(".retro-button").closest("table").css("background", "white");
    });
});

function collapseHeaderTop() {
    // definition of initial state expanded, in order to get correct values for navbar widths
    // (initial state collapsed does not work):
    let headerTop     =      $('.header-top')
                                .removeClass('headerTopCollapse')
                                .   addClass('headerTopExpand'),
        delta =      $('.header-top .container') .width()
                          - ($('.navbar-nav')           .width() ?? 0)
                          -  (window.innerWidth < 992
                                    ? $('.navbar-brand')    .width()
                                    : 0)
                          -  50;
    if (delta > 0) {
        headerTop
            .removeClass('headerTopCollapse')
            .addClass(   'headerTopExpand');
    } else {
        headerTop
            .removeClass('headerTopExpand')
            .addClass(   'headerTopCollapse');
    }
}
/*
function processRVK() {
    // all commands in this function use variables + functions from:
    // hds2/themes/hebisbs3/js/RVK_VISUAL/rvk-visual.js
    // (see github.com/bvb-kobv-allianz/RVK-VISUAL, but massively modified);
    // rvk-visual.js is embedded in the html head of the page (which is NOT part of the html body / page header),
    // this embedding is defined in hds2/themes/hebisbs3/templates/layout/layout.phtml
    // (since the HTML head is directly accessible only via layout.phtml);
    // popover of data received from API is defined in:
    // hds2/themes/hebisbs3/templates/RecordDriver/SolrDefault/rvk.phtml

    // create a new instance 'rvkVisual' of 'RvkVisual',
    // with default CSS class '.rvk_button' for RVK notations:
    let rvkVisual = RvkVisual.newInstance();
    window.addEventListener("load", () => {
        RvkVisual.prepareRVK();
    })
}
*/

function toIso(n) {
    if (n <= 0) {
        n = 1;
    }
    n = n.toString();
    return zeroFill(n, 4)// + '-01-01T00:00:00Z'
}

function zeroFill( number, width ) {
    width -= number.toString().length;
    if ( width > 0 )
    {
        return new Array( width + (/\./.test( number ) ? 2 : 1) ).join( '0' ) + number;
    }
    return number + ""; // always return a string
}

/* The currently selected sorting value needs to be copied to the bulkActionForm
in order to keep the selected sorting for the printed list.
This is done by the following function. */
function fillInSortingValue() {
    $('#listSorting').val($('option[selected="selected"]').val());
}

function printPageAndRedirectTo(url) {
    window.addEventListener("afterprint", function () {
        window.location.href = url;
    });
    window.print();
}

function changeFacets() {
    // does not work if position fixed is integrated into a css class, for example into .sidebar
    // since then it gets overwritten by bootstrap rtl(!)-class .col-sm-3 (for hebrew / arabic characters)
    const screen_sm_max      = 991,                 // as in <instance>/variables.less
          sidebar            = $(".sidebar");
    window.innerWidth <= screen_sm_max
        ?   (   sidebar.css("position", "fixed"),
                setDirection("html")
        )
        :       sidebar.css("position", "relative")
}

// toggle sidebar on small screens:
function togglesidebar() {
    $(".sidebarButton").on("click", function(){
        html.hasClass("overlay")
            ?   html.removeClass("overlay htmlOverlay-ltr htmlOverlay-rtl")
            :  (html.addClass(   "overlay"),
                    setDirection("html", "Overlay")
            )
    });
}

function setupStickyElements() {
    function sortStickyElements(a, b) {
        let posA = a.dataset.stickyPos;
        let posB = b.dataset.stickyPos;
        if (posA === undefined && posB === undefined) return 0;
        if (posA === undefined) return 1;
        if (posB === undefined) return -1;
        return posA - posB;
    }

    function setPlaceholderStyle (stickyElement, sideOnly = false) {
        let style = window.getComputedStyle(stickyElement, null);
        let placeholder = stickyElement.parentNode.previousSibling;
        if (sideOnly) {
            placeholder.style.width = style.width;
            placeholder.style.paddingLeft = style.paddingLeft;
            placeholder.style.paddingRight = style.paddingRight;
            placeholder.style.borderLeft = style.borderLeft;
            placeholder.style.borderRight = style.borderRight;
            placeholder.style.marginLeft = style.marginLeft;
            placeholder.style.marginRight = style.marginRight;
        } else {
            placeholder.style.height = style.height;
            placeholder.style.width = style.width;
            placeholder.style.padding = style.padding;
            placeholder.style.border = style.border;
            placeholder.style.margin = style.margin;
        }
    }

    function getInheritedBackgroundColor(el) {
        var defaultStyle = getDefaultBackground();
        var backgroundColor = window.getComputedStyle(el).backgroundColor;
        if (backgroundColor !== defaultStyle) return backgroundColor;
        if (!el.parentElement) return defaultStyle;
        return getInheritedBackgroundColor(el.parentElement)
    }

    function getDefaultBackground() {
        var div = document.createElement("div")
        document.head.appendChild(div)
        var bg = window.getComputedStyle(div).backgroundColor
        document.head.removeChild(div)
        return bg
    }

    function handleStickyElements() {
        let num = 0;
        let count = stickyElements.length;
        let currentOffset = 0;
        stickyElements.each(
            (i, stickyElement) => {
                let stickyContainer = stickyElement.parentNode;
                let placeholder = stickyContainer.previousSibling;
                let gapFiller = stickyElement.previousSibling;
                let isSticky = stickyContainer.classList.contains("sticky");
                let stickyElementStyle = window.getComputedStyle(stickyElement, null);
                if (
                    (!isSticky && window.scrollY + currentOffset >= stickyContainer.offsetTop - parseInt(stickyElementStyle.marginTop, 10))
                    || (isSticky && window.scrollY + currentOffset >= placeholder.offsetTop - parseInt(stickyElementStyle.marginTop, 10))
                ) {
                    stickyContainer.classList.add("sticky");
                    placeholder.classList.remove("hidden");
                    gapFiller.classList.remove("hidden");
                    let parentStyle = window.getComputedStyle(stickyContainer.parentNode, null);
                    let parentBoundingClientRect = stickyContainer.parentNode.getBoundingClientRect()
                    stickyContainer.style.top = (currentOffset - 1) + "px";
                    stickyContainer.style.marginLeft = parentBoundingClientRect.left + "px";
                    stickyContainer.style.marginRight = (window.screen.width - parentBoundingClientRect.right) + "px";
                    stickyContainer.style.borderLeft = parentStyle.borderLeft;
                    stickyContainer.style.borderRight = parentStyle.borderRight;
                    stickyContainer.style.paddingLeft = parentStyle.paddingLeft;
                    stickyContainer.style.paddingRight = parentStyle.paddingRight;
                    stickyContainer.style.width = parentBoundingClientRect.width + "px";
                    stickyContainer.style.zIndex = 10 + count - num;
                    gapFiller.style.width = stickyElementStyle.width;
                    gapFiller.style.marginLeft = stickyElementStyle.marginLeft;
                    gapFiller.style.borderLeft = stickyElementStyle.borderLeft;
                    gapFiller.style.paddingLeft = stickyElementStyle.paddingLeft;
                    currentOffset -= 1;
                } else {
                    stickyContainer.classList.remove("sticky");
                    placeholder.classList.add("hidden");
                    gapFiller.classList.add("hidden");
                    stickyContainer.style.top = "";
                    stickyContainer.style.marginLeft = "0";
                    stickyContainer.style.marginRight = "0";
                    stickyContainer.style.borderLeft = "0";
                    stickyContainer.style.borderRight = "0";
                    stickyContainer.style.paddingLeft = "0";
                    stickyContainer.style.paddingRight = "0";
                    stickyContainer.style.width = "";
                    stickyContainer.style.zIndex = "";
                }
                currentOffset += stickyContainer.offsetHeight;
                num += 1;
            });
    }

    let stickyElements = $('.sticky-element').sort(sortStickyElements);
    stickyElements.each(
        (i, stickyElement) => {
            $('<div class="sticky-placeholder hidden"></div>').insertBefore(stickyElement);
            $('<div class="sticky-container"></div>').insertBefore(stickyElement);
            stickyElement.previousSibling.insertAdjacentElement('beforeEnd', stickyElement);
            $('<div class="sticky-gap-filler hidden"></div>').insertBefore(stickyElement);
            setPlaceholderStyle(stickyElement);
            stickyElement.parentNode.style.backgroundColor = getInheritedBackgroundColor(stickyElement.parentNode);
            stickyElement.previousSibling.style.backgroundColor = getInheritedBackgroundColor(stickyElement);
        }
    )
    handleStickyElements();

    window.addEventListener("resize", () => {
        stickyElements.each(
            (i, stickyElement) => {
                setPlaceholderStyle(stickyElement, true);
            }
        )
        handleStickyElements();
    });
    window.addEventListener("orientationchange", () => {
        stickyElements.each(
            (i, stickyElement) => {
                setPlaceholderStyle(stickyElement, true);
            }
        )
        handleStickyElements();
    });
    document.addEventListener("scroll",  handleStickyElements);
}

function moveSearchboxElements() {
    // todo:
    // breakpointHeaderQuery = case instance:
    // "ubffm":                   992
    // "ubgi":                    768
    const breakpointHeaderQuery = 480;
    let fieldSelection          = $("#fieldSelection"),
        search_button           = $("#search_button");
    window.innerWidth < breakpointHeaderQuery
        ? ( search_button
            .addClass("pull-left flip xxs")
            .insertBefore($("#advancedAndHistoryAndKeepFilters") ),
            fieldSelection
                .addClass("col-xs-12 xxs")
                .insertBefore($("#indent_button_grp_no2")) )
        : ( search_button
            .removeClass("pull-left flip xxs")
            .appendTo($("#inputGrpSearchBox")),
            fieldSelection
                .removeClass("col-xs-12 xxs")
                .insertBefore(search_button) );
}

function setDirection(whichClass, addon = "") {
    let     mainDirection    = "ltr",           // = $("body").css.direction does not work: why??
            reverseDirection = "rtl";
    switch (VuFind.userLang) {
        case "he":
        case "ar":
            mainDirection    = "rtl";
            reverseDirection = "ltr";
    }
    html
        .removeClass(whichClass + addon + "-" + reverseDirection)
        .addClass(   whichClass + addon + "-" + mainDirection);
}

// if a new element is saved to the favorites list, scroll it into view after saving,
// either if user goes back to search results, or if user goes forward to favorites list:
function moveIntoView(page) {
    if (sessionStorage.itemToSave) {
        if (page === "searchResults") {
            for (   element of $(".levelAboveID") ) {
                if (element.firstElementChild.href.includes(sessionStorage.itemToSave) ) {
                    scrollToView(element);
                    break;
                }
            }
        }

        if (page === "myList") {
            for (   element of $(".result-list-item")) {
                if (element.firstElementChild.value === sessionStorage.itemToSave) {
                    scrollToView(element);
                    break;
                }
            }
        }
    }
}

function scrollToView(target) {
    target.scrollIntoView();
    sessionStorage.removeItem("itemToSave");
}

// handle zoomable retro card images
function init_zoomable_retro_card_image(){
    // get the retro-card images
    var img_divs = document.querySelectorAll(".retro-card-img");

    img_divs.forEach(function(img_div) {
        // get the modal
        var modal = img_div.querySelector(".retro-card-modal");

        // get the image and insert it inside the modal - use its "alt" text as caption
        var img = img_div.querySelector(".retro-card-small-img");
        var modalImg = img_div.querySelector(".retro-card-modal-img");
        var captionText = img_div.querySelector(".retro-card-modal-caption");
        modalImg.src = img.src;
        modalImg.alt = img.alt;
        captionText.innerHTML = img.alt;

        img.onclick = function () {
            modal.style.display = "block";
        }

        modal.onclick = function () {
            setTimeout(function () {
                modal.style.display = "none"
            }, 50);
        }
    });
}

/* If a combine tab is used (typically showing Solr and EDS results) the number of combined results is updated with each number of hits that is received via ajax, adding it to the previously established number with this function. */
function addToCombined(num)
{
    var numberText = $("#Combined > a .hit-number").attr("hit-number"); //the attribute hit-number stores the number of hits in a non-localized format for easy retrieval.
    var combinedNumber = parseInt(numberText);
    combinedNumber += parseInt(num);
    $("#Combined > a .hit-number").text("(" + combinedNumber.toLocaleString() + ")");
    $("#Combined > a .hit-number").attr("hit-number", combinedNumber);
}

/* Format a non-empty string with the provided callback. Return an empty string if the input is empty. */

function formatNonEmpty(string, format)
{
    if(string == "") {
        return string;
    } else {
        return format(string);
    }
}

function printElement(elementId, title) {
    var elementContent = document.getElementById(elementId).innerHTML;
    var a = window.open('', '', 'height=700, width=700');
    a.document.write('<html>');
    a.document.write('<head><title>' + title  + '</title></head>');
    a.document.write('<body>');
    a.document.write(elementContent);
    a.document.write('</body></html>');
    a.document.close();
    a.print();
    a.close();
}