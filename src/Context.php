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
    const DEFAULT_FILTER = 1;
    const DEFAULT_RENDER = 2;
    const CONTAINER_PATH = 3;

    /** @var array Configuration options */
    protected $config = [
        self::DEFAULT_FILTER => HtmlFilter::class,
        self::DEFAULT_RENDER => MarkdownRender::class,
        self::CONTAINER_PATH => '/html/body',
    ];

    /** @var Filter Filter instance */
    protected $filter = null;

    /**
     * Create a new content extractor
     *
     * @param string $content
     * @param array $options
     */
    public function __construct(string $content, array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->config[$key] = $value;
        }

        $filterClass = $this->getConfig(self::DEFAULT_FILTER);
        $this->filter = new $filterClass($this, $content);
    }

    /**
     * Render content
     *
     * @param int $flags Render flags
     * @param string $renderClass
     * @return string
     */
    public function render(int $flags = Render::RENDER_NONE, string $renderClass = null) : string
    {
        if (is_null($renderClass)) {
            $renderClass = $this->getConfig(self::DEFAULT_RENDER);
        }
        $r = new $renderClass($this, $this->filter);
        return $r->render($flags);
    }

    /**
     * Get config option
     *
     * @param int $option
     * @return mixed
     */
    public function getConfig(int $option)
    {
        return $this->config[$option] ?? null;
    }
}
