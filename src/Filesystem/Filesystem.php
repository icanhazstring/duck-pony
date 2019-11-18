<?php

declare(strict_types=1);

namespace duckpony\Filesystem;

use duckpony\Filesystem\Adapter\Local;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\Util;
use SplFileInfo;

class Filesystem extends LeagueFilesystem
{
    /**
     * @param SplFileInfo[] $dirs
     * @return bool
     */
    public function deleteDirs(array $dirs): bool
    {
        $dirnames = array_map(static function (SplFileInfo $dir) {
            return Util::normalizePath($dir->getFilename());
        }, $dirs);

        $adapter = $this->getAdapter();

        if ($adapter instanceof Local) {
            return $adapter->deleteDirs($dirnames);
        }

        $success = true;

        foreach ($dirnames as $dirname) {
            $success &= $adapter->deleteDir($dirname);
        }

        return $success;
    }
}
