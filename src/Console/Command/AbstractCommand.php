<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

abstract class AbstractCommand extends Command
{
    use SlackLoggerAwareTrait;

    /** @var Logger */
    protected $logger;

    public function __construct(Logger $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Config',
            $this->getRootPath() . '/config/config.yml'
        );
    }

    protected function getRootPath(): string
    {
        return stream_resolve_include_path(__DIR__ . '/../../../');
    }

    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Yaml::parse(file_get_contents($input->getOption('config')));
        $this->initSlackLogger($this->logger, $config);

        try {
            return $this->executeWithConfig($input, $output, $config);
        } catch (Throwable $t) {
            $this->logger->emergency(
                $t->getMessage(),
                [
                            'Duck-Pony'  => 'Unhandled Exception during execution of ' . $input->getFirstArgument(),
                            'StackTrace' => $t->getTraceAsString()
                    ]
            );

            return 10;
        }
    }

    /**
     * This method is needed so we can add an error handler around it.
     *
     * This is a hack, that is needed because there is not DI in this project yet.
     * @param string[][] $config
     */
    abstract protected function executeWithConfig(
        InputInterface $input,
        OutputInterface $output,
        array $config
    );
}
