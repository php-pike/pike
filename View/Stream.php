<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
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
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */

/**
 * Turns on auto variable escaping in the view by default to prevent XSS attacks
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Stream extends Zend_View_Stream
{

    /**
     * Opens the script file and converts markup.
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        // get the view script source
        $path = str_replace('zend.view://', '', $path);
        $this->_data = file_get_contents($path);

        /**
         * If reading the file failed, update our local stat store
         * to reflect the real stat of the file, then return on failure
         */
        if ($this->_data === false) {
            $this->_stat = stat($path);
            return false;
        }

        /**
         * Convert <? ?> to <?php ?> and <?= ?> to long-form <?php echo ?> and auto escape
         *
         * Values with <?=~ ?> will convert to <?php echo ?> but NOT be auto escaped (raw value)
         */
        $this->_data = self::autoEscape($this->_data);

        /**
         * file_get_contents() won't update PHP's stat cache, so we grab a stat
         * of the file to prevent additional reads should the script be
         * requested again, which will make include() happy.
         */
        $this->_stat = stat($path);

        return true;
    }

    /**
     * Auto escapes variables
     *
     * @param  string $data
     * @return string
     */
    public static function autoEscape($data)
    {
        // Convert "<?" to "<?php"
        $data = preg_replace('/<\?(?!xml|php|=)/s', '<?php ', $data);

        // Convert "<?php echo $var" to "<?php echo $this->escape($var)"
        $data = preg_replace('/\<\?php\s*\n*\s*echo\s*\n*\s*(.*?);*\s*\n*\s*\?>/m',
            '<?php echo $this->escape((string)$1); ?>', $data);

        // Convert "<?= $var" to "<?php echo $this->escape($var)"
        $data = preg_replace('/\<\?\=\s*\n*\s*(.*?);*\s*\n*\s*\?>/m',
            '<?php echo $this->escape((string)$1); ?>', $data);

        // Convert raw value that are defined as "<?=~", but are converted with the line above to
        // "<?php echo $this->escape(~$var)". Convert these cases to "<?php echo $var"
        $data = preg_replace('/\<\?php\s*\n*\s*echo\s*\n*\s*\$this-\>escape\(\(string\)~(.*?)\);*\s*\n*\s*\?>/m',
            '<?php echo $1; ?>', $data);
        $data = preg_replace('/\<\?=~\s*\n*\s*(.*?);*\s*\n*\s*\?>/m',
            '<?php echo $1; ?>', $data);

        return $data;
    }
}