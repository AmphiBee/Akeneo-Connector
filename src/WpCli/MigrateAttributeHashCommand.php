<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;
use AmphiBee\AkeneoConnector\Service\AkeneoClientBuilder;
use AmphiBee\AkeneoConnector\Adapter\AttributeAdapter;

/**
 * This file is part of the Amphibee package.
 *
 * @package    AmphiBee/AkeneoConnector
 * @author     Amphibee
 * @license    MIT
 * @copyright  (c) Amphibee <hello@amphibee.fr>
 * @since      1.1
 * @access     public
 */
class MigrateAttributeHashCommand extends AbstractCommand
{
    public static string $name = 'migrate_attribute_hash';

    public static string $desc = 'Génère des hash pour les attributs existants';

    public static string $long_desc = 'Cette commande génère des hash pour les attributs existants afin de permettre la détection des changements lors des imports.';

    /**
     * Run the migration command.
     */
    public function run(): void
    {
        # Debug
        $this->print('Démarrage de la migration pour générer des hash pour les attributs existants');

        $provider = AkeneoClientBuilder::create()->getAttributeProvider();
        $adapter = new AttributeAdapter();
        
        $count = 0;
        $errors = 0;
        
        foreach ($provider->getAll() as $ak_attribute) {
            try {
                $wp_attribute = $adapter->fromAttribute($ak_attribute, $this->translator->default);
                $code = $wp_attribute->getCode();
                $taxonomy = 'pa_' . $code;
                
                // Vérifier si la taxonomie existe
                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }
                
                // Récupérer tous les termes de cette taxonomie
                $terms = get_terms([
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                ]);
                
                // Si aucun terme n'est trouvé, passer à l'attribut suivant
                if (empty($terms) || is_wp_error($terms)) {
                    continue;
                }
                
                // Mettre à jour le hash pour chaque terme
                foreach ($terms as $term) {
                    update_term_meta($term->term_id, '_akeneo_hash', $wp_attribute->getHash());
                    $count++;
                }
                
                $this->print(sprintf('Hash généré pour l\'attribut %s', $code), 'line');
            } catch (\Exception $e) {
                $this->error('Erreur lors de la génération du hash pour l\'attribut ' . $ak_attribute->getCode() . ' : ' . $e->getMessage());
                $errors++;
            }
        }
        
        $this->print(sprintf('Hash générés pour %d termes d\'attributs, %d erreurs', $count, $errors), 'success');
        LoggerService::log(Logger::INFO, sprintf('Hash générés pour %d termes d\'attributs, %d erreurs', $count, $errors));
    }
} 