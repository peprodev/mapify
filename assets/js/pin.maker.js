 (function($) {
   $(document).ready(function() {
     $(".wpb_vc_param_value.wpb-textinput.pinimagehelper").hide();
     $(".peprovc_pinimagegenerator .edit_form_line").append(`
       <div class='wpb_vc_param_value wpb-pin-image-generator wpb-select'>
            <div>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="red" checked    /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_red.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="black"          /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_black.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="blue"           /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_blue.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="green"          /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_green.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="grey"           /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_grey.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="orange"         /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_orange.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="purple"         /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_purple.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="white"          /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_white.png') no-repeat; background-size: cover;"/></label>
             <label style="display: inline-flex;align-content: center;align-items: center;text-align: center;flex-direction: column-reverse;"><input style="width: auto;" type="radio" name="peprodev_add_pin_image_generator_color" value="yellow"         /><img style="	background: url('${markermaker.vc_pinmarkermaker_dirfolder}/_yellow.png') no-repeat; background-size: cover;"/></label>
           </div>

           <div>
             <select name='peprodev_add_pin_image_generator_char' id='peprodev_add_pin_image_generator_char'>
               <optgroup label="${markermaker.vc_pinmarkermaker_symbols}">
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

              <optgroup label="${markermaker.vc_pinmarkermaker_character}">
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

              <optgroup label="${markermaker.vc_pinmarkermaker_numbers}">
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
            <img src="" id="peprodev_add_pin_image_generator_preview" alt="pin" style="width: 22px; height: auto;" />
           </div>


       </div>`);
     peprodev_add_pin_image_generator_build();
     $(document).on("change", "[name='peprodev_add_pin_image_generator_color'] , #peprodev_add_pin_image_generator_char", function(e) {
       e.preventDefault();
       peprodev_add_pin_image_generator_build();
       $('[name="pinimage"]').val($("#peprodev_add_pin_image_generator_preview").attr("src"));
     });
     function peprodev_add_pin_image_generator_build() {
       var color = $("[name='peprodev_add_pin_image_generator_color']:checked").val() || "red";
       var char = $("#peprodev_add_pin_image_generator_char").val() || "";
       var data = `${markermaker.vc_pinmarkermaker_dirfolder}/marker_${color}${encodeURIComponent(char)}.png`;
       $("#peprodev_add_pin_image_generator_preview").attr("src",data).data("url",data);
     }


     $(document).on("click tap", "#mapify_load_default_markup", function(e) {
       e.preventDefault(); let me = $(this);
       $("textarea[name='popup_markup']").val(markermaker.default_template);
     });

   });
 })(jQuery);
