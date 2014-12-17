<?php
/*
    Plugin Name: PagCoin for WooCommerce
    Plugin URI:  https://pagcoin.com
    Description: Your client pays in bitcoin, you receive your local currency (currently only BRL), risk free and only 1% tax.
    Author:      PagCoin
    Author URI:  https://pagcoin.com

    Version: 	       1.0
    License:           MIT License
    License URI:       https://github.com/pagcoin/woocommerce
    GitHub Plugin URI: https://github.com/pagcoin/woocommerce
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('plugins_loaded', 'woocommerce_pagcoin_init', 0);

function woocommerce_pagcoin_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;

	class WC_Gateway_PagCoin extends WC_Payment_Gateway
	{
		public function __construct()
		{
			$this->log = new WC_Logger();
			
			$this->id                 = 'pagcoin';
			$this->icon               = plugin_dir_url(__FILE__) . 'img/PoweredByPagCoin-40h.png';
			$this->has_fields         = false;
			$this->order_button_text  = __('Pagar com PagCoin', 'pagcoin');
			$this->method_title       = 'BitCoins - PagCoin';
			$this->method_description = 'PagCoin permite que você aceite bitcoins e receba em Reais em sua conta bancária.';

			$this->init_form_fields();
			$this->init_settings();

			$this->title              = $this->get_option('title');
			$this->description        = $this->get_option('description');
			$this->debug              = $this->get_option('debug');

			$this->api_key 			  = $this->get_option('api_key');
			$this->sandbox 			  = $this->get_option('sandbox') == 'yes' ? true : false;
			$this->iframeWidth		  = $this->get_option('iframeWidth');
			$this->fullWidth		  = $this->get_option('fullWidth') == 'yes' ? true : false;
			$this->tema		  		  = $this->get_option('tema');
			
			if (!$this->sandbox){
				$this->base_url = 'https://pagcoin.com';
			} else {
				$this->base_url = 'https://sandbox.pagcoin.com';
			}
			
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
			add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'ipn_callback'));
		}

		public function is_valid_for_use()
		{
			if (is_null($this->api_key)) {
				return false;
			}
			return true;
		}

		public function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __('Habilitar/Desabilitar', 'pagcoin'),
					'type'    => 'checkbox',
					'label'   => __('Aceitar Bitcoin usando PagCoin', 'pagcoin'),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __('Nome', 'pagcoin'),
					'type'        => 'text',
					'default'     => __('Bitcoin (via PagCoin)', 'pagcoin'),
				),
				'description' => array(
					'title' => __('Descrição', 'pagcoin'),
					'type' => 'textarea',
					'default' => 'Você receberá uma ordem de pagamento, com a quantia em bitcoins e o endereço para onde você deve transferir o valor.'
				),
				'api_key' => array(
					'title'       => __('API Key', 'pagcoin'),
					'type'        => 'text',
					'description' => __('API Key informada em <a href="https://pagcoin.com/Painel/API">https://pagcoin.com/Painel/API</a>.', 'pagcoin'),
					'default'     => __('', 'pagcoin'),
				),
				'fullWidth' => array(
					'title'   => __('Ocupar 100% de largura', 'pagcoin'),
					'type'    => 'checkbox',
					'label'   => __('Largura da ordem de pagamento atribuída para para 100%. Valor de "Largura da ordem de pagamento" será desconsiderado. Recomendado para layouts responsivos.', 'pagcoin'),
					'default' => 'yes'
				),
				'iframeWidth' => array(
					'title'       => __('Largura da ordem de pagamento', 'pagcoin'),
					'type'        => 'number',
					'description' => __('Se "Ocupar 100% de Largura" está desabilitado, esta será a largura em pixels da área onde é mostrada a ordem de pagamento para o usuário', 'pagcoin'),
					'desc_tip'    => __('Recomenda-se não inserir valores menores que 480 ou maiores que 950', 'pagcoin'),
					'default'     => '480'
				),
				'tema' => array(
					'title'			=> __('Tema', 'pagcoin'),
					'type'			=> 'select',
					'default'		=> 'light',
					'description'	=> 'Esquema de cores predominante de seu site. Caso seu plano de fundo seja uma cor clara, a ordem será mostrada com o texto em cores escuras. Caso contrário, em cores claras',
					'options'		=> array(
											'light' => 'light',
											'dark' => 'dark')
				),
				'sandbox' => array(
					'title'       => __('Modo Sandbox', 'pagcoin' ),
					'type'        => 'checkbox',
					'label'       => __('Habilitar modo de sandbox', 'pagcoin' ),
					'default'     => 'no',
					'desc_tip'    => __('Lembre-se que a API Key usada no modo de sandbox é diferente de sua API Key de produção.', 'pagcoin'),
					'description' => __('Modo de Sandbox que permite teste de integração. Mais informações em <a href="https://pagcoin.com/Desenvolvedores/Sandbox">https://pagcoin.com/Desenvolvedores/Sandbox.<a>', 'pagcoin')
				)
			);
		}

		public function thankyou_page($order_id)
		{
		}
		
		function receipt_page($order_id)
		{
			$ratio = 0.72;
			
			if ($this->sandbox){
				$ratio = 0.84;
			}
			
			$width = $this->iframeWidth;
			$height = ceil($this->iframeWidth * $ratio);
			
			if($this->fullWidth){
				$width = '100%';
				echo '<script src="' . plugin_dir_url(__FILE__) . 'js/adap.js' . '"></script>';
			}
			
			echo '<div>' . __('Não feche esta janela ou mude de página até que a confirmação da transferência seja mostrada abaixo.', 'pagcoin') . '</div>';
			echo '<center><iframe id="pagCoinInvoiceIFrame" src="' . $this->base_url . '/Invoice/' . $_GET['inv'] . '" width="' . $width . '" height="' . $height . '"></iframe></center>';
			
			$order = wc_get_order($order_id);
			$order->update_status('on-hold', 'Aguardando pagamento');
		}

		public function process_payment($order_id)
		{
			if (empty($this->api_key)){
				throw new Exception('A configuração do plugin está incorreta. API Key não informada.');
			}
		
			$order = wc_get_order($order_id);
			
			$thanks_link = $this->get_return_url($order);
			
			$cust_email = $order->billing_email;
			if (empty($cust_email)){
				 $cust_email = $order->getUser()->user_email;
			}
			
			$apikey = $this->api_key;
			$grandTotal = $order->get_total();
			$apiEmail = $cust_email;
			$apiOrderId = $order_id;
			$storeName = get_bloginfo('name');
			
			$pagCoinUrl = $this->base_url . "/api/1/CriarInvoice/?modo=" . $this->tema;
		
			$request = array(
				"apiKey" => $apikey, 
				"valorEmMoedaOriginal" => (double)$grandTotal, 
				"nomeProduto" => $storeName . ' - Pedido: ' . $order_id, 
				"idInterna" => $apiOrderId, 
				"email" => $apiEmail, 
				"redirectURL" => ''
			);
			
			$jsonRequest = json_encode($request);
			
			$this->log->add('pagcoin', $pagCoinUrl);
			$this->log->add('pagcoin', $jsonRequest);
			
			$ch = curl_init($pagCoinUrl);
			$cabundle = dirname(__FILE__).'/xtra/ca-bundle.crt';
			$this->log->add('pagcoin', $cabundle);
			
			curl_setopt( $ch, CURLOPT_POST, 1);
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $jsonRequest);
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt( $ch, CURLOPT_HEADER, 0);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt( $ch, CURLOPT_CAINFO, $cabundle);
			
			$redirectUrl = curl_exec($ch);
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$this->log->add('pagcoin', $redirectUrl);
			$this->log->add('pagcoin', $http_status);

			curl_close($curl);
			
			$token = '';
			if($http_status == 200){
				$token = $redirectUrl;
				
				$order->reduce_order_stock();

				WC()->cart->empty_cart();
				
				// Redirect to checkout receipt page (invoice loaded inside the receipt page's iframe)
				return array(
					'result'    => 'success',
					'redirect'  => add_query_arg('order', 
													$order->id,
													add_query_arg('inv', 
																   str_replace('/Invoice/', '', $token), 
																   $order->get_checkout_payment_url(true)
													)
									)
				);
			}else{
				$this->log->add('pagcoin', 'return url: ' . $this->get_return_url( $order ));
				
				throw new Exception('Erro criando ordem de pagamento. Tente novamente em alguns minutos');
			}
		}
		
		public function ipn_callback()
		{
			$this->log->add('pagcoin', 'Entrou IPN');
			
			// Auxiliar array created to ignore casing (WC running in IIS was losing the header key's casing information).
			$request = getallheaders();
			$keys = array_keys($request);
			$headers = array();
			foreach($keys as $key)
			{
				 $headers[strtolower($key)]=$request[$key];
			}
			
			$enderecoPagCoin = $headers['enderecopagcoin'];
			$assinaturaPagCoin = $headers['assinaturapagcoin'];
		
			$this->log->add('pagcoin', 'Endereco: ' . $enderecoPagCoin);
			$this->log->add('pagcoin', 'Assinatura: ' . $assinaturaPagCoin);
			
			if (empty($enderecoPagCoin)) {
				throw new Exception('Header inválido. Campo EnderecoPagCoin não informado.');
			}
			if (empty($assinaturaPagCoin)) {
				throw new Exception('Header inválido. Campo AssinaturaPagCoin não informado.');
			}
			
			$postdata = file_get_contents("php://input");
			$apikey = $this->api_key;
			
			$signature = hash_hmac('sha256', $enderecoPagCoin . $postdata, $apikey);
			
			$this->log->add('pagcoin', 'Assinatura esperada: ' . $signature);
			if ($signature != $assinaturaPagCoin) {
				throw new Exception('Assinatura não confere.');
			}
			
			$fields = json_decode($postdata,true);

			$order = new WC_Order( $fields["idInterna"] );
			
			$this->log->add('pagcoin', 'IdInterna: ' . $fields["idInterna"]);
			$this->log->add('pagcoin', 'StatusPagamento: ' . $fields["statusPagamento"]);
			
			if ($fields["statusPagamento"] == 'confirmado') {
				$order->payment_complete();
				$order->update_status('processing', __('Ordem de pagamento paga e confirmada.', 'pagcoin'));
			} else if ($fields["statusPagamento"] == 'timeout') {
				$order->update_status('cancelled', __('O valor não foi transferido dentro do intervalo de 15 minutos. A ordem de pagamento foi invalidada.', 'pagcoin'));
			} else if ($fields["statusPagamento"] == 'recusado') {
				$order->update_status('failed', __('A transferência foi invalidada pela rede bitcoin.', 'pagcoin'));
			}

			echo '200 OK';
		}

	}

    function wc_add_pagcoin($methods)
    {
        $methods[] = 'WC_Gateway_PagCoin';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'wc_add_pagcoin' );
	add_filter('plugin_action_links', 'pagcoin_plugin_action_links', 10, 2);

	function pagcoin_plugin_action_links($links, $file)
	{
		static $this_plugin;

		if (!$this_plugin) {
			$this_plugin = plugin_basename(__FILE__);
		}

		if ($file == $this_plugin) {
			$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_gateway_pagcoin">Settings</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}
}

