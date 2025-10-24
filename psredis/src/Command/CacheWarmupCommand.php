<?php

namespace Psredis\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Context;
use Db;

class CacheWarmupCommand extends Command
{
    protected static $defaultName = 'psredis:warmup-cache';

    protected function configure()
    {
        $this->setDescription('Generuje pamięć podręczną dla produktów i kategorii (pre-caching).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Rozpoczęcie generowania pamięci podręcznej...</info>');

        // Pobranie listy ID produktów i kategorii
        $product_ids = Db::getInstance()->executeS('SELECT id_product FROM ' . _DB_PREFIX_ . 'product');
        $category_ids = Db::getInstance()->executeS('SELECT id_category FROM ' . _DB_PREFIX_ . 'category');

        $context = Context::getContext();

        // 1. Warmup dla produktów
        $output->writeln("Buforowanie " . count($product_ids) . " produktów...");
        foreach ($product_ids as $row) {
            // Tworzymy obiekt Product, który automatycznie buforuje się przy ładowaniu
            $product = new Product($row['id_product'], true, $context->language->id, $context->shop->id);
            // Można dodać dodatkowe buforowanie np. strony produktu poprzez wysłanie cURL
            // file_get_contents($context->link->getProductLink($product));
            unset($product);
        }

        // 2. Warmup dla kategorii
        $output->writeln("Buforowanie " . count($category_ids) . " kategorii...");
        foreach ($category_ids as $row) {
            $category = new Category($row['id_category'], $context->language->id, $context->shop->id);
            // file_get_contents($context->link->getCategoryLink($category));
            unset($category);
        }

        $output->writeln('<info>Generowanie pamięci podręcznej zakończone pomyślnie.</info>');

        return Command::SUCCESS;
    }
}