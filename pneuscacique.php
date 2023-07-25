<?php
/*
Plugin Name: CUPOM Pneus Cacique
Description: Personalizações de CUPOM de desconto.
Version: 1.0
Author: Guilherme Prado
 */

require 'vendor/autoload.php';

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

function atualizar_vendas_produto($pedido_id)
{
    global $wpdb;

    $pedido = wc_get_order($pedido_id);

    if (!$pedido) {
        return;
    }

    foreach ($pedido->get_items() as $item_data) {
        $nome_produto = $item_data->get_name();
        $quantidade = $item_data->get_quantity();

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE wp_relatoriopneuscacique SET vendas = vendas + %d WHERE produto = '%s'",
                $quantidade,
                $nome_produto
            )
        );
    }
}

add_action('woocommerce_new_order', 'atualizar_vendas_produto');

function atualizar_numero_vendas($order_id)
{
    global $wpdb;
    global $product;

    // Obtenha o pedido

    $order = wc_get_order($order_id);

    if ($order) {
        // Obtém todos os itens do pedido
        $order_items = $order->get_items();

        // Iterar por todos os itens do pedido
        foreach ($order_items as $item_id => $item) {
            $product_name = $item->get_name(); // Obter o nome do produto

            // Atualize a coluna 'vendas' na tabela 'wp_relatoriopneuscacique' para o produto atual
            $wpdb->query(
                $wpdb->prepare("
                    UPDATE {$wpdb->prefix}relatoriopneuscacique
                    SET vendas = vendas + 1
                    WHERE produto = %s
                ", $product_name)
            );
        }
    }

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

add_action('woocommerce_order_status_completed', 'atualizar_numero_vendas');

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

function exibir_pagina_relatorio_produtos($order_id)
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
    echo '<input type="submit" style="margin-bottom: 10px" name="atualizar_relatorio" value="Atualizar Relatório">';
    echo '</form>';

    echo '<table id="datatable" style="border-collapse: collapse; width: 100%;">';
    echo '<tr><th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Produto</th><th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Pesquisas</th><th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Vendas</th><th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Negociação</th><th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Participação Vendas x Pesquisas</th><th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">Participação Negociação x Pesquisas</th></tr>';

    while ($query->have_posts()) {

        global $order;
        $query->the_post();
        $product_id = get_the_ID();
        $product_name = get_the_title();

        // Obter o número de vezes que o produto foi pesquisado
        $numero_pesquisas = get_post_meta($product_id, 'woocommerce_views', true);

        // Obter o número de vezes que o produto foi vendido

        $numero_vendas = atualizar_numero_vendas($order_id);

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
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . get_the_title() . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . $numero_pesquisas . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . $numero_vendas . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . $quantidade_negociacao . '</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . ceil($percentual_vendas) . '%</td>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . ceil($percentual_negociacao) . '%</td>';
        echo '</tr>';
    }

    echo '</table>';

    wp_reset_postdata();
}

add_action('admin_menu', 'adicionar_menu_pneus_cacique');
add_action('woocommerce_order_status_completed', 'exibir_pagina_relatorio_produtos');
