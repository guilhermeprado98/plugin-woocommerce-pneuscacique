<head>

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

   <style>
   a.form-cap_popup {
      border-radius: 40px;
      font-size: 15px;
      color: white !important;
      background: rgb(0, 0, 0);
      background: linear-gradient(19deg, #299aa7 0%, #16c396 62%);
   }
   </style>
</head>

<?php

function adicionar_botao()
{
    global $wpdb;
    global $product;

    $stockQuantity = $product->get_stock_quantity();
    $stockStatus = $product->get_stock_status();

    if (is_product() && $product->is_type('simple') && $stockStatus === 'instock' || $stockQuantity > 0) {
        $tabela = $wpdb->prefix . 'relatoriopneuscacique';
        $sql = $wpdb->prepare("SELECT negociacao FROM $tabela WHERE produto = %s", $product->get_name());
        $result = $wpdb->get_var($sql);

        ?>

<a href="#" class="form-cap_popup button alt button-popup" id="meu-botao_<?php echo $product->get_name(); ?>"
   onclick="abrirPopUp('<?php echo $product->get_name(); ?>', '<?php echo $result + 1; ?>', '<?php echo $product->get_sku() ?>')">
   <i class="fa fa-whatsapp formcap" style="font-size: 40px;" aria-hidden="true"></i>&nbsp;
   Quer negociar preços e prazos?<br>
   Fale com o nosso atendimento.
</a>

<?php
}
}
add_action('woocommerce_after_add_to_cart_button', 'adicionar_botao', 999);

?>
<script>
function abrirPopUp(produto, clickCount, sku) {
   var popupURL = '<?php echo plugins_url('popup.php', __FILE__); ?>' + '?produto=' + encodeURIComponent(produto) +
      '&sku=' + encodeURIComponent(sku);
   window.open(popupURL, 'meu-popup', 'width=600,height=400');

   $.ajax({
      type: 'POST',
      url: '<?php echo plugins_url('add_count_button.php', __FILE__); ?>',
      data: 'produto=' + produto + '&click_count=' + clickCount + '&sku=' + sku,
      contentType: 'application/x-www-form-urlencoded',
      success: function(data) {
         console.log('Envio com sucesso');
      },
      error: function(xhr, status, error) {
         console.error('Error ao enviar:', error);
      }
   });
}
</script>
