jQuery(document).ready(function ($) {

    $(document).on("click", ".wun-delete-notifications", function (e) {
        const el = $(this);
        let text = "";
        if (el.attr("data-wundelete") === "all") {
            text = wunBackendJsObj.wunMsgDeleteAllNotifications;
        } else if (el.attr("data-wundelete") === "expired") {
            text = wunBackendJsObj.wunMsgDeleteExpiredNotifications;
        } else if (el.attr("data-wundelete") === "read") {
            text = wunBackendJsObj.wunMsgDeleteReadNotifications;
        }

        if (el.attr("disabled") || !text.length || !confirm(text)) {
            e.preventDefault();
            return;
        }
    });

    /*=== ACCORDION START ===*/

    const hashRegex = /#(wun\-[a-zA-z0-9\-\_]+)/;
    let urlHash = location.href.match(hashRegex);
    let item = null;
    let supportsHash = false;

    if ("onhashchange" in window) {
        supportsHash = true;
    }

    if (supportsHash) {
        window.addEventListener("hashchange", wunOnhashchange, false);
    }

    if (urlHash != null) {
        item = $('.wun-accordion-title[data-wun-selector="' + urlHash[1] + '"');
        wunAccordion(item);
    }

    $(document).on('click', '.wun-accordion-title', function (e) {
        e.preventDefault();
        e.stopPropagation();
        item = $(this);

        if (!supportsHash) {
            wunAccordion(item);
        }

        urlHash = location.href.match(hashRegex);
        const selector = item.attr("data-wun-selector");

        if (urlHash != null) {
            if (urlHash[1] === selector) {
                window.history.replaceState({}, "", location.href.replace("#" + selector, ""));
                if (supportsHash) {
                    wunAccordion(item);
                }
            } else {
                window.history.replaceState({}, "", location.href.replace(urlHash[1], selector));
                if (supportsHash) {
                    window.dispatchEvent(new HashChangeEvent("hashchange"));
                }
            }
        } else {
            location.href = location.href.indexOf("#") >= 0 ? location.href + selector : location.href + "#" + selector;
        }
    });

    function wunAccordion(item) {
        if (item != null) {
            $(item).parent().siblings('.wun-accordion-item').removeClass('wun-accordion-current');
            $(item).parent().siblings('.wun-accordion-item').find('.wun-accordion-content').slideUp(0);
            $(item).siblings('.wun-accordion-content').slideToggle(0);
            $(item).parent().toggleClass('wun-accordion-current');
            return false;
        }
    }

    function wunOnhashchange() {
        urlHash = location.href.match(hashRegex);
        if (urlHash != null) {
            item = $('.wun-accordion-title[data-wun-selector="' + urlHash[1] + '"');
            wunAccordion(item);
        }
    }

    /*=== ACCORDION END ===*/


    function wunScrollToRight() {
        const elems = document.querySelectorAll(".wun-scroll-to-right");
        elems.forEach((el, i, elems) => {
            el.scrollLeft = el.scrollWidth;
        });
    }

    wunScrollToRight();

});