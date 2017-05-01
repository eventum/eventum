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

namespace Eventum;

/**
 * Class MemoizeDecorator
 *
 * All class methods are memoized for same input arguments
 */
class MemoizeDecorator
{
    /** @var object class to be decorated */
    private $decorated;

    /**
     * Cache of memoized methods
     *
     * @var callable[]
     */
    private $methods = [];

    public function __construct($decorated)
    {
        $this->decorated = $decorated;
    }

    public function __call($method, $arguments)
    {
        if (!isset($this->methods[$method])) {
            $this->methods[$method] = $this->memoize($method);
        }

        return $this->methods[$method]($arguments);
    }

    /**
     * Return closure for memoized $method
     *
     * @param string $method
     * @return \Closure
     */
    private function memoize($method)
    {
        $function = [$this->decorated, $method];
        if (!is_callable($function)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected callable argument. %s given',
                    is_object($function) ? get_class($function) : gettype($function)
                )
            );
        }

        $memoized = [];

        return function ($args) use (&$memoized, $function) {
            $key = md5(serialize($args));

            if (!isset($memoized[$key])) {
                $memoized[$key] = call_user_func_array($function, $args);
            }

            return $memoized[$key];
        };
    }
}
