<?php

namespace App\Controller;

use App\Components\Helper;
use App\Components\StoreListingService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PluginStoreController
 * @package App\Controller
 */
class PluginStoreController extends Controller
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
     * @Route(path="/pluginStore/categories")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function handleCustomCategories(Request $request)
    {
        $response = Helper::proxy($request);

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
     * @Route(path="/pluginStore/plugins")
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
            $response = $this->get(StoreListingService::class)->getListing($filter, $request->query->get('offset', 0), $request->query->get('limit', 20));
        } else {
            $response = Helper::proxy($request);
        }

        if (isset($filter['search'])) {
            $brigeData = $this->get(StoreListingService::class)->getListing($filter, $request->query->get('offset', 0), $request->query->get('limit', 20));
            $response['data'] = Helper::mergeArray($brigeData['data'], $response['data'], 'name');
        }

        return new JsonResponse($response);
    }

    /**
     * @Route(path="/pluginFiles/{name}/data")
     * @param Request $request
     * @param string $name
     * @return JsonResponse
     */
    public function handleCustomPluginDownload(Request $request, string $name)
    {
        if ($pluginData = $this->get(Helper::class)->getPluginData($name)) {
            $baseUrl = ($request->isSecure() ? 'https://' : 'http://') . $request->getHttpHost();
            $path = '/storage/' . $name . '/' . $name . '-' . $pluginData['latestVersion'] . '.zip';
            $sPath = dirname($this->get('kernel')->getRootDir()) . $path;

            return new JsonResponse([
                'location' => $baseUrl . $path,
                'size' => filesize($sPath),
                'sha1' => sha1_file($sPath),
                'binaryVersion' => $pluginData['latestVersion'],
                'encrypted' => false,
            ]);
        } else {
            return new JsonResponse(Helper::proxy($request));
        }
    }

    /**
     * @Route(path="/pluginStore/updateablePlugins")
     * @param Request $request
     * @return JsonResponse
     */
    public function handlePluginUpdates(Request $request)
    {
        $response = Helper::proxy($request);
        $updates = $this->get(StoreListingService::class)->getPluginUpdates($request->query->get('plugins'));

        if (!isset($response['data'])) {
            $response['data'] = [];
        }

        // merge store updates and bridge updates, and prefer bridge updates
        $response['data'] = Helper::mergeArray($updates, $response['data'], 'name');

        if (count($updates) >= 1) {
            // Remove shopware api error code, if the bridge has updates
            unset($response['code']);
            $response['success'] = true;
        }

        return new JsonResponse($response);
    }
}
