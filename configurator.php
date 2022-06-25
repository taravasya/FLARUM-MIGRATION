<?php
include __DIR__ . '/../../vendor/autoload.php';
// Use the Forum bundle's configurator
$configurator = s9e\TextFormatter\Configurator\Bundles\Forum::getConfigurator();

// Customize it to your need
$configurator->MediaEmbed->add('vk');
$configurator->MediaEmbed->add('coub');
$configurator->MediaEmbed->add('flickr');
$configurator->MediaEmbed->add('googledrive');
$configurator->MediaEmbed->add('googlesheets');
$configurator->MediaEmbed->add('imgur');
$configurator->MediaEmbed->add('jsfiddle');
$configurator->MediaEmbed->add('mailru');
$configurator->MediaEmbed->add('rutube');
$configurator->MediaEmbed->add('spotify');
$configurator->MediaEmbed->add('telegram');
$configurator->BBCodes->addFromRepository('HR');

// Save it back as your own
$configurator->saveBundle('FlarumBundle', '/flarumbundle.php');
?>