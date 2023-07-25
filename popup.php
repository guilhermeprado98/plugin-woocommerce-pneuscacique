<?php
global $product;
require_once '../../../wp-load.php';
?>

<!DOCTYPE html>
<html>

<head>
   <title>CUPOM DE DESCONTO</title>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

   <style>
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

   a.form-cap {
      border-radius: 40px;
      font-size: 15px;
      color: white !important;
      background: rgb(0, 0, 0);
      background: linear-gradient(19deg, #299aa7 0%, #16c396 62%);
   }

   @media (min-width: 208px) {
      a.form-cap {
         border-radius: 40px;
         font-size: 15px;
         color: white !important;
         background: rgb(0, 0, 0);
         background: linear-gradient(19deg, #299aa7 0%, #16c396 62%);
      }
   }
   </style>
</head>

<body>
   <!-- Conteúdo do pop-up -->
   <center>
      <h1>VOCÊ ACABOU DE GANHAR UM CUPOM DE DESCONTO!</h1>
      <p>Caso queira continuar o atendimento com um de nossos consultores, clique em "Continue com o atendimento",
         caso
         queira ativar o CUPOM DE DESCONTO
         para sua compra, clique em "Adquirir CUPOM DE DESCONTO"</p>

      <button id="criar-cupom">Adquirir CUPOM DE DESCONTO</button>

      <a href="https://pneuscacique.com.br/negociar-precos-e-prazos?producttitle=PNEU 215/55R18 WRANGLER TERRITORY HT 95 V&amp;productsku=110313"
         id="continue-atendimento-link">
         <button id="continue-atendimento">Continue com o atendimento</button>
      </a>
   </center>

   <!-- Pop-up do select -->
   <div id="select-popup" style="display: none;">
      <h2>Selecione sua opção de pagamento:</h2>
      <select id="cupom-select">
         <option value="avista">À vista</option>
         <option value="4vezes">4 vezes</option>
         <option value="8vezes">8 vezes</option>
      </select>
      <button id="enviar-select">GERAR CUPOM</button>
   </div>

   <script>
   jQuery(document).ready(function($) {
      // Abrir o pop-up do select quando clicar no botão "Adquirir CUPOM DE DESCONTO"
      $("#criar-cupom").click(function() {
         $("#select-popup").show();
      });

      // Enviar o valor selecionado via AJAX para o arquivo criar_cupom.php
      $("#enviar-select").click(function() {
         <?php
// Verifica se o parâmetro 'produto' foi passado na URL
if (isset($_GET['produto'])) {
    $nome_produto = $_GET['produto'];

}
?>
         var valorSelect = $("#cupom-select").val();
         var nomeProduto = "<?php echo $nome_produto; ?>";
         $.ajax({
            url: "criar_cupom.php",
            method: "POST",
            data: {
               valor: valorSelect,
               produto: nomeProduto

            },
            success: function(response) {

               alert(response);
               window.close();
            },
            error: function(xhr, status, error) {

               console.error(error);
            }
         });
      });

      // Redirecionar para a página pai quando clicar em "Continue com o atendimento"
      document.getElementById("continue-atendimento-link").addEventListener("click", function(e) {
         e.preventDefault(); // Evita o comportamento padrão do link
         var href = this.getAttribute("href");
         if (href) {
            window.addEventListener("beforeunload", function() {
               window.opener.location.href = href; // Redireciona a página pai
            });
            window.close(); // Fecha o pop-up
         }
      });
   });
   </script>
</body>

</html>
