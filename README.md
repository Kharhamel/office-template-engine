Office template engine for php
=========================

This library allow you to easily inject text or images into a template office document

This project is a (still in progress) rewriting of the template engine TBS and its plugin OpenTBS, 
with more modern practices such as a CI environement, an exception-based error gestion, etc.
Check its documentation here: https://www.tinybutstrong.com/opentbs.php?doc


credits go to Skrol29 and the TinyButStrong team. http://www.tinybutstrong.com/

### Work in progress

This project is a heavy wip. The first objective is to increase the code coverage as much as possible, before refractoring the code, and then maybe had features or edit the template syntax.

### Installation

```php
composer require kharhamel/office-template-engine
```

If you use symfony, a bundle is available:

```php
composer require kharhamel/office-template-engine-bundle
```

### How to use

This project try to keep the same api than OpenTBS for the moment. Everything you can see in its doc should be applicable here.

###Symfony integration

TODO: create a bundle package for symfony

### For more information ...
read the TBS manual at http://www.tinybutstrong.com/manual.php

and the OpenTBS plugin documentation at http://www.tinybutstrong.com/plugins/opentbs/tbs_plugin_opentbs.html
