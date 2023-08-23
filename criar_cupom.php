<?php

require_once '../../../wp-load.php';

function criar_cupom()
{

    global $wpdb;

    $table_name = $wpdb->prefix . 'relatoriopneuscacique';
    $produto = $_POST['produto'];
    $sku = $_POST['sku'];

    $product_id = wc_get_product_id_by_sku($sku);

    if ($product_id) {
        // Verificar se o produto já está no carrinho
        $cart = WC()->cart;
        $cart_item_key = $cart->find_product_in_cart($product_id);

        if (!$cart_item_key) {
            // Adicionar o produto ao carrinho se ainda não estiver lá
            $cart->add_to_cart($product_id);

        }
    }

    $participacao_vendas_pesquisas = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT participacao_vendas_pesquisas
            FROM $table_name
            WHERE produto = %s",
            $produto
        )
    );

    $codigo_cupom = 'compre-agora-' . $sku . '';

    if ($participacao_vendas_pesquisas >= 0 && $participacao_vendas_pesquisas <= 35 && $_POST['valor'] == 'avista') {
        $desconto = 4;
    } elseif ($participacao_vendas_pesquisas >= 0 && $participacao_vendas_pesquisas <= 35 && $_POST['valor'] == '4vezes') {
        $desconto = 3;
    } elseif ($participacao_vendas_pesquisas >= 0 && $participacao_vendas_pesquisas <= 35 && $_POST['valor'] == '8vezes') {
        $desconto = 2;
    } elseif ($participacao_vendas_pesquisas >= 36 && $participacao_vendas_pesquisas <= 70 && $_POST['valor'] == 'avista') {
        $desconto = 3;
    } elseif ($participacao_vendas_pesquisas >= 36 && $participacao_vendas_pesquisas <= 70 && $_POST['valor'] == '4vezes') {
        $desconto = 2;
    } elseif ($participacao_vendas_pesquisas >= 36 && $participacao_vendas_pesquisas <= 70 && $_POST['valor'] == '8vezes') {
        $desconto = 1;
    } elseif ($participacao_vendas_pesquisas >= 70 && $_POST['valor'] == 'avista') {
        $desconto = 2;
    } elseif ($participacao_vendas_pesquisas >= 70 && $_POST['valor'] == '4vezes') {
        $desconto = 1;
    } else {
        return 'https://www.pneuscacique.com.br/negociar-precos-e-prazos';
    }

    $tipo_desconto = 'percent';

    $cupom = new WC_Coupon();

    $cupom->set_code($codigo_cupom);

    $cupom->set_discount_type($tipo_desconto);
    $cupom->set_amount($desconto);

    $expira_em = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $cupom->set_date_expires($expira_em);

    $cupom->save();

    $codigo_cupom_with_sku = 'compre-agora-' . $sku . '';

    $applied_coupons = WC()->cart->get_applied_coupons();

    if (empty($applied_coupons)) {
        WC()->cart->apply_coupon($codigo_cupom_with_sku);
    }

    $return_message = 'O cupom de desconto <span id="cupom-code" style="font-size: 30px;">
    ' . $codigo_cupom_with_sku . '</span> foi aplicado automaticamente ao seu carrinho!!';

    return $return_message;
}

$resultado = criar_cupom();

echo $resultado;
