--
-- This file is part of the TemplaVoilà project.
--
-- It is free software; you can redistribute it and/or modify it under
-- the terms of the GNU General Public License, either version 2
-- of the License, or any later version.
--
-- For the full copyright and license information, please read the
-- LICENSE.md file that was distributed with this source code.
--

#
# Table structure for table 'tx_templavoila_tmplobj'
#
CREATE TABLE tx_templavoila_tmplobj (
    uid int(11) DEFAULT '0' NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    t3ver_oid int(11) DEFAULT '0' NOT NULL,
    t3ver_id int(11) DEFAULT '0' NOT NULL,
    t3ver_wsid int(11) DEFAULT '0' NOT NULL,
    t3ver_label varchar(30) DEFAULT '' NOT NULL,
    t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
    t3ver_stage int(11) DEFAULT '0' NOT NULL,
    t3ver_count int(11) DEFAULT '0' NOT NULL,
    t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
    t3ver_move_id int(11) DEFAULT '0' NOT NULL,
    t3_origuid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
    fileref_mtime int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    datastructure varchar(100) DEFAULT '' NOT NULL,
    fileref tinytext,
    templatemapping mediumblob,
    previewicon tinytext,
    description tinytext,
    rendertype varchar(32) DEFAULT '' NOT NULL,
    sys_language_uid int(11) unsigned DEFAULT '0' NOT NULL,
    parent int(11) unsigned DEFAULT '0' NOT NULL,
    rendertype_ref int(11) unsigned DEFAULT '0' NOT NULL,
    localprocessing text,
    fileref_md5 varchar(32) DEFAULT '' NOT NULL,
    backendGridTemplateName varchar(255) DEFAULT NULL,

    PRIMARY KEY (uid),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid),
    KEY parent (pid)
);

#
# Table structure for table 'tx_templavoila_datastructure'
#
CREATE TABLE tx_templavoila_datastructure (
    uid int(11) DEFAULT '0' NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    t3ver_oid int(11) DEFAULT '0' NOT NULL,
    t3ver_id int(11) DEFAULT '0' NOT NULL,
    t3ver_wsid int(11) DEFAULT '0' NOT NULL,
    t3ver_label varchar(30) DEFAULT '' NOT NULL,
    t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
    t3ver_stage int(11) DEFAULT '0' NOT NULL,
    t3ver_count int(11) DEFAULT '0' NOT NULL,
    t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
    t3ver_move_id int(11) DEFAULT '0' NOT NULL,
    t3_origuid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    dataprot mediumtext,
    scope tinyint(4) unsigned DEFAULT '0' NOT NULL,
    previewicon tinytext,
    backendGridTemplateName varchar(255) DEFAULT NULL,

    PRIMARY KEY (uid),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid),
    KEY parent (pid)
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
    tx_templavoila_ds varchar(100) DEFAULT '' NOT NULL,
    tx_templavoila_to int(11) DEFAULT '0' NOT NULL,
    tx_templavoila_flex mediumtext,
    tx_templavoila_pito int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
    tx_templavoila_ds varchar(100) DEFAULT '' NOT NULL,
    tx_templavoila_to int(11) DEFAULT '0' NOT NULL,
    tx_templavoila_next_ds varchar(100) DEFAULT '' NOT NULL,
    tx_templavoila_next_to int(11) DEFAULT '0' NOT NULL,
    tx_templavoila_flex mediumtext,
);

#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
    tx_templavoila_access text,
);
