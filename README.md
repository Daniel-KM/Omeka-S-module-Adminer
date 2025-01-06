Adminer Sql (module for Omeka S)
================================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Adminer Sql] is a module for [Omeka S] that allows to view and manage a MySQL
database. It uses [Adminer], formerly phpMinAdmin, a one file full-featured
database management tool written in PHP. In fact, the fork [AdminerEvo] is used,
because [Adminer] was not updated to last version of php.

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

If the module was installed from the source, rename the name of the folder of
the module to `Adminer`, go to the root of the module, and run:

```sh
composer install --no-dev
```

To change the default theme, you can set it in `composer.json` and run `composer update`
or copy it inside directory asset/vendor/adminer/.


Usage
-----

Just fill the confirm and create a read only user.

If the omeka database user has the rights to create a user and to specify
privileges, the read only user will be automatically created.

Else, you can run this query in the database, modifying the user name ("readonly"
here), the host (generally "localhost" or "127.0.0.1"), the password, and the
database name ("omeka" here).

```sql
CREATE USER 'readonly'@'localhost' IDENTIFIED BY 'a very long password';
GRANT SELECT ON `omeka`.* TO 'readonly'@'localhost';
GRANT SHOW VIEW ON `omeka`.* TO 'readonly'@'localhost';
FLUSH PRIVILEGES;
```


TODO
----

* [x] Remove the login page (login directly).
* [x] Use composer package vrana/adminer (to minify and remove from vendor for security).
* [x] Allow to use any adminer.css theme simply by putting it in a directory.
* [ ] Remove access to column `password` of users and api credentials.
* [x] Give the choice to use the simplified version "adminer editor" (finalize theme).
- [ ] Fix the warning when changing theme on the first page. The issue is related to the load of the minified js.
      It is related to the auth process (with or without login form, that may reset token. See adminer/include/auth.inc.php).


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

* Module

This module is published under the [CeCILL v2.1] license, compatible with
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

* Library Adminer/AdminerEvo

The library Adminer is released under [Apache] or [GPL v2].
Adminer themes are released the same.


Copyright
---------

* Copyright Daniel Berthereau, 2019-2025 (see [Daniel-KM] on GitLab)

Adminer:
* Copyright 2007, Jakub Vrana
* Copyright 2016, Aleksey M. (theme)


[Adminer Sql]: https://gitlab.com/Daniel-KM/Omeka-S-module-Adminer
[Adminer]: https://www.adminer.org
[AdminerEvo]: https://adminerevo.org
[Omeka S]: https://omeka.org/s
[warning]: #Warning
[`Adminer.zip`]: https://gitlab.com/Daniel-KM/Omeka-S-module-Adminer/-/releases
[installing a module]: https://omeka.org/s/docs/user-manual/modules/#installing-modules
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-Adminer/-/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Apache]: https://www.apache.org/licenses/LICENSE-2.0.html
[GPL v2]: https://www.gnu.org/licenses/gpl-2.0.txt
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
