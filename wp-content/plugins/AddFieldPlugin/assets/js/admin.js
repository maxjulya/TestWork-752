jQuery(document).ready(function ($) {

    'use strict';

    console.log(1111);

    /*
         * действие при нажатии на кнопку загрузки изображения
         * вы также можете привязать это действие к клику по самому изображению
         */
    $('.upload_image_button').click(function( event ){

        event.preventDefault();

        const button = $(this);

        const customUploader = wp.media({
            title: 'Выберите изображение плз',
            library : {
                // uploadedTo : wp.media.view.settings.post.id, // если для метобокса и хотим прилепить к текущему посту
                type : 'image'
            },
            button: {
                text: 'Выбрать изображение' // текст кнопки, по умолчанию "Вставить в запись"
            },
            multiple: false
        });

        // добавляем событие выбора изображения
        customUploader.on('select', function() {
            console.log(2222);

            const image = customUploader.state().get('selection').first().toJSON();

            button.parent().prev().attr( 'src', image.url );
            button.prev().val( image.id );

        });

        // и открываем модальное окно с выбором изображения
        customUploader.open();
    });
    /*
     * удаляем значение произвольного поля
     * если быть точным, то мы просто удаляем value у <input type="hidden">
     */
    $('.remove_image_button').click(function( event){

        event.preventDefault();

        if ( true == confirm( "Уверены?" ) ) {
            const src = $(this).parent().prev().data('src');
            $(this).parent().prev().attr('src', src);
            $(this).prev().prev().val('');
        }
    });



});