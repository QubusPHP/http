<?php

declare(strict_types=1);

namespace Qubus\Http\Factories;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Qubus\Http\Exception\MalformedUrlException;
use Qubus\Http\Request;

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @inheritDoc
     * @throws MalformedUrlException
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($uri, $method);
    }
}
