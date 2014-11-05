MultiCampus
===========

Routing Plugin for Jooma 3+.
----------------------------

(Corresponding module [here](https://github.com/blackbricksoftware/multicampusdisplay).)

This plugin will transparently rewrite matching urls if a cookie is set. It is designed to match a generic url or an organization and rewrite it to the department, campus, or location specific url based on a cookie (if it exists). For example, `https://example.com/donate` will be rewriten to `https://example.com/department/donate`.

## Settings

A list of valid campuses is specific in the plugin settings. They are entered one per line in the format `campus-name-url-value=Campus Name`.

For example, valid settings would look like:

    campus-name-url-value=Campus Name
    marketing=Marketing
    sales=Sales

## Triggering MultiCampus Cookie

The MultiCampus cookie is set by either passing a GET/POST multicampus variable (e.g. `https://example.com/landing-page?multicampus=campus-name-url-value`), or by visiting a url matching one of the URL values (e.g. `https://example.com/campus-name-url-value`).

## Example

A new visitor is directed to your landing page via a search engine. They are taken to the url: `https://example.com/campus-name-url-value`. By visiting this URL, they have the MultiCampus cookie set. 

This visitor then proceeds to look around your website. They eventually decide you are super awesome and want to donate to your. Conveniently, you have a donate link in the main menu. Normally this link points to `https://example.com/donate`, but since the visitor has the multicampus cookie set, and `https://example.com/campus-name-url-value/donate` is a valid url on your website, the donate link points to the latter url. 

## What's it good for?

I wrote the plugin for a church that had three locations. They wanted to share a single website and most of the content. However, they wanted visitors to be able to choose which campus they belonged to and have content specific content delivered to them transparently (i.e. not having large complicated menu/module system). The site looks the same to ever visitor, but depending on which campus they were directed to the correct area of the site. This was especially important for items such as bullions, news, and donations. 

## Caveats

Please someone help me out here if you know the answer. It appears Joomla doesn't pass menu items of the type External URL or Menu Item Alias to the route builders. This means these types of url's cannot be rewritten by this plugin. 

However. I added a work around as of v0.0.2. Create them menu items with the correct alias and a normal type -- I use Featured Articles. Then create a redirect for that url to the external url or alias you want to go to in the Joomla Redirect component. Finally, enable "Redirect Publish URLs" in the plugin.

Basically, the URLs are rewritten as before. When requested, the plugin hijacks the request, checks the Joomla redirects for where you want the request to go, and redirects if necessary. This work nearly the same as the default redirect plugin, but it will redirect urls regardless of whether they exists or not; the default one will only redirect if they don't exist.
