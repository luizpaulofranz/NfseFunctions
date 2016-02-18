<?php
/*
 * BASEADO NO PROJETO nfephp-master.
 * 
 * Created at 2016-02-18 17:47
 * 
 *@package    Undefined
 *@subpackage /lib
 *@name       NFSeFunctions
 *@version    0.0.1
 *@copyright  2016 &copy; NFSeFunctions
 *@author     Luiz Paulo Franz <luizpaulofranz at gmail dot com>
 * 
 * Baseado no projeto NFePHP
 */
abstract class NfseFunctions{
    
    //sistema de emissão NFSE
    private $sistemaEmissao=0;
    //namespace para as tags de assinatura do xml
    private $URLdsig='http://www.w3.org/2000/09/xmldsig#';
    //dados fixos para a assinatura digital nao sei o que sao huehue brbr
    private $URLCanonMeth='http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $URLSigMeth='http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private $URLTransfMeth_1='http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private $URLTransfMeth_2='http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $URLDigestMeth='http://www.w3.org/2000/09/xmldsig#sha1';
    //nome emitente (imobiliaria)
    protected $nomeEmi='';
    //UF do emitente (imobiliaria)
    protected $UFEmi='';
    //CNPJ do emitente (imobiliaria)
    protected $cnpjEmi='';
    //Inscricao Estadual do emitente (imobiliaria)
    protected $inscricaoEstadualEmi='';
    //Inscricao Municipal do emitente (imobiliaria)
    protected $inscricaoMunicipalEmi='';
    //vencimento do certificado no formato timestamp
    protected $pfxTimestamp='';
    //obj imobiliaria
    protected $imobi='';
    //obj configuracoes imobiliaria
    protected $confImobi='';
    //configuracao imobiliaria optante simples nacional
    protected $optanteSimplesNacional='';
    //Valor da aliquota do iss
    protected $aliquotaIss=0;
    //quantidade de notas enviadas
    protected $quantidadeRps=1;
    //Raiz dos diretorios dentro da pasta httpdocs
    protected $raizDir='';
    //String do xml da nota
    protected $nfsexml='';
    //Contem as mensagens de erro, caso existam
    protected $errMsg='';
    //indica se há algum erro ou não
    protected $errStatus=false;
    //Contem o nome do arquivo de certificado
    protected $certName='';
    //contera o diretorio dos certificados
    protected $certsDir='';
    //Chave privada
    protected $priKEY='';
    //Chave publica
    protected $pubKEY='';
    //Certificado digital
    protected $certKEY='';
    //Alternar entre ambiente de testes e producao
    protected $ambienteProducao=true;
    // CONSTANTES usadas no controle das exceções  //
    const WARNING_MESSAGE = 1; // apenas um aviso, o processamento continua
    const STOP_CRITICAL   = 2; // Erro critico, interrupcao total
    // CONSTANTES para indicar o sistema de emissao //
    const BETHA_SISTEMAS = 1; //SMO
    const ISSNET = 2; //Santa Maria
    /**
     * baseEmitente
     * Método Base da classe para emissao de notas
     * Este método carrega os dados do emitente (imobiliaria) nas variaveis padrao
     * Utilizados na geracao das notas
     * 
     * @package /lib
     * @param $carregarCertificados Booleano, indica se devemos ou nao carregar os certificados
     * @return  boolean true sucesso false Erro
     */
    //Esse método deve ser alterado, colocando os dados da empresa emitente, carregando do banco, manualmente como quiser.
    //Esses dados devem estar em um cadastro de empresas emitentes.
    function baseEmitente($carregarCertificados=true){
        //###### EMPRESA EMITENTE #######
        //Nome da Empresa 
        $this->nomeEmi='Empresa XYZ';
        //Sigla da UF
        $this->UFEmi='SC';
        //Numero do CNPJ
        $this->cnpjEmi=str_replace(array('/','-','.'), '', '11.425.872/0001-18');
        //Incricao Estadual
        $this->inscricaoEstadualEmi='1234';
        //Incricao Municiapal
        $this->inscricaoMunicipalEmi='1234';
        //diretorio raiz (/httpdocs)
        $this->raizDir='/notas';
        //Opcao Optante simples nacional
        //1=Sim 2=Nao
	/*
        if($this->confImobi->getOptanteSimplesNacional()){
            $this->optanteSimplesNacional=1;
        }else{
            $this->optanteSimplesNacional=2;
        }
	*/
        $this->optanteSimplesNacional=2;
	/*
        if(!$this->confImobi->getAliquotaIss()){
            throw new nfsephpException('Aliquota ISS não foi definida.',self::STOP_CRITICAL);
            return false;
        }else{
            //dividimos a liquota por 100, se for 4%, deve ficar 0.04
            $this->aliquotaIss=$this->confImobi->getAliquotaIss()/100;
        }
	*/
        //dividimos a liquota por 100, se for 4%, deve ficar 0.04
        $this->aliquotaIss=4/100;
        //Sistemas de Nfse
        //1=Betha
	//2=ISSNET
        $this->sistemaEmissao=1;//atualmente é o único funcionando

        if($carregarCertificados){
            if($this->baseCerts()){
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
    }//fim baseEmitente

    /**
     * baseCerts
     * Método base para notas que utilizam certificados na emissao de notas.
     * Este método utiliza o configurações setadas em uma tabela de configurações.
     * 
     * Utilizada para fazer as analizes referente aos certificados digitais
     * 
     * @return  boolean true sucesso false Erro
     */
    function baseCerts(){
            //testa a existencia das configurações no banco
	    //as seguintes variáveis devem ser setadas pelo seu sistema:
	    $senhaPfxNfse = 'minha senha';
	    $codigoMunicipioIBGE = '1234';
            if ($senhaPfxNfse && $codigoMunicipioIBGE){
                //#### CERITIFICADO DIGITAL #####
                //Nome do certificado que deve ser colocado em uma pasta especifica de seu emitente
                //arquivo de certificado deve ser sempre .pfx, se for .p12, apenas alterar a extencao q vai funcionar
                $this->certName='certificado.pfx';
                //############ PROXY ############
                //Configuração de proxy (nao usado por enquanto, mas ta aeh se precisar =D)
                $proxyIP='';
                $proxyPORT='';
                $proxyUSER='';
                $proxyPASS='';
                if ($proxyIP != ''){
                    $this->aProxy = array('IP'=>$proxyIP,'PORT'=>$proxyPORT,'USER'=>$proxyUSER,'PASS'=>$proxyPASS);
                }
            } else {
                // caso não exista dados de configuração no banco retorna erro
                $msg = "Senha do certificado e Código do Município são necessários nas configurações da imobiliária.\n";
                //primeiro a msg da execao, depois o codigo do erro
                throw new nfsephpException($msg,self::STOP_CRITICAL);
                return false;
            }
        //carrega o caminho para os certificados
        $this->certsDir = $this->raizDir . DIRECTORY_SEPARATOR.'emitentes'.DIRECTORY_SEPARATOR.'certs'.DIRECTORY_SEPARATOR;
        //carregar o certificado digital
        if($this->__loadCerts()){
            return true;
        }else{
            return false;
        }
    } //fim __beseCerts
    
    /**
     * __loadCerts
     * Método usado para abrir os arquivos .pfx e extrair os dados como chave privada
     * chave publica. Tambem analiza a validade do certificado, de acordo com o 
     * parametro $testaVal.
     * @version 1.0
     * @package NfseFunctions
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param $testaVal usada para verificar a validade dos certificados 
     * Utilizada para fazer as analizes referente aos certificados digitais
     * 
     * @return  boolean true sucesso false Erro
     */
    protected function __loadCerts($testaVal=true){
        $msg = "Erro no carregamento dos certificados.<br/>";
        if(!function_exists('openssl_pkcs12_read')){
            $msg .= "Função não existente: openssl_pkcs12_read!!";
            throw new nfsephpException($msg,self::STOP_CRITICAL);
            return false;
        }
        //monta o path completo com o nome da chave privada
        $this->priKEY = $this->certsDir.'priKEY.pem';
        //monta o path completo com o nome da chave prublica
        $this->pubKEY = $this->certsDir.'pubKEY.pem';
        //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
        $this->certKEY = $this->certsDir.'certKEY.pem';
        //verificar se o nome do certificado e
        //o path foram carregados nas variaveis da classe
        if ($this->certsDir == '' || $this->certName == '') {
            $msg .= "Um certificado deve ser passado para a classe pelo arquivo de configuração!";
            throw new nfsephpException($msg,self::STOP_CRITICAL);
        }
        //monta o caminho completo ate o certificado pfx
        $pfxCert = $this->certsDir.$this->certName;
        //verifica se o arquivo existe
        if(!file_exists($pfxCert)){
            $msg .= "Arquivo do Certificado não encontrado! $pfxCert";
            throw new nfsephpException($msg,self::STOP_CRITICAL);
        }
        //carrega o certificado em um string
        $pfxContent = file_get_contents($pfxCert);
        //carrega os certificados e chaves para um array denominado $x509certdata
        if (!openssl_pkcs12_read($pfxContent,$x509certdata,$this->confImobi->getSenhaPfxNfse()) ){
            $msg .= "O certificado não pode ser lido. Pode estar corrompido ou a senha cadastrada está errada!";
            throw new nfsephpException($msg,self::STOP_CRITICAL);
        }
	//Verifica se o certificado é válido
        if ($testaVal){
            try {
                $this->__validCerts($x509certdata['cert']);
            }  catch (nfsephpException $e){
                //rethrow, fazer o mesmo throw, para a mesma classe usando a mesa execao, sem criar uma nova
                throw $e;
                //se for erro ou warning
                if($e->getCode()==self::STOP_CRITICAL){
                    return false;
                }
            }
        }
        //aqui verifica se existem as chaves em formato PEM
        //se existirem pega a data da validade dos arquivos PEM 
        //e compara com a data de validade do PFX
        //caso a data de validade do PFX for maior que a data do PEM
        //deleta dos arquivos PEM, recria e prossegue
        $flagNovo = false;
        if(file_exists($this->pubKEY)){
            $cert = file_get_contents($this->pubKEY);
            if (!$data = openssl_x509_read($cert)){
                //arquivo não pode ser lido como um certificado 
                //entao deletar
                $flagNovo = true;
            } else {
                //pegar a data de validade do mesmo
                $cert_data = openssl_x509_parse($data);
                // reformata a data de validade;
                $ano = substr($cert_data['validTo'],0,2);
                $mes = substr($cert_data['validTo'],2,2);
                $dia = substr($cert_data['validTo'],4,2);
                //obtem o timeestamp da data de validade do certificado
                $dValPubKey = gmmktime(0,0,0,$mes,$dia,$ano);
                //var_dump(date('d/m/Y',$dValPubKey));exit();
                //compara esse timestamp com o do pfx que foi carregado
                if ($testaVal){
                    //$this->pfxTimestamp global setada na funcao __validCerts()
                    if( $dValPubKey < $this->pfxTimestamp){
                        //o arquivo PEM eh de um certificado anterior 
                        //entao apagar os arquivos PEM
                        $flagNovo = true;
                    }//fim teste timestamp
                }
            }//fim read pubkey
        } else {
            //arquivo não localizado
            $flagNovo = true;
        }//fim if file pubkey
        //verificar a chave privada em PEM
        if(!file_exists($this->priKEY)){
            //arquivo nao encontrado
            $flagNovo = true;
        }
        //verificar o certificado em PEM
        if(!file_exists($this->certKEY)){
            //arquivo não encontrado
            $flagNovo = true;
        }
        //criar novos arquivos PEM
        if ($flagNovo){
            if(file_exists($this->pubKEY)){
                unlink($this->pubKEY);
            }
            if (file_exists($this->priKEY)){	
                unlink($this->priKEY);
            }
            if (file_exists($this->certKEY)){
                unlink($this->certKEY);
            }
            //recriar os arquivos pem com o arquivo pfx
            if (!file_put_contents($this->priKEY,$x509certdata['pkey'])) {
                $msg .= "Impossivel gravar no diretório! Permissão negada!";
                throw new nfsephpException($msg,self::STOP_CRITICAL);
                return false;
            }    
            $n = file_put_contents($this->pubKEY,$x509certdata['cert']);
            $n = file_put_contents($this->certKEY,$x509certdata['pkey']."\r\n".$x509certdata['cert']);                    
        }
        return true;
    } //fim __loadCerts
    
    /**
    * __validCerts
    * Validacao do cerificado digital, alem de indicar
    * a validade, este metodo carrega a propriedade
    * mesesToexpire da classe que indica o numero de
    * meses que faltam para expirar a validade do mesmo
    * esta informacao pode ser utilizada para a gestao dos
    * certificados de forma a garantir que sempre estejam validos
    * @version 1.0
    * @package NfseFunctions
    * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
    * @name __validCerts
    * @param    string  $cert Certificado digital no formato pem
    * @param    array   $aRetorno variavel passa por referencia Array com os dados do certificado
    * @return	boolean true ou false
    */
    protected function __validCerts($cert='',&$aRetorno=''){
        $msg = "Erro no carregamento dos certificados.<br/>";
        if ($cert == ''){
            $msg .= "O certificado é um parâmetro obrigatorio.";
            throw new nfsephpException($msg,self::STOP_CRITICAL);
            return false;
        }
        if (!$data = openssl_x509_read($cert)){
            $msg .= "O certificado não pode ser lido pelo SSL - $cert .";
            throw new nfsephpException($msg,self::STOP_CRITICAL);
            return false;
        }
        $flagOK = true;
        $errorMsg = "";
        $cert_data = openssl_x509_parse($data);
        // reformata a data de validade;
        $ano = substr($cert_data['validTo'],0,2);
        $mes = substr($cert_data['validTo'],2,2);
        $dia = substr($cert_data['validTo'],4,2);
        //obtem o timestamp da data de validade do certificado
        $dValid = gmmktime(0,0,0,$mes,$dia,$ano);
        // obtem o timestamp da data de hoje
        $dHoje = gmmktime(0,0,0,date("m"),date("d"),date("Y"));
        //var_dump("$dia/$mes/$ano");exit();
        // compara a data de validade com a data atual
        if ($dValid < $dHoje ){
            $flagOK = false;
            $errorMsg = "A Validade do certificado expirou em ".$dia.'/'.$mes.'/'.$ano."";
            //alert para validade ultrapassada
            throw new nfsephpException($errorMsg, self::WARNING_MESSAGE);
        }else{
            $flagOK = $flagOK && true;
        }
        //diferenca em segundos entre os timestamp
        $diferenca = $dValid - $dHoje;
        // convertendo para dias
        $diferenca = round($diferenca /(60*60*24),0);
        //carregando a propriedade
        $daysToExpire = $diferenca;
        // convertendo para meses e carregando a propriedade
        $m = ($ano * 12 + $mes);
        $n = (date("y") * 12 + date("m"));
        //numero de meses até o certificado expirar
        $monthsToExpire = ($m-$n);
        $this->pfxTimestamp = $dValid;
        $aRetorno = array('status'=>$flagOK,'error'=>$errorMsg,'meses'=>$monthsToExpire,'dias'=>$daysToExpire);
        return true;
    } //fim __validCerts
    
    /**
    * buildAndSendNfse
    * Método responsavel por identificar o sistema de emissao do cliente e direcionar para a funcao correspondente
    * 
    * @version 1.0
    * @package NfseFunctions
    * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
    * @name buildAndSendNfse
    * @param    array  $arrayloteRps array contendo os dados da nota.
    */
     protected function buildAndSendNfse($arrayloteRps) {
        //analizamos qual o sistema de emissao do cliente
        switch ($this->sistemaEmissao) {
            case self::BETHA_SISTEMAS:
		//O que define o ambiente de produção e testes, é a URL do web-service.
                if ($this->ambienteProducao === true) {
                    return $this->buildAndSendBethaNFSe($arrayloteRps);
                } else {
                    return $this->buildAndSendBethaNFSe($arrayloteRps, 'https://e-gov.betha.com.br/e-nota-contribuinte-test-ws/recepcionarLoteRps');
                }
                break;
	    //INCOMPLETO =/
            case self::ISSNET:
                if ($this->ambienteProducao === true) {
                    return $this->buildAndSendIssNetNFSe($arrayloteRps);
                } else {
                    return $this->buildAndSendIssNetNFSe($arrayloteRps, "http://www.issnetonline.com.br/webserviceabrasf/homologacao/servicos.asmx");
                }
                break;
            default:
                return array(false, 'O sistema ainda não está emitindo notas para o sistema escolhido');
                break;
        }
    }

     /**
    * buildAndSendBethaNFSe
    * Método responsavel pela construcao do xml da betha, com os campos exigidos pela empresa
    * Utiliza o método sendSoapMessage para montar o xml SOAP e enviar.
    * 
    * @version 1.0
    * @package NfseFunctions
    * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
    * @name buildAndSendBethaNFSe
    * @param    array  $arrayloteRps array contendo os dados da nota
    * @param    string  $urlWebService caminho do webservice
    * @return	string xml soap response
    */
    protected function buildAndSendBethaNFSe($arrayloteRps,$urlWebService='https://e-gov.betha.com.br/e-nota-contribuinte-ws/recepcionarLoteRps'){
        //cria o objeto DOM para o xml
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $ListaRps=$dom->createElement("ListaRps");
	//Essa variável, seu sistema deve oferecer, em uma tabela de configurações do emitente.
        $codigo_municipio_ibge='1234';
	//Esse array possui um formato que deve ser respeitado, apresentarei mais adiante.
        foreach ($arrayloteRps as $key => $loteRps){
            if(is_array($loteRps)){
                $loteRps['idRPS']='Rps'.$codigo_municipio_ibge.$this->getProxNumRpsBetha().$this->cnpjEmi.date('dmY');
                $Rps=$dom->createElement("Rps");
                //lista de RPS
                //$infRps = $dom->createElement("ListaRps");
                $infRps = $dom->createElement("InfRps");
                $infRps->setAttribute("Id", $loteRps['idRPS']);
                // Identificacao
                $IdentificacaoRps = $dom->createElement("IdentificacaoRps");
                //tsNumeroRps numerico 15 posicoes
                $loteRps['numNFSe']=$this->getProxNumRpsBetha();
                $Numero= $dom->createElement("Numero",$loteRps['numNFSe']);
                $Serie= $dom->createElement("Serie",$loteRps['numSerie']);
                $Tipo= $dom->createElement("Tipo",$loteRps['tipo']);
                $IdentificacaoRps->appendChild($Numero);
                $IdentificacaoRps->appendChild($Serie);
                $IdentificacaoRps->appendChild($Tipo);
                $infRps->appendChild($IdentificacaoRps);
                //data emissao
                $loteRps['dataEmissao']=date("Y-m-d")."T".date("H:i:s");
                $infRps->appendChild($dom->createElement("DataEmissao",$loteRps['dataEmissao']));
                $infRps->appendChild($dom->createElement("NaturezaOperacao",$loteRps['natOperacao']));
                $infRps->appendChild($dom->createElement("OptanteSimplesNacional",$loteRps['optanteSimplesNacional']));
                $infRps->appendChild($dom->createElement("IncentivadorCultural",$loteRps['incentivadorCultural']));
                $infRps->appendChild($dom->createElement("Status",$loteRps['status']));
                //cria as variaveis zeradas    
                $qtd=$v_total=$total_itens=$t_icms=$t_ipi=$total_pb=$total_pl=0;
                $temIcms=false;
                foreach ($loteRps['valores'] as $item) {
                    //var_dump($item);
                    $qtd++;
                    $Servico= $dom->createElement("Servico");
                    $Valores=$dom->createElement("Valores");
                    //campo obrigatorio
                    $ValorServicos=$dom->createElement("ValorServicos",number_format($item['valor'],2, '.', ''));
                    if(isset($item['valorDeducoes'])){
                        $ValorDeducoes=$dom->createElement("ValorDeducoes",number_format($item['valorDeducoes'],2, '.', ''));
                    }
                    if(isset($item['valorPis'])){
                        $ValorPis=$dom->createElement("ValorPis",number_format($item['valorPis'],2, '.', ''));
                    }
                    if(isset($item['valorCofins'])){
                        $ValorCofins=$dom->createElement("ValorCofins",number_format($item['valorCofins'],2, '.', ''));
                    }
                    if(isset($item['valorIr'])){
                        $ValorIr=$dom->createElement("ValorIr",number_format($item['valorIr'],2, '.', ''));
                    }
                    if(isset($item['valorCsll'])){
                        $ValorCsll=$dom->createElement("ValorCsll",number_format($item['valorCsll'],2, '.', ''));
                    }
                    //campo obrigatorio
                    $IssRetido=$dom->createElement("IssRetido",$item['issRetido']);
                    if(isset($item['valorIss'])){
                        $ValorIss=$dom->createElement("ValorIss",number_format($item['valorIss'],2, '.', ''));
                    }
                    if(isset($item['outrasRetencoes'])){
                        $OutrasRetencoes=$dom->createElement("OutrasRetencoes",number_format($item['outrasRetencoes'],2, '.', ''));
                    }
                    if(isset($item['valorIssRetido'])){
                        $ValorIssRetido=$dom->createElement("ValorIssRetido",number_format($item['valorIssRetido'],2, '.', ''));
                    }
                    //campo obrigatorio
                    $BaseCalculo=$dom->createElement("BaseCalculo",number_format($item['baseCalculo'],2, '.', ''));
                    if(isset($item['aliquota'])){
                        $Aliquota=$dom->createElement("Aliquota",number_format($item['aliquota'],2, '.', ''));
                    }
                    if(isset($item['valorLiquidoNfse'])){
                        $ValorLiquidoNfse=$dom->createElement("ValorLiquidoNfse",number_format($item['valorLiquidoNfse'],2, '.', ''));
                    }
                    if(isset($item['descontoIncondicionado'])){
                        $DescontoIncondicionado=$dom->createElement("DescontoIncondicionado",number_format($item['descontoIncondicionado'],2, '.', ''));
                    }
                    if(isset($item['descontoCondicionado'])){
                        $DescontoCondicionado=$dom->createElement("DescontoCondicionado",number_format($item['descontoCondicionado'],2, '.', ''));
                    }
                    //campo obrigatorio
                    $Valores->appendChild($ValorServicos);
                    if(isset($item['valorDeducoes'])){
                        $Valores->appendChild($ValorDeducoes);
                    }
                    if(isset($item['valorPis'])){
                        $Valores->appendChild($ValorPis);
                    }
                    if(isset($item['valorCofins'])){
                        $Valores->appendChild($ValorCofins);
                    }
                    if(isset($item['valorIr'])){
                        $Valores->appendChild($ValorIr);
                    }
                    if(isset($item['valorCsll'])){
                        $Valores->appendChild($ValorCsll);
                    }
                    //campo obrigatorio
                    $Valores->appendChild($IssRetido);
                    if(isset($item['valorIss'])){
                        $Valores->appendChild($ValorIss);
                    }
                    if(isset($item['outrasRetencoes'])){
                        $Valores->appendChild($OutrasRetencoes);
                    }
                    if(isset($item['valorIssRetido'])){
                        $Valores->appendChild($ValorIssRetido);
                    }
                    //campo obrigatorio
                    $Valores->appendChild($BaseCalculo);
                    if(isset($item['aliquota'])){
                        $Valores->appendChild($Aliquota);
                    }
                    if(isset($item['valorLiquidoNfse'])){
                        $Valores->appendChild($ValorLiquidoNfse);
                    }
                    if(isset($item['descontoIncondicionado'])){
                        $Valores->appendChild($DescontoIncondicionado);
                    }
                    if(isset($item['descontoCondicionado'])){
                        $Valores->appendChild($DescontoCondicionado);
                    }
                    // Detalhes do servico
                    //campo obrigatorio
                    $ItemListaServico=$dom->createElement("ItemListaServico",trim($item['itemListaServico']));
                    if(isset($item['codigoCnae'])){
                        $CodigoCnae=$dom->createElement("CodigoCnae",trim($item['codigoCnae']));
                    }
                    if(isset($item['codigoTributacao'])){
                        $CodigoTributacaoMunicipio=$dom->createElement("CodigoTributacaoMunicipio",trim($item['codigoTributacao']));
                    }
                    //campos obrigatorios
                    $Discriminacao=$dom->createElement("Discriminacao",$this->convertCaractersAscII($item['discriminacao'],'utf-8'));
                    //codigo do municipio no IBGE
                    $CodigoMunicipio=$dom->createElement("CodigoMunicipio",$codigo_municipio_ibge);
                    $Servico->appendChild($Valores);
                    $Servico->appendChild($ItemListaServico);
                    if(isset($item['codigoCnae'])){
                        $Servico->appendChild($CodigoCnae);
                    }
                    if(isset($item['codigoTributacao'])){
                        $Servico->appendChild($CodigoTributacaoMunicipio);
                    }
                    $Servico->appendChild($Discriminacao);
                    $Servico->appendChild($CodigoMunicipio);
                    $infRps->appendChild($Servico);
                } //fim foreach aItens
                // Prestador do Servico
                $Prestador=$dom->createElement("Prestador");
                $Cnpj=$dom->createElement("Cnpj",$this->cnpjEmi);//Cnpj do emitente/prestador
                $InscricaoMunicipal=$dom->createElement("InscricaoMunicipal",$this->inscricaoMunicipalEmi);//Inscricao municipal do prestador
                $Prestador->appendChild($Cnpj);
                $Prestador->appendChild($InscricaoMunicipal);
                // Tomador do Servico
                $Tomador=$dom->createElement("Tomador");
                $IdentificacaoTomador=$dom->createElement("IdentificacaoTomador");
                $CpfCnpj=$dom->createElement("CpfCnpj");
                //var_dump($loteRps['tomaCPF']);
                if ($loteRps['tomaCPF']){
                    $TomadorCpf=$dom->createElement("Cpf",$loteRps['tomaCPF']);
                }else{
                    $TomadorCnpj=$dom->createElement("Cnpj",$loteRps['tomaCNPJ']);
                }
                //tcEndereco
                
                $EEndereco=$dom->createElement("Endereco");
                if ($loteRps['tomaCPF'] != ''){
                    if(array_key_exists('tomaEndCep', $loteRps)){
                        if(array_key_exists('tomaCodigoMunicipio', $loteRps)){
                            $EEndereco->appendChild($dom->createElement("CodigoMunicipio",$loteRps['tomaCodigoMunicipio']));
                        }
                    }
                    $CpfCnpj->appendChild($TomadorCpf);
                } else {
                    if(array_key_exists('tomaEndCep', $loteRps)){
                        if(array_key_exists('tomaCodigoMunicipio', $loteRps)){
                           $EEndereco->appendChild($dom->createElement("CodigoMunicipio",$loteRps['tomaCodigoMunicipio']));
                        }else{
                           $EEndereco->appendChild($dom->createElement("CodigoMunicipio",$codigo_municipio_ibge));
                        }
                    }else{
                        $EEndereco->appendChild($dom->createElement("CodigoMunicipio",$codigo_municipio_ibge));
                    }
                    
                    $CpfCnpj->appendChild($TomadorCnpj);
                }
                $IdentificacaoTomador->appendChild($CpfCnpj);
                //nome do tomador do servico
                $RazaoSocial=$dom->createElement("RazaoSocial",$this->convertCaractersAscII($loteRps['tomaRazaoSocial'],'utf-8'));
                $Endereco=$dom->createElement("Endereco",$this->convertCaractersAscII($loteRps['tomaEndLogradouro'],'utf-8'));
                //$Numero=$dom->createElement("Numero",$loteRps['tomaEndNumero']);
                $Bairro=$dom->createElement("Bairro",$this->convertCaractersAscII($loteRps['tomaEndBairro'],'utf-8'));
                if(array_key_exists('tomaEndCep', $loteRps)){
                    $Uf=$dom->createElement("Uf",$loteRps['tomaEndUF']);
                    $Cep=$dom->createElement("Cep",$loteRps['tomaEndCep']);   
                }

                $EEndereco->appendChild($Endereco);
                //$EEndereco->appendChild($Numero);
                $EEndereco->appendChild($Bairro);
                //$EEndereco->appendChild($CodigoMunicipio);
                if(array_key_exists('tomaEndCep',$loteRps)){
                    $EEndereco->appendChild($Uf);
                    $EEndereco->appendChild($Cep);
                }
                $Tomador->appendChild($IdentificacaoTomador);
                $Tomador->appendChild($RazaoSocial);
                $Tomador->appendChild($EEndereco);
                if (isset($loteRps['tomaEmail'])){
                    $Contato=$dom->createElement("Contato");
                    $Email=$dom->createElement("Email",$loteRps['tomaEmail']);
                    $Contato->appendChild($Email);
                    $Tomador->appendChild($Contato);
                }
                $infRps->appendChild($Prestador);
                $infRps->appendChild($Tomador);
                //Servicos
                $Rps->appendChild($infRps);
                //string do RPS assinado
                $Rps=$this->signXML($dom->saveXml($Rps),'InfRps');
                //retiramos a tag <Rps> pois a criaremos novamente abaixo
                $Rps = str_replace(array('<Rps>','</Rps>'),"",$Rps);
                //recriamos o node Rps igual ao anterior, mas agora ja com assinatura
                $Rps=$dom->createElement("Rps",$Rps);
                //enfim o inserimos o node ao lote
                $ListaRps->appendChild($Rps);
                //funcao para gravar os dados da nfse no banco.
                $this->saveNfse($loteRps,$key);
            }
        }
	
        $LoteRps=$dom->createElement("LoteRps");
        $LoteRps->setAttribute("Id", $arrayloteRps['idLoteRps']);
        $NumeroLote=$dom->createElement("NumeroLote",$arrayloteRps['numeroLote']);
        $QuantidadeRps=$dom->createElement("QuantidadeRps",  $this->quantidadeRps);
        $Cnpj=$dom->createElement("Cnpj",$this->cnpjEmi);
        $InscricaoMunicipal=$dom->createElement("InscricaoMunicipal",$this->inscricaoMunicipalEmi);
        $EnviarLoteRpsEnvio=$dom->createElement("e:EnviarLoteRpsEnvio");
        $EnviarLoteRpsEnvio->setAttribute("xmlns:e", "http://www.betha.com.br/e-nota-contribuinte-ws");
        $LoteRps->appendChild($NumeroLote);
        $LoteRps->appendChild($Cnpj);
        $LoteRps->appendChild($InscricaoMunicipal);
        $LoteRps->appendChild($QuantidadeRps);
        $LoteRps->appendChild($ListaRps);
        $EnviarLoteRpsEnvio->appendChild($LoteRps);
        $dom->appendChild($EnviarLoteRpsEnvio);

        $xml= $dom->saveXML();
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$xml);
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml);
        $xml = str_replace('<?xml version="1.0"?>','',$xml);
        $xml = str_replace("\n","",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("> <","><",$xml);
        $this->nfsexml = $xml;
	//metodo para fazer a assinatura digital, somente da tag especifica, coisa da betha ¬¬
        $this->nfsexml=$this->signXML(html_entity_decode($this->nfsexml),"LoteRps");
        //enviamos a requisicao SOAP
        $soap_response=$this->sendSoapMessage($this->nfsexml,$urlWebService);
        //se houver erro na conexao com o servidor, deletar todos os rps gerados para esse lote
	//meramente ilustrativo, vc deverá fazer isso em sua base, do seu sistema.
        if($soap_response==false){
            $q = Doctrine_Query::create()
                ->delete('Nfse n')
                ->addWhere('n.id_emitente=?',  sfContext::getInstance()->getUser()->getAttribute('imobi'))
                ->addWhere('n.num_lote = ?', (int)$arrayloteRps['numeroLote']);
            $q->execute();
            return array(false,'Erro na conexão com o servidor!');
        }
/*-------------------TRATAMENTO ERROS RETURN DO SOAP--------------------------*/        

        //erro na comunicacao SOAP
        if(strstr($soap_response,'Fault')){
            $DomFaultXml=new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($soap_response);
            $error_msg='';
            foreach ($DomFaultXml->getElementsByTagName('faultstring') as $key => $value) {
                $error_msg.=$value->nodeValue.'<br/>';
            }
            $q = Doctrine_Query::create()
                ->update('Nfse n')
                ->set('n.status_nfse', '4')
                ->set('n.obs',$error_msg)
                ->addWhere('n.id_imobiliaria=?',  sfContext::getInstance()->getUser()->getAttribute('imobi'))
                ->addWhere('n.num_lote = ?', (int)$arrayloteRps['numeroLote']);
            $q->execute();
            //retornamos false indicando o erro e as mensagens de erro
            return array(false,$error_msg);
        }
        //erros de validacao do webservice
        if(strstr($soap_response,'Correcao')){
            $DomXml=new DOMDocument('1.0', 'utf-8');
            $DomXml->loadXML($soap_response);
            $error_msg='';
            foreach ($DomXml->getElementsByTagName('Correcao') as $key => $value) {
                $error_msg.=$value->nodeValue.'<br/>';
            }
            $q = Doctrine_Query::create()
                ->update('Nfse n')
                ->set('n.status_nfse', '4')
                ->set('n.obs',$error_msg)
                ->addWhere('n.id_imobiliaria=?',  sfContext::getInstance()->getUser()->getAttribute('imobi'))
                ->addWhere('n.num_lote = ?', (int)$arrayloteRps['numeroLote']);
            $q->execute();
            //retornamos false indicando o erro e as mensagens de erro
            return array(false,$error_msg);
        }
        //se retornar o protocolo, o envio funcionou corretamente
        if(strstr($soap_response,'Protocolo')){
            //retornamos false indicando o erro e as mensagens de erro
            //echo htmlentities($soap_response);exit();
            return array(true,$soap_response);
        }
    }
    
    /**
     * MÉTODO INCOMPLETO E NÃO TESTADO
     *
     * buildAndSendIssNetNFSe
     * Método responsavel pela construcao do xml da IssNet, com os campos exigidos pela empresa
     * Utiliza o método sendSoapMessage para montar o xml SOAP e enviar.
     * 
     * @version 1.0
     * @package NfseFunctions
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @name buildAndSendIssNetNFSe
     * @param    array  $arrayloteRps array contendo os dados da nota
     * @param    string  $urlWebService caminho do webservice
     * @return	string xml soap response
     */
    protected function buildAndSendIssNetNFSe(&$arrayloteRps, $urlWebService = 'http://www.issnetonline.com.br/webserviceabrasf/santamaria/servicos.asmx') {
        //cria o objeto DOM para o xml
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $ListaRps = $dom->createElement("tc:ListaRps");
        if ($this->ambienteProducao) {
	    //seu sistema deve oferecer essa informação, proveniente de alguma tabela de configurações do emitente
            $codigo_municipio_ibge = '999';
        } else {
            $codigo_municipio_ibge = "999";
        }
        foreach ($arrayloteRps as $key => $loteRps) {
            if (is_array($loteRps)) {
                //$loteRps['idRPS'] = 'Rps' . $codigo_municipio_ibge . $this->getProxNumRpsBetha() . $this->cnpjEmi . date('dmY');
                $Rps = $dom->createElement("tc:Rps");
                //lista de RPS  
                //$infRps = $dom->createElement("ListaRps");
                $infRps = $dom->createElement("tc:InfRps");
                //$infRps->setAttribute("Id", $loteRps['idRPS']);
                // Identificacao
                $IdentificacaoRps = $dom->createElement("tc:IdentificacaoRps");
                //tsNumeroRps numerico 15 posicoes
                $loteRps['numNFSe'] = $this->getProxNumRpsBetha();
                $Numero = $dom->createElement("tc:Numero", $loteRps['numNFSe']);
                $Serie = $dom->createElement("tc:Serie", $loteRps['numSerie']);
                $Tipo = $dom->createElement("tc:Tipo", $loteRps['tipo']);
                $IdentificacaoRps->appendChild($Numero);
                $IdentificacaoRps->appendChild($Serie);
                $IdentificacaoRps->appendChild($Tipo);
                $infRps->appendChild($IdentificacaoRps);
                //data emissao
                $loteRps['dataEmissao'] = date("Y-m-d") . "T" . date("H:i:s");
                $infRps->appendChild($dom->createElement("tc:DataEmissao", $loteRps['dataEmissao']));
                $infRps->appendChild($dom->createElement("tc:NaturezaOperacao", $loteRps['natOperacao']));
                $infRps->appendChild($dom->createElement("tc:OptanteSimplesNacional", $loteRps['optanteSimplesNacional']));
                $infRps->appendChild($dom->createElement("tc:IncentivadorCultural", $loteRps['incentivadorCultural']));
                $infRps->appendChild($dom->createElement("tc:Status", $loteRps['status']));
                //cria as variaveis zeradas    
                $qtd = $v_total = $total_itens = $t_icms = $t_ipi = $total_pb = $total_pl = 0;
                $temIcms = false;
                foreach ($loteRps['valores'] as $item) {
                    //var_dump($item);
                    $qtd++;
                    $Servico = $dom->createElement("tc:Servico");
                    $Valores = $dom->createElement("tc:Valores");
                    //campo obrigatorio
                    $ValorServicos = $dom->createElement("tc:ValorServicos", number_format($item['valor'], 2, '.', ''));
                    if (isset($item['valorDeducoes'])) {
                        $ValorDeducoes = $dom->createElement("tc:ValorDeducoes", number_format($item['valorDeducoes'], 2, '.', ''));
                    }
                    if (isset($item['valorPis'])) {
                        $ValorPis = $dom->createElement("tc:ValorPis", number_format($item['valorPis'], 2, '.', ''));
                    }
                    if (isset($item['valorCofins'])) {
                        $ValorCofins = $dom->createElement("tc:ValorCofins", number_format($item['valorCofins'], 2, '.', ''));
                    }
                    if (isset($item['valorIr'])) {
                        $ValorIr = $dom->createElement("tc:ValorIr", number_format($item['valorIr'], 2, '.', ''));
                    }
                    if (isset($item['valorCsll'])) {
                        $ValorCsll = $dom->createElement("tc:ValorCsll", number_format($item['valorCsll'], 2, '.', ''));
                    }
                    //campo obrigatorio
                    $IssRetido = $dom->createElement("tc:IssRetido", $item['issRetido']);
                    if (isset($item['valorIss'])) {
                        $ValorIss = $dom->createElement("tc:ValorIss", number_format($item['valorIss'], 2, '.', ''));
                    }
                    if (isset($item['outrasRetencoes'])) {
                        $OutrasRetencoes = $dom->createElement("tc:OutrasRetencoes", number_format($item['outrasRetencoes'], 2, '.', ''));
                    }
                    if (isset($item['valorIssRetido'])) {
                        $ValorIssRetido = $dom->createElement("tc:ValorIssRetido", number_format($item['valorIssRetido'], 2, '.', ''));
                    }
                    //campo obrigatorio
                    $BaseCalculo = $dom->createElement("tc:BaseCalculo", number_format($item['baseCalculo'], 2, '.', ''));
                    if (isset($item['aliquota'])) {
                        $Aliquota = $dom->createElement("tc:Aliquota", number_format($item['aliquota'], 2, '.', ''));
                    }
                    if (isset($item['valorLiquidoNfse'])) {
                        $ValorLiquidoNfse = $dom->createElement("tc:ValorLiquidoNfse", number_format($item['valorLiquidoNfse'], 2, '.', ''));
                    }
                    if (isset($item['descontoIncondicionado'])) {
                        $DescontoIncondicionado = $dom->createElement("tc:DescontoIncondicionado", number_format($item['descontoIncondicionado'], 2, '.', ''));
                    }
                    if (isset($item['descontoCondicionado'])) {
                        $DescontoCondicionado = $dom->createElement("tc:DescontoCondicionado", number_format($item['descontoCondicionado'], 2, '.', ''));
                    }
                    //campo obrigatorio
                    $Valores->appendChild($ValorServicos);
                    if (isset($item['valorDeducoes'])) {
                        $Valores->appendChild($ValorDeducoes);
                    }
                    if (isset($item['valorPis'])) {
                        $Valores->appendChild($ValorPis);
                    }
                    if (isset($item['valorCofins'])) {
                        $Valores->appendChild($ValorCofins);
                    }
                    if (isset($item['valorIr'])) {
                        $Valores->appendChild($ValorIr);
                    }
                    if (isset($item['valorCsll'])) {
                        $Valores->appendChild($ValorCsll);
                    }
                    //campo obrigatorio
                    $Valores->appendChild($IssRetido);
                    if (isset($item['valorIss'])) {
                        $Valores->appendChild($ValorIss);
                    }
                    if (isset($item['outrasRetencoes'])) {
                        $Valores->appendChild($OutrasRetencoes);
                    }
                    if (isset($item['valorIssRetido'])) {
                        $Valores->appendChild($ValorIssRetido);
                    }
                    //campo obrigatorio
                    $Valores->appendChild($BaseCalculo);
                    if (isset($item['aliquota'])) {
                        $Valores->appendChild($Aliquota);
                    }
                    if (isset($item['valorLiquidoNfse'])) {
                        $Valores->appendChild($ValorLiquidoNfse);
                    }
                    if (isset($item['descontoIncondicionado'])) {
                        $Valores->appendChild($DescontoIncondicionado);
                    }
                    if (isset($item['descontoCondicionado'])) {
                        $Valores->appendChild($DescontoCondicionado);
                    }
                    // Detalhes do servico
                    //campo obrigatorio
                    $ItemListaServico = $dom->createElement("tc:ItemListaServico", trim($item['itemListaServico']));
                    if (isset($item['codigoCnae'])) {
                        $CodigoCnae = $dom->createElement("tc:CodigoCnae", trim($item['codigoCnae']));
                    }
                    if (isset($item['codigoTributacao'])) {
                        $CodigoTributacaoMunicipio = $dom->createElement("tc:CodigoTributacaoMunicipio", trim($item['codigoTributacao']));
                    }
                    //campos obrigatorios
                    $Discriminacao = $dom->createElement("tc:Discriminacao", $this->convertCaractersAscII($item['discriminacao'], 'utf-8'));
                    //codigo do municipio no IBGE
                    $CodigoMunicipio = $dom->createElement("tc:CodigoMunicipio", $codigo_municipio_ibge);
                    $Servico->appendChild($Valores);
                    $Servico->appendChild($ItemListaServico);
                    if (isset($item['codigoCnae'])) {
                        $Servico->appendChild($CodigoCnae);
                    }
                    if (isset($item['codigoTributacao'])) {
                        $Servico->appendChild($CodigoTributacaoMunicipio);
                    }
                    $Servico->appendChild($Discriminacao);
                    $Servico->appendChild($CodigoMunicipio);
                    $infRps->appendChild($Servico);
                } //fim foreach aItens
                // Prestador do Servico
                $Prestador = $dom->createElement("tc:Prestador");
                $CpfCnpj = $dom->createElement("tc:CpfCnpj");
                $Cnpj = $dom->createElement("tc:Cnpj", $this->cnpjEmi); //Cnpj do emitente/prestador (Imobiliaria)
                $CpfCnpj->appendChild($Cnpj);
                $InscricaoMunicipal = $dom->createElement("tc:InscricaoMunicipal", $this->inscricaoMunicipalEmi); //Inscricao municipal do prestador (Imobiliaria)
                $Prestador->appendChild($CpfCnpj);
                $Prestador->appendChild($InscricaoMunicipal);
                // Tomador do Servico
                $Tomador = $dom->createElement("tc:Tomador");
                $IdentificacaoTomador = $dom->createElement("tc:IdentificacaoTomador");
                $CpfCnpj = $dom->createElement("tc:CpfCnpj");
                //var_dump($loteRps['tomaCPF']);
                if ($loteRps['tomaCPF']) {
                    $TomadorCpf = $dom->createElement("tc:Cpf", $loteRps['tomaCPF']);
                } else {
                    $TomadorCnpj = $dom->createElement("tc:Cnpj", $loteRps['tomaCNPJ']);
                }
                //tcEndereco

                $EEndereco = $dom->createElement("tc:Endereco");
                if ($loteRps['tomaCPF'] != '') {
                    if (array_key_exists('tomaEndCep', $loteRps)) {
                        if (array_key_exists('tomaCodigoMunicipio', $loteRps)) {
                            $EEndereco->appendChild($dom->createElement("tc:CodigoMunicipio", $loteRps['tomaCodigoMunicipio']));
                        }
                    }
                    $CpfCnpj->appendChild($TomadorCpf);
                } else {
                    if (array_key_exists('tomaEndCep', $loteRps)) {
                        if (array_key_exists('tomaCodigoMunicipio', $loteRps)) {
                            $EEndereco->appendChild($dom->createElement("tc:CodigoMunicipio", $loteRps['tomaCodigoMunicipio']));
                        } else {
                            $EEndereco->appendChild($dom->createElement("tc:CodigoMunicipio", $codigo_municipio_ibge));
                        }
                    } else {
                        $EEndereco->appendChild($dom->createElement("tc:CodigoMunicipio", $codigo_municipio_ibge));
                    }

                    $CpfCnpj->appendChild($TomadorCnpj);
                }
                $IdentificacaoTomador->appendChild($CpfCnpj);
                //nome do tomador do servico
                $RazaoSocial = $dom->createElement("tc:RazaoSocial", $this->convertCaractersAscII($loteRps['tomaRazaoSocial'], 'utf-8'));
                $Endereco = $dom->createElement("tc:Endereco", $this->convertCaractersAscII($loteRps['tomaEndLogradouro'], 'utf-8'));
                //$Numero=$dom->createElement("Numero",$loteRps['tomaEndNumero']);
                $Bairro = $dom->createElement("tc:Bairro", $this->convertCaractersAscII($loteRps['tomaEndBairro'], 'utf-8'));
                if (array_key_exists('tomaEndCep', $loteRps)) {
                    $Uf = $dom->createElement("tc:Uf", $loteRps['tomaEndUF']);
                    $Cep = $dom->createElement("tc:Cep", $loteRps['tomaEndCep']);
                }
                $EEndereco->appendChild($Endereco);
                //$EEndereco->appendChild($Numero);
                $EEndereco->appendChild($Bairro);
                //$EEndereco->appendChild($CodigoMunicipio);
                if (array_key_exists('tomaEndCep', $loteRps)) {
                    $EEndereco->appendChild($Uf);
                    $EEndereco->appendChild($Cep);
                }
                $Tomador->appendChild($IdentificacaoTomador);
                $Tomador->appendChild($RazaoSocial);
                $Tomador->appendChild($EEndereco);
                if (isset($loteRps['tomaEmail'])) {
                    $Contato = $dom->createElement("tc:Contato");
                    $Email = $dom->createElement("tc:Email", $loteRps['tomaEmail']);
                    $Contato->appendChild($Email);
                    $Tomador->appendChild($Contato);
                }
                $infRps->appendChild($Prestador);
                $infRps->appendChild($Tomador);
                //Servicos
                $Rps->appendChild($infRps);
                //string do RPS assinado
                //$Rps=$this->signXML($dom->saveXml($Rps),"InfRps");
                //retiramos a tag <Rps> pois a criaremos novamente abaixo
                //$Rps = str_replace(array("<Rps>","</Rps>"),"",$Rps);
                //recriamos o node Rps igual ao anterior, mas agora ja com assinatura
                //$Rps=$dom->createElement("Rps",$Rps);
                //enfim o inserimos o node ao lote
                $ListaRps->appendChild($Rps);
                //funcao para gravar os dados da nfse no banco.
                //$this->saveNfse($loteRps, $key);
            }
        }
        $LoteRps = $dom->createElement("LoteRps");
        $LoteRps->setAttribute("Id", $arrayloteRps['idLoteRps']);
        $NumeroLote = $dom->createElement("tc:NumeroLote", $arrayloteRps['numeroLote']);
        $QuantidadeRps = $dom->createElement("tc:QuantidadeRps", $this->quantidadeRps);
        $Cnpj = $dom->createElement("tc:Cnpj", $this->cnpjEmi);
        $CpfCnpj = $dom->createElement("tc:CpfCnpj");
        $CpfCnpj->appendChild($Cnpj);
        $InscricaoMunicipal = $dom->createElement("tc:InscricaoMunicipal", $this->inscricaoMunicipalEmi);
        $EnviarLoteRpsEnvio = $dom->createElement("EnviarLoteRpsEnvio");
        $EnviarLoteRpsEnvio->setAttribute("xmlns", "http://www.issnetonline.com.br/webserviceabrasf/vsd/servico_enviar_lote_rps_envio.xsd");
        $EnviarLoteRpsEnvio->setAttribute("xmlns:tc", "http://www.issnetonline.com.br/webserviceabrasf/vsd/tipos_complexos.xsd");
        $LoteRps->appendChild($NumeroLote);
        $LoteRps->appendChild($CpfCnpj);
        $LoteRps->appendChild($InscricaoMunicipal);
        $LoteRps->appendChild($QuantidadeRps);
        $LoteRps->appendChild($ListaRps);
        $EnviarLoteRpsEnvio->appendChild($LoteRps);
        $dom->appendChild($EnviarLoteRpsEnvio);

        $xml = $dom->saveXML();
        /*$xml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $xml);
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml);
        $xml = str_replace('<?xml version="1.0"?>', '', $xml);
        $xml = str_replace("\n", "", $xml);
        $xml = str_replace("  ", " ", $xml);*/
        $xml = str_replace("> <", "><", $xml);
        $this->nfsexml = $xml;

        $this->nfsexml = $this->signXML(html_entity_decode($this->nfsexml), "LoteRps");
        //enviamos a requisicao SOAP

        //$this->validarDomXML($this->nfsexml, "/home/paulo/v_1125/httpdocs/arquivos_teste/imobiliaria_26/servico_enviar_lote_rps_envio.xsd");
        //$soap_response = $this->sendSoapMessage($this->nfsexml, $urlWebService);
        //echo htmlentities($this->nfsexml);exit();
        $arquivo = fopen('/home/paulo/v_1142/httpdocs/arquivos_imobiliaria/xyz_smo.xml', 'w');
        fwrite($arquivo, $this->nfsexml);
        fclose($arquivo); //exit();
        $client = new SoapClient($urlWebService.'?WSDL');
        $function = 'RecepcionarLoteRps';

        $arguments = array('RecepcionarLoteRps' => array('xml' => $this->nfsexml));
        $options = array('location' => $urlWebService);

        $result = $client->__soapCall($function, $arguments, $options);

        echo 'Response: ';
        print_r($result);
        exit();
        //se houver erro na conexao com o servidor, deletar todos os rps gerados para esse lote
        if ($soap_response == false) {
            if ($progressbar) {
                SisflexFunctions::getObject()->setDadosProgressBar($progressbar['arquivo_progress_bar'], 93, 'Erro na conexão com o servidor! Restaurando dados...');
            }
            $q = Doctrine_Query::create()
                    ->delete('Nfse n')
                    ->addWhere('n.id_imobiliaria=?', sfContext::getInstance()->getUser()->getAttribute('imobi'))
                    ->addWhere('n.num_lote = ?', (int) $arrayloteRps['numeroLote']);
            $q->execute();
            return array(false, 'Erro na conexão com o servidor!');
        }
        /* -------------------TRATAMENTO ERROS RETURN DO SOAP-------------------------- */
        $arquivo = fopen('/home/paulo/v_1125/httpdocs/arquivos_teste/imobiliaria/retorno.xml', 'w');
        fwrite($arquivo, $soap_response);
        fclose($arquivo); //exit();
        //erro na comunicacao SOAP
        if (strstr($soap_response, 'Fault')) {
            $DomFaultXml = new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($soap_response);
            $error_msg = '';
            foreach ($DomFaultXml->getElementsByTagName('faultstring') as $key => $value) {
                $error_msg.=$value->nodeValue . '<br/>';
            }
            $q = Doctrine_Query::create()
                    ->update('Nfse n')
                    ->set('n.status_nfse', '4')
                    ->set('n.obs', $error_msg)
                    ->addWhere('n.id_imobiliaria=?', sfContext::getInstance()->getUser()->getAttribute('imobi'))
                    ->addWhere('n.num_lote = ?', (int) $arrayloteRps['numeroLote']);
            $q->execute();
            //retornamos false indicando o erro e as mensagens de erro
            return array(false, $error_msg);
        }
        //erros de validacao do webservice
        if (strstr($soap_response, 'Correcao')) {
            $DomXml = new DOMDocument('1.0', 'utf-8');
            $DomXml->loadXML($soap_response);
            $error_msg = '';
            foreach ($DomXml->getElementsByTagName('Correcao') as $key => $value) {
                $error_msg.=$value->nodeValue . '<br/>';
            }
            $q = Doctrine_Query::create()
                    ->update('Nfse n')
                    ->set('n.status_nfse', '4')
                    ->set('n.obs', $error_msg)
                    ->addWhere('n.id_imobiliaria=?', sfContext::getInstance()->getUser()->getAttribute('imobi'))
                    ->addWhere('n.num_lote = ?', (int) $arrayloteRps['numeroLote']);
            $q->execute();
            //retornamos false indicando o erro e as mensagens de erro
            return array(false, $error_msg);
        }
        //se retornar o protocolo, o envio funcionou corretamente
        if (strstr($soap_response, 'Protocolo')) {
            //retornamos false indicando o erro e as mensagens de erro
            //echo htmlentities($soap_response);exit();
            return array(true, $soap_response);
        }
    }
    
    /**
    * consultaSituacaoLote
    * Método responsavel por identificar o sistema de emissao do cliente e direcionar para a funcao correspondente
    * 
    * @version 1.0
    * @package NfseFunctions
    * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
    * @name consultaSituacaoLote
    * @param    array  $arrayloteRps array contendo os dados da nota
    */
     protected function consultaSituacaoLote($numero_protocolo,$numero_lote){
         //analizamos qual o sistema de emissao do cliente
         switch ($this->sistemaEmissao) {
             case self::BETHA_SISTEMAS:
                 //controle de ambientes (teste e producao)
                 if($this->ambienteProducao === true){
                     return $this->consultaSituacaoLoteBethaNFSe($numero_protocolo,$numero_lote);
                 }else{
                     return $this->consultaSituacaoLoteBethaNFSe($numero_protocolo,$numero_lote,'http://e-gov.betha.com.br/e-nota-contribuinte-test-ws/consultarSituacaoLoteRps');
                 }
                 break;
             default:
                 return array(false,'O sistema ainda não está emitindo notas para o sistema escolhido');
                 break;
         }
     }
    
    /**
     * consultaSituacaoLoteBethaNFSe
     * Consulta se o Lote Rps ja foi processado pela Betha e prefeitura
     * 
     * @name consultaSituacaoLoteBethaNFSe
     * @version 1.0
     * @package NfseFunctions
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param int $numero_protocolo numero do protocolo do lote a ser consultado
     * @param int $numero_lote numero do lote a ser consultado
     * @param string $urlWebService url do webservice
     */
    protected function consultaSituacaoLoteBethaNFSe($numero_protocolo,$numero_lote,$urlWebService='http://e-gov.betha.com.br/e-nota-contribuinte-ws/consultarSituacaoLoteRps'){
        $dom=new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput=true;
        $dom->preserveWhiteSpace=false;
        $ConsultarSituacao=$dom->createElement("e:ConsultarSituacaoLoteRpsEnvio");
        $ConsultarSituacao->setAttribute("xmlns:e", "http://www.betha.com.br/e-nota-contribuinte-ws");
        //prestador
        $Prestador=$dom->createElement("Prestador");
        $Cnpj=$dom->createElement("Cnpj",$this->cnpjEmi);//Cnpj do emitente/prestador (Imobiliaria)
        $InscricaoMunicipal=$dom->createElement("InscricaoMunicipal",$this->inscricaoMunicipalEmi);//Inscricao municipal do prestador
        $Prestador->appendChild($Cnpj);
        $Prestador->appendChild($InscricaoMunicipal);
        //protocolo
        $Protocolo=$dom->createElement('Protocolo',$numero_protocolo);
        $ConsultarSituacao->appendChild($Prestador);
        $ConsultarSituacao->appendChild($Protocolo);
        $dom->appendChild($ConsultarSituacao);

        //sei q isso é ridiculo, mas só assim funcionou =/
        $xml= $dom->saveXML();
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0" encoding="UTF-8" standalone="no"?>',$xml);
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$xml);
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml);
        $xml = str_replace("\n","",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("> <","><",$xml);
        $soap_response=$this->sendSoapMessage($xml,$urlWebService);
        if($soap_response==false){
            return array(false,'Erro na conexão com o servidor!');
        }
        //tratar os erros
        $codigo='';
        $msg='';
        $correcao='';
        //tratamento de erros
        if(strstr($soap_response,'Codigo')){
            $DomFaultXml=new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($soap_response);
            foreach ($DomFaultXml->getElementsByTagName('Codigo') as $key => $value) {
                $codigo=$value->nodeValue;
            }
        }
        if(strstr($soap_response,'Mensagem')){
            $DomFaultXml=new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($soap_response);
            $msg='';
            foreach ($DomFaultXml->getElementsByTagName('Mensagem') as $key => $value) {
                $msg=$value->nodeValue;
            }
        }
        if(strstr($soap_response,'Correcao')){
            $DomFaultXml=new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($soap_response);
            $correcao='';
            foreach ($DomFaultXml->getElementsByTagName('Correcao') as $key => $value) {
                $correcao=$value->nodeValue;
            }
        }
        //echo htmlentities($soap_response);
        if($codigo=='0000'){
            $error_msg=$msg.'<br/>';
            $error_msg='Protocolo não encontrado!';
            return array(false,$error_msg);
        }
	//codigos de erro da Betha
        if($codigo=='BTH03' || $codigo=='E86' || $codigo=='E10' || $codigo=='E59' || $codigo=='BTH12' || $codigo=='E111'){
            $error_msg=$msg.'<br/>';
            $error_msg=$correcao;
            //ilustrativo, vc devera adaptar para seu sistema
            $q = Doctrine_Query::create()
                ->update('Nfse AS n')
                ->set('n.status_nfse','?', '4')
                ->set('n.obs', '?', $error_msg)
                ->Where('n.id_emitente=?',  23)
                ->andWhere('n.num_lote = ?', $numero_lote)
                ->andWhere('n.protocolo = ?', $numero_protocolo);
            $q->execute();
            return array(false,$error_msg);
        }
        //Lote ainda nao processado
        if($codigo=='E92'){
            $error_msg=$msg.'<br/>';
            $error_msg=$correcao;
            return array(true,$error_msg);
        }
        //Caso haja sucesso
        $lote='';
        $situacao='';
        if(strstr($soap_response,'NumeroLote')){
            $DomFaultXml=new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($soap_response);
            foreach ($DomFaultXml->getElementsByTagName('NumeroLote') as $value) {
                $lote=$value->nodeValue;
            }
        }
        if(strstr($soap_response,'Situacao')){
            $DomFaultXml=new DOMDocument('1.0', 'utf-8');
            $DomFaultXml->loadXML($soap_response);
            foreach ($DomFaultXml->getElementsByTagName('Situacao') as $value) {
                $situacao=$value->nodeValue;
            }
        }
        switch ($situacao) {
            case 1://Nao Recebido
                return array(false,'O Lote '.$numero_lote.' não foi recebido.<br/>');
                break;
            case 2://Nao Processado
                return array(false,'O Lote '.$numero_lote.' foi recebido e esta na fila para o processamento.<br/>');
                break;
            case 3://Processado com Erro
                return array(false,'O Lote '.$numero_lote.' foi recebido e processado com erro, o lote deverá ser reenviado.<br/>');
                break;
            case 4://Processado com Sucesso
                //aqui pegamos os dados das Nfse no banco (sim, vc terá de desenvolver isso, sinto muito se achou q ia encontrar algo completo =/)
                $error=false;
		//meramente ilustrativo
                $array_rps=Doctrine::getTable('Nfse')->findByProtocoloAndNumLoteAndIdEmitente($numero_protocolo,$numero_lote,  sfContext::getInstance()->getUser()->getAttribute('idEmitente'));
                $totalRps=count($array_rps);
                $cont_rps=0;
                foreach ($array_rps as $key => $rps) {
                    $cont_rps++;
                    $percent = intval(($cont_rps / $totalRps) * 100) . "%";                    
                    //tratamento de ambientes (teste e producao)
                    if($this->ambienteProducao===true){
                        //passamos um obj contendo um registro da table nfse, apenas um rps por vez para atualizar ¬¬ coisas da betha
                        $response=$this->consultarNFSeByRpsBetha($rps,true);
                    }else{
                        //passamos um obj contendo um registro da table nfse
                        $response=$this->consultarNFSeByRpsBetha($rps,true,'http://e-gov.betha.com.br/e-nota-contribuinte-test-ws/consultarNfsePorRps');
                    }
                    //essa variavel analiza se ocorreram erros durante a execucao, para informar se o lote 
                    //foi processado com sucesso ou com alguns erros
                    if(!$response[0]){
                        $error=true;
                    }
                }
                if($error){
                    $msg="O Lote $numero_lote foi recebido e processado com alguns erros!";
                }else{
                    $msg="O Lote $numero_lote foi recebido e processado com Sucesso!";
                }
                return array(true,$msg);
                break;
            default:
                return array(false,'A Consulta não foi bem sucedida!<br>Código: '.$codigo.
                    '<br>Menssagem: '.$msg.'<br>Correção: '.$correcao);
                break;
        }
        //var_dump($xml);
    }
    
    /**
     * consultarNFSeByRpsBetha
     * Consulta e retorna os dados de uma Nota a partir de um Rps
     * 
     * @name consultarNFSeByRpsBetha
     * @version 1.0
     * @package NfseFunctions
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param obj $rps objeto contendo um registro da tabela nfse
     * @param string $urlWebService url do webservice
     */
    protected function consultarNFSeByRpsBetha($rps,$urlWebService='e-gov.betha.com.br/e-nota-contribuinte-ws/consultarNfsePorRps'){
        $num_rps=$rps->getNumRps();
        $serie=$rps->getSerie();
        $tipo=$rps->getTipo();
        //exit($numero_protocolo);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput=true;
        $dom->preserveWhiteSpace=false;
        $ConsultarNfse=$dom->createElement("e:ConsultarNfsePorRpsEnvio");
        $ConsultarNfse->setAttribute("xmlns:e", "http://www.betha.com.br/e-nota-contribuinte-ws");
        //identificacao Rps
        $IdentificacaoRps=$dom->createElement('IdentificacaoRps');
        $Numero=$dom->createElement('Numero',$num_rps);
        $Serie=$dom->createElement('Serie',$serie);
        $Tipo=$dom->createElement('Tipo',$tipo);
        $IdentificacaoRps->appendChild($Numero);
        $IdentificacaoRps->appendChild($Serie);
        $IdentificacaoRps->appendChild($Tipo);
        //prestador
        $Prestador=$dom->createElement("Prestador");
        $Cnpj=$dom->createElement("Cnpj",$this->cnpjEmi);//Cnpj do emitente/prestador (Imobiliaria)
        $InscricaoMunicipal=$dom->createElement("InscricaoMunicipal",$this->inscricaoMunicipalEmi);//Inscricao municipal do prestador 
        $Prestador->appendChild($Cnpj);
        $Prestador->appendChild($InscricaoMunicipal);
        //aplicando no node raiz
        $ConsultarNfse->appendChild($IdentificacaoRps);
        $ConsultarNfse->appendChild($Prestador);
        $dom->appendChild($ConsultarNfse);
        //mesma historia, sei q isso é horrível, mas só assim funcionou =/ (não parei pra ver isso com calma tb ^^)
        $xml= $dom->saveXML();
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0" encoding="UTF-8" standalone="no"?>',$xml);
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$xml);
        $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml);
        $xml = str_replace("\n","",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("  "," ",$xml);
        $xml = str_replace("> <","><",$xml);

        $soap_response=$this->sendSoapMessage($xml,$urlWebService);
        if($soap_response==false){
            return array(false,'Erro na conexão com o servidor!');
        }
        //echo htmlentities($soap_response);
        //tratar os erros
        $cod_verificacao='';
        $num_nota='';
        $link_danfe='';
        $valor_iss='';
        $nota_cancelada='false';
        $DomResponseXml=new DOMDocument('1.0', 'utf-8');
        $DomResponseXml->loadXML($soap_response);
        //pegamos informacoes relevantes do retorno
	//sim, isso pode ser melhorado, me dêem um desconto, fazem alguns anos desde que fiz isso ¬¬
        if(strstr($soap_response,'CodigoVerificacao')){
            foreach ($DomResponseXml->getElementsByTagName('CodigoVerificacao') as $key => $value) {
                $cod_verificacao=$value->nodeValue;
            }
        }
        if(strstr($soap_response,'InfNfse')){
            foreach ($DomResponseXml->getElementsByTagName('InfNfse') as $key => $value) {
                $num_nota=$value->firstChild->nodeValue;
            }
        }
        if(strstr($soap_response,'OutrasInformacoes')){
            foreach ($DomResponseXml->getElementsByTagName('OutrasInformacoes') as $key => $value) {
                $link_danfe=$value->nodeValue;
            }
        }
        if(strstr($soap_response,'ValorIss')){
            foreach ($DomResponseXml->getElementsByTagName('ValorIss') as $key => $value) {
                $valor_iss=$value->nodeValue;
            }
        }
        if(strstr($soap_response,'InfConfirmacaoCancelamento')){
            foreach ($DomResponseXml->getElementsByTagName('Sucesso') as $key => $value) {
                $nota_cancelada=$value->nodeValue;
            }
        }
        //verificamos se realmente retornou alguma nota, e se a mesma nao esta cancelada.
        if($cod_verificacao && $num_nota && $nota_cancelada=='false'){
	    //aqui atualizamos a tabela de nfse, novamente, meramente ilustrativo
            $nfse=Doctrine::getTable('Nfse')->findOneById($rps->getId());
            $nfse->setNumNfse($num_nota);
            $nfse->setCodigoVerificacao($cod_verificacao);
            $nfse->setLinkNfse($link_danfe);
            $nfse->setValorIss($valor_iss);
            //status 2 emitido
            $nfse->setStatusNfse(2);
            $nfse->save();
            return array(true,'Nota atualizada!');
        }else{
            return array(false,'Nota Cancelada ou invalida;');
        }
    }
    
    /**
     * Esse método deverá ser totalmente adaptado para seu sistema.
     * saveNfse
     * Salvar os dados da nota no banco
     * 
     * @name saveNfse
     * @version 1.0
     * @package NfseFunctions
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param array $loteRps array com os dados da nota
     * @param int $id_pessoa id do tomador
     */
    private function saveNfse($loteRps,$id_pessoa){
        //var_dump($loteRps['numeroLote']);exit();
        $nfse=new Nfse();
        $nfse->setIdPessoa($id_pessoa);
        $nfse->setDataEmissao(str_replace('T', ' ', $loteRps['dataEmissao']));
        $nfse->setStatusNfse(1);//enviado
        $nfse->setNumLote($loteRps['numeroLote']);
        $nfse->setIdEmitente(sfContext::getInstance()->getUser()->getAttribute('emitente'));
        $nfse->setNumRps($loteRps['numNFSe']);
        $nfse->setSerie($loteRps['numSerie']);
        $nfse->setTipo($loteRps['tipo']);
        $nfse->setCpfCnpjTomador($loteRps['tomaCPF'].$loteRps['tomaCNPJ']);
        $nfse->setValorHonorarios($loteRps['valores'][0]['valor']);
        $nfse->save();
    }
    
    /**
    * cancelarNfse
    * Método responsavel por identificar o sistema de emissao do cliente e direcionar para a funcao correspondente
    * 
    * @version 1.0
    * @package NfseFunctions
    * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
    * @name cancelarNfse
    * @param    array  $arrayloteRps array contendo os dados da nota (por referencia)
    */
     protected function cancelarNfse($nfse,$codigoCancelamento=1){
         //analizamos qual o sistema de emissao do cliente
         switch ($this->sistemaEmissao) {
             case self::BETHA_SISTEMAS:
                 if($this->ambienteProducao === true){
                     return $this->cancelarNFSeBetha($nfse,$codigoCancelamento=1);
                 }else{
                     return $this->cancelarNFSeBetha($nfse,$codigoCancelamento=1,'http://e-gov.betha.com.br/e-nota-contribuinte-test-ws/cancelarNfse');
                 }
                 break;
             default:
                 return array(false,'O sistema ainda não está emitindo notas para o sistema escolhido');
                 break;
         }
     }
    
     /**
     * Esse método deverá ser adaptado pelo seu sistema, para que o mesmo possa atualizar os registros das nfse no banco
     *
     * cancelarNFSeBetha
     * cancela a Nfse junto a Betha e a prefeitura e deleta o registro do nosso sistema
     * 
     * @name cancelarNFSeBetha
     * @version 1.0
     * @package NfseFunctions
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param obj $nfse objeto contendo um registro da tabela nfse
     * @param int $codigoCancelamento optional Inteiro contendo codigo do cancelamento (tabela de codigos encontrase comentado dentro da funcao)
     * @param string $urlWebService optional url do webservice
     */
    protected function cancelarNFSeBetha($nfse,$codigoCancelamento=1,$urlWebService='http://e-gov.betha.com.br/e-nota-contribuinte-ws/cancelarNfse'){
        /* Codigo Cancelamento:
        1 – Erro na emissão
        2 – Serviço não prestado
        3 – Erro de assinatura
        4 – Duplicidade da nota
        5 – Erro de processamento 
        ->Importante: Os códigos 3 (Erro de assinatura) e 5 (Erro de processamento) são de uso restrito da Administração Tributária Municipal
         */
        $numNfse=$nfse->getNumNfse();
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput=true;
        $dom->preserveWhiteSpace=false;
        $pedido=$dom->createElement("Pedido");
        //identificacao Nfse
        $InfPedidoCancelamento=$dom->createElement('InfPedidoCancelamento');
        $IdentificacaoNfse=$dom->createElement('IdentificacaoNfse');
        $Numero=$dom->createElement('Numero',$numNfse);
        $Cnpj=$dom->createElement("Cnpj",$this->cnpjEmi);//Cnpj do emitente/prestador
        $CodigoMunicipio=$dom->createElement("CodigoMunicipio",'123');//Codigo do municipio do emitente, seu sistema deve oferecer esse dado
        $IdentificacaoNfse->appendChild($Numero);
        $IdentificacaoNfse->appendChild($Cnpj);
        $IdentificacaoNfse->appendChild($CodigoMunicipio);
        //prestador
        $CodigoCancelamento=$dom->createElement("CodigoCancelamento",$codigoCancelamento);
        $InfPedidoCancelamento->appendChild($IdentificacaoNfse);
        $InfPedidoCancelamento->appendChild($CodigoCancelamento);
        $pedido->appendChild($InfPedidoCancelamento);
        //echo htmlentities($dom->saveXML($pedido));
        $pedido=$this->signXML($dom->saveXml($pedido),'InfPedidoCancelamento');
        //echo htmlentities($pedido);
        $pedido = str_replace(array('<Pedido>','</Pedido>'),"",$pedido);
        //recriamos o node Pedido igual ao anterior, mas agora ja com assinatura
        $pedido=$dom->createElement("Pedido",$pedido);
        $CancelarNfseEnvio=$dom->createElement("e:CancelarNfseEnvio");
        $CancelarNfseEnvio->setAttribute("xmlns:e", "http://www.betha.com.br/e-nota-contribuinte-ws");
        $CancelarNfseEnvio->appendChild($pedido);
        $xml=html_entity_decode($dom->saveXML($CancelarNfseEnvio));
        //echo $xml;exit();
        $soap_response=$this->sendSoapMessage($xml,$urlWebService);
        if($soap_response==false){
            return array(false,'Erro na conexão com o servidor!');
        }
        //tratar os erros
        $sucesso_cancelamento='';
        $data_cancelamento='';
        $error_mesage='';
        $DomResponseXml=new DOMDocument('1.0', 'utf-8');
        $DomResponseXml->loadXML($soap_response);
        //pegamos informacoes relevantes do retorno
        if(strstr($soap_response,'InfConfirmacaoCancelamento')){
            foreach ($DomResponseXml->getElementsByTagName('Sucesso') as $key => $value){
                $sucesso_cancelamento=$value->nodeValue;
            }
            foreach ($DomResponseXml->getElementsByTagName('DataHora') as $key => $value){
                $data_cancelamento=$value->nodeValue;
            }
        }
        //tratamento de erros
        if(strstr($soap_response,'Correcao')){
            foreach ($DomResponseXml->getElementsByTagName('Mensagem') as $key => $value){
                $error_mesage=$value->nodeValue;
            }
            foreach ($DomResponseXml->getElementsByTagName('DataHora') as $key => $value){
                $data_cancelamento=$value->nodeValue;
            }
        }
        //verificamos se realmente retornou alguma nota
        if($sucesso_cancelamento=='true'){
            $data=explode('T', $data_cancelamento);
            $dt_cancel=explode('-', $data[0]);
            $dt_cancel=$dt_cancel[2].'/'.$dt_cancel[1].'/'.$dt_cancel[0];
            $hrs_cancel=explode('.', $data[1]);
            $obs='Nota cancelada em: '.$dt_cancel.' as '.$hrs_cancel[0];
            $nfse->setStatusNfse(3);//status 3 = cancelada, 4 = erro
            $nfse->setObs($obs);
            $nfse->save();
            return array(true,'Nota Cancelada com Sucesso!');
        }else{
            return array(false,$error_mesage);
        }
    }

    /**
     * TENTEI USAR PARA A BETHA, PORÉM NÃO CONSEGUI. LOGO ESSE MÉTODO NÃO É UTILIZADO
     *
     * validarDomXML
     * Validar um arquivo XML com um arquivo XSD 
     * 
     * @name validarDomXML
     * @version 1.0
     * @package NfseFunctions
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param Dom object $xmlDomDocument objeto Dom contendo o arquivo xml a ser validado
     * @param string $pathFileXSD string contendo o path do arquivo XSD para validacao
     * @return mixed true em caso de sucesso ou imprime os erros na tela em caso de erro
     */
    private function validarDomXML(&$xmlDomDocument,$pathFileXSD){
        /* Tenta validar os dados utilizando o arquivo XSD */
        //libxml_disable_entity_loader(false);
        libxml_use_internal_errors(true);
        if (!$xmlDomDocument->schemaValidate($pathFileXSD)) {
            //funcao usada para exibir os erros e warnings
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $return = "<br/>\n";
                switch ($error->level) {
                    case LIBXML_ERR_WARNING:
                        $return .= "<b><span style='color:orange'>Warning $error->code</span></b>: ";
                    break;
                    case LIBXML_ERR_ERROR:
                        $return .= "<b><span style='color:red'>Error $error->code</span></b>: ";
                    break;
                    case LIBXML_ERR_FATAL:
                        $return .= "<b><span style='color:red'>Fatal Error $error->code</span></b>: ";
                    break;
                    }
                $return .= trim($error->message);
                if ($error->file) {
                    $return .= " in <b>$error->file</b>";
                }
                $return .= " on line <b>$error->line</b>\n";
                print $return;
                $return='';
            }
            libxml_clear_errors();
            exit('<br/><br/>Processo Interrompido em virtude dos erros!');
        }else{
            return true;
        }
    }

    /**
     * signXML
     * Assinador TOTALMENTE baseado em PHP para arquivos XML
     * este assinador somente utiliza comandos nativos do PHP para assinar os arquivos XML
     *
     * OBS: As tags da assinatura serao incluidas na tag root do arquivo xml
     * 
     * @name signXML
     * @version 2.10
     * @package NFePHP
     * @author Roberto L. Machado <linux.rlm at gmail dot com> Adaptado por Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param	string $docxml String contendo o arquivo XML a ser assinado
     * @param   string $tagid TAG do XML que devera ser assinada
     * @return	mixed false se houve erro ou string com o XML assinado
     */
    public function signXML($docxml, $tagid=''){
        if($tagid==''){
            $this->errMsg = "Uma tag deve ser indicada para que seja assinada!!\n";
            $this->errStatus = true;
            return false;
        }
        if($docxml==''){
            $this->errMsg = "Um xml deve ser passado para que seja assinado!!\n";
            $this->errStatus = true;
            return false;
        }
        // obter o chave privada para a ssinatura
        $fp = fopen($this->priKEY, "r");
        //var_dump('a',fread($fp));exit();
        $priv_key=fread($fp, 8192);
        fclose($fp);
        $pkeyid=openssl_get_privatekey($priv_key);
        // limpeza do xml com a retirada dos CR, LF, TAB, Tag de abertura e espacos desnecessarios
        $order=array("\r\n", "\n", "\r", "\t");
        $replace='';
	//mesa história dita antes, esse trecho realmente se repete muito, hoje vejo isso =/
        $docxml=str_replace($order, $replace, $docxml);
        $docxml=str_replace('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0" encoding="UTF-8" standalone="no"?>',$docxml);
        $docxml=str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$docxml);
        $docxml=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$docxml);
        $docxml=str_replace('<?xml version="1.0"?>','',$docxml);
        $docxml=str_replace("\n","",$docxml);
        $docxml=str_replace("  "," ",$docxml);
        $docxml=str_replace("> <","><",$docxml);
        // carrega o documento no DOM
        $xmldoc=new DOMDocument('1.0', 'utf-8');
        $xmldoc->preservWhiteSpace=false; //elimina espaços em branco
        $xmldoc->formatOutput=false;
        // MUITO IMPORTANTE: Deixar ativadas as opcoes para limpar os espacos em branco e as tags vazias
        $xmldoc->loadXML($docxml,LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        $root=$xmldoc->documentElement;
        //extrair a tag com os dados a serem assinados
        $node = $xmldoc->getElementsByTagName($tagid)->item(0);
        if (!isset($node)){
            $this->errMsg = "A tag < $tagid > nao existe no XML!";
            $this->errStatus = true;
            return false;
         }
         $id = trim($node->getAttribute("Id"));
         $idnome = preg_replace('/[^0-9]/','', $id);
         //extrai os dados da tag para uma string
         $dados = $node->C14N(false,false,NULL,NULL);
         $dados=str_replace(' >', '>', $dados);
         //echo htmlentities($dados);exit();
         //calcular o hash dos dados
         $hashValue = hash('sha1',$dados,true);
         //converte o valor para base64 para serem colocados no xml
         $digValue = base64_encode($hashValue);
         //monta a tag da assinatura digital
         $Signature = $xmldoc->createElementNS($this->URLdsig,'Signature');
         $root->appendChild($Signature);
         //$node->appendChild($Signature);
         $SignedInfo = $xmldoc->createElement('SignedInfo');
         $Signature->appendChild($SignedInfo);
         //Cannocalization
         $newNode = $xmldoc->createElement('CanonicalizationMethod');
         $SignedInfo->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLCanonMeth);
         //SignatureMethod
         $newNode = $xmldoc->createElement('SignatureMethod');
         $SignedInfo->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLSigMeth);
         //Reference
         $Reference = $xmldoc->createElement('Reference');
         $SignedInfo->appendChild($Reference);
         $Reference->setAttribute('URI', '#'.$id);
         //Transforms
         $Transforms = $xmldoc->createElement('Transforms');
         $Reference->appendChild($Transforms);
         //Transform
         $newNode = $xmldoc->createElement('Transform');
         $Transforms->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLTransfMeth_1);
         //Transform
         $newNode = $xmldoc->createElement('Transform');
         $Transforms->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLTransfMeth_2);
         //DigestMethod
         $newNode = $xmldoc->createElement('DigestMethod');
         $Reference->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLDigestMeth);
         //DigestValue
         $newNode = $xmldoc->createElement('DigestValue',$digValue);
         $Reference->appendChild($newNode);
         // extrai os dados a serem assinados para uma string
         $dados = $SignedInfo->C14N(false,false,NULL,NULL);
         //inicializa a variavel que irÃ¡ receber a assinatura
         $signature = '';
         //executa a assinatura digital usando o resource da chave privada
         $resp = openssl_sign($dados,$signature,$pkeyid);
         //codifica assinatura para o padrao base64
         $signatureValue = base64_encode($signature);
         //SignatureValue
         $newNode = $xmldoc->createElement('SignatureValue',$signatureValue);
         $Signature->appendChild($newNode);
         //KeyInfo
         $KeyInfo = $xmldoc->createElement('KeyInfo');
         $Signature->appendChild($KeyInfo);
         //X509Data
         $X509Data = $xmldoc->createElement('X509Data');
         $KeyInfo->appendChild($X509Data);
         //carrega o certificado sem as tags de inicio e fim
         $cert = $this->__cleanCerts($this->pubKEY);
         //X509Certificate
         $newNode = $xmldoc->createElement('X509Certificate',$cert);
         $X509Data->appendChild($newNode);
         //grava na string o objeto DOM
         $xml = $xmldoc->saveXML();
         // libera a memoria
         openssl_free_key($pkeyid);
         //e olha essa m** aqui de novo, precisamos melhorar isso galera =/
         $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0" encoding="UTF-8" standalone="no"?>',$xml);
         $xml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$xml);
         $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml);
         $xml = str_replace('<?xml version="1.0"?>','',$xml);
         $xml = str_replace("\n","",$xml);
         $xml = str_replace("  "," ",$xml);
         $xml = str_replace("> <","><",$xml);
         //retorna o documento assinado
         //echo htmlentities($xml);exit();
         return $xml;
    } //fim signXML
    
    /**
     * sendSoapMessage
     * Monta o XML padrão das mensagens do protocolo SOAP
     * e envia a mensagem usando o cURL do PHP
     * 10/04/2013 11:15
     * 
     * @name sendSoapMessage
     * @package NFePHP
     * @author Luiz P. Franz <luizpaulofranz at gmail dot com>
     * @param    $stringXML que será o <soap:body> da mensagem
     * @param $urlWebService URL para onde enviar o webservice
     * @return response do webservice
     */

    //Importante falar, que o PHP possui funções nativas para web-services, que utilizam os wsdls para tal e automatiza boa parte disso, porém nesse caso, as funções nativas não funcionaram.
    protected function sendSoapMessage(&$stringXML,$urlWebService=''){
        set_time_limit(0);
        //tags padrão para o protocolo SOAP, dê uma lida
        $soap_msg='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" soapenv:encodingStyle="http://www.w3.org/2001/12/soap-encoding"><soapenv:Body>'.$stringXML.'</soapenv:Body></soapenv:Envelope>';
        //Meu deus, isso aqui de novo!
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0" encoding="UTF-8" standalone="no"?>',$soap_msg);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$soap_msg);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$soap_msg);
        $soap_msg = str_replace("\n","",$soap_msg);
        $soap_msg = str_replace("  "," ",$soap_msg);
        $soap_msg = str_replace("> <","><",$soap_msg);
        //echo htmlentities($soap_msg);exit();
        //Se quisermos salvar o XML enviado para o web-service, esse eh o momento!
          //$arquivo = fopen('/home/paulo/v_1125/httpdocs/arquivos_teste/xyz_smo.xml', 'w');
          //fwrite($arquivo, $soap_msg);
          //fclose($arquivo); //exit();     
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL,            $urlWebService);
        //tempo em segundos de aguardo na tentativa de conectar-se com o servidor
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 40);
        //tempo em que o webservice aguradara a execucao no servidor
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        86400);//24 horas
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
        //verificacao de certificados da camada SSL (https)
        //primeira opcao para analizar certificado de quem chama o servico (o proprio sistema), deixar false
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        //verificar o certificado do servidor da url requisitada 
        //1: confere se a url requisitada consta nas autoridades certificadoras
        //2: verifica se a url da autoridade certificadora corresponde a url chamada
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, 2);
        //enviar a msg soap via post
        curl_setopt($soap_do, CURLOPT_POST,           true );            
        curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_msg);
        //para mensagens SOAP, isso eh obrigatorio
        curl_setopt($soap_do, CURLOPT_HTTPHEADER,     array('Content-Type: text/xml; charset=utf-8', 'Content-Length: '.strlen($soap_msg)));
        
        $response=curl_exec($soap_do);
        //retornamos o xml do response do webservice, aqui nao tratamos erros
        return $response;
    }
    
    /**
     * __cleanCerts
     * Retira as chaves de inicio e fim do certificado digital
     * para inclusão do mesmo na tag assinatura do xml
     *
     * @name __cleanCerts
     * @param    $certFile
     * @return   mixed false ou string contendo a chave digital limpa
     */
    protected function __cleanCerts($certFile){
        try {
            //inicializa variavel
            $data = '';
            //carregar a chave publica do arquivo pem
            if (!$pubKey = file_get_contents($certFile)){
                $msg = "Arquivo não encontrado - $certFile .";
                throw new nfsephpException($msg);
            }
            //carrega o certificado em um array usando o LF como referencia
            $arCert = explode("\n", $pubKey);
            foreach ($arCert AS $curData) {
                //remove a tag de inicio e fim do certificado
                if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) != 0 && strncmp($curData, '-----END CERTIFICATE', 20) != 0 ) {
                    //carrega o resultado numa string
                    $data .= trim($curData);
                }
            }
        } catch (nfephpException $e) {
            $this->__setError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
        return $data;
    }//fim __cleanCerts
    
    /**
     * __setError
     * Adiciona descrição do erro ao contenedor dos erros 
     *  
     * @name __setError
     * @param   string $msg Descrição do erro
     * @return  none
     */
    private function __setError($msg){
        $this->errMsg .= "$msg\n";
        $this->errStatus = true;
    }
    
    //Esse método deve ser adaptado em seu sistema
    protected function getProxNumRpsBetha(){
	//os numeros de RPS da Betha, devem ter 15 digitos
        $maxRps=Doctrine::getTable('Nfse')->getMaxRps();
        if($maxRps){
            $maxRps++;
            for($i=strlen($maxRps);$i<15;$i++){
                $maxRps='0'.$maxRps;
            }
            return $maxRps;
        }else{
            return '000000000000001';
        }
    }
    
    /**
     * Método devse ser adaptado em seu sistema.
     *
     * getProxNumLoteBetha
     * Pega o proximo seuqencial de lote dos Nfse, analiza o maior enviado e incrementa 1
     *  
     * @name getProxNumLoteBetha
     * @return  int numero de lote
     */
    protected function getProxNumLoteBetha(){
        $maxLote=Doctrine::getTable('Nfse')->getMaxLote();
        if($maxLote){
            $maxLote++;
            for($i=strlen($maxLote);$i<15;$i++){
                $maxLote='0'.$maxLote;
            }
            return $maxLote;
        }else{
            return '000000000000001';
        }
    }
    
    /**
     * Função Interna, para verificar se determinada entrada já tem uma nfse associada (Deve ser adaptada para sua realidade)
     *
     * analizaNfseByIdsPagto
     * Analiza se a Nota/Rps marcada jah foi enviada
     *  
     * @name __setError
     * @param   string $msg Descrição do erro
     * @return  none
     */
    public function analizaNfseByIdsPagto(&$ids_pagto){
        $q=Doctrine_Query::create()->from('Nfse')->whereIn('id_pagamento',$ids_pagto);
        if(!$dados=$q->execute()->getData()){
            return true;
        }else{
            $emitidos=array();
            foreach ($dados as $key => $value) {
                if($value->getNumNfse()){
                    $emitidos[]=$value->getNumNfse();
                }else{
                    $emitidos[]=$value->getNumRps();
                }
                unset($ids_pagto[array_search($value->getIdPagamento(),$ids_pagto)]);
            }
            $msg='Alguns pagamentos já foram emitidos/enviados, com o número Rps/Nota';
            foreach ($emitidos as $key => $num_rps_nfse) {
                $msg.=', '.$num_rps_nfse;
            }
            throw new nfsephpException($msg,self::WARNING_MESSAGE);
            return false;
        }
    }
    
    /**
     * convertCaractersAscII
     * Retira caracteres especiais como acentuacao de uma string
     *  
     * @name convertCaractersAscII
     * @param   string $string A string a ser processada
     * @param   string $encoding Codificacao da string, se for null, nao usa nenhuma, se setar alguma coisa usa utf-8
     * @return  string Retorna a string tratada, sem nenhum caractere especial
     */
    function convertCaractersAscII ($string,$encoding = NULL){
        $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ°ºª'; 
        $b = 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYbsaaaaaaaceeeeiiiidnoooooouuuyybyRr   '; 
        if(is_null($encoding)){
            $string = strtr($string, $a, $b); 
            return $string;
            //se for utf8
        }else{
            $string = utf8_decode($string);     
            $string = strtr($string, utf8_decode($a), $b); 
            //$string = strtolower($string); 
            return utf8_encode($string); 
        }
    }
    
}

/**
 * Classe complementar 
 * necessária para extender a classe base Exception
 * Usada no tratamento de erros da API
 * 
 * @version 1.0.0
 * @package /lib
 * @name nfsephpException
 * 
 */
class nfsephpException extends Exception {
    public function errorMessage() {
        $errorMsg = $this->getMessage()."\n";
        return $errorMsg;
    }
}
?>
