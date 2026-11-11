---
id: create-db-table
name: Create database table
description: Creates a new database table.
enabled: true
---

In each module can be (if not, then create) a file called db.xml. It is xml, contains root element <root> and inside it can be multiple <table> elements.

Each <table> has:
* <name> - name of the table
* <column> - column definition
* <index> - index/key definition

<column> has:
* <name> - name of the column
* <type> - type of the column (int, varchar, text, datetime, etc)
  * attribute "length" - length of the column (for varchar, int, etc)
* <null> - whether the column can be null (YES/NO)
* <default> - default value of the column (if any)
* <autoincrement> - whether the column is auto increment (YES/NO)


<index> has:
* <name> - name of the index (can be empty, but preferably not)
* <type> - type of the index (PRIMARY, UNIQUE, INDEX, FOREIGN)
* <element> - name of the column that is part of the index
* <reference> - for FOREIGN key
  * attribute name="" - name of table that is referenced
  * <element> - name of the column in the referenced table
