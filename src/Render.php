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
     * Make the punctuation in the provided paragraph fancy
     *
     * @param string $paragraph
     * @return string
     */
    protected function smartPunctuation(string $paragraph) : string
    {
        $map = [
            '/["\x{201D}\x{201C}]+/u' => '"',
            '/"(.+?)"/u' => '“$1”',
            '/^"/u' => '“',
            "/['\x{2018}\x{2019}]+/u" => "'",
            "/(?<=[\w])'(?=[\w])/u" => "’",
            "/(?<=s)'(?=[\s,.;:-\x{2014}?\x{201D}]|$)/u" => "’",
            "/'(.+?)'/u" => '‘$1’',
            '/(?<=[^.]|^)\.\.\.(?=[^.]|$)/u' => '…',
            '/(?<=[^\1])([,.;:])(?=[\w])/' => '$1 ',
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
