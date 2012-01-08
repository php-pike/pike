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
 */

/**
 * Sets PDF page defaults and adds convenient methods
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Pdf_Page extends Zend_Pdf_Page
{
    /**
     * Page size
     *
     * @var string
     */
    public $pageSize = self::SIZE_A4;

    /**
     * Character encoding
     *
     * @var string
     */
    public $charEncoding = 'UTF-8';

    /**
     * Font name
     *
     * @var string
     */
    public $fontName = Zend_Pdf_Font::FONT_HELVETICA;

    /**
     * Font name bold
     *
     * @var string
     */
    public $fontNameBold = Zend_Pdf_Font::FONT_HELVETICA_BOLD;

    /**
     * Font
     *
     * @var Zend_Pdf_Resource_Font
     */
    public $font;

    /**
     * Font bold
     *
     * @var Zend_Pdf_Resource_Font
     */
    public $fontBold;

    /**
     * Font size
     *
     * @var integer
     */
    public $fontSize = 10;

    /**
     * Line height
     *
     * 1.1 = 110% of text
     * To calculate the actual line height for a font use $this->lineHeight * $this->getFontSize()
     *
     * @var float
     */
    public $lineHeight = 1.1;

    /**
     * Margin top
     *
     * @var float
     */
    public $marginTop = 40;

    /**
     * Margin right
     *
     * @var float
     */
    public $marginRight = 40;

    /**
     * Margin bottom
     *
     * @var float
     */
    public $marginBottom = 40;

    /**
     * Margin left
     *
     * @var float
     */
    public $marginLeft = 40;

    /**
     * Text align
     *
     * @var string
     */
    public $textAlign = 'left';

    /**
     * X coordinate
     *
     * @var float
     */
    public $x = 0;

    /**
     * Y coordinate
     *
     * @var float
     */
    public $y = 0;

    /**
     * Constructor
     *
     * @param mixed $param1
     * @param mixed $param2
     * @param mixed $param3
     * @see parent::__construct
     */
    public function __construct($param1 = null, $param2 = null, $param3 = null)
    {
        $param1 = $this->pageSize;
        parent::__construct($param1, $param2, $param3);

        $this->font = Zend_Pdf_Font::fontWithName($this->fontName);
        $this->fontBold = Zend_Pdf_Font::fontWithName($this->fontNameBold);
        $this->setFont($this->font, $this->fontSize);
    }

    /**
     * Draws text with x and y coordinates that respect the page margins
     *
     * @param string $text
     * @param float  $x
     * @param float  $y
     * @param string $textAlign
     * @param string $charEncoding
     */
    public function drawText($text, $x, $y, $textAlign = null, $charEncoding = null)
    {
        if (null === $charEncoding) {
            $charEncoding = $this->charEncoding;
        }

        if (null === $textAlign) {
            $textAlign = $this->textAlign;
        }

        $x += $this->marginLeft;
        $y += $this->marginBottom;

        return parent::drawText($text, $x, $y, $charEncoding);
    }

    /**
     * Draws text with word wrap that depends on the specified max characters per line
     *
     * @param  string $text
     * @param  float  $x
     * @param  float  $y
     * @param  float  $maxCharsPerLine
     * @param  string $textAlign
     * @param  string $charEncoding
     * @return array  An array of created pages represented by Zend_Pdf_Page objects
     */
    public function drawTextBox($text, $x, $y, $maxCharsPerLine = null, $textAlign = null,
        $charEncoding = null
    ) {
        $createdPages = array();
        $bodyHeight = $this->getBodyHeight();
        $startHeight = $y + $this->marginBottom;
        $lineHeight = $this->getFontSize() * $this->lineHeight;
        if (null === $maxCharsPerLine) {
            $maxCharsPerLine = 75;
        }
        if (null === $charEncoding) {
            $charEncoding = $this->charEncoding;
        }

        $lines = explode("\n", Zend_Text_MultiByte::wordWrap(
            $text, $maxCharsPerLine, "\n", false, $charEncoding));

        foreach ($lines as $index => $line) {
            // Check if lines exceed the current page
            $resultHeight = $bodyHeight + ($y < $lineHeight ? $y + $lineHeight : $y);
            if ($resultHeight < $bodyHeight) {

                // Set max page lines for an empty page
                $maxPageLines = floor($bodyHeight / $lineHeight);

                $chunks = array_chunk($lines, $maxPageLines);
                foreach ($chunks as $chunk) {
                    $page = new Pike_Pdf_Page();
                    $page->drawTextBox(implode("\n", $chunk), $x, $this->getBodyHeight(), $maxCharsPerLine);
                    $createdPages[] = $page;
                }

                break;
            }

            // Write line on the current page
            $this->drawText($line, $x, $y);
            unset($lines[$index]);
            $y -= $lineHeight;
        }

        return $createdPages;
    }

    /**
     * Draws an image by the specified dimensions
     *
     * Proportions can be constraint.
     *
     * @param  string              $imagePath
     * @param  float               $x
     * @param  float               $y
     * @param  string|integer|null $maxWidth  Maximum width
     * @param  string|integer|null $maxHeight Maximum height
     * @param  boolean             $constrainProportions
     * @param  boolean             $allowScaleUp If the image must be pixelated. Default is false.
     * @return Zend_Pdf_Canvas_Interface
     */
    public function drawImageByDimensions($imagePath, $x1, $y1,
        $maxWidth = '*', $maxHeight = '*', $constrainProportions = true, $allowScaleUp = false
    ) {
        $x1 += $this->marginLeft;
        $y1 += $this->marginBottom;

        $image = Zend_Pdf_Image::imageWithPath($imagePath);

        if ($constrainProportions === false && $maxWidth > 0 && $maxHeight > 0) {
            $dimensions = array('width' => $maxWidth, 'height' => $maxHeight);
        } else {
            $dimensions = $this->resizeImage($imagePath, $maxWidth, $maxHeight, $allowScaleUp);
        }

        $x2 = $x1 + $dimensions['width'];
        $y2 = $y1 + $dimensions['height'];

        return $this->drawImage($image, $x1, $y1, $x2, $y2);
    }

    /**
     * Resizes an image proportionally
     *
     * Example usage:
     *   // Resize proportionally to make the object fit in a 300px by 200px space
     *   $this->resizeImage('example.png', 300, 200);
     *
     *   // Resize to 300px wide and calculates the height proportionally
     *   $this->resizeImage('example.png', 300, '*');
     *
     *   // Resize to 200px high and calculates the width proportionally
     *   $this->resizeImage('example.png', '*', 200);
     *
     *   // Resize proportionally to make the largest side 300px
     *   $this->resizeImage('example.png', 300);
     *
     *   // Resize proportionally to make the smallest side 200px
     *   $this->resizeImage("example.png", null, 200);
     *
     * @param  string              $imagePath    Image path
     * @param  string|integer|null $maxWidth     Maximum width
     * @param  string|integer|null $maxHeight    Maximum height
     * @param  boolean             $allowScaleUp If the image must be pixelated. Default is false.
     * @return array|false
     */
    public function resizeImage($imagePath, $maxWidth = '*', $maxHeight = '*', $allowScaleUp = false)
    {
        $imageSize = getimagesize($imagePath);

        // Check if the file exists and is an image
        if ($imageSize) {
            $oldWidth  = $imageSize[0];
            $oldHeight = $imageSize[1];

            // Check if resize is needed
            if (($oldWidth > $maxWidth && '*' != $maxWidth)
                || ($oldHeight > $maxHeight && '*' != $maxHeight)
                || true === $allowScaleUp
            ) {
                if ($maxWidth && '*' == $maxHeight) {
                    // Constrain by width
                    $proportion = $oldHeight / $oldWidth;
                    $width = $maxWidth;
                    $height = $maxWidth * $proportion;
                } else if ($maxHeight && '*' == $maxWidth) {
                    // Constrain by height
                    $proportion = $oldWidth / $oldHeight;
                    $height = $maxHeight;
                    $width = $maxHeight * $proportion;
                } else if (!$maxWidth && $maxHeight) {
                    // Constrain by smallest side
                    if ($oldWidth > $oldHeight) {
                        return $this->resizeImage($imagePath, '*', $maxHeight, $allowScaleUp);
                    } else {
                        return $this->resizeImage($imagePath, $maxWidth, '*', $allowScaleUp);
                    }
                } else if ($maxWidth && !$maxHeight) {
                    // Constrain by largest side
                    if ($oldWidth > $oldHeight) {
                        return $this->resizeImage($imagePath, $maxWidth, '*', $allowScaleUp);
                    } else {
                        return $this->resizeImage($imagePath, '*', $maxHeight, $allowScaleUp);
                    }
                } else {
                    if ($maxWidth > $maxHeight) {
                        // Constrain by height
                        return $this->resizeImage($imagePath, '*', $maxHeight, $allowScaleUp);
                    } else {
                        // Constrain by width
                        return $this->resizeImage($imagePath, $maxWidth, '*', $allowScaleUp);
                    }
                }
            } else {
                $width  = $oldWidth;
                $height = $oldHeight;
            }

            $width  = round($width);
            $height = round($height);

            return array('width' => $width, 'height' => $height);
        } else {
            return false;
        }
    }

    /**
     * Returns the body width
     */
    public function getBodyWidth()
    {
        return $this->getWidth() - $this->marginLeft - $this->marginRight;
    }

    /**
     * Returns the body height
     */
    public function getBodyHeight()
    {
        return $this->getHeight() - $this->marginTop - $this->marginBottom;
    }
}