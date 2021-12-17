require('./bootstrap');


// GENERATING CODE VERIFIER
function dec2hex(dec) 
{
  return ("0" + dec.toString(16)).substr(-2);
}

function generateCodeVerifier()
{
  var array = new Uint32Array(56 / 2);
  window.crypto.getRandomValues(array);
  return Array.from(array, dec2hex).join("");
}

function sha256(plain)
{
  // returns promise ArrayBuffer
  const encoder = new TextEncoder();
  const data = encoder.encode(plain);
  return window.crypto.subtle.digest("SHA-256", data);
}

function base64urlencode(a) 
{
  var str = "";
  var bytes = new Uint8Array(a);
  var len = bytes.byteLength;

  for (var i = 0; i < len; i++) {
    str += String.fromCharCode(bytes[i]);
  }

  return btoa(str)
    .replace(/\+/g, "-")
    .replace(/\//g, "_")
    .replace(/=+$/, "");
}

async function generateCodeChallengeFromVerifier(v)
{
  var hashed = await sha256(v);
  var base64encoded = base64urlencode(hashed);
  return base64encoded;
}





window.authorizeGooglDriveApp = function()
{
  gapi.load('client:auth2', function()
  {
    var clientId     = $('input[name="google_drive[client_id]"]').val().trim();
    var clientSecret = $('input[name="google_drive[secret_id]"]').val().trim();

    gapi.auth2.authorize({
      'scope': 'https://www.googleapis.com/auth/drive email',
      'clientId': clientId,
      'response_type': 'code id_token permission',
      'prompt': 'select_account consent'
    }, 
    function(response)
    {
      if(response.hasOwnProperty('error'))
      {
        alert(response.error + ' - ' + response.detail)
      }
      else
      {
        var idToken = response.id_token;
        var payload = {clientId: clientId, clientSecret: clientSecret, code: response.code};

        $.post(`${googleDriveCurrentUserRoute}`, {"id_token": idToken}, null, 'json')
        .done((res) =>
        {
          $('input[name="google_drive[connected_email]"]').val(res.email);
          $('input[name="google_drive[id_token]"]').val(idToken);
        })
        .fail(() =>
        {
          alert('Google drive "Current user" Request failed');
          return;
        })

        $.post(googleDriveCode2AcessTokenRoute || '', payload, null, 'json')
        .done((res) =>
        {
          if(res.hasOwnProperty('error'))
          {
            alert(res.error);
            return;
          }
          
          $('input[name="google_drive[refresh_token]"]').val(res.refresh_token);
        })
        .fail(() =>
        {
          alert('Google drive "Refresh token" Request failed')
        })
      }
    })
  });
}


/*
curl -X POST https://graph.microsoft.com/v1.0/{foxtinez@gmail.com}/revokeSignInSessions \
-H "Content-Type: application/json" \
-H "Authorization: Bearer "

*/


window.authorizeOneDriveApp = function()
{
  var userPrincipalName = $('input[name="one_drive[connected_email]"]').val();
  var refreshToken      = $('input[name="one_drive[refresh_token]"]').val();
  var accessToken       = $('input[name="one_drive[access_token]"]').val();
  var idToken           = $('input[name="one_drive[id_token]"]').val();
  var clientId          = $('input[name="one_drive[client_id]"]').val();
  var clientSecret      = $('input[name="one_drive[client_secret]"]').val();
  var objectId          = $('input[name="one_drive[object_id]"]').val();
  var directoryId       = $('input[name="one_drive[directory_id]"]').val();

  // https://graph.microsoft.com/v1.0/me/drive/items/root/children

  var logoutWindow = window.open(`https://login.live.com/oauth20_logout.srf?client_id=${clientId}&redirect_uri=${oneDriveRedirectUrl}`);

  logoutWindow.addEventListener('load', function () 
  {
    logoutWindow.close();

    window.focus();

    var codeVerifier  = generateCodeVerifier();
    var codeChallengePromise = generateCodeChallengeFromVerifier(codeVerifier);

    codeChallengePromise.then(function(codeChallenge)
    {
      localStorage.setItem('oneDrivePayload', JSON.stringify({clientId, clientSecret, objectId, directoryId, codeVerifier, codeChallenge}));

      location.href = `https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=${clientId}&scope=Files.ReadWrite.All+openid+profile+email&response_type=code&redirect_uri=${oneDriveRedirectUrl}&response_mode=query&code_challenge=${codeChallenge}&code_challenge_method=S256`;
    })
  })

  // https://account.live.com/consent/Manage
}


window.getOneDriveAccessToken = function()
{
  if(!localStorage.hasOwnProperty('oneDrivePayload'))
    return;

  var urlParams = new URLSearchParams(window.location.search);
  var code = urlParams.get('code');

  if(code === null) return;

  var reqParams = JSON.parse(localStorage.getItem('oneDrivePayload')) || {};

  var payload = `client_id=${reqParams.clientId}&redirect_uri=${oneDriveRedirectUrl}&code=${code}&scope=Files.ReadWrite.All+openid+profile+email&grant_type=authorization_code&code_verifier=${reqParams.codeVerifier}`;

  $.post('https://login.microsoftonline.com/common/oauth2/v2.0/token', payload)
  .done(function(data)
  {
    $('input[name="one_drive[refresh_token]"]').val(data.refresh_token);
    $('input[name="one_drive[id_token]"]').val(data.id_token);
    $('input[name="one_drive[access_token]"]').val(data.access_token);
    $('input[name="one_drive[client_id]"]').val(reqParams.clientId || null);
    $('input[name="one_drive[secret_id]"]').val(reqParams.clientSecret || null);

    $.ajax({
      url: 'https://graph.microsoft.com/v1.0/me',
      headers: {"Authorization": `Bearer ${data.access_token}`},
      type: "GET",
      success: function(data)
      {
        $('input[name="one_drive[connected_email]"]').val(data.userPrincipalName || null);
      }
    })

    localStorage.removeItem('oneDrivePayload');

    history.replaceState({}, document.title, "/admin/settings/files_host");
  })
}


window.authorizeDropBoxApp = function()
{
  var clientId            = $('input[name="dropbox[app_key]"]').val();
  var clientSecret        = $('input[name="dropbox[app_secret]"]').val();

  var payload = `client_id=${clientId}&response_type=token&redirect_uri=${dropBoxRedirectUri}&force_reauthentication=true&force_reapprove=true`;

  localStorage.setItem('dropBoxPayload', JSON.stringify({clientId, clientSecret}));

  location.href = `https://www.dropbox.com/oauth2/authorize?${payload}`;
}


window.getDropBoxAccessToken = function()
{
  if(!localStorage.hasOwnProperty('dropBoxPayload'))
    return;

  if(location.href.split('#').length === 2)
  {
    var dropBoxReqResponse =  Object.fromEntries(location.href.split('#')[1].split('&').map(el => {
                                return el.split('=', 2)
                              })) || {};

    $.post(`${dropboxCurrentAccount}`, {"access_token": dropBoxReqResponse.access_token}, null, 'json')
    .done((res) =>
    {
      $('input[name="dropbox[current_account]"]').val(res.email);
    })
    .fail(() =>
    {
      alert('"Current dropbox account" Request failed');
      return;
    })

    var reqParams = JSON.parse(localStorage.getItem('dropBoxPayload')) || {};

    $('input[name="dropbox[access_token]"]').val(dropBoxReqResponse.access_token);

    $('input[name="dropbox[app_key]"]').val(reqParams.clientId || null);
    $('input[name="dropbox[app_secret]"]').val(reqParams.clientSecret || null);

    localStorage.removeItem('dropBoxPayload');
    history.replaceState({}, document.title, "/admin/settings/files_host");
  }
}


window.authorizeYandexApp = function()
{
  var clientId = $('input[name="yandex[client_id]"]').val();
  var secretId = $('input[name="yandex[secret_id]"]').val();

  localStorage.setItem('yandexPayload', JSON.stringify({clientId, secretId}));

  location.href = `https://oauth.yandex.com/authorize?response_type=code&client_id=${clientId}&force_confirm=true`;
}


window.getYandexAccessToken = function()
{
  if(!localStorage.hasOwnProperty('yandexPayload'))
    return;

  if(location.href.includes('?'))
  {
    var yandexPayload = JSON.parse(localStorage.getItem('yandexPayload'));
    var code          = location.href.split('?')[1].split('=')[1];

    $.post(yandexDiskCode2AccessTokenRoute, {code, ...yandexPayload}, null, 'json')
    .done(function(res)
    {
      if(res.hasOwnProperty('error'))
      {
        alert(`Error : ${res.error}`)
      }
      else
      {
        $('input[name="yandex[refresh_token]"]').val(res.refresh_token);
      }
    })

    localStorage.removeItem('yandexPayload');
    history.replaceState({}, document.title, "/admin/settings/files_host");
  }
}

window.testAmazonS3Connection = function(e)
{
  var $this = $(e);
  
  $this.addClass('disabled').prop('disbaled', true);

  var payload = {
    access_key_id : $('#settings input[name="amazon_s3[access_key_id]"]').val().trim(),
    secret_key    : $('#settings input[name="amazon_s3[secret_key]"]').val().trim(),
    bucket        : $('#settings input[name="amazon_s3[bucket]"]').val().trim(),
    region        : $('#settings input[name="amazon_s3[region]"]').val().trim(),
    version       : $('#settings input[name="amazon_s3[version]"]').val().trim()
  };

  $.post('/admin/settings/files_host/test_amazon_s3_connection', payload)
  .done(function(data)
  {
    alert(data.status);

    $this.removeClass('disabled').prop('disbaled', false);
  })
}


window.testWasabiConnection = function(e)
{
  var $this = $(e);
  
  $this.addClass('disabled').prop('disbaled', true);

  var payload = {
    access_key    : $('#settings input[name="wasabi[access_key]"]').val().trim(),
    secret_key    : $('#settings input[name="wasabi[secret_key]"]').val().trim(),
    bucket        : $('#settings input[name="wasabi[bucket]"]').val().trim(),
    region        : $('#settings input[name="wasabi[region]"]').val().trim(),
    version       : $('#settings input[name="wasabi[version]"]').val().trim()
  };

  $.post('/admin/settings/files_host/test_wasabi_connection', payload)
  .done(function(data)
  {
    alert(data.status);

    $this.removeClass('disabled').prop('disbaled', false);
  })
}

window.debounce = function(func, wait, immediate) {
  var timeout;
  return function() {
    var context = this, args = arguments;
    var later = function() {
      timeout = null;
      if (!immediate) func.apply(context, args);
    };
    var callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(context, args);
  };
};


window.__ = function(key, params = {})
{
  var string = window.translation[key] || key;

  if(Object.keys(params).length)
  {
    for(var k in params)
    {
      string = string.replace(`:${k}`, params[k]);
    }
  }

  return string;
}



$(function()
{  
  getDropBoxAccessToken();
  getYandexAccessToken();
  getOneDriveAccessToken();

  $(document).on('click', '#product .tab.segment[data-tab="faq"] .actions i', function()
  {
    var action = $(this).data('action');

    if(! /^add|remove$/i.test(action)) return;

    var thisFaq = $(this).closest('.segment');

    if(action === 'add')
    {
      var dict  = $(this).closest('.tab').data('dict');

      $(`<div class="ui segment">
          <div class="field">
            <label>${dict.Question}</label>
            <input type="text" name="question[]" class="faq-question" placeholder="...">
          </div>
          <div class="field">
            <label>${dict.Answer}</label>
            <textarea name="answer[]" class="faq-answer" cols="30" rows="3" placeholder="..."></textarea>
          </div>
          <div class="actions right aligned">
            <i class="times grey circle big icon link" data-action="remove" title="${dict.Remove}"></i>
            <i class="plus blue circle big icon link mx-0" data-action="add" title="${dict.Add}"></i>
          </div>
        </div>`).insertAfter(thisFaq);
    }
    else
    {
      if($('#product .tab.segment[data-tab="faq"] .segment').length === 1) return;

      thisFaq.remove();
    }

    var faqs = $('#product .tab.segment[data-tab="faq"] .segment');

    faqs.each(function()
    {
      $(this).find('.faq-question').attr('name', `question[${faqs.index($(this))}]`);
      $(this).find('.faq-answer').attr('name', `answer[${faqs.index($(this))}]`);
    })
  })



  $(document).on('click', '#product .tab.segment[data-tab="additional-fields"] .actions i', function()
  {
    var action = $(this).data('action');

    if(! /^add|remove$/i.test(action)) return;

    var thisFields = $(this).closest('.segment');

    if(action === 'add')
    {
      var dict  = $(this).closest('.tab').data('dict');

      $(`<div class="ui segment">
          <div class="two fields">
            <div class="three columns wide field">
              <label>${dict.Name}</label>
              <input type="text" name="_name_[]" class="additional-info-name" placeholder="...">
            </div>
            <div class="thirteen columns wide field">
              <label>${dict.Value}</label>
              <input type="text" name="_value_[]" class="additional-info-value" placeholder="...">
            </div>
          </div>
          <div class="actions right aligned">
            <i class="times grey circle big icon link" data-action="remove" title="${dict.Remove}"></i>
            <i class="plus blue circle big icon link mx-0" data-action="add" title="${dict.Add}"></i>
          </div>
        </div>`).insertAfter(thisFields);
    }
    else
    {
      if($('#product .tab.segment[data-tab="additional-fields"] .segment').length === 1) return;

      thisFields.remove();
    }

    var fields = $('#product .tab.segment[data-tab="additional-fields"] .segment');

    fields.each(function()
    {
      $(this).find('.additional-fields-name').attr('name', `name[${fields.index($(this))}]`);
      $(this).find('.additional-fields-value').attr('name', `value[${fields.index($(this))}]`);
    })
  })



  $(document).on('click', '#product .tab.segment[data-tab="table-of-contents"] .actions i', function()
  {
    var action = $(this).data('action');

    if(! /^add|remove$/i.test(action)) return;

    var thisRow = $(this).closest('tr');

    if(action === 'add')
    {
      var dict  = $(this).closest('table').data('dict');

      $(`<tr>
          <td>
            <div class="ui floating circular fluid dropdown large basic button mx-0">
              <input type="hidden" name="text_type[]" class="toc-type">
              <span class="default text">${dict.Type}</span>
              <i class="dropdown icon"></i>
              <div class="menu">
                <a class="item" data-value="header">${dict.Header}</a>
                <a class="item" data-value="subheader">${dict.Subheader}</a>
                <a class="item" data-value="subsubheader">${dict['Sub-Subheader']}</a>
              </div>
            </div>
          </td>
          <td class="ten column wide right aligned">
            <input type="text" name="text[]" class="toc-text" placeholder="...">
          </td>
          <td class="two column wide center aligned actions">
            <i class="times grey circle big icon link" data-action="remove" title="${dict.Remove}"></i>
            <i class="plus blue circle big icon link mx-0" data-action="add" title="${dict.Add}"></i>
          </td>
        </tr>`).insertAfter(thisRow);

      $('.ui.dropdown').dropdown();
    }
    else
    {
      if($('#product .tab.segment[data-tab="table-of-contents"] table tbody tr').length === 1) return;

      thisRow.remove();
    }

    var rows = $('#product .tab.segment[data-tab="table-of-contents"] table tbody tr');

    rows.each(function()
    {
      $(this).find('.toc-type').attr('name', `text_type[${rows.index($(this))}]`);
      $(this).find('.toc-text').attr('name', `text[${rows.index($(this))}]`);
    })
  })


  $(document).on('click', '.logout', function() {
    $('#logout-form').submit();
  })

  $('.ui.rating').rating('disable');
  
  $('#post .ui.placeholder input').on('change', function()
  { 
    var _this  = $(this); 
    var file   = $(this)[0].files[0];
    var reader = new FileReader();

    reader.addEventListener("load", function() 
    {
      _this.parent().find('img').attr('src', reader.result);

    }, false);

    reader.readAsDataURL(file);

    _this.siblings('.image')
         .toggleClass('ui', true)
         .find('img').show();
  })


  $('.ui.dropdown.languages .item').on('click', function()
  {
    $('#set-locale input[name="locale"]')
    .val($(this).data('locale'))
    .closest('form').submit();
  })

  $('.message .close')
  .on('click', function() {
    $(this)
      .closest('.message')
      .transition('fade')
    ;
  })


  $('#mobile-menu-toggler, #cover').on('click', function()
  {
      $('#content .l-side-wrapper')
      .toggleClass('active')
      .transition('slide right');

      $('#cover').toggleClass('d-none', !$('#content .l-side-wrapper').hasClass('active'));
  })


  $('.ui.dropdown').dropdown();
  $('.ui.checkbox').checkbox();
  

  $('.ui.dropdown.admin-notifications').dropdown({
    action: 'hide',
    onChange: function(value, text, $choice)
    {
      if($choice.hasClass('all'))
        return;

      var payload = {0: $choice.data()};

      $.post('/admin/admin-notifs/mark_as_read', {items: payload})
      .done(function()
      {
        location.href = `/admin/${payload[0].table}#${payload[0].id}`;
      })
    }
  })

  $('video').hover(function()
  {
    $(this).prop('controls', true);
  }, function()
  {
    $(this).prop('controls', false);
  })

  $('.item.export').on('click', function()
  {
    $('.export.modal')
    .modal({
      centered: true,
      closable: false
    })
    .modal('show');
  })


  $('#report-errors').on('click', function()
  {
    let _this = $(this);
    
    _this.toggleClass('active loading', true);

    $.post('/admin/report_errors')
    .done(function(data)
    {
      alert(data.message)
    })
    .always(function()
    {
      _this.toggleClass('active loading', false);
    })
  })


  $('[vhidden]').removeAttr('vhidden')
})