<?php

declare(ticks=1);

namespace Qubus\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

class SapiEmitter extends BaseEmitter
{
    /**
     * {@inheritDoc}
     */
    public function emit(ResponseInterface $response): void
    {
        $this->assertNoPreviousOutput();
        $this->emitStatusLine($response);
        $this->emitHeaders($response);

        if ($this->shouldEmitBody($response)) {
            $this->emitBody($response);
        }

        $this->closeConnection();
    }

    /**
     * Emit the response body
     *
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }
}
