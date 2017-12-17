<?php

namespace App\Commands;

use App\Components\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class ResetPluginsCommand
 * @package App\Commands
 */
class ResetPluginsCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('reset:plugins')
            ->setDescription('Deletes all plugin data');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        system('rm -rf ' . $this->container->getParameter('kernel.project_dir') . '/storage/*');
        $this->container->get(Helper::class)->resetPlugins();

        $io = new SymfonyStyle($input, $output);
        $io->success('All plugin data has been deleted');
    }
}