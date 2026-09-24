# PeproDev Branches Map (Mapify)

Show your branches on **Google Maps, OpenStreetMap, Mapbox, Map.ir, Neshan, Parsimap, Mapup**, any XYZ tile server, **your own SVG or image**, or a bundled **offline map of Iran** — as an **Elementor widget**, a **WPBakery Page Builder element** or a **shortcode**.

*Version 2.4.0* · Requires WordPress 6.5+ (tested 7.1.2), PHP 7.4+ (tested 8.5.8), Elementor 4.2.1, WPBakery 8.2 · by [Pepro Dev](https://pepro.dev/), lead programmer [Amirhosseinhpv](https://hpv.im/)

## Structure

| Path | What it does |
|---|---|
| `pepro-mapify.php` | Bootstrap |
| `includes/class-schema.php` | One settings schema used by the shortcode, Elementor, WPBakery and the builder |
| `includes/class-renderer.php` | Server-side markup + front-end config |
| `includes/class-options.php` | Global settings (`mapify_settings`, REST-enabled) |
| `includes/class-admin.php` | Settings page, Shortcode Builder, live preview endpoint |
| `includes/class-branches.php` / `class-branch-editor.php` | Post type, REST meta, category pin settings, branch editor |
| `includes/class-transfer.php` | `mapify/v1` REST routes, JSON import / export, WXR category export |
| `includes/integrations/elementor/` | Elementor widget |
| `includes/integrations/wpbakery/` | WPBakery element |
| `assets/js/mapify-front.js` | All engines (Google, Leaflet tiles, Neshan SDK, SVG/image planes) |
| `assets/js/admin.js` | Settings + builder UI (`@wordpress/components`, no build step) |
| `assets/maps/iran.svg` | Offline Iran provinces map (geoBoundaries, CC BY 4.0) |
| `assets/vendor/` | Leaflet 1.9.4, Leaflet.markercluster 1.5.3, @googlemaps/markerclusterer 2.6.2 |

## Shortcode

```
[pepro-mapify maptype="iran" branchplacement="end" list_layout="cards"]
[pepro-mapify maptype="image" map_image="123" pins="…urlencoded JSON…" branchtype="none"]
[pepro-mapify maptype="osm"]<h3>{title}</h3>{address}[/pepro-mapify]
```

Use **Branches → Shortcode Builder** to generate shortcodes with a live preview.

## REST API

| Route | Access | Returns |
|---|---|---|
| `GET /wp-json/wp/v2/mapify` | public (edit: `edit_posts`) | Branches with `meta` (`place_details_*`) and `mapify_location` `{latitude, longitude, zoom}` |
| `GET /wp-json/wp/v2/mapify_category` | public (edit: `manage_categories`) | Categories with `meta.mapify_pin_color` / `meta.mapify_pin_image` |
| `GET /wp-json/mapify/v1/branches?category=a,b&include=1,2&search=…` | public | Published branches as the map uses them |
| `GET /wp-json/mapify/v1/categories` | public | Categories with pin settings and counts |
| `GET /wp-json/mapify/v1/export?settings=1&categories=1&branches=1` | `manage_options` | Export file (JSON) |
| `POST /wp-json/mapify/v1/import` | `manage_options` | `{data, settings, categories, branches, existing: update\|skip\|duplicate, images}` → counts |

## Screenshots

Screenshots of the admin pages, every Elementor content/style section, every WPBakery tab, front-end maps and Persian (RTL) versions are kept outside the plugin source, in the `screenshots/` folder next to `source/`.

## Credits

Iran map boundaries: [geoBoundaries](https://www.geoboundaries.org/) (CC BY 4.0). Tile style thumbnails in `assets/img/tile-style/`: © OpenStreetMap contributors, OpenStreetMap France / Germany, CyclOSM, OpenTopoMap, Esri and its data providers. Navigation app icons in `assets/img/nav/` are trademarks of their owners and are used only to identify the apps. Leaflet and Leaflet.markercluster: BSD-2 / MIT.
