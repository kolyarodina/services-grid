(function ($) {
    'use strict';

    $(function () {
        var frame;

        $(document).on('click', '.sg-service-gallery-add', function (e) {
            e.preventDefault();

            var $wrapper = $('#sg-service-gallery-wrapper');
            var $input = $('#sg_service_gallery_ids');
            var $container = $wrapper.find('.sg-service-gallery-images');

            var currentIds = $input.val()
                ? $input.val().split(',').map(function (id) { return parseInt(id, 10); })
                : [];

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: 'Оберіть зображення для галереї',
                button: { text: 'Використати зображення' },
                multiple: true
            });

            frame.on('open', function () {
                if (currentIds.length) {
                    var selection = frame.state().get('selection');
                    currentIds.forEach(function (id) {
                        var attachment = wp.media.attachment(id);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] : []);
                    });
                }
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection');
                var ids = [];

                $container.empty();

                selection.each(function (attachment) {
                    attachment = attachment.toJSON();
                    ids.push(attachment.id);

                    var src = (attachment.sizes && attachment.sizes.thumbnail)
                        ? attachment.sizes.thumbnail.url
                        : attachment.url;

                    $container.append(
                        '<div class="sg-service-gallery-item" data-id="' + attachment.id + '">' +
                            '<img src="' + src + '" alt=""/>' +
                        '</div>'
                    );
                });

                $input.val(ids.join(','));
            });

            frame.open();
        });

        $(document).on('click', '.sg-service-gallery-clear', function (e) {
            e.preventDefault();
            $('#sg_service_gallery_ids').val('');
            $('#sg-service-gallery-wrapper .sg-service-gallery-images').empty();
        });
    });

})(jQuery);
