<?php

declare(strict_types=1);

namespace Qubus\Http\Swoole\Callback\Helpers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Http\Swoole\Callback\RequestCallback;
use Qubus\Http\Swoole\Callback\RequestCallbackOptions;

/**
 * @param callable(ServerRequestInterface):ResponseInterface|RequestHandlerInterface $handler
 * @param RequestCallbackOptions|null $options
 * @return RequestCallback
 */
function request_callback(
    callable|RequestHandlerInterface $handler,
    ?RequestCallbackOptions $options = null
): RequestCallback {
    if (is_callable($handler)) {
        return RequestCallback::fromCallable($handler, $options);
    }

    return new RequestCallback($handler, $options);
}
