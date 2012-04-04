/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * 
 * 
 * 
 * # jquery.cookie
 * 
 * A simple, lightweight jQuery plugin for reading, writing and deleting cookies.
 * 
 * ## Installation
 * 
 * Include script *after* the jQuery library (unless you are packaging scripts somehow else):
 * 
 *     <script src="/path/to/jquery.cookie.js"></script>
 * 
 * ## Usage
 * 
 * Create session cookie:
 * 
 *     $.cookie('the_cookie', 'the_value');
 * 
 * Create expiring cookie, 7 days from then:
 * 
 *     $.cookie('the_cookie', 'the_value', { expires: 7 });
 * 
 * Create expiring cookie, valid across entire page:
 * 
 *     $.cookie('the_cookie', 'the_value', { expires: 7, path: '/' });
 * 
 * Read cookie:
 * 
 *     $.cookie('the_cookie'); // => 'the_value'
 *     $.cookie('not_existing'); // => null
 * 
 * Delete cookie by passing null as value:
 * 
 *     $.cookie('the_cookie', null);
 * 
 * *Note: when deleting a cookie, you must pass the exact same path, domain and secure options that were used to set the cookie.*
 * 
 * ## Options
 * 
 *     expires: 365
 * 
 * Define lifetime of the cookie. Value can be a `Number` (which will be interpreted as days from time of creation) or a `Date` object. If omitted, the cookie is a session cookie.
 * 
 *     path: '/'
 * 
 * Default: path of page where the cookie was created.
 * 
 * Define the path where cookie is valid. *By default the path of the cookie is the path of the page where the cookie was created (standard browser behavior).* If you want to make it available for instance across the entire page use `path: '/'`.
 * 
 *     domain: 'example.com'
 * 
 * Default: domain of page where the cookie was created.
 * 
 *     secure: true
 * 
 * Default: `false`. If true, the cookie transmission requires a secure protocol (https).
 * 
 *     raw: true
 * 
 * Default: `false`.
 * 
 * By default the cookie is encoded/decoded when creating/reading, using `encodeURIComponent`/`decodeURIComponent`. Turn off by setting `raw: true`.
 * 
 * ## Changelog
 * 
 * ## Development
 * 
 * - Source hosted at [GitHub](https://github.com/carhartl/jquery-cookie)
 * - Report issues, questions, feature requests on [GitHub Issues](https://github.com/carhartl/jquery-cookie/issues)
 * 
 * Pull requests are very welcome! Make sure your patches are well tested. Please create a topic branch for every separate change you make.
 * 
 * ## Authors
 */
(function($) {
    $.cookie = function(key, value, options) {

        // key and at least value given, set cookie...
        if (arguments.length > 1 && (!/Object/.test(Object.prototype.toString.call(value)) || value === null || value === undefined)) {
            options = $.extend({}, options);

            if (value === null || value === undefined) {
                options.expires = -1;
            }

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = String(value);

            return (document.cookie = [
                encodeURIComponent(key), '=', options.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path    ? '; path=' + options.path : '',
                options.domain  ? '; domain=' + options.domain : '',
                options.secure  ? '; secure' : ''
            ].join(''));
        }

        // key and possibly options given, get cookie...
        options = value || {};
        var decode = options.raw ? function(s) { return s; } : decodeURIComponent;

        var pairs = document.cookie.split('; ');
        for (var i = 0, pair; pair = pairs[i] && pairs[i].split('='); i++) {
            if (decode(pair[0]) === key) return decode(pair[1] || ''); // IE saves cookies with empty string as "c; ", e.g. without "=" as opposed to EOMB, thus pair[1] may be undefined
        }
        return null;
    };
})(jQuery);