 var r = {};
 var $_targetSVG = "M9,0C4.029,0,0,4.029,0,9s4.029,9,9,9s9-4.029,9-9S13.971,0,9,0z M9,15.93 c-3.83,0-6.93-3.1-6.93-6.93S5.17,2.07,9,2.07s6.93,3.1,6.93,6.93S12.83,15.93,9,15.93 M12.5,9c0,1.933-1.567,3.5-3.5,3.5S5.5,10.933,5.5,9S7.067,5.5,9,5.5 S12.5,7.067,12.5,9z";
 var $_markerScale = 1;
 var $_targetSVG = "M243.15,0v104.4c44.11,0,80,35.88,80,80c0,44.11-35.89,80-80,80v221.9l146.43-184.1c26.29-33.25,40.19-73.21,40.19-115.58C429.77,83.72,346.05,0,243.15,0zM163.15,184.4c0-44.12,35.89-80,80-80V0C140.25,0,56.53,83.72,56.53,186.62c0,42.37,13.9,82.33,40.23,115.62L243.15,486.3V264.4C199.04,264.4,163.15,228.51,163.15,184.4z";
 var $_markerScale = 0.04;
 var $_pinColor = "#bb1b06";
 var $_mapBg = "#9a9a9a";
 var $_mapHover = "#9a9a9a";
 var $_mapActive = "#9af10c";
 $pinnedMap = [{
   "id": document.getElementById('province').value || "IR-07",
   "showAsSelected": true
 }];
 var $pinnedPinner = [{
   "title": "مکان پین شده",
   "longitude": document.getElementById('pinlong').value || 51.4042,
   "latitude": document.getElementById('pinlat').value || 35.5383,
   "svgPath": $_targetSVG,
   "id": "pinniwinee",
   "scale": $_markerScale,
   "alpha": 1, //Opacity of the image.
 }];
 var map = AmCharts.makeChart("mapifymapdiv", {
   "type": "map",
   "theme": "dark",
   "language": "fa",
   "fontFamily": "IRANYekan", //Font family.
   "preventDragOut": true,
   "dataProvider": {
     "map": "iranHigh",
     "getAreasFromMap": true,
     "areas": $pinnedMap,
     "images": $pinnedPinner
   },
   // "smallMap": {},
   "balloon": {
     "adjustBorderColor": true, //If this is set to true, border color instead of background color will be changed when user rolls-over the slice, graph, etc.
     "animationDuration": .3, //Duration of balloon movement from previous point to current point, in seconds.
     "borderAlpha": 1, //Balloon border opacity. Value range is 0 - 1.
     "borderColor": "#FFFFFF", //Balloon border color. Will only be used of adjustBorderColor is false. REQ -> adjustBorderColor :: TRUE
     "borderThickness": 2, //Balloon border thickness.
     "color": "#000000", //Color of text in the balloon.
     "cornerRadius": 2, //Balloon corner radius.
     "enabled": true, //Use this property to disable balloons
     "fadeOutDuration": .3, //Duration of a fade out animation, in seconds.
     "fillAlpha": 0.8, //Balloon background opacity.
     "fillColor": "#FFFFFF", //Balloon background color. REQ -> adjustBorderColor :: TRUE
     "fontSize": 13, //Size of text in the balloon
     "shadowAlpha": 0.4, //Opacity of a shadow.
     "shadowColor": "#000000", //Color of a shadow.
     "textAlign": "middle", //Text alignment, possible values "left", "middle" and "right"
     "horizontalPadding": 8, //Horizontal padding of the balloon.
     "verticalPadding": 4, //Vertical padding of the balloon.
   },
   "imagesSettings": {
     "centered": true,
     "rollOverScale": 1,
     "selectedScale": 1,
     "selectable": false,
     "color": $_pinColor //Color of image
   },
   "areasSettings": {
     "autoZoom": true,
     "rollOverColor": $_mapHover,
     "selectedColor": $_mapActive,
     "selectable": true,
   },
   "zoomControl": {
     "zoomDuration": 0.1, //Specifies if zoom control is enabled.
     "zoomControlEnabled": true, //Specifies if zoom control is enabled.
     "buttonBorderAlpha": 0.1, //Button border opacity.
     "buttonBorderColor": "#000000", //Color of button borders.
     "buttonBorderThickness": 1, //Button border thickness.
     "buttonColorHover": "#FF0000", //Button roll-over color.
     "buttonCornerRadius": 2, //Button corner radius.
     "buttonFillAlpha": 1, //Button fill opacity.
     "buttonFillColor": "#FFFFFF", //Button fill color.
     "buttonIconAlpha": 1, //Opacity of button icons.
     "buttonIconColor": "#000000", //Button icon color.
     "buttonRollOverColor": "#DADADA", //Button roll-over color.
     "buttonSize": 30, //Size of buttons.
     "draggerAlpha": 0, //Opacity of a dragger.
     "gridAlpha": 0, //Opacity of zoom-grid.
     "gridBackgroundAlpha": 0, //Opacity of background under zoom-grid.
     "gridBackgroundColor": "#000000", //Color of background under zoom-grid.
     "gridColor": "#000000", //Grid color.
     "gridHeight": 5, //Zoom grid height in pixels.
     "homeButtonEnabled": false, //Specifies if home button is visible or not.
     "homeIconColor": "#FFFFFF", //Home icon color.
     "iconSize": 11, //Size of icons. You might need to change size of icon gif files if you change this property.
     "minZoomLevel": 1, //Min zoom level.
     "maxZoomLevel": 65, //Max zoom level.
     "roundButtons": true, //Specifies if buttons should be round or not (rectangular).
     "left": 10, //Distance from left of map container to the zoom control.
     // "right": 10, //Distance from right of map container to the zoom control.
     "top": 10, //Distance from top of map container to the zoom control.
     // "bottom": 10, //Distance from bottom of map container to the zoom control.
     "panControlEnabled": false, //Specifies if pan control is enabled.
     "panStepSize": 0.1, //Specifies by what part of a map container width/height the map will be moved when user clicks on pan arrows.
     "zoomFactor": 1.5, //zoomFactor is a number by which current scale will be multiplied when user clicks on zoom in button or divided when user clicks on zoom-out button.

   },
   "mouseWheelZoomEnabled": true,
   "listeners": [{
     "event": "rendered",
     "method": function(e) {
       // Let's log initial zoom settings (for home button)
       var map = e.chart;
       map.initialZoomLevel = map.zoomLevel();
       map.initialZoomLatitude = map.zoomLatitude();
       map.initialZoomLongitude = map.zoomLongitude();
     }
   }]
 });
 (function($) {
   $(document).ready(function() {
     // map.zoomToLongLat(5, 51.4042, 35.5383);map.zoomToObject(map.getObjectById($pinnedMap.id));
     setTimeout(function(){map.clickMapObject(
       map.getObjectById($pinnedMap[0].id));
       updateFooter("pepro");
     },800);
     $("body").removeClass("notice-0 notice-1 notice-2 notice-3 notice-present");
     $(document).on('mapify_backend_loaded', function(e) {
       setTimeout(function() {
         $.toptip("Welcome to Mapify workstation ¯\\_(ツ)_\/¯ All data loaded successfully", 5000, "w3-green");
       }, 200);
     });
     $(document).trigger("mapify_backend_loaded");
     $(document).on("click", "#save", function(e) {
       e.preventDefault();
       let str = `${map.zoomLevel()},${map.zoomLongitude()},${map.zoomLatitude()}`;
       if (typeof map.selectedObject.id === "undefined") {
         alert("No pin found in SVG Map");
       } else {
         console.log({
           "1. province en": map.selectedObject.title,
           "2. province fa": map.selectedObject.titleTr,
           "3. province id": map.selectedObject.id,
           "4. zoomLevel": map.zoomLevel(),
           "5. zoomLongitude": map.zoomLongitude(),
           "6. zoomLatitude": map.zoomLatitude(),
           "7. selected latitude": map.getObjectById("pinniwinee").latitude,
           "8. selected longitude": map.getObjectById("pinniwinee").longitude,
         });
         console.log(str);
       }

     });
     map.addListener("zoomCompleted", updateDataSet);
     map.addListener("positionChanged", updateDataSet);
     map.addListener("click", function(event) {
       // deselct all ! :D by amirhosseinhpv
       map.dataProvider.areas.map(function(e) {
         e.showAsSelected = false;
         return e;
       });
       // reset zoom later to current level
       map.dataProvider.zoomLevel = map.zoomLevel();
       map.dataProvider.zoomLatitude = map.dataProvider.zoomLatitudeC = map.zoomLatitude();
       map.dataProvider.zoomLongitude = map.dataProvider.zoomLongitudeC = map.zoomLongitude();
       var info = map.getDevInfo();
       let sel = map.selectedObject;
       var image = new AmCharts.MapImage();
       image.latitude = info.latitude,
         image.selectable = false,
         image.longitude = info.longitude,
         image.svgPath = $_targetSVG,
         image.scale = $_markerScale,
         image.id = "pinniwinee",
         image.alpha = 1,
         image.title = "مکان پین شده";
       map.dataProvider.images = new Array(image);
       map.validateData();
       setTimeout(function() {
         if (typeof map.selectedObject.id === "undefined") {
           map.dataProvider.images = new Array();
           map.validateData();
         }
       }, 10);
       setTimeout(function() {
         updateDataSet();
       }, 100);
       updateFooter();

     });
     function updateFooter(sclass = "pep") {
       jQuery(".mapify-container-parent div#mapifymapdiv svg+a").remove();

       jQuery(".mapify-container-parent div#mapifymapdiv svg desc").remove();
     }
     function updateDataSet() {
       if (typeof map.selectedObject.id === "undefined") {
         // nothing
       } else {
         $(".mapify-selected-details").html(`${map.selectedObject.title} // ${map.getObjectById("pinniwinee").longitude},${map.getObjectById("pinniwinee").latitude} // ${map.selectedObject.id}`);
         var $vDATA = new Array();
         $vDATA.push({
           "province_title": map.selectedObject.title,
           "province_id": map.selectedObject.id,
           "pin_longitude": map.getObjectById("pinniwinee").longitude,
           "pin_latitude": map.getObjectById("pinniwinee").latitude,
           "zoom_longitude": map.zoomLongitude(),
           "zoom_latitude": map.zoomLatitude(),
           "zoom_level": map.zoomLevel(),
         });
         // $("#jsondata").val(JSON.stringify($vDATA));
         $("#province").val(map.selectedObject.id);
         $("#pinlong").val(map.getObjectById("pinniwinee").longitude);
         $("#pinlat").val(map.getObjectById("pinniwinee").latitude);
       }
     }
   });
 })(jQuery);
