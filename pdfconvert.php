<?php
/*
  Plugin Name: Конвертор PDF в PNG
  Description: Конвертор для преобразования научных трудов.
  Version: 1.0
  Author: Maks
 */

define('PDFCONVERT_DIR', plugin_dir_path(__FILE__));
define('PDFCONVERT_URL', plugin_dir_url(__FILE__));

add_action('admin_menu', 'pdf_menu');
add_action('init', 'pdfconvert_load');

function my_action_callback() {
    $whatever = intval($_POST['whatever']);
    $whatever += 10;
    echo $whatever;
    wp_die(); // выход нужен для того, чтобы в ответе не было ничего лишнего, только то что возвращает функция
}

function pdf_menu() {
    add_menu_page('Компонент конвертирования PDF to PNG', 'PDF Конвертор', 8, 'pdfconvert_log', 'pdfconvert_log');
}

function pdfconvert_log() {
    global $wpdb;
    $sql = 'SELECT
                wp2.post_title AS rubric,
                wp.ID as id_post,
                wp.post_title AS title,
                wp1.ID AS id_attache,
                wp1.guid AS url_attache,
                wp1.post_date
              FROM ' . $wpdb->posts . ' wp
                JOIN ' . $wpdb->posts . ' wp1
                  ON wp1.post_parent = wp.id
                  AND wp1.post_type = \'attachment\'
                  AND wp1.post_mime_type = \'application/pdf\'
                JOIN ' . $wpdb->posts . ' wp2 ON 
                  wp.post_parent=wp2.ID
              WHERE wp.post_parent IN (410, 470, 540,675,689)
              AND wp.post_status = \'publish\'
             ORDER BY wp1.post_date DESC;';
    $result = $wpdb->get_results($sql);
    ?>
    <style>
        .dashicons:hover {
            color: #d54e21;
            cursor: pointer;
        }
    </style>
    <script>
        function updatePNG(id) {
            var data = {
                action: 'generatepng',
                id_attache: id
            };
            jQuery('#btn-action-' + id).hide();
            jQuery('#btn-load-' + id).show();
            // с версии 2.8 'ajaxurl' всегда определен в админке
            jQuery.post(ajaxurl, data)
                    .done(function (response) {
                        if (response == 'ok') {
                            //location.reload();
                            jQuery('#btn-load-' + id).hide();
                            jQuery('#btn-action-' + id).show();
                        } else {
                            alert(response);
                        }
                    })
                    .fail(function (xhr, status, error) {
                        //alert();
                    });

        }

    </script>
    <div class="wrap">
        <h1 class="wp-heading-inline">Лог целостности PDF - PNG</H1>
        <a href="<?= admin_url(); ?>post-new.php?post_type=page" class="page-title-action">Добавить новую страницу</a>
        <hr class="wp-header-end">
        <table class="wp-list-table widefat striped posts">
            <thead>
                <tr>
                    <th scope="col" id="title" class="manage-column column-title sortable desc">
                        <a href="#">
                            <span>id</span>
                        </a>
                    </th>
                    <th scope="col" id="title" class="manage-column column-title sortable desc">
                        <a href="#">
                            <span>Название раздела</span>
                        </a>
                    </th>
                    <th scope="col" id="title" class="manage-column column-title column-primary sortable desc">
                        <a href="#">
                            <span>Название публикации</span>
                        </a>
                    </th>
                    <th scope="col" id="title" class="manage-column column-title sortable desc">
                        <a href="#">
                            <span>Файл</span>
                        </a>
                    </th>
                    <th scope="col" id="title" class="manage-column column-title sortable desc">
                        <a href="#">
                            <span>Кол. стр</span>
                        </a>
                    </th>
                    <th scope="col" id="title" class="manage-column column-title sortable desc">
                        <a href="#">
                            <span>Дата</span>
                        </a>
                    </th>
                    <th scope="col" id="title" class="manage-column column-title sortable desc">
                        <a href="#">
                            <span>Статус</span>
                        </a>
                    </th>
                    <th scope="col" id="title" class="manage-column column-title sortable desc">
                        <a href="#">
                            <span></span>
                        </a>
                    </th>
                </tr>
            </thead>
            <body>
                <?php if (empty($result)): ?>
                <tr><td colspan="7">Нет данных</td></tr>
                <?php
            else:
                $url = get_option('siteurl');

                foreach ($result as $row) :
                    $file = ABSPATH . ltrim(str_replace($url, "", $row->url_attache), '/');
                    $countinPDF = getCountPage($file);
                    $countImage = getCountFile($row->id_attache);
                    ?>
                    <tr id="post-746" class="iedit author-self level-0 post-746 type-post status-publish format-standard hentry category-1">
                        <th scope="row" class="title has-row-actions column-primary page-title"><?= $row->id_attache; ?></th>
                        <th scope="row" class="title has-row-actions column-primary page-title"><?= $row->rubric; ?></th>
                        <th scope="row" class="title column-title has-row-actions column-primary page-title"><a href="<?= admin_url(); ?>post.php?post=<?= $row->id_post; ?>&action=edit"><?= $row->title; ?></a></th>
                        <th scope="row" class="title has-row-actions column-primary page-title"><a href="<?= $row->url_attache; ?>">посмотреть</a></th>
                        <th scope="row" class="title has-row-actions column-primary page-title"><?= $countinPDF . '/' . $countImage ?></th>
                        <th scope="row" class="title has-row-actions column-primary page-title"><?= $row->post_date ?></th>
                        <th scope="row" class="title has-row-actions column-primary page-title"><?= ($countinPDF == $countImage) ? '<span class="pll_icon_tick"></span>' : '' ?></th>
                        <th scope="row" class="title has-row-actions column-primary page-title" >
                            <div id="btn-load-<?= $row->id_attache ?>" style="display:none;" >...</div>
                            <div id="btn-action-<?= $row->id_attache ?>" class="dashicons dashicons-share-alt2" onclick="javascript:updatePNG(<?= $row->id_attache ?>);" ><br></div>
                        </th>
                    </tr>

                    <?php
                endforeach;
            endif;
            ?>
            </body>
        </table>

    </div>
    <?php
}

function pdfconvert_load() {
    global $wp_query;
    if (is_admin()) {
        add_action('wp_ajax_generatepng', 'pdfconvert_createPNG_callback');
    } else {
		
    }
}

function pdfconvert_activation() {
    // действие при активации
}

function pdfconvert_deactivation() {
    // при деактивации
}

function getCountPage($file) {
    $stream = fopen($file, "r");
    $content = fread($stream, filesize($file));
    $count = 0;
    $regex = "/\/Count\s+(\d+)/";
    $regex2 = "/\/Page\W*(\d+)/";
    $regex3 = "/\/N\s+(\d+)/";
    if (preg_match_all($regex, $content, $matches))
        $count = max($matches);
    return $count[0];
}

function getCountFile($id) {
    $pathimage = ABSPATH . 'wp-content/uploads/pdfimage/' . $id;
    $listimage = scandir($pathimage);
    $ret = 0;
    foreach ($listimage as $f) {
        if (preg_match('/.*-\d+\.png$/', $f))
            $ret++;
    }
    return $ret;
}

function pdfconvert_createPNG_callback() {
    global $wpdb;
    if (isset($_POST['id_attache']) && $_POST['id_attache'] > 0) {
        $sql = 'SELECT
                    ID,  guid 
                  FROM ' . $wpdb->posts . '
                  WHERE  id=' . (int) $_POST['id_attache'] . '
                    AND post_type = \'attachment\'
                    AND post_mime_type = \'application/pdf\' LIMIT 1';
        $result = $wpdb->get_row($sql, OBJECT);
        $url = get_option('siteurl');
        $file = ABSPATH . ltrim(str_replace($url, "", $result->guid), '/');
        $p = ABSPATH . 'wp-content/uploads/pdfimage/' . $result->ID;
        try {
            if (!is_file($file))
                throw new Exception('Не найден исходный PDF ' . $file);


            if (!is_dir($p))
                if (!mkdir($p))
                    throw new Exception('Нет возможности создать папку ' . $p . '');
            exec("convert -density 100 -quality 100 $file" . "  $p/page.png");
            echo 'ok';
        } catch (Exception $exc) {
            echo $exc->getMessage();
            wp_die();
        }
    }
    wp_die();
}
