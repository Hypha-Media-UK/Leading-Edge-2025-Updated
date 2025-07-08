<?php

$vendorDir = dirname(__DIR__);
$rootDir = dirname(dirname(__DIR__));

return array (
  'craftcms/ckeditor' => 
  array (
    'class' => 'craft\\ckeditor\\Plugin',
    'basePath' => $vendorDir . '/craftcms/ckeditor/src',
    'handle' => 'ckeditor',
    'aliases' => 
    array (
      '@craft/ckeditor' => $vendorDir . '/craftcms/ckeditor/src',
    ),
    'name' => 'CKEditor',
    'version' => '4.9.0',
    'description' => 'Edit rich text content in Craft CMS using CKEditor.',
    'developer' => 'Pixel & Tonic',
    'developerUrl' => 'https://pixelandtonic.com/',
    'developerEmail' => 'support@craftcms.com',
    'documentationUrl' => 'https://github.com/craftcms/ckeditor/blob/master/README.md',
  ),
  'jsmrtn/craftagram' => 
  array (
    'class' => 'jsmrtn\\craftagram\\Craftagram',
    'basePath' => $vendorDir . '/jsmrtn/craftagram/src',
    'handle' => 'craftagram',
    'aliases' => 
    array (
      '@jsmrtn/craftagram' => $vendorDir . '/jsmrtn/craftagram/src',
    ),
    'name' => 'Craftagram',
    'version' => '4.1.0',
    'description' => 'Grab Instagram content through the Instagram API',
    'developer' => 'Joshua Martin',
    'documentationUrl' => 'https://github.com/jsmrtn/craftagram/blob/master/README.md',
    'changelogUrl' => 'https://raw.githubusercontent.com/jsmrtn/craftagram/master/CHANGELOG.md',
    'components' => 
    array (
      'craftagramService' => 'jsmrtn\\craftagram\\services\\CraftagramService',
    ),
  ),
);
