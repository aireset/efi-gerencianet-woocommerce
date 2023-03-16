jQuery(document).ready(function( $ ) {

    $( document.body ).on( 'updated_checkout', function(){
        console.log("loaded-gn-payment");

        if(GNTYPE == "gn_sandbox"){
            var s = document.createElement("script");
            s.type = "text/javascript";
            var v = parseInt(Math.random() * 1000000);
            s.src = "https://sandbox.gerencianet.com.br/v1/cdn/"+ GNPAYEECODE +"/" + v;
            s.async = false;
            s.id = GNPAYEECODE;
            if (!document.getElementById(GNPAYEECODE)) {
                document.getElementsByTagName("head")[0].appendChild(s);
            }
            $gn = {
                validForm: true,
                processed: false,
                done: {},
                ready: function (fn) {
                    $gn.done = fn;
                },
            };
        }
        
        if(GNTYPE == "gn_production"){
            var s = document.createElement("script");
            s.type = "text/javascript";
            var v = parseInt(Math.random() * 1000000);
            s.src = "https://api.gerencianet.com.br/v1/cdn/"+ GNPAYEECODE +"/" + v;
            s.async = false;
            s.id = GNPAYEECODE;
            if (!document.getElementById(GNPAYEECODE)) {
                document.getElementsByTagName("head")[0].appendChild(s);
            }
            $gn = {
                validForm: true,
                processed: false,
                done: {},
                ready: function (fn) {
                    $gn.done = fn;
                },
            };
        }

        function detectCardType(number) {
            let cards = {
                visa: /^4[0-9]{12}(?:[0-9]{3})?$/,
                mastercard: /^5[1-5][0-9]{14}$/,
                amex: /^3[47][0-9]{13}$/,
                elo: /^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})/,
                hipercard: /^(606282\d{10}(\d{3})?)|(3841\d{15})/
            }

            for (let key in cards) {
                if (cards[key].test(number)) {
                    return key
                }
            }

            Swal.fire(
                'Bandeira do cartão não aceita',
                'Por favor, tente novamente com outro cartão. <br> Bandeiras aceitas: Visa, Mastercard, Amex, Elo, Hipercard',
                'info'
            )
            return 0;
        }
        

        $gn.ready(function (checkout) {
            console.log(checkout);

            // Gerar Payment Token
            $("#gn_cartao_expiration").on('keyup', function () {
                let cardNumber = $("#gn_cartao_number").val().replace(/\s/g, '');
                let cardCvv = $("#gn_cartao_cvv").val();
                let cardExpiration = $("#gn_cartao_expiration").val().split("/");

                if ($("#gn_cartao_expiration").val().length >= 7) {

                    Swal.fire({
                        title: 'Por favor, aguarde...',
                        text: '',
                        showConfirmButton: false,
                    })

                    let brand = detectCardType(cardNumber);

                    if (brand != 0) {
                        let params = {
                            brand: brand, // bandeira do cartão
                            number: cardNumber, // número do cartão
                            cvv: cardCvv, // código de segurança
                            expiration_month: cardExpiration[0], // mês de vencimento
                            expiration_year: cardExpiration[1] // ano de vencimento
                        }
                        console.log(params)
                        checkout.getPaymentToken(params,
                            function (error, response) {                            
                                if (error) {
                                    // Trata o erro ocorrido
                                    console.error(error);
                                    Swal.fire(
                                        'Ocorreu um erro',
                                        'Houve uma falha ao gerar o Token do seu cartão. Por favor, entre em contato com o administrador da loja.',
                                        'error'
                                    )
                                } else {
                                    console.log(response.data.payment_token)
                                    // Trata a resposta
                                    $('#gn_payment_token').val(response.data.payment_token);
                                    swal.close()
                                }
                            }
                        );
                    }
                }


            });


            // 4012 0010 3844 3335
            $("#gn_cartao_number").keyup(function () {
                let total = document.querySelector("#gn_payment_total").value;
                let cardNumber = $("#gn_cartao_number").val().replace(/\s/g, '');
                
                if (cardNumber.length >= 16) {
                    let brand = detectCardType(cardNumber);
                    if (brand != 0) {                    
                        checkout.getInstallments(
                            parseInt(total), // valor total da cobrança
                            brand, // bandeira do cartão
                            function (error, response) {
                                if (error) {
                                    // Trata o erro ocorrido
                                    console.log(error);
                                    Swal.fire(
                                        'Ocorreu um erro',
                                        'Houve um erro ao recuperar o número de parcelas. Por favor, entre em contato com o administrador da loja.',
                                        'error'
                                    )
                                } else {
                                    // Trata a respostae
                                    $('#gn_cartao_no_installments').hide();

                                    $('#gn_cartao_installments')
                                        .find('option')
                                        .remove()

                                    response.data.installments.forEach(element => {
                                        $('#gn_cartao_installments').append($('<option>', {
                                            value: element.installment,
                                            text: element.installment + 'x de R$' + element.currency
                                        }));
                                    });

                                    $('#gn_cartao_installments').show();
                                }
                            }
                        );
                    }
                }
            })

        });
        
    });

});