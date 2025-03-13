<?php

namespace AmphiBee\AkeneoConnector\WpCli;

use AmphiBee\AkeneoConnector\Models\ProductModel;
use Monolog\Logger;
use AmphiBee\AkeneoConnector\Service\LoggerService;

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
class MigrateHashColumnCommand extends AbstractCommand
{
    public static string $name = 'migrate_hash_column';

    public static string $desc = 'Ajoute une colonne hash à la table des modèles de produits';

    public static string $long_desc = 'Cette commande ajoute une colonne hash à la table akconnector_products_models pour permettre la détection des changements dans les modèles.';

    /**
     * Run the migration command.
     */
    public function run(): void
    {
        # Debug
        $this->print('Démarrage de la migration pour ajouter la colonne hash');

        global $wpdb;
        $table_name = $wpdb->prefix . 'akconnector_products_models';

        // Vérifier si la colonne existe déjà
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'hash'");

        if (!empty($column_exists)) {
            $this->print('La colonne hash existe déjà dans la table ' . $table_name, 'warning');
            return;
        }

        // Ajouter la colonne hash
        $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN hash VARCHAR(32) NULL AFTER variant_code");

        if ($result === false) {
            $this->error('Erreur lors de l\'ajout de la colonne hash : ' . $wpdb->last_error);
            LoggerService::log(Logger::ERROR, 'Erreur lors de l\'ajout de la colonne hash : ' . $wpdb->last_error);
            return;
        }

        $this->print('La colonne hash a été ajoutée avec succès à la table ' . $table_name, 'success');

        // Mettre à jour le modèle ProductModel pour inclure la colonne hash
        // (Cela n'est pas nécessaire si vous avez déjà mis à jour le modèle)

        // Générer des hash pour les modèles existants
        $this->print('Génération des hash pour les modèles existants...', 'line');

        $count = 0;
        $models = ProductModel::all();

        foreach ($models as $model) {
            // Nous ne pouvons pas générer de hash précis sans les données originales d'Akeneo
            // Donc nous allons simplement générer un hash basé sur les données disponibles
            $hash = md5($model->model_code . $model->family_code . $model->variant_code);
            $model->hash = $hash;
            $model->save();
            $count++;
        }

        $this->print(sprintf('Hash générés pour %d modèles existants', $count), 'success');
        LoggerService::log(Logger::INFO, sprintf('Hash générés pour %d modèles existants', $count));
    }
}
