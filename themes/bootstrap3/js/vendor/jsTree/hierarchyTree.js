/*global hierarchySettings, html_entity_decode, jqEscape, path, vufindString*/

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

$(document).ready(function()
{
  $('#treeSearch input[type="submit"]').click(doTreeSearch);

  // Code for the search button
  hierarchyID = $("#hierarchyTree").find(".hiddenHierarchyId")[0].value;
  var recordID = $("#hierarchyTree").find(".hiddenRecordId")[0].value;
  var parentElement = hierarchySettings.lightboxMode ? '#modal .modal-body' : '#hierarchyTree';
  var context = $("#hierarchyTree").find(".hiddenContext")[0].value;

  $("#hierarchyTree")
    .bind("ready.jstree", function (event, data) {
      var jsTreeNode = $("#hierarchyTree").jstree('select_node', recordID.replace(':', '-'));
      jsTreeNode.parents('.jstree-closed').each(function () {
        data.inst.open_node(this);
      });
      if (context == "Collection") {
        getRecord(recordID.replace('-', ':'));
      }

      $("#hierarchyTree").bind('select_node.jstree', function(e, data) {
        if (context == "Record") {
          window.location.href = data.node.a_attr.href;
        } else {
          getRecord(data.node.id.replace('-', ':'));
        }
      });

      // Scroll to the current record
      if (hierarchySettings.lightboxMode) {
        var offsetTop = $(parentElement).offset().top;
        $(parentElement).animate({
          scrollTop: $('.jstree-clicked').offset().top - offsetTop + $(parentElement).scrollTop() - 50
        }, 1500);
      }
    })
    .jstree({
      'plugins' : [ 'search','types' ],
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
      "search": {
        'ajax': {
          "url" : path + '/Hierarchy/SearchTree?' + $.param({
            'hierarchyID': hierarchyID,
            'type': $("#treeSearchType").val()
          }) + "&format=true",
          'success': function(e) {
            alert('!');
            return [];
          }
        },
        'fuzzy': false,
        'show_only_matches': false
      }
    });

  $('#treeSearch').removeClass('hidden');
  $('#treeSearchText').keyup(function (e) {
    var code = (e.keyCode ? e.keyCode : e.which);
    if(code == 13 && $(this).val().length > 0) {
      doTreeSearch();
    } else if($(this).val().length == 0) {
      $('#hierarchyTree').jstree(true).search('', true);
    }
  });
});
