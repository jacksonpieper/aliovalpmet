
.. include:: ../../Includes.txt

===================================================
Breaking: ConfigurationOfContainerTitlesInFlexforms
===================================================

Description
===========

Since TYPO3 7.6 the parsing of flexforms is very strict. In the past it was possible to set a container element title
by putting it into the templavoila container definition.

Example:

.. code-block:: xml

   <container_element type="array">
       <type>array</type>
       <tx_templavoila type="array">
           <title>Title</title>
           ...
       </tx_templavoila>
       <TCEforms type="array">
           ...
       </TCEforms>
       <el type="array">
           ...
       </el>
   </container_element>

Impact
======

The old configuration does not lead to an exception but the title will be ignored by the TYPO3 core.

Affected Installations
======================

All

Migration
=========

Simply put the title next to it's parent node.

Example:

.. code-block:: xml

   <container_element type="array">
       <type>array</type>
       <title>Title</title>
       <tx_templavoila type="array">
           ...
       </tx_templavoila>
       <TCEforms type="array">
           ...
       </TCEforms>
       <el type="array">
           ...
       </el>
   </container_element>
