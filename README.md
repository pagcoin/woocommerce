PagCoin para WooCommerce
===========

Plugin para integrar sistemas de e-commerce desenvolvidos na plataforma WooCommerce 2.0+ com o gateway de pagamentos bitcoin PagCoin

### Instalando o Plugin ###
Na pasta wp-content\plugins, crie uma pasta pagcoin e copie os arquivos e pastas deste projeto.

### Configurando o Plugin ###
Após copiar os arquivos, é hora de configurar o plugin. Para isso, realize os seguintes passos:

- Acesse o painel de administração de seu e-commerce, clique em WooCommerce, e depois em Configuração.
- Clique na aba superior "Checkout"
- Clique no link BitCoins - PagCoin, logo abaixo das abas superiores
- Garanta que a opção "Aceitar Bitcoin usando PagCoin" está habilitada
- Informe sua API Key (disponível em https://pagcoin.com/Painel/Api)
- Garanta que a opção "Habilitar modo de sandbox" está desabilitada (Exceto no caso de você estar testando a integração)
- Clique em Salvar


### Configurando a URL de Callback (IPN) ###
Para receber a notificação de pagamentos confirmados ou cancelados (por pagamento irregular ou falta de pagamento), você deve configurar a URL de callback. Para isso, realize os seguintes passos:

- Entre no site do PagCoin (http://www.pagcoin.com)
- Acesse o Painel de Controle
- Selecione a opção Configurações de API.
- Preencha o campo URL de Callback (IPN) com o seguinte valor, alterando "enderecoDeSuaLoja" pelo domínio de seu site:
 - http://enderecoDeSuaLoja/?wc-api=WC_Gateway_PagCoin
-  Clique em Salvar
