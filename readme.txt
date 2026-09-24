=== PeproDev Branches Map (Mapify) ===
Contributors: peprodev,amirhosseinhpv
Donate link: https://pepro.dev/donate
Tags: map, branches, store locator, openstreetmap, elementor
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 2.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your branches on Google Maps, OpenStreetMap, Mapbox, Persian maps (Map.ir, Neshan, Parsimap, Mapup), custom SVG/image maps or an offline map of Iran.

== Description ==

Add branches (address, phone, social links, location) and show them on a map with a searchable list, popups and clusters.

**Map engines**

* OpenStreetMap and other free tiles (Esri light/dark/street/topo/satellite, OpenTopoMap) — no key needed
* Google Maps with 70+ built-in styles, custom JSON styles or a cloud Map ID (Advanced Markers)
* Mapbox (built-in and Mapbox Studio styles)
* Persian maps: Map.ir, Neshan, Parsimap and Mapup (Mapup needs no key)
* Any XYZ tile server
* Offline map of Iran (SVG, 31 provinces, no external requests). Branches are placed by latitude/longitude, provinces with branches are highlighted and clicking a province filters the list
* Your own SVG map — shapes with an id become hoverable regions
* Any image as a map (floor plans, campus maps…). Pins use percent (relative, responsive) or pixel (absolute) positions; with optional geo bounds, branches are placed on the image by latitude/longitude

**Page builders**

* Elementor widget “Branches Map” with full content settings and a Style tab (map, pins, tooltip, clusters, popup, list, search box and SVG regions — colors, typography, borders, shadows, responsive sizes)
* WPBakery Page Builder element with the same settings in tabs, a Google style picker and Design Options
* `[pepro-mapify]` / `[mapify]` shortcode, with a visual Shortcode Builder and live preview (Branches → Shortcode Builder)

**Branches list and filters**

* Eight list layouts: chips, cards, detailed list, image grid, compact numbered list, table, carousel and dropdown
* Category chips under the search box and on the map; one or several categories at a time
* Pin color and pin image per category (Branches → Categories)

**Popup**

* Popup image from the featured image, the branch pin image, or none
* Get directions button: Android lists the installed map apps, other devices get the app list from Map Settings → Navigation, or the phone's default map app opens directly
* Navigation apps can be turned on or off, renamed, reordered, given new icons, or added

**Other**

* Zoom buttons, mouse wheel, double click and pinch zoom on every map, including the offline Iran, SVG and image maps
* Import / export of settings, categories and branches as JSON (Branches → Map Settings → Import / Export); single map export / import in the Shortcode Builder
* Branches and categories in Tools → Export and in the REST API (`wp/v2/mapify`, `wp/v2/mapify_category`, `mapify/v1/branches`, `mapify/v1/categories`)
* Map attribution can be replaced or hidden for development use
* Settings page built with WordPress components (Branches → Map Settings)
* Branch editor with an OpenStreetMap location picker and address search
* Single branch card with directions links (Google Maps, Waze, Neshan, Balad)
* Persian (fa_IR) translation, RTL support

== Shortcode ==

`[pepro-mapify maptype="iran" branchplacement="end" list_layout="cards"]`

Every option of the builders is an attribute (see the Shortcode Builder for the full list). Enclosed content is used as the popup template:

`[pepro-mapify maptype="osm"]<h3>{title}</h3><p>{address}</p>[/pepro-mapify]`

Popup tags: {id} {title} {image} {pin_image} {popup_image} {url} {latitude} {longitude} {address} {phone} {site} {email} {twitter} {facebook} {instagram} {telegram} {linkedin} {additional} {categories} {directions}. Use {tag|fallback} for a default value.

Shortcodes made with version 1.x keep working.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install it from the Plugins screen, then activate it.
2. Add branches under Branches → Add New Branch.
3. Optional: add API keys under Branches → Map Settings (Google Maps, Mapbox, Map.ir, Neshan, Parsimap). OpenStreetMap, Mapup and the offline Iran map work without keys.
4. Add the map with Elementor, WPBakery or the shortcode.

== Frequently Asked Questions ==

= What happens if a provider key is missing? =

The map falls back to OpenStreetMap and editors see a notice above the map.

= How do I place pins on my own image? =

Choose “Image as map”, select the image and add custom pins with X/Y in percent (stays aligned when the image scales) or pixels of the original image. To place branches automatically, enter the image's geographic bounds.

== Screenshots ==

1. Map Settings page
2. Shortcode Builder with live preview
3. Elementor widget — content settings
4. Elementor widget — style settings
5. WPBakery Page Builder element settings
6. Offline Iran map with branches list

== Changelog ==

= 2.3.0 =

- Map Settings → Navigation: turn navigation apps on or off, rename them, change their icons, reorder them, choose the devices they show on, and add your own apps with a link template ({lat} {lng} {title} {address})
- App icons for Google Maps, Apple Maps, Waze, Neshan, Balad and other phone apps in the directions list
- New Get directions action: open the phone's default map app directly, without the list
- “Map without labels” Google style from Snazzy Maps
- Snazzy Maps style section: paste a style's JavaScript style array (JSON or a full `var styles = [...]` snippet), with a link to Snazzy Maps

= 2.2.0 =

- 12 more free tile styles (no key): OpenStreetMap grayscale, dark and vintage, OpenStreetMap France and Germany, CyclOSM, National Geographic, satellite with labels, terrain, shaded relief, physical and ocean
- Thumbnail gallery picker for tile styles and Google styles in Elementor, with search; the same thumbnails in WPBakery and the Shortcode Builder
- Popup tags: {image} is always the featured image, new {pin_image} and {popup_image} (follows the “Popup image” setting)

= 2.1.0 =

- Category filter chips under the search box and on the map, with single or multiple selection
- Pin color and pin image per category; branch pin settings still win
- Six new list layouts: detailed list, image grid, compact numbered list, table, carousel and dropdown
- Popup image setting (featured image, branch pin image, featured then pin, none) and placeholder toggle
- Get directions button in popups with the installed map apps on Android and an app list elsewhere; {directions} popup tag
- Zoom settings: buttons and their position, mouse wheel, double click, pinch, minimum and maximum zoom; zoom and pan on the offline Iran, SVG and image maps
- Import / export of settings, categories and branches (JSON), and export / import of a single map in the Shortcode Builder
- REST API: branch meta, category pin meta and a mapify_location field in wp/v2; new mapify/v1 routes (branches, categories, export, import)
- Tools → Export includes branch categories with their pin settings when only Branches are exported
- Map attribution can be replaced or hidden (development use only)
- Elementor and WPBakery category renamed to “PeproDev Elements”

= 2.0.0 =

- Rewritten for PHP 7.4 – 8.5, WordPress 7.1, Elementor 4.2 and WPBakery 8/9
- New Elementor widget with full Style controls
- WPBakery element rebuilt with vc_lean_map, param groups and Design Options
- New engines: OpenStreetMap, Mapbox, Map.ir, Neshan, Parsimap, Mapup, custom XYZ tiles, offline Iran SVG, custom SVG and image maps
- Custom pins with relative (%) or absolute (px) positions, or latitude/longitude
- Settings page and Shortcode Builder built with WordPress components
- New branch editor with OpenStreetMap location picker
- Popup templates are sanitized; SVG uploads are sanitized; no more eval()
- Removed the Google tile-URL hack ("Development mode") and the custom Google logo option


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
