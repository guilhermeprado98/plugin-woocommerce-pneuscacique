<?php
// Conexão com o banco de dados
require_once '../../../wp-load.php';
global $wpdb;
$table_name = $wpdb->prefix . 'relatoriopneuscacique';

if (isset($_POST['button_clicked'])) {
    $buttonClicked = $_POST['button_clicked'];
    $nome_produto = $_POST['produto'];

    if ($buttonClicked == 'criar-cupom') {
        $wpdb->query("UPDATE $table_name SET criar_cupom_count = criar_cupom_count + 1 WHERE produto = '$nome_produto'");
        echo '<pre>';
        print_r($wpdb);
    } elseif ($buttonClicked == 'continue-atendimento') {
        $wpdb->query("UPDATE $table_name SET continue_atendimento_count = continue_atendimento_count + 1 WHERE produto = '$nome_produto'");
    }

}

$wpdb->flush();
