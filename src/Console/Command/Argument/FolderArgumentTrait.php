<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Argument;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

trait FolderArgumentTrait
{
    protected function configureFolderArgument(): void
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'Target folder to scan');
    }

    protected function getFolder(InputInterface $input): string
    {
        $folder = $input->getArgument('folder');
        return stream_resolve_include_path($folder);
    }
}
