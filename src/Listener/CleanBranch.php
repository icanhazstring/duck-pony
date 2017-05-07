<?php

namespace duckpony\Listener;

class CleanBranch implements ListenerInterface
{
    public function __invoke(InvocationParameterInterface $invocationParameter): bool {
        return false;
    }

}