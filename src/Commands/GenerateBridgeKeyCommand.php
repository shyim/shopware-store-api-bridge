<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


/**
 * Class GenerateBridgeKeyCommand
 * @package App\Commands
 */
class GenerateBridgeKeyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('generate:key')
            ->setDescription('Generate a bridge key for communication');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);
        $pubKey = openssl_pkey_get_details($res);

        file_put_contents('bridge.public', $pubKey["key"]);
        file_put_contents('bridge.private', $privKey);

        $io = new SymfonyStyle($input, $output);
        $io->success('Generated custom bridge certificates');
    }
}