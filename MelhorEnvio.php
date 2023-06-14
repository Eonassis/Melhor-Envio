<?php

include ("Curl.php");

class MelhorEnvio{

	private $token;

	private $urlBase;

	private $curlInstance;

	public function __construct($token, $urlBase){

		$this->token = $token;
		$this->urlBase = $urlBase;
		$this->init();
	}

	private function init(){
		$this->curlInstance = new Curl();
		$this->curlInstance->setHeader("accept",  "application/json");
		$this->curlInstance->setHeader("authorization",  "Bearer {$this->token}");
		$this->curlInstance->setHeader("content-type", "application/json");
	}

	public function listarTransportadoras(){
		
		$lista = $this->curlInstance->getMelhor("{$this->urlBase}/shipment/companies");

		$arrayTransportadoras = array();

		$transportadoras = $this->curlInstance->getCallback();

		foreach ($transportadoras as $transportadora) {
			
			$objeto = array(
				'codigoServico' => $transportadora->id,
				'nome' => $transportadora->name
			);

			array_push($arrayTransportadoras, (object)$objeto);
		}

		return $arrayTransportadoras;
	}

	public function calcularFrete($from, $to, $produtos, $options, $services){

		$parametros = array(
			"from" => [
				"postal_code" => $from->cep
			],
			"to" => [
				"postal_code" => $to->cep
			],
			"products" => [],
			"options" => [
				"receipt" => false, 
			    "own_hand" => false, 
			    "collect" => false
			],
			"services" => "1,2,6,9,12,14"
		);
		
		$indice = 0;

		foreach ($produtos as $produto) {

			$parametros['products'][$indice]['weight'] = $produto->peso;
			$parametros['products'][$indice]['width'] = $produto->largura;
			$parametros['products'][$indice]['height'] = $produto->altura;
			$parametros['products'][$indice]['length'] = $produto->comprimento;
			$parametros['products'][$indice]['quantity'] = $produto->qtd;
			$parametros['products'][$indice]['insurance_value'] = 100;

			$indice++;
		}

		$this->curlInstance->postMelhor("{$this->urlBase}/shipment/calculate", $parametros);

		$resposta = $this->curlInstance->getCallback();

		
		return $this->tratarRespostaCalcularFrete($resposta);

	}

	private function tratarRespostaCalcularFrete($json){

		$response = array(
			'fretes' => [],
			'erros' => []
		);

		//se existir erro
		if(!empty($json->message) && !empty($json->errors)){

			$i=0;

			foreach ($json->errors as $erro) {
				$response['erros'][$i][] = $erro;
			}

		}else{

			$indice = 0;

			foreach ($json as $frete) {


				if (property_exists($frete, 'name') && property_exists($frete, 'custom_price') && property_exists($frete, 'delivery_time')) {
					$nomef = $frete->name;
					$precof = $frete->custom_price;
					$prazoEntregaf = $frete->delivery_time;
					}else{
					$nomef = "nao atende";
					$precof = "nao atende";
					$prazoEntregaf = "nao atende";
					}

				$objeto = array(
					'nome' => $nomef,
					'preco' => $precof,
					'prazoEntrega' => $prazoEntregaf
				);

				$response['fretes'][$indice][] = $objeto;

				$indice ++;
			}
		}

		return $response;
	}


	public function incluir_carrinho($from, $to, $produto, $service){
				
			$parametros = array(
			"from" => [
				"name" => $from->nome,
				"document" => $from->cpf,
				"phone" => $from->telefone,
      			"email" => $from->email,
				"address" => $from->endereco,
				"number" => $from->numero,
				"district" => $from->bairro,
				"city" => $from->cidade,
    			"state_abbr" => $from->estado,
				"postal_code" => $from->cep  
			],
			"to" => [
				"name" => $to->nome,
                "document" => $to->cpf,
                "phone" => $to->telefone,
                "email" => $to->email,
                "address" => $to->endereco,
                "number" => $to->numero,
                "district" => $to->bairro,
                "city" => $to->cidade,
                "state_abbr" => $to->estado,
                "postal_code" => $to->cep
			],
			"products" => [
				[
				"name" => $produto->nome,
				"quantity" => $produto->qtd,
				"unitary_value" => $produto->valor
				]
			],
			"volumes" => [
				  "height" => $produto->altura,
			      "width" => $produto->largura,
			      "length" => $produto->comprimento,
			      "weight" => $produto->peso
			],
			"options" => [
				"receipt" => false, 
			    "own_hand" => false, 
			    "collect" => false
			],
			"service" => $service
		);
		
		 


		$this->curlInstance->postMelhor("{$this->urlBase}/cart", $parametros);

		$resposta = $this->curlInstance->getCallback();

		
		return $resposta;

	}



	

	 


}





?>
