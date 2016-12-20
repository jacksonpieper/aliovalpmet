
.. include:: ../../Includes.txt

=======================================================
Important: Migrate TCEforms sections in data structures
=======================================================

Description
===========

There is an update script (to be run in the install tool), to migrate all TCEforms sections of data structures and
template objects.

In the past, the Flexform-XML used to look like this:

.. code-block:: xml

   <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
   <T3DataStructure>
      <meta type="array">
         <langDisable>1</langDisable>
      </meta>
      <ROOT type="array">
         <type>array</type>
         <el type="array">
            <field_content type="array">
               <TCEforms>
                  <label>Title</label>
                  <config type="array">
                     <type>group</type>
                     <internal_type>db</internal_type>
                     <allowed>tt_content</allowed>
                     <size>5</size>
                     <maxitems>200</maxitems>
                     <minitems>0</minitems>
                     <multiple>1</multiple>
                     <show_thumbs>1</show_thumbs>
                  </config>
               </TCEforms>
            </field_content>
         </el>
      </ROOT>
   </T3DataStructure>

The TCA definition used to be encapsulated in a `<TCEforms>` section.

From TYPO3 7 on, that section is deprecated and removed on the fly by the form engine but to be compatible with
future versions of TYPO3, this has to be changed permanently. Thus, an update script can be executed in the install
tool to migrate the data structures and template objects.

.. code-block:: xml

   <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
   <T3DataStructure>
      <meta type="array">
         <langDisable>1</langDisable>
      </meta>
      <ROOT type="array">
         <type>array</type>
         <el type="array">
            <field_content type="array">
               <label>Title</label>
               <config type="array">
                  <type>group</type>
                  <internal_type>db</internal_type>
                  <allowed>tt_content</allowed>
                  <size>5</size>
                  <maxitems>200</maxitems>
                  <minitems>0</minitems>
                  <multiple>1</multiple>
                  <show_thumbs>1</show_thumbs>
               </config>
            </field_content>
         </el>
      </ROOT>
   </T3DataStructure>

.. warning::

   Backup the tables **tx_templavoila_datastructure** and **tx_templavoila_tmplobj** in case something goes wrong.


Impact
======

The codebase is completely adjusted and no longer able to handle `<TCEforms>` sections. Unfortunately you cannot avoid
this migration.
