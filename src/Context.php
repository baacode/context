<?php

namespace Context;

/**
 * Main user API
 *
 * @package erayd/context
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 * @license ISC
 */
class Context
{
    /** @var Filter Filter instance */
    protected $filter = null;

    /**
     * Create a new content extractor
     *
     * @param string $content
     * @param string $filterClass
     */
    public function __construct(string $content, string $filterClass = HtmlFilter::class, ...$filterOptions)
    {
        $this->filter = new $filterClass($content, ...$filterOptions);
    }

    /**
     * Render content
     *
     * @param int $flags Render flags
     * @param string $renderClass
     * @return string
     */
    public function render(int $flags = Render::RENDER_NONE, string $renderClass = MarkdownRender::class) : string
    {
        $r = new $renderClass($this->filter);
        return $r->render($flags);
    }
}
