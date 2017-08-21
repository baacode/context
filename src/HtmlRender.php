<?php

namespace Context;

/**
 * HTML render class
 *
 * @package erayd/context
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 * @license ISC
 */
class HtmlRender extends MarkdownRender
{
    /** @var string Mimetype for header */
    protected $mimeType = 'text/html';

    /**
     * Render output
     *
     * @return string
     */
    public function render(...$flags) : string
    {
        $content = parent::render('markup', 'p', ...$flags);
        if (in_array('complete', $flags)) {
            $content = "<!DOCTYPE html>\n<html>\n  <head></head>\n  <body>\n$content\n  </body>\n</html>\n";
        }
        return $content;
    }
}
