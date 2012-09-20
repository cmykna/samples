/*jshint laxcomma:true */
// 
var FORM = (function ($, window) {
  "use strict";
  // Put all the stuff we need to manipulate the form in an object literal
  return {

    // Custom form inputs and temp storage for non-static US/INTL inputs
    // 08-07-12 CMC: this is stupidly complicated. Postfix markup s/b defined
    // in index.php using $form_presets. TODO.
    dynamic: {
      us: ''
    , intl: ''
    , prefix: ''
    , postfix: {
        TCK_Reg: {
          markup: [
            '<label for="TCK_EULA">'
          , '<input type="checkbox" id="TCK_EULA" class="required" name="TCK_EULA" value="YES"> '
          , '<p style="display:inline;">I have viewed and agree to the <a rel="terms" href="https://www-k6.thinkcentral.com/nsmedia/footer/terms_of_use.html">'
          , 'ThinkCentral Terms of Use</a> and <a rel="privacy" href="https://www-k6.thinkcentral.com/nsmedia/footer/privacy_policy.html">'
          , 'Privacy Policy</a></p></label>'
          ].join("")
        }
      }
    }

    // Storage for data that needs to be accessible throughout the form
  , conf: {
      form_type: '',
      schoolDistrictOptions: '',
      sfdcResults: {},
      campaignResults: {},
      elqSiteID: 1145,
      elqCookieWrite: 0,
      elqName: '',
      loc: ''
    }

    // Form initialization
  , init: function (form) {
      var geo = window.GEOIP || {}
      , country = geo.country_code;

      // Store the eloqua form name that we got from PHP
      FORM.conf.elqName = $('[name="elqFormName"]').val();

      // Location-dependent initialization
      if (country === 'US' || country === undefined) {
        $('#C_Location').val('us');
        FORM.conf.loc = 'us';
        /* this.updateCampaignCodes(); */
      } else {
        $('#C_Location').val('international');
        $('#C_Country').val(country);
        FORM.conf.loc = 'international';
      }

      // Cut conditionally presented markup and store it
      this.dynamic.us = $('[data-loc=us]').remove();
      this.dynamic.intl = $('[data-loc=intl]').remove();

      this.conf.form_type = form;

      // Move conditional inputs into the DOM
      this.toggleLocation();

      // If we've got pre/postfix markup that matches the form_type, 
      // dump it into the custom input containers and display them
      // All.this.namespacing.is.starting.to.look.like.java(omg)
      if (this.dynamic.postfix.hasOwnProperty(this.conf.form_type)) {
        $('#postfix').html(this.dynamic.postfix[this.conf.form_type].markup).show();
      }

      this.attachEvents();

      // Change the form target if we're running in an iframe
      if (window !== window.top) {
        $('.contact-form').attr('target','_parent');
      }

      // Return the form's container so we can chain hide/show to init
      return $('.main-form');

    }

  , attachEvents: function () {
      $('#C_Location').change(this.toggleLocation);
      $('#C_Zip').change(this.getSfAccount);
      $('#C_SchoolDistrict').change(this.updateMdrAndAccount);
      $('#C_State_Prov').change(this.getCampaignCodes);
      $('#C_Campaign11').change();
    }
    
    // Returns JSON from Salesforce with School Name, SFDCAccountID, mdrPid
    // for schools in a given zip code, then populates the School District
    // select box.
  , getSfAccount: function () {
      // Only do this if the zip has 5+ digits, otherwise SF will return
      // LOOOOONNNG result sets
      if ($('#C_Zip').val().length >= 5) {                   
        var options = '';
        $.ajax({url: "//" + window.location.hostname + "/utils/sfdcCustomer-json.php",
          data: 'zipCode=' + base64_encode($('#C_Zip').val()),
          dataType: 'json',
          async: false,
          success: function (data) {
            var result, value = '';
            for (result in data) {
              if (data.hasOwnProperty(result)) {
                value = data[result][0];
                FORM.conf.sfdcResults[value] = {
                  SFDCAccountID: data[result][1],
                  mdrPid: data[result][2]
                }; 
                options += '<option value="' + value + '">' + value + '</option>';                
              }
            }
            FORM.districtSelect(options);
          },

          error: function (data) {
            FORM.districtSelect(options);
          },

          complete: function (data) {
            return;
          }
        });
      }
    }

  , getCampaignCodes: function () {
      var ccode = (getQueryVariable('code') === -1) ? HMH_CODE : getQueryVariable('code')
        , state = $('#C_State_Prov').val() || "";
      $.ajax({ url: "//" + window.location.hostname + "/utils/get_campaign_codes.php"
      , data: 'hmhcode=' + ccode + '&state=' + state
      , dataType: 'json'
      , success: function(data) {
          FORM.conf.campaignResults = data;
          FORM.updateCampaignCodes(state);
        }
      });
    }

    // DOM manipulation stuff lives here, to keep it separated from
    // AJAX requests.

    // Need to dynamically calculate form height because we don't 
    // necessarily know what kind of pre- and post-fixed form controls 
    // might be there.
    // However! We only want to do this if we're rendering a desktop form.
  , position: function(form_type) {
      var loc = FORM.conf.loc
        , $form_ol = $('#olWrapper ol')
        , setHeight = function () {
            $form_ol.height(function () {
              var dynHeight = 0;
              $form_ol.find('li').filter(':visible').each(function (){
                dynHeight += $(this).outerHeight();
              });
              if (loc === 'us') {
                return dynHeight/2;
              } else if (loc === 'international') {
                return dynHeight/2+30;
              }
            });
          };

      if (form_type === "TCK_Reg" ||
          form_type === "Email" ||
          form_type === "VS_Registration" ||
          form_type === "SHS" ||
          form_type === "Journeys_eval") {
          setHeight();
      // Contact forms don't need dynamic height, but we do have to insert
      // and hide a spacer li between the form and the message box for intl
      // form.
      } else if (form_type === 'Contact_form') {
        if (loc === 'us') {
          $('.spacer').remove();
        } else if (loc === 'international') {
          $('<li class="spacer">').insertBefore('#special-request');
        }
      }
    }

  , toggleLocation: function () {
      var val = $('#C_Location option:selected').val()
      , fm = FORM.conf.form_type
      , elqn = FORM.conf.elqName
      , dyn = FORM.dynamic
      , tck = $('[name=tckServerType]').val();

      FORM.conf.loc = val;

      
      // Have to check if the tckServerType here === cert and turn off
      // the domestic/intl elqName postfixing to support a hack Mario needs
      // to do to keep TCK's testing stuff pointed to their cert server.
      

      if (val === 'us') {
        if (tck === 'cert') {
          $('input[name=elqFormName]').val(elqn);
        } else {
          $('input[name=elqFormName]').val(elqn + "_Domestic");
        }
        $(dyn.us).insertAfter('#city');
        $('[data-loc=intl]').detach();

        FORM.position(fm);
        FORM.getCampaignCodes();

      } else if (val === 'international') {
        if (tck === 'cert') {
          $('input[name=elqFormName]').val(elqn);
        } else {
          $('input[name=elqFormName]').val(elqn + "_Intl");
        }
        $(dyn.intl).insertAfter('#city');
        $('[data-loc=us]').detach();

        FORM.position(fm);
        FORM.getCampaignCodes();
      }
    }

  , districtSelect: function (options) {
      var output = [
        '<option value="" selected="selected">Which institution are you at?</option>'
      , options
      , '<option value="Not Listed">Not Listed</option>'
      ].join("\n");

      if (FORM.conf.schoolDistrictOptions !== options) {
        $("select#C_SchoolDistrict").html(output);
      }

      FORM.conf.schoolDistrictOptions = options;

      $("select#C_SchoolDistrict").prop("disabled", false);
      $('#school-district').removeClass("disabled");
      $("select#C_SchoolDistrict").focus();
    }

  , updateMdrAndAccount: function () {
      var obj = FORM.conf.sfdcResults
      , school_name = $('#C_SchoolDistrict option:selected').val();

      // Make sure the selection is in the result obj 
      if (obj.hasOwnProperty(school_name)) {
        $("#SFDCAccountID").val(obj[school_name].SFDCAccountID);
        $("#mdrPid").val(obj[school_name].mdrPid);
      } else {
        // Empty out values if there's not a match
        $("#SFDCAccountID").val('');
        $("#mdrPid").val('');
      }
    }

  , updateCampaignCodes: function (state) {
      var obj = FORM.conf.campaignResults
      , $hmhfield = $('input[name="C_Campaign11"]')
      , $sffield = $('input[name="sfdccampaignid"]')
      , hmh_natl = obj.NATL.hmhcode
      , sf_natl = obj.NATL.sfcode
      , hmh_state = (obj[state] === undefined) ? hmh_natl : obj[state].hmhcode
      , sf_state = (obj[state] === undefined) ? sf_natl : obj[state].sfcode;

      // Clear 'em out first
      $hmhfield.val('');
      $sffield.val('');

      // Populate fields based on state info if US
      if (FORM.conf.loc === 'us') {
        (hmh_state !== -1)
        ? $hmhfield.val(hmh_state)
        : $hmhfield.val(hmh_natl);

        (sf_state !== -1)
        ? $sffield.val(sf_state)
        : $sffield.val(sf_natl);        
      }

      // Just default to national values if INTL
      if (FORM.conf.loc === 'international') {
        $hmhfield.val(hmh_natl);
        $sffield.val(sf_natl);
      }
    }
   };
}(jQuery, window));

// Detect certain weirdo forms and define functions to handle them:
// If it's a Journeys form OR if it's a TCK form with
// a Florida access word, we want to initialize both of
// these functions, and we'll call one or both as needed.
// Florida needs some DOM manipulation to remove certain fields.
if ((getQueryVariable('form') === 'journeys') ||
    (getQueryVariable('tck') === 'FLJOURNEYS14')) {

  // This will prepend a screen before the actual form that allows
  // the user to select a state-specific Journeys evaluation. Markup
  // for this lives in form.tpl.php, in div.pre-form. 
  var JOURNEYS = (function ($) {
    return {
      init: function () {
        // Initialize the form as usual, then hide it.
        FORM.init($('.contact-form').attr('id')).hide();
        $('map').on('click', JOURNEYS.mapClick);
        $('[name="eval_state"]').change(JOURNEYS.evalSelect);
        return $('.pre-form');
      }

    , mapClick: function (e) {
        var eval_code = e.target.hash.replace("#", "")
          , state_text = e.target.alt;
        JOURNEYS.displayMainForm(eval_code, state_text);
      }

    , evalSelect: function () {
        var $select = $(this)
        , eval_code = $select.find('option:selected').val()
        , state_text = $select.find('option:selected').text();
        JOURNEYS.displayMainForm(eval_code, state_text);
      }

    , displayMainForm: function (ev, txt) {
        var $mainh1 = $('.main-form h1')
          , ev = ev.toLowerCase()
          , state = (ev === 'journeys14') ? 'na' : ev.substr(0,2);

        $('[name="accessWord"]').val(ev);

        // We do NOT want the state dropdown to re-run the SFID lookup...
        $('#C_State_Prov').unbind();

        //...instead, we'll do it based on the state the user selected in pre-form
        $.ajax({ url: "//" + window.location.hostname + "/utils/get_campaign_codes.php"
        , data: 'hmhcode=' + ev + '&state=' + state
        , dataType: 'json'
        , success: function(data) {
            $('[name=C_Campaign11]').val(data.NATL.hmhcode);
            $('[name=sfdccampaignid]').val(data.NATL.sfcode);
          }
        });

        $mainh1.text('Evaluate ' + txt + ' Journeys');

        // Remove the link to swap back to the map if it's already there
        if ($('.main-form h1 + p').length === 1) {
          $('.main-form h1 + p').remove();
        }

        // Inject the link to swap back to the map
        $mainh1.after('<p data-back="pre-form"><a>Select a different state</a></p>');
        $('[data-back="pre-form"]').click(function (e) {
          window.parent.location.reload();
        });

        $('.pre-form').hide();
        // If the user selected Florida, run our Florida DOM hilarity
        if (ev.toLowerCase() === 'fljourneys14') {
          // Call DOM hilarity directly instead of re-initializing FORM.
          FLJOURNEYS.manip().show();
        } else {
          $('.main-form').show();
        }
      }

    }
  })(jQuery);

  var FLJOURNEYS = (function ($) {
    return {
      init: function () {
        FORM.init($('.contact-form').attr('id')).hide();
        this.manip();
        return $('.main-form');
      }
    , manip: function () {
        $('[data-loc="us"], #city, #location, #phone-number, #job-role').remove();
        $('#vs-form').width('450px').height('181px');
        $('#postfix').css('text-align','left');
        $('fieldset').width('450px');
        /* $('button[name="contact"]').css({'float':'left','margin-left':'150px'}); */
        $('.buffer').css('margin-left','25%');
        return $('.main-form');
      }
    }
  })(jQuery);
}

// Experimental :P
var logger = (function ($, window) {
  var logelem = '<div id="log" class="logger"></div>';
  return {
    create: function() {
      $('.main-form h1').after(logelem);
    }
  , update: function(text) {
      $('#log').text(text);
    }
  , setWidth: function(w) {
      $('#log').width(w);
    }
  };

})(jQuery, window);

$(document).ready(function () {

  var form_var = getQueryVariable('form');

  // Overrides stupid base css styling for .email, which is all blue and
  // italic. We just need to use .email as a validator hook, so it should
  // look the same as everything else. This really should be a CSS fix.
  // TODO: see above. Blah.
  $('#C_EmailAddress').css({
    'font-size':'15px',
    'color':'black',
    'font-family':'sans-serif'
  });


  // 3 cases to handle if Journeys:
  // 
  // 1. Journeys form, user selects Florida
  // 2. Journeys form, user selects anything else
  // 3. Think Central form with FLJOUREYS14 accessWord
  // 
  // If it's a Journeys form, do our special initialization

  if (form_var === 'journeys') {
    JOURNEYS.init().show();
    $('#postfix').html(FORM.dynamic.postfix.TCK_Reg.markup).show();
  } else if (getQueryVariable('tck') === 'FLJOURNEYS14') {
    // Get all our elements in the right place
    FLJOURNEYS.init().show();
  } else {
    FORM.init($('.contact-form').attr('id'));
  }

  // Validate dat form
  $("#"+FORM.conf.form_type).validate({
    validClass: "valid"
  , errorClass: "error"

  , highlight: function(element, errorClass, validClass) {
      $(element).parent().closest('li').removeClass(validClass).addClass(errorClass);
    }

  , unhighlight: function(element, errorClass, validClass) {
      $(element).parent().closest('li').removeClass(errorClass).addClass(validClass);
    }

  , messages: {
    // Validation messages for static inputs
      C_EmailAddress: {
        required: 'Email address <span class="req">required</span>'
      , email: 'Please enter a valid email address.'
      }

      , C_FirstName: {
          required: 'First name <span class="req">required</span>'
      }

      , C_LastName: {
          required: 'Last name <span class="req">required</span>'
      }

      , C_Mailing_City11: {
          required: 'City <span class="req">required</span>'
      }

      , C_BusPhone: {
          required: 'Phone number <span class="req">required</span>'
      }

      , C_Job_Role1: {
          required: 'Role <span class="req">required</span>'
      }

      // School or district exists in both layouts, but is a text input
      // in intl, and a select dropdown in domestic. This doesn't affect
      // the message we need, but it's not technically "static".
      , C_SchoolDistrict: {
          required: 'Choose your institution <span class="req">required</span>'
      }

      // Validation messages for international inputs
      , C_Country: {
          required: 'Country <span class="req">required</span>'
      }

      // Validation messages for domestic inputs
      , C_State_Prov: {
          required: 'State <span class="req">required</span>'
      }

      , C_Zip: {
          required: 'School Zip Code <span class="req">required</span>'
      }

      , TCK_EULA: {
          required: '<p style="display: inline-block; color:red;"><strong>Please confirm: </strong></p>'
      }
    }
  });

  // Fancybox invocation for TCK form. This should be moved into
  // a config object.
  $("a[rel=terms], a[rel=privacy]").fancybox({
    'padding'     : 5,
    'overlayOpacity'  : .8,
    'scrolling'     : 'yes',
    'width'       : 500,
    'height'      : 500,
    'showNavArrows'   : false,
    'transitionIn'    : 'none',
    'transitionOut'   : 'none',
    'type'        : 'iframe', 
    'titleShow'     : false
  });
});
