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
class MarkdownRender extends Render
{
    const FMTABLE_HTML = [
        Filter::FORMAT_ITALIC    => 'em',
        Filter::FORMAT_BOLD      => 'strong',
        Filter::FORMAT_UNDERLINE => 'u',
        Filter::FORMAT_STRIKE    => 's',
        Filter::FORMAT_CENTER    => 'center',
    ];

    const FMTABLE_MARKDOWN = [
        Filter::FORMAT_ITALIC    => '*',
        Filter::FORMAT_BOLD      => '**',
        Filter::FORMAT_STRIKE    => '~~',
        Filter::FORMAT_UNDERLINE => '__',
    ];

    /** @var string Mimetype for header */
    protected $mimeType = 'text/markdown';

    /**
     * Render output
     *
     * @return string
     */
    public function render(...$flags) : string
    {
        // whether to use html markup for text styling
        $useMarkup = in_array('markup', $flags);

        // whether to also use paragraph markup
        $useParagraphMarkup = $useMarkup && in_array('paragraph-markup', $flags);

        // whether to use fancy punctuation
        $fancy = in_array('fancy', $flags);

        $markdown = null;
        $paragraph = $useParagraphMarkup ? '<p>' : null;
        $mode = Filter::FORMAT_NONE;
        foreach ($this->content as $part) {
            $format = $part[0];

            // output breaks
            if ($paragraph) {
                if ($format & (Filter::FORMAT_RULE | Filter::FORMAT_BREAK)) {
                    $paragraph .= $this->closeFormat($mode, $useMarkup);
                    $paragraph .= ($useParagraphMarkup ? '</p>' : null) . "\n\n";
                    if ($format & Filter::FORMAT_RULE) {
                        $paragraph .= ($useMarkup ? '<hr />' : '***') . "\n\n";
                    }
                    $markdown .= $fancy ? $this->smartPunctuation($paragraph) : $paragraph;
                    $paragraph = null;
                    $mode = Filter::FORMAT_NONE;
                } elseif ($format & Filter::FORMAT_NEWLINE) {
                    $paragraph .= $useMarkup ? "<br />\n" : "\n";
                }
            }

            // output format changes
            $paragraph .= $this->closeFormat($mode & ~$format, $useMarkup);
            $paragraph .= $this->openFormat($format & ~$mode, $useMarkup);
            $text = $part[1];
            $paragraph .= ($format & (Filter::FORMAT_BREAK | Filter::FORMAT_NEWLINE)) ? ltrim($text) : $text;

            // set current mode
            $mode = $format;
        }
        $paragraph .= $this->closeFormat($mode, $useMarkup);

        $markdown .= $fancy ? $this->smartPunctuation($paragraph) : $paragraph;

        return wordwrap($markdown, 75, "\n", 100);
    }

    /**
     * Return the opening style tags for the given format
     *
     * @param int $format Style flags to apply
     * @param bool $useMarkup Whether to use markup for text styling
     * @return string
     */
    private function openFormat(int $format, $useMarkup) : string
    {
        $formatTable = $useMarkup ? self::FMTABLE_HTML : self::FMTABLE_MARKDOWN;
        $out = '';
        foreach ($formatTable as $key => $tag) {
            if ($format & $key) {
                $out .= $useMarkup ? "<$tag>" : $tag;
            }
        }
        return $out;
    }

    /**
     * Return the closing style tags for the given format
     *
     * @param int $format Style flags to apply
     * @param bool $useMarkup Whether to use markup for text styling
     * @return string
     */
    private function closeFormat(int $format, $useMarkup) : string
    {
        $formatTable = $useMarkup ? self::FMTABLE_HTML : self::FMTABLE_MARKDOWN;
        $out = '';
        foreach (array_reverse($formatTable, true) as $key => $tag) {
            if ($format & $key) {
                $out .= $useMarkup ? "</$tag>" : $tag;
            }
        }
        return $out;
    }
}
