<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\Console\Command\Argument\FolderArgumentTrait;
use duckpony\Console\Command\Option\PatternOptionTrait;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use SystemCtl\SystemCtl;
use SystemCtl\Unit\Service;
use Zend\Config\Config;

class PurgeIssueServiceCommand extends Command
{
    use AliasDeprecationTrait;

    use FolderArgumentTrait;
    use PatternOptionTrait;

    protected const DEPRECATION_ALIAS = 'service:purge';

    /** @var string */
    protected $unitName;
    protected $pattern;

    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        parent::__construct('issue:purge-service');
        // Used in traits
        $this->config = $config->get(self::class);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->configureFolderArgument();
        $this->addOption('unit', 'u', InputOption::VALUE_REQUIRED, 'Name of unit');
        $this->configurePatternOption();

        $this->setAliases([self::DEPRECATION_ALIAS])
            ->setDescription('Scan folder an purge services with same name')
            ->setHelp(
                <<<EOT
Disables and stops systemd services that have
no reference folder in given folder argument
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->checkAliasDeprecation($input, $io);

        $folder = $this->getFolder($input);
        $this->unitName = $input->getOption('unit');
        $this->pattern = $this->getPattern($input);

        $finder = new Finder();
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

        $removeServices = array_filter(
            $services,
            static function (Service $service) use ($dirList) {
                $serviceName = $service->isMultiInstance() ? $service->getInstanceName() : $service->getName();

                return !in_array($serviceName, $dirList, true);
            }
        );

        $io->title('Remove services');
        $io->progressStart(count($removeServices));
        foreach ($removeServices as $service) {
            $service->disable();
            $service->stop();
            /** @noinspection DisconnectedForeachInstructionInspection */
            $io->progressAdvance();
        }

        $io->progressFinish();

        return 0;
    }

    private function filterServices(Service $service): bool
    {
        if (strpos($service->getName(), $this->unitName) === false) {
            return false;
        }

        return (bool)preg_match($this->pattern, $service->getName());
    }
}
