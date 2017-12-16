<?php

namespace ShyimStoreApi\Components;

use Doctrine\DBAL\Connection;
use ShyimStoreApi\Controllers\PluginStore;

/**
 * Class StoreListingService
 * @package ShyimStoreApi\Components
 */
class StoreListingService
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * StoreListingService constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getListing(array $filters, int $offset, int $limit)
    {
        $plugins = $this->getPluginsFromDatabase($filters, $offset, $limit);

        foreach ($plugins as &$plugin) {
            $plugin = $this->convertPluginToStorePlugin($plugin);
        }

        return [
            'data' => $plugins,
            'total' => $this->getTotalCount($filters)
        ];
    }

    /**
     * @param array $plugins
     * @return array
     */
    public function getPluginUpdates(array $plugins)
    {
        $pluginsCustom = $this->getPluginsFromDatabase(['plugins' => array_keys($plugins)], 0, 20000);
        $updateNeededPlugins = [];

        foreach ($pluginsCustom as $plugin) {
            if (isset($plugins[$plugin['name']]) && version_compare($plugin['latestVersion'], $plugins[$plugin['name']], '>')) {
                $updateNeededPlugins[] = $this->convertPluginToStorePlugin($plugin);
            }
        }

        return $updateNeededPlugins;
    }

    /**
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return array
     */
    private function getPluginsFromDatabase(array $filters, int $offset, int $limit)
    {
        return $this->getQueryBuilder($filters, $offset, $limit)->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array $filters
     * @return bool|string
     */
    private function getTotalCount(array $filters)
    {
        $qb = $this->getQueryBuilder($filters, 0, 0);
        $qb->resetQueryPart('select')
            ->setFirstResult(null)
            ->setMaxResults(null);
        $qb->addSelect(['COUNT(*)']);

        return $qb->execute()->fetchColumn();
    }

    /**
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getQueryBuilder(array $filters, int $offset, int $limit)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->from('plugins', 'plugins')
            ->addSelect('plugins.*')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if (isset($filters['categoryId']) && isset(PluginStore::CUSTOM_CATEGORIES[$filters['categoryId']])) {
            $qb->andWhere('plugins.type = :type')
                ->setParameter('type', PluginStore::CUSTOM_CATEGORIES[$filters['categoryId']]['type']);
        }

        if (isset($filters['plugins'])) {
            $qb->andWhere('plugins.name IN(:names)')
                ->setParameter('names', $filters['plugins'], Connection::PARAM_STR_ARRAY);
        }

        return $qb;
    }

    /**
     * @param array $plugin
     * @return array
     */
    private function convertPluginToStorePlugin(array $plugin)
    {
        return [
            'id' => 'custom-' . $plugin['id'],
            'name' => $plugin['name'],
            'code' => $plugin['packageName'],
            'useContactForm' => false,
            'creationDate' => [
                'date' => '2017-12-14 13:58:54.000000',
                'timezone_type' => 3,
                'timezone' => 'Europe\Berlin'
            ],
            'lastChange' => [
                'date' => '2017-12-14 13:58:54.000000',
                'timezone_type' => 3,
                'timezone' => 'Europe\Berlin'
            ],
            'support' => false,
            'supportOnlyCommercial' => false,
            'responsive' => true,
            'iconPath' => null,
            'examplePageUrl' => '',
            'moduleKey' => $plugin['name'],
            'automaticBugfixVersionCompatibility' => true,
            'producer' => [
                'id' => 123,
                'prefix' => $plugin['authors'],
                'name' => $plugin['authors'],
                'website' => '',
                'fixed' => true,
                'iconPath' => null,
                'saleMail' => '',
                'supportMail' => '',
                'ratingMail' => '',
            ],
            'priceModels' => [
                [
                    'id' => rand(1,9999),
                    'bookingKey' => $plugin['name'],
                    'bookingText' => $plugin['name'],
                    'price' => null,
                    'subscription' => false,
                    'discount' => 0,
                    'duration' => null,
                    'discr' => 'priceModelFree'
                ]
            ],
            'pictures' => [],
            'comments' => [],
            'ratingAverage' => 0,
            'label' => $plugin['name'],
            'description' => $plugin['description'],
            'installationManual' => 'currentNotPossible',
            'version' => $plugin['latestVersion'],
            'changelog' => [],
            'addons' => [
                'SW5_integrated'
            ],
            'link' => $plugin['homepage'] ? : $plugin['url'],
            'redirectToStore' => null,
            'lowestPriceValue' => null
        ];
    }
}