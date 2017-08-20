<?php

namespace Context;

/**
 * Extracts text from HTML
 *
 * @package erayd/context
 * @copyright (c) 2017 Erayd LTD
 * @author Steve Gilberd <steve@erayd.net>
 * @license ISC
 */
class HTMLFilter extends Filter {
    /** @var bool Whether to use markup in the output */
    protected $useMarkup = false;

    /**
     * Load HTML content
     *
     * @param string $content
     */
    public function __construct(string $content)
    {
        // load content document
        $d = new \DOMDocument('1.0', 'utf8');
        $errorMode = libxml_use_internal_errors(true);
        $d->loadHTML($content);
        $x = new \DOMXPath($d);
        libxml_use_internal_errors($errorMode);

        // find the closest container that holds enough of the text
        $ancestors = [];
        $discard = '#/head[[/]|/a[[/]|/script[[/]|/form[[/]|/select[[/]#ui';
        foreach ($x->query('//text()') as $textNode) {
            if (preg_match($discard, $textNode->getNodePath())) {
                continue;
            }
            $content = $textNode->wholeText;
            $contentLength = mb_strlen($content);
            $contentWords = str_word_count($content);
            if ($contentWords > 2 && $contentLength > 20 && $contentLength / $contentWords >= 3) {
                $ancestors[$textNode->parentNode->getNodePath()] += $contentWords;
            }
        }
        while (count($ancestors) > 1 && end($ancestors) < array_sum($ancestors) * 0.7) {
            asort($ancestors, \SORT_NUMERIC);
            end($ancestors);
            $container = substr(key($ancestors), 0, strrpos(key($ancestors), '/'));
            foreach ($ancestors as $path => $words) {
                if (substr($path, 0, strlen($container)) === $container) {
                    $ancestors[$container] += $words;
                    unset($ancestors[$path]);
                }
            }
        }

        // get the container node
        if (!($containerNode = $x->query($container)->item(0))) {
            throw new \Exception('Missing container');
        }

        // find the text nodes
        $textNodes = [];
        $carry = self::FORMAT_NONE;
        $targetQuery = './/*[not(self::script|self::style)]/text()|//br|//hr';
        foreach ($x->query($targetQuery, $containerNode) as $textNode) {
            if ($textNode instanceof \DOMElement) {
                switch (strtolower($textNode->tagName)) {
                    case 'hr':
                        $carry |= self::FORMAT_RULE;
                        break;
                    case 'br': 
                        if ($carry & self::FORMAT_NEWLINE) {
                            $carry = ($carry & ~self::FORMAT_NEWLINE) | self::FORMAT_BREAK;
                        } else {
                            $carry |= self::FORMAT_NEWLINE;
                        }
                        break;
                }
            } elseif($textNode instanceof \DOMText) {
                $textNode->normalize();
                $textNodes[$textNode->getNodePath()] = [$carry, $textNode];
                $carry = self::FORMAT_NONE;
            }
        }
        if (empty($textNodes)) {
            throw new \Exception('No text available');
        }

        // set the container to the closest common ancestor
        $container = reset($textNodes)[1];
        foreach ($textNodes as $path => $node) {
            while (strpos($node[1]->getNodePath(), $container->getNodePath()) !== 0) {
                $container = $container->parentNode;
            }
        }

        // extract formatting information
        $section = $d->createElement('p');
        $previousStyle = self::FORMAT_NONE;
        foreach ($textNodes as $path => &$node) {
            // whitespace-only nodes do not contain anything that can be
            // formatted, so just use the same style as the previous node
            if (node[1]->isWhitespaceInElementContent || trim($node[1]->wholeText) === '') {
                $node[0] = $previousStyle | self::FORMAT_WHITESPACE;
                continue;
            }

            // capture manual section breaks
            if ($this->isManualBreak($node[1]->wholeText)) {
                $previousStyle |= $node[0];
                $node[0] = self::FORMAT_RULE;
                continue;
            }

            $carryFormat = $node[0];
            // walk ancestors and parse text styling...
            if (!$container->isSameNode($node[1]->parentNode)) { // ...unless the text node is a direct child of the container
                for ($n = $node[1]->parentNode; is_object($n) && $n instanceof \DOMElement; $n = $n->parentNode) {
                    if (!($n instanceof \DOMElement)) {
                        continue;
                    }
                    $initialFormat = $node[0];
                    switch ($n->tagName) {
                        case 'em':
                        case'i': {
                            $node[0] |= self::FORMAT_ITALIC;
                            break;
                        }
                        case 'strong':
                        case 'b': {
                            $node[0] |= self::FORMAT_BOLD;
                            break;
                        }
                        case 'u':
                        case 'a': {
                            $node[0] |= self::FORMAT_UNDERLINE;
                            break;
                        }
                        case 's': {
                            $node[0] |= self::FORMAT_STRIKE;
                            break;
                        }
                        case 'center': {
                            $node[0] |= self::FORMAT_CENTER;
                            break;
                        }
                        default: {
                            if (!$section->isSameNode($n)) {
                                $node[0] |= self::FORMAT_BREAK;
                            }

                            $style = strtolower($n->getAttribute('style'));
                            if (strpos($style, 'italic') !== false) {
                                $node[0] |= self::FORMAT_ITALIC;
                            }

                            if (strpos($style, 'bold') !== false) {
                                $node[0] |= self::FORMAT_BOLD;
                            }

                            if (strpos($style, 'underline') !== false) {
                                $node[0] |= self::FORMAT_UNDERLINE;
                            }

                            if (strpos($style, 'line-through') !== false) {
                                $node[0] |= self::FORMAT_STRIKE;
                            }

                            if (preg_match('|text-align:[^;]*center|', $style)) {
                                $node[0] |= self::FORMAT_CENTER;
                            }

                        }
                    }

                    // don't parse the container
                    if ($container->isSameNode($n->parentNode)) {
                        break;
                    } elseif($node[0] & self::FMASK_STYLE & ~$initialFormat) {
                        // if this node introduced new style formatting, strip everything else
                        $node[0] &= self::FMASK_STYLE;
                    }
                }
            }

            // restore carried format options
            $node[0] |= $carryFormat;

            // update the section pointer
            if ($node[0] & self::FORMAT_BREAK && !$n->isSameNode($section)) {
                $section = $n;
            }

            // update current style for next node
            $previousStyle = $node[0] & self::FMASK_STYLE;
        }
        unset($node); // clear &$node reference from loop

        // save text nodes
        $this->content = [];
        foreach ($textNodes as $node) {
            $this->content[] = [$node[0], preg_replace('/\s+/', ' ', $node[1]->wholeText)];
        }
    }
}
