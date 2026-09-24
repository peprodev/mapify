=== PeproDev Branches Map (Mapify) ===
Contributors: peprodev,amirhosseinhpv
Donate link: https://pepro.dev/donate
Tags: map, branches, store locator, openstreetmap, elementor
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 3.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your branches on OpenStreetMap, Google Maps, Mapbox, Persian maps or an offline map of Iran, with a searchable branches list.

== Description ==

Add branches (address, phone, social links, location) and show them on a map with a searchable list, popups and clusters. Use the Elementor widget, the WPBakery Page Builder element or the shortcode.

**Map engines**

* 20 free tile styles with no key: OpenStreetMap (standard, grayscale, dark, vintage, Humanitarian, France, Germany), CyclOSM, OpenTopoMap and Esri (light, dark, street, topographic, National Geographic, satellite, satellite with labels, terrain, shaded relief, physical, ocean)
* Google Maps with five built-in styles or a cloud Map ID (Advanced Markers)
* Mapbox (built-in and Mapbox Studio styles)
* Persian maps: Map.ir, Neshan, Parsimap and Mapup (Mapup needs no key)
* Any XYZ tile server
* Offline map of Iran (SVG, 31 provinces, no external requests). Branches are placed by latitude/longitude, provinces with branches are highlighted and clicking a province filters the list
* Zoom buttons, mouse wheel, double click and pinch zoom on every map, including the offline Iran map

**Page builders**

* Elementor widget “Branches Map” with full content settings and a Style tab (map, pins, tooltip, clusters, popup, list, search box and regions — colors, typography, borders, shadows, responsive sizes)
* WPBakery Page Builder element with the same settings in tabs, a thumbnail style picker and Design Options
* `[pepro-mapify]` / `[mapify]` shortcode, with a visual Shortcode Builder and live preview (Branches → Shortcode Builder)
* Thumbnail gallery pickers for tile styles and Google styles in Elementor, WPBakery and the Shortcode Builder

**Branches list and popups**

* Three list layouts: chips, cards and a detailed list, with a search box and grouping by category
* Picking a branch in the list scrolls the page to the map and opens its popup (both can be turned off)
* Pin color and pin image per branch
* Popup template with tags; popup image from the featured image, the branch pin image, or none
* Get directions button: Android lists the map apps installed on the phone, other devices open Google Maps

**Other**

* Up to 15 branches
* Branches and categories in Tools → Export and in the REST API (`wp/v2/mapify`, `wp/v2/mapify_category`, `mapify/v1/branches`, `mapify/v1/categories`)
* Settings page built with WordPress components (Branches → Map Settings)
* Branch editor with an OpenStreetMap location picker and address search
* Single branch card with directions links (Google Maps, Waze, Neshan, Balad)
* Ready for WPML (wpml-config.xml: branches, categories, branch details, widget and shortcode texts) and Polylang
* Persian (fa_IR) translation, RTL support

Maps show a small “© PeproDev Mapify” credit next to the map provider attribution.

== Shortcode ==

`[pepro-mapify maptype="iran" branchplacement="end" list_layout="cards"]`

Every option of the builders is an attribute (see the Shortcode Builder for the full list). Enclosed content is used as the popup template:

`[pepro-mapify maptype="osm"]<h3>{title}</h3><p>{address}</p>[/pepro-mapify]`

Popup tags: {id} {title} {image} {pin_image} {popup_image} {url} {latitude} {longitude} {address} {phone} {site} {email} {twitter} {facebook} {instagram} {telegram} {linkedin} {additional} {categories} {directions}. Use {tag|fallback} for a default value.

Shortcodes made with version 1.x keep working.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install it from the Plugins screen, then activate it.
2. Add branches under Branches → Add New Branch.
3. Optional: add API keys under Branches → Map Settings (Google Maps, Mapbox, Map.ir, Neshan, Parsimap). OpenStreetMap, Esri, Mapup and the offline Iran map work without keys.
4. Add the map with Elementor, WPBakery or the shortcode.

== Frequently Asked Questions ==

= How many branches can I add? =

Up to 15 branches.

= What happens if a provider key is missing? =

The map falls back to OpenStreetMap and editors see a notice above the map.

= Can I translate branches with WPML or Polylang? =

Yes. Branches and branch categories are translatable post types and taxonomies, the address and additional text are translated per language, and the texts of the Elementor widget and the shortcode are picked up by WPML. Interface texts use the “mapify” text domain.

== Screenshots ==

1. Cards layout with the map and a branch popup
2. Offline Iran map with chips layout
3. Popup with the Get directions button on a phone
4. Shortcode Builder with the Google Maps style picker
5. Map Settings
6. Branch editor with the location picker
7. Elementor: Branches Map widget
8. Elementor: Google Maps style gallery
9. WPBakery: Branches Map element
10. WPBakery: Google Maps style picker

== Changelog ==

= 3.0.0 =

- Rewritten for PHP 7.4 – 8.5, WordPress 7.1, Elementor 4.3 and WPBakery 8/9
- New map engines: OpenStreetMap (20 free tile styles), Mapbox, Map.ir, Neshan, Parsimap, Mapup, custom XYZ tiles and an offline map of Iran
- New Elementor widget with full Style controls, and a WPBakery element rebuilt with param groups and Design Options
- Thumbnail gallery pickers for tile styles and Google styles
- Settings page and Shortcode Builder with live preview, built with WordPress components
- New branch editor with an OpenStreetMap location picker and address search
- Chips, cards and detailed list layouts; picking a branch scrolls to the map and opens its popup
- Popup image setting, {image}, {pin_image}, {popup_image} and {directions} popup tags
- Get directions button (installed map apps on Android, Google Maps elsewhere)
- Zoom settings on every map, including the offline Iran map
- REST API routes for branches and categories; branch categories in Tools → Export
- WPML (wpml-config.xml) and Polylang support
- Popup templates are sanitized; no more eval()
- Removed the Google tile-URL hack ("Development mode") and the custom Google logo option
- Up to 15 branches

= 1.3.6 =

-  Plugin Name fixes

= 1.3.5 =

-  Javascript Issue with latest Visual Composer fixed

= 1.3.4.1 =

-   Google Chrome > 84 Compatible

= 1.3.4 =

-   Category Name fix

= 1.3.3 =

-   Fixed Branches Data not saving
-   Fixed Compatibility with WordPress 5.5

= 1.3.2 =

-   Fixed MITM-Attack Issue

= 1.3.1 =

-   Dependency free marker maker added
-   Removed custom css styles from Visual Composer Widget
-   Branches Metabox Class renamed to `PeproBranchesCPT_metabox`
-   Directory Index Blocked for resources

1.3.0 =

-   Initial release for GitHub and WordPress


== About Us ==

PEPRO DEV is a premium supplier of quality WordPress plugins, services and support.
Join us at [https://pepro.dev/](https://pepro.dev/) and also don't forget to check our [free offerings](http://profiles.wordpress.org/peprodev/), we hope you enjoy them!


== Upgrade Notice ==

= 3.0.0 =

- Major rewrite with new map engines, page builder integrations and settings. Shortcodes made with version 1.x keep working. Please back up your site before updating.

= 1.3.6 =

-  Plugin Name fixes

= 1.3.5 =

-  Javascript Issue with latest Visual Composer fixed

= 1.3.4.1 =

-   Google Chrome > 84 Compatible

= 1.3.4 =

-   Category Name fix

= 1.3.3 =

-   Fixed Branches Data not saving
-   Fixed Compatibility with WordPress 5.5

= 1.3.2 =

-   Fixed MITM Attack Issue

= 1.3.1 =

-   Dependency free marker maker added
-   Removed custom css styles from Visual Composer Widget
-   Branches Metabox Class renamed to `PeproBranchesCPT_metabox`
-   Directory Index Blocked for resources

1.3.0 =

-   Initial release for GitHub and WordPress
