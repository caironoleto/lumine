<?php

/**
 * Classe para gerar as entidades através da engenharia reversa
 *
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br/lumine
 */

class Lumine_Reverse_ClassTemplate 
{

    /**
     * armazena as chaves estrangeiras
     *
     * @var array
     */
    private $foreign      = array();
    /**
     * nome da classe
     *
     * @var string
     */
    private $classname;
    /**
     * nome da tabela
     *
     * @var string
     */
    private $tablename;
    /**
     * nome do pacote
     *
     * @var sting
     */
    private $package;
    /**
     * descrição da classe (campos)
     *
     * @var array
     */
    private $description  = array();
    
    /**
     * relacionamentos do tipo um-para-muitos
     *
     * @var array
     */
    private $one_to_many  = array();
    /**
     * relacionamentos muitos-para-muitos
     *
     * @var unknown_type
     */
    private $many_to_many = array();
    
    /**
     * delimitador inicial
     *
     * @var string
     */
    private $init_delim   = "#### START AUTOCODE";
    /**
     * delimitar final
     *
     * @var string
     */
    private $end_delim    = "#### END AUTOCODE";

    /**
     * nome do autor
     *
     * @var string
     */
    private $author       = 'Hugo Ferreira da Silva';
    /**
     * data de criação
     *
     * @var unknown_type
     */
    private $date         = null;
    /**
     * nome do gerador
     *
     * @var string
     */
    private $generator    = "Lumine_Reverse";
    /**
     * link padrão da documentação
     *
     * @var string
     */
    private $link         = 'http://www.hufersil.com.br/lumine';
    
    /**
     * identação dos campos
     *
     * @var string
     */
    private $ident        = '    ';
    /**
     * dialeto usado
     *
     * @var string
     */
    private $dialect      = null;
    
    /**
     * Utilizar ou não CamelCase nos nomes das propriedades
     *
     * @var unknown_type
     */
    private $useCamelCase = true;
    
    /**
     * Construtor
     *
     * @param string $tablename nome da tabela
     * @param string $classname nome da classe a ser criada
     * @param string $package nome do pacote da classe
     */
    function __construct($tablename = null, $classname=null, $package=null)
    {
        $this->date = date("Y-m-d");
        $this->setTablename($tablename);
        $this->setClassname($classname);
        $this->setPackage($package);
    }
    
    /**
     * Altera o nome do dialeto
     *
     * @param string $dialect novo dialeto
     */
    public function setDialect( $dialect )
    {
        $this->dialect = $dialect;
    }

    /**
     * altera o nome da tabela
     *
     * @param string $tablename nome da tabela
     */
    public function setTablename( $tablename )
    {
        $this->tablename = $tablename;
    }

    /**
     * altera o nome da classe
     *
     * @param string $classname nome da classe
     */
    public function setClassname( $classname )
    {
        $this->classname = $classname;
    }

    public function setPackage( $package )
    {
        $this->package = $package;
    }
    
    public function setDescription(array $desc)
    {
        $this->description = $desc;
    }
    public function setForeignKeys(array $foreign)
    {
        $this->foreign = $foreign;
    }
    
    public function setCamelCase( $camelCase )
    {
        $this->useCamelCase = $camelCase;
    }
    
    public function getDialect()
    {
        return $this->dialect;
    }	
    public function getTablename()
    {
        return $this->tablename;
    }
    public function getClassname()
    {
        if( $this->getCamelCase() == true )
        {
            $str = $this->CamelCase($this->classname);
            return ucfirst($str);
        }
        return $this->classname;
    }
    public function getPackage()
    {
        return $this->package;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getForeignKeys()
    {
        return $this->foreign;
    }
    
    public function getCamelCase()
    {
        return $this->useCamelCase;
    }
    
    /**
     * pega os dados de uma coluna especifica
     *
     * @param string $column nome da coluna
     * @return array Matriz associativa com os dados
     */
    public function getDefColumn( $column )
    {
        reset($this->description);
        foreach($this->description as $def)
        {
            if($def[0] == $column)
            {
                return $def;
            }
            if(!empty($def['options']) && $def['options']['column'] == $column)
            {
                return $def;
            }
        }
    }
    
    /**
     * Altera os dados de uma coluna
     *
     * @param string $column Nome da coluna
     * @param array $newdef Matriz associativa com os dados
     */
    public function setDefColumn( $column, $newdef)
    {
        reset($this->description);
        foreach($this->description as $item => $def)
        {
            if($def[0] == $column)
            {
                $this->description[ $item ] = $newdef;
                return;
            }
        }
        
    }
    
    public function getGeneratedFile()
    {
        $str = $this->getTop();
        $str .= $this->getClassBody();
        $str .= $this->getFooter();
        
        return $str;
    }
    
    public function addOneToMany($def)
    {
        $this->one_to_many[] = $def;
    }
    
    public function addManyToMany($def)
    {
        $this->many_to_many[] = $def;
    }

    public function getInitDelim()
    {
        return $this->init_delim;
    }

    public function getEndDelim()
    {
        return $this->end_delim;
    }
    
    
    private function getTop()
    {
        $txt = file_get_contents(LUMINE_INCLUDE_PATH.'/lib/Templates/classes.top.txt');
        $txt = str_replace('{classname}', $this->getClassname(), $txt);
        $txt = preg_replace('@\{(\w+)\}@e', '$this->$1', $txt);
        
        return $txt;
    }
    
    
    private function getClassBody()
    {
        $txt = file_get_contents(LUMINE_INCLUDE_PATH.'/lib/Templates/classes.body.txt');
        
        // definicoes
        if(preg_match('@\{definition\}(.*?)\{/definition\}@ms', $txt, $reg))
        {
            $modelo = trim($reg[1]);
            $itens = array('');
            
            foreach($this->description as $item)
            {
                if(empty($item['options']['column']))
                {
                    $column = $item[0];
                } else {
                    $column = $item['options']['column'];
                }

                $length = empty($item[3]) ? 'null' : $item[3];
                $options = array();
                
                if($item[4] == true)
                {
                    $options[] = "'primary' => true";
                }
                if($item[5] == true)
                {
                    $options[] = "'notnull' => true";
                }
                if( !is_null($item[6]) )		// if( !empty($item[6]))
                {
                    $options[] = "'default' => '".$item[6]."'";
                }
                if( !empty($item[7]))
                {
                    $options[] = "'autoincrement' => true";
                }
                
                if( !empty($item['options']))
                {
                    unset($item['options']['column']);
                    foreach($item['options'] as $def => $value)
                    {
                        if( $def == 'linkOn' )
                        {
                            $value = $this->CamelCase($value);
                        }
                        $options[] = "'".$def."' => '".$value."'";
                    }
                }
                
                $options_str = 'array('.implode(', ', $options) . ')';
                $type = $this->dialect->getLumineType( $item[2] );
                
                $line = $modelo;
                $line = str_replace('{name}',    $this->CamelCase($item[0]),      $line);
                $line = str_replace('{column}',  $column,       $line);
                $line = str_replace('{type}',    $type,         $line);
                $line = str_replace('{length}',  $length,       $line);
                $line = str_replace('{options}', $options_str,  $line);
                
                $itens[] = $this->ident . $this->ident . $line;
            }
            
            $txt = str_replace($reg[0], implode(PHP_EOL, $itens), $txt);
        }
        
        if(preg_match('@\{relations\}(.*?)\{/relations\}@ms', $txt, $reg))
        {
            $modelo = trim($reg[1]);
            $itens = array('');
            
            foreach($this->one_to_many as $otm)
            {
                $name    = $otm['name'];
                $type    = 'ONE_TO_MANY';
                $class   = $otm['class'];
                $linkOn  = $otm['linkOn'];
                
                $line = $modelo;
                $line = str_replace('{name}',        $this->CamelCase($name),      $line);
                $line = str_replace('{type}',        $type,      $line);
                $line = str_replace('{class}',       $class,     $line);
                $line = str_replace('{linkOn}',      $this->CamelCase($linkOn),    $line);
                $line = str_replace('{table_join}',  'null',     $line);
                $line = str_replace('{column_join}', 'null',     $line);
                $line = str_replace('{lazy}',        'null',     $line);
                
                $itens[] = $this->ident . $this->ident . $line;
            }
            
            foreach($this->many_to_many as $mtm)
            {
                $mtm['table_join']   = "'" . $mtm['table_join'] . "'";
                $mtm['column_join']  = "'" . $mtm['column_join'] . "'";
            
                $line = $modelo;
                $line = preg_replace('@\{(\w+)\}@e', '$mtm["$1"]', $line);

                $itens[] = $this->ident . $this->ident . $line;
            }
            
            $txt = str_replace($reg[0], implode(PHP_EOL, $itens), $txt);
        }
        
        $txt = preg_replace('@\{(\w+)\}@e', '$this->$1', $txt);
        
        return $txt;
    }
    
    private function CamelCase( $name )
    {
        if( $this->getCamelCase() == false )
        {
            return $name;
        }
        return preg_replace('@_(\w{1})@e', 'strtoupper("$1")', strtolower($name) );
    }
    
    private function getFooter()
    {
        $str = PHP_EOL . '}' . PHP_EOL . '?>';
        
        return $str;
    }
}

?>