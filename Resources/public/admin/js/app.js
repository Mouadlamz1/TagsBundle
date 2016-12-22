$.noConflict();
jQuery(document).ready(function($) {
    /**
     * This method creates jsTree object for each DIV element with appropriate ID prefix.
     */
    var tagsTreeContainers = $('div.tags-tree');

    $.each(tagsTreeContainers, function(index, value) {
        $(value).jstree({
            'plugins': ["sort", "contextmenu", "ui"],
            'contextmenu': {
                'select_node': false,
                'items': $(value).parents('div.modal-tree').length ? '' : tagTreeContextMenu
            },
            'core': {
                'data': {
                    'url': function (node) {
                        var route = $(value).data('path');
                        var rootTagId = $(value).data('roottagid');

                        return route
                            .replace("_tagId_", node.id)
                            .replace("#", rootTagId + "/true")
                            ;
                    }
                },
                'themes': {
                    'name': 'ng-tags'
                }
            }
        }).bind("ready.jstree", function (event, data) {
            if ($(value).data('selectedtagid') !== undefined) {
                if ($(value).data('selectedtagid') === 0) {
                    $(value).jstree(true).select_node(0);
                }

                else {
                    $.getJSON('/tags/admin/tree/parents/' + $(value).data('selectedtagid'), {}, function (data) {
                        $(value).jstree(true).load_node(data, function () {
                            this.select_node($(value).data('selectedtagid'));
                        });
                    });
                }
            }
        });
    });

    /**
     * Builds context menu for right click on a tag in tags tree.
     *
     * @param node
     * */
    function tagTreeContextMenu(node) {
        var actions = ['add_child'],
            menu = {};
        if(node.parent != '#') {
            actions = actions.concat(['update_tag', 'delete_tag', 'merge_tag', 'add_synonym']);
        }
        actions.forEach(function(action){
            menu[action] = {
                "label": node.data[action].text,
                "action": function () {
                    window.location.href = node.data[action].url;
                }
            };
        });
        return menu;
    }

    /**
     * This method is called when user clicks on a node in tree.
     * If it's a main tree, it redirects user to a route provided in selected node.
     * Else, it puts selected node's ID in a form field with provided ID.
     * And also it puts selected node's text in a span field with provided ID:
     */
    tagsTreeContainers.on('click', '.jstree-anchor', function (event) {
        var selectedNode = $(this).jstree(true).get_node($(this)),
            modalTreeDiv = $(event.target).parents('div.modal-tree');

        if (!modalTreeDiv.length) {
            document.location.href = selectedNode.a_attr.href;
        } else {
            $(modalTreeDiv).children('input[type=hidden]').val(selectedNode.id);

            if (selectedNode.text === undefined || selectedNode.id == '0') {
                $(modalTreeDiv).children('span.tag-keyword').html($(modalTreeDiv).data('novaluetext'));
            } else {
                $(modalTreeDiv).children('span.tag-keyword').html(selectedNode.text);
            }

            $(modalTreeDiv).children('div.modal').hide();
        }
    });

    /**
     * Opens modal when modal open button is clicked.
     */
    $('.modal-tree-button').on('click', function(){
        $(this).parent('div.modal-tree').children('div.modal').show();
    });

    /**
     * It closes modal when Close span inside modal is clicked.
     */
    $('.modal').on('click', '.close', function(){
        $(this).parents('div.modal').hide();
    });

    /**
     * It closes modal when user clicks anywhere outside modal window.
     */
    $(window).on('click', function(e) {
        if(e.target.className == 'modal') {
            $(e.target).hide();
        }
    });

    /* button click effect */
    function TagsBtn(el){
        this.$el = $(el);
        this.$effect = $('<span class="tags-btn-effect">');
        this.init();
    }
    TagsBtn.prototype.init = function(){
        this.setupEvents();
    };
    TagsBtn.prototype.setupEvents = function(){
        this.$el.on('mousedown', function(e){
            this.$effect.detach();
            this.addEffect(e);
        }.bind(this));
        this.$effect.on('animationend', function(){
            $(this).detach();
        });
    };
    TagsBtn.prototype.addEffect = function(e){
        this.$effect.css(this.calcPos(e));
        this.$el.append(this.$effect);
    };
    TagsBtn.prototype.calcPos = function(e){
        var btnOffset = this.$el.offset(),
            elWidth = this.$el.outerWidth(),
            rel = {
                x: e.pageX - btnOffset.left,
                y: e.pageY - btnOffset.top
            },
            effectWidth = rel.x <= (elWidth/2) ? (elWidth - rel.x) * 2.4 : rel.x * 2.4;
        this.effectCss = {
            'left': rel.x,
            'top': rel.y,
            'width': effectWidth,
            'height': effectWidth
        };
        return this.effectCss;
    };
    $.fn.tagsBtn = function () {
        return $(this).each(function(){
            var $this = $(this);
            if ($this.data('tagsbtn')){
                return;
            }
            var instance = new TagsBtn(this);
            $this.data('tagsbtn', instance);
        });
    };

    $('.tags-btn').tagsBtn();

    /* tabs */
    $.fn.tagsTabs = function(){
        return this.each(function(){
            if(!$(this).data('tagsTabs')){
                var $el = $(this),
                    controls = $el.find('.tags-tab-control'),
                    toggleActive = function(el){
                        el.addClass('active').siblings().removeClass('active');
                    };
                $(this).data('tagsTabs', 'true');
                $(this).on('click', '.tags-tab-control', function(e){
                    var target = this.getAttribute('href');
                    toggleActive($(this).parents('li'));
                    toggleActive($('[data-tab="' + target + '"]'));
                    localStorage.tagsTabActive = target;
                });
                if(localStorage.tagsTabActive){
                    console.log(localStorage.tagsTabActive);
                    controls.each(function(i, control){
                        if (control.getAttribute('href') == localStorage.tagsTabActive){
                            $(control).click();
                        }
                    });
                } else {
                    $(controls[0]).click();
                }
            }
        });
    };

    $('.tags-tabs').tagsTabs();
});
