<?php

$vendorDir = dirname(__DIR__);

return array (
  'yiisoft/yii2-debug' => 
  array (
    'name' => 'yiisoft/yii2-debug',
    'version' => '2.1.27.0',
    'alias' => 
    array (
      '@yii/debug' => $vendorDir . '/yiisoft/yii2-debug/src',
    ),
  ),
  'craftcms/generator' => 
  array (
    'name' => 'craftcms/generator',
    'version' => '2.2.0.0',
    'alias' => 
    array (
      '@craft/generator' => $vendorDir . '/craftcms/generator/src',
    ),
    'bootstrap' => 'craft\\generator\\Extension',
  ),
  'yiisoft/yii2-shell' => 
  array (
    'name' => 'yiisoft/yii2-shell',
    'version' => '2.0.6.0',
    'alias' => 
    array (
      '@yii/shell' => $vendorDir . '/yiisoft/yii2-shell',
    ),
    'bootstrap' => 'yii\\shell\\Bootstrap',
  ),
  'nystudio107/craft-code-editor' => 
  array (
    'name' => 'nystudio107/craft-code-editor',
    'version' => '1.0.23.0',
    'alias' => 
    array (
      '@nystudio107/codeeditor' => $vendorDir . '/nystudio107/craft-code-editor/src',
    ),
    'bootstrap' => 'nystudio107\\codeeditor\\CodeEditor',
  ),
  'yiisoft/yii2-symfonymailer' => 
  array (
    'name' => 'yiisoft/yii2-symfonymailer',
    'version' => '4.0.0.0',
    'alias' => 
    array (
      '@yii/symfonymailer' => $vendorDir . '/yiisoft/yii2-symfonymailer/src',
    ),
  ),
  'yiisoft/yii2-queue' => 
  array (
    'name' => 'yiisoft/yii2-queue',
    'version' => '2.3.7.0',
    'alias' => 
    array (
      '@yii/queue' => $vendorDir . '/yiisoft/yii2-queue/src',
      '@yii/queue/db' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/db',
      '@yii/queue/sqs' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/sqs',
      '@yii/queue/amqp' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/amqp',
      '@yii/queue/file' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/file',
      '@yii/queue/sync' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/sync',
      '@yii/queue/redis' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/redis',
      '@yii/queue/stomp' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/stomp',
      '@yii/queue/gearman' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/gearman',
      '@yii/queue/beanstalk' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/beanstalk',
      '@yii/queue/amqp_interop' => $vendorDir . '/yiisoft/yii2-queue/src/drivers/amqp_interop',
    ),
  ),
  'samdark/yii2-psr-log-target' => 
  array (
    'name' => 'samdark/yii2-psr-log-target',
    'version' => '1.1.4.0',
    'alias' => 
    array (
      '@samdark/log' => $vendorDir . '/samdark/yii2-psr-log-target/src',
      '@samdark/log/tests' => $vendorDir . '/samdark/yii2-psr-log-target/tests',
    ),
  ),
  'creocoder/yii2-nested-sets' => 
  array (
    'name' => 'creocoder/yii2-nested-sets',
    'version' => '0.9.0.0',
    'alias' => 
    array (
      '@creocoder/nestedsets' => $vendorDir . '/creocoder/yii2-nested-sets/src',
    ),
  ),
);
