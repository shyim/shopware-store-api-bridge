<?php

namespace ShyimStoreApi\Components\Packagist;

use Doctrine\DBAL\Connection;

/**
 * Class PluginVersionUpdater
 * @package ShyimStoreApi\Components\Packagist
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
     */
    public function updateVersions(Plugin $plugin)
    {
        global $app;

        $pluginStorageFolder = $app['storage_dir'] . '/' . $plugin->getInstallName();

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
                system('mkdir -p ' . $tmpDirVersionPlugin . ' && cd ' . $tmpDirVersionPlugin . ' && git clone --branch ' . $name . ' '  . $version['source']['url'] . ' ' . $plugin->getInstallName());

                // plugin has custom requires-
                if (count($version['require']) > 1) {
                    system('cd ' . $tmpDirVersionPlugin . '/' . $plugin->getInstallName() . ' && composer install -o --no-dev');
                }

                system('cd ' . $tmpDirVersion . ' && zip -r ' . $zipName . ' * -x *.git* && mv ' . $zipName . ' ' . $pluginStorageFolder);

                $this->connection->insert('plugins_versions', [
                    'pluginID' => $plugin->getId(),
                    'version' => $name
                ]);
            }
        }
    }
}