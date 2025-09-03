<?php

declare(strict_types=1);

namespace Qubus\Http\Emitter;

use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Emitter\Traits\EmitterTraitAware;

abstract class BaseEmitter implements Emitter
{
    use EmitterTraitAware;

    /**
     * {@inheritDoc}
     */
    abstract public function emit(ResponseInterface $response): void;
}
