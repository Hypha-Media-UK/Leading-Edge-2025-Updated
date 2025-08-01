<?php
/**
 * Yii Application Config for Console Requests
 *
 * Edit this file at your own risk!
 *
 * The array returned by this file will get merged with
 * vendor/craftcms/cms/src/config/app.php and app.php, when
 * Craft's bootstrap script is defining the configuration for the entire
 * application.
 */

return [
    'modules' => [
        'newsorganizer' => [
            'class' => \modules\newsorganizer\Module::class,
        ],
    ],
    'bootstrap' => ['newsorganizer'],
];
