# PeproDev Branches Map (Mapify)

Show your branches on **Google Maps, OpenStreetMap, Mapbox, Map.ir, Neshan, Parsimap, Mapup**, any XYZ tile server, **your own SVG or image**, or a bundled **offline map of Iran** — as an **Elementor widget**, a **WPBakery Page Builder element** or a **shortcode**.

*Version 2.0.0* · Requires WordPress 6.5+ (tested 7.1.2), PHP 7.4+ (tested 8.5.8), Elementor 4.2.1, WPBakery 8.2 · by [Pepro Dev](https://pepro.dev/), lead programmer [Amirhosseinhpv](https://hpv.im/)

## Structure

| Path | What it does |
|---|---|
| `pepro-mapify.php` | Bootstrap |
| `includes/class-schema.php` | One settings schema used by the shortcode, Elementor, WPBakery and the builder |
| `includes/class-renderer.php` | Server-side markup + front-end config |
| `includes/class-options.php` | Global settings (`mapify_settings`, REST-enabled) |
| `includes/class-admin.php` | Settings page, Shortcode Builder, live preview endpoint |
| `includes/class-branches.php` / `class-branch-editor.php` | Post type, data, branch editor |
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

## Screenshots

See [`screenshots/`](screenshots/) — admin pages, every Elementor content/style section, every WPBakery tab, front-end maps and Persian (RTL) versions.

## Credits

Iran map boundaries: [geoBoundaries](https://www.geoboundaries.org/) (CC BY 4.0). Leaflet and Leaflet.markercluster: BSD-2 / MIT.
