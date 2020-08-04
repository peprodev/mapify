 (function($) {
   $(document).ready(function() {
     var map,
       googlemap_infotip,
       googlemap_marker, chart,
       googlemap_infotip_icon = MAPIFY.spotlight,
       googlemap_infotip_title = MAPIFY.drag,
       googlemap_infotip_dragtxt = MAPIFY.dragging,
       googlemap_infotip_context = MAPIFY.drop,
       googlemap_infotip_content = "<div>" + googlemap_infotip_context + "</div>",
       prev_data = $("#map_data").val();

     $("#mapify-pinimg>td").append(`
       <div class='peprobackend-pin-image-generator wpb-select'>
            <div>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="red" checked /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_red.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="black"   /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_black.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="blue"    /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_blue.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="green"   /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_green.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="grey"    /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_grey.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="orange"  /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_orange.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="purple"  /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_purple.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="white"   /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_white.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprobackend-pin" value="yellow"  /><img style="	background: url('https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/source/_yellow.png') no-repeat; background-size: cover;"/></label>
           </div>

           <div>
             <select name='peprobackend-pin-char' id='peprobackend-pin-char'>
               <optgroup label="${MAPIFY.vc_pinmarkermaker_symbols}">
                <option value='' selected>•</option>
                <option value='!'>!</option>
                <option value='@'>@</option>
                <option value='$'>$</option>
                <option value='+'>+</option>
                <option value='-'>-</option>
                <option value='='>=</option>
                <option value='#'>#</option>
                <option value='%'>%</option>
                <option value='&'>&</option>
              </optgroup>

              <optgroup label="${MAPIFY.vc_pinmarkermaker_character}">
                <option value='A'>A</option>
                <option value='B'>B</option>
                <option value='C'>C</option>
                <option value='D'>D</option>
                <option value='E'>E</option>
                <option value='F'>F</option>
                <option value='G'>G</option>
                <option value='H'>H</option>
                <option value='I'>I</option>
                <option value='J'>J</option>
                <option value='K'>K</option>
                <option value='L'>L</option>
                <option value='M'>M</option>
                <option value='N'>N</option>
                <option value='O'>O</option>
                <option value='P'>P</option>
                <option value='Q'>Q</option>
                <option value='R'>R</option>
                <option value='S'>S</option>
                <option value='T'>T</option>
                <option value='U'>U</option>
                <option value='V'>V</option>
                <option value='W'>W</option>
                <option value='X'>X</option>
                <option value='Y'>Y</option>
                <option value='Z'>Z</option>
              </optgroup>

              <optgroup label="${MAPIFY.vc_pinmarkermaker_numbers}">
                <option value='1'>1</option>
                <option value='2'>2</option>
                <option value='3'>3</option>
                <option value='4'>4</option>
                <option value='5'>5</option>
                <option value='6'>6</option>
                <option value='7'>7</option>
                <option value='8'>8</option>
                <option value='9'>9</option>
                <option value='10'>10</option>
                <option value='11'>11</option>
                <option value='12'>12</option>
                <option value='13'>13</option>
                <option value='14'>14</option>
                <option value='15'>15</option>
                <option value='16'>16</option>
                <option value='17'>17</option>
                <option value='18'>18</option>
                <option value='19'>19</option>
                <option value='20'>20</option>
                <option value='21'>21</option>
                <option value='22'>22</option>
                <option value='23'>23</option>
                <option value='24'>24</option>
                <option value='25'>25</option>
                <option value='26'>26</option>
                <option value='27'>27</option>
                <option value='28'>28</option>
                <option value='29'>29</option>
                <option value='30'>30</option>
                <option value='31'>31</option>
                <option value='32'>32</option>
                <option value='33'>33</option>
                <option value='34'>34</option>
                <option value='35'>35</option>
                <option value='36'>36</option>
                <option value='37'>37</option>
                <option value='38'>38</option>
                <option value='39'>39</option>
                <option value='40'>40</option>
                <option value='41'>41</option>
                <option value='42'>42</option>
                <option value='43'>43</option>
                <option value='44'>44</option>
                <option value='45'>45</option>
                <option value='46'>46</option>
                <option value='47'>47</option>
                <option value='48'>48</option>
                <option value='49'>49</option>
                <option value='50'>50</option>
                <option value='51'>51</option>
                <option value='52'>52</option>
                <option value='53'>53</option>
                <option value='54'>54</option>
                <option value='55'>55</option>
                <option value='56'>56</option>
                <option value='57'>57</option>
                <option value='58'>58</option>
                <option value='59'>59</option>
                <option value='60'>60</option>
                <option value='61'>61</option>
                <option value='62'>62</option>
                <option value='63'>63</option>
                <option value='64'>64</option>
                <option value='65'>65</option>
                <option value='66'>66</option>
                <option value='67'>67</option>
                <option value='68'>68</option>
                <option value='69'>69</option>
                <option value='70'>70</option>
                <option value='71'>71</option>
                <option value='72'>72</option>
                <option value='73'>73</option>
                <option value='74'>74</option>
                <option value='75'>75</option>
                <option value='76'>76</option>
                <option value='77'>77</option>
                <option value='78'>78</option>
                <option value='79'>79</option>
                <option value='80'>80</option>
                <option value='81'>81</option>
                <option value='82'>82</option>
                <option value='83'>83</option>
                <option value='84'>84</option>
                <option value='85'>85</option>
                <option value='86'>86</option>
                <option value='87'>87</option>
                <option value='88'>88</option>
                <option value='89'>89</option>
                <option value='90'>90</option>
                <option value='91'>91</option>
                <option value='92'>92</option>
                <option value='93'>93</option>
                <option value='94'>94</option>
                <option value='95'>95</option>
                <option value='96'>96</option>
                <option value='97'>97</option>
                <option value='98'>98</option>
                <option value='99'>99</option>
              </optgroup>
            </select>
           </div>

           <div>
            <img src="" id="mappinpreview" alt="pin" title="${MAPIFY.vc_pinmarkermaker_clipboard}" style="width: 22px; height: auto;" />
           </div>


       </div>`);
     $(document).on("change", "[name='peprobackend-pin'] , #peprobackend-pin-char", function(e) {
       e.preventDefault();
       peprodev_add_pin_image_generator_build();
       $('#pinimg').val($("#mappinpreview").attr("src")).trigger("keyup");
     });

     if ($.trim(prev_data) !== "") {
       map_data = JSON.parse($.trim(prev_data));
       var defLng = map_data.longitude;
       var defLat = map_data.latitude;
       var defzoom = map_data.gzoom;
     } else {
       var defLng = 53.06119;
       var defLat = 36.56442;
       var defzoom = 9;
     }

     if (typeof google !== "undefined") {
       google.maps.event.addDomListener(window, 'load', googlemap_init);
     } else {
       alert("ERR: Cannot initiate Google Map!");
       return false;
     }

     $(document).on("click tap", "#go_mapupdate", function(e) {
       e.preventDefault();
       let me = $(this);
       var go_latitude = $("#go_latitude").val();
       if ($.trim(go_latitude) !== "" && !isNaN(go_latitude)) {
         go_latitude = parseFloat(go_latitude);
       } else {
         return;
       }
       var go_longitude = $("#go_longitude").val();
       if ($.trim(go_longitude) !== "" && parseFloat(go_longitude)) {
         go_longitude = parseFloat(go_longitude);
       } else {
         return;
       }
       if (go_latitude && go_longitude) {
         googlemap_update_marker(go_latitude, go_longitude);
         googlemap_update_address(go_latitude, go_longitude);
       }
     });

     function googlemap_marker_set_icon() {
       var image = new Image();
       var url_image = $("#pinimg").val() || MAPIFY.spotlight;
       image.src = url_image;
       if (image.width == 0) {
         googlemap_infotip_icon = MAPIFY.spotlight;
       } else {
         googlemap_infotip_icon = $("#pinimg").val();
       }
       googlemap_marker.setIcon(googlemap_infotip_icon);
     }

     function googlemap_init() {

       var image = new Image();
       var url_image = $("#pinimg").val() || MAPIFY.spotlight;
       image.src = url_image;
       if (image.width == 0) {
         googlemap_infotip_icon = MAPIFY.spotlight;
       } else {
         googlemap_infotip_icon = $("#pinimg").val();
       }

       var center = new google.maps.LatLng(defLat, defLng);
       var markerpops = new google.maps.LatLng(defLat, defLng);
       var mapOptions = {
         zoom: defzoom,
         minZoom: 1,
         center: center,
         mapTypeId: google.maps.MapTypeId.ROADMAP,
       };
       map = new google.maps.Map(document.getElementById('googlemapdiv'), mapOptions);
       googlemap_infotip = new google.maps.InfoWindow({
         content: googlemap_infotip_content,
         maxWidth: 350
       });
       googlemap_marker = new google.maps.Marker({
         position: markerpops,
         map: map,
         icon: googlemap_infotip_icon,
         draggable: true,
         title: googlemap_infotip_title,
       });
       google.maps.event.addListener(map, 'click', function(evt) {
         googlemap_infotip.close();
       });
       google.maps.event.addListener(googlemap_marker, 'click', function(evt) {
         googlemap_infotip.open(map, googlemap_marker);
       });
       google.maps.event.addListener(googlemap_marker, 'dragend', function(evt) {
         googlemap_update_address(this.getPosition().lat().toFixed(7), this.getPosition().lng().toFixed(7));
       });
       google.maps.event.addListener(map, 'zoom_changed', function(evt) {
         var prev_data = $("#map_data").val();
         if ($.trim(prev_data) !== "") {
           map_data = JSON.parse($.trim(prev_data));
           googlemap_update_address(map_data.latitude, map_data.longitude);
         }
       });
       google.maps.event.addListener(googlemap_marker, 'dragstart', function(evt) {
         $("#mapify-selected-details>div:last-of-type").html(`${googlemap_infotip_dragtxt}`)
       });
       google.maps.event.addListener(googlemap_infotip, 'domready', function(evt) {
         var iwOuter = $('.gm-style-iw');
         iwOuter.addClass("googlemapinfo_div");
         iwOuter.parent().addClass("googlemapinfo_div_parent");
       });

       map.addListener("tilesloaded", function() {
         $("#googlemapdiv").addClass("loaded");
         setTimeout(function() {
           var prevattr = $("#googlemapdiv>div iframe+div a").attr("title");
           $("#googlemapdiv>div iframe+div a").attr("title", `${prevattr}\r\nCreated using Pepro Branches Map (Mapify)\r\nby Pepro Dev ( https://pepro.dev/ )`);
         }, 200);
       });

       // google.maps.event.addListener(map, 'center_changed', googlemap_remap);
       // google.maps.event.addListener(map, 'click', googlemap_remap);
       // google.maps.event.addListener(map, 'dblclick', googlemap_remap);
       // google.maps.event.addListener(map, 'zoom_changed', googlemap_remap);

       setTimeout(function() {googlemap_generate_mapdata(defLat, defLng);}, 200);
       setInterval(googlemap_remap, 50);
       setInterval(googlemap_marker_set_icon, 50);

     }

     function peprodev_add_pin_image_generator_build() {
       var color = $("[name='peprobackend-pin']:checked").val() || "red";
       var char = $("#peprobackend-pin-char").val() || "";
       var data = `https://raw.githubusercontent.com/amirhosseinhpv/Google-Maps-Markers/master/images/marker_${color}${encodeURIComponent(char)}.png`;
       $("#mappinpreview").attr("src", data).data("url", data);
     }

     function googlemap_update_marker(lat, lng) {
       var latlng = new google.maps.LatLng(lat, lng);
       googlemap_marker.setPosition(latlng);
       map.setCenter(latlng);
       googlemap_remap();
     }

     function googlemap_generate_mapdata(latitude, longitude) {
       map_data = {
         latitude: latitude,
         longitude: longitude,
         gzoom: map.getZoom(),
       };
       $("#cordlat").val(latitude);
       $("#pinlat").val(latitude);
       $("#go_latitude").val(latitude);

       $("#cordlong").val(longitude);
       $("#pinlong").val(longitude);
       $("#go_longitude").val(longitude);

       $("#mapify-selected-details>div:last-of-type").html(`Pinned at ${latitude},${longitude}`);

       map_data = JSON.stringify(map_data);

       $("#map_data").val(map_data);
       googlemap_remap();
     }

     function googlemap_update_address(latitude, longitude) {
       googlemap_generate_mapdata(latitude, longitude);
       googlemap_remap();
     }

     function googlemap_remap() {
       if (MAPIFY.api === "1") {
         return;
       }
       var gimg = $(`img[src*="maps.googleapis.com/maps/vt?"]:not(.gmf)`);
       $.each(gimg, function(i, x) {
         var imgurl = $(x).attr("src");
         var urlarray = imgurl.split('!');
         var newurl = "";
         var newc = 0;
         for (i = 0; i < 1000; i++) {
           if (urlarray[i] == "2sen-US") {
             newc = i - 3;
             break;
           }
         }
         for (i = 0; i < newc + 1; i++) {
           newurl += urlarray[i] + "!";
         }
         $(x).attr("src", newurl).addClass("gmf");
       });
     }
   });
 })(jQuery);
