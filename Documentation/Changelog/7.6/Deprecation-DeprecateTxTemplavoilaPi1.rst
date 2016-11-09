=========================================
Deprecation: Deprecate tx_templavoila_pi1
=========================================

Description
===========

The class tx_templavoila_pi1 is deprecated and replaced by \Schnitzler\Templavoila\Controller\FrontendController.


Impact
======

`userFunc = tx_templavoila_pi1->main_page`, `userFunc = tx_templavoila_pi1->main_record` and `userFunc = tx_templavoila_pi1->tvSectionIndex` will write a deprecation log entry. In 7.6.0 everything still works as usual, but from 8.0.0 on, thes class, along with its methods, will disappear.


Affected Installations
======================

All.


Migration
=========

Replace `tx_templavoila_pi1` with `\Schnitzler\Templavoila\Controller\FrontendController`.

Example:
.. code-block:: typoscript

	page = PAGE
	page.10 = USER
	page.10.userFunc = Schnitzler\Templavoila\Controller\FrontendController->renderPage

Further replace the following method calls:

* `main_page` -> `renderPage`
* `main_record` -> `renderRecord`
* `tvSectionIndex` -> `renderSectionIndex`
