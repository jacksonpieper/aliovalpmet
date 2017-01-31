
.. include:: ../../Includes.txt

=============================================
Breaking: Configuration Of Types In Flexforms
=============================================

Description
===========

Since TYPO3 7.6 the parsing of flexforms is very strict. In the past TemplàVoila put the `type` configuration directly
into the element nodes.

Example:

.. code-block:: xml

   <element type="array">
       <type>attr</type>
       <tx_templavoila type="array">
           ...
       </tx_templavoila>
       <TCEforms type="array">
           ...
       </TCEforms>
   </element>

Impact
======

An exception is thrown when having a non-compatible configuration.

The only allowed `type` definition directly inside the element node is `array`. But only if in combination with the
section node `<section>1</section>`, that makes that element a section container.

Example:

.. code-block:: xml

   <element type="array">
       <type>array</type>
       <section>1</section>
       <el type="array">
           ...
       </el>
   </element>

Affected Installations
======================

All

Migration
=========

Simply put the `type` node into the `tx_templavoila` node

Example:

.. code-block:: xml

   <element type="array">
       <tx_templavoila type="array">
           <type>attr</type>
           ...
       </tx_templavoila>
       <TCEforms type="array">
           ...
       </TCEforms>
   </element>
