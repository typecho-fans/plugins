CREATE TABLE '%prefix%verion_plugin' (
  'vid'          int(10) unsigned NOT NULL auto_increment,
  'cid'         int(10)        default NULL   ,
  'text'        longtext       default NULL   ,
  'auto'        varchar(32)    default NULL   ,
  'time'        int(32)        default NULL   ,
  'modifierid'  int(10)        default NULL   ,
  'comment'     text           default NULL   ,
  PRIMARY KEY ('vid')
) ENGINE=InnoDB DEFAULT CHARSET=%charset%;
