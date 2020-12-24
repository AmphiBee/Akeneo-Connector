<?php
/**
 * This file is part of the Amphibee package.
 * (c) Amphibee <hello@amphibee.fr>
 */

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\DataPersister\Product as ProductPersister;
use WP_CLI;

class ProductCommand
{
    protected static $dummyProduct = [
        [
            'type'               => 'variable', // Simple product by default
            'name'               => "The product title",
            'description'        => "The product description…",
            'short_description'  => "The product short description…",
            'sku'                => 'baddd124234',
            'regular_price'      => '5.00', // product price
            // 'sale_price'         => '',
            'reviews_allowed'    => true,
            'images'            => [
                'https://media3.taklope.com/29439-thickbox_default/chargeur-imate-r4.jpg',
                'https://www.recto-versoi.com/sites/default/files/inline-images/test-d-orientation-scolaire_0.jpg',
            ],
            'attributes'         => [
                // Taxonomy and term name values
                'color' => [
                    'term_names' => ['Red', 'Blue'],
                    'is_visible' => true,
                    'for_variation' => true,
                ],
                'size' =>  [
                    'term_names' => ['X Large'],
                    'is_visible' => true,
                    'for_variation' => true,
                ],
            ],
            'variations' => [
                [
                    'image' => 'https://media3.taklope.com/29439-thickbox_default/chargeur-imate-r4.jpg',
                    'attributes' => [
                        'size'  => 'M',
                        'color' => 'Green',
                    ],
                    'sku'           => 'ba1234',
                    'regular_price' => '122.00',
                    'sale_price'    => '',
                    'stock_qty'     => 10,
                ],
                [
                    'image' => 'https://www.recto-versoi.com/sites/default/files/inline-images/test-d-orientation-scolaire_0.jpg',
                    'attributes' => [
                        'size'  => 'X Large',
                        'color' => 'Blue',
                    ],
                    'sku'           => 'ba13426',
                    'regular_price' => '126.00',
                    'sale_price'    => '',
                    'stock_qty'     => 10,
                ]
            ]
        ]
    ];

    public function import(): void
    {




        $launcher_text = sprintf('Import de %s produit(s)', count(self::$dummyProduct));
        WP_CLI::line(WP_CLI::colorize("%B{$launcher_text}%n"));
        $progress = WP_CLI\Utils\make_progress_bar('Import en court', self::$dummyProduct);
        foreach (self::$dummyProduct as $product) {
            ProductPersister::addProduct($product);
            $progress->tick();
        }
        $progress->tick();
        WP_CLI::success('Produits importés');
    }
}
