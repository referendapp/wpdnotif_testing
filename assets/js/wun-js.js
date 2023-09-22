jQuery(document).ready(function ($) {

    const wunIsNotificationsActive = Boolean(wunJsObj.wunIsNotificationsActive);
    const wunRestURL = wunJsObj.wunRestURL;
    const wunAjaxURL = wunJsObj.wunAjaxUrl;
    const wunLoadMethod = wunJsObj.wunLoadMethod;
    const wunLiveUpdate = Boolean(wunJsObj.wunLiveUpdate);
    const wunUpdateTimer = parseInt(wunJsObj.wunUpdateTimer, 10) * 1000;
    const wunBrowserNotifications = Boolean(wunJsObj.wunBrowserNotifications);
    const wunRestNonce = wunJsObj.wunRestNonce;
    const wunUserIP = wunJsObj.wunUserIP;
    const wunCookieHash = wunJsObj.wunCookieHash;
    const wunCenteredContainerMaxWidth = parseInt(wunJsObj.wunCenteredContainerMaxWidth, 10);
    const wunSoundUrl = wunJsObj.wunSoundUrl;
    const wunPlaySoundWhen = wunJsObj.wunPlaySoundWhen;
    const wunContainerAnimationInMs = parseInt(wunJsObj.wunContainerAnimationInMs, 10);
    const wunRequestTypeCheck = wunJsObj.wunRequestTypeCheck;
    const wunRequestTypeLoad = wunJsObj.wunRequestTypeLoad;
    const wunShowCountOfNotLoaded = Boolean(wunJsObj.wunShowCountOfNotLoaded);
    const wunSetReadOnLoad = Boolean(wunJsObj.wunSetReadOnLoad);
    const wunUniqueNonce = wunJsObj.wunUniqueNonce;

    const wunContainer = $(".wun-container");
    const wunNotifications = $(".wun-notifications", wunContainer);
    const wunList = $(".wun-list", wunNotifications);

    let wunIsPageHidden = document.hidden;
    let wunRestInProcess = false;
    let wunAjaxInProcess = false;
    let wunRequestType = wunRequestTypeCheck;
    let wunBellParent = null;
    let wunResponse = null;
    let wunTimeoutID;
    let wunLastId = 0;
    let wunUpdateHtml = false;
    let wunNonce = null;
    let wunCount = 0;
    let wunPlay = false;

    // browser visibility API variables
    let wunDocHidden, wunDocVisibilityChange;

    localStorage.setItem("wunUserIP", wunUserIP);
    Cookies.set("wunCurrentURL", location.href, {sameSite: 'Lax'});

    if (!wunContainer.parent("body").length) {
        wunContainer.appendTo("body");
    }

    $(document).on("click", ".menu-item-wun-bell, .menu-item-wun-bell a", function (e) {
        e.preventDefault();
        e.stopPropagation();
        const el = $(this);
        const parent = el.parents("li");
        const parentWidth = Math.ceil(parent.outerWidth());
        const parentHeight = Math.ceil(parent.outerHeight());
        parent.css({'max-width': parentWidth + 'px'});

        if (!wunContainer.hasClass("wun-opened")) {
            wunUpdateHtml = true;
            wunRequestType = wunRequestTypeLoad;
            wunLastId = 0;
            wunList.html("");
            wunGetNotifications();
            wunCheckBrowserNotifications(wunResponse);
        }

        wunContainer.css({
            'position': 'absolute',
            'z-index': '99999999',
            'display': 'block',
            'left': '0',
            'top': '-9999px'
        });

        const offsets = parent.offset();
        if (offsets) {
            const windowWidth = Math.ceil(window.innerWidth) || Math.ceil(document.documentElement.clientWidth) || Math.ceil(document.body.clientWidth);
            const windowHeight = Math.ceil(window.innerHeight) || Math.ceil(document.documentElement.clientHeight) || Math.ceil(document.body.clientHeight);
            const wunContainerWidth = Math.ceil(wunContainer.outerWidth());
            //const bodyRect = document.body.getBoundingClientRect();

            const parentTop = Math.ceil(offsets.top);
            const parentLeft = Math.ceil(offsets.left);

            if (windowWidth > wunCenteredContainerMaxWidth) {

                if ((parentLeft + wunContainerWidth) > windowWidth) {
                    let wunContainerLeft = (parentLeft - wunContainerWidth) + parentWidth;
                    wunContainer.css({'left': wunContainerLeft + 'px'});
                } else {
                    wunContainer.css({'left': parentLeft + 'px'});
                }

                wunContainer.css({'top': (parentTop + parentHeight) + 'px'});
            } else {
                wunContainer.css({
                    'left': ((windowWidth - wunContainerWidth) / 2) + 'px',
                    'top': Math.ceil(windowHeight / 5) + 'px',
                    'position': 'fixed'
                });
            }
        }

        if (!wunContainer.hasClass("wun-opened") || wunBellParent === null) {
            wunNotifications.slideToggle(wunContainerAnimationInMs);
            wunContainer.toggleClass("wun-opened");
        }

        if (wunBellParent === parent[0]) {
            if (wunContainer.hasClass("wun-opened")) {
                wunNotifications.slideToggle(wunContainerAnimationInMs);
                wunContainer.toggleClass("wun-opened");
                wunBellParent = null;
                wunLastId = 0;
            } else {
            }
        } else {
            wunBellParent = parent[0];
        }
        return false;
    });

    $(document).on("click", ".wun-action-load-more", function (e) {
        wunUpdateHtml = true;
        wunRequestType = wunRequestTypeLoad;
        wunGetNotifications();
    });

    $(document).on("click", ".wun-action-delete-all", function (e) {
        if (!wunAjaxInProcess) {
            const el = $(this);
            const data = new FormData();
            data.append("action", "wunDeleteAllNotifications");
            data.append("nonce", wunNonce);

            const ajax = wunGetAjax(data);

            ajax.done(function (response) {
                wunResetProcesses();
                if (typeof response === "object") {
                    if (response.success) {
                        wunLastId = 0;
                        wunCount = 0;
                        wunUpdateHtml = false;
                        wunList.html("");
                        $(".wun-actions", wunContainer).css({"display": "none"});
                        wunGetNotifications();
                        $(".wun-bell", wunBellParent).trigger("click");
                        wunBellParent = null;
                    }
                    console.log(response.data.message);
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.log(errorThrown);
                wunResetProcesses();
            });
        }
    });

    function wunCheckBrowserNotifications(response) {

        if (!wunBrowserNotifications || !("Notification" in window)) {
            return;
        }

        if (Notification.permission === "granted") {
            wunShowBrowserNotifications(response);
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(access => {
                if (access === "granted") {
                    wunShowBrowserNotifications(response);
                }
            });
        }
    }

    function wunShowBrowserNotifications(response) {
        if (response === null || response.itemsRaw === "undefined" || response.itemsRaw === null || Notification.permission !== "granted") {
            return;
        }

        response.itemsRaw.forEach((item, i, arr) => {
            let icon = $.trim(item.icon).length ? item.icon : response.defaultIcon;

            let notification = new Notification(item.title, {
                body: item.message,
                icon: icon,
                badge: icon,
                silent: false,
                tag: item.tag,
                data: {
                    url: item.url
                }
            });

            notification.addEventListener("click", (e) => {
                if (e.srcElement.data.url) {
                    window.open(e.srcElement.data.url, "_blank");
                }
            });
        });
    }

    wunGetNotifications();

    $(document).on('wunGetNotifications', function () {
        wunGetNotifications();
    });

    function wunGetNotifications() {
        if (wunIsNotificationsActive) {

            let request = null;

            if (wunLoadMethod === "rest") {
                request = wunRestRequest();
            } else {
                request = wunAjaxRequest();
            }

            if (request === null) {
                wunSetTimeout();
            } else {
                request.done(function (response) {
                    if (typeof response === "object") {
                        const itemsTotal = parseInt(response.itemsTotal, 10);

                        if (itemsTotal > 0 || response.lastId) {

                            if (wunPlay) {
                                const audio = new Audio(wunSoundUrl);
                                if (wunPlaySoundWhen === "unread") {
                                    audio.play();
                                } else if (wunPlaySoundWhen === "new" && itemsTotal > wunCount) {
                                    // check count for latest request and if it > than the old count play new notification sound
                                    audio.play();
                                }
                            }


                            if (itemsTotal > 0) {
                                $(".wun-bell").addClass("wun-has-unread");
                                $(".wun-count").addClass("wun-has-unread").html(itemsTotal);
                            } else {
                                $(".wun-bell").removeClass("wun-has-unread");
                                $(".wun-count").removeClass("wun-has-unread").html(itemsTotal);
                            }

                            if (wunUpdateHtml && wunRequestType === wunRequestTypeLoad) {
                                wunLastId = response.lastId;
                                $(".wun-no-notifications", wunList).parents(".wun-item").remove();
                                wunList.append(response.itemsHtml);

                                if (Boolean(response.itemsLeft)) {
                                    $(".wun-action-load-more", wunContainer).css({"display": "block"});
                                    if (wunShowCountOfNotLoaded) {
                                        $(".wun-items-left", wunContainer).removeClass("wun-hidden").text(`(${response.itemsLeft})`);
                                    }
                                } else {
                                    $(".wun-action-load-more", wunContainer).css({"display": "none"});
                                    if (wunShowCountOfNotLoaded) {
                                        $(".wun-items-left", wunContainer).addClass("wun-hidden").text("");
                                    }
                                }

                                $(".wun-actions", wunContainer).css({"display": "block"});
                            }

                            wunUpdateHtml = false;
                            wunResponse = response;
                            wunShowBrowserNotifications(response);

                            if (response.nonce) {
                                wunNonce = response.nonce;
                                $(".wun-action-delete-all").attr("data-nonce", wunNonce);
                            }
                        } else {
                            $(".wun-bell").removeClass("wun-has-unread");
                            $(".wun-count").removeClass("wun-has-unread").html(itemsTotal);
                            wunResponse = null;
                            if (!response.lastId) {
                                wunList.html(response.itemsHtml);
                                $(".wun-actions", wunContainer).css({"display": "none"});
                            }
                        }
                        wunCount = itemsTotal;
                    }

                    wunResetProcesses();
                    wunSetTimeout();
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    console.log(errorThrown);
                    wunResetProcesses();
                    wunSetTimeout();
                });
            }
        }
    }

    $(document).on("click", function (e) {
        if (wunSoundUrl) {
            wunPlay = true;
        } else {
            console.log("Invalid audio URL");
        }
        if (wunContainer.hasClass("wun-opened") && !(wunContainer[0].contains(e.target))) {
            wunNotifications.slideToggle(wunContainerAnimationInMs);
            wunContainer.toggleClass("wun-opened");
            wunBellParent = null;
            wunLastId = 0;
        }
    });

    function wunSetTimeout() {
        if (wunLiveUpdate) {
            if (wunTimeoutID) {
                clearTimeout(wunTimeoutID);
            }
            wunTimeoutID = setTimeout(wunGetNotifications, wunUpdateTimer);
        }
    }

    window.wunAddSubscriptionsNotifications = function (response) {
        if (response.success) {
            const data = new FormData();
            data.append("action", "wunAddSubscriptionsNotifications");
            data.append("subscriptions", response.data.subscriptions);
            const ajax = wunGetAjax(data);
            ajax.done(function (r) {
                wunResetProcesses();
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.log(errorThrown);
                wunResetProcesses();
            });
        }
    };


    window.wunAddFollowsNotifications = function (response) {
        if (response.success) {
            const data = new FormData();
            data.append("action", "wunAddFollowsNotifications");
            data.append("follows", response.data.follows);
            const ajax = wunGetAjax(data);
            ajax.done(function (r) {
                wunResetProcesses();
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.log(errorThrown);
                wunResetProcesses();
            });
        }
    };

    function wunPageVisibilityVars() {
        if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
            wunDocHidden = "hidden";
            wunDocVisibilityChange = "visibilitychange";
        } else if (typeof document.msHidden !== "undefined") {
            wunDocHidden = "msHidden";
            wunDocVisibilityChange = "msvisibilitychange";
        } else if (typeof document.webkitHidden !== "undefined") {
            wunDocHidden = "webkitHidden";
            wunDocVisibilityChange = "webkitvisibilitychange";
        }
    }

    function wunHandleVisibilityChange() {
        // set page current visibility status    
        wunIsPageHidden = document[wunDocHidden];
    }

    wunPageVisibilityVars();

    if (typeof document.addEventListener === "undefined" || wunDocHidden === undefined) {
        console.log("This browser does not support the Page Visibility API.");
        wunIsPageHidden = false;
    } else {
        // Handle page visibility change            
        document.addEventListener(wunDocVisibilityChange, wunHandleVisibilityChange, false);
    }


    function wunResetProcesses() {
        wunRestInProcess = false;
        wunAjaxInProcess = false;
        wunRequestType = wunRequestTypeCheck;
        $(".wun-loader").css({"display": "none"});
    }

    function wunPushPermission() {
        return wunBrowserNotifications && ("Notification" in window) && Notification.permission === "granted";
    }

    function wunRestRequest() {
        let rest = null;
        if (wunRestInProcess) {
            console.log("REST in process");
        } else if (wunIsPageHidden) {
            console.log("Request has been prevented: tab is not active");
        } else {
            const loadTime = parseInt(Cookies.get("load_time"), 10);

            let data = {request_type: wunRequestType, last_id: wunLastId};

            if (wunPushPermission()) {
                data["load_raw"] = 1;
            }

            if (wunNonce === null) {
                data["nonce"] = wunNonce;
            }

            if (Number.isInteger(loadTime) && loadTime > 0) {
                data["load_time"] = loadTime;
            }

            rest = wunGetRestAjax(data);
        }
        return rest;
    }

    function wunAjaxRequest() {
        let ajax = null;
        if (wunAjaxInProcess) {
            console.log("AJAX in process");
        } else if (wunIsPageHidden) {
            console.log("Request has been prevented: tab is not active");
        } else {
            const data = new FormData();
            data.append("action", "wunUpdate");
            data.append("request_type", wunRequestType);
            data.append("last_id", wunLastId);

            const loadTime = parseInt(Cookies.get("load_time"), 10);

            if (wunPushPermission()) {
                data.append("load_raw", 1);
            }

            if (wunNonce === null) {
                data.append("nonce", wunNonce);
            }

            if (Number.isInteger(loadTime) && loadTime > 0) {
                data.append("load_time", loadTime);
            }
            ajax = wunGetAjax(data);
        }
        return ajax;
    }

    $(document).on('click', '.wun-mark-read', function (e) {
        e.preventDefault();
        const el = $(this);
        const href = el.attr('href');
        const url = new URL(href);
        const searchParams = new URLSearchParams(url.search);
        const id = parseInt(searchParams.get('id'), 10);
        const nonce = searchParams.get('_nonce');

        if (isNaN(id) || typeof id === 'undefined' || !nonce) {
            console.log('Invalid data');
            return;
        }

        const data = new FormData();
        data.append('action', 'wunUpdateStatusAjax');
        data.append('id', id);
        data.append('nonce', nonce);
        const ajax = wunGetAjax(data);
        ajax.done(function (response) {
            wunResetProcesses();
            if (response.success) {
                const countEl = $('.wun-count');
                if (response.data.wunStatus === 'read') {
                    // $(document).trigger('wunGetNotifications');
                    el.text(wunJsObj.wunPhraseSetAsUnread);
                } else {
                    el.text(wunJsObj.wunPhraseSetAsRead);
                }
                $('.wun-count').text(response.data.itemsTotal);
            }
            console.log(response.data.message);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.log(errorThrown);
            wunResetProcesses();
        });

    });

    function wunGetRestAjax(data) {
        wunRestInProcess = true;
        $(".wun-loader").css({"display": "block"});
        return $.ajax({
            beforeSend: function (xhr) {
                xhr.setRequestHeader("X-WP-Nonce", wunRestNonce);
            },
            type: "GET",
            url: wunRestURL,
            data: data
        });
    }

    function wunGetAjax(data) {
        wunAjaxInProcess = true;
        $(".wun-loader").css({"display": "block"});

        return $.ajax({
            type: "POST",
            url: wunAjaxURL,
            data: data,
            contentType: false,
            processData: false
        });
    }
});