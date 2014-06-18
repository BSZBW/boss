/*global hierarchySettings, path, vufindString*/

var hierarchyID;
var baseTreeSearchFullURL;

function getRecord(recordID)
{
  $.ajax({
    url: path + '/Hierarchy/GetRecord?' + $.param({id: recordID}),
    dataType: 'html',
    success: function(response) {
      if (response) {
        $('#hierarchyRecord').html(html_entity_decode(response));
        // Remove the old path highlighting
        $('#hierarchyTree a').removeClass("jstree-highlight");
        // Add Current path highlighting
        var jsTreeNode = $(":input[value='"+recordID+"']").parent();
        jsTreeNode.children("a").addClass("jstree-highlight");
        jsTreeNode.parents("li").children("a").addClass("jstree-highlight");
      }
    }
  });
}

function hideFullHierarchy(jsTreeNode)
{
    // Hide all nodes
  $('#hierarchyTree li').hide();
  // Show the nodes on the current path
  $(jsTreeNode).show().parents().show();
  // Show the nodes below the current path
  $(jsTreeNode).find("li").show();
}

function toggleFullHierarchy(parentElement)
{
  // Toggle status
  $('#toggleTree').toggleClass("open");
  // Get the currently clicked item
  var jsTreeNode = $(".jstree-clicked").parent('li');
  // Toggle display of closed nodes
  $('#hierarchyTree li.jstree-closed').toggle();
  if ($('#toggleTree').hasClass("open")) {
    $('#hierarchyTree li').show();
    $("#hierarchyTree").jstree("show_dots");
    console.log(jsTreeNode);
    console.log(parentElement);
    console.log($(parentElement));
    $(parentElement).animate({
      scrollTop: $(jsTreeNode).offset().top - $(parentElement).offset().top + $(parentElement).scrollTop()
    });
    $('#toggleTree').html(vufindString.hierarchy_hide_tree);
  } else {
    hideFullHierarchy(jsTreeNode);
    $(parentElement).animate({
        scrollTop: -$(parentElement).scrollTop()
    });
    $("#hierarchyTree").jstree("hide_dots");
    $('#toggleTree').html(vufindString.hierarchy_show_tree);
  }
}

function changeNoResultLabel(display)
{
  if (display) {
    $("#treeSearchNoResults").show();
  } else {
    $("#treeSearchNoResults").hide();
  }
}

function changeLimitReachedLabel(display)
{
  if (display) {
    $("#treeSearchLimitReached").removeClass('hidden');
  } else {
    $("#treeSearchLimitReached").addClass('hidden');
  }
}

function doTreeSearch()
{
  $('#treeSearchLoadingImg').removeClass('hidden');
  var keyword = $("#treeSearchText").val();
  var type = $("#treeSearchType").val();
  if(type == 'Title') {
    $('#hierarchyTree').jstree(true).search(keyword, true);
    $('#treeSearchLoadingImg').addClass('hidden');
  } else {
    $('#hierarchyTree').jstree(true).search(keyword, false);
  }
}

$(document).ready(function()
{
  // Code for the search button
  $('#treeSearch input[type="submit"]').click(doTreeSearch);

  hierarchyID = $("#hierarchyTree").find(".hiddenHierarchyId")[0].value;
  var recordID = $("#hierarchyTree").find(".hiddenRecordId")[0].value;
  var parentElement = hierarchySettings.lightboxMode ? '#modal .modal-body' : '#hierarchyTree';
  var context = $("#hierarchyTree").find(".hiddenContext")[0].value;

  if (!hierarchySettings.fullHierarchy) {
    // Set Up Partial Hierarchy View Toggle
    $('#hierarchyTree').parent().prepend('<a href="#" id="toggleTree" class="">' + vufindString.hierarchy_show_tree + '</a>');
    $('#toggleTree').click(function(e)
    {
      e.preventDefault();
      toggleFullHierarchy(parentElement);
    });
  }

  $("#hierarchyTree")
    .bind("ready.jstree", function (event, data) {
      var jsTreeNode = $("#hierarchyTree").jstree('select_node', recordID.replace(':', '-'));
      jsTreeNode.parents('.jstree-closed').each(function () {
        data.inst.open_node(this);
      });

      $("#hierarchyTree a").click(function(e) {
        return false;
      });
      $("#hierarchyTree").bind('select_node.jstree', function(e, data) {
        if (context == "Record") {
          window.location.href = data.node.a_attr.href;
        } else {
          getRecord(data.node.id.replace('-', ':'));
        }
      })
      if (!hierarchySettings.fullHierarchy) {
        // Initial hide of nodes outside current path
        toggleFullHierarchy(parentElement);
      }

      // Scroll to the current record
      if (hierarchySettings.lightboxMode) {
        var scroller = '#modal .modal-body';
        var offsetTop = $(scroller).offset().top;
        $(scroller).animate({
          scrollTop: $('.jstree-clicked').offset().top - offsetTop + $(scroller).scrollTop() - 50
        }, 1500);
      }
    })
    .jstree({
      'core' : {
        'data' : function (obj, cb) {
          $.ajax({
            'url': path + '/Hierarchy/GetTree',
            'data': {
              'hierarchyID': hierarchyID,
              'id': recordID,
              'context': context,
              'mode': 'Tree'
            },
            'success': function(xml) {
              var nodes = buildJSONNodes($(xml).find('root'));
              cb.call(this, nodes);
            }
          })
        },
        "themes" : {
          "url": path + '/themes/bootstrap3/js/vendor/jsTree/themes/default/style.css'
        }
      },
      "plugins" : [ "search","types" ],
      "search": {
        'ajax': {
          "url" : path + '/Hierarchy/SearchTree?' + $.param({
            'hierarchyID': hierarchyID,
            'type': $("#treeSearchType").val()
          }),
          "success": function(results) {
            if (results["limitReached"] == true) {
              if(typeof(baseTreeSearchFullURL) == "undefined" || baseTreeSearchFullURL == null) {
                baseTreeSearchFullURL = $("#fullSearchLink").attr("href");
              }
              $("#fullSearchLink").attr("href", baseTreeSearchFullURL + "?lookfor="+ results['lookfor'] + "&filter[]=hierarchy_top_id:\"" + hierarchyID  + "\"");
              changeLimitReachedLabel(true);
            } else {
              changeLimitReachedLabel(false);
            }

            var jsonNode = [];
            $.each(results["results"], function(key, val) {
              jsonNode.push({
                'id': val.replace(':', '-')
              });
            });
            console.log(jsonNode);

            $('#treeSearchLoadingImg').addClass('hidden');
            return jsonNode;
          }
        },
        'fuzzy': false,
        'show_only_matches': true
      }
    });

  $('#treeSearch').removeClass('hidden');
  $('#treeSearchText').keyup(function (e) {
    var code = (e.keyCode ? e.keyCode : e.which);
    if(code == 13 || $(this).val().length == 0) {
      doTreeSearch();
    }
  });
});

function buildJSONNodes(xml)
{
  var jsonNode = [];
  $(xml).children('item').each(function() {
     var content = $(this).children('content');
     var id = content.children("name[class='JSTreeID']");
     var name = content.children('name[href]');
     jsonNode.push({
       'id': id.text().replace(':', '-'),
       'text': name.text(),
       'a_attr': {
         'href': name.attr('href')
       },
       children: buildJSONNodes(this)
     });
  });
  return jsonNode;
}

function html_entity_decode(string, quote_style)
{
  var hash_map = {},
    symbol = '',
    tmp_str = '',
    entity = '';
  tmp_str = string.toString();

  delete(hash_map['&']);
  hash_map['&'] = '&amp;';
  hash_map['>'] = '&gt;';
  hash_map['<'] = '&lt;';

  for (symbol in hash_map) {
    entity = hash_map[symbol];
    tmp_str = tmp_str.split(entity).join(symbol);
  }
  tmp_str = tmp_str.split('&#039;').join("'");

  return tmp_str;
}

