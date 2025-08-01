<?php

namespace modules\newsorganizer;

use Craft;
use yii\base\Module as BaseModule;

/**
 * News Organizer module
 *
 * @author Craft CMS
 * @since 1.0.0
 */
class Module extends BaseModule
{
    /**
     * Initializes the module.
     */
    public function init(): void
    {
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__ . '/..');

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\newsorganizer\\console\\controllers';
        }

        parent::init();

        // Custom initialization code goes here...
    }
}
