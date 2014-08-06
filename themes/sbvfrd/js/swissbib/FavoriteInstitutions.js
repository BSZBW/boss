swissbib.FavoriteInstitutions = {

  baseUrl: '/MyResearch/Favorites',

  /**
   * Values for autocomplete list (cached)
   */
  autocompleteValues: [],


  /**
   * Initialize favorite management
   *
   * @param  {Object|Boolean}  availableInstitutions    List of institutions of false of already cached
   */
  init: function (availableInstitutions) {
    this.baseUrl = window.path + this.baseUrl;

    // The institutions should already be cached
    if (availableInstitutions === false) {
      availableInstitutions = this.getInstitutionsFromStorage();
    } else {
      // New institutions downloaded, save them
      this.saveInstitutionsToStorage(availableInstitutions);
    }

    this.installAutocomplete(availableInstitutions);
    this.installHandlers();
  },


  /**
   * Install click handlers
   *
   */
  installHandlers: function () {
    var that = this;

    $('#favorites-table').find('.deleteFavoriteInstitution').click(function (event) {
      var institutionCode = $(this).data('institution');

      that.deleteInstitution(institutionCode);
    });
  },


  /**
   * Install autocompleter
   *
   * @param  {Object}  availableInstitutions
   */
  installAutocomplete: function (availableInstitutions) {
    $('#query').bind('typeahead:selected', $.proxy(this.onInstitutionSelect, this));
    $('#query').typeahead({
      hint: true,
      highlight: true,
      minLength: 1,
    }, {
      displayKey: 'label',
      source: swissbib.FavoriteInstitutions.substringMatcher(availableInstitutions),
    });
  },


  /**
   *
   * @param {Object}
   * @returns {Function} findMatches
   */
  substringMatcher: function (strs) {
    return function findMatches(q, cb) {
      var matches, substrRegex;

      // an array that will be populated with substring matches
      matches = [];

      // regex used to determine if a string contains the substring `q`
      substrRegex = new RegExp(q, 'i');

      // iterate through the pool of strings and for any string that
      // contains the substring `q`, add it to the `matches` array
      $.each(strs, function (i, str) {
        if (substrRegex.test(str)) {
          // the typeahead jQuery plugin expects suggestions to a
          // JavaScript object, refer to typeahead docs for more info
          matches.push({ label: str, value: i });
        }
      });

      cb(matches);
    };
  },


  /**
   * Find data for autocomplete
   * Call custom matcher
   *
   * @param  {Object}  request
   * @param  {Function}  response
   */
  autocompleteMatcher: function (request, response) {
    response(this.getMatchingItems(request.term));
  },


  /**
   * Customized matcher
   * Test label and value for match
   *
   * @param  {String}  term
   * @returns {Object[]}
   */
  getMatchingItems: function (term) {

    //remove the trailing blank
    if (term.charAt(term.length - 1) == " ") {
      term = term.substr(0, term.length - 1);
    }

    var splittedTerms = term.split(" ");
    var aRegexes = new Array();
    for (var x = 0; x < splittedTerms.length; x++) {
      var splittedTerm = splittedTerms[x];
      aRegexes.push(new RegExp(splittedTerm, "gi"));
    }


    var responseItems = new Array();

    for (var iItems = 0; iItems < this.autocompleteValues.length; iItems++) {

      var allTermsInLine = true;
      var tName = this.autocompleteValues[iItems].label;


      for (var xx = 0; xx < aRegexes.length; xx++) {
        if (!aRegexes[xx].test(tName)) {
          allTermsInLine = false;

          break;
        }
      }

      //return $.grep(this.autocompleteValues, function(value) {
      //    return matcher.test(value.label) || matcher.test(value.value);
      //});


      if (allTermsInLine) {
        for (var x = 0; x < splittedTerms.length; x++) {
          var splittedTerm = splittedTerms[x];
          tName = tName.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + splittedTerm.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
        }
        responseItems.push({label: tName, value: this.autocompleteValues[iItems].value});
      }
    }

    return responseItems;
  },


  /**
   * Handle institution selection
   *
   * @param  {Object}  event
   * @param  {Object}  ui
   */
  onInstitutionSelect: function (obj, datum, name) {
    this.clearSearchField();
    this.addInstitution(datum.value);
    console.log(datum.value);

    return false;
  },


  /**
   * Delete an institution and update list
   *
   * @param  {String}  institutionCode
   */
  deleteInstitution: function (institutionCode) {
    this.sendRequestOnUpdateList('delete', institutionCode);
  },


  /**
   * Add institution and update list
   *
   * @param  {String}  institutionCode
   */
  addInstitution: function (institutionCode) {
    this.sendRequestOnUpdateList('add', institutionCode);
  },


  /**
   * Send a request to the given action with the institution as parameter
   * Update list with response
   *
   * @param  {String}  action
   * @param  {String}  institutionCode
   */
  sendRequestOnUpdateList: function (action, institutionCode) {
    var that = this,
        url = this.baseUrl + '/' + action,
        data = {
          institution: institutionCode,
          list: true
        };

    $('#user-favorites').mask('Update...');

    $('#user-favorites').load(url, data, function () {
      that.installHandlers();
      $('#user-favorites').unmask();
    });
  },


  /**
   * Clear search field value
   *
   */
  clearSearchField: function () {
    $('#query').val('');
  },


  /**
   * Get institution list from local storage
   *
   * @returns {Object}
   */
  getInstitutionsFromStorage: function () {
    return $.jStorage.get('favorite-institutions');
  },


  /**
   * Add institution list to local storage
   *
   * @param  {Object}  institutions
   */
  saveInstitutionsToStorage: function (institutions) {
    $.jStorage.set('favorite-institutions', institutions);
  }

};