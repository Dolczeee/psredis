<?php

namespace Psredis\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheWarmupCommand extends Command
{
    protected static $defaultName = 'psredis:cache:warmup';

    protected function configure()
    {
        $this->setDescription('Rozgrzewa cache Redis');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // implementacja
        return Command::SUCCESS;
    }
}