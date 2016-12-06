
.. include:: ../../Includes.txt

================================================
Deprecation: Deprecate renderPreviewContent hook
================================================

Description
===========

The hook `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']` is deprecated and replaced by `\Schnitzler\Templavoila\Container\ElementRendererContainer`


Impact
======

Registering a preview renderer for content elements in the backend is still possible via the hook, but will trigger a deprecation log entry. From 8.0.0 on, this hook will be removed completely.
From then on, the `ElementRendererContainer` needs to be used to register preview renderers.


Affected Installations
======================

Only installations that override the default preview renderer of TemplaVoilà.


Migration
=========

Replace these code blocks

.. code-block:: php

   <?php
   // ext_localconf.php

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['text'] = 'Vendor\Extension\My\TextRenderer';
   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['bullets'] = 'Vendor\Extension\My\BulletsRenderer';

with

.. code-block:: php

   <?php
   // ext_localconf.php

   $elementRendererContainer = \Schnitzler\Templavoila\Container\ElementRendererContainer::getInstance();
   $elementRendererContainer->add('text', new \Vendor\Extension\My\TextRenderer());
   $elementRendererContainer->add('bullets', new \Vendor\Extension\My\BulletsRenderer());
