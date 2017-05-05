<?php

declare(strict_types=1);

namespace duckpony\Listener;

interface ListenerInterface
{
    /**
     * Invoke listener
     *
     * @param InvocationParameterInterface $invocationParameter
     *
     * @return bool
     */
    public function __invoke(InvocationParameterInterface $invocationParameter) : bool;
}