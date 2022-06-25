<?php
include __DIR__ . '/vendor/autoload.php';
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
$configurator->BBCodes->addFromRepository('FONT');
$configurator->BBCodes->addFromRepository('H1');
$configurator->BBCodes->addFromRepository('H2');
$configurator->BBCodes->addFromRepository('H3');
$configurator->BBCodes->addFromRepository('H4');
$configurator->BBCodes->addFromRepository('H5');
$configurator->BBCodes->addFromRepository('H6');
$configurator->__unset('Emoji');
$configurator->__unset('Emoticons');
$configurator->BBCodes->delete('QUOTE');
$configurator->BBCodes->delete('SPOILER');

// Save it back as your own
$configurator->saveBundle('FlarumBundle', 'new3_flarumbundle.php');
?>