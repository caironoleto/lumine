<?php
/**
 * Classe principal para as entidades. 
 * Todas as classes de interace com o banco devem extender esta classe
 *
 * @author Hugo Ferreira da Silva
 * @package Lumine
 */
Lumine::load('Sequence');

/**
 * Classe principal
 *
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br/lumine
 * @package Lumine
 */
class Lumine_Base extends Lumine_EventListener
{
	const BASE_CLASS           = 'Lumine_Base';
	const ONE_TO_MANY          = 1;
	const MANY_TO_ONE          = 2;
	const MANY_TO_MANY         = 3;

	const STATE_TRANSIENT      = 10;
	const STATE_PERSISTENT     = 11;
	
	const WHERE_ADD_ONLY       = 30;
	
	const SQL_SELECT           = 40;
	const SQL_SELECT_COUNT     = 41;
	const SQL_UPDATE           = 42;
	const SQL_INSERT           = 43;
	const SQL_DELETE           = 44;
	const SQL_MULTI_INSERT     = 45;
	
	const FETCH_ROW            = 50;
	const FETCH_ASSOC          = 51;
	const FETCH_BOTH           = 52;
	
    /**
     * Armazena o estado atual do objeto (estÃ¡ sendo usado)
     */
	protected static $state;

    /**
     * DefiniÃ§Ã£o do objeto
     */
	protected $_definition     = array();
	
	/**
	 * Armazena os campos pelo nome das colunas
	 */
	protected $_fieldsByColumn = array();
	
    /**
     * Chaves estrangeiras
     */
	protected $_foreign        = array();

    /**
     * Faz a ponte com o dialeto
     */
	protected $_bridge         = null;
	
    /**
     * Lista de classes que foram utilizadas no inner|left|right join
     */
	protected $_join_list      = array();
	
    /**
     * Alias para a tabela
     */
	protected $_alias          = '';

	// partes da consulta
    /**
     * Campos a serem selecionados (data select)
     */	
	protected $_data           = array();
    /**
     * tabelas para consulta (FROM)
     */	
	protected $_from           = array();
    /**
     * condiÃ§Ãµes de pesquisa
     */
	protected $_where          = array();
    /**
     * Clausula having
     */
	protected $_having         = array();
    /**
     * Clausula order by
     */
	protected $_order          = array();
    /**
     * Clausula de agrupamento
     */
	protected $_group          = array();
    /**
     * lista de strings de uniÃ£o das classes
     */
	protected $_join           = array();
    /**
     * Limite de registros em uma consulta
     */
	protected $_limit          = null;
	/**
     * Inicio dos registros em uma consulta com limit
     */
	protected $_offset         = null;
	
    /**
     * Modo do resultado (estÃ¡ sendo usado?)
     */
	protected $_fetch_mode     = self::FETCH_ASSOC;
	
    /**
     * Armazena os dados da linha atual
     */
	protected $_dataholder     = array();
	
	/**
	 * Armazena os dados originais da linha atual
	 */
	protected $_original_dataholder = array();
	
	/**
	 * Formatadores de campos
	 */
	protected $_formatters       = array();
	
    /**
     * Eventos disparados por esta classe
     */
	protected $_event_types  = array(
		'preInsert','preUpdate','preDelete','preSelect','preGet','preFind',
		'posInsert','posUpdate','posDelete','posSelect','posGet','posFind',
		'preQuery', 'posQuery',
		'preFormat', 'posFormat',
		'onPreMultiInsert', 'onPosMultiInsert'
	);
	
	// devem ser sobrecarregados para carregamento correto
    /**
     * Pacote que esta classe pertence
     */	
	protected $_package      = null;
    /**
     * Nome da tabela que esta classe representa
     */
	protected $_tablename    = null;
	
	/**
	 * Armazena a lista de multi-inserts
	 */
	protected $_multiInsertList = array();
	
    /**
     * Construtor da classe
	 * NÃ£o deverÃ¡ ser instanciado diretamente, e se a classe filha tiver um construtor
	 * deverÃ¡ chamar este para que funcione corretamente.
	 * @author Hugo Ferreira da Silva
     */
     
     protected static $package;
     
	function __construct()
	{
		if($this->_package == null || $this->_tablename == null)
		{
			throw new Lumine_Exception('Você não pode acessar esta classe diretamente.', Lumine_Excetpion::ERROR);
		}
		self::$package = $this->_package;
		
		$dialect = $this->_getConfiguration()->getProperty('dialect');
		$class_dialect = 'Lumine_Dialect_' . $dialect;

		Lumine::load($class_dialect);

		if( ! class_exists($class_dialect) )
		{
			throw new Lumine_Exception('Dialeto não encontrado: '.$class_dialect, Lumine_Exception::ERROR);
		}

		$this->_initialize();
		$this->_join_list[] = $this;
		$this->_from[]      = $this;
		$this->_bridge      = new $class_dialect($this->fetchMode());
		// varre as chaves primarias em busca de classes pais
		$this->_joinSubClasses( $this );
	}
	
	public function destroy() {
		$this->__destruct();
	}
	
	function __destruct()
	{
		if (count($this->_join_list) > 0) {
			foreach ($this->_join_list as $key => $value) {
				unset($value);
			}
		}

		if (count($this->_from) > 0) {
			foreach ($this->_from as $key => $value) {
				unset($value);
			}
		}
		$list = get_object_vars( $this );
		foreach( $list as $key => $val )
		{
			unset($this->$key);
		}
		unset($this->_join_list, $this->_from, $list, $this->_bridge, $this->_alias, $this->_data, $this->_where, $this->_having, $this->_order, $this->_group, $this->_join, $this->_limit, $this->_offset, $this->_fetch_mode, $this->_dataholder, $this->_original_dataholder, $this->_multiInsertList, $this->_formatters, $this->_join_list, $this->_from, $this->_bridge, $this->_alia, $this->_data, $this->_where, $this->_having, $this->_order, $this->_group, $this->_join, $this->_limit, $this->_offset, $this->_fetch_mode, $this->_dataholder, $this->_original_dataholder, $this->_multiInsertList, $this->_formatters);
		parent::__destruct();
	}

	//----------------------------------------------------------------------//
	// MÃ©todos pÃºblicos                                                     //
	//----------------------------------------------------------------------//
	/**
	 * Recupera registros a partir da chave primaria ou chave = valor
	 *
	 * @param mixed $pk Valor da chave primaria ou nome do membro a ser pesquisado
	 * @param mixed $pkValue Valor do campo quando pesquisado por um campo em especÃ­fico
	 * @author Hugo Ferreira da Silva
	 * @return int NÃºmero de registros encontrados.
	 */	
	public function get( $pk, $pkValue = null )
	{
		$this->dispatchEvent('preGet', $this);

		if( !empty($pk) && ! empty($pkValue) )
		{
			$field = $this->_getField( $pk );
			$this->$field['name'] = $pkValue;
			$this->find(true);
			return $this->_bridge->num_rows();
			
		} else if( !empty($pk)){
			$list = $this->_getPrimaryKeys();
			
			if( empty($list) )
			{
				Lumine_Log::warning( 'A entidade '.$this->_getName().' nÃ£o possui chave primÃ¡ria. Especifique um campo.');
				return 0;
			}

			$this->$list[0]['name'] = $pk;
			$this->find( true );
			
			$this->dispatchEvent('posGet', $this);
			return $this->_bridge->num_rows();
		}
		Lumine_Log::warning('Nenhum valor informado para recuperaÃ§Ã£o em '.$this->_getName());
	}

	/**
	 * Efetua uma consulta a partir dos valores dos membros
	 * <code>
	 * var $pessoa = new Pessoa;
	 * $pessoa->email = 'eu@hufersil.com.br';
	 * $pessoa->find();
	 * </code>
	 * GerarÃ¡ 
	 * <code>
	 * SELECT pessoa.nome, pessoa.email, pessoa.codpessoa, pessoa.data_cadastro FROM pessoa WHERE pessoa.email = 'eu@hufersil.com.br'
	 * </code>
	 *
	 * @param boolean $auto_fetch Ir para o primeiro registro assim que finalizado
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br/lumine Lumine - Mapeamento para banco de dados em PHP
	 * @return int NÃºmero de registros encontrados.
	 */
	public function find( $auto_fetch = false )
	{
		$this->dispatchEvent('preFind', $this);
		
		$sql = $this->_getSQL(self::SQL_SELECT);
		$result = $this->_execute($sql);
		
		if($result == true)
		{
			if($auto_fetch == true)
			{
				$this->fetch();
			}
		}
		
		$this->dispatchEvent('posFind', $this);
		
		return $this->_bridge->num_rows();
	}
	
	/**
	 * Move o cursor para o prÃ³ximo registro
	 *
	 * @param boolean $getLinks Recuperar automaticamente os links do tipo Lazy
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br/lumine Lumine - Mapeamento para banco de dados em PHP
	 * @return boolean True se existir registros, do contrÃ¡rio false
	 */	
	public function fetch( $getLinks = true )
	{
		$result = $this->_bridge->fetch();
		
		if($result === false)
		{
			return false;
		}
		$this->_dataholder = array();
		$this->_original_dataholder = array();
		
		foreach($result as $key => $val)
		{
			$def = $this->_getFieldByColumn( $key );
			if( !empty($def))
			{
				$key = $def['name'];
			}
			
			
			
			//if( gettype($val) != 'NULL')
			//{			
			$this->$key = $val;
			$this->_original_dataholder[ $key ] = $val;
			//}
		}
		
		$this->loadLazy(  );
		
		return true;
	}
	
	/**
	 * NÃºmero de registros encontrados na Ãºltima consulta
	 *
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br/lumine Lumine - Mapeamento para banco de dados em PHP
	 * @return int NÃºmero de registros encontrados.
	 */
	public function numrows()
	{
		return $this->_bridge->num_rows();
	}

     /**
      * Numero de linhas afetadas apÃ³s um UPDATE ou DELETE
      *
      * <code>
      * </code>
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return int NÃºmero de linhas afetadas
      */
	public function affected_rows()
	{
		return $this->_bridge->affected_rows();
	}
	
     /**
      * Efetua uma consulta de contagem 
      *
      * <code>
	  * $obj->count();
      * </code>
	  * IrÃ¡ produzir
      * <code>
	  * SELECT count(*) FROM tabela
      * </code>
      * @param string $what coluna ou condicionamento desejado para efetuar a contagem
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return int NÃºmero de registros encontrados
      */
	public function count($what = '*')
	{
		$ds = $this->_bridge->getDataset();
		
		$sql = $this->_prepareSQL(true, $what);
		$res = $this->_execute($sql);
		
		if($res == true)
		{
			$total = $this->_bridge->fetch();
			$this->_bridge->setDataset( $ds );

			return $total['lumine_count'];
		}

		$this->_bridge->setDataset( $ds );		
		return 0;
	}

     /**
      * Indica quais campos deverÃ£o ser selecionados em uma consulta (SELECT)
      *
	  * <code>
	  * $obj->select('nome, data_nascimento, codpessoa);
	  * $obj->find();
	  * // SELECT nome, data_nascimento, codpessoa FROM pessoas
	  * </code>
      * @param string $data String contendo os valores a serem selecionados
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base A prÃ³pria classe
      */
	public function select( $data )
	{
		if( is_null($data) )
		{
			$this->_data = array();
		} else {
			$parts = Lumine_Tokenizer::dataSelect( $data, $this );
			$this->_data = array_merge($this->_data, $parts);
		}
		
		return $this;
	}
	
     /**
      * Adiciona a seleÃ§Ã£o de campos de outra classe permitindo alterar seu padrÃ£o para nÃ£o mesclar
      *
	  * <code>
	  * $pessoa = new Pessoa;
	  * $carro = new Carro;
	  * $pessoa->join($carro);
	  * $pessoa->selectAs($carro, '%s_carro');
	  * $obj->find();
	  * // SELECT pessoa.nome, pessoa.data_nascimento, pessoa.codpessoa, carro.nome as nome_carro, carro.modelo_carro FROM pessoa inner join carro on (pessoa.idpessoa=carro.idpessoa)
	  * </code>
      * @param string $data String contendo os valores a serem selecionados
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base A prÃ³pria classe
      */
	public function selectAs( Lumine_Base $obj = null, $format = '%s' )
	{
		if( empty($obj))
		{
			$obj = $this;
		}
		
		$list = $obj->_getDefinition();
		$objName = $obj->_getName();
		
		foreach($list as $name => $options)
		{
			$this->_data[] = sprintf('{%s.%s} as "'.$format.'"', $objName, $name, $name);
		}
		return $this;
	}

     /**
      * Adiciona uma classe (tabela) a lista de seleÃ§Ã£o (SELECT .. FROM tabela1, tabela2)
      *
      * <code>
	  * $car = new Carro;
	  * $pes = new Pessoa;
	  * $car->from($car);
	  * // SELECT * FROM pessoa, carro
      * </code>
      * @param Lumine_Base $obj Objeto para uniÃ£o
	  * @param strin $alias Alias para a tabela de uniÃ£o
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base O prÃ³prio objeto
      */	
	public function from(Lumine_Base $obj = null, $alias = null)
	{
		if(is_null($obj))
		{
			$this->_from = array($this);
		} else {
			if(!empty($alias))
			{
				$obj->_setAlias($alias);
			}
			$this->_from[] = $obj;
			// adiciona tambÃ©m na lista de join's
			$list = $obj->_getObjectPart('_join_list');
			foreach($list as $ent)
			{
				$add = true;
				foreach($this->_join_list as $this_ent)
				{
					if($ent->_getName() == $this_ent->_getName() && $ent->_getAlias() == $this_ent->_getAlias())
					{
						$add = false;
						break;
					}
				}
				if($add)
				{
					$this->_join_list[] = $ent;
					// verifica a lista de join string
					$this->_join = array_merge($this->_join, $ent->_getStrJoinList());
					$this->_join = array_unique($this->_join);
				}
			}
		}
		return $this;
	}
	
	/**
	 * Adiciona a consulta de uma classe para realizar uma consulta uniÃ£o
	 * <code>
	 * $obj1 = new Teste;
	 * $obj1->where('nome like ?', 'hugo');
	 * $obj2 = new Teste;
	 * $obj2->where('nome like ?', 'mirian');
	 * $obj2->union( $obj1 );
	 * $obj2->find();
	 * // (SELECT * FROM teste WHERE nome like '%hugo%') UNION (SELECT * FROM teste WHERE nome like '%mirian%')
	 * </code>
	 * @param Lumine_Base $obj Objeto para unir com esta classe
	 * @return Lumine_Union Uma instancia de Lumine_Union contendo as uniÃµes realizadas
	 * @author Hugo Ferreira da Silva
	 */
	function union(Lumine_Base $obj)
	{
		$union = new Lumine_Union(Lumine_ConnectionManager::getInstance());
		$union->add($this)
			->add($obj);
			
		return $union;
	}
	
     /**
      * Une uma classe com outra para efetuar uma consulta (inner|left|right) join
      *
      * <code>
	  * $car = new Carro;
	  * $pes = new Pessoa;
	  * $car->join($car);
	  * // SELECT pessoa.nome, pessoa.idpessoa, carro.modelo FROM pessoa inner join carro on(carro.idpessoa=pessoa.idpessoa)
      * </code>
      * @param Lumine_Base $obj Objeto para uniÃ£o
	  * @param string $type Tipo de uniÃ£o (LEFT|INNER|RIGHT)
	  * @param string $alias Alias para a tabela de uniÃ£o
	  * @param string $linkName Nome especifico do link desta entidade
	  * @param string $linkTo Nome da propriedade que se deseja linkar na outra entidade
	  * @param string $extraCondition CondiÃ§Ã£o extra para adicionar a clausula ON da uniÃ£o
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base O prÃ³prio objeto
      */
	public function join( Lumine_Base $obj, $type = 'INNER', $alias = '', $linkName = null, $linkTo = null, $extraCondition = null )
	{
		if( !preg_match('@^(INNER|LEFT|CROSS)$@i', $type) )
		{
			Lumine_Log::error('Tipo de uniÃ£o nÃ£o permitida: ' . $type);
			return $this;
		}
		
		$type = strtoupper( $type );
		
		// verifica as chaves daqui pra lÃ¡
		$name = $obj->_getName();
		if( is_null( $linkName ) )
		{
			Lumine_Log::debug('Nome do link nÃ£o especificado. Tentando recuperar automaticamente de '.$name);
			$opt = $this->_getRelation( $name );
		} else {
			Lumine_Log::debug('Nome de link especificado: '. $linkName);
			$opt = $this->_getField( $linkName );
		}
		
		if( !empty($alias))
		{
			Lumine_Log::debug('Alterando o alias de '.$name.' para '. $alias);
			$obj->_setAlias( $alias );
		}
		
		$dest = null;														// link de destino
		if( !empty($linkTo) )												// se foi especificado um link de destino
		{
			Lumine_Log::debug('Link de destino especificado: '. $linkTo);
			$dest = $obj->_getField( $linkTo );								// pega o link de destino
		}
		
		if( !is_null($extraCondition) )										// se a pessoa definiu uma condiÃ§Ã£o extra
		{
			$args = func_get_args();
			if( count($args) > 6 )
			{
				$args = array_slice($args, 6);
				
			} else {
				$args = null;
			}
			
			$extraCondition = trim($extraCondition);						// remove espaÃ§os em branco
			
			try {
				$extraCondition = Lumine_Parser::parsePart($obj, $extraCondition, $args); // faz o parser para certificaÃ§Ã£o que os campos existem certinho
				$extraCondition = Lumine_Parser::parseEntityNames($obj, $extraCondition); 

			} catch(Exception $e) {
			
				try {
					$extraCondition = Lumine_Parser::parsePart($this, $extraCondition, $args); // faz o parser para certificaÃ§Ã£o que os campos existem certinho
					$extraCondition = Lumine_Parser::parseEntityNames($this, $extraCondition); 
					
				} catch(Exception $e) {
					Lumine_Log::warning('Houve um erro na analise da condiÃ§Ã£o extra');
				}
			}
			
			if( !preg_match('@^(ON|AND)@i', $extraCondition) )				// se nÃ£o definiu o tipo de logica inicial
			{
				$extraCondition = " AND " . $extraCondition;				// o padrÃ£o Ã© AND
			}
			$extraCondition .= " ";											// adiciona um espaÃ§o em branco para ficar certinho
		}
		
		// se a pessoa especificou um linkTo e linkName e ambos existem
		if( $opt != null && $dest != null )
		{
			Lumine_Log::debug('Ambos links especificados, fazendo uniÃ£o...');
			$schema = '';													// schema das tabelas
			$cfg = $this->_getConfiguration();								// pega o objeto de configuraÃ§Ã£o
			if( $cfg->getOption('schema_name') != null )					// se especificou um schema
			{
				$schema = $cfg->getOption('schema_name').'.';				// coloca o nome do schema mais um ponto
			}
			
			// se for uma uniÃ£o many-to-many e ambas tabelas forem iguais
			if( $opt['type'] == self::MANY_TO_MANY && $dest['type'] == self::MANY_TO_MANY && $opt['table'] == $dest['table'] )
			{
				Lumine_Log::debug('Link do tipo N-N');
				$joinString = "%s JOIN %s ON %s.%s = %s.%s ".PHP_EOL;		// prepara a string de uniÃ£o
				$joinString .= " %s JOIN %s %s ON %s.%s = %s.%s ";
				
				$this_link = $this->_getField( $opt['linkOn'] );			// pega o campo referente a uniÃ£o desta entidade
				$dest_link = $obj->_getField( $dest['linkOn'] );			// pega o campo referente a uniÃ£o da entidade que estÃ¡ sendo unida
				
				$joinString = sprintf($joinString,							// monta a string de join ...
					
					// primeiro, a uniÃ£o da tabela de N-N com esta entidade
					$type,													// ... tipo de uniÃ£o
					$schema . $opt['table'],								// ... nome da tabela N-N
					$opt['table'],											// ... nome da tabela N-N com...
					$opt['column'],											// ... o nome do campo N-N
					$this->_getAlias(),										// ... alias desta entidade
					$this_link[ 'column' ],									// ... coluna desta entidade

					// agora, a uniÃ£o da tabela de N-N com a outra entidade
					$type,													// tipo de uniÃ£o
					$schema . $obj->tablename(),							// nome da tabela estrangeira
					$obj->_getAlias(),										// alias da tabela entrangeira
					$obj->_getAlias(),										// alias da tabela entrangeira
					$dest_link['column'],									// nome do campo da tabela estrangeira
					$dest['table'],											// nome da tabela N-N
					$dest['column']											// nome da coluna da tabela N-N
				);
				
				$this->_join[] = $joinString . $extraCondition;				// coloca a string de uniÃ£o na lista
				
			} else {
				Lumine_Log::debug('Link do tipo 1-N');
				$joinString = "%s JOIN %s %s ON %s.%s = %s.%s";				// inicia a string do join
				$joinString = sprintf( $joinString,							// faz o parse colocando...
					$type,													// ... o tipo de uniÃ£o
					$schema . $obj->tablename(),							// ... o nome da tabela que estÃ¡ sendo unida
					$obj->_getAlias(),										// ... o alias usado na tabela que estÃ¡ sendo unida
					$this->_getAlias(),										// ... o alias desta tabela
					$opt['column'],											// ... a coluna desta tabela
					$obj->_getAlias(),										// ... o alias da tabela que estÃ¡ sendo unida
					$dest['column']											// ... a coluna que estÃ¡ sendo unida
				);
				
				$this->_join[] = $joinString . $extraCondition;				// adiciona a string montada na lista
			}

		} else {															// mas se nÃ£o especificou o linkName e linkTo
			// achou o relacionamento na outra entidade
			// significa que lÃ¡ tem a chave que liga aqui ou vice-e-versa
			if($opt != null)
			{
				Lumine_Log::debug('Join de '.$obj->_getName().' com '. $this->_getName().' do tipo '.$opt['type'],  __FILE__, __LINE__ );
				
				switch($opt['type'])
				{
					case self::MANY_TO_ONE:
						$res = $obj->_getField( $opt['linkOn'] );
	
						$this_alias = $this->_getAlias();
						if( empty($this_alias))
						{
							$this_alias = $this->tablename();
						}
						
						$ent_alias  = $obj->_getAlias();
						$field = $this->_getField( $opt['name'] );
						
						$joinStr = $type. " JOIN " . $obj->tablename() ." ". $ent_alias . " ON ";
						if( empty($ent_alias))
						{
							$ent_alias = $obj->tablename();
						}
						
						$joinStr .= $ent_alias . '.' . $res['column'] . ' = ';
						$joinStr .= $this_alias . '.' . $field['column'];
						
						/*
						$joinStr = $type . " JOIN {".$obj->_getName()."} ON ";
						$joinStr .= '{'.$this->_getName().'.'.$opt['name'].'} = ';
						$joinStr .= '{'.$obj->_getName().'.'.$res['name'].'}';
						*/
						
						$this->_join[] = $joinStr . $extraCondition;
	
						break;
	
					case self::ONE_TO_MANY:
						$res        = $obj->_getField( $opt['linkOn'] );
						$this_ref   = $this->_getField( $res['options']['linkOn'] );
						$obj_alias  = $obj->_getAlias();
						$this_alias = $this->_getAlias();
						
						if( empty($obj_alias))
						{
							$obj_alias = $obj->tablename();
						}
						if( empty($this_alias))
						{
							$this_alias = $this->tablename();
						}
						
						$joinStr = $type . " JOIN ".$obj->tablename().' '.$obj_alias.' ON ';
						$joinStr .= sprintf('%s.%s = %s.%s', $obj_alias, $res['column'], $this_alias, $this_ref['column']);
						$this->_join[] = $joinStr . $extraCondition;
						break;
						
					case self::MANY_TO_MANY:
						$lnk = $obj->_getRelation( $this->_getName() );
						
						$this_table = $opt['table'];
						$obj_table = $lnk['table'];
						
						if($this_table != $obj_table)
						{
							throw new Lumine_Exception('As tabelas de relacionamento devem ser iguais em '.$obj->_getName().' e '.$this->_getName(), Lumine_Exception::ERROR);
						}
						
						$schema = $this->_getConfiguration()->getOption('schema_name');
						if( !empty($schema))
						{
							$schema .= '.';
						}
	
						$this_res = $this->_getField( $opt['linkOn'] );
						$obj_res = $obj->_getField( $lnk['linkOn'] );
						
						if( empty($opt['column']) )
						{
							$mtm_column = $this_res['column'];
						} else {
							$mtm_column = $opt['column'];
						}
						
						if( empty($lnk['column']) )
						{
							$mtm_column_2 = $obj_res['column'];
						} else {
							$mtm_column_2 = $lnk['column'];
						}
						
						$alias_1 = $this->_getAlias();
						$alias_2 = $obj->_getAlias();
						
						if( empty($alias_1)) 
						{
							$alias_1 = $this->tablename();
						}
						if( empty($alias_2)) 
						{
							$alias_2 = $obj->tablename();
						}
						
						$joinStr = sprintf('%s JOIN %s ON %s.%s = %s.%s', $type, $schema.$this_table, $this_table, $mtm_column, $alias_1, $this_res['column']);
						$this->_join[] = $joinStr;
						
						$joinStr = sprintf('%s JOIN %s %s ON %s.%s = %s.%s', $type, $obj->tablename(), $alias_2, $obj_table, $mtm_column_2, $alias_2, $obj_res['column']);
						$this->_join[] = $joinStr . $extraCondition;
						break;
					
					default:
						throw new Lumine_Exception('Tipo de uniÃ£o nÃ£o encontrada: '.$opt['type'], Lumine_Exception::ERROR);
				}
			}
		}
		
		$list = $obj->_getObjectPart('_join_list');
		
		reset($this->_join_list);
		
		foreach($list as $ent)
		{
			$add = true;
			foreach($this->_join_list as $this_ent)
			{
				if($ent->_getName() == $this_ent->_getName() && $ent->_getAlias() == $this_ent->_getAlias())
				{
					$add = false;
					break;
				}
			}
			if(!$add)
			{
				continue;
			}
			
			// ok pode adicionar
			$this->_join_list[] = $ent;
			$this->_join = array_merge($this->_join, $ent->_getStrJoinList());
			
			$where = $ent->_makeWhereFromFields();
			
			if( ! empty($where))
			{
				$this->where( $where );
			}
		}
		
		/*
		foreach($list as $entity_name => $ent)
		{
			if( empty($this->_join_list[$entity_name])) 
			{
				$this->_join_list[ $entity_name ] = $ent;
				$this->_join = array_merge($this->_join, $ent->_getStrJoinList());
				
				$where = $ent->_makeWhereFromFields();
				
				if( ! empty($where))
				{
					$this->where( $where );
				}
			}
		}
		*/
		
		// $this->_join_list = array_unique($this->_join_list);
		$this->_join = array_unique($this->_join);
		
		return $this;
	}
	
     /**
      * Efetua um INSERT
      *
      * <code>
	  * $obj->nome = 'hugo';
	  * $obj->data_cadastro = time();
	  * $obj->insert();
	  * // INSERT INTO pessoas (nome, data_cadastro) VALUES ('hugo','2007-08-20');
      * </code>
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */
	public function insert()
	{
		$this->dispatchEvent('preInsert', $this);
		
		$this->savePendingObjects();
		
		$sql = $this->_getSQL( self::SQL_INSERT );
		
		if($sql === false)
		{
			return false;
		}
		
		$result = $this->_execute( $sql );
		
		// vejamos se inseriu
		if($result == true)
		{
			// vamos analisar as chaves primarias e auto-incrementÃ¡veis
			// para ver os valores e pegar do banco
			$pks = $this->_getPrimaryKeys();
			foreach($pks as $pk)
			{
				// se o valor for nulo e for um campo auto-increment
				if( is_null($this->$pk['name']) && !empty($pk['options']['autoincrement']))
				{
					// pega o ultimo ID do campo
					$valor = $this->_bridge->getLastId( $pk['column'] );
					$this->$pk['name'] = $valor;
				}
			}
			
			$this->saveDependentObjects();
			$this->dispatchEvent('posInsert', $this);
		}
		
		return $this;
	}

     /**
      * Salva / insere o objeto
      * Se a chave primÃ¡ria estiver definida, efetua um update
	  * do contrÃ¡rio, efetua um insert
      *
      * @param boolean $whereAddOnly Utilizar somente os parametros definidos com where para atualizar
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */
	public function save( $whereAddOnly = false  )
	{
		$this->dispatchEvent('preSave', $this);
		// para chamar o update, todas as chaves primarias tem que ter valor
		$pks = $this->_getPrimaryKeys();
		$all = true;
		
		// salva os objetos principais (classes extendidas)
		// $this->savePendingObjects();
		
		foreach($pks as $def)
		{
			if($this->$def['name'] == null)
			{
				$all = false;
				break;
			}
		}
		
		if($all == true)
		{
			$this->update( $whereAddOnly );
			return $this->affected_rows();
		} else {
			return $this->insert();
		}
	}
	
     /**
      * Efetua um update
      *
      * @param boolean $whereAddOnly Utilizar somente os parametros definidos com where para atualizar
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return int NÃºmero de linhas atualizadas
      */
	public function update( $whereAddOnly = false )
	{
		$this->dispatchEvent('preUpdate', $this);
		
		$this->savePendingObjects();
		
		$sql = $this->_getSQL(self::SQL_UPDATE, $whereAddOnly);
		
		if( $sql !== false )
		{
			$this->_execute($sql);
				
			$this->dispatchEvent('posUpdate', $this);
			$this->saveDependentObjects();
			
			return $this->affected_rows();
			
		} else {
			$this->saveDependentObjects();
		}
		
		return 0;
	}
	
     /**
      * Efetua um delete
      *
      * @param boolean $whereAddOnly Utilizar somente os parametros definidos com where para remover
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */
	public function delete( $whereAddOnly = false )
	{
		$this->dispatchEvent('posDelete', $this);
		
		$sql = $this->_getSQL(self::SQL_DELETE, $whereAddOnly);
		$this->_execute($sql);
		
		$this->dispatchEvent('posDelete', $this);
		
		return $sql;
	}
	
     /**
      * Adiciona a clausula LIMIT Ã  consulta
      *
      * @param int $offset Inicio dos registros ou limite se o segundo argumento for omitido
	  * @param int $limit Numero de registros a serem limitados
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base O prÃ³prio objeto
      */
	public function limit($offset = null, $limit = null)
	{
		if( empty($limit))
		{
			$this->_limit = $offset;
		} else {
			$this->_offset = $offset;
			$this->_limit = $limit;
		}
		
		return $this;
	}
	
     /**
      * Adiciona uma clausula having
      *
      * @param string $havingStr String para ser adiciona ao having. Se for nulo, limpa as clausulas
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Luminie_Base O prÃ³prio objeto
      */	
	public function having( $havingStr = null )
	{
		$args = func_get_args();

		if( gettype($havingStr) == 'NULL')
		{
			$this->_having = array();
			return $this;
		}

		if(count($args) > 1)
		{
			array_shift($args);
			$result = Lumine_Parser::parsePart($this, $havingStr, $args);
		} else {
			$result = Lumine_Parser::parsePart($this, $havingStr);
		}
		
		$this->_having[] = $result;
		return $this;
	}
	
     /**
      * Adiciona clausulas where a consulta
      * Ã possÃ­vel adicionar clausulas no modo de preparedStatment
	  * Funciona somente quando se estÃ¡ comparando com os termos abaixo:
	  * =, >=, <=, !=, <>, >, <, like, ilike, not like
      * <code>
	  * $obj = new Pessoa;
	  * $obj->_setAlias('p');
	  * $obj->where('p.nome = ? AND p.idade = ?', 'hugo', '23');
	  * $obj->find();
	  * // SELECT p.idpessoa, p.nome, p.idade, p.data_cadastro FROM pessoa WHERE p.nome = 'hugo' AND p.idade = 23
      * </code>
      * @param string $whereStr String para adicionar a clausula where
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
	  * @return Lumine_Base A prÃ³pria classe
      */
	public function where( $whereStr = null )
	{
		$args = func_get_args();
		
		if( gettype($whereStr) == 'NULL')
		{
			$this->_where = array();
			return $this;
		}

		if(count($args) > 1)
		{
			array_shift($args);
			$result = Lumine_Parser::parsePart($this, $whereStr, $args);
		} else {
			$result = Lumine_Parser::parsePart($this, $whereStr);
		}
		
		$this->_where[] = $result;
		return $this;
	}
	
     /**
      * Adiciona clausulas order by
      *
      * @param string $orderStr String para utilizar no order by
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base O prÃ³prio objeto
      */
	public function order( $orderStr = null )
	{
		if(is_null($orderStr))
		{
			$this->_order = array();
		} else {
			$list = Lumine_Tokenizer::dataSelect( $orderStr, $this );
			$this->_order = array_merge($this->_order, $list);
		}
		return $this;
	}
	
     /**
      * Adiciona clausulas de agrupamento (group by)
      *
      * @param strin $groupStr String para adicionar ao agrupamento
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base O prÃ³rio objeto
      */
	public function group( $groupStr = null )
	{
		if( !is_null($groupStr) )
		{
			$list = Lumine_Tokenizer::dataSelect( $groupStr, $this );
			$this->_group = array_merge($this->_group, $list);
		} else {
			$this->_group = array();
		}
		return $this;
	}
	
     /**
      * Seta as variaveis internas atravÃ©s de um array associativo enviado
      *
      * <code>
	  * $obj->_setFrom($_POST);
      * </code>
      * @param array $arr Array associativo contendo os valores
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base O prÃ³prio objeto
      */
	public function _setFrom( $arr )
	{
		reset($this->_definition);
		
		foreach($this->_definition as $name => $def)
		{
			if( isset($arr[$name]) )
			{
				$this->$name = $arr[$name];
			}
		}
		
		return $this;
	}

     /**
      * Recupera o nome da classe
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string Nome da classe
      */
	public function _getName()
	{
		return ucfirst(get_class($this));
	}
	
     /**
      * Recupera a definiÃ§Ã£o dos campos da classe
      * EspecÃ­fico para as colunas da tabela representada pela classe
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array Lista contendo as definiÃ§Ãµes de cada campo
      */
	public function _getDefinition()
	{
		return $this->_definition;
	}
	
     /**
      * Recupera as chaves estrangeiras da classe
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array Lista contendo as chaves estrangeiras e suas definiÃ§Ãµes
      */
	public function _getForeignRelations()
	{
		$list = $this->_foreign;

		foreach($this->_definition as $key => $def)
		{
			if( !empty($def['options']['foreign']))
			{
				$def['name'] = $key;
				$def['type'] = self::MANY_TO_ONE;
				
				foreach($def['options'] as $k => $v)
				{
					$def[$k] = $v;
				}
				unset($def['options']);
				
				$list[ $key ] = $def;
			}
			
		}
		
		return $list;
	}
	
     /**
      * Recupera um campo com um determinado nome
      *
      * @param string $name Nome do campo a ser recuperado
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array Matriz associativa contendo a definiÃ§Ã£o do campo
	  * @throws Lumine_Exception 
      */
	public function _getField( $name )
	{
		if( isset($this->_definition[ $name ]) )
		{
			$def = $this->_definition[ $name ];
			$def['name'] = $name;
			return $def;
		}
		
		if( isset($this->_foreign[ $name ]) )
		{
			$def = $this->_foreign[ $name ];
			$def['name'] = $name;
			return $def;
		}
		
		throw new Lumine_Exception('O campo '.$name.' nÃ£o foi encontrado em '.$this->_getName(), Lumine_Exception::ERROR);
	}
	
     /**
      * Recupera um campo da classe a partir de uma deternimada coluna
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array Matriz associativa contendo a definiÃ§Ã£o do campo
	  * @see _getField
      */	
	public function _getFieldByColumn( $column )
	{
		if( isset($this->_fieldsByColumn[ $column ]) )
		{
			return $this->_fieldsByColumn[ $column ];
		}
		/*
		reset($this->_definition);
		
		foreach($this->_definition as $name => $def)
		{
			if( $def['column'] == $column )
			{
				$def['name'] = $name;
				return $def;
			}
			
			if( !empty($def['column']) && $def['column'] == $column) 
			{
				$def['name'] = $name;
				return $def;
			}
		}
		*/
		
		return null;
	}

     /**
      * Altera o alias para consulta
      * Depois de alterado, vocÃª poderÃ¡ usar o alias seguido do nome do membro da classe
      * <code>
	  * $pes = new Pessoa;
	  * $pes->_setAlias('p');
	  * $pes->select('p.nome, p.idade');
	  * $pes->find();
      * </code>
      * @param string $alias Novo alias a ser usado
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Base A prÃ³pria classe
      */
	public function _setAlias($alias)
	{
		$this->_alias = $alias;
		return $this;
	}
	
     /**
      * Recupera o alias atual
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string Alias atual da classe
      */
	public function _getAlias()
	{
		return $this->_alias;
	}
	
	/**
	 * Recupera/altera o alias atual
	 *
	 * @param mixed $alias null => retorna o alias atual, false => reinicia o alias, outro valor => altera o valor do alias
	 * @return mixed Se null, retorna o alias atual, do contrÃ¡rio, o proprio objeto
	 * @author Hugo Ferreira da Silva
	 * @link http://www.hufersil.com.br/lumine
	 * @return string Alias atual da classe
	 */
	public function alias( $alias = null )
	{
		if( is_null($alias) )
		{
			return $this->_alias;
		}
		
		if( $alias === false )
		{
			$this->_alias = '';
		} else {
			$this->_alias = $alias;
		}
		return $this;
	}

     /**
      * Recupera uma determinada parte do objeto que seja privada
      * Este mÃ©todo Ã© usado mais internamente, para facilitar na manipulaÃ§Ã£o das partes privadas do objeto
      * @param string $partName Nome da parte a ser recuperada
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return mixed Parte do objeto recuperada
      */
	public function _getObjectPart($partName)
	{
		if( ! isset($this->$partName))
		{
			throw new Lumine_Exception('Parte nÃ£o encontrada: '.$partName, Lumine_Exception::ERROR);
		}
		return $this->$partName;
	}

     /**
      * Recupera o objeto de configuração atual
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return Lumine_Configuration Objeto de configuração atual
      */	
	public static function _getConfiguration()
	{
		return Lumine_ConnectionManager::getInstance()->getConfiguration(self::$package);
	}
	
     /**
      * Recupera a conexão atual
      *
      * <code>
      * </code>
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return ILumine_Connection Objeto de conexÃ£o com o banco atual
      */
	public static function _getConnection()
	{
		return self::_getConfiguration()->getConnection();
	}

     /**
      * Recupera uma lista contendo as chaves primÃ¡rias
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array Lista das chaves primÃ¡rias
      */
	public function _getPrimaryKeys()
	{
		$pks = array();
		
		reset($this->_definition);
		
		foreach($this->_definition as $name => $def)
		{
			if( !empty($def['options']['primary']) )
			{
				$def['name'] = $name;
				$pks[] = $def;
			}
		}
		
		return $pks;
	}
	
     /**
      * Recupera o tipo de SQL que serÃ¡ executada
      * VocÃª poderÃ¡ chamar este mÃ©todo para saber como estÃ¡ ficando a estrutura da consulta, por exemplo:
      * <code>
	  * $obj = new Pessoa;
	  * $obj->get(20);
	  * $obj->nome = 'hugo';
	  * $obj->idade = 23;
	  * echo $obj->_getSQL(Lumine_Base::SQL_SELECT);
	  * // SELECT pessoa.idpessoa, pessoa.nome, pessoa.idade FROM pessoa WHERE pessoa.nome = 'hugo' AND pessoa.idade = 23
	  * echo $obj->_getSQL(Lumine_Base::SQL_INSERT);
	  * // INSERT INTO pessoa (idpessoa, nome, idade) VALUES (20, 'hugo', 23)
	  * echo $obj->_getSQL(Lumine_Base::SQL_UPDATE);
	  * // UPDATE pessoa SET nome = 'hugo', idade = 23 WHERE idpessoa = 20;
	  * echo $obj->_getSQL(Lumine_Base::SQL_DELETE);
	  * // DELETE FROM pessoa WHERE idpessoa = 20
      * </code>
      * @param int $type Tipo de SQL a ser retornada
	  * @param mixed $opt OpÃ§Ãµes a serem usadas, dependendo do tipo de SQL a ser retornada
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string SQL desejada
	  * @see save
	  * @see update
	  * @see insert
	  * @see delete
      */
	public function _getSQL( $type = self::SQL_SELECT, $opt = null )
	{
		switch($type)
		{
			case self::SQL_SELECT:
				return $this->_prepareSQL();
			
			case self::SQL_SELECT_COUNT:
				return $this->_prepareSQL(true, $opt);
				
			case self::SQL_UPDATE:
				return $this->_updateSQL( $opt );
				
			case self::SQL_DELETE:
				return $this->_deleteSQL( $opt );
			
			case self::SQL_INSERT:
				return $this->_insertSQL( $opt );

			case self::SQL_MULTI_INSERT;
				return $this->_getMultiInsertSQL( $opt );
		}
		
		throw new Lumine_Exception('Tipo nÃ£o suportado: '.$type, Lumine_Exception::ERROR);
	}

     /**
      * Recupera/Altera o nome da tabela
      *
      * @param string $name Novo nome de tabela ou null para recuperar o atual
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string Nome da tabela
      */
	public function tablename( $name = null)
	{
		if($name !== null)
		{
			$this->_tablename = $name;
		}
		
		return $this->_tablename;
	}
	
     /**
      * Recupera/Altera o modo de recuperaÃ§Ã£o dos registros do banco.
      *
      * @param int $mode Modo a ser utilizado ou null para recuperar o atual
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return int Modo atual de recueperaÃ§Ã£o de registros
      */
	public function fetchMode($mode = null)
	{
		if( !empty($mode))
		{
			$this->_fetch_mode = $mode;
		}
		
		return $this->_fetch_mode;
	}
	
     /**
      * Recupera um determinado link da classe
      *
      * <code>
	  * $pes = new Pessoa;
	  * $pes->get(20);
	  * $carros = $pes->getLink('carros');
	  * foreach($carros as $carro) {
	  *     echo $carro->modelo  .'<br>';
	  * }
      * </code>
      * @param string $linkName Nome do link
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return mixed Lumine_Base para Many-to-ONE, do contrÃ¡rio, uma lista de objetos Lumine_Base
      */	
	public function _getLink($linkName)
	{
		try
		{
			$field = $this->_getField( $linkName );
			$class = empty($field['class']) ? $field['options']['class'] : $field['class'];			
			$this->_getConfiguration()->import($class);
			$obj = new $class;

			switch($field['type'])
			{
				case self::ONE_TO_MANY:
					Lumine_Log::debug('Pegando link do tipo one-to-many de '.$obj->_getName());
					$ref = $obj->_getField( $field['linkOn'] );
					$obj->$field['linkOn'] = $this->$ref['options']['linkOn'];
					$obj->find();
					$newlist = array();
					while($obj->fetch())
					{
						$new_obj = new $field['class'];
						$new_obj->_setFrom($obj->toArray());
						$list[] = $new_obj;
						$new_obj->destroy();
						unset($new_obj);
					}
					$this->$linkName = $list;
					$obj->destroy();
					unset($obj, $list);
					return $this->$linkName;
				break;
				
				case self::MANY_TO_MANY:
					Lumine_Log::debug('Pegando link do tipo many-to-many de '.$obj->_getName());
					$this->_getConfiguration()->import( $field['class'] );
					$list = new $field['class'];
					$sql = "SELECT __a.* FROM %s __a, %s __b WHERE ";
					$sql .= " __a.%s = __b.%s AND __b.%s = %s";
					$campoEstrangeiro = null;
					foreach( $list->_foreign as $item )
					{
						if( !empty($item['table']) && $item['table'] == $field['table'] )
						{
							$campoEstrangeiro = $item;
							break;
						}
					}
					if( is_null($campoEstrangeiro) == true )
					{
						throw new Exception("Deve haver relacionamento many-to-many em ambas as entidades");
					}
					$fieldlink = $list->_getField( $campoEstrangeiro['linkOn'] );
					$reffield = $this->_getField( $field['linkOn'] );
					$valor = $this->$field['linkOn'];
					$colunaUniao = $campoEstrangeiro['column'];
					$colunaLink = $fieldlink['column'];
					$colunaWhere = $field['column'];
					$tabelaUniao = $field['table'];
					$tabelaLink = $list->tablename();
					if( is_null($valor) )
					{
						return array();
					}
					$schema = $this->_getConfiguration()->getOption('schema_name');
					if( !empty($schema) )
					{
						$tabelaUniao = $schema . '.' . $tabelaUniao;
						$tabelaLink  = $schema . '.' . $tabelaLink;
					}
					$valor = Lumine_Parser::getParsedValue( $reffield, $valor, $reffield['type'] );
					$sql = sprintf($sql, $tabelaLink, $tabelaUniao, $colunaLink, $colunaUniao, $colunaWhere, $valor);
					$list->query( $sql );
					$arr_list = array();
					while($list->fetch())
					{
						$dummy = new $field['class'];
						$dummy->_setFrom($list->toArray());
						$listObj[] = $dummy;
						$dummy->destroy();
						unset($dummy);
					}
					$this->$linkName = $listObj;
                    $list->destroy();
                    $obj->destroy();
					unset($list, $obj, $listObj);
					return $this->$linkName;
				break;
				
				case self::MANY_TO_ONE:
				default:
					Lumine_Log::debug('Pegando link do tipo many-to-one de '.$obj->_getName());
					$valor = $this->$linkName;
					if(!empty($valor))
					{
						$obj->$field['options']['linkOn'] = $this->$linkName;
						$obj->find( true );
						$this->$linkName = $obj;
					}
					$obj->destroy();
					unset($obj);
					return $this->$linkName;
				break;
				
			}
			
		} catch(Lumine_Exception $e) {
			Lumine_Log::warning($e->getMessage());
		}
	}
	
     /**
      * Recupera todos os registros em formato de array
      * Cada linha do array representa uma linha de registro encontrado
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
	  * @param boolean $returnRealValues ForÃ§a o retorno dos valores reais do banco
      * @return array Todos registros em um array
      */
	public function allToArray( $returnRealValues = false )
	{
		$p = $this->_bridge->getPointer();
		$this->_bridge->moveFirst();
		
		$dataholder = $this->_dataholder;
		
		$nova = array();
		
		while($this->fetch())
		{
			$row = $this->toArray('%s', $returnRealValues);
			$nova[] = $row;
		}
		
		
		$this->_bridge->setPointer($p);
		$this->_dataholder = $dataholder;
		
		return $nova;
	}
	
     /**
      * Converte o registro atual para um array
      *
      * @author Hugo Ferreira da Silva
	  * @param boolean $returnRealValues ForÃ§a o retorno dos valores reais do banco
	  * @param String $format Formato do nome do campo para ser utilizado com sprintf
      * @link http://www.hufersil.com.br/lumine
      * @return array Array do registro atual
      */	
	public function toArray( $format = '%s', $returnRealValues = false)
	{
		$list = array();

		foreach($this->_dataholder as $key => $val)
		{
			$newkey = sprintf($format, $key);
			
			$fld = $this->_getFieldByColumn( $key );
			if( !empty($fld))
			{
				$key = $fld['name'];
			}
			if( $returnRealValues == true )
			{
				$val = $this->fieldValue( $key );
			} else {
				$val = $this->$key;
			}
			
			if(is_a($val, self::BASE_CLASS))
			{
				$list[ $newkey ] = $val->toArray( $format );

			} else if(is_array($val)) {
				foreach($val as $k => $v)
				{
					$nk = sprintf($format, $k);
					if(is_a($v, self::BASE_CLASS))
					{
						$val[ $nk ] = $v->toArray( $format );
					} else {
						$val[ $nk ] = $v;
					}
				}
				
			} else {
				$list[ $newkey ] = $val;
			}
		}

		return $list;
	}
	
     /**
      * "Escapa" uma string para inserÃ§Ã£o/consulta no banco de dados
      *
      * @param string $str String a ser "escapada"
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string Nova string com o escape correto
      */	
	public function escape( $str )
	{
		return $this->_getConnection()->escape($str);
	}
	
     /**
      * Efetua uma validaÃ§Ã£o
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return mixed True em caso de sucesso, array em caso de erros.
      */	
	public function validate()
	{
		return Lumine_Validator::validate($this);
	}

     /**
      * Reinicia as propriedades do objeto
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */
	public function reset()
	{
		$dialect = $this->_getConfiguration()->getProperty('dialect');
		$class_dialect = 'Lumine_Dialect_' . $dialect;
		$this->_join_list      = array($this);
		$this->_from           = array($this);
		$this->_bridge         = new $class_dialect( $this );
		// alias da tabela
		$this->_alias          = '';
		// partes da consulta
		$this->_data           = array();
		$this->_where          = array();
		$this->_having         = array();
		$this->_order          = array();
		$this->_group          = array();
		$this->_join           = array();
		$this->_limit          = null;
		$this->_offset         = null;
		// modo do resultado
		$this->_fetch_mode     = self::FETCH_ASSOC;
		// armazena os valores das variaveis
		$this->_dataholder     = array();
		$this->_original_dataholder = array();
		$this->_multiInsertList = array();
		$this->_formatters = array();
		// re-une as classes (caso forem extendidas)
		$this->_joinSubClasses();
	}

     /**
      * Remove objetos do banco com o determinado nome de link
      *
      * @param string $linkName Nome do link para remover os objetos do banco
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */
	public function removeAll( $linkname )
	{
		try 
		{
			$field = $this->_getField( $linkname);
			$schema = $this->_getConfiguration()->getOption("schema_name");
			if( !empty($schema))
			{
				$schema .= '.';
			}
			
			switch($field['type'])
			{
				case self::MANY_TO_MANY:
					// $val = Lumine_Parser::parseEntityValues($this,"{".$field['linkOn']."} = ?", $this->$field['linkOn']);
					// $val = Lumine_Parser::parseSQLValues($this, $val);
					
					$field_def = $this->_getField( $field['linkOn'] );

					$val = $field['column'] . "=" . Lumine_Parser::getParsedValue( $this, $this->$field['linkOn'], $field_def['type']);
					$sql = "DELETE FROM " . $schema . $field['table']. " WHERE ";
					$sql .= $val;
					
					$this->_execute($sql);
					return $this->_bridge->affected_rows();
				break;
				
				case self::ONE_TO_MANY:
					$list = $this->_getLink( $linkname );
					$total = count($list);
					
					if(is_array($list))
					{
						foreach($list as $item)
						{
							$item->delete();
						}
					}
					unset($list);
					return $total;
				break;
			}
		} catch(Exception $e) {
			Lumine_Log::warning('Link nÃ£o encontrado: '.$linkname);
		}
	}
	
	/** 
	 * Adiciona um formatador a um campo
	 * @param String $member Nome do membro da classe
	 * @param mixed $formatter FunÃ§Ã£o / array de classe e mÃ©todo para formatar o valor
	 * @return Lumine_Base O prÃ³prio objeto
	 */
	public function addFormatter( $member, $formatter )
	{
		if( !isset($this->_formatters[$member]) )
		{
			$this->_formatters[ $member ] = array();
		}
		$this->_formatters[ $member ][] = $formatter;
		return $this;
	}
	
	/**
	 * @param String $member Nome do membro da classe
	 * @param mixed $formatter FunÃ§Ã£o / array de classe e mÃ©todo para ser removido dos formatadores
	 * @return Lumine_Base O prÃ³prio objeto
	 */
	public function removeFormatter( $member, $formatter )
	{
		if( !isset($this->_formatters[$member]) )
		{
			foreach( $this->_formatters[ $member ] as $idx => $item )
			{
				if( $item === $formatter )
				{
					unset($this->_formatters[ $member ][ $idx ]);
					continue;
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Recupera o valor formatado do campo
	 * @param String $membro Nome do membro da classe
	 * @return mixed Valor formatado
	 */
	public function formattedValue( $member )
	{
		$oldvalue = $this->$member;
		$newvalue = $oldvalue;
		
		$this->dispatchEvent('preFormat', $this, $oldvalue);
		
		if( isset($this->_formatters[ $member ]) )
		{
			foreach( $this->_formatters[ $member ] as $formatter )
			{
				$newvalue = call_user_func_array( $formatter, array($newvalue) );
			}
		}
		
		$this->dispatchEvent('posFormat', $this, $oldvalue, $newvalue);
		
		return $newvalue;
	}
	
	/**
	 * Recupera o valor real do campo (sem formatar)
	 */
	public function fieldValue( $key )
	{
		try
		{
			if( !isset($this->_dataholder[ $key ]) )
			{
				return null;
			}
			
			if( gettype($this->_dataholder[ $key ]) == 'NULL')
			{
				return null;
			}
			
			$res = $this->_getField($key);
			if( ! empty($res['options']['format']) )
			{
				switch( $res['type'] )
				{
					case 'int':
					case 'integer':
					case 'float':
					case 'double':
						return sprintf( $res['options']['format'], $this->_dataholder[ $key ]);
						break;

					case 'date':
					case 'datetime':
						return strftime($res['options']['format'], strtotime($this->_dataholder[ $key ]));
						break;
				}
			}
			
			if( !empty( $res['options']['formatter'] ) )
			{
				return call_user_func_array( $res['options']['formatter'], array($this->_dataholder[ $key ]) );
			}
			
			return $this->_dataholder[ $key ];
			
		} catch (Exception $e) {
			// Lumine_Log::warning( 'Campo nÃ£o encontrado: '.$key);
			
			// se encontrar, retorna o que encontrou
			if( isset($this->_dataholder[ $key ]))
			{
				return $this->_dataholder[ $key ];
			}
				
			return null;
		}
	}
	
	//////////////////////////////////////////////////////////////////
	// mÃ©todos depreciados, existem por questÃµes de compatibilidade //
	//////////////////////////////////////////////////////////////////
    /**
     * @see group
     * @deprecated
     */	
	public function groupBy( $groupStr )
	{
		Lumine_Log::debug( "Depreciado, use group");
		return $this->group( $groupStr );
	}
	
    /**
     * @see order
     * @deprecated
     */
	public function orderBy( $orderStr )
	{
		Lumine_Log::debug( "Depreciado, use order");
		return $this->order( $orderStr );
	}

    /**
     * @see where
     * @deprecated
     */
	public function whereAdd( $whereStr )
	{
		Lumine_Log::debug( "Depreciado, use where");
		$args = func_get_args();
		return call_user_func_array( array($this,'where'), $args );
	}

    /**
     * @see join
     */
	public function joinAdd( Lumine_Base $obj, $type = 'INNER', $alias = '', $linkName = null, $linkTo = null )
	{
		Lumine_Log::debug( "Depreciado, use join");
		return $this->join($obj, $type, $alias, $linkName, $linkTo);
	}

    /**
     * @see select
     */
	public function selectAdd( $data )
	{
		Lumine_Log::debug( "Depreciado, use select");
		return $this->select( $data );
	}

    /**
     * @see _setFrom
	 * @deprecated
     */
	public function setFrom($arr)
	{
		Lumine_Log::debug( "Por questÃµes de compatibilidade, use _setFrom");
		return $this->_setFrom($arr);
	}
	
    /**
     * @see _getLink
	 * @deprecated
     */
	public function getLink($linkName)
	{
		Lumine_Log::debug( "Por questÃµes de compatibilidade, use _getLink");
		return $this->_getLink($linkName);
	}
	
	/**
	 * Executa uma query definida pelo usuÃ¡rio
	 * @author Hugo Ferreira da Silva
	 * @param string $sql Comando SQL a ser executado
	 * @return int NÃºmero de registros encontrados / afetados
	 */
	public function query( $sql )
	{
		$this->dispatchEvent( 'preQuery', $this, $sql );
		$rs = $this->_execute( $sql );
		$this->dispatchEvent( 'posQuery', $this, $sql );
		
		return $this->numrows();
	}
	
	
	/**
	 * Adiciona dados a uma lista de multi-insert
	 * @return void
	 * @author Hugo Ferreira da Silva
	 */
	public function addMultiInsertItem()
	{
		$sql = $this->_getSQL( self::SQL_INSERT );
		$sql = preg_replace('@^INSERT\s*INTO.*?\(.+?\)\s*VALUES\s*\((.+?)\)$@', '($1)', $sql);

		$this->_multiInsertList[] = $sql;
	}
	
	/**
	 * Efetua um comando de multi-inserÃ§Ã£o
	 * Ex: INSERT INTO tabela (campo1, campo2, campo3) VALUES (...,...,...), (...,...,...), (...,...,...)
	 * @param boolean $ignoreAutoIncrement NÃ£o inclui campos auto-incrementÃ¡veis no insert
	 * @author Hugo Ferreira da Silva
	 */
	public function multiInsert( $ignoreAutoIncrement = true )
	{
		
		$sql = $this->_getMultiInsertSQL( $ignoreAutoIncrement );
		
		if( $sql == false )
		{
			return false;
		}
		
		$this->dispatchEvent('onPreMultiInsert', $this, $sql);									// dispara o pre-evento
		
		$this->_execute( $sql );																// executa a inserÃ§Ã£o
		
		$this->dispatchEvent('onPosMultiInsert', $this, $sql);									// dispara o pos evento
		return true;																			// retorna true
	}

	//----------------------------------------------------------------------//
	// MÃ©todos protegidos                                                   //
	//----------------------------------------------------------------------//	

     /**
      * InicializaÃ§Ã£o da classe, chamada no construtor
      * Aqui serÃ£o adicionadas as chamadas para adicionar as propriedades da classe
      * para mapeamento.
      * 
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */	
	protected function _initialize()
	{
	}
	
	/**
	 * Efetua um comando de multi-inserÃ§Ã£o
	 * Ex: INSERT INTO tabela (campo1, campo2, campo3) VALUES (...,...,...), (...,...,...), (...,...,...)
	 * @param boolean $ignoreAutoIncrement NÃ£o inclui campos auto-incrementÃ¡veis no insert
	 * @author Hugo Ferreira da Silva
	 */
	protected function _getMultiInsertSQL($ignoreAutoIncrement = true)
	{
		if( empty($this->_multiInsertList) )													// se nÃ£o hÃ¡ itens na lista
		{
			Lumine_Log::warning('NÃ£o hÃ¡ itens para inserir com o MULTI-INSERT');				// envia um alerta no log
			return false;																		// retorna falso
		}
		
		Lumine_Log::warning('Iniciando multi-insert');											// log informa que iniciou
		
		$schema = $this->_getConfiguration()->getOption('schema_name');							// tenta pega o nome do schema
		
		if( !empty($schema) )																	// se foi informado o schema
		{
			$schema .= '.';																		// adiciona um ponto como separador
		}
		
		$columns = array();																		// lista das colunas da tabela
		reset( $this->_definition );															// reinicia a definiÃ§Ã£o
		
		foreach( $this->_definition as $name => $prop )											// para cada item da definiÃ§Ã£o
		{
			if( !empty($prop['options']['autoincrement']) && $ignoreAutoIncrement == true )  	// se for auto-inc. e for para igonrar
			{
				continue;																		// pula este campo
			}
			$columns[] = $prop['column'];														// adiciona o campo na lista de inserÃ§Ã£o
		}
		
		reset( $this->_definition );															// reinicia a definiÃ§Ã£o
		
		$sql = "INSERT INTO " . $schema.$this->tablename() ;									// monta a consulta
		$sql .= '(' . implode(', ', $columns) . ')';											// adiciona os nomes dos campos
		$sql .= " VALUES ";
		$sql .= implode(', '.PHP_EOL, $this->_multiInsertList);									// adiciona os valores
		
		return $sql;
	}

     /**
      * Adiciona um campo Ã  classe para fazer relacionamento Ã  coluna da tabela
      *
      * @param string $name Nome do membro da classe que mapeia a coluna
	  * @param string $column Nome da coluna na tabela
	  * @param string $type Tipo de dados no banco
	  * @param int $length Comprimento do campo no banco de dados
	  * @param array $options OpÃ§Ãµes do campo
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */	
	protected function _addField($name, $column, $type, $length, array $options)
	{
		if( ! isset($this->_definition[$name]) )
		{
			$this->_definition[ $name ]['column']  = $column;
			$this->_definition[ $name ]['type']    = $type;
			$this->_definition[ $name ]['length']  = $length;
			$this->_definition[ $name ]['options'] = $options;
			
			if(isset($options['primary']) && $options['primary'] == true)
			{
				$this->_definition[ $name ]['primary']  = true;
			}
		} else {
			throw new Lumine_Exception('Uma classe nÃ£o pode conter campos duplicados ('.$name.').', Lumine_Exception::ERROR);
		}
		
		
		if( ! isset($this->_fieldsByColumn[$column]) )
		{
			$this->_fieldsByColumn[ $column ]['column']  = $column;
			$this->_fieldsByColumn[ $column ]['name']  = $name;
			$this->_fieldsByColumn[ $column ]['type']    = $type;
			$this->_fieldsByColumn[ $column ]['length']  = $length;
			$this->_fieldsByColumn[ $column ]['options'] = $options;
			
			if(isset($options['primary']) && $options['primary'] == true)
			{
				$this->_fieldsByColumn[ $column ]['primary']  = true;
			}
		} else {
			throw new Lumine_Exception('Uma classe nÃ£o pode conter colunas duplicadas ('.$column.').', Lumine_Exception::ERROR);
		}
	}
	
     /**
      * Adiciona uma mapeamento de chave estrangeira
      *
      * @param string $name Nome do relacionamento
	  * @param int $type Tipo do relacionamento
	  * @param string $class Nome da classe que serÃ¡ relacionada
	  * @param string $linkOn Nome do campo da entidade que serÃ¡ referenciada
	  * @param string $table Nome da tabela de relacionamentos many-to-many
	  * @param string $column Nome da coluna da tabela mtm que referencia a chave primÃ¡ria da classe atual
	  * @param boolean $lazy Carrega os valores dos relacionamentos assim que que carregado o valor atual da classe chamadora
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */	
	protected function _addForeignRelation($name, $type, $class, $linkOn, $table = null, $column = null, $lazy = false)
	{
		if( ! isset($this->_foreign[ $name ]) )
		{
			switch( $type )
			{
				case self::ONE_TO_MANY:
					$this->_foreign[ $name ]['type']    = $type;
					$this->_foreign[ $name ]['class']   = $class;
					$this->_foreign[ $name ]['linkOn']  = $linkOn;
					$this->_foreign[ $name ]['lazy']    = $lazy;
					break;

				case self::MANY_TO_MANY:
					$this->_foreign[ $name ]['type']    = $type;
					$this->_foreign[ $name ]['class']   = $class;
					$this->_foreign[ $name ]['linkOn']  = $linkOn;
					$this->_foreign[ $name ]['table']   = $table;
					$this->_foreign[ $name ]['column']  = $column;
					$this->_foreign[ $name ]['lazy']    = $lazy;
					break;
				default:
					throw new Lumine_Exception('Tipo nÃ£o suportado:'.$type, Lumine_Exception::ERROR);
			}
		} else {
			throw new Lumine_Exception('Uma classe nÃ£o pode conter campos duplicados ('.$name.').', Lumine_Exception::ERROR);
		}
	}
	
     /**
      * Recupera um relacionamento pelo nome da classe
      *
      * @param $entityName Nome da entidade que serÃ¡ recuperada
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array DefiniÃ§Ã£o do relacionamento
      */	
	protected function _getRelation( $entityName )
	{
		foreach($this->_definition as $name => $prop)
		{
			if( ! empty($prop['options']['foreign']) && ! empty($prop['options']['class']) && $prop['options']['class'] == $entityName )
			{
				$opt = $prop['options'];
				$opt['name'] = $name;
				$opt['type'] = self::MANY_TO_ONE;
				return $opt;
			}
		}
		
		foreach($this->_foreign as $name => $options)
		{
			if( $options['class'] == $entityName )
			{
				$opt = $options;
				$opt['name'] = $name;
				return $opt;
			}
		}
		
		return null;
	}
	
     /**
      * Recupera a lista de entidades relacionadas com a classe atual
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array Lista de elementos Lumine_Base relacionados a classe atual
      */	
	protected function _getJoinList()
	{
		return $this->_join_list;
	}
	
     /**
      * Retorna a respresentaÃ§Ã£o de uniÃ£o de classes em string
      *
      * @param
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return array Lista contendo strings dos relacionamentos relacionados a classe
      */	
	protected function _getStrJoinList()
	{
		return $this->_join;
	}

     /**
      * Monta clausulas where a partir das propriedades da classe 
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string String contendo as condiÃ§Ãµes montadas a partir dos campos
      */
	protected function _makeWhereFromFields()
	{
		$where = array();
		foreach($this->_definition as $name => $def)
		{
			$val = $this->$name;
			if( gettype($val) == 'NULL' )
			{
				continue;
			}
			
			$str = sprintf('{%s.%s} = ?', $this->_getName(), $name);
			$where[] = Lumine_Parser::parsePart($this, $str, array($val));
		}
		
		$str_where = implode(' AND ', $where);
		$str_where = $str_where;
		
		return $str_where;
	}
	
     /**
      * Prepara uma SQL para efetuar uma consutla (SELECT)
      *
	  * <code>
	  * $total = $obj->count();
	  * $total_distinct = $obj->count('distinct nome');
	  * </code>
      * @param boolean $forCount Define serÃ¡ uma consulta para contagem ou nÃ£o
	  * @param string $what String contendo lÃ³gica para contagem
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return int NÃºmero de registros encontrados
      */
	protected function _prepareSQL($forCount = false, $what = '*')
	{
		$sql = "SELECT ";
		if( $forCount == false )
		{
			if( empty($this->_data) )
			{
				reset($this->_join_list);
				
				foreach($this->_join_list as $ent)
				{
					$this->selectAs($ent);
				}
			}
			
			$sql .= Lumine_Parser::parseSQLValues( $this, implode(', ', $this->_data));
		}
		
		if($forCount == true && !empty($what)) 
		{
			$sql .= ' count('.Lumine_Parser::parseSQLValues( $this, $what).') as "lumine_count" ';
		}
		
		$sql .= PHP_EOL." FROM "; 
		$list = array();
		
		reset($this->_from);
		
		foreach($this->_from as $obj)
		{
			$list[] = Lumine_Parser::parseFromValue( $obj );
		}
		$sql .= implode(', ', $list);
		
		if(count($this->_join_list) > 1) 
		{
			$sql .= Lumine_Parser::parseJoinValues( $this, $this->_join_list);
		}
		
		$where = $this->_makeWhereFromFields();
		
		if( !empty($this->_where))
		{
			if( ! empty($where) )
			{
				$where .= " AND ";
			}
			$where .= implode(" AND ", $this->_where);
		}
		
		if( !empty($where) )
		{
			$sql .= PHP_EOL . " WHERE ". Lumine_Parser::parseSQLValues( $this, $where);
		}
		
		if( !empty($this->_group) )
		{
			$sql .= PHP_EOL . " GROUP BY " . Lumine_Parser::parseSQLValues( $this, implode(', ', $this->_group));
		}

		if( !empty($this->_having) )
		{
			$sql .= PHP_EOL . " HAVING " . Lumine_Parser::parseSQLValues( $this, implode(' AND ', $this->_having));
		}
		
		if( !empty($this->_order) )
		{
			$sql .= PHP_EOL  ." ORDER BY " . Lumine_Parser::parseSQLValues( $this, implode(', ', $this->_order));
		}
		
		$sql .= PHP_EOL . $this->_getConnection()->setLimit($this->_offset, $this->_limit);
		
		return $sql;
	}

     /**
      * Prepara um SQL para inserÃ§Ã£o (INSERT)
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string String pronta para efetuar um INSERT
      */
	protected function _insertSQL( $opt = null )
	{
		$columns = array();
		$values  = array();
		$schema  = $this->_getConnection()->getOption('schema_name');
		$empty_as_null = $this->_getConfiguration()->getOption('empty_as_null');
		
		if( !empty($schema))
		{
			$schema .= '.';
		}
		
		reset($this->_definition);
		
		foreach($this->_definition as $name => $def)
		{
			if( array_key_exists($name, $this->_dataholder))
			{
				$val = $this->getStrictValue( $name, $this->_dataholder[ $name ] );
				$columns[] = $def['column'];
				
				if( !is_a($val, self::BASE_CLASS) )
				{
					if($val === '' && !empty($empty_as_null))
					{
						$values[] = 'NULL';
						
					} else if($val === null) {
						$values[] = 'NULL';
						
					} else {
						$values[] = Lumine_Parser::getParsedValue($this, $this->_dataholder[ $name ], $def['type']);
					}
				} else {
					//print_r($def);
					$values[] = Lumine_Parser::getParsedValue($this, $val->$def['linkOn'], $def['type']);
				}
				continue;
			} 
			
			if( array_key_exists('default', $def['options']) && empty($this->$name))
			{
				$this->$name = $def['options']['default'];
				$columns[] = $def['column'];
				$values[]  = Lumine_Parser::getParsedValue($this, $this->_dataholder[ $name ], $def['type']);
				continue;
			}
			
			if( !empty($def['options']['autoincrement']) )
			{
				$sequence_type = empty($def['option']['sequence_type']) ? '' : $def['option']['sequence_type'];
				// se nÃ£o estiver definida na entidade, tenta pegar a padrÃ£o para todo o banco
				$st = $this->_getConnection()->getOption('sequence_type');
				if( !empty($st))
				{
					$sequence_type = $st;
				}
				
				switch($sequence_type)
				{
					case Lumine_Sequence::COUNT_TABLE:
					break;
					
					case Lumine_Sequence::SEQUENCE:
					break;
					
					case Lumine_Sequence::NATURAL:
					default:
						// se for natural do banco
						// nÃ£o faz nada, nem insere na lista de inserÃ§Ã£o
						// o banco que se vire em pegar o padrÃ£o
					break;
				}
				
				continue;
			}
		} 
		
		if(empty($columns))
		{
			Lumine_Log::warning('Sem valores para inserir');
			return false;
		}
		
		$sql = "INSERT INTO " . $schema . $this->tablename() . "(";
		$sql .= implode(', ', $columns) . ") VALUES (";
		$sql .= implode(', ', $values) . ")";
		
		return $sql;
	}

     /**
      * Prepara um SQL para atualizaÃ§Ã£o (UPDATE)
      *
      * @param boolean $whereAddOnly Prepara o SQL somente com os parametros definidos com where
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return SQL montada para atualizaÃ§Ã£o
      */
	protected function _updateSQL( $whereAddOnly = false )
	{

		$fields = array();
		$values = array();
		$where  = array();
		$a = $this->_getAlias();

		if( !empty($a))
		{
			$a .= '.';
		}
		
		reset($this->_definition);
		
		foreach($this->_definition as $name => $def)
		{
			if( array_key_exists($name, $this->_dataholder))
			{
			    
				$valor = $this->_dataholder[ $name ]; 			// pega o valor da linha
				
				// se este campo existir no DataHolder original e o valor for o mesmo
				if( isset($this->_original_dataholder[ $name ]) && $this->_original_dataholder[ $name ] == $valor )
				{
					// nÃ£o coloca na lista de atualizaÃ§Ã£o
					continue;
				}
				
				$fields[] = $a . $def['column'];
				// $values[] = Lumine_Parser::getParsedValue($this, $this->_dataholder[ $name ], $def['type']);
				$val = $this->getStrictValue( $name, $this->_dataholder[ $name ] );
				$columns[] = $def['column'];
				
				if( !is_a($val, self::BASE_CLASS) )
				{
					if($val === '' && !empty($empty_as_null))
					{
						$values[] = 'NULL';
						
					} else if($val === null) {
						$values[] = 'NULL';
						
					} else {
						$values[] = Lumine_Parser::getParsedValue($this, $this->_dataholder[ $name ], $def['type']);
					}
				} else {
					$values[] = Lumine_Parser::getParsedValue($this, $val->$def['linkOn'], $def['type']);
				}
				
				continue;
			}
		}
		
		if( empty($values) )
		{
			Lumine_Log::warning('NÃ£o foram encontradas alteraÃ§Ãµes para realizar o udpate');
			return false;
		}
		
		$where_str = '';
		
		if( $whereAddOnly == true )
		{
			$where_str = Lumine_Parser::parseSQLValues($this, implode(' AND ', $this->_where));
		} else {
			$pks = $this->_getPrimaryKeys();
			
			foreach($pks as $id => $def)
			{
				$name = $def['name'];
				
				if( !empty($this->_dataholder[ $name ]))
				{
					$where[] = $a . $def['column'] . ' = ' . Lumine_Parser::getParsedValue($this, $this->_dataholder[ $name ], $def['type']);
				}
			}
			
			$where_str = implode(' AND ', $where);
		}
		
		if( empty($where_str)) 
		{
			throw new Lumine_Exception('NÃ£o Ã© possÃ­vel atualizar sem definiÃ§Ã£o de chaves ou argumentos WHERE', Lumine_Exception::ERROR);
		}
		
		$table = $this->tablename();
		$schema = $this->_getConfiguration()->getOption('schema_name');
		if( !empty($schema))
		{
			$table = $schema.'.'.$table;
		}

		$sql = "UPDATE ".$table." " . $this->_getAlias() . " SET ";
		$valores = array();
		
		for($i=0; $i<count($fields); $i++)
		{
			$valores[] = $fields[$i] .' = '. $values[$i];
		}
		
		$sql .= implode(', ', $valores);
		$sql .= " WHERE ". $where_str;
		
		return $sql;
		
	}

     /**
      * Prepara um SQL para DELETE
      *
      * @param boolean $whereAddOnly Prepara o SQL somente com os parametros definidos com where
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return string SQL preparado para DELETE
      */
	protected function _deleteSQL( $whereAddOnly = false )
	{
		$fields = array();
		$values = array();
		$where  = array();
		$a = $this->_getAlias();
		$this->_setAlias('');

		$where_str = '';
		
		if( $whereAddOnly == true )
		{
			$where_str = Lumine_Parser::parseSQLValues($this, implode(' AND ', $this->_where));
		} else {
			$pks = $this->_getPrimaryKeys();
			
			foreach($pks as $id => $def)
			{
				$name = $def['name'];
				
				if($this->$name !== null)
				{
					$where[] = $def['column'] . ' = ' . Lumine_Parser::getParsedValue($this, $this->$name, $def['type']);
				}
			}
			
			$where_str = implode(' AND ', $where);
		}
		
		$this->_setAlias($a);
		
		if( empty($where_str)) 
		{
			throw new Lumine_Exception('NÃ£o Ã© possÃ­vel remover sem definiÃ§Ã£o de chaves ou argumentos WHERE', Lumine_Exception::ERROR);
		}

		$table = $this->tablename();
		$schema = $this->_getConfiguration()->getOption('schema_name');
		if( !empty($schema))
		{
			$table = $schema.'.'.$table;
		}

		$sql = "DELETE FROM ".$table." ";
		$sql .= " WHERE ". $where_str;
		
		return $sql;
	}

    /**
     * Salva os objetos determinantes para a inserÃ§Ã£o ou atualizaÃ§Ã£o do atual (Classe extendida)
     *
     * @author Hugo Ferreira da Silva
     * @link http://www.hufersil.com.br/lumine
     */
	protected function savePendingObjects()
	{
		// faremos uma iteraÃ§Ã£o nos membros da classe,
		// procurando itens que sejam chaves estrangeiras
		// Menos MTM e OTM
		
		reset( $this->_definition );
		
		foreach( $this->_definition as $name => $prop )
		{
			// para funciona corretamente, tem que ser
			// - chave estrangeira;
			// - chave primaria;
			// - esta classe deve estender a outra.

			if( !empty($prop['options']['primary']) && !empty($prop['options']['foreign']) && !empty($prop['options']['class']) )
			{
				Lumine_Log::debug('Classe pai: "' . $prop['options']['class'] . '"');
				// verifica se esta classe extende a outra
				$class = get_parent_class( $this );
				
				if( strtolower($class) == strtolower($prop['options']['class']) )
				{
					Lumine_Log::debug('Instanciando classe: "' . $prop['options']['class'] . '"');
					// instancia o objeto
					$obj = new $prop['options']['class'];
					// verifica se o objeto que estÃ¡ chamando tem o valor do objeto pai
					$chave = $this->$name;
					
					// se tiver um valor
					if( !is_null( $chave ) )
					{

						// dÃ¡ um GET primeiro
						$total = $obj->get( $chave );
						
						// pega os valores e coloca na classe
						$list = $this->toArray();
						foreach( $list as $chave => $valor )
						{
							$obj->$chave = $valor;
						}
						// se nÃ£o encontrou
						if( $total == 0 )
						{
							// insere
							$obj->insert();
						} else { // se achou
							// atualiza
							$obj->save();
						}
						
						// coloca o valor no campo apropriado da classe chamadora
						$this->$name = $obj->$prop['options']['linkOn'];

					} else { // se nÃ£o tiver um valor
						Lumine_Log::debug('Valor nÃ£o encontrado para: "' . $obj->_getName(). '" com o nome de campo ' . $name . '-> '.$chave);

						$list = $this->toArray();
						foreach( $list as $chave => $valor )
						{
							$obj->$chave = $valor;
						}
						$obj->insert();
						$this->$name = $obj->$prop['options']['linkOn'];
					}
				}
			}
		}
	}

     /**
      * Salva os objetos vinculados a este que dependem deste 
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */	
	protected function saveDependentObjects()
	{
		reset($this->_foreign);
		
		$schema = $this->_getConfiguration()->getOption('schema_name');
		if( !empty($schema))
		{
			$schema .= '.';
		}
		
		foreach($this->_foreign as $name => $def)
		{
			switch($def['type'])
			{
				case self::ONE_TO_MANY:
					$list = $this->$name;
					
					if( !empty($list) && is_array($list))
					{
						foreach($list as $val)
						{
			
							if(is_a($val, self::BASE_CLASS))
							{
								$relname = $this->_getName();
								
								try {
									$field = $val->_getRelation($relname);
									$val->$field['name'] = $this->$field['linkOn'];
									$val->save();
									
								} catch (Lumine_Exception $e) {
									Lumine_log::warning('NÃ£o foi possÃ­vel encontrar o campo '.$relname.' em '.$val->_getName());
								}
							}
						}
					}
					
				break;
				
				case self::MANY_TO_MANY:
					$list = $this->$name;
					
					if( !empty($list) && is_array($list))
					{
						foreach($list as $val)
						{
							// se for uma instancia de Lumine_Base
							if(is_a($val, self::BASE_CLASS))
							{
								// pega o valor da chave primaria
								$f1 = $this->_getField( $def['linkOn'] );
								$v1 = $this->$def['linkOn'];
								
								// salva o objeto
								$val->save();
								
								// valor do outro objeto
								$rel = $val->_getRelation( $this->_getName() );
								
								$f2 = $val->_getField( $rel['linkOn'] );
								$v2 = $val->$f2['name'];
								
								// se ambos nÃ£o forem nulos
								if( !is_null($v1) && !is_null($v2))
								{
									// verifica se jÃ¡ existe
									$sv1 = Lumine_Parser::getParsedValue($this, $v1, $f1['type']);
									$sv2 = Lumine_Parser::getParsedValue($val, $v2, $f2['type']);
									
									$sql = "SELECT * FROM ".$schema.$def['table']. " WHERE ";
									$sql .= $f1['column'].'='.$sv1;
									$sql .= ' AND ';
									$sql .= $f2['column'].'='.$sv2;
									
									$clname = 'Lumine_Dialect_' . $this->_getConfiguration()->getProperty('dialect');
									
									Lumine_Log::debug('Verificando existencia da referencia do objeto no banco: '.$sql);
									$ponte = new $clname( $this );
									$ponte->execute($sql);
									
									// se nÃ£o existir
									if($ponte->num_rows() == 0)
									{
										// insert
										$sql = "INSERT INTO " . $schema . $def['table'] . "(%s, %s) VALUES (%s, %s)";
										$sql = sprintf($sql, $f1['column'], $f2['column'], $sv1, $sv2);
										
										$ponte->execute($sql);
									}
								}
								
							} else {
								// pega o valor do campo desta classe
								$campo = $this->_getField($def['linkOn']);
								
								$valor_pk = $this->$campo['name'];
								
								// se este objeto tem um valor no campo indicado
								if( !is_null($valor_pk))
								{
									// primeiro vemos se este valor jÃ¡ nÃ£o existe
									$sql = "SELECT * FROM " . $schema . $def['table'] . " WHERE ";
									
									// pega o valor do campo desta entidade
									$valor_objeto = Lumine_Parser::getParsedValue($this, $valor_pk, $campo['type']);
									
									// instanciamos a classe estrangeira
									$this->_getConfiguration()->import( $def['class'] );
									
									$obj = new $def['class'];
									// pega o relacionamento com esta entidade
									$rel = $obj->_getRelation( $this->_getName() );
									$rel_def = $obj->_getField( $rel['linkOn'] );
									
									// ajusta o valor
									$valor_estrangeiro = Lumine_Parser::getParsedValue($obj, $val, $rel_def['type']);
									
									// termina a SQL
									$sql .= $campo['column'] .'=' . $valor_objeto;
									$sql .= " AND ";
									$sql .= $rel['column'] .'=' . $valor_estrangeiro;
									
									// ponte alternativa
									$name = 'Lumine_Dialect_'.$this->_getConfiguration()->getProperty('dialect');
									$ponte = new $name( $this );
									$ponte->execute($sql);
									$res = $ponte->num_rows();
									
									// se nÃ£o encontrou
									if($res == 0)
									{
										// insere
										$sql = "INSERT INTO %s (%s,%s) VALUES (%s,%s)";
										$sql = sprintf($sql, $schema . $def['table'], $campo['column'], $rel_def['column'], $valor_objeto, $valor_estrangeiro);
										
										Lumine_Log::debug("Inserindo valor Many-To-Many: " .$sql);
										$ponte->execute($sql);
									}
								} else {
									Lumine_Log::warning('A o campo "'.$pks[0]['name'].' da classe "'.$this->_getName().'" nÃ£o possui um valor');
								}
							}
						}
					}
				break;
			}
		}
	}

     /**
      * Carrega os objetos que sÃ£o "preguiÃ§osos" (LAZY)
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */
	protected function loadLazy( $except = null )
	{
		reset($this->_definition);
		foreach($this->_definition as $name => $def)
		{
			if($except != null && !empty($def['options']['class']) && $def['options']['class'] == $except)
			{
				continue;
			}
			
			if( !empty($def['options']['lazy']) && !empty($def['options']['class']))
			{
				$this->_getLink( $name );
			}
		}
		
		reset($this->_foreign);
		foreach($this->_foreign as $name => $def)
		{
			if($except != null && !empty($def['class']) && $def['class'] == $except)
			{
				continue;
			}
			if( !empty($def['lazy']) && !empty($def['class']))
			{
				$this->_getLink( $name );
			}
		}
	}

	//----------------------------------------------------------------------//
	// MÃ©todos privados                                                     //
	//----------------------------------------------------------------------//
	
	private function __set( $key, $val )
	{
		if( isset($this->_dataholder) )
		{
			$this->_dataholder[ $key ] = $val;
		}
	}
	
	private function getStrictValue($key, $val)
	{
		try
		{
			$res = $this->_getField( $key );
			
			if(is_a($val, self::BASE_CLASS) || gettype($val) == 'NULL')
			{
				//$this->_dataholder[ $key ] = $val;
				return $val;
			}

			switch($res['type'])
			{
				case 'int':
				case 'integer':
				case 'boolean':
				case 'bool':
					$val = sprintf('%d', $val);
					
					break;
				
				case 'float':
				case 'double':
					$val = sprintf('%f', $val);
					
					break;
				
				case 'datetime':
					$val = Lumine_Util::FormatDateTime( $val );
					break;
				
				case 'date':
					if(preg_match('@^(\d{2})/(\d{2})/(\d{4})$@', $val, $reg))
					{
						if(checkdate($reg[2], $reg[1], $reg[3]))
						{
							$val = "$reg[3]-$reg[2]-$reg[1]";
						} else {
							$val = "$reg[3]-$reg[1]-$reg[2]";
						}
					} else if(preg_match('@^(\d{4})-(\d{2})-(\d{2})$@', $val, $reg)) {
						$val = $val;
					} else if(is_numeric($val)) {
						$val = date('Y-m-d', $val);
					} else {
						$val = date('Y-m-d', strtotime($val));
					}
					break;
			}

			// $this->_dataholder[ $key ] = $val;
			return $val;

		} catch(Exception $e) {
			// $this->_dataholder[ $key ] = $val;
			return $val;
		}
	}
	
	private function __get($key)
	{
		if( $this->_getConfiguration()->getOption('use_formatter_as_default') == true )
		{
			return $this->formattedValue( $key );
		} else {
			return $this->fieldValue( $key );
		}
	}
	
     /**
      * Eftua a conexÃ£o com o banco de dados.
      *
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      */
	protected function connect()
	{
		$this->_getConnection()->connect();
	}
	
     /**
      * Executa uma SQL no banco
      *
      * @param string $sql SQL a ser executada
      * @author Hugo Ferreira da Silva
      * @link http://www.hufersil.com.br/lumine
      * @return boolean True em caso de sucesso, false em caso de falhas.
      */	
	protected function _execute($sql)
	{
		$this->dispatchEvent('preExecute', $this, $sql);
		$this->connect();
		$result = $this->_bridge->execute($sql);
		$this->dispatchEvent('posExecute', $this, $sql);

		return $result;
		
	}
	
	protected function _getFromParent( $key )
	{
		try {
			$super = $this->_getParentClass();
			
			if( is_null($super) )
			{
				throw new Exception('Super-Classe nÃ£o encontrada para ' . $this->_getName());
			}
			
			$field = $super->_getField( $key );
//			return $super->
			
		} catch (Exception $e) {
			throw new Exception('Campo nÃ£o encontrado');
		}
	}
	
	protected function _getParentClass()
	{
		$super = get_parent_class( $this );
		if( $super == 'Lumine_Base' )
		{
			return null;
		}
		
		$instance = new $super;
		return $instance;
	}
	
	/**
	 * une classes para realizar consultas
	 * @return void
	 * @author Hugo Ferreira da Silva
	 */
	protected function _joinSubClasses()
	{
		// lista de classes antecessoras a esta
		$classes = array();
		// pesquisa as classes "pais"
		for ($class = get_class($this); $class = get_parent_class ($class); $classes[] = $class)
		{
			// se nÃ£o for Lumine_Base
			if( $class == 'Lumine_Base' )
			{
				// pÃ¡ra a iteraÃ§Ã£o
				break;
			}
		}
		// se nÃ£o encontrou nenhuma
		if( empty($classes) )
		{
			// sai da rotina
			return;
		}
		
		// lista de objetos
		$lista_objetos = array( $this );
		
		// cria um objeto para cada classe encontrada
		foreach( $classes as $classname )
		{
			$obj = new $classname;
			$lista_objetos[] = $obj;
		}
		
		// percorre a lista de tras pra frente
		for($i=count($lista_objetos)-1; $i>=0; $i--)
		{
			// se nÃ£o for o primeiro objeto, 
			if( isset($lista_objetos[ $i - 1 ]) )
			{
				// une com o objeto anterior
				$lista_objetos[ $i - 1 ]->join( $lista_objetos[ $i ] );
			}
		}
		
	}
}


?>
