AjaxSwitch
===========

A MODX plugin that processes ajax and non-ajax requests


Setup
-------------

This plugin is currently not availabel through MODX package management.

If you want to use this plugin you need to do some things first:
* create a new category in the manager called ```AjaxSwitch```
* create a new chunk called ```Default Template``` and add it to the new ```AjaxSwitch``` category. Fill the chunk at least with the ```[[+resource.content]]``` tag.
* create a new TV called ```ajaxswitch.templateWrapper``` give it a nice caption like ```Template Wrapper```, set the input type to "listbox" and add the following @binding as input option values: ```@SELECT name FROM modx_site_htmlsnippets WHERE category = X``` (where ```X``` is the ID of your AjaxSwitch category)
* create a new plugin called ```AjaxSwitch``` and copy the contents of ```core/components/ajaxswitch/elements/plugins/AjaxSwitch.php```