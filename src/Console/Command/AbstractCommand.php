<?php
declare(strict_types=1);

namespace duckpony\Console\Command;

use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    protected function getRootPath()
    {
        return stream_resolve_include_path(__DIR__ . '/../../../');
    }
}