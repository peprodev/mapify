/**
 * @Author: Amirhosseinhpv
 * @Date:   2020/08/13 20:47:03
 * @Email:  its@hpv.im
 * @Last modified by:   Amirhosseinhpv
 * @Last modified time: 2021/03/27 17:11:54
 * @License: GPLv2
 * @Copyright: Copyright © 2020 Amirhosseinhpv, All rights reserved.
 */


/*
get data from snizy!
https://snazzymaps.com/

var url = $("#editor-map>div>.gm-style div div div div div:nth-child(8) img").attr("src");
$('[data-target=".code-body-collapse"]').click();
alert(url.substring(url.indexOf("5e18!12m4"),url.indexOf("!4e0&key")));
alert($("#style-json").text().replace(/(\r\n|\n|\r)/gm, "").replace('\t','').replace(/ /gm,''));

*/
(function($) {
  $(document).ready(function() {
    var map = [],
    mapify_googlemap_pins_list = [],
    mapify_googlemap_hotspots_list = [],
    mapsAll,
    provinceArr,
    sRchRslt,
    markerCluster = [],
    iMapStyle = [],
    isCustom = [],
    isTheme = [],
    isDefault = [],
    isWithoutAPI = [],
    marker = [],
    mRAW_DATA = [];

    $.each(mapifyObjects,function(index,value) {
        M = eval(value);
        var mM = null;
        mM = eval(`${value}.uniqid`);
        try {
          if (M.done === true){
            return;
          }
        }
        catch (e) {} /*singeltone !*/
        var MRAW_DATA = eval(`M.raw_data${mM}`);
        mRAW_DATA[mM] = eval(`M.raw_data${mM}`);
        var MSETTINGS = eval(`M.settings${mM}`);
        var MCONTAINER = eval(`M.container${mM}`);
        if ("googlemap" == MSETTINGS.maptype){
          if (typeof google !== "undefined") {
            google.maps.event.addDomListener(window, 'load', mapify_googlemap_init);
          }
          else {
            alert("ERR: Cannot initiate Google Map!");
            return false;
          }
        }
        function mapify_googlemap_init() {
          E = MSETTINGS.center_coordinate.split(","); var gstyles = []; gstyles[mM] = []
          iMapStyle[mM] = MSETTINGS.map_style;
          isCustom[mM] = ( iMapStyle[mM] === "custom") ? true : false;
          isTheme[mM] = ( !isCustom[mM] && iMapStyle[mM] !== "" ) ? true : false;
          isDefault[mM] = ( iMapStyle[mM] === "") ? true : false;
          isWithoutAPI[mM] = ( MSETTINGS.usegmapcopyright === "true") ? true : false;
          if (isCustom[mM] || isTheme[mM]){
            try {
              gstyles[mM] = eval(MSETTINGS.googlemap_style);
            } catch (e) {
              if (e instanceof SyntaxError) {
                setTimeout(console.error.bind(console, `%cPepro Branches Map :: GoogleMap Style Parsing Error, Style skipped!%c\r\nError: ${e.message}, `, "font-size: 15px;", ""));
              }
            }
          }
          map[mM] = new google.maps.Map(document.getElementById(`${MCONTAINER}`), {
            minZoom: 1,
            zoom: parseInt(MSETTINGS.default_zoom),
            disableDefaultUI: (MSETTINGS.disabledefaultui == "true"),
            // gestureHandling: 'greedy',
            icon: MSETTINGS.pinimage,
            center: new google.maps.LatLng(E[0], E[1]),
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            styles: gstyles[mM],
          });
          if ((isWithoutAPI[mM] && isDefault[mM]) || (isWithoutAPI[mM] && isTheme[mM])){ setInterval(mapify_googlemap_remap, 30); }
          mapify_googlemap_redraw_clusters(mM);
          map[mM].addListener("tilesloaded", function(){ $(`#${MCONTAINER}`).addClass("loaded");});
          if (MSETTINGS.branchlistshow == "true"){
            mapify_googlemap_draw_branches_list(eval(`${value}.el_id`), eval(`${value}.uniqid`), MSETTINGS.brancheslistcat, MSETTINGS.branchplacement,(MSETTINGS.branchessearch == "true"));
            $(document).on("click tap",".mapify-branches-item",function(e){
              e.preventDefault();
              let me = $(this);
              var id = me.data("id");
              var mM = me.data("obj");

              map[mM].setZoom(15);
              map[mM].panTo(marker[mM][id].position);
              new google.maps.event.trigger( marker[mM][id],'click');

            });
            $(document).on("click tap change keyup",".mapify-branches-list-search-input",function(e){
              e.preventDefault();
              var me = $(this), searchTerm = me.val(), uid = me.attr("data-uid"), mM = me.attr("data-mmm"), categorizment = me.attr("data-categorizment"), alignment = me.attr("data-alignment"), showsearch = me.attr("data-showsearch");
              mapify_googlemap_search(searchTerm, uid, mM, categorizment, alignment, showsearch);
            });
          }
        }
        function mapify_googlemap_remap() {
          skip = false;
          if( isWithoutAPI[mM] && isDefault[mM]   ){}                 // hAPI and Default Theme   ~> ok
          if( isWithoutAPI[mM] && isTheme[mM]     ){}                 // hAPI and Built-in Theme  ~> ok
          if( isWithoutAPI[mM] && isCustom[mM]    ){skip = true;}     // hAPI and Custom Theme    ~> skip

          if( !isWithoutAPI[mM] && isDefault[mM]  ){skip = true;}     // Original API and Default Theme   ~> skip
          if( !isWithoutAPI[mM] && isTheme[mM]    ){skip = true;}     // Original API and Built-in Theme  ~> ok
          if( !isWithoutAPI[mM] && isCustom[mM]   ){skip = true;}     // Original API and Custom Theme    ~> skip

          if (skip){return;}

          var gimg = $(`#${MCONTAINER} img[src*="maps.googleapis.com/maps/vt?"]:not(.gmf)`);

          $.each(gimg, function(i,x){
            var imgurl = $(x).attr("src");
            var urlarray = imgurl.split('!');
            var newurl = ""; var newc = 0;
            for (i = 0; i < 1000; i++) { if (urlarray[i] == "2sen-US"){ newc = (!isCustom[mM] && !isDefault[mM]) ? i+1 : i-3; break; } }
            for (i = 0; i < newc+1; i++) { newurl += urlarray[i] + "!"; }
            newurl = (!isCustom[mM] && !isDefault[mM]) ? newurl + iMapStyle[mM] : newurl;
            $(x).attr("src", newurl).addClass("gmf");
          });
        }
        function mapify_googlemap_hotspot_html(pin,m) {
          // template
          var string = MSETTINGS.popup_markup;

          // replace variables in template in most dynamic way ever existed!
          //
          // get data inside curly braces: /[^{\}]+(?=})/gi
          // get data including curly braces: /{([^}]+)}/g
          // get only start and ending braces: /[{}]/g

          string = replaceAll(string, /{([^}]+)}/g,function(a, b){
            var curly = c = a;
            var nocurly = replaceAll(curly, /[{}]/g,"");
            var val = $.trim(nocurly.split("|")[0]);
            var def = $.trim(nocurly.split("|")[1]);
            if (!def){ def = nocurly; }
            if("undefined" !== typeof pin[val]){ a = pin[val]; }
            if (!a){ a = def; }
            return a;
          });
          return string;
        }
		function replaceAll(str, find, replace) {
			return str.replace(new RegExp(find, 'g'), replace);
		}
        function mapify_googlemap_draw_hotspots(mM) {
          mapify_googlemap_hotspots_list[mM] = [];
          mapify_googlemap_pins_list[mM] = [];
          marker[mM] = [];
          $.each(MRAW_DATA, function(key, pin) {
            var pin_img_uri = M.spotlight;
            if ( "" !== MSETTINGS.pinimage){
              pin_img_uri = mapify_isvalidimgurl(MSETTINGS.pinimage) || M.spotlight;
            }else{
              pin_img_uri = mapify_isvalidimgurl(pin.pin_img) || M.spotlight;
            }
            marker[mM][pin.id] = new google.maps.Marker({
              map: map[mM],
              title: pin.title,
              draggable: false,
              animation: google.maps.Animation.DROP,
              icon: pin_img_uri,
              position: new google.maps.LatLng(pin.map_data.latitude, pin.map_data.longitude),
            });
            content = mapify_googlemap_hotspot_html(pin,mM);
            marker[mM][pin.id]['infowindow'] = new google.maps.InfoWindow({content: content});
            mapify_googlemap_pins_list[mM].push(marker[mM][pin.id]);
            mapify_googlemap_hotspots_list[mM].push(marker[mM][pin.id]['infowindow']);
            google.maps.event.addListener(map[mM], 'click', mapify_googlemap_hotspots_close_all);
            marker[mM][pin.id]["url"] = pin.url;
            google.maps.event.addListener(marker[mM][pin.id], 'click', function(e){
              switch (MSETTINGS.pinaction) {
                case "url":
                  var win = window.open(this.url, MSETTINGS.pinurltarget || '_blank');
                  win.focus();
                  break;
                case "popup":
                  mapify_googlemap_hotspots_close_all();
                  this['infowindow'].open(map[mM], this);
                  break;
                default:
                  return;
              }
            });
            google.maps.event.addListener(marker[mM][pin.id]['infowindow'], 'domready', function(evt) {
              var iwOuter = $('.gm-style-iw');
              iwOuter.addClass("googlemapinfo_div " + M.el_id);
              iwOuter.parent().addClass("googlemapinfo_div_parent " + M.el_id);
            });
          });
          if ("true" == MSETTINGS.branchascluster){
            markerCluster[mM] = new MarkerClusterer(map[mM], mapify_googlemap_pins_list[mM], {
            imagePath: `${M.clusterrepo}/m`,
            gridSize: parseInt(MSETTINGS.clustergridsize||100),
            minimumClusterSize: parseInt(MSETTINGS.clusterminsize||5)
          });
          }
        }
        function mapify_googlemap_hotspots_close_all() {
          for (var i = 0; i < mapify_googlemap_hotspots_list[mM].length; i++) {
            mapify_googlemap_hotspots_list[mM][i].close();
          }
        }
        function mapify_googlemap_redraw_clusters(mM) {
          if (markerCluster[mM] instanceof MarkerClusterer) {
            markerCluster[mM].clearMarkers()
          }
          mapify_googlemap_draw_hotspots(mM);
        }
        function mapify_googlemap_clear_hotspots() {
          for (var i = 0; i < mapify_googlemap_pins_list[mM].length; i++) {
            mapify_googlemap_pins_list[mM][i].setMap(null);
          }
          mapify_googlemap_pins_list[mM].length = 0;
        }
        function mapify_googlemap_draw_branches_list(uid, mM, categorizment, alignment, showsearch) {
          var container = $(`.mapify-branches-list-container.${alignment}.${uid}`);
          container.append(`
            <div class="mapify-branches-list-search-container ${uid} ${mM}" style="margin-bottom: .3rem;">
            <input data-uid="${uid}"
              data-mmm="${mM}"
              data-categorizment="${categorizment}"
              data-alignment="${alignment}"
              data-showsearch="${showsearch}"
              class="mapify-branches-list-search-input"
              placeholder="${M.searchtxt}" />
            </div>
            <div data-err="${M.searcherr}" class="mapify-branches-list-search-result ${uid} ${mM}"></div>`);
          if (!showsearch){
            $(`.mapify-branches-list-search-container.${uid}`).hide();
          }
          searchResult = mRAW_DATA[mM];
          switch (MSETTINGS.brancheslistcat) {
            case "category":
              uncatzedcount = 0; $(`.mapify-branches-list-search-result.${uid}`).append(`<div data-mmm="${mM}" id='mapify-branches-cat-uncatzed'><div>${M.uncatzed}</div></div>`);
              $.each(searchResult, function(key, pin) {
                if (pin.categories.length !== 0){
                  cats = new Array;
                  $.each(pin.categories, function(index, val) {
                    cats.push(val);
                    if (!$(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-${index}`).length){
                      $(`.mapify-branches-list-search-result.${uid}`).append(`<div data-mmm="${mM}" id='mapify-branches-cat-${index}'><div>${val}</div></div>`)
                    }
                    container.find(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-${index}`).append(`<a href="#" class="mapify-branches-item id-${pin.id}" title="${pin.title} - ${cats.join(", ")}" data-categories="${cats}" data-obj="${mM}" data-id="${pin.id}">${pin.title}</a>`);
                  });
                }
                else{
                  uncatzedcount += 1;
                  $(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-uncatzed`).show();
                  container.find(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-uncatzed`).append(`<a href="#" class="mapify-branches-item id-${pin.id}" title="${pin.title} - ${M.uncatzed}" data-categories="${M.uncatzed}" data-obj="${mM}" data-id="${pin.id}">${pin.title}</a>`);
                }
                if (!uncatzedcount){
                  $(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-uncatzed`).hide();
                }
              });
              break;
            default:
              $.each(searchResult, function(key, pin) {
                  cats = M.uncatzed; if (pin.categories.length !== 0){ cats = new Array; $.each(pin.categories, function(index, val) { cats.push(val); }); cats = cats.join(", "); }
                  container.find(`.mapify-branches-list-search-result.${uid}`).append(`<a href="#" class="mapify-branches-item id-${pin.id}" title="${pin.title} - ${cats}" data-categories="${cats}" data-obj="${mM}" data-id="${pin.id}">${pin.title}</a>`);
              });
          }


        }
        function mapify_googlemap_search(searchTerm , uid, mM, categorizment, alignment, showsearch) {

           var searchResult = [], numSearchTermFound = 0, searchExpression = new RegExp(searchTerm, "i");
           container = $(`.mapify-branches-list-container.${alignment}.${uid}`);
           container_results = container.find(".mapify-branches-list-search-result");

           $.each(mRAW_DATA[mM], function(key, value) {
             $.each(value, function(i, val) {
               // var arrayofData2SearchIn = ["additional", "address", "categories", "email", "facebook", "instagram", "linkedin", "phone", "site", "telegram", "title", "twitter", "url"];
               if (val.toString().search(searchExpression) != -1){
                 searchResult.push(value);
                 numSearchTermFound += 1;
               }
             });

           });
           searchResult = searchResult.filter(function onlyUnique(value, index, self) {return self.indexOf(value) === index;});
           if (!showsearch){
             $(`.mapify-branches-list-search-container.${uid}`).hide();
           }
           if (numSearchTermFound > 0){
             container_results.html("");
             console.log(MSETTINGS.brancheslistcat);
             switch (MSETTINGS.brancheslistcat) {
               case "category":
                 uncatzedcount = 0; $(`.mapify-branches-list-search-result.${uid}`).append(`<div data-mmm="${mM}" id='mapify-branches-cat-uncatzed'><div>${M.uncatzed}</div></div>`);
                 $.each(searchResult, function(key, pin) {
                   if (pin.categories.length !== 0){
                     cats = new Array;
                     $.each(pin.categories, function(index, val) {
                       cats.push(val);
                       if (!$(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-${index}`).length){
                         $(`.mapify-branches-list-search-result.${uid}`).append(`<div data-mmm="${mM}" id='mapify-branches-cat-${index}'><div>${val}</div></div>`)
                       }
                       container.find(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-${index}`).append(`<a href="#" class="mapify-branches-item id-${pin.id}" title="${pin.title} - ${cats.join(", ")}" data-categories="${cats}" data-obj="${mM}" data-id="${pin.id}">${pin.title}</a>`);
                     });
                   }
                   else{
                     uncatzedcount += 1;
                     $(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-uncatzed`).show();
                     container.find(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-uncatzed`).append(`<a href="#" class="mapify-branches-item id-${pin.id}" title="${pin.title} - ${M.uncatzed}" data-categories="${M.uncatzed}" data-obj="${mM}" data-id="${pin.id}">${pin.title}</a>`);
                   }
                   if (!uncatzedcount){
                     $(`.mapify-branches-list-search-result.${uid} #mapify-branches-cat-uncatzed`).hide();
                   }
                 });
                 break;
               default:
                 $.each(searchResult, function(key, pin) {
                     cats = M.uncatzed; if (pin.categories.length !== 0){ cats = new Array; $.each(pin.categories, function(index, val) { cats.push(val); }); cats = cats.join(", "); }
                     container.find(`.mapify-branches-list-search-result.${uid}`).append(`<a href="#" class="mapify-branches-item id-${pin.id}" title="${pin.title} - ${cats}" data-categories="${cats}" data-obj="${mM}" data-id="${pin.id}">${pin.title}</a>`);
                 });
             }
           }
           else{
             container_results.html(container_results.data("err"));
           }
        }
        function mapify_isvalidimgurl(url=""){
            var image = new Image();
            image.src = url;
            if (image.width !== 0) { return url; }
            return false;
         }

      M.done = true;
    });

  });
})(jQuery);
