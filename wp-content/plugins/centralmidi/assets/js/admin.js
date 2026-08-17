/**
 * Central MIDI Admin — media picker for the artist photo field.
 */
jQuery(function ($) {
    'use strict';

    var frame = null;
    var $input = $('#centralmidi_artista_foto');
    var $preview = $('#centralmidi_artista_foto_preview');

    if (!$input.length) {
        return;
    }

    $('#cm-artista-foto-choose').on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Selecionar foto do artista',
            library: { type: 'image' },
            multiple: false,
            button: { text: 'Usar esta foto' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.id);
            var src = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;
            $preview.attr('src', src).show();
        });

        frame.open();
    });

    $('#cm-artista-foto-remove').on('click', function (e) {
        e.preventDefault();
        $input.val('');
        $preview.attr('src', '').hide();
    });
});