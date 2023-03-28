<?php
session_start();

class ExportLogs
{

    const PACKAGE_VERSION = '1.0.0';

    public function __construct()
    {

        add_action( 'admin_menu', [ $this, 'create_page' ] );

        add_action('admin_enqueue_scripts', [ $this, 'scripts' ], 10, 1);

        add_action( 'admin_init', [ $this, 'add_ajax_actions' ] );

    }

    /**
     * @return void
     */
    public function add_ajax_actions()
    {
        add_action( 'wp_ajax_goit_export_logs', [ $this, 'export' ], 99 );

    }

    /**
     * Create Admin Menu Page
     * @return void
     */
    public function create_page()
    {

        add_submenu_page(
            'tools.php',
            __( 'Zoho Export Logs', 'exportlogs' ),
            __( 'Zoho Export Logs', 'exportlogs' ),
            'manage_options',
            'zoho-export-logs',
            [ $this, 'render_page' ]
        );

    }

    /**
     * Script and Style
     * @return false|void
     */
    public function scripts()
    {

        $current_screen = get_current_screen();

        if ( 'tools_page_zoho-export-logs' !== $current_screen->id ) return false;

        wp_enqueue_style(
            'exportlogs',
            ZOHO_EXPORT_LOGS_PLUGIN_URL . 'assets/css/main.css',
            [],
            self::PACKAGE_VERSION
        );

        wp_enqueue_script(
            'exportlogs',
            ZOHO_EXPORT_LOGS_PLUGIN_URL . 'assets/js/script.js',
            array(),
            false,
            true
        );
    }

    /**
     * Render Page
     * @return void
     */
    public function render_page()
    {

        $files = $this->scanDir();

        ?>
        <div class="wrap errorlogs">
            <h2><?php echo get_admin_page_title() ?></h2>

            <?php if( $files ) { ?>

                <div class="errorlogs__container">

                    <div class="errorlogs__container-group">
                        <div>
                            <h5 class="errorlogs__container-group--title"><?php _e('Выберите лог', 'exportlogs' ); ?></h5>
                        </div>
                        <div>
                            <select name="file" id="exportLogsDateFile">
                                <option value=""><?php _e('Выбрать', 'exportlogs' ); ?></option>
                                <?php  foreach ( $files as $file ) { ?>
                                    <?php
                                    $path = parse_url($file, PHP_URL_PATH);
                                    $filename = basename($path) . PHP_EOL;
                                    ?>
                                    <option value="<?= $file ?>"><?= $filename ?></option>
                                <?php } ?>
                            </select>

                        </div>
                    </div>

                    <div>
                        <div>
                            <h5 class="errorlogs__container-group--title"><?php _e('Период', 'exportlogs' ); ?></h5>
                        </div>
                        <div class="errorlogs__container-group--dates">
                            <input type="date" name="date_start" id="exportLogsDateStart">
                            <input type="date" name="date_end" id="exportLogsDateEnd">
                        </div>
                    </div>

                </div>


                <div style="padding-top: 15px">
                    <button type="button" class="components-button is-primary exportLogsStart errorlogs__container-submit"><?php _e('Экспорт', 'exportlogs' ); ?></button>
                </div>

                <div class="exportLogsMessage errorlogs__container-message"></div>

            <?php } ?>
        </div>
        <?php

    }

    /**
     * Scaning Dir
     * @return array|false|void
     */
    private function scanDir()
    {

        try{

            $dir = glob(get_template_directory() . '/assets/crm/*.log');
            return $dir;

        }
        catch(Exception $e){

            $log = sprintf('File - %s, Line - %s | %s', __FILE__, __LINE__, $e->getMessage());

            file_put_contents(ZOHO_EXPORT_LOGS_PLUGIN_PATH . 'logs/log_'.date("j.n.Y").'.log', $log, FILE_APPEND);

        }

    }

    /**
     * Start Export
     * @return void
     */
    public function export()
    {

        try{

            $error = null;

            $file = $_POST['file'];
            $date_start = $_POST['date_start'];
            $date_end = $_POST['date_end'];

            if( empty($file) ) $error .= '<div>' . __( 'Не выбран файл лога', 'exportlogs' ) . '</div>';
            if( empty($date_start) ) $error .= '<div>' . __( 'Не указана дата начала', 'exportlogs' ) . '</div>';
            if( empty($date_end) ) $error .= '<div>' . __( 'Не указана дата конца', 'exportlogs' ) . '</div>';

            if ( !empty($error) )
            {
                wp_send_json_error([
                    'message' => $error
                ]);
            }

            $result = $this->parse($file, $date_start, $date_end);

            if( $result )
            {

                $uploads = wp_upload_dir();
                $dir = $uploads['basedir'];
                $url = $uploads['baseurl'];
                $json_data = json_encode($result, JSON_UNESCAPED_UNICODE);
                $path_parts = pathinfo($file);
                $filename = $path_parts['filename'] . '-' . $date_start . '-' . $date_end . '.json';

                file_put_contents($dir . '/' . $filename, $json_data);

                $return_url = $url . '/' . $filename;
                wp_send_json_success([
                    'message' => '<a href="' . $return_url . '" target="_blank">' . $return_url . '</a>'
                ]);

            }else{
                wp_send_json_error([
                    'message' => '<div>' . __( 'Пустой результат', 'exportlogs' ) . '</div>'
                ]);
            }

        }
        catch(Exception $e){

            $log = sprintf('File - %s, Line - %s | %s', __FILE__, __LINE__, $e->getMessage());

            file_put_contents(ZOHO_EXPORT_LOGS_PLUGIN_PATH . 'logs/log_'.date("j.n.Y").'.log', $log, FILE_APPEND);

        }

    }

    /**
     * Parse Log File
     * @param $file
     * @param $date_start
     * @param $date_end
     * @return false|string|void
     */
    private function parse($file, $date_start, $date_end)
    {

        try{

            $data = file_get_contents($file);
            $data = rtrim($data,',');
            $data = '[' . $data . ']';
            $data = str_replace(':::', ':""', $data);

            $data = json_decode($data, true, 1024, JSON_THROW_ON_ERROR);

            if( $data )
            {

                $rangeStart = strtotime(date('d-m-Y', strtotime($date_start)));
                $rangeEnd = strtotime(date('d-m-Y', strtotime($date_end)));

                $filtered_events = array_filter( $data, function($var) use ($rangeStart, $rangeEnd) {
                    $evtime = strtotime(date('d-m-Y', strtotime($var['date'])));
                    return $evtime <= $rangeEnd && $evtime >= $rangeStart;
                });

                return $filtered_events;
            }else{
                return false;
            }

        }
        catch(Exception $e){

            $log = sprintf('File - %s, Line - %s | %s', __FILE__, __LINE__, $e->getMessage());

            file_put_contents(ZOHO_EXPORT_LOGS_PLUGIN_PATH . 'logs/log_'.date("j.n.Y").'.log', $log, FILE_APPEND);

        }

    }

}

new ExportLogs();
