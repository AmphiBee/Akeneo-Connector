<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Entity\Akeneo\Attribute;
use AmphiBee\AkeneoConnector\Adapter\AttributeAdapter;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\DataPersister\AttributeDataPersister;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee & tgeorgel
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class AttributeCommand extends AbstractCommand
{
    public static string $name = 'attributes';

    public static string $desc = 'Supports Akaneo Attributes import';

    public static string $long_desc = '';


    /**
     * Run the import command.
     */
    public function import(): void
    {
        # Debug
        $this->print('Starting attributes import');

        $attributeDataProvider = AkeneoClientBuilder::create()->getAttributeProvider();
        $attributeAdapter      = new AttributeAdapter();
        $attrPersister         = new AttributeDataPersister();

        do_action('ak/attributes/before_import', $attributeDataProvider->getAll());

        $attribute_data = (array) apply_filters('ak/attributes/import_data', iterator_to_array($attributeDataProvider->getAll()));

        # Statistiques d'import
        $stats = [
            'total' => count($attribute_data),
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        /**
         * @var Attribute $AknAttr
         */
        foreach ($attribute_data as $AknAttr) {
            $this->print(sprintf('Running AttrCode: %s', $AknAttr->getCode()));

            try {
                $wc_attribute = $attributeAdapter->fromAttribute($AknAttr, $this->translator->default);

                // Vérifier si l'attribut existe déjà et si son hash a changé
                $taxonomy = sprintf('pa_%s', strtolower($wc_attribute->getCode()));
                $attribute_id = wc_attribute_taxonomy_id_by_name($taxonomy);

                if ($attribute_id) {
                    $stored_hash = get_term_meta($attribute_id, '_akeneo_hash', true);
                    $current_hash = $wc_attribute->getHash();

                    if ($stored_hash && $stored_hash === $current_hash) {
                        $this->print(sprintf('Skipping attribute %s - No changes detected', $wc_attribute->getCode()), 'line');
                        $stats['skipped']++;
                        continue;
                    }
                }

                // Utiliser la valeur de retour pour déterminer si l'attribut a été importé ou ignoré
                $imported = $attrPersister->importBooleanAttributeOption($wc_attribute);

                if ($imported) {
                    $stats['imported']++;
                } else {
                    $stats['skipped']++;
                }
            } catch (\Exception $e) {
                $this->error('An error occurred while importing the attribute : ' . $e->getMessage());
                $stats['errors']++;
            }
        }

        do_action('ak/attributes/after_import', $attributeDataProvider->getAll());

        $this->print(sprintf(
            'Import completed: %d attributes processed, %d imported, %d skipped, %d errors',
            $stats['total'],
            $stats['imported'],
            $stats['skipped'],
            $stats['errors']
        ), 'success');
    }
}
