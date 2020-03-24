jQuery(function($) {
  'use strict';

  var selectors = {
    apiUrlInput: '.ezdefi_api_url input',
    apiKeyInput: '.ezdefi_api_key input',
    publicKeyInput: '.ezdefi_public_key input'
  };

  var EDD_EZDefi_Admin = function() {
    this.$form = $('form');

    this.$form.find(selectors.apiKeyInput).attr('autocomplete', 'off');
    this.$form.find(selectors.apiUrlInput).attr('placeholder', 'https://merchant-api.ezdefi.com/api/');

    this.initValidation.call(this);

    var onChangeApiKey = this.onChangeApiKey.bind(this);
    var onChangePublicKey = this.onChangePublicKey.bind(this);

    $(document.body)
      .on('change', selectors.apiKeyInput, onChangeApiKey)
      .on('change', selectors.publicKeyInput, onChangePublicKey);
  };

  EDD_EZDefi_Admin.prototype.initValidation = function() {
    var self = this;

    this.$form.validate({
      ignore: [],
      errorElement: 'span',
      errorClass: 'error',
      errorPlacement: function(error, element) {
        error.appendTo(element.closest('td'));
      },
      highlight: function(element) {
        $(element)
          .closest('td')
          .addClass('form-invalid');
      },
      unhighlight: function(element) {
        $(element)
          .closest('td')
          .removeClass('form-invalid');
      },
      rules: {
        'edd_settings[ezdefi_api_url]': {
          required: true,
          url: true
        },
        'edd_settings[ezdefi_api_key]': {
          required: true
        },
        'edd_settings[ezdefi_public_key]': {
          required: true
        }
      }
    });
  };

  EDD_EZDefi_Admin.prototype.onChangeApiKey = function(e) {
    var self = this;
    var $input = $(e.target);
    $input.rules('add', {
      remote: {
        url: edd_ezdefi_data.ajax_url,
        type: 'POST',
        data: {
          action: 'edd_ezdefi_check_api_key',
          api_url: function() {
            return self.$form.find('.ezdefi_api_url input').val();
          },
          api_key: function() {
            return self.$form.find('.ezdefi_api_key input').val();
          }
        },
        complete: function(data) {
          var response = data.responseText;
          var $inputWrapper = self.$form.find('#edd_settings[ezdefi_api_key]').closest('td');
          if (response === 'true') {
            $inputWrapper.append('<span class="correct">Correct</span>');
            window.setTimeout(function() {
              $inputWrapper.find('.correct').remove();
            }, 1000);
          }
        }
      },
      messages: {
        remote: 'API Key is not correct. Please check again'
      }
    });
  };

  EDD_EZDefi_Admin.prototype.onChangePublicKey = function(e) {
    var self = this;
    var $input = $(e.target);
    $input.rules('add', {
      remote: {
        url: edd_ezdefi_data.ajax_url,
        type: 'POST',
        data: {
          action: 'edd_ezdefi_check_public_key',
          api_url: function() {
            return self.$form.find('.ezdefi_api_url input').val();
          },
          api_key: function() {
            return self.$form.find('.ezdefi_api_key input').val();
          },
          public_key: function() {
            return self.$form.find('.ezdefi_public_key input').val();
          }
        },
        complete: function(data) {
          var response = data.responseText;
          var $inputWrapper = self.$form.find('.ezdefi_public_key input').closest('td');
          if (response === 'true') {
            $inputWrapper.append('<span class="correct">Correct</span>');
            window.setTimeout(function() {
              $inputWrapper.find('.correct').remove();
            }, 1000);
          }
        }
      },
      messages: {
        remote: 'Website ID is not correct. Please check again'
      }
    });
  };

  new EDD_EZDefi_Admin();
});
