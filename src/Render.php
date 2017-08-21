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
    /** @var string Mimetype for header */
    protected $mimeType = 'unknown';

    /** @var array Text content & formatting info */
    protected $content = null;

    /**
     * Load the content
     *
     * @param string JSON-encoded content
     */
    public function __construct(string $content)
    {
        $this->content = json_decode($content);
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
            '/\s+/u' => ' ',                                       // normalise whitespace
            '/["\x{201D}\x{201C}]+/u' => '"',                      // normalise double-quotes
            '/"(.+?)"/u' => '“$1”',                                // fancy double-quote pairs
            '/^"/u' => '“',                                        // fancy double-quote paragraph start
            "/['\x{2018}\x{2019}]+/u" => "'",                      // normalise single-quotes
            "/(?<=[\w])'(?=[\w])/u" => "’",                        // fancy apostrophes
            "/(?<=s)'(?=[\s,.;:-\x{2014}?\x{201D}]|$)/u" => "’",   // fancy possessive apostrophes
            "/'(.+?)'/u" => '‘$1’',                                // fancy single-quote pairs
            '/(?<=[^.]|^)\s*(?:\.(\s*)){3,5}(?=[^.]|$)/u' => '… ', // fancy ellipses
            '/\s+([.,;:!?\x{2026}])/u' => '$1',                    // correct preceeding whitespace
            '/(?<=[\w])\(\s+/u' => ' (',                           // correct preceding whitespace
            '/([,.;:!?)\x{2026}])\s*(?=[\w])/u' => '$1 ',          // correct trailing whitespace
            '|(?<=[\w])\s*/\s*(?=[\w])|u' => ' / ',                // correct surrounding whitespace
            '/^\s*(.+?)\s*$/u' => "$1",                            // remove wrapping whitespace
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
    abstract public function render() : string;

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
