Adminer Sql (module for Omeka S)
================================

[Adminer Sql] is a module for [Omeka S] that allows to view and manage a MySQL
database. It uses [Adminer], formerly phpMinAdmin, a one file full-featured
database management tool written in PHP.

It is highly recommended to create a read-only user to use it, because it’s very
easy to break a database, even for people who know the Omeka code perfectly.
Anyway, see the [warning] below: always save your files and your database
automatically and regularly, and before risky and non-risky commands.


Installation
------------

The module uses an external library ([Adminer]) to access database, so use the
release zip to install the module, or use and init the source.

See general end user documentation for [installing a module].

* From the zip

Download the last release [`Adminer.zip`] from the list of releases (the master
does not contain the dependency), and uncompress it in the `modules` directory.

* From the source and for development

Currently, adminer is provided directly with the module with a specific theme.

Development branch allows to install adminer via composer, but without the theme
currently.

Dependencies cannot be updated via composer: the version number of Adminer
should be updated manually in `composer.json`.

* Notes

The settings of the module (config access of the database) is not saved in the
database with other settings, but in the file `config/database-adminer.ini`. It
is highly recommended to check access rights to this file (no `other` access).
It is possible to set this file as read only too.


TODO
----

* Allow to use any adminer.css theme simply by putting it in a directory.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitHub.


License
-------

* Module

This module is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.

* Library Adminer

The library Adminer is released under [Apache] or [GPL v2].
Adminer theme [`mvt`] by Aleksey M.


Copyright
---------

* Copyright Daniel Berthereau, 2019 (see [Daniel-KM] on GitHub)

Adminer:
* Copyright 2007, Jakub Vrana
* Copyright 2016, Aleksey M. (theme)


[Adminer Sql]: https://github.com/Daniel-KM/Omeka-S-module-Adminer
[Adminer]: https://www.adminer.org
[Omeka S]: https://omeka.org/s
[warning]: #Warning
[`Adminer.zip`]: https://github.com/Daniel-KM/Omeka-S-module-Adminer/releases
[installing a module]: http://dev.omeka.org/docs/s/user-manual/modules/#installing-modules
[module issues]: https://github.com/Daniel-KM/Omeka-S-module-Adminer/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Apache]: https://www.apache.org/licenses/LICENSE-2.0.html
[GPL v2]: https://www.gnu.org/licenses/gpl-2.0.txt
[`mvt`]: https://github.com/alekseymvt/Adminer.theme
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
