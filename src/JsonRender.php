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
class JsonRender extends Render
{
    /** @var string Mimetype for header */
    protected $mimeType = 'application/json+context';

    /**
     * Render output
     *
     * @return string
     */
    public function render(int $flags = self::RENDER_NONE) : string
    {
        return json_encode($this->content, ($flags & self::RENDER_PRETTY) ? \JSON_PRETTY_PRINT : 0);
    }
}
