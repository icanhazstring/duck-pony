<?php

declare(strict_types=1);

namespace duckpony\Test\Unit\Rule;

use duckpony\Rule\IsDirectoryTooOld;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class IsDirectoryTooOldTest extends TestCase
{
    public function provideDirectories(): array
    {
        /** @var SplFileInfo|MockObject $dir */
        $dir = $this->createMock(SplFileInfo::class);
        $dir->method('getMTime')->willReturn(strtotime('-2 day', time()));

        return [
            'not too old' => [
                'dir' => $dir,
                'keepDays' => 2,
                'expectedResult' => false,
            ],
            'too old' => [
                'dir' => $dir,
                'keepDays' => 1,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideDirectories
     */
    public function testAppliesTo(SplFileInfo $dir, int $keepDays, bool $expectedResult): void
    {
        $subject = new IsDirectoryTooOld();
        $result = $subject->appliesTo($dir, $keepDays);
        Assert::assertSame($expectedResult, $result);
    }
}
