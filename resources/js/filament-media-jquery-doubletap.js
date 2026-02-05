(function () {
    let retries = 0;
    const maxRetries = 50; // 50 * 100ms = 5 seconds

    const checkAndInit = () => {
        const $ = window.jQuery || window.$;
        if ($ && $.event) {
            $.event.special.doubletap = {
                bindType: 'touchend',
                delegateType: 'touchend',
                handle: function (event) {
                    let handleObj = event.handleObj,
                        targetData = $.data(event.target) || {},
                        now = new Date().getTime(),
                        delta = targetData.lastTouch ? now - targetData.lastTouch : 0,
                        delay = 300;

                    if (delta < delay && delta > 30) {
                        targetData.lastTouch = null;
                        event.type = handleObj.origType;
                        if (event.originalEvent && event.originalEvent.changedTouches) {
                            ['clientX', 'clientY', 'pageX', 'pageY'].forEach(function (p) {
                                event[p] = event.originalEvent.changedTouches[0][p];
                            });
                        }
                        handleObj.handler.apply(this, arguments);
                    } else {
                        targetData.lastTouch = now;
                        $.data(event.target, targetData);
                    }
                }
            };
            return;
        }

        if (retries < maxRetries) {
            retries++;
            setTimeout(checkAndInit, 100);
        } else {
            console.error('[FilamentMedia] jQuery dependency failed to load after 5s.');
        }
    };

    checkAndInit();
})();