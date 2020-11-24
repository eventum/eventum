<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\LinkFilter;

use Ds\Set;
use Symfony\Component\HttpFoundation\Request;

class LinkFilter
{
    /** @var Set */
    private $rules;
    /** @var Request */
    private $request;

    public function __construct(Request $request)
    {
        $this->rules = new Set();
        $this->request = $request;
    }

    public function addFilter(LinkFilterInterface $handler): self
    {
        foreach ($handler->getPatterns() as $pattern) {
            $this->addRule($pattern, $handler);
        }

        return $this;
    }

    public function addRule(string $pattern, $handler): self
    {
        $this->rules->add([$pattern, $handler]);

        return $this;
    }

    public function addRules(array $rules): self
    {
        foreach ($rules as $rule) {
            [$pattern, $handler] = $rule;
            $this->addRule($pattern, $handler);
        }

        return $this;
    }

    public function replace(string $text): string
    {
        foreach ($this->rules as $rule) {
            [$pattern, $handler] = $rule;
            if (is_callable($handler)) {
                $text = (string)preg_replace_callback($pattern, function ($text) use ($handler) {
                    return $handler($text, $this->request);
                }, $text);
            } else {
                $text = preg_replace($pattern, $handler, $text);
            }
        }

        return $text;
    }
}
