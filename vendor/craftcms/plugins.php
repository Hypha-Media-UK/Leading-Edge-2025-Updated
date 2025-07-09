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
  'verbb/field-manager' => 
  array (
    'class' => 'verbb\\fieldmanager\\FieldManager',
    'basePath' => $vendorDir . '/verbb/field-manager/src',
    'handle' => 'field-manager',
    'aliases' => 
    array (
      '@verbb/fieldmanager' => $vendorDir . '/verbb/field-manager/src',
    ),
    'name' => 'Field Manager',
    'version' => '4.0.3',
    'description' => 'Manage your fields and field groups with ease.',
    'developer' => 'Verbb',
    'developerUrl' => 'https://verbb.io',
    'developerEmail' => 'support@verbb.io',
    'documentationUrl' => 'https://github.com/verbb/field-manager',
    'changelogUrl' => 'https://raw.githubusercontent.com/verbb/field-manager/craft-5/CHANGELOG.md',
  ),
);
