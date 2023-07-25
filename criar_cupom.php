<?php

require_once '../../../wp-load.php';

function criar_cupom()
{

    global $wpdb;

    $table_name = $wpdb->prefix . 'relatoriopneuscacique';

    $produto = $_POST['produto'];

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
        //COLOCAR AQUI A PAGINA DE ATENDIMENTO QUANDO ESTIVER EM HOMOLOGAÇÃO
        header("Location: https://pneuscacique.com.br");
    }

// Defina o valor do desconto em porcentagem
    $tipo_desconto = 'percent'; // Pode ser 'percent' ou 'fixed_cart'

    // Cria um novo objeto de cupom
    $cupom = new WC_Coupon();

    // Define o código do cupom
    $cupom->set_code($codigo_cupom);

    // Define o tipo de desconto e o valor
    $cupom->set_discount_type($tipo_desconto);
    $cupom->set_amount($desconto);

    // Define a data de expiração (24 horas a partir do momento atual)
    $expira_em = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $cupom->set_date_expires($expira_em);

    // Salva o cupom
    $cupom->save();

    // Retornar a mensagem do cupom juntamente com a participação de vendas pesquisas
    return 'VOCÊ acabou de ganhar um cupom de desconto para seu pedido!
Adicione o código ' . $codigo_cupom . '_' . '' . $codigo_cupom . ' em sua compra e aproveite já!';
}

// Chama a função para criar o cupom
$resultado = criar_cupom();

// Retorna a resposta
echo $resultado;
