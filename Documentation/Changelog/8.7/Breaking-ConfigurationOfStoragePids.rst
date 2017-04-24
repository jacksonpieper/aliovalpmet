
.. include:: ../../Includes.txt

==========================================
Breaking: Configuration Of Storage Folders
==========================================

Description
===========

TemplaVoilà 7.6 introduced a new PageTS setting, that could be used to define a `Storage Folder`. The configuration key was
`mod.tx_templavoila.storagePid`. During the development of TemplaVoilà 8.7 I realized there has been a similar configuration key
for years: `tx_templavoila.storagePid`. Therefore the key has changed once more. I am sorry for that but in the end this solution
is more consistent.

Impact
======

All installations that used `mod.tx_templavoila.storagePid` need to remove the `mod.` prefix. Also, from now on it is possible
to define the same setting as UserTS, which wasn't possible before.

Affected Installations
======================

All installations that defined storage folders with PageTS.

Migration
=========

Simply remove the `mod.` prefix.

.. code-block:: ts

   mod.tx_templavoila.storagePid = x

.. code-block:: ts

   tx_templavoila.storagePid = x
