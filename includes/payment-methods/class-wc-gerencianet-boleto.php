<?php

use GN_Includes\Gerencianet_I18n;

function init_gerencianet_boleto() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	};

	class WC_Gerencianet_Boleto extends WC_Payment_Gateway {

		// payment gateway plugin ID
		public $id;
		public $has_fields;
		public $method_title;
		public $method_description;
		public $supports;

		public function __construct() {

			$this->id                 = GERENCIANET_BOLETO_ID; // payment gateway plugin ID
			$this->has_fields         = true; // custom form
			$this->method_title       = __( 'Efi - Boleto Bancário', 'efigerencianet-por-aireset' );
			$this->method_description = __( 'Venda com boleto bancário através da Efi.', 'efigerencianet-por-aireset' );

			$this->supports = array(
				'products',
			);

			$this->init_form_fields();

			$this->gerencianetSDK = new Gerencianet_Integration();

			$discountText = '';

			if ( $this->get_option( 'billet_discount' ) != '' && $this->get_option( 'billet_discount' ) != '0' && $this->get_option( 'billet_discount' ) != null && intval($this->get_option( 'billet_discount' )) > 0) {
				$discountText = ' - ' . esc_html( $this->get_option( 'billet_discount' ) ) . __('% de Desconto', 'efigerencianet-por-aireset' );
			}

			// Load the settings.
			$this->init_settings();
			$this->enabled                       = sanitize_text_field( $this->get_option( 'billet_banking' ) );
			$this->billet_unpaid                 = sanitize_text_field( $this->get_option( 'billet_unpaid' ) );
			$this->billet_discount            = sanitize_text_field( $this->get_option( 'billet_discount' ) );
			$this->billet_discount_shipping   = sanitize_text_field( $this->get_option( 'billet_discount_shipping' ) );
			$this->billet_number_days         = sanitize_text_field( $this->get_option( 'billet_number_days' ) );
			$this->client_id_production       = sanitize_text_field( $this->get_option( 'client_id_production' ) );
			$this->client_secret_production   = sanitize_text_field( $this->get_option( 'client_secret_production' ) );
			$this->client_id_homologation     = sanitize_text_field( $this->get_option( 'client_id_homologation' ) );
			$this->client_secret_homologation = sanitize_text_field( $this->get_option( 'client_secret_homologation' ) );
			$this->sandbox                    = sanitize_text_field( $this->get_option( 'sandbox' ) );

			
			$this->title                         = __( 'Efi - Boleto Bancário', 'efigerencianet-por-aireset' ) . $discountText;
			$this->description                   = sprintf( __( 'Realize o pagamento através de boleto pela Efí, será confirmado em até %s úteis.', 'efigerencianet-por-aireset' ), $this->billet_number_days );

			// // This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . GERENCIANET_BOLETO_ID, array( $this, 'process_admin_options' ) );

			// This hook add the "view payment Methods" button
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'add_view_payment_methods' ) );
			
			// wp_enqueue_script( 'gn-sweetalert-js', GERENCIANET_OFICIAL_PLUGIN_URL . 'assets/js/sweetalert.js', ['jquery' ], GERENCIANET_OFICIAL_VERSION, true );

			add_action( 'woocommerce_api_' . strtolower( GERENCIANET_BOLETO_ID ), array( $this, 'webhook' ) );
		}

		public function init_form_fields() {

			$this->form_fields = array(
                'title' => [
                    'title' => __('Title', 'efigerencianet-por-aireset'),
                    'type' => 'text',
                    'description' => __(
                        'This controls the title which the user sees during checkout.',
                        'efigerencianet-por-aireset'
                    ),
                    'default' => $this->method_title,
                    'desc_tip' => true,
                ],
				'status_section' => array(
					'title'       => __( 'Status Gerencianet', 'efigerencianet-por-aireset' ),
					'type'        => 'title',
					'description' => __( "Selecione o status que deseja", 'efigerencianet-por-aireset' ),
				),
                'order_status' => [
                    'title' => __('Status Inicial do Pedido', 'efigerencianet-por-aireset'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __(
                        'Choose whether status you wish after checkout.',
                        'efigerencianet-por-aireset'
                    ),
                    'default' => 'wc-on-hold',
                    'desc_tip' => true,
                    'options' => wc_get_order_statuses(),
                ],
                'order_status_payed' => [
                    'title' => __('Status do Pedido Pago', 'efigerencianet-por-aireset'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __(
                        'Choose whether status you wish after checkout.',
                        'efigerencianet-por-aireset'
                    ),
                    'default' => 'wc-processing',
                    'desc_tip' => true,
                    'options' => wc_get_order_statuses(),
                ],
				'api_section'                => array(
					'title'       => __( 'Credenciais Gerencianet', 'efigerencianet-por-aireset' ),
					'type'        => 'title',
					'description' => __( "<a href='https://gerencianet.com.br/artigo/como-obter-chaves-client-id-e-client-secret-na-api/#versao-7' target='_blank'>Clique aqui para obter seu Client_id e Client_secret! </a>", 'efigerencianet-por-aireset' ),
				),
				'client_id_production'       => array(
					'title'       => __( 'Client_id Produção', 'efigerencianet-por-aireset' ),
					'type'        => 'text',
					'description' => __( 'Por favor, insira seu Client_id. Isso é necessário para receber o pagamento.', 'efigerencianet-por-aireset' ),
					'desc_tip'    => false,
					'default'     => '',
				),
				'client_secret_production'   => array(
					'title'       => __( 'Client_secret Produção', 'efigerencianet-por-aireset' ),
					'type'        => 'text',
					'description' => __( 'Por favor, insira seu Client_secret. Isso é necessário para receber o pagamento.', 'efigerencianet-por-aireset' ),
					'desc_tip'    => false,
					'default'     => '',
				),
				'client_id_homologation'     => array(
					'title'       => __( 'Client_id Homologação', 'efigerencianet-por-aireset' ),
					'type'        => 'text',
					'description' => __( 'Por favor, insira seu Client_id de Homologação. Isso é necessário para testar os pagamentos.', 'efigerencianet-por-aireset' ),
					'desc_tip'    => false,
					'default'     => '',
				),
				'client_secret_homologation' => array(
					'title'       => __( 'Client_secret Homologação', 'efigerencianet-por-aireset' ),
					'type'        => 'text',
					'description' => __( 'Por favor, insira seu Client_secret de Homologação. Isso é necessário para testar os pagamentos.', 'efigerencianet-por-aireset' ),
					'desc_tip'    => false,
					'default'     => '',
				),
				'sandbox_section'            => array(
					'title'       => __( 'Ambiente Sandbox', 'efigerencianet-por-aireset' ),
					'type'        => 'title',
					'description' => 'Habilite para usar o ambiente de testes da Gerencianet. Nenhuma cobrança emitida nesse modo poderá ser paga.',
				),
				'sandbox'                    => array(
					'title'   => __( 'Sandbox', 'efigerencianet-por-aireset' ),
					'type'    => 'checkbox',
					'label'   => __( 'Habilitar o ambiente sandbox', 'efigerencianet-por-aireset' ),
					'default' => 'no',
				),
				'billet_section'             => array(
					'title' => __( 'Configurações de recebimento', 'efigerencianet-por-aireset' ),
					'type'  => 'title',
				),
				'billet_banking'             => array(
					'title'   => __( 'Boleto', 'efigerencianet-por-aireset' ),
					'type'    => 'checkbox',
					'label'   => __( 'Habilitar Boleto', 'efigerencianet-por-aireset' ),
					'default' => 'no',
				),
				'billet_unpaid'              => array(
					'title'       => __( 'Cancelar Boletos inadimplentes?', 'efigerencianet-por-aireset' ),
					'type'        => 'checkbox',
					'label'       => __( 'Habilitar o cancelamento de Boletos não pagos', 'efigerencianet-por-aireset' ),
					'description' => __( 'Quando ativado, cancela todos os Boletos que não foram pagos. Evitando que o cliente pague o Boleto após o vencimento.', 'efigerencianet-por-aireset' ),
					'default'     => 'no',
				),
				'billet_discount'            => array(
					'title'       => __( 'Desconto no Boleto', 'efigerencianet-por-aireset' ),
					'type'        => 'number',
					'description' => __( 'Porcentagem de desconto para pagamento com Boleto. (Opcional)', 'efigerencianet-por-aireset' ),
					'desc_tip'    => false,
					'placeholder' => '10',
					'default'     => '0',
				),
				'billet_discount_shipping'   => array(
					'title'       => __( 'Aplicar desconto do Boleto', 'efigerencianet-por-aireset' ),
					'type'        => 'select',
					'description' => __( 'Escolha a modalidade de desconto.', 'efigerencianet-por-aireset' ),
					'default'     => 'total',
					'options'     => array(
						'total'    => __( 'Aplicar desconto no valor total com Frete', 'efigerencianet-por-aireset' ),
						'products' => __( 'Aplicar desconto apenas no preço dos produtos', 'efigerencianet-por-aireset' ),
					),
				),
				'billet_number_days'         => array(
					'title'       => __( 'Vencimento do Boleto', 'efigerencianet-por-aireset' ),
					'type'        => 'number',
					'description' => __( 'Dias para expirar o Boleto depois de emitido.', 'efigerencianet-por-aireset' ),
					'desc_tip'    => false,
					'placeholder' => '0',
					'default'     => '5',
				),
				
			);
		}

		public function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wp_kses_post( $this->description ) );
			}

			if(intval( $this->get_option( 'billet_discount' ) ) > 0){
				$discountMessage = '';
				if ( $this->get_option( 'billet_discount_shipping' ) == 'total' ) {
					$discountMessage = __(' no valor total da compra (frete incluso)', 'efigerencianet-por-aireset' );
				}else{
					$discountMessage = __(' no valor total dos produtos (frete não incluso)', 'efigerencianet-por-aireset' );
				}
				
				$discountWarn = '<div class="warning-payment" id="wc-gerencianet-messages-sandbox">
										<div class="woocommerce-info">' .esc_html( $this->get_option( 'billet_discount' ) ) . '% de Desconto'.$discountMessage. '</div>
									</div>';
				echo wpautop( wp_kses_post( $discountWarn ) );
			}
			
			$is_sandbox = $this->get_option( 'sandbox' ) == 'yes' ? true : false;
			if ( $is_sandbox ) {
				$sandboxWarn = '<div class="warning-payment" id="wc-gerencianet-messages-sandbox">
                                    <div class="woocommerce-error">' . __( 'O modo Sandbox está ativo. As cobranças emitidas não serão válidas.', 'efigerencianet-por-aireset' ) . '</div>
                                </div>';
				echo wpautop( wp_kses_post( $sandboxWarn ) );
			}

			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent; border: none;">';

			?>
				<div class="form-row form-row-wide" id="gn_field_boleto">
					<label>CPF/CNPJ <span class="required">*</span></label>
					<input id="gn_boleto_cpf_cnpj" name="gn_boleto_cpf_cnpj" type="text" placeholder="___.___.___-__" autocomplete="off" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
				</div>
				<div class="clear"></div></fieldset>
			<?php

		}

		public function validate_fields() {

			if ( empty( sanitize_text_field( $_POST['gn_boleto_cpf_cnpj'] ) ) ) {
				wc_add_notice( __( 'CPF é obrigatório!', 'efigerencianet-por-aireset' ), 'error' );
				return false;
			} else {
				$cpf_cnpj = preg_replace( '/[^0-9]/', '', sanitize_text_field( $_POST['gn_boleto_cpf_cnpj'] ) );
				if ( strlen( $cpf_cnpj ) == 11 ) {
					// cpf
					return Gerencianet_Validate::cpf( $cpf_cnpj );
				} else {
					// cnpj
					return Gerencianet_Validate::cnpj( $cpf_cnpj );
				}
			}
			return true;
		}

		public function process_payment( $order_id ) {

			global $woocommerce;

			$order = wc_get_order( $order_id );

			$types          = array( 'line_item', 'fee', 'shipping', 'coupon' );
			$items          = array();
			$shipping       = array();
			$discount       = false;
			$orderTotal     = 0;
			$shippingTotal  = 0;
			$expirationDate = date( 'Y-m-d' );
			// get the Items
			foreach ( $order->get_items( $types ) as $item_id => $item ) {

				switch ( $item->get_type() ) {
					case 'fee':
						$newFee      = array(
							'name'   => __( 'Taxas', 'efigerencianet-por-aireset' ),
							'amount' => 1,
							'value'  => $item->get_subtotal() * 100,
						);
						$orderTotal += $item->get_subtotal() * 100;
						$items[]     = $newFee;
						break;
					case 'shipping':
						if ( $item->get_total() > 0 ) {
							$shipping[] = array(
								'name'  => __( 'Frete', 'efigerencianet-por-aireset' ),
								'value' => $item->get_total() * 100,
							);
						    $shippingTotal += $item->get_total();
						}
						break;
					case 'coupon':
						$newDiscount = array(
							'type'  => 'currency',
							'value' => $item->get_discount() * 100,
						);
						$discount    = $newDiscount;
						break;
					case 'line_item':
						$product     = $item->get_product();
						if(!empty($product->get_price())){
						    $newItem     = array(
    							'name'   => $product->get_name(),
    							'amount' => $item->get_quantity(),
    							'value'  => $product->get_price() * 100,
    						);
						    $orderTotal += $product->get_price() * 100 * $item->get_quantity();
						} else {
						    $newItem     = array(
    							'name'   => $product->get_name(),
    							'amount' => $item->get_quantity(),
    							'value'  => $item->get_subtotal() * 100,
    						);
						    $orderTotal += $item->get_subtotal() * 100;
						}
						$items[]     = $newItem;
						break;
					default:
						$product     = $item->get_product();
						$newItem     = array(
							'name'   => $item->get_name(),
							'amount' => $item->get_quantity(),
							'value'  => $product->get_price() * 100,
						);
						$orderTotal += $product->get_price() * 100 * $item->get_quantity();
						$items[]     = $newItem;
						break;
				}
			}

			if($order->get_total_tax()>0){
				$newItem     = array(
					'name'   => 'Taxas',
					'amount' => 1,
					'value'  => $order->get_total_tax() * 100,
				);
				array_push($items, $newItem);
			}

			$cpf_cnpj = preg_replace( '/[^0-9]/', '', sanitize_text_field( $_POST['gn_boleto_cpf_cnpj'] ) );
			if ( Gerencianet_Validate::cpf( $cpf_cnpj ) ) {
				$customer = array(
					'name' => $order->get_formatted_billing_full_name(),
					'cpf'  => $cpf_cnpj,
				);
			} elseif ( Gerencianet_Validate::cnpj( $cpf_cnpj ) ) {
				$customer = array(
					'juridical_person' => array(
						'corporate_name' => $order->get_billing_company() != '' ? $order->get_billing_company() : $order->get_formatted_billing_full_name(),
						'cnpj'           => $cpf_cnpj,
					),
				);
			} else {
				wc_add_notice( __( 'Verifique seu CPF/CNPJ e tente emitir novamente!', 'efigerencianet-por-aireset' ), 'error' );
				return;
			}

			if ( $this->get_option( 'billet_discount' ) != '' && $this->get_option( 'billet_discount' ) != '0' ) {

				if ( ! isset( $discount['value'] ) ) {
					$discount = array(
						'type'  => 'currency',
						'value' => 0,
					);
				}
				
				$discount_gn = 0;

				if ( $this->get_option( 'billet_discount_shipping' ) == 'total' ) {
					if ( isset( $shipping[0]['value'] ) ) {
						$discount = ( ( $orderTotal + $shipping[0]['value'] ) * ( intval( $this->get_option( 'billet_discount' ) ) / 100 ) );
						$discount['value'] += $discount_gn;
					} else {
						$discount = ( ( $orderTotal ) * ( intval( $this->get_option( 'billet_discount' ) ) / 100 ) );
						$discount['value'] += $discount_gn;
					}
					$discountMessage = ' no valor total da compra';
				} else {				    
					$discount_gn = ( ( $orderTotal - $shippingTotal ) * ( intval( $this->get_option( 'billet_discount' ) ) / 100 ) );
					$discount['value'] += $discount_gn;
					$discountMessage = ' no valor total dos produtos (frete não incluso)';
				}
				$order_item_id = wc_add_order_item(
					$order_id,
					array(
						'order_item_name' => $this->get_option( 'billet_discount' ) . __( '% de desconto no boleto' ).$discountMessage,
						'order_item_type' => 'fee',
					)
				);
				if ( $order_item_id ) {
					wc_add_order_item_meta( $order_item_id, '_fee_amount', -$discount_gn / 100, true );
					wc_add_order_item_meta( $order_item_id, '_line_total', -$discount_gn / 100, true );
					$order->set_total( $order->get_total() - ( $discount_gn / 100 ) );
					$order->save();

				}
			}

			if ( $this->get_option( 'billet_number_days' ) != '' && $this->get_option( 'billet_number_days' ) != '0' ) {
				$today          = date( 'Y-m-d' );
				$numberDays     = $this->get_option( 'billet_number_days' );
				$expirationDate = date( 'Y-m-d', strtotime( '+' . $numberDays . ' days', strtotime( $today ) ) );
			}

			try {
				$response = $this->gerencianetSDK->one_step_billet( $order_id, $items, $shipping, strtolower( $woocommerce->api_request_url( GERENCIANET_BOLETO_ID ) ), $customer, $expirationDate, $discount );
				$charge   = json_decode( $response, true );

				if ( isset( $charge['data']['barcode'] ) ) {
					update_post_meta( $order_id, '_gn_barcode', $charge['data']['barcode'] );
				}
				if ( isset( $charge['data']['pix'] ) ) {
					update_post_meta( $order_id, '_pix_qrcode', $charge['data']['pix']['qrcode_image'] );
					update_post_meta( $order_id, '_pix_copy', $charge['data']['pix']['qrcode'] );
				}
				if ( isset( $charge['data']['link'] ) ) {
					update_post_meta( $order_id, '_gn_link_responsive', $charge['data']['link'] );
					update_post_meta( $order_id, '_gn_link_pdf', $charge['data']['pdf']['charge'] );
				}

				$order->update_status( 'pending-payment' );
				wc_reduce_stock_levels( $order_id );
				$woocommerce->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
				return;
			}
		}

		public function webhook() {
			$post_notification = sanitize_text_field( $_POST['notification'] );
			if ( isset( $post_notification ) && ! empty( $post_notification ) ) {
				header( 'HTTP/1.0 200 OK' );

				$notification = json_decode( $this->gerencianetSDK->getNotification( GERENCIANET_BOLETO_ID, $post_notification ) );
				if ( $notification->code == 200 ) {

					foreach ( $notification->data as $notification_data ) {
						$orderIdFromNotification     = sanitize_text_field( $notification_data->custom_id );
						$orderStatusFromNotification = sanitize_text_field( $notification_data->status->current );
						$gerencianetChargeId         = sanitize_text_field( $notification_data->identifiers->charge_id );
					}

					$order = wc_get_order( $orderIdFromNotification );

					switch ( $orderStatusFromNotification ) {
						case 'paid':
							$order->update_status( 'processing' );
							$order->payment_complete();
							break;
						case 'unpaid':
							$order->update_status( 'failed' );

							if ( $this->get_option( 'billet_unpaid' ) == 'yes' ) {
								$this->gerencianetSDK->cancel_charge( $gerencianetChargeId );
							}

							break;
						case 'refunded':
							$order->update_status( 'refund' );
							break;
						case 'contested':
							$order->update_status( 'failed' );
							break;
						case 'canceled':
							$order->update_status( 'cancelled' );
							break;
						default:
							// no action
							break;
					}
				} else {
					error_log( 'gerencianet-aireset', 'GERENCIANET :: notification Request : FAIL ' );
				}

				exit();

			} else {
				wp_die( __( 'Request Failure', 'efigerencianet-por-aireset' ) );
			}
		}

		public static function getMethodId() {
			return self::$id;
		}

		public function add_view_payment_methods( $order ) {
			if ( $order->get_payment_method() != $this->id ) {
				return;
			}
			?>

				<style>

					.gngrid-container {
						display: grid;
						grid-template-columns: 50% 50%;
						overflow: hidden;
					}
					.gngrid-item {
						padding: 20px;
						font-size: 30px;
					}

					.gn-item-area {
						border-radius: 1.2rem;
						transition: transform 300ms ease;
					}

					.gn-item-area:hover {
						transform: scale(1.02);
					}

					.gn-btn{
						width: 100%;
						background: #EB6608 !important; 
						color:#ffff !important; 
						border:none;
						border-color: #EB6608 !important; 
						font-size: 1rem !important; 
						padding: 3px 5px 3px 5px !important;
					}
				</style>

				<script>

					function gncopy($field){
						let copyText, id, btnlabel;
						if($field == 1){
							id="gnbarcode";
							btnlabel = 'Copiar Código de Barras'
							copyText = '<?php echo esc_html(get_post_meta( $order->get_id(), '_gn_barcode', true )); ?>';
						}else{
							id="gnpix";
							btnlabel = 'Copiar Pix Copia e Cola'
							copyText = '<?php echo esc_html(get_post_meta( $order->get_id(), '_pix_copy', true )); ?>'
						}
						
						document.getElementById(id).innerHTML = 'Copiado!';
						navigator.clipboard.writeText(copyText);
						setTimeout(()=> {
							document.getElementById(id).innerHTML = btnlabel;
						},1000)
					}

					function viewGnInfos(params) {
						Swal.fire({
							title: 'Meios de Pagamento Disponíveis',
							icon: 'info',
							html: '<div class="gngrid-container"><?php if ( get_post_meta( $order->get_id(), '_pix_copy', true ) !== NULL ) { ?><div class="gngrid-item"><div class="gn-item-area"><img style="width:150px;"src="<?php echo esc_url(plugins_url( 'woo-gerencianet-official/assets/img/pix-copia.png' ))?>" /><br><a onclick="gncopy(2)" class="button gn-btn" id="gnpix">Copiar Pix Copia e Cola</a></div></div><?php }if ( get_post_meta( $order->get_id(), '_gn_link_responsive', true ) !== NULL ) {	?><div class="gn-item-area"><div class="gngrid-item"><img style="width:150px;" src="<?php echo esc_url(plugins_url('woo-gerencianet-official/assets/img/boleto-online.png' )); ?>" /><br><a href="<?php echo esc_url(get_post_meta( $order->get_id(), '_gn_link_responsive', true )); ?>" target="_blank" class="button gn-btn">Acessar Boleto Online</a></div></div>	<?php }	if ( get_post_meta( $order->get_id(), '_gn_barcode', true ) !== NULL ) {?><div class="gn-item-area"><div class="gngrid-item"><img style="width:150px;" src="<?php echo esc_url(plugins_url('woo-gerencianet-official/assets/img/copy-barcode.png' )); ?>" /><br><a onclick="gncopy(1)" class="button gn-btn" id="gnbarcode">Copiar Código de Barras</a></div></div><?php }if(get_post_meta( $order->get_id(), '_gn_link_pdf', true ) !== NULL) {	?><div class="gn-item-area"><div class="gngrid-item"><img style="width:150px;" src="<?php echo esc_url(plugins_url('woo-gerencianet-official/assets/img/download-boleto.png' )); ?>" /><br><a href="<?php echo esc_url( get_post_meta( $order->get_id(), '_gn_link_pdf', true ) ); ?>" target="_blank" class="button gn-btn">Baixar Boleto</a></div></div><?php } ?></div>',
							showCloseButton: true,
							showCancelButton: false,
							showConfirmButton: false
						})
					}

				</script>
				<div class="gn_payment_methods">
					<a onclick="viewGnInfos()" style="background: #EB6608; color:#ffff; border:none;" class="button">
					<span class="dashicons dashicons-visibility" style="margin-top:4px;"></span>
					<?php echo __( 'Ver métodos de pagamento', 'efigerencianet-por-aireset' ); ?>
					</a>
				</div>
				
				<?php
			}
		
	}
}
