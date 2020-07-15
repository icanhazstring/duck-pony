<?php

declare(strict_types=1);

namespace duckpony\Test\Unit\Console\Command\Option;

use duckpony\Console\Command\Option\KeepDaysOptionTrait;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class KeepDaysOptionTraitTest extends TestCase
{
    /** @var InputInterface */
    private $input;

    /** @var KeepDaysOptionTrait */
    private $subject;

    protected function setUp(): void
    {
        $this->input = $input = new ArrayInput(
            [],
            new InputDefinition([
                new InputOption(
                    'keep-days',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'The number of days a branch is allowed to remain.'
                ),
            ])
        );

        $this->subject = new class {
            use KeepDaysOptionTrait {
                getKeepDays as traitGetKeepDays;
            }

            public function getKeepDays(InputInterface $input): ?int
            {
                return $this->traitGetKeepDays($input);
            }
        };
    }

    public function provideValidKeepDaysOptionValues(): array
    {
        return [
            ['1', 1],
            ['2', 2],
            [(string) PHP_INT_MAX, PHP_INT_MAX],
            [1, 1],
            [2, 2],
            [PHP_INT_MAX, PHP_INT_MAX],
            [null, null],
        ];
    }

    /**
     * @dataProvider provideValidKeepDaysOptionValues
     */
    public function testGetKeepDays($inputOptionValue, ?int $expectedOptionValue): void
    {
        $this->input->setOption('keep-days', $inputOptionValue);

        $keepDays = $this->subject->getKeepDays($this->input);
        Assert::assertSame($expectedOptionValue, $keepDays);
    }

    public function provideInvalidKeepDaysOptionValues(): array
    {
        return [
            ['0', 'Option "keep-days" must be an integer and bigger than 0, "0" given.'],
            ['-1', 'Option "keep-days" must be an integer and bigger than 0, "-1" given.'],
            ['foo', 'Option "keep-days" must be an integer and bigger than 0, "foo" given.'],
            ['true', 'Option "keep-days" must be an integer and bigger than 0, "true" given.'],
        ];
    }

    /**
     * @dataProvider provideInvalidKeepDaysOptionValues
     */
    public function testGetKeepDaysWithInvalidValue(string $inputOptionValue, string $expectedExceptionMessage): void
    {
        $this->input->setOption('keep-days', $inputOptionValue);

        $this->expectExceptionObject(new InvalidArgumentException($expectedExceptionMessage));
        $this->subject->getKeepDays($this->input);
    }
}
