<?php

namespace Context;

/**
 * Base filter class
 *
 * @package erayd/context
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 * @license ISC
 */
abstract class Filter
{
    const FORMAT_NONE       = 0;
    const FORMAT_BREAK      = 1 << 0;
    const FORMAT_ITALIC     = 1 << 1;
    const FORMAT_BOLD       = 1 << 2;
    const FORMAT_UNDERLINE  = 1 << 3;
    const FORMAT_STRIKE     = 1 << 4;
    const FORMAT_CENTER     = 1 << 5;
    const FORMAT_NEWLINE    = 1 << 6;
    const FORMAT_RULE       = 1 << 7;
    const FORMAT_WHITESPACE = 1 << 8;

    const FMASK_STYLE = self::FORMAT_ITALIC | self::FORMAT_BOLD | self::FORMAT_UNDERLINE |
        self::FORMAT_STRIKE | self::FORMAT_CENTER;
    const FMASK_STRUCTURE = self::FORMAT_BREAK | self::FORMAT_NEWLINE | self::FORMAT_RULE;

    /** @var string Part content */
    protected $content = null;

    /**
     * Load the content
     *
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Return the content as a string
     *
     * @return string
     */
    public function render() : string
    {
        return json_encode($this->content);
    }

    /**
     * Check whether the provided paragraph is a manual section break
     *
     * @param string $paragraph
     * @return bool
     */
    protected function isManualBreak(string $paragraph) : bool
    {
        //  - any non-word, non-period character repeated at least three times
        //  - any two different non-word characters repeated in the same sequence >= three times
        //    + optionally with the first character appended again
        //  - five or more periods
        $pattern = '/^\s*(?:([^\w.])\1{2,}|([^\w])([^\w\2])(?:\2\3){2,}\2?|\.{5,})\s*$/u';

        return preg_match($pattern, $paragraph);
    }
}
