# About

This module integrates the mmenu library with Drupal's menu system with the aim
of having an off-canvas mobile menu and a horizontal menu at wider widths. It
integrates with your theme's breakpoints to allow you to trigger the display of
the horizontal menu at your desired breakpoint. The mobile off-canvas menu is
displayed through a toggle icon or with the optional integration of a swipe
gesture.

# Install

The module should be installed using composer:

```
composer require drupal/responsive_menu:^4.0
```

# Libraries install

The module requires the installation of at least the mmenujs library. There are
two methods to install the external libraries:

## NPM method:

If you have npm installed you can change directory into the `responsive_menu`
module directory and run `npm install`. This will create a `libraries`
directory which you _must_ move to your Drupal root.

## Manual method:

The only library required by this module is the [mmenu](https://mmenujs.com/)
plugin. You need to [download an 8.x.x
version](https://github.com/FrDH/mmenu-js/releases) of the library and place it
in `/libraries` in your docroot (create the directory if you need to). Rename
your newly placed download to mmenu, so the resulting path is
`/libraries/mmenu`. This module will look in `/libraries/mmenu/dist` for the
javascript files so ensure you have the correct file structure.

The other optional library which adds functionality (if desired) is the
[superfish](https://github.com/joeldbirch/superfish) plugin. This library uses
jQuery so your theme will also need to use jQuery. Place this in `/libraries`
and rename it to superfish, so that you have a structure like
`/libraries/superfish/dist/js` starting at your docroot/webroot (at the same
level as index.php)`.

# Upgrade from 8.x-2.x versions

Due to class name changes upstream in the mmenu library you may need to adjust
your custom css. The main css change implemented in the provided
`responsive_menu.css` is the targeting of the `html.mm-opening` class which no
longer exists. This has changed to `.mm-wrapper_opening`. So for example:

```css
html.mm-opening .responsive-menu-toggle-icon span.icon {
```

is now:

```css
.mm-wrapper_opening .responsive-menu-toggle-icon span.icon {
```

# Configuration

As an administrator visit `/admin/config/user-interface/responsive-menu`

You can adjust the various options. Some of the options will require the
libraries to be present before allowing configuration.

There is an option to _not use_ the breakpoint which in turn will not add the
breakpoint css file to the page. This will allow you to use the off-canvas menu
at any screen size. Alternatively you might want to use your own menu at desktop
screen widths and control the visibility of both with your own css.

# Block placement

The module provides two blocks, one for the horizontal menu, labeled in the
block UI as "Horizontal menu". The other is labeled as "Responsive menu mobile
icon" and is the 'burger' style menu icon and text which allows the user to
toggle the mobile menu open and closed.

The placement of the horizontal menu block is _optional_, the off-canvas menu
will work regardless of the existence of the horizontal menu block. This is
useful if you want to use another block for the horizontal menu, eg. Bootstrap.

The mobile icon block is necessary for the mobile menu to work.

# Theming and theme compatibility

This module should be compatible with most themes. One basic requirement is that
the theme includes a wrapping 'page' div. This is so that mmenu knows which
elements belong inside the wrapper when the off canvas menu is opened. Bartik is
an example of a theme with a wrapping div. Bootstrap (v3) is an example of a
theme which doesn't have the wrapping div. There is a setting to
automatically add a wrapping div should your theme need it.

It should also be noted that the default css that comes with the module provides
some _very basic_ styling and should be copied and pasted into your theme's css
so that you can modify it to fit your theme's style. Once you've copied and
pasted the css into your stylesheet you can disable the inclusion of the
module's css on the settings page.

There is a default 'selected' class of `menu-item--active-trail` which
corresponds to the active menu trail class of any theme using `classy` as the
base theme. If you want to change this take a look at the preprocess code below
as an example. Note that this class will be replaced with `mm-listitem_selected`
by the mmenu library in the off-canvas menu so don't rely on the original Drupal
class for styling.

The mmenu library does not require jQuery and this module does not have a
requirement for jQuery either, so your theme could potentially use both the
horizontal and the off-canvas (mmenu based) menu without any jQuery. The
optional superfish library (and the included hoverintent) do require jQuery so
if you want to use those your theme will need to include jQuery (jQuery will be
automatically added by the module as a dependency in this situation).

# Customising the mmenu config and settings (advanced)

It is possible to completely customise the config provided to the mmenu library,
beyond what the module's admin form provides. This is done by preprocessing the
page and providing a `custom` element on the `drupalSettings` for the
`responsive_menu`.

In the following example you would place this in your custom theme's `.theme`
file and change `MYTHEME` to the name of your theme. This example adds a navbar
title to the off-canvas menu with the string 'This is a test'. It also changes
the selected class name to `my-custom-menu--active-trail`:

```php
/**
 * Implements hook_preprocess_page().
 */
function MYTHEME_preprocess_page(&$variables) {
  $variables['#attached']['drupalSettings']['responsive_menu']['custom'] = [
    'options' => [
      'navbar' => [
        'add' => TRUE,
        'title' => 'This is a test',
      ]
    ],
    'config' => [
      'classNames' => [
        'selected' => 'my-custom-menu--active-trail',
      ],
    ]
  ];
}
```

# Licenses

The licenses for the libraries used by this module are:

mmenu: Creative Commons Attribution-NonCommercial
superfish: MIT

The mmenu plugin used to have an MIT license but has changed to the CC
NonCommercial license. So you'll need to pay the developer a fee to use it in a
commercial web site.
