<?php
namespace AmphiBee\AkeneoConnector\WpCli;
use AmphiBee\AkeneoConnector\Entities\ProductAttribute as ProductAttributeEntity;
use WP_CLI;

class Product {

	protected static $dummyProduct = [
			// Taxonomy and term name values
			'pa_color' => [
				'term_names' => ['Red', 'Blue'],
				'is_visible' => true,
				'for_variation' => true,
			],
			'pa_size' =>  [
				'term_names' => ['X Large'],
				'is_visible' => true,
				'for_variation' => true,
			],

	];

	/**
	 * Returns 'Hello World'
	 *
	 * @since  0.0.1
	 * @author Scott Anderson
	 */
	public function import_attributes() {
		$launcher_text = sprintf('Import de %s attributs(s)', count(self::$dummyAttributes) );
		WP_CLI::line(WP_CLI::colorize( "%B{$launcher_text}%n" ));
		$progress = WP_CLI\Utils\make_progress_bar('Import en court', self::$dummyAttributes );
		foreach (self::$dummyProduct as $product) {
			ProductEntity::addProduct($product);
			$progress->tick();
		}
		$progress->tick();
		WP_CLI::success( 'Attributs importés' );
	}

}
