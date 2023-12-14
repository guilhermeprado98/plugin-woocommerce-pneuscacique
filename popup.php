<?php
global $product;
require_once '../../../wp-load.php';

?>

<!DOCTYPE html>
<html>

<head>
   <title>CUPOM DE DESCONTO</title>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <link rel="stylesheet" href="include/css/bootstrap.min.css">
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.18/dist/sweetalert2.all.min.js"></script>

   <!-- Google Tag Manager -->
   <script>
   (function(w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({
         'gtm.start': new Date().getTime(),
         event: 'gtm.js'
      });
      var f = d.getElementsByTagName(s)[0],
         j = d.createElement(s),
         dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src =
         'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
      f.parentNode.insertBefore(j, f);
   })(window, document, 'script', 'dataLayer', 'GTM-55G3SZV');
   </script>
   <!-- End Google Tag Manager -->

   <style>
   @media (max-width: px) {

      p {
         font-size: 20px !important;
      }

   }

   body {
      padding: 20px;
      font-family: Arial, sans-serif;
   }

   h1 {
      color: #333;
      font-size: 24px;
   }

   p {
      color: #666;
      font-size: 16px;
   }

   a.form-cap_popup {
      border-radius: 40px;
      font-size: 15px;
      color: white !important;
      background: rgb(0, 0, 0);
      background: linear-gradient(19deg, #299aa7 0%, #16c396 62%);
   }

   @media (min-width: 208px) {
      a.form-cap_popup {
         border-radius: 40px;
         font-size: 15px;
         color: white !important;
         background: rgb(0, 0, 0);
         background: linear-gradient(19deg, #299aa7 0%, #16c396 62%);
      }
   }


   /* Media query for desktops with a minimum width of 768px */
   @media (min-width: 500px) {
      #continue-atendimento {
         margin-bottom: 10px !important;

      }

      #selecione-pagamento {
         margin-top: 10px;
         margin-right: 10px;
      }
   }
   </style>
</head>

<body>
   <!-- Google Tag Manager (noscript) -->
   <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-55G3SZV" height="0" width="0"
         style="display:none;visibility:hidden"></iframe></noscript>
   <!-- End Google Tag Manager (noscript) -->
   <!-- Conteúdo do pop-up -->
   <center>
      <h1>VOCÊ ACABOU DE GANHAR UM CUPOM DE DESCONTO!</h1>
      <p>Caso queira continuar o atendimento com um de nossos consultores, clique em "Continue com o atendimento",
         caso
         queira gerar o CUPOM DE DESCONTO
         para sua compra, clique em "Adquirir CUPOM DE DESCONTO"</p>

      <button id="criar-cupom" class="btn btn-success"><b>Adquirir CUPOM DE DESCONTO</b><b></button>

      <a href="https://pneuscacique.com.br/negociar-precos-e-prazos?producttitle=<?php echo $_GET['produto']; ?>&productsku=<?php echo $_GET['sku']; ?>"
         id="continue-atendimento-link">
         <button id="continue-atendimento" class="btn btn-warning"
            style="margin-top:10px; color: white !important;"><b>Continue com o
               atendimento</b></button>
      </a>
   </center>

   <!-- Pop-up do select -->
   <div class="input-group mb-3" id="select-popup" style="display: none; margin-top: 30px; margin-right: 5px">
      <div class="input-group-prepend">
         <label class="input-group-text" for="inputGroupSelect01" id="selecione-pagamento">Selecione a forma de
            pagamento</label>
      </div>
      <select class="form-select" id="cupom-select" style="margin-top:10px">
         <option value="">Selecionar</option>
         <option value="avista">À vista</option>
         <option value="4vezes">4 vezes</option>
         <option value="8vezes">8 vezes</option>
      </select>
      <button id="enviar-select" class="btn btn-primary" style="margin-left: 13px; margin-top:10px"><b>GERAR
            CUPOM</b></button>
   </div>

   <script>
   jQuery(document).ready(function($) {

      $("#criar-cupom").click(function() {
         sendButtonClickData("criar-cupom");
         $("#select-popup").show();
      });
      $("#continue-atendimento").click(function() {
         sendButtonClickData("continue-atendimento");

      });


      $("#enviar-select").click(function() {
         <?php

if (isset($_GET['produto'])) {
    $nome_produto = $_GET['produto'];
    $sku = $_GET['sku'];

}
?>
         var valorSelect = $("#cupom-select").val();
         var nomeProduto = "<?php echo $nome_produto; ?>";
         var skuProduto = "<?php echo $sku; ?>";
         $.ajax({
            url: "criar_cupom.php",
            method: "POST",
            data: {
               valor: valorSelect,
               produto: nomeProduto,
               sku: skuProduto
            },
            success: function(response) {


               if (response.trim() == "https://www.pneuscacique.com.br/negociar-precos-e-prazos") {

                  window.location.href = response + '?producttitle=' +
                     encodeURIComponent(nomeProduto) + '&productsku=' +
                     encodeURIComponent(skuProduto);
               } else {
                  Swal.fire({
                     icon: 'success',
                     title: 'cupom gerado com sucesso!',
                     html: response,
                     confirmButtonText: 'OK'
                  }).then((result) => {
                     if (result.isConfirmed) {

                        window.location.href =
                           "https://www.pneuscacique.com.br/finalizar-compra";

                     }
                  });
               }
            },
            error: function(xhr, status, error) {
               console.error(error);
            }
         });

      });
      var nomeProduto = "<?php echo $nome_produto; ?>";

      function sendButtonClickData(buttonId) {
         $.ajax({
            url: "add_count_popup.php",
            method: "POST",
            data: {
               produto: nomeProduto,
               button_clicked: buttonId
            },
            success: function(response) {
               // Faça algo se necessário com a resposta do servidor
               console.log(response);
            },
            error: function(xhr, status, error) {
               console.error(error);
            }
         });
      }

      document.getElementById("continue-atendimento-link").addEventListener("click", function(e) {
         e.preventDefault();
         var href = this.getAttribute("href");
         if (href) {
            window.location.href = href; // Redireciona na mesma página
         }
      });
   });
   </script>
</body>

</html>
