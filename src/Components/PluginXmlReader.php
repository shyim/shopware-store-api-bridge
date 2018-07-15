<?php

namespace App\Components;

/**
 * Class PluginXmlReader
 * @package App\Components
 */
class PluginXmlReader
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * PluginXmlReader constructor.
     * @param string $fileName
     */
    public function __construct(string $fileName)
    {
        $this->data = json_decode(json_encode(simplexml_load_file($fileName, "SimpleXMLElement", LIBXML_NOCDATA)), true);
    }

    /**
     * Returns plugin.xml converted to database schema
     * @return array
     */
    public function toArray()
    {
        $returnData =  [
            'label' => is_string($this->data['label']) ? $this->data['label'] : $this->data['label'][0],
        ];

        if (!empty($this->data['author'])) {
            $returnData['authors'] = $this->data['author'];
        }

        if (!empty($this->data['link'])) {
            $returnData['homepage'] = $this->data['link'];
        }

        if (!empty($this->data['changelog'])) {
            $returnData['changelog'] = json_encode($this->processChangelog($this->data['changelog']));
        }

        return $returnData;
    }

    /**
     * @param array $changelog
     * @return array
     */
    private function processChangelog(array $changelog)
    {
        $formattedChangelog = [];

        if (isset($changelog['@attributes'])) {
            $changelog = [$changelog];
        }

        foreach ($changelog as $item) {
            $formattedChangelog[] = [
                'version' => $item['@attributes']['version'],
                'text' => is_array($item['changes']) ? $item['changes'][0] : $item['changes'],
                'creationDate' => [
                    "date" => "2017-10-09 16:01:10.000000",
                    "timezone_type" => 3,
                    "timezone" => "Europe\/Berlin"
                ]
            ];
        }

        return $formattedChangelog;
    }
}
