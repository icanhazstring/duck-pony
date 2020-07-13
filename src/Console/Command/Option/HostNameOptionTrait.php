<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Option;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @uses \Zend\Config\Config
 * @author Dogan Ucar <dogan@dogan-ucar.de>
 */
trait HostNameOptionTrait
{

    protected function configureHostNameOption(): void
    {
        $this->addOption('host_name', 'h', InputOption::VALUE_REQUIRED, 'The hostname of the master host machine');
    }

    protected function getHostName(InputInterface $input): ?string
    {
        return $input->getOption('host_name') ?? null;
    }

    protected function canRunOnHost(?string $hostNameFromOptions, SymfonyStyle $io): bool
    {
        $hostName = gethostname();
        $hostName = $hostName === false ? null : $hostName;

        if ($hostName === null) {
            $io->warning('No hostname. Please configure so that gethostname() returns a value. The command will run!');
            return true;
        }

        if ($hostNameFromOptions === null) {
            $io->note('No configurations regarding to master/slave found. The command will run regularly on any host!');
            return true;
        }

        return $hostNameFromOptions === $hostName;
    }
}
