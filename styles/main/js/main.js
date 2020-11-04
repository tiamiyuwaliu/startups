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
    iziToast.show({
        theme: 'dark',
        icon: 'icon-person',
        title: '',
        overlay : true,
        zindex : 9999999,
        message: (mess === undefined || mess === '') ? strings.are_your_sure : mess,
        position: 'center', // bottomRight, bottomLeft, topRight, topLeft, topCenter, bottomCenter
        progressBarColor: 'rgb(0, 255, 184)',
        buttons: [
            ['<button>'+strings.ok+'</button>', function (instance, toast) {
                if (ajax === undefined || !ajax) {
                    window.location.href = url;
                    instance.hide({
                        transitionOut: 'fadeOutUp',
                        onClosing: function(instance, toast, closedBy){
                        }
                    }, toast, 'buttonName');
                } else {
                    instance.hide({
                        transitionOut: 'fadeOutUp'
                    }, toast, 'buttonName');
                    if (ajax === 'function') {

                        functionName.call();
                    } else {
                        ajaxAction(url);
                    }
                }
            }, true], // true to focus
            ['<button>'+strings.close+'</button>', function (instance, toast) {
                instance.hide({
                    transitionOut: 'fadeOutUp',
                    onClosing: function(instance, toast, closedBy){
                        console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                    }
                }, toast, 'buttonName');
            }]
        ]
    });

    return false;
}


function notify(m, type) {
    if (type === 'error') {
        iziToast.error({
            message: m,
            position: 'topRight'
        });
    } else if(type === 'success') {
        iziToast.success({
            message: m,
            position: 'topRight'
        });
    } else {
        iziToast.info({
            message: m,
            position: 'topRight'
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
    $(".filemanager-uploader").submit();
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
                cursorcolor: "#E2E2E2",
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
        var start = ($(this).val() !== '') ? stringToDate($(this).val(), dateFormat) : new Date();

        var picker = $(this).datepicker({
            language: 'en',
            'position': 'top center',
            startDate: start,
            dateFormat: dateFormat
        }).data('datepicker');
        picker.selectDate(start);
    })

    $('.datepicker-input-time').each(function() {

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

           if ($('.post-container').length > 0) {
               var el = $(this).emojioneArea({
                   pickerPosition: 'bottom',
                   attributes: {
                       spellcheck: true
                   }
               });
               el[0].emojioneArea.on("keyup", function(editor, event) {
                   refereshPreview();
               });
               el[0].emojioneArea.on("change", function(editor, event) {
                   refereshPreview()
               });
           } else {
               if ($(this).css('display') !== 'none') {
                   $(this).emojioneArea({
                       pickerPosition: 'bottom',
                       attributes: {
                           spellcheck: true
                       }
                   });
               }
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
            move: function(color) {
                var color = color.toHexString(); // #ff0000
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

            },
            change: function(color) {
                var color = color.toHexString(); // #ff0000
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
            }
        });
    })

}


function stringToDate(_date,_format)
{

    var _delimiter = '.';
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
    return  new Date(newString);

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
                        if (m !== '' ) notify(m, 'success');
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
        var parent = $(this).parent();
        var link = parent.attr('action');
        load_page(link + '?term=' + $(this).val(), false, parent.data('container'));
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




function previewFile(t,file, type) {
    var files = [];
    var position = 1;
    var i = 0;
    $('.files-grid .each').each(function() {
        if($(this).data('folder') === undefined) {
            files.push({
                src : $(this).data('file'),
                isVid: ($(this).data('type') === 'image') ? false : true
            });
            if (file === $(this).data('file')) position = i;
            i++
        }
    });


    BigPicture({
        el:t,
        gallery: files,
        // optionally specify a starting index
        position: position,
    })
    return false;
    if (type === 'image') {
        BigPicture({
            el: t,
            imgSrc: file,
        })
    } else {
        BigPicture({
            el: t,
            vidSrc: file,
        })
    }
    return false;
}


function processAccountResult(result) {
    if (result.error_type === 'general' && result.message !== 'challenge_required') {
        notify(result.message, 'error');
    } else {
        $('.account-add-first-step').hide();
        $('.account-add-second-step').fadeIn();
        $('.detail-stepper').removeClass('active');
        $('.verification-stepper').addClass('active');
        $('.verification-text').html(result['message'])
        $('#verificationModal').find('label').html(c['message']);
    }
}

function accountAddedSuccess() {
    $('#addAccountModal').modal('hide');
    $('.account-add-first-step').fadeIn();
    $('.account-add-second-step').hide();
    $('.account-add-first-step').find('input').val('');
    $('.account-add-second-step').find('input').val('');
    $('.detail-stepper').addClass('active');
    $('.verification-stepper').removeClass('active');

    load_page(buildLink('accounts'));
    return false;
}

function submit_file_upload() {
    $(".filemanager-uploader").submit();
}


