<?php

declare(strict_types=1);

namespace duckpony\Filesystem\Adapter;

use League\Flysystem\Adapter\Local as LeagueAdapterLocal;
use Symfony\Component\Process\Process;

class Local extends LeagueAdapterLocal
{
    /**
     * Delete multiple directories at once
     *
     * @param array $dirNames
     * @return bool
     */
    public function deleteDirs(array $dirNames): bool
    {
        $locations = array_map(function ($dir) {
            return $this->applyPathPrefix($dir);
        }, $dirNames);

        $locations = array_filter($locations, 'is_dir');

        if (empty($locations)) {
            return false;
        }

        $commandArguments = implode(' ', $locations);
        $process = new Process(['rm', '-rf', $commandArguments]);

        $process->run();

        return $process->isSuccessful();
    }
}
