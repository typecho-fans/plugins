CREATE TABLE '%prefix%verion_plugin' (
  'vid'          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  'cid'         INTEGER        default NULL   ,
  'text'        text           default NULL   ,
  'auto'        varchar(32)    default NULL   ,
  'time'        int(32)        default NULL   ,
  'modifierid'  int(10)        default NULL   ,
  'comment'     text           default NULL
);
