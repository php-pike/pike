/**
 * Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
(function ($) {
    if (typeof $.pike == 'undefined') {
        $.pike = {};
    }

    $.pike.toolbar = {
        /**
         * Base URL
         */
        baseUrl : null,

        /**
         * Unique key is used to match database queries in AJAX requests
         */
        uniqueKey : null,

        /**
         * If database queries in AJAX requests must be displayed
         */
        ajaxDatabaseQueriesEnabled : false,

        /**
         * If true, the toolbar will be updated with AJAX database queries (if enabled) after
         * each AJAX request
         */
        reloadAjaxDatabaseQueriesAfterAjaxComplete : false,

        /**
         * If AJAX database queries are retrieved for the first time
         */
        firstTimeAjaxDatabaseQueriesRetrieved : false,

        /**
         * Initialization
         */
        init : function() {
            if ($('#pike-toolbar').length) {
                this.initToolbarToggle();
                this.initButtons();
                this.initDialogs();
                this.initAjaxDatabaseQueries();
            }
        },

        initToolbarToggle : function() {
            var self = this;

            if (0 == this.getCookie('pikeToolbarEnabled')) {
                $('#pike-toolbar').hide();
                $('#pike-toolbar-button').show();
            } else {
                $('#pike-toolbar').show();
                $('#pike-toolbar-button').hide();
            }

            // Hide toolbar and show the "show" button
            $('.button-hide-toolbar a', '#pike-toolbar').click(function() {
                $('#pike-toolbar').slideToggle('fast');
                $('#pike-toolbar-button').fadeIn('slow');
                self.setCookie('pikeToolbarEnabled', 0);
                return false;
            });

            // Show toolbar and hide the "show" button
            $('.button-show-toolbar a', '#pike-toolbar-button').click(function() {
                $('#pike-toolbar').slideToggle('fast');
                $('#pike-toolbar-button').fadeOut();
                self.setCookie('pikeToolbarEnabled', 1);
                return false;
            });
        },

        initButtons : function() {
            $('a', '#pike-toolbar').click(function() {
                if ($(this).attr('href').length <= 1) {
                    return false;
                }
            })

            $('.button a[rel~="external"]', '#pike-toolbar').attr('target', '_blank');

            $(".button a").click(function() {
                var button = $(this).closest('.button');
                var classes = $(button).attr('class').split(/\s+/);
                var name = '';

                $.each(classes, function(index, value) {
                    if (value.match('button-')) {
                        name = value.replace('button-', '');
                        return false;
                    }
                });

                var dialog = $('.dialog-' + name);

                if (dialog.length) {
                    if (dialog.is(':hidden')) {
                        $('.dialog', '#pike-toolbar').hide();
                        dialog.fadeIn('fast');
                    } else if (dialog.is(':visible')) {
                        dialog.fadeOut('fast');
                    }
                }
            });
        },

        initDialogs : function() {
            // Hide dialog(s) when something outside the dialog is clicked
            $(document).click(function() {
                $('.dialog', '#pike-toolbar').fadeOut('fast');
            });

            $('.dialog', '#pike-toolbar').click(function(event) {
                // Avoid closing of a dialog when clicking on its elements
                event.stopPropagation();
            });
        },

        initAjaxDatabaseQueries : function() {
            var self = this;

            if (this.ajaxDatabaseQueriesEnabled) {
                // This merges with other setup AJAX data
                $.ajaxSetup({
                    data: { pikeToolbarUniqueKey: $.pike.toolbar.uniqueKey }            
                });
                
                $('.query-log-ajax-container', '#pike-toolbar').show();
                this.bindClickEventToAjaxDatabaseQueriesReloadButton();

                // Retrieve all database queries executed in AJAX requests after 2 seconds
                setTimeout("$.pike.toolbar.getAjaxDatabaseQueries();", 2000);

                if (this.reloadAfterEachAjaxComplete) {
                    $('#pike-toolbar').ajaxComplete(function(event, XMLHttpRequest, ajaxOptions) {
                        // Only take action if AJAX request was not initiated by PiKe
                        if (ajaxOptions.url.indexOf(self.baseUrl + '/pike') !== 0
                            && self.firstTimeAjaxDatabaseQueriesRetrieved
                        ) {
                            $('.reload', '#pike-toolbar').click();
                        }
                    });
                }
            }
        },

        bindClickEventToAjaxDatabaseQueriesReloadButton : function() {
            var self = this;
            $('.reload', '#pike-toolbar').click(function() {
                self.getAjaxDatabaseQueries();
                return false;
            });
        },

        getAjaxDatabaseQueries : function() {
            var self = this;
            $.post(this.baseUrl +'/pike/ajax-database-queries', {
                    pikeToolbarUniqueKey: this.uniqueKey
                }, function(data) {
                    var json = $.parseJSON(data);
                    if (typeof json.data != 'undefined') {
                        if ('' != json.data) {
                            $('.query-log-ajax-container', '#pike-toolbar').replaceWith(json.data);
                            self.bindClickEventToAjaxDatabaseQueriesReloadButton();

                            $('.button-databaseQueries .count-ajax', '#pike-toolbar')
                                .text(json.count)
                                .show();
                            $('.button-databaseQueries .divider', '#pike-toolbar').show();
                        }
                    }

                    self.firstTimeAjaxDatabaseQueriesRetrieved = true;
                }
            );
        },

        setCookie : function(name, value) {
            $.cookie(name, value, { expires: 30, path: '/' });
        },

        getCookie : function(name) {
            return $.cookie(name);
        }
    };
})(jQuery);