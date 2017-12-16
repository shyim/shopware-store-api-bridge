<?php

namespace ShyimStoreApi\Controllers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use ShyimStoreApi\Components\Helper;
use ShyimStoreApi\Components\StoreListingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PluginStore implements ServiceProviderInterface
{
    const CUSTOM_CATEGORIES = [
        1001 => [
            'name' => '5.2 Plugins',
            'type' => 'shopware-plugin'
        ],
        1002 => [
            'name' => 'Backend',
            'type' => 'shopware-backend-plugin'
        ],
        1003 => [
            'name' => 'Frontend',
            'type' => 'shopware-frontend-plugin'
        ],
        1004 => [
            'name' => 'Core',
            'type' => 'shopware-core-plugin'
        ]
    ];

    /**
     * @var \ShyimStoreApi\Application
     */
    private $app;

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $this->app = $pimple;
        $this->app->get('/pluginStore/categories', [$this, 'handleCustomCategories']);
        $this->app->get('/pluginStore/plugins', [$this, 'handleCustomCategoryListing']);
        $this->app->get('/pluginStore/updateablePlugins', [$this, 'handlePluginUpdates']);
        $this->app->get('/pluginFiles/{name}/data', [$this, 'handleCustomPluginDownload']);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function handleCustomCategories(Request $request)
    {
        $response = $this->app->proxy($request);

        $response[] = [
            'categoryId' => 1000,
            'parentId' => null,
            'name' => [
                'de_DE' => 'Composer',
                'en_GB' => 'Composer'
            ]
        ];

        foreach (self::CUSTOM_CATEGORIES as $categoryId => $category) {
            $response[] = [
                'categoryId' => $categoryId,
                'parentId' => 1000,
                'name' => [
                    'de_DE' => $category['name'],
                    'en_GB' => $category['name']
                ]
            ];
        }

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCustomCategoryListing(Request $request)
    {
        $filterJson = $request->query->get('filter');
        $filterJson = json_decode($filterJson, true);
        $filter = [];

        foreach ($filterJson as $item) {
            $filter[$item['property']] = $item['value'];
        }

        // check is it a custom category, otherwise proxy it
        if (isset($filter['categoryId']) && (isset(self::CUSTOM_CATEGORIES[$filter['categoryId']]) || $filter['categoryId'] == 1000)) {
            $response = $this->app['store_listing']->getListing($filter, $request->query->get('offset', 0), $request->query->get('limit', 20));
        } else {
            $response = $this->app->proxy($request);
        }

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @param string $name
     * @return JsonResponse
     */
    public function handleCustomPluginDownload(Request $request, string $name)
    {
        if ($pluginData = $this->app['db']->fetchAssoc('SELECT * FROM plugins WHERE name = ?', [$name])) {
            $baseUrl = ($request->isSecure() ? 'https://' : 'http://') . $request->getHttpHost();
            $path = '/storage/' . $name . '/' . $name . '-' . $pluginData['latestVersion'] . '.zip';
            $sPath = $this->app['root_dir'] . $path;

            return new JsonResponse([
                'location' => $baseUrl . $path,
                'size' => filesize($sPath),
                'sha1' => sha1_file($sPath),
                'binaryVersion' => $pluginData['latestVersion'],
                'encrypted' => false,
            ]);
        } else {
            return new JsonResponse($this->app->proxy($request));
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function handlePluginUpdates(Request $request)
    {
        $response = $this->app->proxy($request);
        $updates = $this->app['store_listing']->getPluginUpdates($request->query->get('plugins'));

        if (!isset($response['data'])) {
            $response['data'] = [];
        }

        // merge store updates and bridge updates, and prefer store updates
        $response['data'] = Helper::mergeArray($response['data'], $updates, 'name');

        if (count($updates) >= 1) {
            // Remove shopware api error code, if the bridge has updates
            unset($response['code']);
            $response['success'] = true;
        }

        return new JsonResponse($response);
    }
}