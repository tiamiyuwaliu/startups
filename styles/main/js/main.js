"use strict";
(function ($) { $.fn.datepicker.language['en'] = {
    days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
    months: ['January','February','March','April','May','June', 'July','August','September','October','November','December'],
    monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    today: 'Today',
    clear: 'Clear',
    dateFormat: 'mm/dd/yyyy',
    timeFormat: 'hh:ii aa',
    firstDay: 0
}; })(jQuery);
function confirm(url, mess, ajax, functionName) {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-primary shadow-none',
            cancelButton: 'btn btn-light shadow-none'
        },
        buttonsStyling: false
    })

    swalWithBootstrapButtons.fire({
        title: '',
        text: (mess === undefined || mess === '') ? strings.are_your_sure : mess,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: strings.yes,
        cancelButtonText: strings.cancel,
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            if (ajax === undefined || !ajax) {
                window.location.href = url;
            } else {
                if (ajax === 'function') {

                    functionName.call();
                } else {
                    ajaxAction(url);
                }
            }
        } else if (
            /* Read more about handling dismissals below */
            result.dismiss === Swal.DismissReason.cancel
        ) {

        }
    })

    return false;
}


function notify(m, type) {
    if (m === ' ' || m === '') return false;
    var notyf = new Notyf({
        types: [
            {
                type: 'warning',
                background: 'orange',
                icon: {
                    className: 'material-icons',
                    tagName: 'i',
                    text: 'warning'
                }
            },
            {
                type: 'info',
                background: '#1773CC',
            }
        ]
    });
    if (type === 'error') {
        notyf.error({
            message: m,
            position: {
                x: 'right',
                y: 'top',
            },
        })
    } else if(type === 'success') {
        notyf.success({
            message: m,
            position: {
                x: 'center',
                y: 'top',
            },
        });
    } else {
        notyf.open({
            type: 'info',
            message: m
        });
    }
}

function validate_fileupload(fileName, type)
{
    var allowed_extensions = new Array("jpg","png","gif");
    allowed_extensions = supportImagesType.split(',');
    if (type == 'video') allowed_extensions = supportVideoType.split(',');
    if (type == 'image-video') allowed_extensions.push(supportVideoType.split(','));
    var file_extension = fileName.split('.').pop().toLowerCase(); // split function will split the filename by dot(.), and pop function will pop the last element from the array which will give you the extension as well. If there will be no extension then it will return the filename.
    for(var i = 0; i <= allowed_extensions.length; i++)
    {
        if(allowed_extensions[i]==file_extension)
        {
            return true; // valid file extension
        }
    }

    return false;
}

function validate_file_size(input, type, func) {
    var files = input.files;


    for (var ii = 0; ii < files.length; ii++) {
        var file = files[ii];

        if (type == 'image') {
            if (!validate_fileupload(file.name, 'image')) {
                notify(strings.notImageError, 'error')
                $(input).val('');//yes
                return true;
            }

            if (file.size > allowPhotoSize) {
                //this file is more than allow photo file
                notify(strings.allowImageSizeError, 'error');
                //empty the input
                $(input).val('');//yes
                return true;
            }

        } else if(type == 'image-video') {
            if (!validate_fileupload(file.name, 'image-video')) {
                notify(strings.notImageVideoError, 'error')
                $(input).val('');//yes
                return true;
            }

            if (file.size > allowFileSize) {
                //this file is more than allow photo file
                notify(strings.allowFileSizeError, 'error');
                //empty the input
                $(input).val('');//yes

                return true;
            }

        }
    }

    if(func !== undefined) {
        eval(func)();
    }
}
function sanitize(string) {
    try {
        return string.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;')
    } catch (e){
        return string;
    }
}
function errorHandler(event) {
    console.log(event);
}
function abortHandler(event) {
    console.log(event);
}

// Animation on Scroll
function initAnimation() {
    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

}

function submit_file_upload() {
    var id = getPossibleFolderId();
    $("#file-folder-id-input").val(id);
    $(".filemanager-uploader").each(function() {
        if($(this).find('input[type=file]').val() !== '') {
            $(this).submit();
        }
    })

}

function pageLoader(full) {
    $('#top-loading').show().animate({width:20 + 80  + "%"}, 200);
    if (full != undefined) $("#cover-loader").fadeIn();
}
function pageLoaded() {
    $('#top-loading').animate({width:"100%"}, 200).fadeOut(300, function() {
        $(this).width("0");
    });
    $("#cover-loader").fadeOut();
}

function buildLink(link, param) {
    var result = baseUrl;
    var append = "";
    if (param !== undefined) {
        for(var i=0;i<param.length;i++) {
            append += (append.length > 0) ? '&'+param[i].key+'='+param[i].value : param[i].key+'='+param[i].value;
        }
    }
    if (permaLink === 1) {
        result += link;
        if(param !== undefined) {
            result += '?'+append;
        }
    } else {
        result += '?p='+link;
        if(param !== undefined) {
            result += '&'+ append;
        }
    }
    return result;
}

window.previousUrl = [];
window.previousUrl.push(window.location.href);
function load_page(url, f, cont) {
    window.onpopstate = function(e) {
        window.previousUrl.pop();
        var url = window.previousUrl[window.previousUrl.length - 1];
        if (url == undefined) url = window.previousUrl[window.previousUrl.length - 1];
        load_page(url, true);

    };

    pageLoader();
    $.ajax({
        url: url,
        cache: false,
        type: 'GET',
        success: function(data) {

            if(data === 'login') {
                pageLoaded();
            } else {
                $(".modern-scroll").each(function() {
                    $(this).getNiceScroll().remove()
                });
                try {
                    data = jQuery.parseJSON(data);
                    if (data.type !== undefined) {
                        if (data.type == 'error') {
                            notify(data.message, data.type);
                            pageLoaded();
                            return false;
                        }
                    }
                    var content = data.content;
                    var container = (cont == undefined) ? data.container : cont;
                    var title = data.title;

                    $(container).html(content);
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();

                } catch(e) {
                }
                document.title = title;
                if (f == undefined) window.previousUrl.push(url);
                window.history.pushState({}, data.title + ':' + url, url);
                $(window).scrollTop(0);
                ///hideMenu();
                reloadInit();
                if ($('.post-edit-content').length > 0) {

                }
                pageLoaded();
                window.runPreviewAuto = true;
                window.openFolders = [];
                window.lastOpenFolder=null;
                //$("#help-modal").modal('hide');
                //automationPageInit();
                $('body').click();


            }

        },
        error: function() {
            pageLoaded();

        }
    });
    return false;
}

function scrollToBottom(container) {
    container.animate({scrollTop : container.prop("scrollHeight")}, 200)
}



window.captchIsLoaded = false;
window.runPreviewAuto = true;
function reloadInit(paginate) {
    initAnimation();
    $('.static-dropdown-menu').on("click.bs.dropdown", function (e) {
        e.stopPropagation();
        //e.preventDefault();
    });
    $('.trumbowyg-editor').each(function() {
        tinymce.init({
            selector: '#'+$(this).attr('id'),
            plugins: 'a11ychecker advcode casechange export formatpainter linkchecker autolink lists checklist media mediaembed pageembed permanentpen powerpaste table advtable tinycomments tinymcespellchecker',
            toolbar: 'a11ycheck addcomment showcomments casechange checklist code export formatpainter pageembed permanentpen table',
            toolbar_mode: 'floating',
            tinycomments_mode: 'embedded',
            tinycomments_author: 'Author name',
        });
    })
    /**nolt('init', {
        url: 'https://timably.nolt.io',
        selector: '.feedback-btn'
    });

    nolt('identify', {
        jwt: jwtAuth
    });**/
    $('.carousel').carousel()
    $('.digit-group').find('input').each(function() {
        $(this).attr('maxlength', 1);
        $(this).on('keyup', function(e) {
            var parent = $($(this).parent());

            if(e.keyCode === 8 || e.keyCode === 37) {
                var prev = parent.find('input#' + $(this).data('previous'));

                if(prev.length) {
                    $(prev).select();
                }
            } else if((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 65 && e.keyCode <= 90) || (e.keyCode >= 96 && e.keyCode <= 105) || e.keyCode === 39) {
                var next = parent.find('input#' + $(this).data('next'));

                if(next.length) {
                    $(next).select();
                }
            }
        });
    });

    var welcome = getCookie('welcome-completed');

    if (welcome === '') {
        $('#newWelcomeModal').modal({
            keyboard: false,
            show:true,
            backdrop:'static'
        })
    }
    if ($('#calendar').length > 0) {
        var calendarEl = document.getElementById('calendar');
        var calendarMedias = document.getElementById('side-filemanager');

        $('.each-file-drag').draggable({
            containment: 'document',
            appendTo: 'body',
            cursorAt: { left: 5 , top: 5},
            helper: function(obj) {
                var div = $("<div class='dragged-media'></div>");
                if ($(obj.currentTarget).data('type') === 'image') {
                    div.css('background-image', 'url('+$(obj.currentTarget).data('file')+')')
                } else {
                    div.append('<div class="video-content"><video src="'+$(obj.currentTarget).data('file')+'" ></video></div>')
                }
                var count = $('#side-filemanager .selected').length;
                if (count > 0) {
                    count -= 1;
                    div.append('<span class="count">'+count+'</span>');
                }
                return div;
            }
        });
        new FullCalendar.ThirdPartyDraggable(calendarMedias, {
            itemSelector: '.fc-event',
            eventData: function(eventEl) {
                return {
                    title: '',
                    create: false
                };
            }
        });

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',

            headerToolbar: { center: 'timeGridWeek,dayGridMonth' },
            droppable: true,
            editable: true,
            eventDisplay: 'block',
            eventDidMount: function(info) {

                if (info.event._def.extendedProps.image !== undefined) {

                    var div = $('<div class="fc-post '+info.event._def.extendedProps.bgcolor+'"></div>');

                    div.append('<div class="img" style="background-image:url('+info.event._def.extendedProps.image+')"></div>')
                    div.append("<div class='time'>"+info.event._def.extendedProps.time+" "+info.event._def.extendedProps.social_icon+"</div>")
                    div.append("<div class='status mtitle "+info.event._def.extendedProps.status_color+"' title='"+info.event._def.extendedProps.status_title+"'></div>")
                    $(info.el).find('.fc-event-main-frame .fc-event-title').html(div)
                    if (info.event._def.extendedProps.bgcolorValue !== undefined) {
                        div.css('background', info.event._def.extendedProps.bgcolorValue+'')
                        div.css('background-color', info.event._def.extendedProps.bgcolorValue+'')
                        div.css('border', 'solid 2px '+info.event._def.extendedProps.bgcolorValue+'')
                    }
                    tippy('.mtitle',{
                        animation: 'shift-toward',
                        arrow: true
                    });
                }
                },
            eventClick: function(info) {
                PostEditor.openPostDetail(info.event._def.extendedProps);
                return false;
            },
            events: {
                url: $('#calendar').data('url'),
                extraParams: function() {
                    return {
                        cachebuster: new Date().valueOf()
                    };
                },
                color: 'yellow',   // a non-ajax option
                textColor: 'black'
            },
            eventDrop: function(info) {
                var date = calendar.formatDate(info.event.start,{
                    month: '2-digit',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                $.ajax({
                    url: buildLink('publishing/posts'),
                    data:{action: 'change-date', id: info.event._def.extendedProps.postId, date: date}
                })
            },
            drop: function(info) {
                var date = new Date(info.date);
                //console.log(date);
                //var datepicker = $("#post-editor-schedule-time-input").datepicker().data('datepicker');
                var fp = document.querySelector("#post-editor-schedule-time-input")._flatpickr;

                fp.setDate(date);
                var selected = [];
                if ($('#side-filemanager .selected').length > 0) {
                   $('.selected').each(function() {
                       if ($(this).data('raw') !== undefined) selected.push($(this).data('raw'))
                   });
                } else {
                    var obj = $(info.draggedEl);
                    selected.push(obj.data('raw'))
                }
               // console.log(selected)
                $('.selected-media-input').val(selected.join(','))
                PostEditor.updateMediaPreview();
                setTimeout(function() {
                    PostEditor.openComposer();
                }, 50)
            }
        });
        calendar.render();
    }
    /****/
    $('.content-scroller').each(function() {
        var h = ($(this).data('height') !== undefined) ? $(this).data('height') : 0;
        h += $('.content-top').height();
        //h += 50;
        $(this).css('height', 'calc(100vh - '+h+'px)');
    });



    $('.scroll-paginate').scroll(function () {
        if(($(this).find('.the-content').height() - $(this).scrollTop()) - 10 <= $(this).height()) {
            var mainContainer = $(this);
            if (mainContainer.data('finished') !== undefined || (mainContainer.data('laoding') !== undefined && mainContainer.data('laoding'))) return false;
            var contentContainer = $(mainContainer.data('container'))
            mainContainer.data('laoding', true);
            pageLoader();
            $.ajax({
                url: $(this).data('url'),
                data:{offset: $(this).data('offset'), paginate: true},
                type: 'POST',
                success: function(result) {
                    var json = jQuery.parseJSON(result);
                    mainContainer.data('offset', json.offset);
                    mainContainer.attr('data-offset', json.offset);
                    contentContainer.append(json.content);
                    //console.log(json.content)
                    mainContainer.data('laoding', false);

                    if (json.content === '') {
                        mainContainer.data('finished', true);
                        mainContainer.attr('data-finished', true);
                    }
                    setTimeout(function() {
                        reloadInit()
                    }, 100);
                    pageLoaded();
                }
            })
        }
    })


    try{

        $(".modern-scroll").each(function() {
            try {
                $(this).getNiceScroll().resize();
            } catch (e){

            }
        });
    } catch (e){}
    setTimeout(function() {
        $(".modern-scroll").each(function() {
            $(this).niceScroll({
                autohidemode: 'leave',
                cursorcolor: "#7A2DE0",
                horizrailenabled: false
            });

            if ($(this).data('paginate') !== undefined) {
                $(this).bind('scroll', function() {
                    var thisScroll = $(this).getNiceScroll()[0];
                    if (thisScroll.scrollvaluemax === thisScroll.scroll.y) {
                        var mainContainer = $(this);

                        if (mainContainer.data('finished') !== undefined || (mainContainer.data('laoding') !== undefined && mainContainer.data('laoding'))) return false;
                        var contentContainer = $(mainContainer.data('container'))
                        mainContainer.data('laoding', true);
                        pageLoader();
                        $.ajax({
                            url: $(this).data('url'),
                            data:{offset: $(this).data('offset'), paginate: true},
                            type: 'POST',
                            success: function(result) {
                                var json = jQuery.parseJSON(result);
                                mainContainer.data('offset', json.offset);
                                mainContainer.attr('data-offset', json.offset);
                                contentContainer.append(json.content);
                                //console.log(json.content);
                                mainContainer.data('laoding', false);

                                if (json.content === '') {
                                    mainContainer.data('finished', true);
                                    mainContainer.attr('data-finished', true);
                                }
                                reloadInit()
                                pageLoaded();
                                mainContainer.data('laoding',false)
                            }
                        })
                    }
                })
            }
        })
    }, 300)


    if ($('.g-recaptcha-pane').length > 0) {
        window.loadCaptchaInterval = setInterval(function() {
            if (window.captchIsLoaded) {
                grecaptcha.render('g-recaptcha-pane', {
                    'sitekey' : $('.g-recaptcha-pane').data('sitekey')
                });
                clearInterval(window.loadCaptchaInterval)
            }
        }, 300)
    }
    try {
        AOS.init({
            easing: 'ease-in-out-sine',
            duration: 1000
        });
    } catch (e){}

    $('.chartjs').each(function() {
        new Chart(document.getElementById($(this).attr('id')), $(this).data('content'));
    });

    $('.chartjs-dou').each(function() {
        new Chart(document.getElementById($(this).attr('id')), $(this).data('content'));
    });

    $('.datepicker-here').each(function() {
        if ($(this).data('datepicker') !== undefined) return false;
        $(this).datepicker({
            inline: true,
            language: 'en',
            dateFormat: dateFormat,
            timeFormat: 'hh:i'
        })

    });


    $('input.timepicker').timepicker({dropdown: true,});

    $('.datepicker-input').each(function() {


        //if ($(this).data('datepicker') !== undefined) return false;
        var start = ($(this).val() !== '') ? stringToDate($(this).val(), 'm/d/Y') : new Date();
        $(this).flatpickr({
            dateFormat: "m/d/Y",
            efaultDate: start,
        });
        return false;
        var picker = $(this).datepicker({
            language: 'en',
            'position': 'top center',
            startDate: start,
            dateFormat: dateFormat
        }).data('datepicker');
        picker.selectDate(start);
    })

    $('.datepicker-input-time').each(function(){
        var start = ($(this).val() !== '') ? stringToDate($(this).val(), 'm/d/Y H:i') : new Date();
        console.log(start);
        $(this).flatpickr({
            enableTime: true,
            defaultDate: start,
            position:'above',
            dateFormat: "m/d/Y H:i",
        });
    })
    $('.input-timepicker').each(function(){
        $(this).flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            //time_24hr: true
        });
    })

    $('.datepicker-input-timeds').each(function() {

        if ($(this).data('datepicker') === undefined) {
            var start = ($(this).val() !== '') ? stringToDate($(this).val(), dateFormat) : new Date();
            var obj = $(this)
            var picker = $(this).datepicker({
                language: 'en',
                position: 'top center',
                startDate: start,
                timepicker: true,
                dateFormat: dateFormat,
                timeFormat: 'hh:ii AA',
                onHide: function() {
                    obj.trigger('change');
                }
            }).data('datepicker');
            picker.selectDate(start);
        }

    });

    $('.emoji-text').each(function() {
       if ($(this).prop('nodeName').toLowerCase() === 'textarea' && $(this).css('display') !== 'none') {
           var obj = $(this);
           if ($(this).css('display') !== 'none') {
               var el = $(this).emojioneArea({
                   pickerPosition: 'bottom',
                   emojiPlaceholder: ":smile_cat:",
                   attributes: {
                       spellcheck: true
                   }
               });
               el[0].emojioneArea.on("keyup", function(editor, event) {
                   ////refereshPreview();
                   if (obj.data('counter') !== undefined) {

                       $(obj.data('counter')).html(el[0].emojioneArea.getText().length)
                   }
                   if (obj.data('keyup')) eval(obj.data('keyup'))(obj, el[0].emojioneArea.getText())
               });
               el[0].emojioneArea.on("change", function(editor, event) {
                   ///refereshPreview()
                   if (obj.data('counter') !== undefined) {

                       $(obj.data('counter')).html(el[0].emojioneArea.getText().length)
                   }
                   if (obj.data('keyup')) eval(obj.data('keyup'))(obj,el[0].emojioneArea.getText())
               });
           }
       }
    });

    $('.text-editor-placeholder .holder').focus(function() {
        var obj = $(this);
        var parent = obj.parent();
        if (obj.css('display') === 'none') return false;
        obj.hide();
        parent.find('textarea').addClass('emoji-text');
        parent.find('textarea').show();
        reloadInit();
        var el = parent.find('textarea').emojioneArea();
        el[0].emojioneArea.setFocus();
        return false;
    })

    if ($('#text-editor').length > 0) $('#text-editor').trumbowyg();

    $(document).on('click', '.menu-toggle-left', function(){
        $(this).parent().find(".menu-container .menu").animate({scrollLeft: "-=100px"});
        return false;
    });

    $(document).on('click', '.menu-toggle-right', function(){
        $(this).parent().find(".menu-container .menu").animate({scrollLeft: "+=100px"});
        return false;
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        // newly activated tab
        if ($(e.delegateTarget).hasClass('menu-item')) {
            $('#horizontal-menu .menu-item').removeClass('active')
            $(e.delegateTarget).addClass('active') // previous active tab
        }

    })


    tippy('[data-toggle="tooltip"]',{
        animation: 'shift-toward',
        arrow: true
    });

    tippy('.mtitle',{
        animation: 'shift-toward',
        arrow: true
    });

    tippy('.mtitle-right',{
        animation: 'shift-toward',
        placement: 'right',
        arrow: true
    });

    tippy('.mtitle-left',{
        animation: 'shift-toward',
        placement: 'left',
        arrow: true,
        theme : 'light',
    });

    tippy('.mtitle-light',{
        animation: 'shift-toward',
        arrow: true,
        interactive : true,
        theme : 'light',
        allowTitleHTML: true,
    });

    var settings = {
        "async": true,
        "crossDomain": true,
        "url": "https://api.ip.sb/geoip",
        "dataType": "jsonp",
        "method": "GET",
        "headers": {
            "Access-Control-Allow-Origin": "*"
        }
    }

    if ($(".select-timezone").length > 0) {
        $.ajax(settings).done(function (response) {
            var timezone = response.timezone;
            var selected = $(".select-timezone").val();
            if (selected == '') {
                $(".select-timezone").val(timezone);
            }
        });
    }

    $('.input-tags').each(function() {
        var data = "search/tags";
        var options = ['remove_button'];
        try {
            $(this)[0].selectize.destroy();
        }catch(e){}
        window.globalSelect = $(this).selectize({
            plugins: options,
            delimiter: ',',
            persist: false,
            preload: true,
            closeAfterSelect: true,
            create: function(input) {
                return {
                    value: input,
                    text: input
                }
            }
        });
    });



    $('.input-tags-fetch').each(function() {
        var data = $(this).data('url');
        var options = ['remove_button'];
        try {
            $(this)[0].selectize.destroy();
        }catch(e){}
        $(this).selectize({
            plugins: options,
            delimiter: ',',
            persist: false,
            options: [],
            preload: true,
            valueField: 'value',
            labelField: 'text',
            closeAfterSelect: true,
            load: function(query, callback) {
                if (!query.length) return callback();
                $.ajax({
                    url: data,
                    type: 'GET',
                    data : {key: encodeURIComponent(query)},
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        res = jQuery.parseJSON(res);
                        callback(res);
                    }
                });
            }
        });
    });


    if($('.captcha-container').length > 0) {

    }

    $('.color').each(function() {
        var obj = $(this);
        $(this).spectrum({
            showInitial: true,
            showInput: true,
            showPalette: true,
            showSelectionPalette: true, // true by default
            palette: [ ],
            preferredFormat: "hex",
            move: function(colorValue) {
                var color = colorValue.toHexString(); // #ff0000
                if (obj.data('target-bg') !== undefined) {
                    $(obj.data('target-bg')).each(function() {
                        this.style.setProperty( 'background-color', color, 'important');
                    })
                }
                if (obj.data('target-color') !== undefined) {
                    $(obj.data('target-color')).each(function() {
                        this.style.setProperty('color', color, 'important');
                    })
                }

                if (obj.data('target-border') !== undefined) {
                    $(obj.data('target-border')).each(function() {
                        this.style.setProperty('border-color', color, 'important');
                    })
                }

                if (obj.data('result') !== undefined) {
                    var func = obj.data('result');
                    eval(func)(obj, colorValue);
                }

            },
            change: function(colorValue) {
                var color = colorValue.toHexString(); // #ff0000
                if (obj.data('target-bg') !== undefined) {
                    $(obj.data('target-bg')).each(function() {
                        this.style.setProperty( 'background-color', color, 'important');
                    })
                }
                if (obj.data('target-color') !== undefined) {
                    $(obj.data('target-color')).each(function() {
                        this.style.setProperty('color', color, 'important');
                    })
                }

                if (obj.data('target-border') !== undefined) {
                    $(obj.data('target-border')).each(function() {
                        this.style.setProperty('border-color', color, 'important');
                    })
                }

                if (obj.data('target-gradient') !== undefined) {

                    var fColor = $(obj.data('container')).find('.first').val();
                    var sColor = $(obj.data('container')).find('.second').val();
                    $(obj.data('target-gradient')).each(function() {

                        this.style.setProperty('background', 'linear-gradient(45deg, '+fColor+' 0%,  '+fColor+' 40%,  '+sColor+' 60%,  '+sColor+' 100%) ', 'important');
                    })
                }

                if (obj.data('result') !== undefined) {
                    var func = obj.data('result');
                    eval(func)(obj, colorValue)
                }

            }
        });
    })

    $('.party-posts-list').sortable({
        items: '.each',
        scrollSensitivity: 50,
        scrollSpeed: 40,
        cancel: ':input,button,[contenteditable]',
    })
    if ($('#side-filemanager').length < 1) {
        $( ".files-container" ).sortable({
            items: '.sort',
            placeholder: "ui-state-highlight",
            scrollSensitivity: 50,
            scrollSpeed: 40,
            appendTo: 'body',
            zIndex: 999999999,
            cursorAt: { left: 5 , top: 5},
            //containment: "parent",
            helper: function(event, ui) {
                var newUi = $(ui).clone();
                $(newUi).css('width', "40px");
                $(newUi).css('height', "40px");
                $(newUi).css('z-index', "999999999999");
                $(newUi).find('.each').css('height', '40px')
                $(newUi).find('.the-content').remove();
                return newUi;
            },
            start : function(event, ui) {

            },

            stop: function() {
                if ($('.sortable-item').length > 0) {
                    Filemanager.adjustSorting()
                } else {
                    var obj = $(this);
                    setTimeout(function() {
                        if (obj.length > 0){
                            $( ".files-container" ).sortable( "cancel" );
                            notify(strings.sorting_error, 'error')
                        }
                    }, 100)
                }
            }

        });
        $(".sortable-item").bind('sortstart', function(event, ui) {

            $('.ui-state-highlight').html('<div></div>');
        });
    }

    $('.each-collection').droppable({
        over: function( event, ui ) {
            $(ui.helper).css('width', "40px");
            $(ui.helper).css('height', "40px");
            $(ui.helper).find('.the-content').remove();
            $(ui.helper).find('.dropdown').remove();
            $(this).addClass('folder-overed');
        },
        out: function(event, ui) {
            $(this).removeClass('folder-overed');
        },
        drop: function( event, ui ) {
            $(this).removeClass('folder-overed');
            //return false;
            var folderId = $(this).data('id');
            if ($('#templates').length > 0) {
                $.ajax({
                    url : buildLink('parties',[{key: 'action', value: 'move'},{key: 'id', value: $(ui.draggable).data('id')}, {key: 'folder', value: $(this).data('id')}])
                })
            } else {
                $.ajax({
                    url : buildLink('files',[{key: 'action', value: 'move'},{key: 'file', value: $(ui.draggable).data('id')}, {key: 'folder', value: $(this).data('id')}]),
                    success: function(c) {
                        $('.each-collection-'+folderId).find('.count').html(c);
                    }
                })
            }
            if ($('.sortable-item').length > 0) $(ui.draggable).remove();
        }
    });
}


function stringToDate(_date,_format)
{

    /**var _delimiter = '.';
    if (_date.match('/'))  {
        _delimiter = '/';
    } else if(_date.match('-')) {
        _delimiter = '-';
    }
    var formatLowerCase=_format.toLowerCase();
    var formatItems=formatLowerCase.split(_delimiter);
    var dateItems=_date.split(_delimiter);

    var monthIndex=formatItems.indexOf("mm");

    var dayIndex=formatItems.indexOf("dd");
    var yearIndex=formatItems.indexOf("yyyy");

    var newString = dateItems[monthIndex] + '/' + dateItems[dayIndex] + '/' + dateItems[yearIndex];
    console.log(dateItems);**/
    return  new Date(_date);

}



function submitForm(id, type) {
    if (type != undefined) {
        switch (type) {
            case 'bulk':

                var checked = [];
                $(id).find('input[type=checkbox]').each(function() {
                    if($(this).prop('checked')) {
                        checked.push($(this).val())
                    }
                });
                if (checked.length < 1) {
                    return false;
                }

                return confirm(null, '', 'function', function() {
                    $(id).submit();
                })
                break;
        }
    }
    $(id).submit();
}

function clickButton(id) {
    $(id).click();
    return false;
}
function ajaxAction(url) {
    pageLoader(true);
    $.ajax({
        url : url,
        success : function(d) {
            pageLoaded();
            if (d === 'login') {

            }
            try{
                var r = jQuery.parseJSON(d);
                $('body').click()//to remove unwanted dropdowns
                var m = r.message;var t = r.type;var v = r.value;var c = r.content,modal = r.modal,table=r.table;
                if (t === 'url') {
                    load_page(v);
                } else if(t === 'normal-url') {

                    window.location.href = v;
                }else if(t === 'function') {
                    if (v !== undefined && v !== '') eval(v)(c);
                } else if(t === 'modal-function') {
                    $(modal).modal("hide");
                    if (v !== undefined && v !== '') eval(v)(c);
                } else if(t === 'modal-url') {
                    $(c).modal("hide");
                    load_page(v)
                } else if(t === 'reload') {
                    load_page(window.location.href);
                } else if(t === 'reload-modal') {
                    $(c).modal("hide");
                    load_page(window.location.href);
                }
                if (t === 'error') {
                    notify(m, 'error');
                } else {
                    if (m !== '' ) notify(m, 'success');
                }
            } catch (e) {
                console.log(e);
            }
        }
    })
}


$(function() {

    reloadInit();

    $(document).on("click", ".confirm", function() {
        return confirm($(this).attr('href'), $(this).data('message'), $(this).data('ajax-action'));
    });

    $(document).on('click', 'body', function() {
        $('.header-search-result').hide();
    })

    $(window).scroll(function() {
        if ($(window).scrollTop() > 20) {
            $('.general-header').addClass('header-fixed');
        } else {
            $('.general-header').removeClass('header-fixed');
        }
        if($(window).scrollTop() + $(window).height() == $(document).height()) {

        }
    });



    $(document).on('submit','.general-form',function() {
        var url = $(this).attr('action');
        var f = $(this);

        if ($(this).data('validate') !== undefined) {
            var result = eval($(this).data('validate') + "()");
            if (!result) return false;
        }

        if (f.data('not-ready') !== undefined && f.data('not-ready')) return false;
        if (f.data('no-loader') === undefined) pageLoader(true);



        var progress = null;
        if (f.data('upload')) {
            progress = $(f.data('upload'));
            var progressPercent = 0;
        }
        f.ajaxSubmit({
            url : url,
            uploadProgress: function(event, position, total, percentComplete) {
                var percentVal = percentComplete;

                if (progress !== null) {
                    progress.find('.progress-bar').css('width', percentVal + '%');
                    progress.find('.progress-bar').html(percentVal + '%');
                    progress.show();
                }
            },
            success : function(d) {
                try{
                    var r = jQuery.parseJSON(d);
                    $('body').click()//to remove unwanted dropdowns
                    var m = r.message;var t = r.type;var v = r.value;var c = r.content,modal = r.modal,table=r.table;
                    if (t === 'url') {
                        load_page(v);

                    } else if(t === 'normal-url') {

                        window.location.href = v;
                    } else if(t === 'function') {
                        if (v != undefined && v != '') eval(v)(c);
                    } else if(t === 'modal-function') {
                        $(modal).modal("hide");
                        if (v !== undefined && v !== '') eval(v)(c);
                    } else if(t === 'modal-url') {
                        $(c).modal("hide");
                        load_page(v)
                    } else if(t === 'reload') {
                        load_page(window.location.href);
                    } else if(t === 'reload-modal') {
                        $(c).modal("hide");
                        load_page(window.location.href);
                    } else if(t === 'normal-url') {
                        $(c).modal("hide");
                        window.location.href=v;
                    }
                    if (t === 'error' || t === 'error-function') {
                        notify(m, 'error');
                        if (v != undefined && v != '') eval(v)(c);
                    } else {
                        if (m !== '' && m !== undefined ) notify(m, 'success');

                    }
                    pageLoaded();
                } catch (e) {
                    pageLoaded()
                }

                if (progress !== null) {
                    progress.hide();
                    progress.find('.progress-bar').css('width', '0%');
                    progress.find('.progress-bar').html('0%');
                }
                window.globalFormSubmitting = false;

            }
        });
        return false;
    });

    $(document).on('click', '[data-ajax=true]', function() {
        load_page($(this).attr('href'));
        return false;
    });





    $(document).on('click', '.ajax-action', function() {
        ajaxAction($(this).attr('href'));
        return false;
    });

    $(document).on('keyup', '.search-form input', function() {
        if($(this).data('auto') !== undefined) return false;
        var parent = $(this).parent();
        var link = parent.attr('action');
        load_page(link + '?term=' + $(this).val(), false, parent.data('container'));
    });



    $(".each-file .the-content a").on('click',function(e) {
        e.stopPropagation();
    });


    var gdpr = getCookie('gdpr');
    if (gdpr === '') {
        $('.gdpr-container').slideDown();
    }

    $(document).on('click', '.listed-event', function() {
        load_page($(this).attr('href'));
        return false;
    });




});


function setCookie(cname, cvalue, exdays) {
    if(exdays == undefined) exdays = 365;
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while(c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if(c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function deleteCookie(cname) {
    document.cookie = cname + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
}

function acceptCookie() {
    setCookie('gdpr', '1');
    $('.gdpr-container').fadeOut();
    return false;
}

function earlyAccessDone() {
    $("#joinGroupModal").modal('show');
}


function previewFile(t,file, type) {
    var files = [];
    var position = 0;
    var i = 0;
    $('.files-grid .each').each(function() {
        if($(this).data('folder') === undefined && $(this).data('file') !== undefined) {
            files.push({
                src : $(this).data('file'),
                isVid: ($(this).data('type') === 'image') ? false : true
            });
            if (file === $(this).data('file')) position = i;
            i++
        }
    });
    if (files.length < 2) files.push({src: '', isVid: false});
    BigPicture({
        el:t,
        gallery: files,
        // optionally specify a starting index
        position: position,
    })
    return false;

    return false;
}



/**function submit_file_upload() {
    $(".filemanager-uploader").submit();
}**/

function selectThisFile(t) {

    if(!$(t).hasClass('selected')) {

        $(t).addClass('selected')
        $(t).find('.custom-control-input').prop('checked', 'checked')
    } else {
        $(t).removeClass('selected')
        $(t).find('.custom-control-input').removeAttr('checked')
        $(t).find('.custom-control-input').removeProp('checked')

    }
    toggleSelectButton();
}

function selectAllFiles(t) {
    $('.each-file ').each(function() {
        $(this).addClass('selected')
        $(this).find('input').prop('checked', 'checked')
    });
    $('.select-item-input').each(function() {
        $(this).prop('checked', 'checked')
    })
    toggleSelectButton();
    return false;
}

function deselectAllFiles(t) {
    $('.each-file').each(function() {
        $(this).removeClass('selected')
        $(this).find('input').prop('checked', false)
    });
    $('.select-item-input').each(function() {
        $(this).prop('checked', false)
    })
    toggleSelectButton();
    return false;
}
function countSelectedFiles() {
    var count = 0;
    $('.each-file').each(function() {
        if ($(this).find('input').is(':checked')) count += 1;
    });
    $('.select-item-input').each(function() {
        if ($(this).is(':checked')) count += 1;
    });
    return count;
}
function getSelectedIds(c) {
    var ids = [];
    c = (c === undefined) ? '.each-file': c;
    $(c).each(function() {
        if ($(this).hasClass('selected')) ids.push($(this).data('id'));
    });
    return ids;
}
function getSelectedMedias(c) {
    var ids = [];
    c = (c === undefined) ? '.each-file': c;
    $(c).each(function() {
        if ($(this).hasClass('selected')) ids.push($(this).data('raw'));
    });
    return ids;
}
function toggleSelectButton() {
    if (countSelectedFiles() > 0) {
        $('.select-button').hide();
        $('.deselect-button').fadeIn();
        $('.action-button').removeClass('disabled');
    } else {
        $('.deselect-button').hide();
        $('.select-button').fadeIn();
        $('.action-button').addClass('disabled');
    }
}


function submitFilesForm(action) {

    $('#form-action-input').val(action)
    $('.action-button').addClass('disabled');
    $('#files-form').submit();
}

function filterMediaList(t) {
    var v = $(t).val();
    return Filemanager.toggleMediaType(this,v)
}

function loadTemplates(t) {
    window.theTextEditor = $($(t).data('editor'));
    if (!window.theTextEditor.hasClass('emoji-text')) {
        window.theTextEditor.parent().find('.holder').focus();
    }
    $("#addTemplatesModal .modal-content").html("<img  style='width:100px;margin: 30px auto;' src='"+baseUrl+"styles/main/images/loader-bar.gif'/>")
    $("#addTemplatesModal").modal('show');
    $.ajax({
        url: buildLink('load/templates'),
        type: 'POST',
        data: {type: $(t).data('type')},
        success : function(r) {
            $("#addTemplatesModal .modal-content").html(r);
            reloadInit()
        }
    })
    return false;
}

function insertTemplate(t) {
    var el = window.theTextEditor.emojioneArea();
    el[0].emojioneArea.setText(el[0].emojioneArea.getText()+ '  ' +$(t).find('.value').html());
    $("#addTemplatesModal").modal('hide');
    el[0].emojioneArea.setFocus();
    return false;
}

function toggleSelectItemsAll(t) {
    $('.select-item-input').each(function() {
        if ($(t).is(':checked')) {
            $(this).prop('checked', 'checked')
            $(this).attr('checked', 'checked');
            $('#selectActionBtn').removeClass('disabled')
        }  else {
            $(this).prop('checked', false)
            $(this).attr('checked', false)
            $('#selectActionBtn').addClass('disabled')
        }
    });
}

function processSelectItems(t) {
    var all = true;
    var ids = [];
    $('.select-item-input').each(function() {
        if ($(this).is(':checked')) {
            ids.push($(this).data('id'))
        }  else {
            all = false;
        }
    });

    if (all) {
        $('.select-button').hide();
        $('.deselect-button').show();
    } else {
        $('.select-button').show();
        $('.deselect-button').hide();
    }

}

function doSelectForm(action) {
    $('#select-action-input').val(action);
    $('.select-form').submit();
    return false;
}

function chooseAccount(t, url) {
    if ($(t).hasClass('active')) {
        $(t).removeClass();
    } else {
        $(t).addClass('active');
    }
    $.ajax({
        url: url,
        success: function() {
            load_page(window.location.href);
        }
    });
    return false;
}
function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Fallback: Copying text command was ' + msg);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    document.body.removeChild(textArea);
}
function copyTextToClipboard(text) {
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).then(function() {
        console.log('Async: Copying to clipboard was successful!');
    }, function(err) {
        console.error('Async: Could not copy text: ', err);
    });
}

// Create and render a Picker object for picking user Photos.
function createPicker() {
    if (pickerApiLoaded && oauthToken) {
        $('.picker-dialog').remove();
        $('.picker-dialog-bg').remove();
        $('#ssIFrame_google').remove();
        var folderView = new google.picker.View(google.picker.ViewId.FOLDERS);
        //view.setMimeTypes("image/png,image/jpg,video/mp4");
        var picker = new google.picker.PickerBuilder()
            .enableFeature(google.picker.Feature.SUPPORT_DRIVES)
            .enableFeature(google.picker.Feature.NAV_HIDDEN)
            .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
            .setOAuthToken(oauthToken)
            //.addView(folderView)
            .addView(new google.picker.DocsView().setParent('root').setIncludeFolders(true))
            .addView(new google.picker.DocsUploadView())
            .setDeveloperKey(googleDriveDeveloperKey)
            .setCallback(pickerCallback)
            .build();
        picker.setVisible(true);
    }

}

// A simple callback implementation.
function pickerCallback(data) {
    var action = data[google.picker.Response.ACTION];
    if (action == google.picker.Action.PICKED) {
        if(data[google.picker.Response.DOCUMENTS] != undefined){
            var doc       = data[google.picker.Response.DOCUMENTS][0];
            var fileId    = doc[google.picker.Document.ID];
            var file_name = doc[google.picker.Document.NAME];
            var file_size = doc['sizeBytes'];
            if (data[google.picker.Response.ACTION] == google.picker.Action.PICKED) {
                pageLoader(true);
                var lastOpenFolder = getPossibleFolderId();
                $.ajax({
                    type: "POST",
                    datatype: 'json',
                    url: buildLink('files'),
                    data: {  file_id:fileId, file_name: file_name, file_size: file_size, oauthToken:oauthToken, google: true,folder_id: lastOpenFolder },
                    success: function(result){
                        pageLoaded();
                        var result = jQuery.parseJSON(result);
                        if (result.status == 1) {
                            notify(result.message,'success');
                            $('.files-list').prepend(result.content)
                        } else {
                            notify(result.message, 'error');
                        }
                        reloadInit()
                    }
                });

            }
        }
    }else if (action == google.picker.Action.CANCEL) {

    }
}

var scope = ['https://www.googleapis.com/auth/drive.readonly'];
var pickerApiLoaded = false;
var oauthToken;
var fileSelectorType = 1; //0 - single 1 -multiple ;
var fileSelectorMediaType = 2; // 0 - image 1 - video - 2 - both
function onAuthApiLoad() {
    window.gapi.auth.authorize({
        'client_id': googleDriveClientId,
        'scope': scope,
        'immediate': false
    }, handleAuthResult);
}

function onPickerApiLoad() {
    pickerApiLoaded = true;
    createPicker();
}

function handleAuthResult(authResult) {
    if (authResult && !authResult.error) {
        oauthToken = authResult.access_token;
        createPicker();
    }
    console.log(authResult);
}
function openGoogleDrivePicker() {
    gapi.load('auth', {'callback': onAuthApiLoad});
    gapi.load('picker', {'callback': onPickerApiLoad});
    return false;
}

function getPossibleFolderId() {
    var id = '';

    if ($('.possible-folder-id').length > 0) id = $('.possible-folder-id').val();
    if ($('.popup-folder-input').val() !== '0') id = $('.popup-folder-input').val();
    return id;
}
function showActivationMessage() {
    $("#activationEmailSuccess").fadeIn();
}
function openDropboxPicker() {
    Dropbox.choose({
        linkType: "direct",
        multiselect: true,
        success: function(files) {
            pageLoader(true);
            var lastOpenFolder = getPossibleFolderId();
            for(var i =0;i<files.length;i++) {
                var file = files[i];
                $.ajax({
                    type: "POST",
                    datatype: 'json',
                    url: buildLink('files'),
                    data: {   file_name: file.name, file_size: file.bytes, file:file.link, dropbox: true ,folder_id: lastOpenFolder},
                    success: function(result){
                        pageLoaded();
                        var result = jQuery.parseJSON(result);
                        if (result.status == 1) {
                            notify(result.message,'success');
                            $('.files-list').prepend(result.content)
                        } else {
                            notify(result.message, 'error');
                        }
                        reloadInit()
                    }
                });
            }


        },
        extensions: ['.jpg', '.jpeg', '.mp4','.gif','.png']
    });
    return false;
}
var Timably = {
    searchTimeout: null,
    loadQuickHelp: function(t) {
        $('.quick-help-container').fadeIn();
        $('.quick-help-menu a').removeClass('active');
        $('.'+t+'-btn').addClass('active');
        $('.help-lists').html('');
        $.ajax({
            url: buildLink('help/load'),
            data: {type: t, term: $('.quick-help-search-input').val()},
            success:function(d) {
                $('.help-lists').html(d);
            }
        });
        return false;
    },
    searchQuickHelp: function(t) {
        var term = $(t).val();
        if (term === '') return Timably.loadQuickHelp('basic');
        clearTimeout(Timably.searchTimeout);
        Timably.searchTimeout = setTimeout(function(){
            Timably.loadQuickHelp('basic');
        }, 500)
    },
    closeHelp: function() {
        $('.quick-help-container').hide();
        return false;
    },
    choosePlan: function(t) {
        var v = $(t).val();
        if (v === '1') {
            $('#yearly-plans').hide();
            $("#monthly-plans").fadeIn();
        } else {
            $("#monthly-plans").hide();
            $('#yearly-plans').fadeIn();
        }
    },
    showNotification: function() {

        $('.notification-dropdown').html("<div class='loader'><img src='"+buildLink('styles/main/images/loader-bar.gif')+"'/></div>")
        $.ajax({
            url: buildLink('notifications'),
            success: function (r) {
                $('.notification-dropdown').html(r);
            }
        })
    },
    globalSearch: function(t) {
        var i = $(t).val();
        if (i !== '') {
            $('.header-search-result').show();
            $('.header-search-result').html("<div class='loader'><img src='"+buildLink('styles/main/images/loader-bar.gif')+"'/></div>")
            if (Timably.searchTimeout) clearTimeout(Timably.searchTimeout);
            Timably.searchTimeout = setTimeout(function(){
                $.ajax({
                    url: buildLink('search'),
                    data: {term : i},
                    success: function (r) {
                        $('.header-search-result').html(r);
                    }
                })
            }, 1000);
        } else {
            $('.header-search-result').hide();
        }
    },
    closeWelcome: function() {
        $("#newWelcomeModal").modal('hide');
        setCookie("welcome-completed", 1);
        Timably.openPane('#new-account-modal');
        return false;
    },
    openPane: function(pane) {
        $(pane).fadeIn();
        return false;
    },
    copyReferLink: function( ) {
        copyTextToClipboard($('#refer-link-input').val());
        notify('Referral link copied', 'success');
        return false;
    },
    openEarlyAccess: function(type) {
        $('#pricing-type').val(type);
        $('#signpupEarlyAccessModal').modal('show')
        return false;
    },
    closePane: function(t) {
        $(t).parent().parent().parent().parent().hide();
        return false;
    },
    closeAllPane: function() {
        $('.cover-pane-container').hide();
        return false;
    },
    changeView: function(t, type) {
        $('.change-view-btn').removeClass('active');
        $(t).addClass('active');
        var className = (type === 'grid') ? 'grid-active' : 'list-active';
        $('.general-view-container').removeClass('grid-active');
        $('.general-view-container').removeClass('list-active');
        $('.general-view-container').addClass(className);
        return false;
    },
    userInvited: function(c) {
        $('.workspace-users-list-'+c.id).append(c.content);
        $('#users-'+c.id).find('.input').val('')
    },
    selectWorkspace: function(id, n) {
        pageLoader(true)
        $.ajax({
            url: buildLink('publishing?workspace='+id),
            success:function() {
                pageLoaded();
                if (n  === undefined) {
                    load_page(buildLink('publishing'));
                } else {
                    window.location.href = buildLink('publishing');
                }

            }
        });

        return false;
    },
    createWorkspace: function(p) {
        if (p === 1) {
            //show modal
            $('#newWorkspaceModal').modal('show');
        } else if(p === 2) {
            // show become member modal
        } else {
            //show upgrade to advance
        }
        return false;
    },
    workspaceMemberDeleted: function(c) {
        $('.member-row-'+c).remove();
    },
    addNewTime: function(day) {
        var id = "time-"+Date.now()+$('.'+day).find('.each-time').length;
        $('.'+day).find('.add').after("<div class=\"time each-time shadow-1\">\n" +
            "                                    <input id='"+id+"' name=\"val["+day+"][]\" type='text' class='input-timepicker' value='01:00' min='01' max='24'/> \n" +
            "                                    <a href=\"\" onclick=\"return Timably.removeTime(this)\"><i class=\"las la-times\"></i></a>\n" +
            "                                </div>");
        reloadInit()
        return false;
    },
    removeTime: function(t) {
        $(t).parent().remove();
        return false;
    },
    labelColorChanged: function(i,color) {
        var rgb = color.toRgb();
        var hex = color.toHexString();
        i.parent().parent().css('border-color', hex);
        i.parent().parent().css('background-color', 'rgba('+rgb.r+','+rgb.g+','+rgb.b+', 0.1)');
        i.parent().parent().find('.label-text').css('color', hex);
    },
    addLabel: function() {
        $('.label-alert').remove();
        if ($('.label-text').val() === '') {
            notify(strings.label_add_error, 'error')
            return false;
        }
        var c = $('.post-labels-container .each').length;
        $(".post-labels-container").prepend("<div class=\"each\">\n" +
            "                            <div class=\"color-container\">\n" +
            "                                <input data-result=\"Timably.labelColorChanged\" type=\"text\" value=\"#ff9900\" class=\"color\" name=\"label["+c+"][color]\"/>\n" +
            "                            </div>\n" +
            "                            <input name=\"label["+c+"][title]\" type=\"text\" class=\"label-text\" placeholder=\""+strings.label_title+"\" />\n" +
            "                            <a href=\"\" onclick=\"return Timably.removeLabel(this)\" class=\"delete-btn\"><i class=\"las la-times\"></i></a>\n" +
            "                        </div>");
        reloadInit();
        return false;
    },
    removeLabel: function(t) {
        $(t).parent().remove();
        return false;
    }
}
var PostEditor = {
    accounts : [],
    media : [],
    postType: 'media',
    refreshPage: null,
    switchScheduleType: function(t) {

        if ($(t).val() === '2') {
            $('.schedule-input-container').removeClass('disabled')
        } else {
            $('.schedule-input-container').addClass('disabled')
        }
        return false;
    },

    addLabel: function(bg,color,title) {
        $(".label-inputs").append("<div class=\"each\" style='background-color:"+bg+";color:"+color+";border-color:"+color+"'>\n" +
            "                                <span>"+title+"</span>\n" +
            "                                <input type='hidden' name='val[labels][]' value='"+title+"|"+color+"'/>" +
            "                                <a href=\"\" onclick=\"return PostEditor.removeLabel(this)\"><i class=\"las la-times\"></i></a>\n" +
            "                            </div>");
        return false;
    },

    removeLabel: function(t) {
        $(t).parent().remove();
        return false;
    },
    openPostDetail: function(info) {
        var mediaType = (info.image.indexOf('.mp4') !== -1) ? 'video': 'image';
        if (mediaType === 'image') {
            $("#calendarPostModal .post-image").html('')
            $("#calendarPostModal .post-image").css("background-image", "url("+info.image+")");
        } else {
            $("#calendarPostModal .post-image").html("<div class='video-content'><video src='"+info.image+"'/></div>")
        }
        $("#calendarPostModal .post-caption").html(info.caption);
        console.log(info.schedule_date);
        $("#calendarPostModal .schedule-date").html(info.schedule_date);
        $('#calendarPostModal .delete-btn').attr('href', buildLink('publishing/posts?action=delete&id='+info.postId));
        if (info.edit === false) {
            $('#calendarPostModal .edit-button').hide();
            $('#calendarPostModal .party-link').show();
            $('#calendarPostModal .party-link').attr('href', info.party_link)
        } else {
            $('#calendarPostModal .party-link').hide();
            $('#calendarPostModal .edit-button').show();
            $('#calendarPostModal .edit-button .info').html(JSON.stringify(info.edit))
        }

        $('#calendarPostModal').modal('show')
        return false;
    },
    deleteSelected: function(t) {
        var ids = [];
        $('.select-item-input').each(function() {
            if ($(this).is(':checked')) ids.push($(this).data('id'));
        });
        if (ids.length > 0) {
            ids = ids.join(',');
            var url = buildLink('publishing/posts?action=delete&id='+ids);

            confirm(url, $(t).data('message'), true);
        }
        return false;
    },
    loadPost: function(t) {
        var data = jQuery.parseJSON($(t).find('.info').html());
        $('#calendarPostModal').modal('hide');
        $('.selected-media-input').val(data.medias.join(','));
        PostEditor.updateMediaPreview();
        var el = $("#compose-editor").emojioneArea();
        el[0].emojioneArea.setText(data.caption);
        $('.post-editor-accounts input').prop('checked', false);
        $('.post-editor-accounts .each-account-'+data.account).click();
        $('.account-picker-trigger').addClass('disabled');
        if (data.status === '4'){
            $(".draft-check-input").prop('checked', 'checked');
        } else if(data.status === '2') {
            $(".scheduled-check-input").prop('checked', 'checked');
            $('.schedule-input-container').removeClass('disabled')
        }
        var fp = document.querySelector("#post-editor-schedule-time-input")._flatpickr;
        fp.setDate(data.time, true, 'm/d/Y h:i');

        $('.edit-post-input').val(data.id);
        Timably.openPane('#new-compose-modal')
        return false;
    },

    refereshSelectedAccount: function(c, r) {
        var all = true;
        var ids = [];
        var names = [];
        var avatar = [];
        $(c).find('.each-account-input').each(function() {
            if ($(this).is(':checked')) {
                ids.push($(this).data('id'));
                names.push($(this).data('name'));
                avatar.push($(this).data('avatar'));
            }  else {
                all = false;
            }
        });


        if (all) {
            $(c).find('.account-select-all-input').prop('checked', 'checked')
            $(c).find('.account-select-all-input').attr('checked', 'checked')
        } else {
            $(c).find('.account-select-all-input').prop('checked', false)
            $(c).find('.account-select-all-input').attr('checked', false)
        }


        //load_page(url)
        if (ids.length > 0) {

            var pickerText =  '<img src="'+avatar[0]+'" class="picker-img"/> ';
            if (ids.length > 1) {
                var v = ids.length-1;
                pickerText += '<span class="picker-count">+'+v+'</span>';
            }
            pickerText += names[0];
            $(c).find('.dropdown-account-picker .text').html(pickerText);
            ids = ids.join(',');
            PostEditor.refreshPage = setTimeout(function() {

                //reload page
                if (r === 1) {
                    $.ajax({
                        url: buildLink('publishing'),
                        data: {account:ids}
                    });
                    load_page(window.location.href);
                }
            }, 3000)
        } else {
            ids = 'clear';
            $(c).find('.dropdown-account-picker .text').html(strings.choose_accounts);
            PostEditor.refreshPage = setTimeout(function() {
                if (r === 1) {
                    $.ajax({
                        url: buildLink('publishing'),
                        data: {account:ids}
                    });
                    load_page(window.location.href);
                }
            }, 3000)
        }
    },
    addSelectAccount : function(t, r, c) {

        if(PostEditor.refreshPage) clearTimeout(PostEditor.refreshPage);
        if ($(t).hasClass('each')) {
            if ($(t).find('input').is(':checked')) {
                $(t).find('input').removeProp('checked');
                $(t).find('input').removeAttr('checked');
                if ($(t).next().hasClass('groups-page-container')) $(t).next().hide();
            } else {
                $(t).find('input').prop('checked', 'checked');
                $(t).find('input').attr('checked', 'checked');

                if ($(t).next().hasClass('groups-page-container')) {
                    $(t).next().show();
                }
            }
        }

        PostEditor.refereshSelectedAccount(c, r);
        return false;
    },

    togglePageSelectAll : function(t) {
        var ids = [];
        $('.each-account-input').each(function() {
            if ($(t).is(':checked')) {
                ids.push($(this).data('id'));
                $(this).prop('checked', 'checked')
                $(this).attr('checked', 'checked')
            }  else {
                $(this).prop('checked', false)
                $(this).attr('checked', false)
            }
        });
        var url = $(c).find('.account-select-all-input').data('url');

        load_page(url)
    },

    toggleSelectAll: function(t, r, c) {


        if($(t).find('input').is(':checked')) {
            $(c).find('.account-selector-list a ').each(function() {
                $(this).find('input').prop('checked', false)
                $(this).find('input').attr('checked', false)
            });
        } else {
            $(c).find('.account-selector-list a ').each(function() {
                $(this).find('input').prop('checked', 'checked')
                $(this).find('input').attr('checked', 'checked')
            });

        }
        PostEditor.refereshSelectedAccount(c, r);
        return false;
    },

    removeMedia: function(t) {
        var media = $(t).parent().data('media');
        var input = $('.selected-media-input').val();
        input = input.split(',');
        var newInput = [];
        for(var i = 0; i<input.length;i++) {
            var md = input[i];
            if (md !== media) newInput.push(md);
        }
        $('.selected-media-input').val(newInput.join(','));

        PostEditor.updateMediaPreview();

        return false;
    },

    setMediaActive: function(t) {
        var media = $(t).parent().data('media');
        $('.media-select-container .more-media .each').removeClass('active');
        $(t).parent().addClass('active');
        var mediaType = (media.indexOf('.pm4') !== -1) ? 'video': 'image';
        if (mediaType === 'image'){
            $('.media-select-container .first').css('background-image', 'url('+buildLink(media)+')')
            $('.media-select-container .first').html('');
        } else {
            $('.media-select-container .first').html('<video src="'+buildLink(media)+'"/>');
        }
    },
    updateMediaPreview: function() {
        var input = $('.selected-media-input').val();
        if (input === '') {
            $('.media-select-container .first').remove();
            $('.media-select-container').prepend("<div class='first'><i class=\"las la-cloud-upload-alt\"></i></div>")

        }
        input = input.split(',');


        var container = $(".media-select-container .more-media");
        container.html('');
        switch(input.length) {
            case 1:
                input.push("");
                input.push("");
                input.push("");
                break;
            case 2:
                input.push("");
                input.push("");
                break;
            case 3:
                input.push("")
                break;
            case 5:
                input.push("");
                input.push("");
                input.push("");
                break;
            case 6:
                input.push("");
                input.push("");
                break;
            case 7:
                input.push("");
                break;
            case 9:
                input.push("");
                input.push("");
                input.push("");
                break;
            case 10:
                input.push("");
                input.push("");
                break;
            case 11:
                input.push("");
                break;
        }
        for(var i=0;i<input.length;i++) {
            var media = input[i];
            if (media === '') {
                container.append('<div class="each"></div>')
            } else {
                var mediaType = (media.indexOf('.mp4') !== -1) ? 'video': 'image';
                var div = null;
                if (mediaType === 'image') {
                    div = $('<div class="each"  data-media="'+media+'" style="background-image:url('+buildLink(media)+')"><div onclick="PostEditor.setMediaActive(this)"></div><a onclick="return PostEditor.removeMedia(this)" href="" class="remove"><i class="las la-times"></i></a></div>')
                    container.append(div)
                } else {
                    div = $('<div class="each"  data-media="'+media+'"><div onclick="PostEditor.setMediaActive(this)"><div class="video-content"><video src="'+buildLink(media)+'"/></div></div> <a onclick="return PostEditor.removeMedia(this)" href="" class="remove"><i class="las la-times"></i></a></div>')
                    container.append(div)
                }
            }
            if (i === 0 && media !== '') {
                div.addClass('active');
                if (mediaType === 'image'){
                    $('.media-select-container .first').css('background-image', 'url('+buildLink(media)+')')
                    $('.media-select-container .first').html('');
                } else {
                    $('.media-select-container .first').html('<div class="video-content"><video src="'+buildLink(media)+'"/></div>');
                }
            }
        }


    },

    chooseCallback: function(r) {
        var input = $('.selected-media-input').val();

        input = (input === '') ? [] :  input.split(',');

        for(var i = 0;i<r.length;i++) {
            var media = r[i];
            input.push(media.file_name);
        }
        $('.selected-media-input').val(input.join(','));
        PostEditor.updateMediaPreview();
    },




    submitPost: function(draft, action) {
        $("#postEditorForm").submit();
        return false;
    },

    postCompleted: function(c) {
        PostEditor.close()
        load_page(window.location.href);
    },

    preview: function(img, captions, t) {
        if (img === undefined) {
            img = $('.selected-media-input').val();
            var el = $("#compose-editor").emojioneArea();
            captions = el[0].emojioneArea.getText();
        }
        img = img.split(",");
        if (img.length> 0) {
            var media = img[0];
            var mediaType = (media.indexOf('.mp4') !== -1) ? 'video': 'image';
            var div = null;
            if (mediaType === 'image') {
                div = $('<div class="media"  data-media="'+media+'" style="background-image:url('+buildLink(media)+')"></div>')
            } else {
                div = $('<div class="media"  data-media="'+media+'"><div class="video-content"><video src="'+buildLink(media)+'"/></div></div>')

            }
            $('.preview .medias-container').html(div);
        } else {
            $('.preview .medias-container').html('');
        }

        if (t !== undefined) {
            captions = $(t).find('span').html();
        }

        if (captions === '') {
            $('.preview .caption .text').html('')
            $('.preview .caption .placeholder').fadeIn();
        } else {
            $('.preview .caption .text').html(captions)
            $('.preview .caption .placeholder').hide();
        }
        $("#postPreviewModal").modal('show');

        return false;

    },

    close: function(t) {
        if (t !== undefined){
            Timably.closePane(t);
        } else {
            Timably.closePane($('.post-editor-close-btn'));
        }
        $('.selected-media-input').val('');
        var el = $("#compose-editor").emojioneArea();
        el[0].emojioneArea.setText("");
        PostEditor.updateMediaPreview();
        $('.edit-post-input').val('');
        return false;
    },
    openComposer: function(medias) {
        if(medias !== undefined) {
            $('.selected-media-input').val(medias.join(','));
            PostEditor.updateMediaPreview();
        }
        Timably.openPane('#new-compose-modal');
        return false;
    }
}

var Filemanager = {
    func : null,
    openCanva: function(t) {
        window.canvaAPI.createDesign({
            design: {
                type: t,
            },
            onDesignPublish: function (options) {
                // Triggered when design is published to an image.
                var exportUrl = options.exportUrl;
                var folderId = getPossibleFolderId();
                pageLoader(true);
                $.ajax({
                    url: buildLink('files'),
                    type: 'POST',
                    data: {action: 'import-image', link: exportUrl, folderId: folderId,canva: true},
                    success: function(result) {
                        pageLoaded();
                        var result = jQuery.parseJSON(result);
                        if (result.status == 1) {
                            notify(result.message,'success');
                            $('.files-list').prepend(result.content);
                            $("#importImageModal").modal('hide');
                            $('#import-image-input').val('');
                        } else {
                            notify(result.message, 'error');
                        }
                        reloadInit();
                    }
                })
               /** $.ajax({
                    url : buildLink('party/upload/canva'),
                    data : {image: exportUrl, type: type, id: id},
                    success: function(result ) {
                        var json = jQuery.parseJSON(result);
                        if (type === 'party-post') {
                            finishPartyAddMedia(json, true);
                        } else {
                            uploadFinished(json.display);
                        }
                    }
                })**/
            },
            onDesignClose: function () {
                // Triggered when editor is closed.
            },
        });
        return false;
    },
    checkedStyles: function(t) {
        if($(t).find('input').is(':checked')) {
            $(t).find('input').attr('checked',false)
            $(t).find('input').prop('checked',false)
        }  else {
            $(t).find('input').attr('checked','checked')
            $(t).find('input').prop('checked','checked')
        }
        Filemanager.reloadGraphics();
    },
    checkColors: function(t) {
        if ($(t).hasClass('active')) {
            $(t).removeClass('active')
        } else {
            $(t).addClass('active')
        }
        Filemanager.reloadGraphics();
        return false;
    },
    reloadGraphics: function() {
        setTimeout(function() {
            var url = $('.graphic-page-url').html();
            var colors = [];
            var styles = [];
            $(".filter-color .active").each(function() {
                colors.push($(this).data('color'))
            });
            $(".style-inputs").each(function() {
                if($(this).is(':checked')) styles.push($(this).val());
            });
            load_page(url+'?colors='+colors.join(',')+'&styles='+styles.join(','));
        }, 1000)
    },
    startParty: function() {
        var ids = getSelectedMedias();
        if (ids.length < 1) {
            notify('Please select some graphics to continue', 'error')
        } else {
            $('.party-media-inputs').val(ids.join(','));
            Timably.openPane('#new-party-modal');
        }
        return false;
    },
    scheduleSinglePhoto: function(media) {
        PostEditor.openComposer([media]);
        return false;
    },
    scheduleMultiPhoto: function() {
        var ids = getSelectedMedias();
        if (ids.length < 1) {
            notify('Please select some graphics to continue', 'error')
        } else {
            PostEditor.openComposer(ids);
        }
        return false;
    },
    createTemplate: function() {
        var ids = getSelectedMedias();
        if (ids.length < 1) {
            notify('Please select some graphics to continue', 'error')
        } else {
            $('.party-media-inputs').val(ids.join(','));
            $("#addTemplateModal").modal('show')
        }
        return false;
    },
    showCollection: function(t, w) {
        if($('.side-collections-container').css('display') === 'none') {
            $('.side-collections-container').fadeIn();
            $(t).addClass('active')
        } else {
            $('.side-collections-container').hide();
            $(t).removeClass('active')
        }
        return false;
    },
    openCollection: function(t) {
        pageLoader();
        $('.side-collections-container').hide();
        $('.files-container').data('offset', 0);
        $('.files-container').data('url', buildLink('files/load/'+t));

        $('.files-container .content-scroller').html('')
        $('.file-folder-id-input').val(t);
        $.ajax({
            url: buildLink('files/open'),
            data: {id: t},
            type: 'POST',
            success: function(c) {
                pageLoaded();
                $('.files-container .content-scroller').html(c);
            }
        });
        return false;
    },
    toggleMediaType: function(item, t) {
        $('.filter-media-container .dropdown-item').removeClass('active');
        $(item).addClass('active')
        if (t === 'all') {
            $('.files-container').removeClass('image-only');
            $('.files-container').removeClass('video-only');
        } else if(t === 'image') {
            $('.files-container').addClass('image-only');
            $('.files-container').removeClass('video-only');
        } else if(t === 'video') {
            $('.files-container').removeClass('image-only');
            $('.files-container').addClass('video-only');
        }
        return false;
    },
    add: function() {
        var result = [];
        $("#fileManagerModal .selected").each(function() {
            result.push({id: $(this).data('id'), file_name: $(this).data('raw'), thumbnail: $(this).data('thumbnail'), type: $(this).data('type'), file: $(this).data('file')})
        });

        $("#fileManagerModal").modal('hide');
        eval(Filemanager.func)(result);
        return false;
    },
    open: function(callback) {
        Filemanager.func = callback;
        $("#fileManagerModal").modal('show');
        $("#fileManagerModal #choose-files-container").data('offset', 0);
        $("#fileManagerModal #choose-files-container").data('url', buildLink('files/load'))
        $("#fileManagerModal #choose-files-container").html("<img style='width: 100px !important;max-width:100px !important;display: block;height:20px !important; margin: 30px auto;' src='"+baseUrl+"styles/main/images/loader-bar.gif' class='loader'/>");
        Filemanager.load(0)
        return false;
    },

    load: function(id) {
        $.ajax({
            url: buildLink('files/load'),
            data: {id: id},
            success: function(d) {
                $("#fileManagerModal #choose-files-container").html(d);
            }
        })
    },

    loadCollections: function(id) {
        id = $(id).val();
        $('#choose-files-container').data('url', buildLink('files/load?id=' + id)) ;
        $('#choose-files-container').data('offset', 0);
        $("#fileManagerModal #choose-files-container").html("<img style='width: 100px !important;max-width:100px !important;display: block;height:20px !important; margin: 30px auto;' src='"+baseUrl+"styles/main/images/loader-bar.gif' class='loader'/>");

        Filemanager.load(id);
    },

    openFolder: function(url) {
        $("#fileManagerModal #choose-files-container").html("<img style='width: 100px !important;max-width:100px !important;display: block;height:20px !important; margin: 30px auto;' src='"+baseUrl+"styles/main/images/loader-bar.gif' class='loader'/>");
        $("#fileManagerModal #choose-files-container").data('offset', 0);
        $("#fileManagerModal #choose-files-container").data('url', url)
        $.ajax({
            url: url,
            success: function(d) {
                $("#fileManagerModal #choose-files-container").html(d);
            }
        })
        return false;
    },
    collectionCreated: function(c) {
        $('.collection-list').append(c);
        reloadInit();
    },
    fileUploaded: function(c) {
        $('.files-list').prepend(c);
        $('.empty-result').hide();
        $('.filemanager-uploader input[type=file]').val('');
        reloadInit();
    },
    copy: function() {
        var ids = getSelectedIds();
        $('#copy-id-input').val(ids.join(','));
        if ($('.graphics-page').length >0 ){
            $('#copy-source-input').val('graphics')
        } else {
            $('#copy-source-input').val('files')
        }
        $('#copyToModal').modal('show');
        return false;
    },
    move: function() {
        var ids = getSelectedIds();
        $('#move-id-input').val(ids.join(','));
        $('#moveToModal').modal('show');
        return false;
    },
    delete: function() {
        var ids = getSelectedIds();
        $('#files-form').removeProp('data-no-loader');
        $('#files-form').removeAttr('data-no-loader')
        $('#form-action-ids-input').val(ids.join(','));
        confirm(null, "Are you sure you want to delete files", "function",function() {
            submitFilesForm('delete');
        })
        return false;
    },
    adjustSorting: function() {
        $('#files-form').data('no-loader', true)
        submitFilesForm('sort');
    },
    fileDeleted: function() {
        $('.selected').remove();
    },
    editDescrition: function(id) {
        $(id).modal('show')
        return false;
    },
    saveDescription: function(id) {
        pageLoader(true);
        $.ajax({
            url: buildLink('files'),
            data:{action:'description', id: id, text: $('#editMediaDescriptionModal'+id).find('textarea').val()},
            success:function(d){
                notify(d, 'success');
                $('#editMediaDescriptionModal'+id).modal('hide')
                pageLoaded()
            }
        })
        return false;
    },
    importImage: function() {
        var link = $('#import-image-input').val();
        if (link === '') return notify('Please provide a image link to import', 'error');
        var folderId = getPossibleFolderId();
        pageLoader(true);
        $.ajax({
            url: buildLink('files'),
            data: {action: 'import-image', link: link, folderId: folderId},
            success: function(result) {
                pageLoaded();
                var result = jQuery.parseJSON(result);
                if (result.status == 1) {
                    notify(result.message,'success');
                    $('.files-list').prepend(result.content);
                    $("#importImageModal").modal('hide');
                    $('#import-image-input').val('');
                } else {
                    notify(result.message, 'error');
                }
                reloadInit();
            }
        })
        return false;
    }
}

var Account = {
    connectFacebook: function(t, type, id, name,token, avatar) {
        if ($(t).hasClass('active')) {
            //notify(strings.accounts_connected_already, 'error');
            //return false;
        }
        $(t).addClass('active');
        pageLoader();
        $.ajax({
            url: buildLink('accounts/facebook'),
            type: 'POST',
            data: {action: 'add', type: type, id: id, name: name, token: token, avatar: avatar},
            success: function(r) {
                pageLoaded()
                r = parseInt(r);
                if (r === 1) {
                } else if(r === 2){
                    //we can't remove the active can be due to double clicking
                } else {
                    notify(strings.accounts_limit_reach, 'error');
                    $(t).removeClass('active');
                    //i think here we should show the upgrade to advance plan to add more accounts
                }
            }
        })
        return false;
    },
    changeView: function(t, w) {
        $('.change-view-btn').removeClass('active');
        $(t).addClass('active');
        if (w === 'list') {
            $('#accounts-container').addClass('accounts-container-list');
        } else {
            $('#accounts-container').removeClass('accounts-container-list');
        }
        return false;
    },
    multiAction: function(a, account) {
        var selectedAccounts = (account !== undefined) ? [account] : this.selectedAccounts() ;
        if (selectedAccounts.length < 1) {
            notify(strings.select_account_actions, 'danger');
            return false;
        }
        //continue action
        pageLoader(true);
        $.ajax({
            url: buildLink('accounts'),
            data: {accounts: selectedAccounts, action: a},
            success: function(s) {
                pageLoaded();
                if (a === 'sync') {
                    notify(strings.accounts_synced, 'success');
                    load_page(window.location.href);
                } else if(a === 'delete-action') {
                    notify(strings.accounts_deleted, 'success');
                    load_page(window.location.href);
                }
            }
        })

        return false;
    },
    selectedAccounts: function() {
        var accounts = [];
        $('.account-checkbox').each(function() {
            if ($(this).is(':checked')) accounts.push($(this).data('id'));
        });
        return accounts;
    },
    search: function(t) {
        var term = $(t).val();
        if (term !== '') {
            pageLoader();
            $('#accounts-container').html('')
            $.ajax({
                url:buildLink('accounts'),
                data:{term: term, action:'search'},
                success: function(d) {
                    pageLoaded();
                    $('#accounts-container').html(d)
                }
            })
        }
    },
    switchAccount: function(url) {
        $('#switchAccountsModal').modal('hide');
        load_page(url);
        return false;
    },

    selectAccount: function(t, id) {
        if ($(t).is(':checked')) {
            $('.each-account-'+id).find('.each').addClass('active')
            $('.each-account-'+id).find('input').prop('checked', 'checked');
            $('.each-account-'+id).find('input').attr('checked', 'checked');
        } else {
            $('.each-account-'+id).find('.each').removeClass('active')
            $('.each-account-'+id).find('input').removeProp('checked');
            $('.each-account-'+id).find('input').removeAttr('checked');
        }
    }
}

var PartyTemplate = {
    addType: 0,
    addId: null,
    saveDescGoing: null,
    updateMedia: function (id) {
        var medias = [];
        $('.media-container-'+id+' .post-media-input').each(function()  {
            medias.push($(this).val());
        });
        $.ajax({
            url: window.location.href,
            data: {action: 'update-media', id: id, medias: medias}
        })
    },
    removePostMedia: function(t, single) {
        var id = $(t).data('id');

        if (single !== undefined) {
            $(t).parent().remove();
        } else {
            $(t).parent().parent().remove();
        }
        PartyTemplate.updateMedia(id);
        return false;
    },
    keyupResult: function(obj, t) {
        if (PartyTemplate.saveDescGoing) clearTimeout(PartyTemplate.saveDescGoing);
        PartyTemplate.saveDescGoing = setTimeout(function() {
            $.ajax({
                    url: window.location.href,
                    data: {action: 'save-caption', id: obj.data('id'), text: t}
                }
            );
        }, 1500)
    },
    updateDay: function(t, id) {
        var v = $(t).val();
        if (v !== '') {
            $.ajax({
                    url: window.location.href,
                    data: {action: 'save-day', id: id, text: v}
                }
            );
        }
    },
    updateTime: function(t, id) {
        var v = $(t).val();
        if (v !== '') {
            $.ajax({
                    url: window.location.href,
                    data: {action: 'save-time', id: id, text: v}
                }
            );
        }
    },
    addMedia: function(type) {
        PartyTemplate.addType = type;
        Filemanager.open('PartyTemplate.chooseCallback')
        return false;
    },
    openAddText: function() {
        var el = $("#add-text-modal").emojioneArea();
        el[0].emojioneArea.setText("");
        $("#addTextModal").modal('show');
        return false;
    },
    addText: function() {

        var el = $("#add-text-modal").emojioneArea();
        var text = el[0].emojioneArea.getText();
        if (text === '') {
            notify(strings.add_text_content, 'error')

            return false;
        }
        $("#addTextModal").modal('hide');
        pageLoader(true);
        $.ajax({
            url: window.location.href,
            data: {text: text, action: 'add-text'},
            success: function(r) {
                $('.party-posts-list').append(r);
                $('.empty-result').hide();
                $("#addTextModal").modal('hide')
                pageLoaded();
                reloadInit();
            }
        })
        return false;
    },
    chooseCallback: function(r) {
        pageLoader(true);
        $.ajax({
            url: window.location.href,
            data: {type: PartyTemplate.addType, files: r, action: 'add-media'},
            success: function(r) {
                $('.party-posts-list').append(r);
                $('.empty-result').hide();
                pageLoaded();
                reloadInit();
            }
        })
    },
    addMediaToPost: function(id) {
        PartyTemplate.addId = id;
        Filemanager.open('PartyTemplate.chooseCallbackForPost');
        return false;
    },
    chooseCallbackForPost: function(r) {
        pageLoader(true);
        $.ajax({
            url: window.location.href,
            data: {id: PartyTemplate.addId, files: r, action: 'add-media-to-post'},
            success: function(r) {
                $('.media-container-'+PartyTemplate.addId).html(r);
                pageLoaded();
                reloadInit();
            }
        });
    },

    removePost: function(id) {
        confirm(null,strings.are_your_sure, 'function', function() {

           $('.each-party-post-'+id).remove();
           $.ajax({
               url: window.location.href,
               data: {action:'delete-post', id: id}
           })
        });
        return false;
    },

    openNewTemplate: function(t, tId, pId) {
        if (tId !== undefined && tId) $('.new-template-template-id').val(tId);
        if (pId !== undefined && pId) $('.new-template-party-id').val(pId);
        if ($(t).data('title') !== undefined) $('.new-template-title').val($(t).data('title'))
        $('#addTemplateModal').modal('show')
        return false;
    }
}

var Comment = {
    checkMember: function(t) {
        if ($(t).hasClass('active')) {
            $(t).removeClass('active');
            $(t).find('input').attr('checked', false)
        } else {
            $(t).addClass('active');
            $(t).find('input').attr('checked', 'checked')
        }
        var peoples = [];
        $(t).parent().find('input').each(function() {
            if ($(this).is(':checked')) peoples.push($(this).val())
        })
        $(t).parent().parent().find('.peoples-count').html(peoples.length);
        return false;
    },
    fakeFocus: function(t) {
        var c = $(t).parent();
        c.hide();
        c.parent().find('.original-comment-input').show();
    },
    added: function(c) {
        $('.comment-lists-'+c.id).prepend(c.display);
        $('.comment-form-'+c.id).find('textarea').val('')
        $('.comment-form-'+c.id).find('input').val('')
    },
    deleted: function(c) {
        $('.comment-'+c).remove();
    },
    marked: function(c) {
        $('.comment-mark-'+c).addClass('active');
    },
    approved: function(c) {
        $('.approve-btn-'+c.id).attr('title', c.title);
        $('.approve-btn-'+c.id).prop('title', c.title);
        if (c.type === 'reject') {
            $('.approve-btn-'+c.id).removeClass('approved')
        } else {
            $('.approve-btn-'+c.id).addClass('approved')
        }
        reloadInit()
    }
}
var Parties = {
    changeCreateFrom : function(t) {
        console.log($(t).val());
        if($(t).val() === '1') {
            $('.copy-party-forms').hide();
            $('.new-party-forms').fadeIn();
        } else {
            $('.new-party-forms').hide();
            $('.copy-party-forms').fadeIn();
        }
    },
    created: function(c) {
        Timably.closeAllPane();
        load_page(c)
    },
    makeAdmin: function(id, type) {
        $.ajax({
            url: buildLink('publishing/parties/templates'),
            data: {action: 'make-admin', id:id,type:type},
            success: function(c) {
                $('.each-shared-user-'+id).html(c);
            }
        });
        return false;
    },
    userListChanged: function(t) {
        var v = $(t).val();
        if (v === 'all') {
            $('.shared-user-lists').removeClass('list-admins')
            $('.shared-user-lists').removeClass('list-read-only')
        } else if(v === '1' ) {
            $('.shared-user-lists').addClass('list-admins')
            $('.shared-user-lists').removeClass('list-read-only')
        } else if(v === '0') {
            $('.shared-user-lists').removeClass('list-admins')
            $('.shared-user-lists').addClass('list-read-only')
        }
    },
    copyLink: function() {
        copyTextToClipboard($('.share-link').html());
        notify(strings.share_link_copied, 'success')
        return false;
    }
}

var PhotoEditor = {
    open: function( width, height,uid, img) {
        var url = "https://studio.pixelixe.com/#api?apiKey=JB82XD4iDDRIGiYHIgQJHiIXBmk9NkpBHBdXXg&width="+width+"&height="+height+"&user_uid="+userId+'&custom_field_1='+workspaceId;
        if (uid !== undefined && uid !== null) url += "&graphicUrl="+uid;
        if (img !== undefined && img !== null) url += "&imageUrl="+img;

        $(".photo-editor-container .editor").html("<iframe frameborder='0'\n" +
            "                        src='"+url+"'></iframe>");
        $(".photo-editor-container").fadeIn();
        return false;
    },
    close: function() {
        $(".photo-editor-container").fadeOut();
        return false;
    },
    startNew: function() {
        $('#newDesignModal').modal('hide');
        PhotoEditor.open($('.design-width').val(), $('.design-height').val());
        return false;
    }
}