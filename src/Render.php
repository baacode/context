<?php

namespace Context;

/**
 * Base render class
 *
 * @package erayd/context
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 * @license ISC
 */
abstract class Render
{
    const RENDER_NONE             = 0;
    const RENDER_MARKUP           = 1 << 0;
    const RENDER_MARKUP_PARAGRAPH = 1 << 1;
    const RENDER_INDENT           = 1 << 2;
    const RENDER_COMPLETE         = 1 << 3;
    const RENDER_PRETTY           = 1 << 4;

    /** @var string Mimetype for header */
    protected $mimeType = 'unknown';

    /** @var array Text content & formatting info */
    protected $content = null;

    /**
     * Load the content
     *
     * @param string JSON-encoded content
     */
    public function __construct(Filter $filter)
    {
        $this->content = $filter->getContent();
    }

    /**
     * Clean up the punctuation in the provided paragraph
     *
     * @param string $paragraph
     * @return string
     */
    protected function smartPunctuation(string $paragraph) : string
    {
        $map = [
            '/\s+/u' => ' ',                                                   // normalise whitespace
            '/["\x{201D}\x{201C}]+/u' => '"',                                  // normalise double-quotes
            '/\s*"(.+?)"\s*/u' => ' “$1” ',                                    // fancy double-quote pairs
            '/^\s*"/u' => '“',                                                 // fancy double-quote paragraph start
            "/['\x{2018}\x{2019}]+/u" => "'",                                  // normalise single-quotes
            "/(?<=[\w])'(?=[\w])/u" => "’",                                    // fancy apostrophes
            "/(?<=s)'(?=[\s,.;:-\x{2014}?\x{201D}]|$)/u" => "’",               // fancy possessive apostrophes
            "/\s*'(.+?)'\s*/u" => ' ‘$1’ ',                                    // fancy single-quote pairs
            '/(?<=[^.]|^)\s*(?:\.(\s*)){3,5}(?=[^.]|$)/u' => '…',              // fancy ellipses
            '/(?:\s*-{2,}\s*)|(?:(?<=\w)-(?=\s))/u' => '—',                    // fancy emdash
            '/(?:(?<=[“‘]|^|^\s)-)|(?:-(?=\s*$|[”’,.;:!?\x{2026}]))/u' => '—', // fancy emdash
            '/\s+([.,;:!?\x{2026}])/u' => '$1',                                // correct preceeding whitespace
            '/(?<=[\w])\(\s+/u' => ' (',                                       // correct preceding whitespace
            '/([,.;:!?)\x{2026}])\s*(?=[\w])/u' => '$1 ',                      // correct trailing whitespace
            '/(\w\.)\s*(?=\w\.)/u' => '$1',                                    // correct acronym period spacing
            '|(?<=[\w])\s*/\s*(?=[\w])|u' => ' / ',                            // correct surrounding whitespace
            '/^\s*(.+?)\s*$/u' => "$1",                                        // remove wrapping whitespace
        ];
        foreach ($map as $from => $to) {
            $paragraph = preg_replace($from, $to, $paragraph);
        }
        return $paragraph;
    }

    /**
     * Render output
     *
     * @return string
     */
    abstract public function render(int $flags = self::RENDER_NONE) : string;

    /**
     * Get the mimetype
     *
     * @return string
     */
    final public function getMimeType() : string
    {
        return $this->mimeType;
    }
}
