<?php

namespace ShyimStoreApi\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SyncPackagistCommand
 * @package ShyimStoreApi\Commands
 */
class SyncPackagistCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('packagist:sync')
            ->setDescription('Synchronize packagist packages to local database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $app;

        $app['packagist_sync']->sync();
    }
}