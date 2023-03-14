# Plugin Não-Oficial Gerencianet para Wordpress/Woocommerce
forked from [gerencianet/gn-api-woocommerce](https://github.com/gerencianet/gn-api-woocommerce)

Receba pagamentos por Boleto bancário, Pix e cartão de crédito em sua loja WooCommerce com a efí/Gerencianet!

## Suporte Técnico
### Atenção: 
* Para resolução de problemas abra uma issue no nosso [Github](https://github.com/aireset/gn-api-woocommerce), envie print, informações de uso para podermos auxiliar na melhor forma, caso precise suporte direto da Efí, use o plugin oficial.
* Para agilizar o atendimento, abra um ticket informando a falha apresentada. Você pode abrir um ticket [Clicando Aqui!](https://sistema.gerencianet.com.br/tickets/criar/)

## Descrição 

Este Moduloe é um Fork do Módulo Oficial de integração fornecido pela [Efí](https://gerencianet.com.br/) para WooCommerce. Com ele, o proprietário da loja pode optar por receber pagamentos por boleto bancário, cartão de crédito e/ou Pix. Todo processo é realizado por meio do checkout transparente. Com isso, o comprador não precisa sair do site da loja para efetuar o pagamento.

Caso você tenha alguma dúvida ou sugestão, entre em contato pelo site [Efí](https://gerencianet.com.br/fale-conosco/).

## Requisitos

* Versão do PHP: 7.x ou 8.x
* Versão do WooCommerce: 5.x
* Versão do WordPress: 5.x

## Instalação automática 

1. Acesse o link em sua loja "Plugins" -> "Adicionar novo" -> No campo de busca, pesquise por "Gerencianet Oficial".
2. Clique em "Instalar agora".
4. Após a instalação, clique em "Ativar o Plugin".
5. Configure o plugin em "WooCommerce" > "Configurações" > "Finalizar Compra" > "Gerencianet" e comece a receber pagamentos.


## Configuração 

1. Ative o plugin.
2. Configure as credenciais de sua Aplicação Gerencianet. Para criar uma nova Aplicação, entre em sua conta Gerencianet, acesse o menu "API" e clique em "Aplicações" -> "Nova aplicação". Insira as credenciais disponíveis neste link (Client ID e Client Secret de produção e homologação) nos respectivos campos de configuração do plugin.
3. Insira o Payee Code (Identificador de Conta) de sua conta Gerencianet. Para encontrar o Payee Code, entre em sua conta Gerencianet, acesse o menu "API" e clique em "Introdução".
4. Configure as opções de pagamento que deseja receber: Boleto, Cartão de Crédito e/ou Pix.
5. Caso utilize a opção de Pix:
   * Insira sua Chave Pix cadastrada em sua conta Gerencianet.
   * Insira o seu certificado (arquivo .p12 ou .pem).
   * Marque o campo "Validar mTLS" caso deseje utilizar a validação mTLS em seu servidor.
6. Defina se deseja aplicar desconto para pagamentos com Boleto, o modo de aplicar esse desconto e insira o número de dias corridos para vencimento.
7. Defina as instruções para pagamento no Boleto em quatro linhas de até 90 caracteres cada uma. Caso essas linhas não sejam definidas pelo lojista, será exibido no boleto as instruções padrões da Gerencianet.
8. Escolha se deseja que o plugin atualize os status dos pedidos da loja automaticamente, de acordo com as notificações de alteração do status da cobrança Gerencianet.
9. Configure se deseja ativar o Sandbox (ambiente de testes) e Debug.
10. Recomendamos que antes de disponibilizar pagamentos pela Gerencianet, o lojista realize testes de cobrança com o sandbox(ambiente de testes) ativado para verificar se o procedimento de pagamento está acontecendo conforme esperado.