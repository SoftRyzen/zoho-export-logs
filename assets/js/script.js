jQuery( function( $ ) {

    'use strict';

    /******************************************************************
     * Home
     * @type {{init: ExportLogs.init, install: ExportLogs.install}}
     * @since 1.0
     * @author Alex Cherniy
     * @date 27.03.2023
     */
    var ExportLogs = {

        /**
         * Init
         */
        init: function ()
        {

            this.install  = this.install( this )


        },

        /**
         * Install
         */
        install: function()
        {

            $( document ).on(
                'click',
                '.exportLogsStart',
                this.export )

        },

        /**
         * Export
         */
        export: function(e)
        {

            e.preventDefault()

            let $this = $(this),
                $file = $('#exportLogsDateFile'),
                $date_start = $('#exportLogsDateStart'),
                $date_end = $('#exportLogsDateEnd'),
                $format = $('#exportLogsFormat')

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'goit_export_logs',
                    file: $file.val(),
                    date_start: $date_start.val(),
                    date_end: $date_end.val(),
                    format: $format.val()
                },
                beforeSend: function()
                {

                    $('.exportLogsMessage').html('')
                    $this.attr('disabled', 'disabled')
                    $file.attr('disabled', 'disabled')
                    $date_start.attr('disabled', 'disabled')
                    $date_end.attr('disabled', 'disabled')
                    $format.attr('disabled', 'disabled')

                },
                complete: function()
                {

                    $this.removeAttr('disabled')
                    $file.removeAttr('disabled')
                    $date_start.removeAttr('disabled')
                    $date_end.removeAttr('disabled')
                    $format.removeAttr('disabled')

                },
                success: function (response)
                {

                    $('.exportLogsMessage').html(response.data.message)

                }
            })

        },

    }

    ExportLogs.init()

});
