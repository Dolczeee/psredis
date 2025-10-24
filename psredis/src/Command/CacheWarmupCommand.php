<?php

namespace Psredis\Command;

use Psredis\Service\RedisConnectionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheWarmupCommand extends Command
{
    protected static $defaultName = 'psredis:cache:warmup';
    protected static $defaultDescription = 'Warm up Redis cache for PrestaShop';

    private ?RedisConnectionService $redis;

    public function __construct(?RedisConnectionService $redis = null)
    {
        parent::__construct();
        $this->redis = $redis;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Psredis cache warmup started');

        if ($this->redis && method_exists($this->redis, 'warmup')) {
            try {
                $this->redis->warmup();
                $output->writeln('Warmup zakończony pomyślnie.');
            } catch (\Throwable $e) {
                $output->writeln('Błąd podczas warmup: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $output->writeln('RedisConnectionService nie jest dostępny lub metoda warmup() nie istnieje.');
        }

        return Command::SUCCESS;
    }
}