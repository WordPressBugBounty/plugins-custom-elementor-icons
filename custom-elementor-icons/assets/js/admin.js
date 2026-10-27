(function ($) {
    "use strict";
    $.stmTrigger = $.stmTrigger || {};
    $.stmTrigger.wp_media = $.stmTrigger.wp_media || [];
    $.stmTrigger.media_new = function () {
        var $body = $("body");
        $body.on('click', '.stm_upload_icon', $.stmTrigger.media_new_activate);
    };
    $.stmTrigger.media_new_activate = function (event) {
        event.preventDefault();
        var clicked = $(this), options = clicked.data();
        options.input_target = $('#' + options.target);
        var file_frame = wp.media(
            {
                frame: options.frame,
                library: {type: options.type},
                button: {text: options.button},
                className: options['class']
            });
        file_frame.on('select update insert', function () {
            $.stmTrigger.media_new_insert(file_frame, options);
        });
        file_frame.open();
    };
    $.stmTrigger.media_new_insert = function (file_frame, options) {
        var state = file_frame.state(), selection = state.get('selection').first().toJSON();
        options.input_target.val(selection.id).trigger('change');
        $("body").trigger(options.trigger, [selection, options]);
    };
    function t(key, fallback) {
        var i18n = (window.cei_window && window.cei_window.i18n) || {};
        return i18n[key] || fallback;
    }
    $(document).ready(function () {
        $.stmTrigger.media_new();
        $("body").on('cei_upload_font', $.stmTrigger.icon_insert);
        $("body").on('click', '.cei_delete_font', $.stmTrigger.icon_remove);
        $.stmTrigger.icon_search();
    });
    $.stmTrigger.icon_search = function () {
        var i18n = cei_window.i18n || {};
        $(".search-icon").on('keyup', function () {
            var filter = $(this).val(), count = 0, regex;
            try {
                regex = new RegExp(filter, "i");
            } catch (e) {
                regex = new RegExp(filter.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), "i");
            }
            $(".fonts-count").hide();
            $(".icons-list li").each(function () {
                if (regex.test($(this).attr("data-icons-tag") || '')) {
                    $(this).show();
                    count++;
                } else {
                    $(this).fadeOut();
                }
            });
            var holder = $(".search-count").empty();
            if (count === 0) {
                holder.append(document.createTextNode(' ' + (i18n.not_found || '') + ' '));
                var link = $('<a href="#stm_upload_icon" class="add-new-h2 stm_upload_icon" data-target="iconfont_upload" data-type="application/octet-stream, application/zip" data-trigger="cei_upload_font" data-class="media-frame"></a>');
                link.attr('data-title', i18n.upload_title || '').attr('data-button', i18n.upload_button || '').text(i18n.upload_link || '');
                holder.append(link);
            } else {
                holder.text(count + ' ' + (i18n.found || ''));
            }
            if (filter === "") {
                $(".fonts-count").show();
            }
        });
    };
    $.stmTrigger.icon_insert = function (event, selection, options) {
        options.input_target.val("");
        var msg = $('#msg');
        if (selection.subtype !== 'zip') {
            $('.spinner').hide();
            msg.empty().append($("<div class='error'></div>").append($("<p></p>").text(t('invalid_zip', 'Please upload a valid ZIP file. You can create the file on icomoon.io'))));
            msg.show();
            setTimeout(function () {
                msg.slideUp();
            }, 5000);
            return;
        }
        $.ajax({
            type: "POST",
            url: cei_window.ajax_url,
            data: {
                action: 'cei_upload_font_archive',
                values: selection,
                nonce: cei_window.nonces.cei_upload_font_archive,
            },
            beforeSend: function () {
                $('.spinner').css({
                    opacity: 0,
                    display: "block",
                    visibility: 'visible',
                    position: 'absolute',
                    top: '21px',
                    left: '345px'
                }).animate({opacity: 1});
            },
            success: function (response) {
                $('.spinner').hide();
                if (response.match(/stm_font_added/)) {
                    msg.empty().append($("<div class='updated'></div>").append($("<p></p>").text(t('added', 'Icons Font added successfully! Reloading the page...'))));
                    msg.show();
                    setTimeout(function () {
                        msg.slideUp();
                        location.reload();
                    }, 5000);
                }
                else {
                    msg.empty().append($("<div class='error'></div>").append($("<p></p>").text(t('add_failed', "Couldn't add the Icons Font. Error:") + ' ' + response)));
                    msg.show();
                    setTimeout(function () {
                        msg.slideUp();
                    }, 5000);
                }
            }
        });
    };
    $.stmTrigger.icon_remove = function (event) {
        event.preventDefault();
        if ( confirm(t('confirm_delete', 'Are you sure to delete this Icons Font?')) ) {
            var button = $(this),
                delete_font = button.data('delete');
            var msg = $('#msg');
            $.ajax({
                type: "POST",
                url: cei_window.ajax_url,
                data: {
                    action: 'cei_remove_font_archive',
                    delete_font,
                    nonce: cei_window.nonces.cei_remove_font_archive,
                },
                beforeSend: function () {
                    $('.spinner').css({
                        opacity: 0,
                        display: "block",
                        visibility: 'visible',
                        position: 'absolute',
                        top: '21px',
                        left: '345px'
                    }).animate({opacity: 1});
                },
                error: function () {
                    $('.spinner').hide();
                    msg.empty().append($("<div class='error'></div>").append($("<p></p>").text(t('remove_failed_retry', "Couldn't remove the Icons Font. Please reload the page, then try again."))));
                    msg.show();
                    setTimeout(function () {
                        msg.slideUp();
                    }, 5000);
                },
                success: function (response) {
                    $('.spinner').hide();
                    if (response.match(/stm_font_removed/)) {
                        msg.empty().append($("<div class='updated'></div>").append($("<p></p>").text(t('removed', 'Icons Font deleted successfully! Reloading the page...'))));
                        msg.show();
                        setTimeout(function () {
                            msg.slideUp();
                            location.reload();
                        }, 5000);
                    }
                    else {
                        msg.empty().append($("<div class='error'></div>").append($("<p></p>").text(t('remove_failed_reload', "Couldn't remove the Icons Font. Reloading the page..."))));
                        msg.show();
                        setTimeout(function () {
                            msg.slideUp();
                            location.reload();
                        }, 5000);
                    }
                }
            });
        }
    }
})(jQuery);