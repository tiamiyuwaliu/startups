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
            position: 'topCenter'
        });
    } else if(type === 'success') {
        iziToast.success({
            message: m,
            position: 'topCenter'
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
                hideMenu();
                reloadInit();
                if ($('.post-edit-content').length > 0) {
                    finilizeSelectedAccounts();
                    validateEditPost();
                }
                pageLoaded();
                window.runPreviewAuto = true;
                window.openFolders = [];
                window.lastOpenFolder=null;
                //$("#help-modal").modal('hide');
                //automationPageInit();
                $('body').click();
                setTimeout(function() {
                    $('body').bootstrapMaterialDesign();
                }, 300)

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

function validateEditPost() {
    var count = $('#edit-content-social-count').val();
    var type = $('#edit-content-post-type').val();
    var socialType = ($('#edit-content-social').length > 0) ? $('#edit-content-social').val() : null;

    if (type === 'link') {
        if (count > 1) {
            $('#general-post-menu a ').removeClass('active');
            $('#general-post-menu a:eq(1)').addClass('active');
        } else {
            if (socialType === 'facebook') {
                $('#facebook-post-menu a ').removeClass('active');
                $('#facebook-post-menu a:eq(2)').addClass('active');
            } else if(socialType === 'vk') {
                $('#vk-post-menu a ').removeClass('active');
                $('#vk-post-menu a:eq(1)').addClass('active');
            } else if(socialType === 'linkedin') {
                $('#linkedin-post-menu a ').removeClass('active');
                $('#linkedin-post-menu a:eq(1)').addClass('active');
            } else if(socialType === 'reddit') {
                $('#reddit-post-menu a ').removeClass('active');
                $('#reddit-post-menu a:eq(1)').addClass('active');
            }
        }
        $("#post-type-input").val("link");
        fileSelectorMediaType = 2;
        fileSelectorType = -1;
        $('.link-input').show();
        removeAllSelectdFiles();
        $('.media-preview').hide();
        $('.media-preview .media-preview-main').html('')
        refereshPreview();
    } else if(type === 'text') {
        if (count > 1) {
            $('#general-post-menu a ').removeClass('active');
            $('#general-post-menu a:eq(2)').addClass('active');
        } else {
            if (socialType === 'facebook') {
                $('#facebook-post-menu a ').removeClass('active');
                $('#facebook-post-menu a:eq(3)').addClass('active');
            } else if(socialType === 'vk') {
                $('#vk-post-menu a ').removeClass('active');
                $('#vk-post-menu a:eq(2)').addClass('active');
            } else if(socialType === 'linkedin') {
                $('#linkedin-post-menu a ').removeClass('active');
                $('#linkedin-post-menu a:eq(2)').addClass('active');
            } else if(socialType === 'reddit') {
                $('#reddit-post-menu a ').removeClass('active');
                $('#reddit-post-menu a:eq(2)').addClass('active');
            } else if(socialType === 'twitter') {
                $('#twitter-post-menu a ').removeClass('active');
                $('#twitter-post-menu a:eq(2)').addClass('active');
            }
        }
        $("#post-type-input").val("text");
        fileSelectorMediaType = 2;
        fileSelectorType = -1;
        removeAllSelectdFiles();
        $('.media-preview').hide();
        $('.media-preview .media-preview-main').html('')
        refereshPreview();
    } else if(type === 'album') {
        fileSelectorMediaType = 2;
        fileSelectorType = 1;
        $("#post-type-input").val("album");
        $(".instagram-post-form").show();
        $('.media-preview').show();
        $("#instagram-post-menu a").removeClass('active');
        $("#instagram-post-menu a:nth-child(4)").addClass('active');
        refereshPreview();
    } else if(type === 'livestream') {
        fileSelectorMediaType = 1;
        fileSelectorType = 0;
        $("#post-type-input").val("livestream");
        if (socialType === 'instagram') $(".instagram-post-form").hide();
        $(".watermark-form").show();
        $('.media-preview').show();
        if (socialType === 'instagram') {
            $("#instagram-post-menu a").removeClass('active');
            $("#instagram-post-menu a:nth-child(3)").addClass('active');
        } else if(socialType === 'facebook') {
            $("#facebook-post-menu a").removeClass('active');
            $("#facebook-post-menu a:nth-child(3)").addClass('active');
        }

        refereshPreview();
    } else if ( type === 'story') {
        //console.log('fucking!!!!')
        fileSelectorMediaType = 2;
        fileSelectorType = 0;
        $("#post-type-input").val("story");
        $(".story-form").show();
        $(".instagram-post-form").show();
        $('.media-preview').show();
        $("#instagram-post-menu a").removeClass('active');
        $("#instagram-post-menu a:nth-child(2)").addClass('active');
        refereshPreview();
    } else if (type === 'photo') {
        fileSelectorMediaType = 0;
        fileSelectorType = 1;
        $("#post-type-input").val("photo");
        $('.media-preview').show();
        if(socialType === 'twitter') {
            $('#twitter-post-menu a ').removeClass('active');
            $('#twitter-post-menu a:eq(0)').addClass('active');
        }
        refereshPreview();
    } else if (type === 'video') {
        fileSelectorMediaType = 1;
        fileSelectorType = 1;
        $("#post-type-input").val("video");
        $('.media-preview').show();
        if(socialType === 'twitter') {
            $('#twitter-post-menu a ').removeClass('active');
            $('#twitter-post-menu a:eq(1)').addClass('active');
        }
        refereshPreview();
    }
}


window.captchIsLoaded = false;
window.runPreviewAuto = true;
function reloadInit(paginate) {
    initAnimation();

    if (window.runPreviewAuto && $('.post-selected-media').length > 0 && $('.post-selected-media input').length > 0) {
        window.runPreviewAuto = false;
        refereshPreview();

    }

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


    if ($('.instagram-analytics-container').length > 0) {
        pageLoader(true);
        $.ajax({
            url : $('.instagram-analytics-container').data('url'),
            success: function(content){
                $('.instagram-analytics-container').html(content);
                pageLoaded();
                $('.chart-container').each(function() {
                    var element = $(this).find('canvas').attr('id');
                    var label = $(this).data('label');
                    var data = [$(this).data('result1')];
                    if ($(this).data('result2')) {
                        data.push($(this).data('result2'))
                    }

                    var name = [$(this).data('name1')];
                    if ($(this).data('name2')) {
                        name.push($(this).data('name2'))
                    }
                    var type = 'line';
                    renderAnalyticCharts(element, label, data, name, type);
                });
            }
        });


    }


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



    if($("#schedule-calender").length > 0 && $("#schedule-calender").html() === '') {
        if ($('#schedule-calender').hasClass('monthly-locale-en')) {
            $('#schedule-calender').removeClass('monthly-locale-en');
            $('#schedule-calender').removeClass('monthly-locale-en-gb');
            $('#schedule-calender').html('');
        }

        var calendarEl = document.getElementById('schedule-calender');


        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: {
                url: $('#schedule-calender').data('url'),
                extraParams: function() {
                    return {
                        cachebuster: new Date().valueOf()
                    };
                },
                color: 'yellow',   // a non-ajax option
                textColor: 'black'
            },

        });
        calendar.render();
        setInterval(function() {
            $('.fc-daygrid-day').each(function() {
                var date = $(this).data('date');
                var dateSplit = date.split('-');
                var month = parseInt(dateSplit[1]);
                var day = parseInt(dateSplit[2]);
                var year = dateSplit[0];
                date = month + '/' + day + '/' + year;
                if ($(this).find('.create-post-link').length < 1) $(this).append("<a title='Create New Post' data-ajax='true' href='"+baseUrl+"post?date="+date+"' class=' create-post-link mtitle' ><i class=\"las la-plus-circle\"></i></a>")


            });
            $(".create-post-link").each(function() {
                if ($(this).data('tippy') === undefined) {
                    tippy('.mtitle',{
                        animation: 'shift-toward',
                        arrow: true
                    });
                }
            })
        }, 300);
    }


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
window.stripeCard = null;
window.stripe = null
function openStripeCardModal() {
    window.stripe = Stripe(stripeKey);
    var elements = window.stripe.elements();
    var style = {
        base: {
            // Add your base input styles here. For example:
            fontSize: '16px',
            color: '#32325d',
        },
    };

// Create an instance of the card Element.
    window.stripeCard = elements.create('card', {style: style});

// Add an instance of the card Element into the `card-element` <div>.
    window.stripeCard.mount('#card-element');
    $("#cardDetailModal").modal('show')
    return false;
}

function continueStripeCard() {
    pageLoader(true);

    stripe.createToken(window.stripeCard).then(function(result) {
        if (result.error) {
            // Inform the customer that there was an error.
            notify(result.error.message, 'error');
            pageLoaded();
        } else {
            // Send the token to your server.
            console.log(result.token);
            $("#stripe-token").val(result.token.id);
            pageLoaded();
            $("#cardDetailModal form").submit();
        }
    });
    return false;
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

function hideViewSchedulePosts() {
    $('.view-schedule-modal').fadeOut();
    $('.view-schedule-modal').html('');
    return false;
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

function sendEditFolder(id) {
    pageLoader(true);
    $.ajax({
        url : buildLink('file-manager'),
        data: {editfolder: true, id: id, name: $('#folder-edit-name-'+id).val()},
        success: function(r) {
            pageLoaded();
            var r = jQuery.parseJSON(r);
            console.log(r);
            $('body').click()//to remove unwanted dropdowns
            var m = r.message;var t = r.type;var v = r.value;var c = r.content,modal = r.modal,table=r.table;
            eval(v)(c);
            notify(m, 'success');
        }
    })
    return false;
}

$(function() {
    $(document).bootstrapMaterialDesign();
    reloadInit();
    $(document).on('click', '.fc-daygrid-event',function() {
        pageLoader(true);
        $.ajax({
            url: $(this).attr('href'),
            success : function(r) {
                $('.view-schedule-modal').html(r);
                pageLoaded();
                $('.view-schedule-modal').fadeIn();
                reloadInit()
            }
        })
        return false;
    });

    if ($('.post-edit-content').length > 0) {
        finilizeSelectedAccounts();
        validateEditPost()
    }
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


    if (enableWelcomeStemps === '1') {
        console.log('sd')
        window.tour = new Tour({
            name: 'SmartPostWelcomeTour12345664324234455435',
            backdrop: true,
            template: "<div class='popover tour' style='min-width:300px;max-width:400px'> <div class='arrow'></div> <div class='welcome-title'>"+welcomeTitle+" <a href='' data-role='end'><i class='las la-times'></i></a></div> <div class='popover-content'></div> <div class='popover-navigation clearfix'>   <button class='btn btn-primary float-right' data-role='next'>Next »</button> </div> </div>",
            steps: welcomeStepsWeb});

        window.tour.init();
        window.tour.start();
    }

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


    $(document).on('click', '.full-menu-container .cover',function() {
       hideFullMenu();
    });


    $(document).on('click', '.ajax-action', function() {
        ajaxAction($(this).attr('href'));
        return false;
    });

    $(document).on('keyup', '.search-form input', function() {
        var parent = $(this).parent().parent();
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

    $(document).on('click', '#stripeButton', function() {
        var type = $(this).data('type');
        var price = $(this).data('price');
        var handler = StripeCheckout.configure({
            key: stripeKey,
            image: logo,
            locale: 'auto',
            currency: $(this).data('currency'),
            token: function (token) {
                $.ajax({
                    url: buildLink('payment/stripe', [
                        {key: 'type', value: type},
                        {key: 'price', value: price},
                        {key: 'token', value: token.id}
                    ]),
                    success: function (r) {
                        r = jQuery.parseJSON(r);
                        if (r.status == 1) {
                            window.location.href = r.url;
                        } else {
                            notify(r.message, 'error');
                        }
                    }
                })
            }
        });
        return false;
    });

});

function finishWelcomeScreen(t) {
    BigPicture({
        el: t,
        ytSrc: welcomeVideoId,
    });
    window.tour.end();
    return false;
}
function preivewDesign(t, p) {
    var o = $(t);
    var json = o.data('json');

    $(".btn-primary").each(function() {
        this.style.setProperty( 'background-color', p, 'important');
        this.style.setProperty('border-color', p, 'important');
    });

    return false;
}

function changeSaveDraftOption(t) {
    $('.draft-options').hide();
    if (t === 1) {
        $('#select-draft-collection').hide();
        $('#new-draft-collection').fadeIn();
    } else {
        $('#new-draft-collection').hide();
        $('#select-draft-collection').fadeIn();
    }
    return false;
}
function goBackSaveDraftOption() {
    $('#new-draft-collection').hide();
    $('#select-draft-collection').hide();
    $('.draft-options').fadeIn();
    return false;
}

function openSaveDraft() {
    if ($('.post-container .middle .menu-item').length < 1) {
        notify(strings.select_an_account_to_continue, 'error');
        return false;
    }
    $('#saveAsDraftModal').modal('show')
    return false;
}

function validateDraftPosting() {
    if ($('#draft-title-input').val() == '' && $('draft-select-input').val() == '') {
        return false;
    }
    $('#isDraftInput').val(1);
    $('.schedule-form').submit();
    return false;
}
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


function resetPluginForm(form) {
    $(form).data('not-ready', true);
    $(form).find('input[type=checkbox]').click();
    setTimeout(function() {
        $(form).removeAttr('data-not-ready');
        $(form).removeProp('data-not-ready');
        $(form).removeData('not-ready');
    })
}

function uploadFinished(image) {
    $("#file-upload-empty").remove();
    $('.uploads-files-container').append(image);
    reloadInit()
}

window.fromSelectAll = false;
function fileSelected(t) {
    var c = $(t);
    if (c.hasClass('selected') && !window.fromSelectAll) {
        c.removeClass('selected');
        c.find('input[type=checkbox]').removeProp('checked');
        c.find('input[type=checkbox]').removeAttr('checked');
        if ($('.post-container').length > 0) {
            postFileSelectedCallbackRemove(c)
        }

    } else {
        c.addClass('selected');
        c.find('input[type=checkbox]').prop('checked', 'checked');
        if ($('.post-container').length > 0) {
            postFileSelectedCallback(t);
        }
    }


}
function selectedFiles(t) {
    var c = $(t);
    if (c.data('selected')) {
        $('.each-file-pane').addClass('selected')
        $('.each-file-pane').each(function() {
            $(this).removeClass('selected');
            $(this).find('input[type=checkbox]').removeProp('checked');
            $(this).find('input[type=checkbox]').removeAttr('checked');
        });
        c.data('selected', false);
        c.html(strings.select_all);

    } else {
        window.fromSelectAll = true;
        //$('.each-file-pane').click();
        $('.each-file-pane').each(function() {
            $(this).addClass('selected');
            $(this).find('input[type=checkbox]').prop('checked', 'checked');
        });
        c.data('selected', true);
        c.html(strings.deselect);
    }
    window.fromSelectAll = false;
    return false;
}

function deleteSelectFiles() {
    var selected = 0;
    $('.each-file-pane').each(function() {
        if ($(this).find('input[type=checkbox]').prop('checked') === true) selected += 1;
    });

    if (selected > 0) {
       confirm('','','function',function() {
           $("#files-form-container").submit();
       })
    }

    return false;
}
function confirmFileDelete(files) {
    var files = files.split(',');
    for(var i=0;i<files.length;i++) {
        var pane = $(".file-item-"+files[i]).find('.item-pane')
        var rawValue = pane.data('raw');
        $('.selected-media-file').each(function() {
            if ($(this).val() === rawValue) {
                $(this).remove();
                //refresh preview
                refereshPreview();
            }
        });
        $(".file-item-"+files[i]).remove();

    }
}

function previewFile(t,file, type) {
    var files = [];
    var position = 1;
    var i = 0;
    $('.each-file-pane').each(function() {
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

var scope = ['https://www.googleapis.com/auth/drive.file'];
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
}

function confirmFolderCreate(c) {
    $("#newFolderModal").modal('hide');
    $('.uploads-files-container').prepend(c);
    reloadInit()
    return true;
}

function confirmFolderEdit(e) {
    var json = jQuery.parseJSON(e);
    $('.file-item-'+json.id).find('.folder-name').html(json.name);
    $('#editFolderModal'+json.id).modal('hide');
    return true;
}
// Create and render a Picker object for picking user Photos.
function createPicker() {
    if (pickerApiLoaded && oauthToken) {
        var view = new google.picker.View(google.picker.ViewId.DOCS);
        view.setMimeTypes("image/png,image/jpg,video/mp4");
        var picker = new google.picker.PickerBuilder()
            .enableFeature(google.picker.Feature.NAV_HIDDEN)
            .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
            .setOAuthToken(oauthToken)
            .addView(view)
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
                $.ajax({
                    type: "POST",
                    datatype: 'json',
                    url: buildLink('file-manager'),
                    data: {  file_id:fileId, file_name: file_name, file_size: file_size, oauthToken:oauthToken, google: true,folder_id: window.lastOpenFolder },
                    success: function(result){
                        pageLoaded();
                        var result = jQuery.parseJSON(result);
                        if (result.status == 1) {
                            notify(result.message,'success');
                            uploadFinished(result.content);
                        } else {
                            notify(result.message, 'error');
                        }
                    }
                });

            }
        }
    }else if (action == google.picker.Action.CANCEL) {

    }
}

function openGoogleDrivePicker() {
    gapi.load('auth', {'callback': onAuthApiLoad});
    gapi.load('picker', {'callback': onPickerApiLoad});
    return false;
}

function openDropboxPicker() {
    Dropbox.choose({
        linkType: "direct",
        success: function(files) {
            pageLoader(true);
            $.ajax({
                type: "POST",
                datatype: 'json',
                url: buildLink('file-manager'),
                data: {   file_name: files[0].name, file_size: files[0].bytes, file:files[0].link, dropbox: true ,folder_id: window.lastOpenFolder},
                success: function(result){
                    pageLoaded();
                    var result = jQuery.parseJSON(result);
                    if (result.status == 1) {
                        notify(result.message,'success');
                        uploadFinished(result.content);
                    } else {
                        notify(result.message, 'error');
                    }
                }
            });

        },
        extensions: ['.jpg', '.jpeg', '.mp4','.gif','.png']
    });
    return false;
}

function launchOneDrivePicker(){
    var odOptions = {
        clientId : onedriveKey,
        multiSelect: false,
        action: 'download',
        advanced: {
            redirectUri: buildLink("onedrive")
        },
        success: function(files) {

            var files = files.value[0];
            pageLoader(true);
            $.ajax({
                type: "POST",
                datatype: 'json',
                url: buildLink('file-manager'),
                data: {   file_name: files['name'], file_size: files['size'], file:files['@microsoft.graph.downloadUrl'], onedrive: true,folder_id: window.lastOpenFolder},
                success: function(result){
                    pageLoaded();
                    var result = jQuery.parseJSON(result);
                    if (result.status == 1) {
                        notify(result.message,'success');
                        uploadFinished(result.content);
                    } else {
                        notify(result.message, 'error');
                    }
                }
            });
        }
    };
    OneDrive.open(odOptions);
    return false;
}

window.openFolders = [];
window.lastOpenFolder=0;
function openFolder(id, fromPost) {

    if (jQuery.inArray(id, window.openFolders) === -1 && id !== 0) window.openFolders.push(id);
    window.lastOpenFolder= id;
    pageLoader(true);
    $.ajax({
        url: buildLink('file/open/folder', [{key: 'id', value: id},{key:'from', value: fromPost}]),
        success : function(r) {
            $('#filemanager-container').html(r);
            $('.file-folder-id-input').val(window.lastOpenFolder);
            reloadInit();
            pageLoaded();
            if ( id !== 0)$("#goback-folder-toggle").fadeIn();
        }
    })
    return false;
}

function goBackFolder(fromPost) {
    if (window.openFolders.length == 1) {
        if (fromPost === 1) {
            openFolder(0, fromPost);
        } else {
            load_page(buildLink('file-manager'));
        }
        $("#goback-folder-toggle").fadeOut();
    } else {
        window.openFolders.pop();
        openFolder(window.openFolders[window.openFolders.length -1], fromPost);
    }
    return false;
}


var instagramCodeType = 'challenge';
function processInstagramLogin(c) {

    if (c.error_type === 'general' && c.message !== 'challenge_required') {
        notify(c.message, 'error');
    } else if(c.error_type === 'enter-digit' || c.message === 'challenge_required') {
        $('#verificationModal').find('label').html(c['message']);
        instagramCodeType = "challenge";
        $('#verificationModal').find('.modal-title').html(strings.challenge_required);
        $('#verificationModal').modal("show")
    } else if(c.error_type === 'enter-digit-two-factor') {
        $('#verificationModal').find('label').html(c['message']);
        instagramCodeType = "twofactor";
        $('#verificationModal').find('.modal-title').html(strings.verification_required);
        $('#verificationModal').modal("show")
    }

}

function continueInstagramLogin() {
    var v = $('#verificationModal').find('input').val();
    if (v === '') return false;
    if (instagramCodeType == 'challenge') {
        $("#scode").val(v);
    } else {
        $("#vcode").val(v);
    }
    $('#verificationModal').modal("hide");
    $("#instagramAccountForm").submit();
}






window.currentTypes = []
function finilizeSelectedAccounts() {
    var currentType = [];
    $('.approved-post-types').html('');
    var isMultiple = false;
    $(".middle #horizontal-menu .menu .menu-item").each(function() {
       var type = $(this).data('type');
       if(jQuery.inArray(type, currentType) < 0) {
           currentType.push(type);
           $('.approved-post-types').append("<input type='hidden' name='val[types][]' value='"+type+"'/>");
       }
    });
    window.currentTypes = currentType;
    $('.post-menus-container .post-menu').hide();
   // if (currentType.length > 0)
    if (currentType.length > 1) {
        isMultiple = true;
        $('.post-menus-container .post-menu').hide();
        $('#general-post-menu').show();
    }
    $('.extra-form').hide();

    buildInstagramPost(isMultiple,currentType);
    buildFacebookPost(isMultiple,currentType);
    buildLinkedIn(isMultiple,currentType);
    buildTwitterPost(isMultiple,currentType);
    buildPinterestPost( isMultiple,currentType);
    buildVkPost(isMultiple,currentType);
    buildGooglePost(isMultiple,currentType);
    buildTumblrPost(isMultiple,currentType);
    buildRedditPost(isMultiple,currentType);
    buildVimeoPost(isMultiple,currentType);
    buildDailymotionPost(isMultiple,currentType);
    buildTelegramPost(isMultiple,currentType);
    buildYoutubePost(isMultiple,currentType);
    if (currentType.length > 1) {
        fileSelectorMediaType = 2;
        fileSelectorType = 1;
    }
    refereshPreview();

    if ($(".middle #horizontal-menu .menu .menu-item").length < 1) {
        fileSelectorMediaType = 2;
        fileSelectorType = 1;
        $("#post-type-input").val("media");
        $('.media-preview').show();
        removeAllSelectdFiles();
    }
}

function unselectFile(o) {
    o.removeClass('selected');
    o.find('input[type=checkbox]').removeProp('checked');
    o.find('input[type=checkbox]').removeAttr('checked');
}
function postFileSelectedCallbackRemove(c) {
    $(".selected-media-"+c.data('id')).remove();
    refereshPreview();
}
function postFileSelectedCallback(t) {
    var o = $(t);
    if (fileSelectorType === 0 || fileSelectorType === -1) {

        $(".each-file-pane").removeClass('selected');
        $(".each-file-pane").find('input[type=checkbox]').removeProp('checked');
        $(".each-file-pane").find('input[type=checkbox]').removeAttr('checked');
        removeAllSelectdFiles();
        if (fileSelectorType === -1) {
            notify(strings.no_media_allow_for_post, 'error');
            return unselectFile(o);
        }
    }
    if(fileSelectorMediaType === 0) {
        if (o.data('type') === 'video') {
            notify(strings.please_select_image, 'error');
            return unselectFile(o);
        }
    } else if(fileSelectorMediaType === 1) {
        if (o.data('type') === 'image') {
            notify(strings.please_select_video, 'error');
            return unselectFile(o);
        }
    }
    o.addClass('selected');
    o.find('input[type=checkbox]').prop('checked', 'checked');

    if(o.data('type') === 'image') {
        $(".post-selected-media").append("<div class='each selected-media-"+o.data('id')+"' style='background-image:url("+o.data('file')+")'><a onclick='return removePostEditorMedia(this,"+o.data('id')+")' href=''><i class='la la-times'></i></a><input type='hidden' data-url='"+o.data('file')+"' data-type='"+o.data('type')+"' value='"+o.data('raw')+"' name='val[media][]' class='selected-media-file selected-media-"+o.data('id')+"'/></div>");
    } else {
        $(".post-selected-media").append("<div class='each selected-media-"+o.data('id')+"'><div class='video-content' style='width:100%;height:100%;overflow: hidden;position:relative;'><video src='"+o.data('file')+"' playsinline='' muted='' loop=''></video></div><a onclick='return removePostEditorMedia(this,"+o.data('id')+")' href=''><i class='la la-times'></i></a><input type='hidden' data-url='"+o.data('file')+"' data-type='"+o.data('type')+"' value='"+o.data('raw')+"' name='val[media][]' class='selected-media-file selected-media-"+o.data('id')+"'/></div>");
    }
    reloadInit();
    //we can call the social callbacks to render preview
    refereshPreview();
}

function removePostEditorMedia(t, id) {
    $(t).parent().remove();
    $('.file-item-'+id).find('.each-file-pane').removeClass('selected');
    refereshPreview();
    return false;
}

function initPostSchedule(t) {
    var o = $(t);
    if (o.prop('checked') == true) {
        $('.schedule-post').find('input[type=text]').removeProp('disabled');
        $('.schedule-post').find('input[type=text]').removeAttr('disabled');
        $('.post-submit-btn').html(strings.schedule_now);
        $('.schedule-more-form').show();
    } else {
        $('.schedule-post').find('input[type=text]').prop('disabled', 'disabled');
        $('.post-submit-btn').html(strings.post_now);
        $('.schedule-more-form').hide();
    }
    //reloadInit()
}

function loadCaptions() {
    var c = $(".caption-list");
    if (c.html() === '') {
        c.html("<div class='loader-image'><img src='"+loaderImage+"'/></div>");
        $.ajax({
            url: buildLink('captions', [{key: 'load', value: 1}]),
            success: function(data) {
                c.html(data);
            }
        })
    }
    c.parent().fadeIn();
    return false;
}

function useCaption(t) {
    var el = $(".post-text textarea").emojioneArea();
    el[0].emojioneArea.setText($(t).data('content'));
    closeLoadCaptions();
    return false;
}

function closeLoadCaptions() {
    $('.use-caption-container').fadeOut();
    return false;
}

function saveCaptions() {
    var el = $(".post-text textarea").emojioneArea();
    var t = el[0].emojioneArea.getText();
    if (t === '') return false;
    pageLoader(true);
    $.ajax({
        type: 'POST',
        data: {text: t},
        url: buildLink('captions', [{key: 'save', value:true}]),
        success: function(data) {
            pageLoaded();
            notify(data, 'success')
        }
    })
    return false;
}

function validatePosting() {
    if($(".middle #horizontal-menu .menu .menu-item").length < 1) {
        notify(strings.select_an_account_to_continue, 'error');
        return false;
    }

    return true;
}

function switchInstagramMenu(type, t) {
    $("#instagram-post-menu a").removeClass('active');
    $(t).addClass('active');
    $(".watermark-form").hide();
    $(".story-form").hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $(".instagram-post-form").show();
            $('.media-preview').show();
            break;
        case 'story':
            fileSelectorMediaType = 2;
            fileSelectorType = 0;
            $("#post-type-input").val("story");
            $(".story-form").show();
            $(".instagram-post-form").show();
            $('.media-preview').show();
            break;
        case 'livestream':
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("livestream");
            $(".instagram-post-form").hide();
            $(".watermark-form").show();
            $('.media-preview').show();
            break;
        case 'album':
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("album");
            $(".instagram-post-form").show();
            $('.media-preview').show();
            break;
    }
    refereshPreview();
    return false;
}

function switchGeneralMenu(type, t) {
    $("#general-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'link':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("link");
            $('.link-input').show();
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}

function switchFacebookMenu(type, t) {
    $("#facebook-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    $(".watermark-form").hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'livestream':
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("livestream");
            $(".watermark-form").show();
            $('.media-preview').show();
            break;
        case 'link':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("link");
            $('.link-input').show();
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}

function switchYoutube(type, t) {
    $("#youtube-post-menu a").removeClass('active');
    $(t).addClass('active');
    $(".youtube-categories").show();
    $('.title-input').show();
    $(".watermark-form").hide();
    $('.media-preview').hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'livestream':
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("livestream");
            $(".watermark-form").show();
            $('.media-preview').show();
            break;

    }
    refereshPreview();
    return false;
}

function switchVimeo(type, t) {
    $("#vimeo-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.title-input').show();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;

    }
    refereshPreview();
    return false;
}

function switchDailymotion(type, t) {
    $("#dailymotion-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.title-input').show();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;

    }
    refereshPreview();
    return false;
}


function switchLinkedInMenu(type, t) {
    $("#linkedin-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'link':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("link");
            $('.link-input').show();
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview .media-preview-main').html('')
            $('.media-preview').hide();
            break;
    }
    refereshPreview();
    return false;
}

function switchTumblrMenu(type, t) {
    $("#tumblr-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'link':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("link");
            $('.link-input').show();
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}

function switchRedditMenu(type, t) {
    $("#reddit-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    $('.title-input').show();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'link':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("link");
            $('.link-input').show();
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}

function switchTelegramMenu(type, t) {
    $("#telegram-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    $('.title-input').show();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'link':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("link");
            $('.link-input').show();
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}
function switchVkMenu(type, t) {
    $("#vk-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'media':
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
            $('.media-preview').show();
            break;
        case 'link':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("link");
            $('.link-input').show();
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}

function switchTwitterMenu(type, t) {
    $("#twitter-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.link-input').hide();
    removeAllSelectdFiles()
    switch(type) {
        case 'photo':
            fileSelectorMediaType = 0;
            fileSelectorType = 1;
            $("#post-type-input").val("photo");
            $('.media-preview').show();
            break;
        case 'video':
            fileSelectorMediaType = 1;
            fileSelectorType = 1;
            $("#post-type-input").val("video");
            $('.media-preview').show();
            break;
        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}

function switchGoogleMenu(type, t) {
    $("#google-post-menu a").removeClass('active');
    $(t).addClass('active');
    $('.google-form-bottom').show();
    removeAllSelectdFiles();
    switch(type) {
        case 'media':
            fileSelectorMediaType = 0;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.google-form-bottom').show();
            $('.media-preview').show();
            break;

        case 'text':
            fileSelectorMediaType = 2;
            fileSelectorType = -1;
            $("#post-type-input").val("text");
            removeAllSelectdFiles();
            $('.media-preview').hide();
            $('.media-preview .media-preview-main').html('')
            break;
    }
    refereshPreview();
    return false;
}

function removeAllSelectdFiles() {
    $(".each-file-pane").removeClass('selected');
    $(".each-file-pane").find('input[type=checkbox]').removeProp('checked');
    $(".each-file-pane").find('input[type=checkbox]').removeAttr('checked');
    $('.media-preview-template').show();
    $('.post-selected-media').html('');
    $('.media-preview .media-preview-main').html('')
}


function buildInstagramPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.instagram-post-form').hide();
    } else {

        if (jQuery.inArray('instagram', currentType) > -1) {
            $("#instagram-post-menu").fadeIn();
            $('.instagram-post-form').show();
            fileSelectorMediaType = 2;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
        }
    }
}

function buildFacebookPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.link-input').hide();
        $(".watermark-form").hide();
    } else {
        if (jQuery.inArray('facebook', currentType) > -1) {
            $("#facebook-post-menu").fadeIn();
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
        }

    }
}

function  buildLinkedIn(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {

    } else {
        if (jQuery.inArray('linkedin', currentType) > -1) {
            $("#linkedin-post-menu").fadeIn();
            fileSelectorMediaType = 2;
            fileSelectorType =1;
            $("#post-type-input").val("media");
        }

    }
}
window.notifyYoutubeError = true;
function buildYoutubePost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {

        //we need to scan through and remove all non youtube
        if (jQuery.inArray('youtube', currentType) > -1) {
            //if (window.notifyYoutubeError) notify(strings.you_post_youtube_only, 'error');
            window.notifyYoutubeError = false;
            $('#horizontal-menu .menu-item').each(function() {
                if ($(this).data('type') !== 'youtube' && $(this).data('type') !== 'vimeo' && $(this).data('type') !== 'dailymotion') {
                    $(this).find('a').click();
                }
            });
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.title-input').show();

        } else {
            $(".youtube-categories").hide();
            $('.title-input').hide();
        }
    } else {
        if (jQuery.inArray('youtube', currentType) > -1) {
            $("#youtube-post-menu").fadeIn();
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $(".youtube-categories").show();
            $('.title-input').show();
        }

    }
}

function buildTwitterPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.link-input').hide();
    } else {
        if (jQuery.inArray('twitter', currentType) > -1) {

            $("#twitter-post-menu").fadeIn();
            fileSelectorMediaType = 0;
            fileSelectorType =1;
            $("#post-type-input").val("photo");
        }

    }
}

function buildPinterestPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.pinterest-form-bottom').hide();
    } else {

        if (jQuery.inArray('pinterest', currentType) > -1) {
            fileSelectorMediaType = 0;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.pinterest-form-bottom').show();
            $('.media-preview').show();
        }

    }

}

function buildVkPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.link-input').hide();
    } else {
        if (jQuery.inArray('vk', currentType) > -1) {
            $("#vk-post-menu").fadeIn();
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
        }

    }
}

function buildGooglePost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.google-form-bottom').hide();
    } else {
        if (jQuery.inArray('google', currentType) > -1) {
            $("#google-post-menu").fadeIn();
            fileSelectorMediaType = 0;
            fileSelectorType = 0;
            $("#post-type-input").val("photo");
        }

    }
}

function buildRedditPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.title-input').hide();
    } else {
        if (jQuery.inArray('reddit', currentType) > -1) {
            $("#reddit-post-menu").fadeIn();
            fileSelectorMediaType = 2;
            fileSelectorType = 0;
            $("#post-type-input").val("photo");
            $('.title-input').show();
        }

    }
}

function buildTelegramPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.title-input').hide();
    } else {
        if (jQuery.inArray('telegram', currentType) > -1) {
            $("#telegram-post-menu").fadeIn();
            fileSelectorMediaType = 2;
            fileSelectorType = 0;
            $("#post-type-input").val("photo");
            $('.title-input').show();
        }

    }
}

function buildTumblrPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {
        $('.link-input').hide();
    } else {
        if (jQuery.inArray('tumblr', currentType) > -1) {
            $("#tumblr-post-menu").fadeIn();
            fileSelectorMediaType = 2;
            fileSelectorType = 1;
            $("#post-type-input").val("media");
        }

    }
}

function buildVimeoPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {

        //we need to scan through and remove all non youtube
        if (jQuery.inArray('vimeo', currentType) > -1) {
            window.notifyYoutubeError = false;
            $('#horizontal-menu .menu-item').each(function() {
                if ($(this).data('type') !== 'youtube' && $(this).data('type') !== 'vimeo' && $(this).data('type') !== 'dailymotion') {
                    $(this).find('a').click();
                }
            });
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.title-input').show();

        } else {

            $('.title-input').hide();
        }
    } else {
        if (jQuery.inArray('vimeo', currentType) > -1) {
            $("#vimeo-post-menu").fadeIn();
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.title-input').show();
        }

    }
}

function buildDailymotionPost(isMultiple, currentType) {
    if (window.currentTypes.length > 1) {

        //we need to scan through and remove all non youtube
        if (jQuery.inArray('dailymotion', currentType) > -1) {
            window.notifyYoutubeError = false;
            $('#horizontal-menu .menu-item').each(function() {
                if ($(this).data('type') !== 'youtube' && $(this).data('type') !== 'vimeo' && $(this).data('type') !== 'dailymotion') {
                    $(this).find('a').click();
                }
            });
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.title-input').show();

        } else {

            $('.title-input').hide();
        }
    } else {
        if (jQuery.inArray('dailymotion', currentType) > -1) {
            $("#vimeo-post-menu").fadeIn();
            fileSelectorMediaType = 1;
            fileSelectorType = 0;
            $("#post-type-input").val("media");
            $('.title-input').show();
        }

    }
}

function getPostHasMedia() {
    var result = [];
    $('.selected-media-file').each(function() {
       result.push({
           url : $(this).data('url'),
           type : $(this).data('type')
       }) ;
    });

    return result;
}
function displayFacebookMedias(medias, container) {
    var imagesContainer = $("<div class='preview-images-container clearfix'></div>");
    if (medias.length  < 1) {
        $("#"+container+" .media-preview-main").html('');
        $("#"+container+" .media-preview-template").show();
        return false;
    }
    for(var i=0;i<medias.length;i++) {
        var media = medias[i];
        if (i<=5) {
            var div = $("<div class='each'></div>");
            if(media.type === 'image') {
                div.css("background-image", 'url(' + media.url+')')
            } else {
                div.html("<video src='"+media.url+"' playsinline='' muted='' loop=''></video>")
            }
            if (medias.length > 5 && i === 4) {
                var count = medias.length - 5;
                div.append("<span class='count'>+"+count+"</span>")
            }
            imagesContainer.append(div)
        }
    }
    var className = 'single';
    if (medias.length === 2) {
        className = 'double';
    } else if(medias.length === 3) {
        className = 'tripple';
    } else if(medias.length === 4) {
        className = 'four'
    } else if(medias.length > 4) {
        className = 'five';
    }
    imagesContainer.addClass(className);
    $("#"+container+" .media-preview-main").html('')
    $("#"+container+" .media-preview-main").html(imagesContainer);
    $("#"+container+" .media-preview-template").hide();
    $("#"+container+" .media-preview-main").show();
}

function displayGeneralOneMedia(medias, container) {
    var imagesContainer = $("<div class='preview-images-container clearfix'></div>");
    var div = $("<div class='each'></div>");
    if (medias.length < 1) {
        $("#"+container+" .media-preview-main").html('');
        $("#"+container+" .media-preview-template").show();
        return false;
    }
    var media = medias[0];
    if(media.type === 'image') {
        div.css("background-image", 'url(' + media.url+')')
    } else {
        div.html("<video src='"+media.url+"' playsinline='' muted='' loop=''></video>")
    }
    if (medias.length > 5 && i === 4) {
        var count = medias.length - 5;
        div.append("<span class='count'>+"+count+"</span>")
    }
    imagesContainer.append(div);
    imagesContainer.addClass('single');
    $("#"+container+" .media-preview-main").html(imagesContainer);
    $("#"+container+" .media-preview-template").hide();
    $("#"+container+" .media-preview-main").show();
    $('.media-preview').show();
}

function displayInstagramMedia(medias, multiple) {
    if (multiple !== undefined) {
        var carouseContainer = $('<div id="instagramCarousel" class="carousel slide preview-images-container" data-interval="false"></div>');
        var indicators = $('<ol class="carousel-indicators"></ol>');
        var inners = $('<div class="carousel-inner"></div>');
        if (medias.length > 0) {
            for(var i=0;i<medias.length;i++) {
                var media = medias[i];
                var active = (i === 0) ? 'active' : '';
                indicators.append('<li data-target="#instagramCarousel" data-slide-to="'+i+'" class="'+active+'"></li>');
                var div = $('<div class="carousel-item '+active+' each"></div>');
                if(media.type === 'image') {
                    div.css("background-image", 'url(' + media.url+')')
                } else {
                    div.html("<video src='"+media.url+"' playsinline='' muted='' loop=''></video>")
                }
                inners.append(div);
            }
            carouseContainer.append(indicators);
            carouseContainer.append(inners);
            var container = 'instagram-normal-preview';
            $("#"+container+" .media-preview-main").html('')
            $("#"+container+" .media-preview-main").html(carouseContainer);
            $("#"+container+" .media-preview-template").hide();
            $("#"+container+" .media-preview-main").show();
            carouseContainer.carousel()
        } else {
            var container = 'instagram-preview';
            $("#"+container+" .media-preview-main").html('');
            $("#"+container+" .media-preview-template").show();
        }
    } else {
        displayGeneralOneMedia(medias, 'instagram-normal-preview');
    }
}
window.instagramLivePreview = false;
window.facebookLivePreview = false;
window.instagramStoryPreview = false;
function refereshPreview() {
    reloadInit();
    var anyActiveTab = false;
    $(".right .menu-container .menu a").each(function() {
        if ($(this).hasClass('active') && $(this).attr('id') !== 'general-preview-toggle') anyActiveTab = true;
    });

    try {
        var text = $(".post-container .emoji-text")[0].emojioneArea.getText();
    } catch (e){}

    if (text !== '') {
        $(".text-container-template").hide();
        $('.text-container').show();
        $('.text-container').html(sanitize(text));
    } else {
        $(".text-container-template").show();
        $(' .text-container').hide();
    }

    var medias = getPostHasMedia();
    if ($('.middle .menu-container .menu .menu-item').length < 1) {
        if(medias.length > 0) {
            displayFacebookMedias(medias, 'general-preview')
        } else {
            $("#general-preview .media-preview-main").html('');
            $("#general-preview .media-preview-template").show();
        }
        $("#general-post-menu").show();
        $('#general-preview-toggle').show();

        $(".right .menu-container .menu a").removeClass('active');
        $("#general-preview-toggle").addClass('active');
        $('#general-preview').addClass('active');

    } else {
        $('#general-preview-toggle').hide();
        $('#general-preview').removeClass('active');
    }
    previewInstagram(medias);
    previewGoogle(medias);
    previewVk(medias);
    previewGoogle(medias);
    previewFacebook(medias);
    previewLinkedIn(medias);
    previewTwitter(medias);
    previewTumblr(medias);
    previewYoutube(medias);
    previewPinterest(medias)

    previewVimeo(medias);
    previewDailymotion(medias);
    previewReddit(medias);
    previewTelegram(medias);

    if(window.currentTypes.length > 1) {
        var o = $('#' + currentTypes[0]+ '-preview-toggle');
        o.tab('show');
    }

}
function previewInstagram(medias) {
    if (jQuery.inArray('instagram', window.currentTypes) > -1) {
        $("#instagram-preview-toggle").show();
        $("#instagram-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#instagram-preview-toggle").addClass('active');
            $("#instagram-preview").addClass('active');
            $("#instagram-preview-toggle").tab('show');

        }
    } else {
        $("#instagram-preview").removeClass('active');
        $("#instagram-preview-toggle").hide();
    }
    $('.instagram-livestream').hide();
    $('.instagram-normal').hide();
    $('.instagram-story').hide();

    if ($("#instagram-post-menu").css('display') !== 'none' ) {

        if ($("#instagram-post-menu a:nth-child(2)").hasClass('active')) {
            $('.instagram-story').show();
            displayGeneralOneMedia(medias, "instagram-preview")
        }
        else if($("#instagram-post-menu a:nth-child(3)").hasClass('active')) {
            if ($("#instagram-post-menu a:nth-child(3)").hasClass('its-album')) {
                $('.instagram-normal').show();
                displayInstagramMedia(medias, true);
            } else {
                $('.instagram-livestream').show();
                displayGeneralOneMedia(medias, "instagram-preview")
            }
        } else if($("#instagram-post-menu a:nth-child(4)").hasClass('active')) {
            $('.instagram-normal').show();
            displayInstagramMedia(medias, true)
        } else {
            $('.instagram-normal').show();
            displayInstagramMedia(medias);
        }
    } else {
        $('.instagram-normal').show();
        displayInstagramMedia(medias, true);
    }
}
function previewGoogle(medias) {
    if (jQuery.inArray('google', window.currentTypes) > -1) {
        $("#google-preview-toggle").show();
        $("#google-preview-toggle").css('display', 'inline-block')

        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#google-preview-toggle").addClass('active');
            $("#google-preview").addClass('active');
            $("#google-preview-toggle").tab('show')
        }
    } else {
        $("#google-preview-toggle").hide();
        $("#google-preview").removeClass('active');
    }
    displayGeneralOneMedia(medias,'google-preview');
}
function previewVk(medias) {
    if (jQuery.inArray('vk', window.currentTypes) > -1) {
        $("#vk-preview-toggle").show();
        $("#vk-preview-toggle").css('display', 'inline-block')

        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#vk-preview-toggle").addClass('active');
            $("#vk-preview").addClass('active');
            $("#vk-preview-toggle").tab('show')
        }
    } else {
        $("#vk-preview-toggle").hide();
        $("#vk-preview").removeClass('active');
    }
    displayFacebookMedias(medias,'vk-preview');
}

function previewReddit(medias) {
    if (jQuery.inArray('reddit', window.currentTypes) > -1) {
        $("#reddit-preview-toggle").show();
        $("#reddit-preview-toggle").css('display', 'inline-block')

        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#reddit-preview-toggle").addClass('active');
            $("#reddit-preview").addClass('active');
            $("#telegram-preview-toggle").tab('show')
        }
    } else {
        $("#reddit-preview-toggle").hide();
        $("#reddit-preview").removeClass('active');
    }
    displayGeneralOneMedia(medias,'reddit-preview');
}

function previewTelegram(medias) {
    if (jQuery.inArray('telegram', window.currentTypes) > -1) {
        $("#telegram-preview-toggle").show();
        $("#telegram-preview-toggle").css('display', 'inline-block')

        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#telegram-preview-toggle").addClass('active');
            $("#telegram-preview").addClass('active');
            $("#telegram-preview-toggle").tab('show')
        }
    } else {
        $("#telegram-preview-toggle").hide();
        $("#telegram-preview").removeClass('active');
    }
    displayGeneralOneMedia(medias,'telegram-preview');
}
function previewFacebook(medias) {
    if (jQuery.inArray('facebook', window.currentTypes) > -1) {
        $("#facebook-preview-toggle").show();
        $("#facebook-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#facebook-preview-toggle").addClass('active');
            $("#facebook-preview").addClass('active');
            $("#facebook-preview-toggle").tab('show');

        }
    } else {
        $("#facebook-preview-toggle").hide();
        $("#facebook-preview").removeClass('active');
    }

    $('.fb-live-btn').hide();
    $('.facebook-preview .media-preview').show();

    if ($("#facebook-post-menu").css('display') !== 'none' ) {

        if ($("#facebook-post-menu a:nth-child(3)").hasClass('active')) {
            displayGeneralOneMedia(medias, "facebook-preview");
            $('.fb-live-btn').show();
        } else {
            displayFacebookMedias(medias,'facebook-preview');
        }
    } else {
        displayFacebookMedias(medias,'facebook-preview');
    }
}
function previewLinkedIn(medias) {
    if (jQuery.inArray('linkedin', window.currentTypes) > -1) {
        $("#linkedin-preview-toggle").show();
        $("#linkedin-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#linkedin-preview-toggle").addClass('active');
            $("#linkedin-preview").addClass('active');
            $("#linkedin-preview-toggle").tab('show')
        }
    } else {
        $("#linkedin-preview-toggle").hide();
        $("#linkedin-preview").removeClass('active');
    }

    displayFacebookMedias(medias,'linkedin-preview');
}
function previewTwitter(medias) {
    if (jQuery.inArray('twitter', window.currentTypes) > -1) {
        $("#twitter-preview-toggle").show();
        $("#twitter-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#twitter-preview-toggle").addClass('active');
            $("#twitter-preview").addClass('active');
            $("#twitter-preview-toggle").tab('show')
        }
    } else {
        $("#twitter-preview-toggle").hide();
        $("#twitter-preview").removeClass('active');
    }

    displayFacebookMedias(medias,'twitter-preview');
}
function previewTumblr(medias) {
    if (jQuery.inArray('tumblr', window.currentTypes) > -1) {
        $("#tumblr-preview-toggle").show();
        $("#tumblr-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#tumblr-preview-toggle").addClass('active');
            $("#tumblr-preview-toggle").tab('show')
            $("#tumblr-preview").addClass('active');
        }
    } else {
        $("#tumblr-preview-toggle").hide();
        $("#tumblr-preview").removeClass('active');
    }
    displayGeneralOneMedia(medias,'tumblr-preview');
}
function previewYoutube(medias) {
    if (jQuery.inArray('youtube', window.currentTypes) > -1) {
        $("#youtube-preview-toggle").show();
        $("#youtube-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#youtube-preview-toggle").addClass('active');
            $("#youtube-preview").addClass('active');
            $("#youtube-preview-toggle").tab('show')
        }
    } else {
        $("#youtube-preview-toggle").hide();
        $("#youtube-preview").removeClass('active');
    }



    displayGeneralOneMedia(medias, "youtube-preview");
}

function previewVimeo(medias) {
    if (jQuery.inArray('vimeo', window.currentTypes) > -1) {
        $("#vimeo-preview-toggle").show();
        $("#vimeo-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#vimeo-preview-toggle").addClass('active');
            $("#vimeo-preview").addClass('active');
            $("#vimeo-preview-toggle").tab('show')
        }
    } else {
        $("#vimeo-preview-toggle").hide();
        $("#vimeo-preview").removeClass('active');
    }

    displayGeneralOneMedia(medias, "vimeo-preview");
}

function previewDailymotion(medias) {
    if (jQuery.inArray('dailymotion', window.currentTypes) > -1) {
        $("#dailymotion-preview-toggle").show();
        $("#dailymotion-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#dailymotion-preview-toggle").addClass('active');
            $("#dailymotion-preview").addClass('active');
            $("#dailymotion-preview-toggle").tab('show')
        }
    } else {
        $("#dailymotion-preview-toggle").hide();
        $("#dailymotion-preview").removeClass('active');
    }

    displayGeneralOneMedia(medias, "dailymotion-preview");
}

function previewPinterest(medias) {
    if (jQuery.inArray('pinterest', window.currentTypes) > -1) {
        $("#pinterest-preview-toggle").show();
        $("#pinterest-preview-toggle").css('display', 'inline-block')
        if(window.currentTypes.length < 2) {
            $(".right .menu-container .menu a").removeClass('active');
            $("#pinterest-preview-toggle").addClass('active');
            $("#pinterest-preview").addClass('active');
            $("#pinterest-preview-toggle").tab('show')
        }
    } else {
        $("#pinterest-preview-toggle").hide();
        $("#pinterest-preview").removeClass('active');
    }
    displayGeneralOneMedia(medias, "pinterest-preview");
}

function resetPosting() {
    return false;
    removeAllSelectdFiles();
    $('#horizontal-menu .menu-item').each(function() {
        $(this).find('a').click();
    });
    $(".post-container .emoji-text")[0].emojioneArea.setText('');
    if ($('.schedule-input').prop('checked') === 'checked') {
        $('.schedule-input').click();
    }
    $('.pin-link-input').val('');
    $('.instagram-first-comment').val('');
    $('.location-input').val('')
    $('.story-link-input').val('')
    $('.youtube-tags').val('');
    $('.call-link-input').val('')
    $('.link-input input').val('');
    $('.title-input input').val('')
}

window.locationInterval = null;
window.lastLocationText = '';
window.locationIsSearching = false;
function fetchLocations(t) {
    var text = $(t).val();
    if (text !== '') {
        $('.instagram-locations-container').html("<img src='"+loaderImage+"' class='loader'/>");
        $('.instagram-locations-container').fadeIn();
        if (window.locationInterval === null) {
            window.locationInterval = setInterval(function() {
                text =  $('.location-input').val();
                if (window.lastLocationText != '') {

                    if (window.lastLocationText == text) {
                        if (!window.locationIsSearching) {
                            window.locationIsSearching = true;
                            $.ajax({
                                url: buildLink('post', [{key:'fetchlocation', value: text}]),
                                success: function(data) {
                                    $('.instagram-locations-container').html(data);

                                    clearInterval(window.locationInterval);
                                    window.lastLocationText = '';
                                    window.locationInterval = null;
                                    window.locationIsSearching = false;
                                }
                            })
                        }
                    }

                }


            }, 4000);
        }
        window.lastLocationText = text;
    } else {
        $('.instagram-locations-container').hide();
    }
}

function selectInstagramLocation(t) {
    $('.instagram-locations-container a input').removeProp('checked');
    $('.instagram-locations-container a input').removeAttr('checked');
    $(t).find('input').prop('checked', 'checked');
    $(t).find('input').attr('checked', 'checked');
    return false;
}

function showActivationMessage() {
    $("#activationEmailSuccess").fadeIn();
}

function switchPricing(t, type) {
    $(".pricing-container .menu li a").removeClass('active');
    $(t).addClass('active');

    if (type === 'monthly') {
        $('.yearly-plans').hide();
        $('.monthly-plans').show();
    } else {
        $('.monthly-plans').hide();
        $('.yearly-plans').show();
    }
    return false;
}

function  switchPlanSelect(t) {
    $('.plan-select-container a').removeClass('selected');
    $('.plan-select-container a input').removeProp('checked');
    $('.plan-select-container a input').removeAttr('checked');
    $(t).addClass('selected');
    $(t).find('input').prop('checked', 'checked');
    return false;
}

function openImageEditor(img) {
    var url = buildLink('image/editor', [{key: 'id', value: img}]);
    $("#image-editor-overlay .content").html("<iframe src='"+url+"'></iframe>");
    $("#image-editor-overlay").fadeIn();
    return false;
}

function closeImageEditor() {
    $("#image-editor-overlay").fadeOut();
    load_page(window.location.href);
    return false;
}

function openGroupModal(url) {
    var modal = $("#groupModal");
    modal.find('.modal-body').html("<img src='"+loaderImage+"' style='width:40px;height:40px;display: block;margin: 50px auto;'/>");
    modal.modal('show');
    $.ajax({
        url : url,
        success: function(data) {
            modal.find('.modal-body').html(data);
        }
    })
    return false;
}

function showFullMenu() {

    if ($("#app-container").hasClass('opened-menu')) {
        $("#app-container").removeClass('opened-menu');
        $.ajax({
            url : buildLink('close/menu')
        });
    } else {
        $("#app-container").addClass('opened-menu');
        $.ajax({
            url : buildLink('open/menu')
        });
    }
    if ($("#app-container").hasClass('mobile-open-menu')) {
        $("#app-container").removeClass('mobile-open-menu');

    } else {
        $("#app-container").addClass('mobile-open-menu');
        if ($(document).width() <= 800) {
            $("#app-container").addClass('opened-menu');
        }
    }
    $(".modern-scroll").each(function() {
        $(this).getNiceScroll().remove()
    });
    reloadInit();
    return false;
}

function hideMenu() {

    if ($(document).width() <= 800) {
        $("#app-container").removeClass('mobile-open-menu');
        $("#app-container .right-pane .inner-container .inner-left-pane").hide();
    }
    if ($(document).width() <= 1000) {
        $("#app-container .right-pane .inner-container .inner-left-pane").hide();
    }
    $(".modern-scroll").each(function() {
        $(this).getNiceScroll().remove()
    });
    reloadInit();
}

function openSubMenu() {
    if ($("#app-container .right-pane .inner-container .inner-left-pane").css('display') == 'none') {
        $("#app-container .right-pane .inner-container .inner-left-pane").fadeIn();
    } else {
        $("#app-container .right-pane .inner-container .inner-left-pane").hide();
    }
    reloadInit();
    return false;
}
function switchPostContent(t, w) {
    $('.post-mobile-menu a').removeClass('active');
    $(t).addClass('active');
    $('.post-container .left, .post-container .middle, .post-container .right').hide();
    $('.post-container .'+w).fadeIn();[]
    return false;
}

function renderAnalyticCharts(element, label, data, name, type){
    var chart_color = ["rgba(255,0,94,0.7)", "rgba(255,117,136, 0.7)", "rgba(255,168,125,0.7)", "rgba(156,39,176,0.7)", "rgba(28,188,216,0.7)", "rgba(64,78,103,0.7)"];

    var ctx2 = document.getElementById(element).getContext("2d");

    // Chart Options
    var userPageVisitOptions = {
        responsive: true,
        maintainAspectRatio: false,
        pointDotStrokeWidth : 2,
        legend: {
            display: true,
            labels: {
                fontColor: '#404e67',
                boxWidth: 10,
            },
            position: 'bottom',
        },
        hover: {
            mode: 'nearest',
            intersect: true
        },
        tooltips: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            xAxes: [{
                display: true,
                ticks: {
                    display: true,
                },
            }],
            yAxes: [{
                display: true,
                gridLines: {
                    drawTicks: true,
                    drawBorder: true,
                    drawOnChartArea: true
                },
                ticks: {
                    display: true,
                    maxTicksLimit: 5,
                    beginAtZero: true,
                    userCallback: function(label, index, labels) {
                        // when the floored value is the same as the value we have a whole number
                        if (Math.floor(label) === label) {
                            return label;
                        }

                    },
                },
            }]
        },
        title: {
            display: false,
            text: 'Report last 30 days'
        },
    };

    var data_set = [];
    var count_data = data.length;

    for (var i = 0; i < count_data; i++) {
        if(type =="line"){
            data_set.push({
                label: name[i],
                data: data[i],
                backgroundColor: "transparent",
                borderColor: chart_color[i],
                pointBorderColor: chart_color[i],
                pointRadius: 2,
                pointBorderWidth: 2,
                pointHoverBorderWidth: 2,
            });
        }else{
            data_set.push({
                label: name[i],
                data: data[i],
                backgroundColor: chart_color[i],
                borderColor: "transparent",
                pointBorderColor: "transparent",
                pointRadius: 2,
                pointBorderWidth: 2,
                pointHoverBorderWidth: 2,
            });
        }
    }

    // Chart Data
    var userPageVisitData = {
        labels: label,
        datasets: data_set
    };

    var userPageVisitConfig = {
        type: 'line',
        // Chart Options
        options : userPageVisitOptions,
        // Chart Data
        data : userPageVisitData,
    };

    // Create the chart
    var stackedAreaChart = new Chart(ctx2, userPageVisitConfig);
}

function changeWaterMark(t) {

    if ($(t).val() === '2') {
        $(".watermark-text").fadeIn();
    } else {
        $(".watermark-text").hide();
    }
}

function doLinkPreview(t) {
    if($(t).val() === '') {
        $('.link-preview').html('');
        $('.link-preview').hide();
    } else {
        $('.link-preview').html("<img src='"+loaderImage+"' class='loaderImage'/>");
        $('.link-preview').fadeIn();
        $.ajax({
            url: buildLink('post/fetch/link'),
            data : {link : $(t).val()},
            success: function(d) {
                if (d === '') {
                    $('.link-preview').html('');
                    $('.link-preview').hide();
                } else {
                    $('.link-preview').html(d);
                }
            }
        });
    }
    return false;
}

function addPostRule() {
    var c = $('.rules-template-container').html();
    c = c.toString().split("placeholder").join($('.posts-rule-container .each-post-rule').length)
    c = c.split("datepicker-input-2").join("datepicker-input")
    $('.posts-rule-container').append(c);
    setTimeout(function() {
        reloadInit()
    }, 300);
    return false;
}
function removePostRule(t) {
    $(t).parent().parent().remove();
    return false;
}
function activeRuleDay(t) {
    var a = $(t);
    if (a.hasClass('active')) {
        a.removeClass('active');
        a.find('input').val(0);
    } else {
        a.addClass('active');
        a.find('input').val(1);
    }
    return false;
}

function submitCsvImport() {
    $('.csv-uploader').submit();
    return false;
}