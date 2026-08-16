(function (global) {
    'use strict';

    function wrapMarked(marked) {
        if (!marked || !global.DOMPurify) {
            return false;
        }
        if (marked.editorMDSanitized) {
            return marked;
        }

        var safeMarked = function () {
            return global.DOMPurify.sanitize(marked.apply(this, arguments));
        };

        for (var key in marked) {
            if (Object.prototype.hasOwnProperty.call(marked, key)) {
                safeMarked[key] = marked[key];
            }
        }

        safeMarked.setOptions = function (options) {
            marked.setOptions(options);
            return safeMarked;
        };
        safeMarked.editorMDSanitized = true;

        return safeMarked;
    }

    global.EditorMDSanitizeMarked = function () {
        var marked = global.marked
            || (global.editormd && global.editormd.$marked);
        var safeMarked = wrapMarked(marked);

        if (!safeMarked) {
            return false;
        }

        global.marked = safeMarked;
        if (global.editormd) {
            global.editormd.$marked = safeMarked;
        }

        return true;
    };

    global.EditorMDGuardMarkedLoader = function () {
        if (!global.editormd || !global.editormd.loadScript) {
            return false;
        }
        if (global.editormd.markedLoaderGuarded) {
            return true;
        }

        var loadScript = global.editormd.loadScript;
        global.editormd.loadScript = function (url, callback) {
            if (
                /(?:^|\/)marked\.min(?:\.js)?(?:[?#].*)?$/.test(url)
                && global.marked
                && global.marked.editorMDSanitized
            ) {
                global.editormd.$marked = global.marked;
                if (callback) {
                    callback();
                }
                return;
            }

            return loadScript.apply(this, arguments);
        };
        global.editormd.markedLoaderGuarded = true;

        return true;
    };
})(window);
