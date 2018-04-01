
To use "yii migrate-module-users/up" and other migrations in separate migrations history
add to console application config (for example /console/config/main.php in advanced application):
  'controllerMap' => [
      'migrate-module-users' => [
          'class' => 'yii\console\controllers\MigrateController',
          'migrationTable' => '{{%migration_module_users}}',
      ],
  ],
