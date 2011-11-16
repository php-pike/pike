<?php
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

/**
 * Pike debug
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Debug extends Zend_Debug
{
    /**
     * Dumps a variable
     *
     * This function is intended to replace the buggy PHP function var_dump and print_r.
     * It can correctly identify the recursively referenced objects in a complex
     * object structure. It also has a recursive depth control to avoid indefinite
     * recursive display of some peculiar variables.
     *
     * It also adds the <pre /> tags, cleans up newlines and indents, runs
     * htmlentities() before output and optionally highlights the dump.
     *
     * @param  mixed   $var       The variable to dump.
     * @param  integer $depth     OPTIONAL The depth of the stack trace to show.
     * @param  string  $label     OPTIONAL Label to prepend to output.
     * @param  boolean $echo      OPTIONAL Echo output if true.
     * @param  boolean $highlight OPTIONAL Highlights the dump if true.
     * @return string
     */
    public static function dump($var, $depth = 10, $label = null, $echo = true, $highlight = false)
    {
        // format the label
        $label = ($label===null) ? '' : rtrim($label) . ' ';

        if (is_null($var)) {
            $output = 'NULL';
        } else if (is_string($var) && '' == $var) {
            $output = 'EMPTY STRING';
        } else {
            $output = Pike_Debug_TVarDumper::dump($var, $depth, $highlight);

            // neaten the newlines and indents
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        }

        if (self::getSapi() == 'cli') {
            $output = PHP_EOL . $label
                    . PHP_EOL . $output
                    . PHP_EOL;
        } else {
            if(!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, ENT_QUOTES);
            }

            $output = '<pre>'
                    . $label
                    . $output
                    . '</pre>';
        }

        if ($echo) {
            echo($output);
        }

        return $output;
    }
}