<?php

namespace App\Components\Packagist;

use App\Components\ShellScript;
use Doctrine\DBAL\Connection;

/**
 * Class PluginVersionUpdater
 * @package App\Components\Packagist
 */
class PluginVersionUpdater
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * PackagistUpdater constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Plugin $plugin
     * @throws \Exception
     */
    public function updateVersions(Plugin $plugin)
    {
        global $kernel;

        $pluginStorageFolder = dirname($kernel->getRootDir()) . '/storage/' . $plugin->getInstallName();

        if (!file_exists($pluginStorageFolder)) {
            mkdir($pluginStorageFolder);
        }

        $tmpDir = sys_get_temp_dir();

        foreach ($plugin->getVersions() as $name => $version) {
            $hasVersion = $this->connection->fetchColumn('SELECT 1 FROM plugins_versions WHERE pluginID = ? AND version = ?', [
                $plugin->getId(),
                $name
            ]);

            if (strpos($name, 'dev') === false && empty($hasVersion)) {
                $tmpDirVersion = $tmpDir . '/' . uniqid();
                $tmpDirVersionPlugin = $tmpDirVersion;

                if ($plugin->getType() !== 'shopware-plugin') {
                    $tmpDirVersionPlugin .= '/' . $plugin->getNamespace();
                }

                $zipName = $plugin->getInstallName() . '-' . $name . '.zip ';

                $script = new ShellScript();

                $script
                    ->addScript('mkdir -p :tmpPluginVersionDir')
                    ->addScript('cd :tmpPluginVersionDir')
                    ->addScript('git clone --branch :tag :gitUrl :pluginName');

                if (isset($version['require']) && count($version['require']) > 1) {
                    $script
                        ->addScript('cd :pluginName')
                        ->addScript('composer install -o --no-dev');
                }

                $script
                    ->addScript('cd :tmpDirVersion')
                    ->addScript('zip -r :zipName * -x *.git*')
                    ->addScript('mv :zipName :storageDir');

                $script
                    ->setParameters([
                        'tmpPluginVersionDir' => $tmpDirVersionPlugin,
                        'tag' => $name,
                        'gitUrl' => $version['source']['url'],
                        'pluginName' => $plugin->getInstallName(),
                        'tmpDirVersion' => $tmpDirVersion,
                        'zipName' => $zipName,
                        'storageDir' => $pluginStorageFolder
                    ]);


                $script->runScript();

                $this->connection->insert('plugins_versions', [
                    'pluginID' => $plugin->getId(),
                    'version' => $name
                ]);
            }
        }
    }
}
