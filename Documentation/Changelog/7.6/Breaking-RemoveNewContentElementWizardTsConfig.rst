====================================================
Breaking: Remove new content element wizard TSconfig
====================================================

Description
===========

TemplaVoilà uses its own content element wizard for creating new content elements due to several reasons.
Therefore - until now - all available elements have been defined via `templavoila.wizards.newContentElement.wizardItems`
in TSconfig. The definition was a copy of `mod.wizards.newContentElement.wizardItems` which then had been adjusted to
also display FCE's. The whole configuration has been removed and TemplaVoilà now relies on the content element definition
of the core.


Impact
======

All adjustments, made to `templavoila.wizards.newContentElement.wizardItems` will not have any affect any more.


Affected Installations
======================

All installations that adjusted `templavoila.wizards.newContentElement.wizardItems`.


Migration
=========

Simply do the adjustments on the core configuration `mod.wizards.newContentElement.wizardItems` instead.
