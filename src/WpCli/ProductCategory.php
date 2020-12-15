<?php
namespace AmphiBee\AkeneoConnector\WpCli;
use AmphiBee\AkeneoConnector\Entities\ProductCategory as ProductCategoryEntity;
use WP_CLI;

class ProductCategory {

	protected static $dummy = [
		'name'             => 'Bike',
		'description'      => 'This is the description of the category'
	];

	/**
	 * Returns 'Hello World'
	 *
	 * @since  0.0.1
	 * @author Scott Anderson
	 */
	public function import_category() {
		ProductCategoryEntity::addCategory(self::$dummy);
		WP_CLI::line( 'Hello World!' );
	}

}
