# Facets

## Description

Plugin for Omeka Classic. Once installed and active, allows to insert a Facets block made of option dropdown boxes containing available metadata values extracted from browsing context

Settings allow to choose the hook to be used, which elements use for search refinement, whether to ignore single entries, sorting order for values.

![Facets screenshot](./facets.jpg)

## Credits

Original development is part of the [Armarium](https://www.armarium-hautsdefrance.fr/) Project, managed by the french association [AR2L Hauts de France](http://www.ar2l-hdf.fr/).

Fixed and improved version by [DBinaghi](https://github.com/DBinaghi).

## Installation
Uncompress files and rename plugin folder "Facets".

Then install it like any other Omeka plugin.

## (Eventually) Customize your Theme

Facets will appear by default in the <b>Items browse</b> page, where you have set the `public_items_browse` hook. It is possible to use instead a custom `public_items_facets` hook, that can be positioned wherever needed (`YOUR_THEME/items/browse.php` or `YOUR_THEME/common/header.php`, f.i.) according to the theme used. For <b>Thanks, Roy</b> theme, for example, best way is to change `common/header.php` code using the custom hook:
```php
<div id="search-container" role="search">
    <?php if (get_theme_option('use_advanced_search') === null || get_theme_option('use_advanced_search')): ?>
    <?php echo search_form(array('show_advanced' => true)); ?>
    <?php else: ?>
    <?php echo search_form(); ?>
    <?php endif; ?>
</div>
<?php fire_plugin_hook('public_items_facets', array('view' => $this, 'item' => $item)); ?>
```

## Plugin configuration

The elements used for search refinement can be selected in the configuration page; best practice suggestion is to activate only elements that are displayed in the browse page, and that are offering some kind of data aggregation (a unique id would not offer any really useful refinement).

Similarly, one can choose to also include <b>Item Types</b>, <b>Collections</b> and <b>Tags</b> to the facets block.

Single values can be exluded as not really significant, although they will be listed anyway if less than 10 values are available.

Sorting order can be alphabetical, or first by popularity and then alphabetical. Popularity count can be hidden, if needed.

<b>Date</b> element filter's behaviour is a bit different: dates are ordered decrescently, and only year is displayed; consequently, the matching will be with the beginning of the date (assuming the format is 'yyyy-mm-dd').

## Licence
MIT Licence, please credit AR2L [AR2L Hauts de France](http://www.ar2l-hdf.fr/)
