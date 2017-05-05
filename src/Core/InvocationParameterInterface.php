<?php

namespace duckpony\Listener;


interface InvocationParameterInterface
{
    public function get(string $param);
}