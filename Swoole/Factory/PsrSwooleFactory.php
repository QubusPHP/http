<?php

declare(strict_types=1);

namespace Qubus\Http\Swoole\Factory;

use Psr\Http\Message\ServerRequestInterface;
use Qubus\Http\Swoole\Request;
use Swoole\Http\Request as SwooleRequest;

interface PsrSwooleFactory
{
    public function createRequest(SwooleRequest $swooleRequest): Request;
    public function createServerRequest(SwooleRequest $swooleRequest): ServerRequestInterface;
}
