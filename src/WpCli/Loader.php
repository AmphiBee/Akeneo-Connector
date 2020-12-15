<?php
namespace AmphiBee\AkeneoConnector\WpCli;

class Loader {

	public function __construct()
	{
		add_action( 'cli_init', [$this, 'register_commands']);
	}

	/**
	 * Register command
	 *
	 * @access public
	 * @since  1.0.0
	 * @author AmphiBee
	 */
	public function register_commands() {
		// \WP_CLI::add_command( 'akeneo', ProductAttribute::class );
		\WP_CLI::add_command( 'akeneo', Product::class );
		// \WP_CLI::add_command( 'akeneo', ProductCategory::class );
	}
}



