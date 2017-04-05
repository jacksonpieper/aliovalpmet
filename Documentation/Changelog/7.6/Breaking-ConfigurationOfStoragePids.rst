
.. include:: ../../Includes.txt

==========================================
Breaking: Configuration Of Storage Folders
==========================================

Description
===========

Since TYPO3 7.6 one cannot select a `Storage Folder` for a page any more. That database field has been removed and the core
does not provide any alternative.

Impact
======

All installations that used `Storage Folders` to provide different page templates and FCE's in different parts of the page
tree need to run the upgrade wizard in the install tool to migrate to a `PageTS` configuration.


Affected Installations
======================

All installations are affected technically but only those that used multiple `Storage Folders` for a separation of page
templates and FCE's are affected visually.

Migration
=========

Run the migration script in the install tool.

.. tip::

   The migration needs to access the field `storage_pid` in the table `pages`. Make sure it has not yet been renamed to
   `zzz_deleted_storage_pid`. Once the migration is done, you can remove/rename that field again.

**Troubleshooting**:

When there is no automatic migration possible, you simply need to put the following `PageTS` on all necessary pages.

.. code-block:: ts

   mod.tx_templavoila.storagePid = x
