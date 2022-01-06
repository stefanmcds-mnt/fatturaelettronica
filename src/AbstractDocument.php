<?php

namespace FatturaElettronica;

abstract class AbstractDocument
{
    /**
     * Constants for root element ("FatturaElettronica")
     */
    const ROOT_TAG_PREFIX = 'p';
    const ROOT_TAG_NAME = 'FatturaElettronica';
    const ROOT_NAMESPACE = 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2';
    const SCHEMA_LOCATION = 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 '
        . 'http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa'
        . '/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd';

    /**
     * DOMDocument object
     */
    protected $dom = null;

    /**
     * Filename
     */
    protected $filename = null;

    /**
     * Optional prefix path where to save this document
     */
    protected $prefixPath = null;

    /**
     * Optional stylesheet href.
     *
     * If not null, the "xml-stylesheet" element will be prepended before
     * the first node.
     */
    protected $stylesheetHref = null;

    /**
     * Invoice formats
     */
    protected static $allowedFormats = array('FPA12', 'FPR12');

    /**
     * Default destination dir where to save documents
     */
    protected static $defaultPrefixPath = null;

    /**
     * Invoice elements
     */
    public static $templateArray = array(
        'FatturaElettronicaHeader' => array(
            'DatiTrasmissione' => array(
                'IdTrasmittente' => array(
                    'IdPaese' => '',
                    'IdCodice' => ''
                ),
                'ProgressivoInvio' => '',
                'FormatoTrasmissione' => '',
                'CodiceDestinatario' => '',
                'ContattiTrasmittente' => array(
                    'Telefono' => '',
                    'Email' => ''
                ),
                'PECDestinatario' => ''
            ),
            'CedentePrestatore' => array(
                'DatiAnagrafici' => array(
                    'IdFiscaleIVA' => array(
                        'IdPaese' => '',
                        'IdCodice' => ''
                    ),
                    'CodiceFiscale' => '',
                    'Anagrafica' => array(
                        'Denominazione' => '',
                        'Nome' => '',
                        'Cognome' => '',
                        'Titolo' => '',
                        'CodEORI' => ''
                    ),
                    'AlboProfessionale' => '',
                    'ProvinciaAlbo' => '',
                    'NumeroIscrizioneAlbo' => '',
                    'DataIscrizioneAlbo' => '',
                    'RegimeFiscale' => '',
                ),
                'Sede' => array(
                    'Indirizzo' => '',
                    'NumeroCivico' => '',
                    'CAP' => '',
                    'Comune' => '',
                    'Provincia' => '',
                    'Nazione' => ''
                ),
                'StabileOrganizzazione' => array(
                    'Indirizzo' => '',
                    'NumeroCivico' => '',
                    'CAP' => '',
                    'Comune' => '',
                    'Provincia' => '',
                    'Nazione' => ''
                ),
                'IscrizioneREA' => array(
                    'Ufficio' => '',
                    'NumeroREA' => '',
                    'CapitaleSociale' => '',
                    'SocioUnico' => '',
                    'StatoLiquidazione' => ''
                ),
                'Contatti' => array(
                    'Telefono' => '',
                    'Fax' => '',
                    'Email' => ''
                ),
                'RiferimentoAmministrazione' => ''
            ),
            // Da valorizzare qualora il cedente/prestatore si avvalga di un
            // rappresentante fiscale in Italia, ai sensi del DPR 633 del 1972
            // e successive modifiche ed integrazioni.
            'RappresentanteFiscale' => array(
                'DatiAnagrafici' => array(
                    'IdFiscaleIVA' => array(
                        'IdPaese' => '',
                        'IdCodice' => ''
                    ),
                    'CodiceFiscale' => '',
                    'Anagrafica' => array(
                        'Denominazione' => '',
                        'Nome' => '',
                        'Cognome' => '',
                        'Titolo' => '',
                        'CodEORI' => ''
                    ),
                ),
            ),
            'CessionarioCommittente' => array(
                'DatiAnagrafici' => array(
                    'IdFiscaleIVA' => array(
                        'IdPaese' => '',
                        'IdCodice' => ''
                    ),
                    'CodiceFiscale' => '',
                    'Anagrafica' => array(
                        'Denominazione' => '',
                        'Nome' => '',
                        'Cognome' => '',
                        'Titolo' => '',
                        'CodEORI' => ''
                    )
                ),
                'Sede' => array(
                    'Indirizzo' => '',
                    'NumeroCivico' => '',
                    'CAP' => '',
                    'Comune' => '',
                    'Provincia' => '',
                    'Nazione' => ''
                ),
                'StabileOrganizzazione' => array(
                    'Indirizzo' => '',
                    'NumeroCivico' => '',
                    'CAP' => '',
                    'Comune' => '',
                    'Provincia' => '',
                    'Nazione' => ''
                ),
                'RappresentanteFiscale' => array(
                    'IdFiscaleIVA' => array(
                        'IdPaese' => '',
                        'IdCodice' => ''
                    ),
                    'Denominazione' => '',
                    'Nome' => '',
                    'Cognome' => ''
                )
            ),
            'TerzoIntermediarioOSoggettoEmittente' => array(
                'DatiAnagrafici' => array(
                    'IdFiscaleIVA' => array(
                        'IdPaese' => '',
                        'IdCodice' => ''
                    ),
                    'CodiceFiscale' => '',
                    'Anagrafica' => array(
                        'Denominazione' => '',
                        'Nome' => '',
                        'Cognome' => '',
                        'Titolo' => '',
                        'CodEORI' => ''
                    )
                ),
            ),
            // Nei casi di documenti emessi da un soggetto diverso dal
            // cedente/prestatore va valorizzato l’elemento seguente.
            'SoggettoEmittente' => ''
        ),
        'FatturaElettronicaBody' => array(
            'DatiGenerali' => array(
                'DatiGeneraliDocumento' => array(
                    // Tipologia del documento oggetto della trasmissione
                    // (fattura, acconto/anticipo su fattura, acconto/anticipo
                    // su parcella , nota di credito, nota di debito, parcella).
                    // TD01 Fattura
                    // TD02 Acconto/Anticipo su fattura
                    // TD03 Acconto/Anticipo su parcella
                    // TD04 Nota di Credito
                    // TD05 Nota di Debito
                    // TD06 Parcella
                    'TipoDocumento' => '',
                    'Divisa' => '',
                    'Data' => '',
                    'Numero' => '',
                    'DatiRitenuta' => array(
                        'TipoRitenuta' => '',
                        'ImportoRitenuta' => '',
                        'AliquotaRitenuta' => '',
                        'CausalePagamento' => ''
                    ),
                    'DatiBollo' => array(
                        'BolloVirtuale' => '',
                        'ImportoBollo' => ''
                    ),
                    'DatiCassaPrevidenziale' => array(
                        'TipoCassa' => '',
                        'AlCassa' => '',
                        'ImportoContributoCassa' => '',
                        'ImponibileCassa' => '',
                        'AliquotaIVA' => '',
                        'Ritenuta' => '',
                        'Natura' => '',
                        'RiferimentoAmministrazione' => ''
                    ),
                    'ScontoMaggiorazione' => array(
                        'Tipo' => '',
                        'Percentuale' => '',
                        'Importo' => ''
                    ),
                    'ImportoTotaleDocumento' => '',
                    'Arrotondamento' => '',
                    'Causale' => '',
                    'Art73' => ''
                ),
                'DatiOrdineAcquisto' => array(
                    'RiferimentoNumeroLinea' => '',
                    'IdDocumento' => '',
                    'Data' => '',
                    'NumItem' => '',
                    'CodiceCommessaConvenzione' => '',
                    'CodiceCUP' => '',
                    'CodiceCIG' => ''
                ),
                'DatiContratto' => array(
                    'RiferimentoNumeroLinea' => '',
                    'IdDocumento' => '',
                    'Data' => '',
                    'NumItem' => '',
                    'CodiceCommessaConvenzione' => '',
                    'CodiceCUP' => '',
                    'CodiceCIG' => ''
                ),
                'DatiConvenzione' => array(
                    'RiferimentoNumeroLinea' => '',
                    'IdDocumento' => '',
                    'Data' => '',
                    'NumItem' => '',
                    'CodiceCommessaConvenzione' => '',
                    'CodiceCUP' => '',
                    'CodiceCIG' => ''
                ),
                'DatiRicezione' => array(
                    'RiferimentoNumeroLinea' => '',
                    'IdDocumento' => '',
                    'Data' => '',
                    'NumItem' => '',
                    'CodiceCommessaConvenzione' => '',
                    'CodiceCUP' => '',
                    'CodiceCIG' => ''
                ),
                'DatiFattureCollegate' => array(
                    'RiferimentoNumeroLinea' => '',
                    'IdDocumento' => '',
                    'Data' => '',
                    'NumItem' => '',
                    'CodiceCommessaConvenzione' => '',
                    'CodiceCUP' => '',
                    'CodiceCIG' => ''
                ),
                'DatiSAL' => array(
                    'RiferimentoBase' => ''
                ),
                'DatiDDT' => array(
                    'NumeroDDT' => '',
                    'DataDDT' => '',
                    'RiferimentoNumeroLinea' => ''
                ),
                'DatiTrasporto' => array(
                    'DatiAnagraficiVettore' => array(
                        'IdFiscaleIVA' => array(
                            'IdPaese' => '',
                            'IdCodice' => ''
                        ),
                        'CodiceFiscale' => '',
                        'Anagrafica' => array(
                            'Denominazione' => '',
                            'Nome' => '',
                            'Cognome' => '',
                            'Titolo' => '',
                            'CodEORI' => ''
                        ),
                        'NumeroLicenzaGuida' => ''
                    ),
                    'MezzoTrasporto' => '',
                    'CausaleTrasporto' => '',
                    'NumeroColli' => '',
                    'Descrizione' => '',
                    'UnitaMisuraPeso' => '',
                    'PesoLordo' => '',
                    'PesoNetto' => '',
                    'DataOraRitiro' => '',
                    'DataInizioTrasporto' => '',
                    'TipoResa' => '',
                    'IndirizzoResa' => array(
                        'Indirizzo' => '',
                        'NumeroCivico' => '',
                        'CAP' => '',
                        'Comune' => '',
                        'Provincia' => '',
                        'Nazione' => ''
                    ),
                    'DataOraConsegna' => '',
                ),
                'FatturaPrincipale' => array(
                    'NumeroFatturaPrincipale' => '',
                    'DataFatturaPrincipale' => ''
                )
            ),
            'DatiBeniServizi' => array(
                'DettaglioLinee' => array(
                    'NumeroLinea' => '',
                    'TipoCessionePrestazione' => '',
                    'CodiceArticolo' => array(
                        'CodiceTipo' => '',
                        'CodiceValore' => ''
                    ),
                    'Descrizione' => '',
                    'Quantita' => '',
                    'UnitaMisura' => '',
                    'DataInizioPeriodo' => '',
                    'DataFinePeriodo' => '',
                    'PrezzoUnitario' => '',
                    'ScontoMaggiorazione' => array(
                        'Tipo' => '',
                        'Percentuale' => '',
                        'Importo' => ''
                    ),
                    'PrezzoTotale' => '',
                    'AliquotaIVA' => '',
                    'Ritenuta' => '',
                    'Natura' => '',
                    'RiferimentoAmministrazione' => '',
                    'AltriDatiGestionali' => array(
                        'TipoDato' => '',
                        'RiferimentoTesto' => '',
                        'RiferimentoNumero' => '',
                        'RiferimentoData' => ''
                    )
                ),
                'DatiRiepilogo' => array(
                    'AliquotaIVA' => '',
                    'Natura' => '',
                    'SpeseAccessorie' => '',
                    'Arrotondamento' => '',
                    'ImponibileImporto' => '',
                    'Imposta' => '',
                    // I IVA ad esigibilità immediata
                    // D IVA ad esigibilità differita
                    // S scissione dei pagamenti
                    'EsigibilitaIVA' => '',
                    'RiferimentoNormativo' => ''
                )
            ),
            // Presenti nei casi di cessioni tra paesi membri di mezzi di
            // trasporto nuovi. Dati relativi ai veicoli di cui
            // all’art. 38, comma 4 del DL 331 del 1993.
            'DatiVeicoli' => array(
                'Data' => '',
                'TotalePercorso' => ''
            ),
            'DatiPagamento' => array(
                'CondizioniPagamento' => '',
                'DettaglioPagamento' => array(
                    'Beneficiario' => '',
                    'ModalitaPagamento' => '',
                    'DataRiferimentoTerminiPagamento' => '',
                    'GiorniTerminiPagamento' => '',
                    'DataScadenzaPagamento' => '',
                    'ImportoPagamento' => '',
                    'CodUfficioPostale' => '',
                    'CognomeQuietanzante' => '',
                    'NomeQuietanzante' => '',
                    'CFQuietanzante' => '',
                    'TitoloQuietanzante' => '',
                    'IstitutoFinanziario' => '',
                    'IBAN' => '',
                    'ABI' => '',
                    'CAB' => '',
                    'BIC' => '',
                    'ScontoPagamentoAnticipato' => '',
                    'DataLimitePagamentoAnticipato' => '',
                    'PenalitaPagamentiRitardati' => '',
                    'DataDecorrenzaPenale' => '',
                    'CodicePagamento' => ''
                )
            ),
            'Allegati' => array(
                'NomeAttachment' => '',
                'AlgoritmoCompressione' => '',
                'FormatoAttachment' => '',
                'DescrizioneAttachment' => '',
                'Attachment' => ''
            )
        )
    );

    /**
     * Constructor
     */
    public function __construct($file = null)
    {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;

        if (null === $file) {
            $root = $this->dom->createElementNS(static::ROOT_NAMESPACE, static::ROOT_TAG_PREFIX . ':' . static::ROOT_TAG_NAME);
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation', static::SCHEMA_LOCATION);
            if (false === is_a($root, '\DOMElement')) {
                throw new \Exception('Method createRootElement must return a \DOMElement');
            }
            if (false === is_array(static::$templateArray)) {
                throw new \Exception('static templateArray must be an array');
            }
            $this->addElementsFromArray($root, static::$templateArray);
            $this->dom->appendChild($root);
        } else if (is_string($file)) {
            //} else if ($file) {
            $this->load($file, 0, 'string');
        } else if (is_readable($file)) {
            //} else if ($file) {
            $this->load($file);
        } else {
            throw new \Exception("File ' $file' not found or not readable ");
        }
    }

    /**
     * Returns document class name
     *
     * @return string
     */
    protected function getClassName()
    {
        $classArray = explode('\\', get_class($this));
        return array_pop($classArray);
    }

    protected function insertStylesheet()
    {
        if (empty($this->stylesheetHref)) {
            throw new \Exception("Cannot add stylesheet because href is empty or null");
        }

        $firstChild = $this->dom->firstChild;

        if ($firstChild->nodeName !== 'xml-stylesheet') {
            $fragment = $this->dom->createDocumentFragment();
            $fragment->appendXML("<?xml-stylesheet type=\"text/xsl\" href=\"{$this->stylesheetHref}\"?>");
            $this->dom->insertBefore($fragment, $this->dom->documentElement);
        } else {
            $firstChild->nodeValue = "type=\"text/xsl\" href=\"{$this->stylesheetHref}\"";
        }

        return $this;
    }

    protected function removeStylesheet()
    {
        $firstChild = $this->dom->firstChild;

        if ($firstChild->nodeName === 'xml-stylesheet') {
            $this->dom->removeChild($firstChild);
        }

        return $this;
    }

    public function setStylesheet(string $href)
    {
        $this->stylesheetHref = $href;

        return $this;
    }

    public function unsetStylesheet()
    {
        $this->stylesheetHref = null;

        return $this;
    }

    /**
     * DOMDOCUMENT, DOMXPATH, XML
     * **************************************************************************
     */

    /**
     * Returns the document as XML
     */
    public function asXML(bool $normalize = true)
    {
        if (true === $normalize) {
            $this->normalize();
        }

        if (false === empty($this->stylesheetHref)) {
            $this->insertStylesheet();
        } else {
            $this->removeStylesheet();
        }

        return $this->dom->saveXML(null, LIBXML_NOEMPTYTAG);
    }

    /**
     * Returns the DOMDocument object
     *
     * @return \DOMDocument
     */
    public function getDOM()
    {
        return $this->dom;
    }

    /**
     * Removes empty elements
     */
    public function normalize()
    {
        // TODO: recursive
        for ($i = 0; $i < 4; $i++) {
            foreach ($this->query('//*[not(node())]') as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        return $this;
    }

    /**
     * Query for elements.
     *
     * - Absolute paths: omit root element (for example p:FatturaElettronica)
     * - Tags are prefixed with "(.)//"
     * - Relative paths are prefixed with "(.)//"
     *
     * $context can be a string or a \DOMNode
     *
     * @return \DOMNodeList
     */
    public function query(string $expr, $context = null, bool $registerNodeNS = true)
    {
        $strpos = strpos($expr, '/');

        if (false === $strpos) {
            $expr = "//$expr";

            if (null !== $context) {
                $expr = ".$expr";
            }
        } else if ($strpos === 0) {
            // Absolute path cannot have a context
            if (null !== $context) {
                throw new \Exception("Cannot specify a context with an absolute path ($expr)");
            }

            if ($expr === '/') {
                $expr = '';
            }

            $expr = '/' . static::ROOT_TAG_PREFIX . ':' . static::ROOT_TAG_NAME . $expr;
        } else if ($strpos > 1) {
            $expr = "//$expr";

            if (null !== $context) {
                $expr = ".$expr";
            }
        }

        if (null !== $context) {
            $context = $this->getElement($context);
        }

        $xpath = new \DOMXpath($this->dom);
        return $xpath->query($expr, $context, $registerNodeNS);
    }

    /**
     * Returns the document as HTML
     */
    public function asHTML(bool $normalize = true)
    {
        if (true === $normalize) {
            $this->normalize();
        }

        if (true === empty($this->stylesheetHref)) {
            throw new \Exception("Cannot specify a StyleSheet template use stylesheetHref('url')");
        }

        // Load XSLT stylesheet
        $xsl = new \DOMDocument;
        $xsl->load($this->stylesheetHref);

        // Configure the transformer
        $proc = new \XSLTProcessor;
        $proc->importStyleSheet($xsl); // attach the xsl rules
        $transformed = $proc->transformToXML($this->dom);
        // determining if output is html document
        $html = $transformed;

        // splitting up html document at doctype and doc
        $html_array = explode("\n", $html, 15);

        $html_doc = array_pop($html_array);

        $html_doctype = implode("\n", $html_array);

        // convert XHTML syntax to HTML5
        // <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        // <!DOCTYPE html>
        $html_doctype = preg_replace("/<!DOCTYPE [^>]+>/", "<!DOCTYPE html>", $html_doctype);

        // <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
        // <html lang="en">
        $html_doctype = preg_replace('/ xmlns=\"http:\/\/www.w3.org\/1999\/xhtml\"| xml:lang="[^\"]*\"/', '', $html_doctype);
        // <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        // to this --> <meta charset="utf-8" />

        $html_doctype = preg_replace('/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=(.*[a-z0-9-])\" \/>/i', '<meta charset="\1" />', $html_doctype);
        $html = $html_doctype . "\n" . $html_doc;

        //return $this->dom->saveXML(null, LIBXML_NOEMPTYTAG);
        return $html;
    }

    /**
     * FILENAME, PATH, LOAD/SAVE FILE
     * **************************************************************************
     */

    /**
     * Get destination dir
     */
    public static function getDefaultPrefixPath()
    {
        return static::$defaultPrefixPath;
    }

    /**
     * Get filename
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filename
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Set destination dir (common prefix path when saving invoices)
     */
    public static function setDefaultPrefixPath(string $dir)
    {
        $dir = realpath($dir);

        if (false === $dir) {
            throw new \Exception("Cannot access to '$dir'");
        }
        if (false === is_writeable($dir)) {
            throw new \Exception("Directory '$dir' is not writeable");
        }

        static::$defaultPrefixPath = $dir;
    }

    /**
     * Get prefix path for current object
     */
    public function getPrefixPath()
    {
        return $this->prefixPath;
    }

    /**
     * Load a document from file
     */
    protected function load(string $filename, int $options = 0, $string = null)
    {
        if (null === $string && is_readable($filename)) {
            if (false === $this->dom->load($filename, $options)) {
                throw new \Exception("Cannot load file '$filename'");
            }
        } elseif (is_string($string)) {
            if (false === $this->dom->loadXML($filename, $options)) {
                throw new \Exception("Cannot load string '$filename'");
            }
        }
        return $this;
    }

    /**
     * Save document to file.
     * If set, prepend static::$defaultPrefixPath to path.
     * Overwrite existing file if $overwrite is true.
     */
    public function save(bool $overwrite = false, bool $normalize = true)
    {
        $prefixPath = '.';
        if (null !== $this->prefixPath) {
            $prefixPath = $this->prefixPath;
        } else if (null !== static::$defaultPrefixPath) {
            $prefixPath = static::$defaultPrefixPath;
        }
        $prefixPath = realpath($prefixPath);

        if (false === $prefixPath) {
            throw new \Exception("Cannot set a valid prefixPath ('$prefixPath')");
        }

        $filename = $this->getFilename();

        if (false === is_string($filename) || empty($filename)) {
            throw new \Exception("Filename '$filename' empty or invalid");
        }

        $dest = $prefixPath . DIRECTORY_SEPARATOR . $filename;

        $className = $this->getClassName();

        if (file_exists($dest) && false === $overwrite) {
            throw new \Exception("$className '$dest' already exists");
        }

        if (true === $normalize) {
            $this->normalize();
        }

        if (false === empty($this->stylesheetHref)) {
            $this->insertStylesheet();
        } else {
            $this->removeStylesheet();
        }

        // if (false === $this->dom->save($dest, LIBXML_NOEMPTYTAG)) {
        if (false === $this->dom->save($dest)) {
            throw new \Exception("Cannot save $className to '$dest'");
        }

        return $this;
    }

    /**
     * Set optional destination dir for current object
     */
    public function setPrefixPath(string $dir)
    {
        $dir = realpath($dir);

        if (false === $dir) {
            throw new \Exception("Cannot access to '$dir'");
        }
        if (false === is_writeable($dir)) {
            throw new \Exception("Directory '$dir' is not writeable");
        }

        $this->prefixPath = $dir;

        return $this;
    }

    /**
     * ELEMENTS
     * **************************************************************************
     */

    /**
     * Add an element
     */
    public function addElement($element, $parent, $beforeRef = null)
    {
        $parent = $this->getElement($parent);

        if (is_string($element)) {
            $element = $this->dom->createElement($element);
        } else if (false === $element instanceof \DOMNode) {
            throw new \Exception('Invalid $element parameter');
        }

        if (null === $beforeRef) {
            $parent->appendChild($element);
        } else {
            $beforeRef = $this->getElement($beforeRef);
            $parent->insertBefore($element, $beforeRef);
        }

        return $element;
    }

    /**
     * Recursively adds elements from array
     */
    public function addElementsFromArray($parent, array $array)
    {
        $parent = $this->getElement($parent);

        foreach ($array as $k => $v) {
            if (true === is_array($v)) {
                $node = $this->dom->createElement($k);
                $this->addElementsFromArray($node, $v);
            } else {
                $node = $this->dom->createElement($k, $v);
            }
            $parent->appendChild($node);
        }
    }

    /**
     * Query document for specified element.
     *
     * Throws an exception if query returns 0 or N>1 elements.
     */
    public function getElement($expr, $context = null)
    {
        if ($expr instanceof \DOMNode) {
            return $expr;
        } else if (false === is_string($expr)) {
            throw new \Exception('Invalid param $expr');
        }

        if (null !== $context) {
            $context = $this->getElement($context);
        }

        $elements = $this->query($expr, $context);
        $count = $elements->length;

        if ($count !== 1) {
            $errSuffix = null === $context ? "" : " (context: {$context->nodeName})";
        }
        /*
        if ($count > 1) {
            $nodeValues = 'values:';
            foreach ($elements as $element) {
                $nodeValues .= " {$element->nodeName}:{$element->nodeValue},";
            }
            throw new \Exception("Element '$expr' returns $count elements ($nodeValues)$errSuffix");
        } else */
        if ($count === 0) {
            //throw new \Exception("Element not found ($expr)$errSuffix");
            $elem = false;
        } else {
            $elem = $elements->item(0);
            //$elem = $elements;
        }

        //return $elements->item(0);
        return $elem;
    }

    /**
     * Remove an element
     */
    public function removeElement($element)
    {
        $element = $this->getElement($element);
        $parent = $element->parentNode;
        $parent->removeChild($element);

        return $this;
    }

    /**
     * Set element count
     */
    public function setElementCount($expr, int $n, $context = null)
    {
        $currentList = $this->query($expr, $context);
        $currentCount = $currentList->length;

        if (0 === $currentCount) {
            throw new \Exception("Cannot find an element with expr/context: $expr/$context");
        }

        if ($currentCount === $n) {
            return $this;
        }

        if ($currentCount > $n) {
            $exceeding = $currentCount - $n;

            for ($i = $currentCount; --$i >= $n;) {
                $cur = $currentList->item($i);
                $cur->parentNode->removeChild($cur);
            }
        } else {
            $adding = $n - $currentCount;
            $element = $currentList->item(0);
            $nextSibling = $currentList->item($currentCount - 1);

            for ($i = 0; $i < $adding; $i++) {
                $this->addElement($element->cloneNode(true), $element->parentNode, $nextSibling);
            }
        }

        return $this;
    }

    /**
     * Split an element if its value (as string) is > $splitLen characters.
     */
    public function splitElement($element, int $splitLen, $context = null)
    {
        $element = $this->getElement($element, $context);
        $nextSibling = $element->nextSibling;
        $value = $this->getValue($element);
        $strlen = strlen((string)$value);

        if ($strlen <= $splitLen) {
            return $this;
        }

        $chunks = str_split($value, $splitLen);
        $first = array_shift($chunks);

        $this->setValue($element, $first);

        foreach ($chunks as $chunk) {
            $newElement = $this->addElement($element->nodeName, $element->parentNode, $nextSibling);
            $newElement->nodeValue = $chunk;
        }
    }

    /**
     * VALUES
     * **************************************************************************
     */

    /**
     * Get value
     */
    public function getValue($expr, $context = null)
    {
        return $this->getElement($expr, $context)->nodeValue;
    }

    /**
     * Set value for a given element, if the element is unique (by tag or xpath).
     * Throws an exception otherwise.
     */
    public function setValue($expr, $value, $context = null)
    {
        $this->getElement($expr, $context)->nodeValue = $value;

        return $this;
    }

    /**
     * Set same value $value to all elements retrieved through $expr
     */
    public function setValueToAll($expr, $value, $context = null)
    {
        $elements = $this->query($expr, $context);

        foreach ($elements as $element) {
            $element->nodeValue = $value;
        }

        return $this;
    }

    /**
     * Set values from an associative array. Keys must return just one element.
     * Array keys are relative to $expr/$context.
     */
    public function setValues($expr, array $array, $context = null)
    {
        $element = $this->getElement($expr, $context);

        foreach ($array as $k => $v) {
            $this->setValue($k, $v, $element);
        }

        return $this;
    }

    /**
     * Recursively set values from array
     */
    public function setValuesFromArray($expr, array $array, $context = null)
    {
        $parent = $this->getElement($expr, $context);

        foreach ($array as $k => $v) {
            if (true === is_array($v)) {
                $node = $this->getElement($k, $parent);
                $this->setValuesFromArray($node, $v);
            } else {
                $this->setValue($k, $v, $parent);
            }
        }
    }

    /**
     * Set values from an associative array. Keys may return N elements.
     * Array keys are relative to $expr/$context.
     */
    public function setValuesToAll($expr, array $array, $context = null)
    {
        $element = $this->getElement($expr, $context);

        foreach ($array as $k => $v) {
            $this->setValueToAll($k, $v, $element);
        }

        return $this;
    }
}
