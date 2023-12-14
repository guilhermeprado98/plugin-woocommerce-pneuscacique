<?php
/*
Plugin Name: CUPOM Pneus Cacique
Description: Personalizações de CUPOM de desconto e Relatório de Vendas.
Version: 2.0
Author: Guilherme Prado
 */

?>


<?php

function include_bootstrap()
{
    if (isset($_GET['page']) && $_GET['page'] === 'pneus-cacique') {

        wp_enqueue_style('bootstrap', plugin_dir_url(__FILE__) . '/include/css/bootstrap.min.css', array(), '4.3.1');
    }
}

add_action('admin_enqueue_scripts', 'include_bootstrap', 99999);

?>


<?php

function criar_tabela_relatorio_pneus_cacique()
{
    global $wpdb;
    $nome_tabela = $wpdb->prefix . 'relatoriopneuscacique';

    $query = "CREATE TABLE IF NOT EXISTS $nome_tabela (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produto VARCHAR(255),
        negociacao INT,
        participacao_vendas_pesquisas VARCHAR(255),
        vendas INT DEFAULT 0
    );";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($query);
}

register_activation_hook(__FILE__, 'criar_tabela_relatorio_pneus_cacique');

// Função para verificar e adicionar colunas à tabela
function verificar_e_adicionar_colunas()
{
    global $wpdb;

    $tabela = $wpdb->prefix . 'relatoriopneuscacique';

    $coluna1 = 'criar_cupom_count';
    $coluna2 = 'continue_atendimento_count';

    $colunas_existentes = $wpdb->get_col("DESCRIBE $tabela", 0);

    if (!in_array($coluna1, $colunas_existentes)) {

        $wpdb->query("ALTER TABLE $tabela ADD $coluna1 int(11) DEFAULT 0");
    }

    if (!in_array($coluna2, $colunas_existentes)) {

        $wpdb->query("ALTER TABLE $tabela ADD $coluna2 int(11) DEFAULT 0");
    }

}

// Hook para executar a função ao iniciar o WordPress
add_action('init', 'verificar_e_adicionar_colunas');

function adicionar_botao_pagina_produto()
{

    if (is_product()) {
        global $product;

        $arquivo_botao = plugin_dir_path(__FILE__) . 'adicionar_botao.php';

        if (file_exists($arquivo_botao)) {
            include $arquivo_botao;
        }
    }
}
add_action('woocommerce_after_add_to_cart_button', 'adicionar_botao_pagina_produto', 998);

function adicionar_menu_pneus_cacique()
{
    add_menu_page(
        'Pneus Cacique',
        'Relatório de Vendas',
        'manage_options',
        'pneus-cacique',
        'exibir_pagina_relatorio_produtos',
        'dashicons-store'
    );

}

function registrar_visualizacao_produto()
{
    if (is_product() && !is_admin()) {
        global $post;

        $product_id = $post->ID;

        $views = get_post_meta($product_id, 'woocommerce_views', true);
        $views = $views ? (int) $views + 1 : 1;
        update_post_meta($product_id, 'woocommerce_views', $views);
    }
}
add_action('wp', 'registrar_visualizacao_produto');

function atualizar_vendas_produto($order_id, $order)
{
    global $wpdb;

    foreach ($order->get_items() as $item_data) {
        $nome_produto = $item_data->get_name();
        $quantidade = $item_data->get_quantity();

        if ($quantidade == 0) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE wp_relatoriopneuscacique SET vendas = 1 WHERE produto = %s",
                    $nome_produto
                )
            );
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE wp_relatoriopneuscacique SET vendas = vendas + %d WHERE produto = %s",
                    $quantidade,
                    $nome_produto
                )
            );
        }
    }
}

add_action('woocommerce_new_order', 'atualizar_vendas_produto', 10, 2);

function obter_quantidade_pedidos_produto($product_id)
{

    $today = date('Y-m-d');
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));

    global $wpdb;

    $query = $wpdb->prepare(
        "SELECT SUM(opl.product_qty)
        FROM {$wpdb->prefix}wc_order_product_lookup as opl
        INNER JOIN {$wpdb->prefix}posts as p
        ON opl.order_id = p.ID
        WHERE opl.product_id = %d
        AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        $product_id
    );

    $total_sales_last_30_days = $wpdb->get_var($query);

    return $total_sales_last_30_days;
}

function obter_quantidade_negociacoes_produto($product_name)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'relatoriopneuscacique';

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT negociacao
            FROM $table_name
            WHERE produto = %s",
            $product_name
        )
    );

    return $result;
}

function vendas_cupom_last_mounth($sku)
{

    global $wpdb;

    $cupom_name = 'compre-agora-' . $sku . '';

    $result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT pc.post_title AS coupon_name,
            pc.post_excerpt AS coupon_description,
            Max(CASE WHEN pmc.meta_key = 'discount_type'      AND  pc.`ID` = pmc.`post_id` THEN pmc.`meta_value` END) AS discount_type,
            Max(CASE WHEN pmc.meta_key = 'coupon_amount'      AND  pc.`ID` = pmc.`post_id` THEN pmc.`meta_value` END) AS coupon_amount,
            Max(CASE WHEN pmc.meta_key = 'product_ids'        AND  pc.`ID` = pmc.`post_id` THEN pmc.`meta_value` END) AS product_ids,
            Max(CASE WHEN pmc.meta_key = 'product_categories' AND  pc.`ID` = pmc.`post_id` THEN pmc.`meta_value` END) AS product_categories,
            Max(CASE WHEN pmc.meta_key = 'customer_email'     AND  pc.`ID` = pmc.`post_id` THEN pmc.`meta_value` END) AS customer_email,
            Max(CASE WHEN pmc.meta_key = 'usage_limit'        AND  pc.`ID` = pmc.`post_id` THEN pmc.`meta_value` END) AS usage_limit,
            Max(CASE WHEN pmc.meta_key = 'usage_count'        AND  pc.`ID` = pmc.`post_id` THEN pmc.`meta_value` END) AS total_usaged,
            po.ID AS order_id,
            MAX(CASE WHEN pmo.meta_key = '_billing_email'      AND po.ID = pmo.post_id THEN pmo.meta_value END) AS billing_email,
            MAX(CASE WHEN pmo.meta_key = '_billing_first_name' AND po.ID = pmo.post_id THEN pmo.meta_value END) AS billing_first_name,
            MAX(CASE WHEN pmo.meta_key = '_billing_last_name'  AND po.ID = pmo.post_id THEN pmo.meta_value END) AS billing_last_name,
            MAX(CASE WHEN pmo.meta_key = '_order_total'        AND po.ID = pmo.post_id THEN pmo.meta_value END) AS order_total
     FROM `wp_posts` AS pc
     INNER JOIN `wp_postmeta` AS pmc ON  pc.`ID` = pmc.`post_id`
     INNER JOIN `wp_woocommerce_order_items` AS woi ON pc.post_title = woi.order_item_name
         AND woi.order_item_type = 'coupon'
     INNER JOIN `wp_posts` AS po ON woi.order_id = po.ID
         AND po.post_type = 'shop_order'
         AND po.post_status IN ('wc-completed', 'wc-processing', 'wc-refunded', 'wc-retirar-na-loja')
     INNER JOIN `wp_postmeta` AS pmo ON po.ID = pmo.post_id
     WHERE pc.post_type = 'shop_coupon'
     AND pc.post_title LIKE %s
     GROUP BY pc.post_title,
              pc.post_excerpt,
              po.ID
     ORDER BY po.ID DESC
     ",
            '%' . $cupom_name . '%'
        )
    );

    $num_rows = count($result);

    return $num_rows;

}

function exibir_pagina_relatorio_produtos()
{
    if (isset($_GET['pesquisar_produtos'])) {
        $search_query = sanitize_text_field($_GET['pesquisar_produtos']);
    } else {
        $search_query = '';
    }

    if (isset($_GET['atualizar_relatorio'])) {

        wp_redirect(admin_url('admin.php?page=pneus-cacique'));
        exit;
    }

    $post_per_page = 20;

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $post_per_page,
        'paged' => max(1, intval($_GET['paged'])),
        's' => $search_query, // Use the paged parameter from $_GET
    );

    $query = new WP_Query($args);

    echo '<form method="get" action="" style="margin-top: 20px;">';
    echo '<input type="hidden" name="page" value="pneus-cacique">';
    echo '<input type="text" name="pesquisar_produtos" placeholder="Pesquisar por nome..." style="margin-right:10px"; value="' . esc_attr($search_query) . '">';
    echo '<input type="submit" style="margin-bottom: 10px; margin-right: 10px" name="atualizar_relatorio" value="Atualizar Relatório" class="btn btn-primary">';
    echo '<input type="submit" name="pesquisar" style="margin-bottom: 10px;" value="Pesquisar Produto" class="btn btn-primary">';
    echo '</form>';

    echo '<table class="table table-bordered">';
    echo '<thead class="thead-light">';
    echo '<tr>';
    echo '<th scope="col">Produto</th>';
    echo '<th scope="col">Pesquisas</th>';
    echo '<th scope="col">Vendas</th>';
    echo '<th scope="col">Negociação</th>';
    echo '<th scope="col">Participação Vendas x Pesquisas</th>';
    echo '<th scope="col">Participação Negociação x Pesquisas</th>';
    echo '<th scope="col">Geraram CUPOM</th>';
    echo '<th scope="col">Continuaram atendimento</th>';
    echo '<th scope="col">Vendas Cupom (30 dias)</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while ($query->have_posts()) {
        global $product;
        $query->the_post();
        $product_id = get_the_ID();
        $product_name = get_the_title();

        $numero_pesquisas = get_post_meta($product_id, 'woocommerce_views', true);

        $numero_vendas = obter_quantidade_pedidos_produto($product_id);

        $quantidade_negociacao = obter_quantidade_negociacoes_produto($product_name);

        $percentual_vendas = 0;
        if ($numero_pesquisas > 0) {
            $percentual_vendas = ($numero_vendas / $numero_pesquisas) * 100;
        }

        global $wpdb;

        global $wpdb;
        $nome_tabela = $wpdb->prefix . 'relatoriopneuscacique';
        $product_name = get_the_title();

        $existe_produto = $wpdb->get_var(
            $wpdb->prepare("
                SELECT COUNT(*)
                FROM $nome_tabela
                WHERE produto = %s
            ", $product_name)
        );

        if ($existe_produto) {
        } else {

            $wpdb->update(
                $nome_tabela,
                array('Produto' => $product_name),
                array('Negociacao' => 0),
                array('vendas' => 0),
                array('%s')
            );
        }

        $table_name = $wpdb->prefix . 'relatoriopneuscacique';

        $data = array(
            'participacao_vendas_pesquisas' => $percentual_vendas,
        );
        $data_format = array(
            '%d',
        );

        $where = array(
            'produto' => $product_name,
        );

        $wpdb->update($table_name, $data, $where, $data_format);

        $percentual_negociacao = 0;
        if ($numero_pesquisas > 0 && $quantidade_negociacao != 0) {
            $percentual_negociacao = ($quantidade_negociacao / $numero_pesquisas) * 100;
        }

        $count_criar_cupom = $wpdb->get_var(
            $wpdb->prepare("
                SELECT criar_cupom_count
                FROM $table_name
                WHERE produto = %s
            ", $product_name)
        );

        $count_continuar_atendimento = $wpdb->get_var(
            $wpdb->prepare("
                SELECT continue_atendimento_count
                FROM $table_name
                WHERE produto = %s
            ", $product_name)
        );

        $product = wc_get_product($product_id);
        $sku = $product->get_sku();
        $count_vendas_cupom_last_30_day = vendas_cupom_last_mounth($sku);

        echo '<tr>';
        echo '<td>' . get_the_title() . '</td>';
        echo '<td>' . $numero_pesquisas . '</td>';
        echo '<td>' . $numero_vendas . '</td>';
        echo '<td>' . $quantidade_negociacao . '</td>';
        echo '<td>' . ceil($percentual_vendas) . '%</td>';
        echo '<td>' . ceil($percentual_negociacao) . '%</td>';
        echo '<td>' . $count_criar_cupom . '</td>';
        echo '<td>' . $count_continuar_atendimento . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    $total_posts = $query->found_posts;
    $total_pages = ceil($total_posts / $post_per_page);
    $current_page = max(1, intval($_GET['paged']));

    if ($total_pages >= 1) {
        echo '<nav aria-label="Page navigation">';
        echo '<ul class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            // Update pagination link to include the paged parameter
            $pagination_url = esc_url(add_query_arg('paged', $i, admin_url('admin.php?page=pneus-cacique')));
            echo '<li class="page-item' . ($i === $current_page ? ' active' : '') . '">';
            echo '<a class="page-link" href="' . $pagination_url . '">' . $i . '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</nav>';
    }
    wp_reset_postdata();
}

add_action('admin_menu', 'adicionar_menu_pneus_cacique');
