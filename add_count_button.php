<?php

require_once '../../../wp-load.php';

if (isset($_POST['produto']) && isset($_POST['click_count'])) {

    $produto = $_POST['produto'];
    $click_count = (int) $_POST['click_count'];

    global $wpdb;
    $nome_tabela = $wpdb->prefix . 'relatoriopneuscacique';

    $existe_produto = $wpdb->get_var(
        $wpdb->prepare("
            SELECT COUNT(*)
            FROM $nome_tabela
            WHERE Produto = %s
        ", $produto)
    );

    if ($existe_produto) {

        $wpdb->update(
            $nome_tabela,
            array('Negociacao' => $click_count),
            array('Produto' => $produto),
            array('%d'),
            array('%s')
        );
    } else {

        $wpdb->insert(
            $nome_tabela,
            array(
                'Produto' => $produto,
                'Negociacao' => $click_count,
            ),
            array('%s', '%d')
        );
    }

    echo 'success';
} else {

    echo 'error';
}
