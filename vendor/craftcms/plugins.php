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
  'verbb/formie' => 
  array (
    'class' => 'verbb\\formie\\Formie',
    'basePath' => $vendorDir . '/verbb/formie/src',
    'handle' => 'formie',
    'aliases' => 
    array (
      '@verbb/formie' => $vendorDir . '/verbb/formie/src',
    ),
    'name' => 'Formie',
    'version' => '3.1.1',
    'description' => 'The most user-friendly forms plugin for Craft.',
    'developer' => 'Verbb',
    'developerUrl' => 'https://verbb.io',
    'developerEmail' => 'support@verbb.io',
    'documentationUrl' => 'https://github.com/verbb/formie',
    'changelogUrl' => 'https://raw.githubusercontent.com/verbb/formie/craft-5/CHANGELOG.md',
  ),
  'craftcms/feed-me' => 
  array (
    'class' => 'craft\\feedme\\Plugin',
    'basePath' => $vendorDir . '/craftcms/feed-me/src',
    'handle' => 'feed-me',
    'aliases' => 
    array (
      '@craft/feedme' => $vendorDir . '/craftcms/feed-me/src',
    ),
    'name' => 'Feed Me',
    'version' => '6.8.0',
    'description' => 'Import content from XML, RSS, CSV or JSON feeds into entries, categories, Craft Commerce products, and more.',
    'developer' => 'Pixel & Tonic',
    'developerUrl' => 'https://pixelandtonic.com/',
    'developerEmail' => 'support@craftcms.com',
    'documentationUrl' => 'https://docs.craftcms.com/feed-me/v4/',
  ),
);
