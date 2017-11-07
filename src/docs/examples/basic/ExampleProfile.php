<?php
/**
 * Copyright © 2016 H&O E-commerce specialisten B.V. (http://www.h-o.nl/)
 * See LICENSE.txt for license details.
 */
namespace Company\ImportModule\Import;

/**
 * Example class how to create your own basic importer
 *
 * @package Company\ImportModule\Import
 */
class ExampleProfile extends \Ho\Import\Model\ImportProfile
{

    /**
     * Steam XML over the web without memory usage, see class for more details
     *
     * @var \Ho\Import\Streamer\HttpXML
     */
    private $httpXml;

    /**
     * Iterate over the source an create items.
     *
     * @var \Ho\Import\RowModifier\SourceIteratorFactory
     */
    private $sourceIteratorFactory;

    /**
     * Factory to map the items
     *
     * @var \Ho\Import\RowModifier\ItemMapperFactory
     */
    private $itemMapperFactory;

    /**
     * This is a normal Magento 2 constructor
     *
     * @param \Magento\Framework\App\ObjectManagerFactory     $objectManagerFactory
     * @param \Symfony\Component\Stopwatch\Stopwatch          $stopwatch
     * @param \Symfony\Component\Console\Output\ConsoleOutput $consoleOutput
     * @param \Ho\Import\Streamer\HttpXML                     $httpXml
     * @param \Ho\Import\RowModifier\SourceIteratorFactory    $sourceIteratorFactory
     * @param \Ho\Import\RowModifier\ItemMapperFactory        $itemMapperFactory
     */
    public function __construct(
        \Magento\Framework\App\ObjectManagerFactory $objectManagerFactory,
        \Symfony\Component\Stopwatch\Stopwatch $stopwatch,
        \Symfony\Component\Console\Output\ConsoleOutput $consoleOutput,
        \Ho\Import\Streamer\HttpXML $httpXml,
        \Ho\Import\RowModifier\SourceIteratorFactory $sourceIteratorFactory,
        \Ho\Import\RowModifier\ItemMapperFactory $itemMapperFactory
    ) {
        parent::__construct($objectManagerFactory, $stopwatch, $consoleOutput);
        $this->httpXml    = $httpXml;
        $this->sourceIteratorFactory = $sourceIteratorFactory;
        $this->itemMapperFactory     = $itemMapperFactory;
    }

    /**
     * Get all the configuration for the importer to that it knows what to do.
     *
     * @return string[]
     */
    public function getConfig()
    {
        return [
            'behavior' => 'replace',
            'entity' => 'catalog_product',
            'validation_strategy' => 'validation-skip-errors',
            'allowed_error_count' => 100,
        ];
    }

    /**
     * Return an array with all the rows to be imported.
     *
     * @return string[]
     */
    public function getItems()
    {
        //Array that will hold all data to be imported.
        $items = [];
        //Move all items into an array to process.
        $iterator       = $this->httpXml->create([
            'url' => 'http://www.pfconcept.com/portal/datafeed/productfeed_nl_v2.xml',
            'uniqueNode' => 'productfeedRow'
        ])->getIterator();
        $sourceIterator = $this->sourceIteratorFactory->create([
            'identifier' => \Ho\Import\Helper\ItemMapperTools::getField('ItemCode'),
            'iterator' => $iterator
        ]);
        $sourceIterator->setItems($items);
        $sourceIterator->process();
        //Create a mapping between the source XML and Magento's required format.
        $itemMapper = $this->itemMapperFactory->create([
            'mapping' => [
                'store_view_code' => \Ho\Import\RowModifier\ItemMapper::FIELD_EMPTY,
                'sku' => \Ho\Import\Helper\ItemMapperTools::getField('ItemCode'),
                'name' => function ($item) {
                    return $item['NameField'] . $item['ColorCode'];
                }
            ]
        ]);
        $itemMapper->setItems($items);
        $itemMapper->process();
        return $items;
    }
}
