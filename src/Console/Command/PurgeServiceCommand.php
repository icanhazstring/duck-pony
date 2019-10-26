<?php
declare(strict_types=1);

namespace duckpony\Console\Command;

use Psr\Log\LoggerInterface;
use SplFileInfo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use SystemCtl\SystemCtl;
use SystemCtl\Unit\Service;

class PurgeServiceCommand extends AbstractCommand
{
    /** @var string */
    protected $unitName;
    protected $pattern;

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('folder', InputArgument::REQUIRED, 'Deployment folder as reference');
        $this->addOption('unit', 'u', InputOption::VALUE_REQUIRED, 'Name of unit');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Instance pattern');

        $this->setName('service:purge')
            ->setDescription('Scan folder an purge services with same name')
            ->setHelp(
                <<<EOT
Disables and stops systemd services that have
no reference folder in given folder argument
EOT
            );
    }

    protected function executeWithConfig(
        InputInterface $input,
        OutputInterface $output,
        LoggerInterface $logger,
        array $config
    ): int {
        $io = new SymfonyStyle($input, $output);

        $folder         = stream_resolve_include_path($input->getArgument('folder'));
        $this->unitName = $input->getOption('unit');
        $this->pattern  = $input->getOption('pattern') ?? $config['PurgeService']['pattern'];

        $finder      = new Finder();
        $directories = $finder->depth(0)->directories()->in($folder);

        SystemCtl::setTimeout(10);
        $systemCtl = new SystemCtl();

        /** @var Service[] $services */
        $services = array_filter($systemCtl->getServices($this->unitName), [$this, 'filterServices']);
        $directories->filter(function (SplFileInfo $dir) {
            return preg_match($this->pattern, $dir->getFilename());
        });

        $dirList = [];
        foreach ($directories->directories() as $dir) {
            /** @var SplFileInfo $dir */
            $dirList[] = $dir->getFilename();
        }

        $removeServices = array_filter($services,
            static function (Service $service) use ($dirList) {
                $serviceName = $service->isMultiInstance() ? $service->getInstanceName() : $service->getName();

                return !in_array($serviceName, $dirList, true);
            });

        $io->title('Remove services');
        $io->progressStart(count($removeServices));
        foreach ($removeServices as $service) {
            $service->disable();
            $service->stop();
            /** @noinspection DisconnectedForeachInstructionInspection */
            $io->progressAdvance();
        }

        $io->progressFinish();
    }

    private function filterServices(Service $service): bool
    {
        if (strpos($service->getName(), $this->unitName) === false) {
            return false;
        }

        return (bool)preg_match($this->pattern, $service->getName());
    }
}
