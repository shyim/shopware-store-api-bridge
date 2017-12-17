<?php

namespace App\Components\Packagist;

use Doctrine\DBAL\Connection;

/**
 * Class PackagistUpdater
 * @package App\Components\Packagist
 */
class PackagistUpdater
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PluginVersionUpdater
     */
    private $versionUpdater;

    /**
     * PackagistUpdater constructor.
     * @param Connection $connection
     * @param PluginVersionUpdater $versionUpdater
     */
    public function __construct(Connection $connection, PluginVersionUpdater $versionUpdater)
    {
        $this->connection = $connection;
        $this->versionUpdater = $versionUpdater;
    }

    /**
     * @var array
     */
    private $shopwarePluginTypes = [
        'shopware-plugin',
        'shopware-core-plugin',
        'shopware-frontend-plugin',
        'shopware-backend-plugin',
    ];

    /**
     * @var array
     */
    private $blacklist = [
        'shopec'
    ];

    public function sync()
    {
        $plugins = [];

        foreach ($this->shopwarePluginTypes as $shopwarePluginType) {
            $this->fetchPluginsByComposerType($shopwarePluginType, $plugins);
        }

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $id = $this->connection->fetchColumn('SELECT id FROM plugins WHERE `name` = ?', [$plugin->getInstallName()]);

            $updateArray = [
                'name' => $plugin->getInstallName(),
                'packageName' => $plugin->getName(),
                'latestVersion' => $plugin->getLatestVersion(),
                'type' => $plugin->getType(),
                'description' => $plugin->getDescription(),
                'downloads' => $plugin->getDownloads(),
                'favers' => $plugin->getFavers(),
                'authors' => implode(',', $plugin->getAuthors()),
                'homepage' => $plugin->getHomepage(),
                'license' => implode(' ', $plugin->getLicense()),
                'keywords' => implode(',', $plugin->getKeywords()),
                'url' => $plugin->getUrl(),
                'repository' => $plugin->getRepository(),
            ];

            if (!empty($id)) {
                $plugin->setId($id);
                $this->connection->update('plugins', $updateArray, ['id' => $id]);
            } else {
                $this->connection->insert('plugins', $updateArray);
                $plugin->setId($this->connection->lastInsertId());
            }

            $this->versionUpdater->updateVersions($plugin);
        }
    }

    private function fetchPluginsByComposerType($composerType, &$plugins)
    {
        $body = $this->request('https://packagist.org/packages/list.json?type=' . $composerType);
        // get addional package info
        foreach ($body['packageNames'] as &$composerPackage) {
            if ($this->isBlacklistedPackage($composerPackage)) {
                echo sprintf('Package "%s" is blacklisted. Skipping!' . "\n", $composerPackage);
                continue;
            }
            $composerPackageBody = $this->request('https://packagist.org/packages/' . $composerPackage . '.json');
            $composerPackageBody = array_reverse($composerPackageBody['package']);
            $latestVersion = $this->getLatestVersion($composerPackageBody['versions']);

            if ($latestVersion === null) {
                echo sprintf('Package "%s" has no releases. Skipping!' . "\n", $composerPackage);
                continue;
            }
            // Missing installer-name in composer.json
            if (empty($latestVersion['extra']['installer-name'])) {
                echo sprintf('Package "%s" has no installer name. Skipping!' . "\n", $composerPackage);
                continue;
            }

            $plugin = new Plugin();
            $plugin->setName($composerPackage);
            $plugin->setType($latestVersion['type']);
            $plugin->setTime($latestVersion['time']);
            $plugin->setLatestVersion($latestVersion['version']);
            $plugin->setDescription($latestVersion['description']);
            $plugin->setDownloads($composerPackageBody['downloads']['total']);
            $plugin->setFavers($composerPackageBody['favers']);
            $plugin->setAuthors(array_column($latestVersion['authors'], 'name'));
            $plugin->setHomepage($latestVersion['homepage']);
            $plugin->setInstallName($latestVersion['extra']['installer-name']);
            $plugin->setLicense($latestVersion['license']);
            $plugin->setKeywords($latestVersion['keywords']);
            $plugin->setUrl('https://packagist.org/p/' . $composerPackage);
            $plugin->setRepository($composerPackageBody['repository']);
            $plugin->setVersions($composerPackageBody['versions']);

            $plugins[] = $plugin;
        }
    }

    /**
     * @param array $versions
     * @return array
     */
    private function getLatestVersion($versions)
    {
        $latestVersion = [];
        foreach ($versions as $item) {
            if (strpos($item['version'], 'dev') === false) {
                $latestVersion = $item;
                break;
            }
        }

        // only tag releases are allowed
        if (empty($latestVersion)) {
            $latestVersion = null;
        }
        return $latestVersion;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isBlacklistedPackage($name)
    {
        foreach ($this->blacklist as $blacklist) {
            if (strpos($name, $blacklist) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $url
     * @return array
     */
    private function request($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($handle);
        curl_close($handle);

        return json_decode($response, true);
    }
}
