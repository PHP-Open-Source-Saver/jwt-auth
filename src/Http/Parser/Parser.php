<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) 2014-2021 Sean Tymon <tymon148@gmail.com>
 * (c) 2021 PHP Open Source Saver
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPOpenSourceSaver\JWTAuth\Http\Parser;

use Illuminate\Http\Request;

class Parser
{
    /**
     * The chain.
     */
    private array $chain;

    /**
     * The request.
     */
    protected Request $request;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(Request $request, array $chain = [])
    {
        $this->request = $request;
        $this->chain = $chain;
    }

    /**
     * Get the parser chain.
     *
     * @return array
     */
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * Add a new parser to the chain.
     *
     * @param array|\PHPOpenSourceSaver\JWTAuth\Contracts\Http\Parser $parsers
     *
     * @return $this
     */
    public function addParser($parsers)
    {
        $this->chain = array_merge($this->chain, is_array($parsers) ? $parsers : [$parsers]);

        return $this;
    }

    /**
     * Set the order of the parser chain.
     *
     * @return $this
     */
    public function setChain(array $chain)
    {
        $this->chain = $chain;

        return $this;
    }

    /**
     * Alias for setting the order of the chain.
     *
     * @return $this
     */
    public function setChainOrder(array $chain)
    {
        return $this->setChain($chain);
    }

    /**
     * Iterate through the parsers and attempt to retrieve
     * a value, otherwise return null.
     *
     * @return string|null
     */
    public function parseToken()
    {
        foreach ($this->chain as $parser) {
            if ($response = $parser->parse($this->request)) {
                return $response;
            }
        }
    }

    /**
     * Check whether a token exists in the chain.
     *
     * @return bool
     */
    public function hasToken()
    {
        return null !== $this->parseToken();
    }

    /**
     * Set the request instance.
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
