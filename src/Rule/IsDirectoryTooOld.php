<?php
declare(strict_types=1);

namespace duckpony\Rule;

use SplFileInfo;

final class IsDirectoryTooOld
{
    /**
     * This Rule returns true if the given $dir was modified more than $keepDays days ago.
     *
     * @param SplFileInfo $dir
     * @param int $keepDays
     * @return bool
     */
    public function appliesTo(SplFileInfo $dir, int $keepDays): bool
    {
        $maxCreationTime = strtotime('-' . $keepDays . ' day', time());
        return $dir->getMTime() < $maxCreationTime;
    }
}
