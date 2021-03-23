<?php
namespace AmphiBee\AkeneoConnector\Admin;

use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;

class Settings {
    public static $akeneoSettings;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'akeneo_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'akeneo_settings_page_init' ) );
	}

	public static function getDamUrl() {
	    // @todo dynamiser
	    return 'https://dam.meo.fr';
    }

	public function akeneo_settings_add_plugin_page() {
		add_options_page(
			'Configuration Akeneo Connector', // page_title
			'Configuration Akeneo Connector', // menu_title
			'manage_options', // capability
			'configuration-akeneo-connector', // menu_slug
			array( $this, 'akeneo_settings_create_admin_page' ) // function
		);
	}

	public static function getAkeneoSettings($key = false) {
	    if (!self::$akeneoSettings) {
            self::$akeneoSettings = get_option( 'akeneo_settings' );
        }
	    return $key && isset(self::$akeneoSettings[$key]) ? self::$akeneoSettings[$key] : self::$akeneoSettings;
    }

	public function akeneo_settings_create_admin_page() {
		$this->akeneo_settings_options = self::getAkeneoSettings(); ?>

		<div class="wrap">
			<h2>Configuration Akeneo Connector</h2>
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'akeneo_settings_option_group' );
					do_settings_sections( 'configuration-akeneo-connector-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function akeneo_settings_page_init() {
		register_setting(
			'akeneo_settings_option_group', // option_group
			'akeneo_settings', // option_name
			array( $this, 'akeneo_settings_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'akeneo_settings_setting_section', // id
			'Settings', // title
			array( $this, 'akeneo_settings_section_info' ), // callback
			'configuration-akeneo-connector-admin' // page
		);

        if ( false === ( $settingsFlds = get_transient( '_akeneo_attr_settings' ) ) ) {
            $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();
            $settingsFlds = [];
            foreach ($attributeDataProvider->getAll() as $AknAttr) {
                $settingsFlds[$AknAttr->getCode()] = [
                    'labels' => $AknAttr->getLabels(),
                    'code' => $AknAttr->getCode()
                ];
            }
            set_transient( '_akeneo_attr_settings', $settingsFlds, 12 * HOUR_IN_SECONDS );
        }

        foreach ($settingsFlds as $settingsFld) {

            $labels = $settingsFld['labels'];
            $language = 'fr_FR';
            $attrName = $labels[$language];

            add_settings_field(
                "map_{$settingsFld['code']}}", // id
                $attrName, // title
                function() use ($settingsFld) {
                    $this->getSelectField($settingsFld);
                }, // callback
                'configuration-akeneo-connector-admin', // page
                'akeneo_settings_setting_section' // section
            );
        }
	}

	public function getSelectField($attrName) {
	    $options = [
	        '' => __( '--- Select an option ---', 'akeneo-connector' ),
            'post_title' => __( 'Product title', 'akeneo-connector' ),
            'post_excerpt' => __( 'Short description', 'akeneo-connector' ),
            'post_content' => __( 'Main description', 'akeneo-connector' ),
            'post_thumbnail' => __( 'Product thumbnail', 'akeneo-connector' ),
            'external_thumbnail' => __( 'External Product thumbnail (DAM)', 'akeneo-connector' ),
            'featured' => __( 'Featured', 'akeneo-connector' ),
            'gallery' => __( 'Gallery', 'akeneo-connector' ),
            'external_gallery' => __( 'External Gallery (DAM)', 'akeneo-connector' ),
            'ugs' => __( 'Product identifier (UGS)', 'akeneo-connector' ),
            'weight' => __( 'Weight', 'akeneo-connector' ),
            'regular_price' => __( 'Price', 'akeneo-connector' ),
            'sale_price' => __( 'Sale Price', 'akeneo-connector' ),
            'sale_from' => __( 'Sale Price from', 'akeneo-connector' ),
            'sale_to' => __( 'Sale Price to', 'akeneo-connector' ),
            'tax_status' => __( 'Tax status', 'akeneo-connector' ),
            'tax_class' => __( 'Tax class', 'akeneo-connector' ),
            'text_attribute' => __( 'Text Attribute', 'akeneo-connector' ),
	        'global_attribute' => __( 'Global Attribute', 'akeneo-connector' ),
            'post_meta' => __( 'Post meta', 'akeneo-connector' ),
            'external_media' => __( 'External Media (DAM)', 'akeneo-connector' ),
        ];
        ?>

        <select name="akeneo_settings[attribute_mapping][<?php echo $attrName['code']; ?>]" id="map_<?php echo $attrName['code']; ?>">
            <?php foreach ($options as $value=>$option_name): ?>
                <?php $selected = self::getMappingValue($attrName['code']) === $value ? 'selected' : '' ; ?>
                <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $option_name; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public static function getMappingValue($key='') {
        $akeneoSettings = self::getAkeneoSettings();

	    if (
	        !isset($akeneoSettings['attribute_mapping'])
            || !isset($akeneoSettings['attribute_mapping'][$key])
        ) {
	        return '';
        }
	    return $akeneoSettings['attribute_mapping'][$key];
    }

    public static function getTypeValue($key='') {
        $akeneoSettings = self::getAkeneoSettings();

        if (
            !isset($akeneoSettings['attribute_type'])
            || !isset($akeneoSettings['attribute_type'][$key])
        ) {
            return '';
        }
        return $akeneoSettings['attribute_type'][$key];
    }

	public function akeneo_settings_sanitize($input) {
	    foreach ($input['attribute_mapping'] as $key=>$value) {
            $input['attribute_mapping'][$key] = sanitize_text_field($value);
        }
        foreach ($input['attribute_type'] as $key=>$value) {
            $input['attribute_type'][$key] = sanitize_text_field($value);
        }
		return $input;
	}

	public function akeneo_settings_section_info() {

	}
}


/*
 * Retrieve this value with:
 * $akeneoSettings = get_option( 'akeneo_settings' ); // Array of All Options
 * $description_du_produit_0 = $akeneoSettings['description_du_produit_0']; // Description du produit
 * $ean_1 = $akeneoSettings['ean_1']; // EAN
 * $ean_2 = $akeneoSettings['ean_2']; // EAN
 * $meta_description_3 = $akeneoSettings['meta_description_3']; // Meta description
 * $meta_description_4 = $akeneoSettings['meta_description_4']; // Meta description
 * $meta_keywords_5 = $akeneoSettings['meta_keywords_5']; // Meta keywords
 */
