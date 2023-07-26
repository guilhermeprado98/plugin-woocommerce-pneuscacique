<?php

require_once '../../../wp-load.php';

function criar_cupom()
{

    global $wpdb;

    $table_name = $wpdb->prefix . 'relatoriopneuscacique';
    $produto = $_POST['produto'];
    $sku = $_POST['sku'];

    $participacao_vendas_pesquisas = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT participacao_vendas_pesquisas
            FROM $table_name
            WHERE produto = %s",
            $produto
        )
    );
/*

➡Participação 0 a 35%
Avista cupom com 4%
4 vezes cupom com 3%
8 vezes cupom com 2%

➡Participação 36 a 70%
Avista cupom com 3%
4 vezes cupom com 2%
8 vezes cupom com 1%

➡Participação acima de 70%
Avista cupom com 2%
4 vezes cupom com 1%
8 vezes falar com atendente
 */

    //COLOCAR AS CONDIÇÕES SOBRE A PARTICIPACAO AQUI, PARA SETAR OS DESCONTOS
    $codigo_cupom = 'COMPREAGORA'; // Defina o código do cupom desejado

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

    $codigo_cupom_with_sku = $codigo_cupom . '' . $sku;

    $return_message = 'VOCÊ acabou de ganhar um cupom de desconto para seu pedido! Adicione o código <a href="teste"> ' . $codigo_cupom_with_sku . '</a> em sua compra e aproveite já!';

    return $return_message;
}

$resultado = criar_cupom();

echo $resultado;
