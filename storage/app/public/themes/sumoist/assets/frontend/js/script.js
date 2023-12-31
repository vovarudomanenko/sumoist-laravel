$(document).ready(function(){
    $("#beanEater").delay().fadeOut();
})
window.addEventListener("load", function(event) {

    $('.drawer').drawer();

    $.notify.defaults({
        position: 'bottom right',
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    var mySwiper = new Swiper('.swiper-container', {
        // Optional parameters
        loop: true,
        autoplay: true,
        // If we need pagination
        pagination: {
            el: '.swiper-pagination',
        },
        // Navigation arrows
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        // And if we need scrollbar
        scrollbar: {
            el: '.swiper-scrollbar',
        },
    })



    // !!!!!!!!!!!!!!!! init modal !!!!!!!!!!!!!!!!!!!!//

    new layerok({
        trigger: '[data-layerok="#show-sign-modal"]'
    });

    new layerok({
        trigger: '[data-layerok="#show-sign-modal_2"]'
    });


    // Form validation

    $.jMaskGlobals = {
        translation: {
            'n': {pattern: /\d/}
        }
    };

    $('input[data-type="phone"]').each(function (key, value) {
        $(value).mask('+38(0nn) nnn-nn-nn');
    });

    jQuery.validator.addMethod("phone", function(value, element) {
        // allow any non-whitespace characters as the host part
        return this.optional( element ) || /^\+?3?8?\(?[0-9]{3}\)?\s?[0-9]{3}\-?[0-9]{2}\-?[0-9]{2}$/.test( value );
    }, 'Введіть дійсний телефонний номер');



    $('.checkout-form').each(function(){
        $(this).validate({
            submitHandler: function (form, event) {

                $("#checkout-loader").addClass('db');
                $("#checkout-loader").removeClass('dn');

                $("#place-order").addClass('dn');
                $("#place-order").removeClass('db');
                event.preventDefault();
                console.log(form);
                $(form).ajaxSubmit({
                    dataType: 'json',
                    success: function (response) {
                        //console.log(res);
                        //let response = JSON.parse(res);
                        //console.log(response);
                        let messages = {
                            37: 'Перевірте ще раз введений номер телефону',
                            33: 'Введіть дійсний email'
                        }
                        if(response.hasOwnProperty('error')){
                            if(messages.hasOwnProperty(response.error)){

                                $.notify(messages[response.error], 'error');
                            }else{
                                $.notify(response.message, 'error');
                            }
                        }else if(response.hasOwnProperty('payment_id')){
                            console.log(response);
                            let formHolder = $('#form-holder');
                            formHolder.html(response.form);
                            formHolder.css('display', 'none');
                            let form = formHolder.find('form');
                            form.submit();

                        }else if(response.hasOwnProperty('cartIsEmpty')){
                            document.location.reload()
                        }
                        else{
                            console.log(response);
                            window.location.href = '/thankyou';
                        }



                        $("#checkout-loader").addClass('dn');
                        $("#checkout-loader").removeClass('db');

                        $("#place-order").addClass('db');
                        $("#place-order").removeClass('dn');

                    },
                    error: function () {
                        $.notify('Ошибка', 'error')
                        $("#checkout-loader").addClass('dn');
                        $("#checkout-loader").removeClass('db');

                        $("#place-order").addClass('db');
                        $("#place-order").removeClass('dn');
                    }
                });
            },
            rules: {
                phone: {
                    required: true,
                    phone: true,
                },
                email: {
                    email: true
                }
            },
            messages: {
                phone: {
                    required: "Будь ласка, заповніть це поле",
                },
                email:{
                    email: "Введіть дійсний email"
                }
            }
        });
    })


});




