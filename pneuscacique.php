<head>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous">
   </script>
</head>


<?php
/*
Plugin Name: CUPOM Pneus Cacique
Description: Personalizações de CUPOM de desconto.
Version: 1.0
Author: Guilherme Prado
 */

function criar_tabela_relatorio_pneus_cacique()
{
    global $wpdb;
    $nome_tabela = $wpdb->prefix . 'relatoriopneuscacique';

    $query = "CREATE TABLE IF NOT EXISTS $nome_tabela (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produto VARCHAR(255),
        negociacao INT,
        participacao_vendas_pesquisas VARCHAR(255)
    );";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($query);
}

register_activation_hook(__FILE__, 'criar_tabela_relatorio_pneus_cacique');

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
add_action('woocommerce_single_product_summary', 'adicionar_botao_pagina_produto', 25);

function adicionar_menu_pneus_cacique()
{
    add_menu_page(
        'Pneus Cacique',
        'Relatório Pesquisa x Vendas',
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

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE wp_relatoriopneuscacique SET vendas = vendas + %d WHERE produto = %s",
                $quantidade,
                $nome_produto
            )
        );
    }
}

function atualizar_numero_vendas()
{
    global $wpdb;
    global $product;

    $product_name = $product->get_name();
    // Obtenha o valor atualizado de vendas após a atualização para o primeiro produto
    $nova_quantidade_vendas = $wpdb->get_var(
        $wpdb->prepare("
            SELECT vendas
            FROM {$wpdb->prefix}relatoriopneuscacique
            WHERE produto = %s
        ", $product_name)
    );

    // Retorne o novo valor de vendas do primeiro produto (pode ser personalizado conforme necessário)
    return $nova_quantidade_vendas;
}
add_action('woocommerce_new_order', 'atualizar_vendas_produto', 1, 2);

function obter_quantidade_pedidos_produto($product_id)
{
    global $wpdb;
    global $product;

    $product_name = $product->get_name();
    // Obtenha o valor atualizado de vendas após a atualização para o primeiro produto
    $nova_quantidade_vendas = $wpdb->get_var(
        $wpdb->prepare("
            SELECT vendas
            FROM {$wpdb->prefix}relatoriopneuscacique
            WHERE produto = %s
        ", $product_name)
    );

    // Retorne o novo valor de vendas do primeiro produto (pode ser personalizado conforme necessário)
    return $nova_quantidade_vendas;
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

function exibir_pagina_relatorio_produtos()
{

    if (isset($_GET['atualizar_relatorio'])) {

        wp_redirect(admin_url('admin.php?page=pneus-cacique'));
        exit;
    }

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);

    echo '<h1>Relatório de Produtos (Pesquisas x Vendas x Negociação)</h1>';
    echo '<h4>Este relatório demonstra a quantidade de vezes em que o produto foi procurado pelo cliente (campo Pesquisas), quantas vendas foram concluídas (campo Vendas), a quantidade de negociações realizadas (campo Negociação) e a relação de porcentagem Vendas/Pesquisas e Negociação/Pesquisas (campo Participação Vendas x Pesquisas e Participação Negociação x Pesquisas).</h4>';

    echo '<form method="get" action="">';
    echo '<input type="hidden" name="page" value="pneus-cacique">';
    echo '<input type="submit" style="margin-bottom: 10px" name="atualizar_relatorio" value="Atualizar Relatório" class="btn btn-primary">';
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
    echo '</tr>';
    echo '</thead>';

    while ($query->have_posts()) {
        global $product;
        $query->the_post();
        $product_id = get_the_ID();
        $product_name = get_the_title();

        // Obter o número de vezes que o produto foi pesquisado
        $numero_pesquisas = get_post_meta($product_id, 'woocommerce_views', true);

        // Obter o número de vezes que o produto foi vendido
        $numero_vendas = obter_quantidade_pedidos_produto($product_id);

        // Obter a quantidade de negociações realizadas
        $quantidade_negociacao = obter_quantidade_negociacoes_produto($product_name);

        // Calcular o percentual de vendas
        $percentual_vendas = 0;
        if ($numero_pesquisas > 0) {
            $percentual_vendas = ($numero_vendas / $numero_pesquisas) * 100;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'relatoriopneuscacique';

        $data = array(
            'participacao_vendas_pesquisas' => $percentual_vendas,
        );
        $data_format = array(
            '%d',
        );
        // Condições para o WHERE
        $where = array(
            'produto' => $product_name,
        );

        $wpdb->update($table_name, $data, $where, $data_format);

        $percentual_negociacao = 0;
        if ($numero_pesquisas > 0) {
            $percentual_negociacao = ($numero_vendas / $quantidade_negociacao) * 100;
        }

        echo '<tr>';
        echo '<td>' . get_the_title() . '</td>';
        echo '<td>' . $numero_pesquisas . '</td>';
        echo '<td>' . $numero_vendas . '</td>';
        echo '<td>' . $quantidade_negociacao . '</td>';
        echo '<td>' . ceil($percentual_vendas) . '%</td>';
        echo '<td>' . ceil($percentual_negociacao) . '%</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Paginação
    $total_posts = $query->found_posts;
    $posts_per_page = 1;
    $total_pages = ceil($total_posts / $posts_per_page);

    if ($total_pages >= 1) {
        $current_page = max(1, get_query_var('paged'));
        echo '<nav aria-label="Page navigation">';
        echo '<ul class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<li class="page-item' . ($i === $current_page ? ' active' : '') . '">';
            echo '<a class="page-link" href="?page=pneus-cacique&paged=' . $i . '">' . $i . '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</nav>';
    }

    wp_reset_postdata();
}

add_action('admin_menu', 'adicionar_menu_pneus_cacique');
