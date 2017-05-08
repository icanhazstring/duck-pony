<?php
/**
 * Created by IntelliJ IDEA.
 * User: andreas.froemer
 * Date: 08.05.2017
 * Time: 12:25
 */

namespace duckpony\Console\Command;


use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    protected function getRootPath()
    {
        return stream_resolve_include_path(__DIR__ . '/../../../');
    }
}