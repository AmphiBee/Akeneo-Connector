<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Adapter\ProductAdapter;
use AmphiBee\AkeneoConnector\DataPersister\ProductDataPersister;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Category as AkeneoCategory;
use AmphiBee\AkeneoConnector\Entity\Akeneo\Product;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use Monolog\Logger;
use WP_CLI;

class ProductCommand
{
// @todo remove it
//    protected static $dummyProduct = [
//        [
//            'type'               => 'variable', // Simple product by default
//            'name'               => "The product title",
//            'description'        => "The product description…",
//            'short_description'  => "The product short description…",
//            'sku'                => 'baddd124234',
//            'regular_price'      => '5.00', // product price
//            // 'sale_price'         => '',
//            'reviews_allowed'    => true,
//            'images'            => [
//                'https://media3.taklope.com/29439-thickbox_default/chargeur-imate-r4.jpg',
//                'https://www.recto-versoi.com/sites/default/files/inline-images/test-d-orientation-scolaire_0.jpg',
//            ],
//            'attributes'         => [
//                // Taxonomy and term name values
//                'color' => [
//                    'term_names' => ['Red', 'Blue'],
//                    'is_visible' => true,
//                    'for_variation' => true,
//                ],
//                'size' =>  [
//                    'term_names' => ['X Large'],
//                    'is_visible' => true,
//                    'for_variation' => true,
//                ],
//            ],
//            'variations' => [
//                [
//                    'image' => 'https://media3.taklope.com/29439-thickbox_default/chargeur-imate-r4.jpg',
//                    'attributes' => [
//                        'size'  => 'M',
//                        'color' => 'Green',
//                    ],
//                    'sku'           => 'ba1234',
//                    'regular_price' => '122.00',
//                    'sale_price'    => '',
//                    'stock_qty'     => 10,
//                ],
//                [
//                    'image' => 'https://www.recto-versoi.com/sites/default/files/inline-images/test-d-orientation-scolaire_0.jpg',
//                    'attributes' => [
//                        'size'  => 'X Large',
//                        'color' => 'Blue',
//                    ],
//                    'sku'           => 'ba13426',
//                    'regular_price' => '126.00',
//                    'sale_price'    => '',
//                    'stock_qty'     => 10,
//                ]
//            ]
//        ]
//    ];

    public function import(): void
    {
        WP_CLI::warning('Import Started');
        LoggerService::log(Logger::DEBUG, 'Starting product import');

        $productProvider = AkeneoClientBuilder::create()->getProductProvider();
        $productAdapter = new ProductAdapter();
        $productPersister = new ProductDataPersister();

        do_action('ak/product/before_import', $productProvider->getAll());

        /** @var Product $aknProduct */
        foreach ($productProvider->getAll() as $aknProduct) {
            LoggerService::log(Logger::DEBUG, sprintf('Running ProductCode: %s', $aknProduct->getIdentifier()));
            WP_CLI::line($aknProduct->getIdentifier());

            $wooCommerceProduct = $productAdapter->getWordpressProduct($aknProduct);
            $productPersister->createOrUpdateProduct($wooCommerceProduct);
        }

        do_action('ak/product/after_import', $productProvider->getAll());

        LoggerService::log(Logger::DEBUG, 'Ending product import');
        WP_CLI::success('Import OK');
    }
}
