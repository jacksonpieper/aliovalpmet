======================================================
Breaking: Replace beLayout with backend grid templates
======================================================

Description
===========

Whenever a page or an FCE (used as grid) holds content elements, one had the possibility to define the arrangement of
the columns by defining a so called **beLayout**. In the context of TemplaVoilà, a **beLayout** is a snippet of HTML, it
is not a backend layout, provided by the core.

Example:
________

.. code-block:: xml

   <T3DataStructure>
      <meta type="array">
         <beLayout><![CDATA[
            <table width="100%">
               <tr>
                  <td valign="top" width="100%" colspan="2">###field_header###</td>
               </tr>
               <tr>
                  <td valign="top" width="70%">###field_content###</td>
                  <td valign="top" width="30%">###field_rightsidebar###</td>
               </tr>
            </table>
         ]]></beLayout>
      </meta>
      ...
   </T3DataStructure>

Alternatively one could store the **beLayout** definition in an html file which was then attached to the template or
data structure records.

From TemplaVoilà 7.6 on, this will not work any longer. The definition inside the data structure XML will be ignored and
both the (templavoila-)template and the data structure records do no longer have the fields for attaching a file.


Impact
======

No worries, the rendering of pages and FCE's will still work, but all columns will be put in one row by default.
Technically, this is not a problem, it's only a visual thing.


Affected Installations
======================

All installations that used **beLayout** definitions.


Migration
=========

Unfortunately there is no automatic migration. In the following, the necessary steps for a manual migration will be
explained.

First thing to know, is that every content element in the backend is rendered with Fluid now. That also applies to any
kind of grid, whose default template is to be found at `Resources/Private/Templates/Backend/Grid/Default.html`.

That default template iterates over all given columns and creates an html column (<td>) for each.

**Example:**

.. code-block:: html

   ...
   <tr>
      <f:for each="{columns}" as="column">
         <td valign="top" class="t3-grid-cell t3-page-column">
             <div class="t3-page-column-header">
                 <div class="t3-page-column-header-label">{column.title}</div>
             </div>
             <div class="t3-page-ce-wrapper sortable">
                 {column.content->f:format.raw()}
             </div>
         </td>
      </f:for>
   </tr>
   ...

Overwrite templates globally
____________________________

As all template paths are configurable, this template can easily be overridden. To do so, simply adjust the
following typoscript constants:

`module.tx_templavoila.view.templateRootPath`

`module.tx_templavoila.view.partialRootPath`

`module.tx_templavoila.view.layoutRootPath`

**Example:**

.. code-block:: ts

   module.tx_templavoila.view.templateRootPath = EXT:site/Resources/Private/Templates/

The default name of the grid template is `Backend/Grid/Default`, so you can create a new template file at
`EXT:site/Resources/Private/Templates/Backend/Grid/Default.html` and easily adjust the rendering of all grids.


Overwrite templates for a single template or data structure record
__________________________________________________________________

Overwriting the template globally is the easiest approach if you have just one type of grid that applies to all of your
pages and FCE's. For sure, this is a rare case and it is possible to define one template per grid. When editing
a (templavoila-)template or data structure record you are able to define a backend grid template name.

**Example:**

Let's say you have a grid with a header and two columns, like in the beLayout example from the beginning.

1. Set the backend grid template name to `Backend/Grid/TwoColummsWithHeader`.
2. Create the template file at `EXT:site/Resources/Private/Templates/Backend/Grid/TwoColummsWithHeader.html`
3. Adjust the markup to fit your grid layout.

.. code-block:: html

   ...
   <colgroup>
      <col style="width:70%">
      <col style="width:30%">
   </colgroup>
   <tr>
      <td valign="top" class="t3-grid-cell t3-page-column" colspan="2">
          <div class="t3-page-column-header">
              <div class="t3-page-column-header-label">{columns.field_header.title}</div>
          </div>
          <div class="t3-page-ce-wrapper sortable">
              {columns.field_header.content->f:format.raw()}
          </div>
      </td>
   </tr>
   <tr>
      <td valign="top" class="t3-grid-cell t3-page-column">
          <div class="t3-page-column-header">
              <div class="t3-page-column-header-label">{columns.field_content.title}</div>
          </div>
          <div class="t3-page-ce-wrapper sortable">
              {columns.field_content.content->f:format.raw()}
          </div>
      </td>
      <td valign="top" class="t3-grid-cell t3-page-column">
          <div class="t3-page-column-header">
              <div class="t3-page-column-header-label">{columns.field_rightsidebar.title}</div>
          </div>
          <div class="t3-page-ce-wrapper sortable">
              {columns.field_rightsidebar.content->f:format.raw()}
          </div>
      </td>
   </tr>
   ...

.. tip::

   You are free to use whatever markup you like but in order to have a sortable column (drag and drop), you should stick to the default nesting and css classes. This also makes writing style attributes superfluous.
