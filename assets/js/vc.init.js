(function($) {
  function build_query() {
    return {
      branchtype                  : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=branchtype]").val(),
      branchids                   : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=branchids]").val(),
      branchcat                   : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=branchcat]").val(),
      maptype                     : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=maptype]").val(),
      center_coordinate           : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=center_coordinate]").val(),
      default_zoom                : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=default_zoom]").val(),
      loading_image               : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=loading_image]").val(),
      loading_color               : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=loading_color]").val(),
      mapfooterimage              : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=mapfooterimage]").val(),
      usegmapcopyright            : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=usegmapcopyright]").is(":checked"),
      disabledefaultui            : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=disabledefaultui]").is(":checked"),
      peprodev_map_defined_style  : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=peprodev_map_defined_style]:checked").val(),
      googlemap_style             : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=googlemap_style]").val(),
      branchlistshow              : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=branchlistshow]").is(":checked"),
      branchessearch              : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=branchessearch]").is(":checked"),
      branchplacement             : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=branchplacement]").val(),
      brancheslistcat             : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=brancheslistcat]").val(),
      branchascluster             : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=branchascluster]").is(":checked"),
      clustergridsize             : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=clustergridsize]").val(),
      clusterminsize              : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=clusterminsize]").val(),
      pinimage                    : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=pinimage]").val(),
      pinaction                   : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=pinaction]").val(),
      pinurltarget                : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=pinurltarget]").val(),
      popup_markup                : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=popup_markup]").val(),
      el_id                       : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=el_id]").val(),
      el_class                    : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=el_class]").val(),
      el_map_width                : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=el_map_width]").val(),
      el_map_height               : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=el_map_height]").val(),
      custom_css_code             : $(".vc_ui-panel-window.vc_ui-panel.vc_active [name=custom_css_code]").val(),
      mapify_version              : GLOBAL_MAPIFY_VERSION,
      vc_version                  : GLOBAL_VC_VERSION,
      php_version                 : GLOBAL_PHP_VERSION,
      wp_version                  : GLOBAL_WP_VERSION,
    };
  }
  $(document).unbind("click tap");
  $(document).unbind("click tap");
  $(document).unbind("click tap");

  $(document).on("click tap",".vc_edit-form-tab-control, .mapify-export",function(e){
    e.preventDefault();
    var me = $(this);
    $("#mapify-importexport").val(JSON.stringify(build_query()));

  });
  $(document).on("click tap",".mapify-export",function(e){
    e.preventDefault();
    var me = $(this);
    $("#mapify-importexport").select();
    document.execCommand('copy');
    me.html(me.data("copied"));
    setTimeout(function () {
      me.html(me.data("caption"));
    }, 1000);
  });
  $(document).on("click tap",".mapify-import",function(e){
    e.preventDefault();
    var me = $(this),
    data = $("#mapify-importexport").val();

    if ($.trim(data) == ""){
      alert(me.data("empty"));
    }

    try {
      a = JSON.parse(data);
      $.each(a, function(id,val) {
        switch (id) {
          // checkboxes
          case "usegmapcopyright":
          case "disabledefaultui":
          case "branchlistshow":
          case "branchessearch":
          case "branchascluster":
            $(`.vc_ui-panel-window.vc_ui-panel.vc_active [name='${id}']`).prop("checked", val).trigger("change");
            break;
          case "peprodev_map_defined_style":
            $(`.vc_ui-panel-window.vc_ui-panel.vc_active [name='${id}'][value='${val}']`).prop("checked", val).trigger("change");
            break;
          case "branchids":
          case "branchcat":
            $(`.vc_ui-panel-window.vc_ui-panel.vc_active [name='${id}']`).val("").trigger("chosen:updated");
            $(`.vc_ui-panel-window.vc_ui-panel.vc_active [name='${id}']`).chosen("destroy").val("").val(val).trigger("change").chosen().trigger("chosen:updated");
            break;
          default:
            $(`.vc_ui-panel-window.vc_ui-panel.vc_active [name='${id}']`).val(val).trigger("change");
        }
      });
        alert(GLOBAL_IMPORT_DONE)
    } catch(e) {
      alert(e);
    }

  });

  var $_rtl = $("body").is(".rtl") ? true : false;
  $(".chosen-select").chosen({ rtl: $_rtl });
  $('[data-vc-shortcode-param-name="googlemap_style__preview"] .googlemap_style__preview.textfield').hide().after("<h3>{title}</h3><img id='googlemap_style__preview' style='width: 100%;height: auto; border-radius: 5px;' src='"+$(`#map_defined_style_default`).attr("src")+"' />")
  mapfiy_change_vc_param($("#map_defined_style").val()||"default");
  $(document).on("change", ".peprodevvcinputforradio.map_defined_style", function(e) {
    e.preventDefault(); let me = $(this);
    mapfiy_change_vc_param(me.val());
  });
  function mapfiy_change_vc_param(current) {
    if ("custom" == current) {
      $('[data-vc-shortcode-param-name="googlemap_style__preview"]').hide();
      $('[data-vc-shortcode-param-name="googlemap_style"]').show();
      $('.vcpepro_radio_item_container.map_defined_style:not(.custom) label > img').css("opacity", 0.5);
    } else {
      $("#googlemap_style__preview").attr("src", $(`#map_defined_style_${current}`).attr("src"));
      $("#googlemap_style__preview").attr("alt", $(`#map_defined_style_${current}`).attr("alt"));
      $("#googlemap_style__preview").parent().find("h3").html($(`#map_defined_style_${current}`).attr("alt"));

      $('[data-vc-shortcode-param-name="googlemap_style"]').hide();
      $('[data-vc-shortcode-param-name="googlemap_style__preview"]').show();
      $('.vcpepro_radio_item_container.map_defined_style:not(.custom) label > img').css("opacity", 1);
    }
  }

})(window.jQuery);
