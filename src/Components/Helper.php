<?php

namespace App\Components;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Helper
 * @package App\Components
 */
class Helper
{
    const SHOPWARE_API = 'https://api.shopware.com';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Helper constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $name
     * @return array|bool
     */
    public function getPluginData(string $name)
    {
        return $this->connection->fetchAssoc('SELECT * FROM plugins WHERE name = ?', [$name]);
    }

    /**
     * @param array $array1
     * @param array $array2
     * @param string $column
     * @return array
     */
    public static function mergeArray(array $array1, array $array2, string $column)
    {
        foreach ($array2 as $itemOfArray2) {
            $found = false;
            foreach ($array1 as $itemOfArray1) {
                if ($itemOfArray1[$column] === $itemOfArray2[$column]) {
                    $found = true;
                }
            }

            if (!$found) {
                $array1[] = $itemOfArray2;
            }
        }

        return $array1;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public static function proxy(Request $request)
    {
        global $kernel;

        $curl = curl_init(self::SHOPWARE_API . $request->getRequestUri());
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $header = [];
        foreach ($request->headers->all() as $key => $value) {
            $header[] = strtoupper($key) . ': ' . $value[0];
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if ($request->getMethod() !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->getContent());
        }
        $response = curl_exec($curl);
        curl_close($curl);

        file_put_contents($kernel->getLogDir() . '/' . md5($request->getRequestUri()) . '.json', json_encode(json_decode($response, true), JSON_PRETTY_PRINT));

        return json_decode($response, true);
    }

    /**
     * @param string $name
     * @return null|string
     */
    public static function getNamespace(string $name)
    {
        switch ($name) {
            case 'shopware-plugin':
                return null;
            case 'shopware-backend-plugin':
                return 'Backend';
            case 'shopware-core-plugin':
                return 'Core';
            case 'shopware-frontend-plugin':
                return 'Frontend';
        }
    }
}
