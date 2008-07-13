<?php

Lumine::load('Form_IForm');

/**
 * Classe para gerenciar formulários dinâmicos do Lumine
 *
 * @author Hugo Ferreira da Silva
 * @package Form
 */
class Lumine_Form_BasicForm extends Lumine_EventListener implements ILumine_Form
{

    private $obj;
    private $strip_slashes;
    private $template = '/lib/Templates/basic/';
    
    // estas variaveis deverão ser alteradas no template, conforme o gosto
    private $autoincrement_string   = '[ Campo auto-incrementável ]';
    private $calendar_string        = '[ Calendario ]';

    /**
     * Construtor
     *
     * @param Lumine_Base $obj Objeto Lumine_Base para montar o formulário
     * @author Hugo Ferreira da Silva
     */
    function __construct(Lumine_Base $obj = null)
    {
        $this->obj = $obj;
        $this->strip_slashes = get_magic_quotes_gpc();
    }

    /**
     * Cria o formulário e retorna sua string
     *
     * @param string $action Endereço para o formulário deverá ser enviado
     * @author Hugo Ferreira da Silva
     * @return string Formulário criado
     */	
    public function createForm( $action = null )
    {
        if(empty($action))
        {
            $action = $_SERVER['PHP_SELF'];
        }
        
        $def = $this->obj->_getObjectPart('_definition');
        foreach($def as $name => $prop)
        {
            if(empty($prop['options']['label']))
            {
                $prop['options']['label'] = ucfirst($name);
            }
            $def[ $name ] = $prop;
        }
        
        ob_start();
        require_once LUMINE_INCLUDE_PATH . $this->template . 'edit_form.php';
        
        $form = ob_get_contents();
        ob_end_clean();
        
        return $form;
    }

    /**
     * Recupera o controle para o campo solicitado
     *
     * @param string $nome Nome do campo
     * @author Hugo Ferreira da Silva
     * @return string String contendo o campo montado
     */
    public function getInputFor($nome)
    {
        $def = $this->obj->_getField($nome);
        if(empty($def['options']['foreign']))
        {
            switch( $def['type'] )
            {
                case 'int':
                case 'float':
                case 'decimal':
                case 'integer':
                    if( !empty($def['options']['autoincrement']))
                    {
                        $field = $this->autoincrement_string;
                        $field .= '<input type="hidden" name="'.$def['name'].'" value="'.@$_POST[ $def['name'] ].'" />';
                        
                        return $field;
                    } else {
                        $field = '<input type="text" name="'.$def['name'].'" value="'.@$_POST[ $def['name'] ].'" />';
                        return $field;
                    }
                break;
                
                case 'text':
                case 'mediumtext':
                case 'longtext':
                    $field = '<textarea name="'.$def['name'].'" cols="30" rows="4">'.@$_POST[ $def['name'] ].'</textarea>';
                    return $field;
                break;
                
                case 'boolean':
                    $field = '<input type="radio" name="'.$def['name'].'" value="1"';
                    if( !empty($_POST[$def['name']]))
                    {
                        $field .= ' checked="checked"';
                    }
                    $field .= ' /> Sim ';
                    
                    $field .= '<input type="radio" name="'.$def['name'].'" value="0"';
                    if( isset($_POST[$def['name']]) && $_POST[ $def['name'] ] == '0')
                    {
                        $field .= ' checked="checked"';
                    }
                    $field .= ' /> Não ';
                    
                    return $field;
                break;
                
                case 'date':
                case 'datetime':
                    $field = '<input id="'.$def['name'].'" type="text" name="'.$def['name'].'" value="'.@$_POST[$def['name']].'"';
                    $field .= ' size="10"';
                    $field .= ' /> ';
                    $field .= $this->getCalendarFor($nome);
                    return $field;
                break;
                
                case 'varchar':
                case 'char':
                default:
                    $field = '<input type="text" name="'.$def['name'].'" value="'.@$_POST[$def['name']].'"';
                    if( !empty($def['length']))
                    {
                         $length = $def['length'];
                         if($length > 50)
                         {
                            $length = 50;
                         }
                        $field .= ' size="'.$length.'" maxlength="'.$def['length'].'"';
                    }
                    $field .= ' />';
                    return $field;
                break;
            }
        } else {
            $this->obj->_getConfiguration()->import( $def['options']['class'] );

            $cls = new $def['options']['class'];
            $cls->_setAlias('cls');
            
            $first = array_shift($cls->_getPrimaryKeys());
            
            $label = $first['name'];
            if( !empty($def['options']['displayField']))
            {
                $label = $def['options']['displayField'];
            }
            
            $cls->order('cls.' . $label . ' ASC');
            $cls->find();
            
            $combo = '<select name="'.$def['name'].'" id="'.$def['name'].'">';
            $combo .= '<option value=""></option>';
            
            while($cls->fetch())
            {
                $combo .= '<option value="'.$cls->$first['name'].'"';
                if(@$_POST[ $def['name'] ] == $cls->$first['name'])
                {
                    $combo .= ' selected="selected"';
                }
                $combo .= '>' . $cls->$label;
                $combo .= '</option>'.PHP_EOL;
            }
            $combo .= '</select>';
            
            return $combo;
        }
    }
    
    /**
     * Cria um calendário para um determinado campo
     *
     * @param string $name Nome do campo
     * @author Hugo Ferreira da Silva
     * @return
     */
     public function getCalendarFor($name)
     {
        return str_replace('{name}', $name, $this->calendar_string);
     }
     
     /**
      * Exibe a lista de registros para edição
      */
    public function showList($offset, $limit, $formAction = null, $fieldSort = null, $order = null)
    {
        if(is_null($formAction))
        {
            $formAction = $_SERVER['PHP_SELF'];
        }
        
        $def = $this->obj->_getObjectPart('_definition');
        foreach($def as $name => $prop)
        {
            if(empty($prop['options']['label']))
            {
                $prop['options']['label'] = ucfirst($name);
            }
            $def[ $name ] = $prop;
        }
        
        $obj = $this->obj;
        $obj->reset();
        $obj->_setAlias('o');
        
        // aplicando os filtros (podemos filtrar por qualquer campo)
        reset($def);
        $rel = 0;
        $pre = 'r';
        foreach($def as $name => $prop)
        {
            if( !empty($prop['options']['foreign']) && !empty($prop['options']['displayField']))
            {
                $class = new $prop['options']['class'];
                $class->_setAlias( $pre . ($rel++) );
                $obj->join($class, 'LEFT');
                
                $obj->select($class->_getAlias().'.' . $prop['options']['displayField'] .' as '.$name.'_'.$prop['options']['displayField']);
                
                if(!empty($_GET[$name.'_filter_']))
                {
                    $obj->where($class->_getAlias().'.'.$prop['options']['displayField'].' like ?', $_GET[$name.'_filter_']);
                }
                
            } else {
                $obj->select('o.'.$name);
                if(array_key_exists($name.'_filter_', $_GET) && $_GET[$name.'_filter_'] !== '')
                {
                    $obj->where('o.'.$name.' like ?', $_GET[$name.'_filter_']);
                }
            }
        }

        $total = $obj->count();
        $obj->limit($offset, $limit);
        $obj->find();
        
        $list = $obj->allToArray();
        
        ob_start();
        require_once LUMINE_INCLUDE_PATH . $this->template . 'edit_list.php';
        
        $form = ob_get_contents();
        ob_end_clean();
        
        return $form;
    }
    
    public function handleAction($actionName, array $values)
    {
        switch($actionName)
        {
            case 'save':
                return $this->save( $values );
            break;
            
            case 'insert':
                return $this->insert( $values );
            break;
            
            case 'delete':
                echo 'oi';
                return $this->delete( $values );
            break;
            
            case 'edit':
                $obj = $this->obj;
                $pks = $obj->_getPrimaryKeys();
                $obj->reset();
                $obj->_setAlias('o');
                
                foreach($pks as $pk)
                {
                    $obj->where('o.'.$pk['name'].' = ?', @$values['_pk_' . $pk['name']]);
                }
                
                if($obj->find( true ) > 0)
                {
                    $_POST = $obj->toArray();
                }
                
            break;
        }
        return false;
    }
    
    public function getControlTemplate( $cfg, $className )
    {
        $file = LUMINE_INCLUDE_PATH . $this->template . 'control.txt';
        if( !file_exists($file))
        {
            Lumine_Log::error('O arquivo "'.$file.'" não existe!');
            exit;
        }
        
        $content = file_get_contents($file);
        $content = str_replace('{class_path}', str_replace('\\','/',$cfg->getProperty('class_path')), $content);
        $content = str_replace('{entity_name}', $className, $content);
        $content = str_replace('{LUMINE_PATH}', LUMINE_INCLUDE_PATH, $content);
        
        return $content;
    }
    
    public function getTop()
    {
        include_once LUMINE_INCLUDE_PATH . $this->template . 'topo.php';
    }
    public function getFooter()
    {
    }
    
    private function save( $values )
    {
        // pega a lista de chaves primarias e seus valores originais
        // porque em alguns casos a pessoa poderá mudar as chaves primarias
        $obj = $this->obj;
        $obj->reset();
        $obj->_setAlias('o');
        
        $pk_list = $obj->_getPrimaryKeys();
        foreach($pk_list as $pk)
        {
            // condição para atualizar
            $obj->where('o.'.$pk['name'] .' = ?', $values['_pk_' . $pk['name'] ]);
        }
        
        // pega os valores da matriz
        $def = $obj->_getObjectPart('_definition');
        foreach($def as $name => $prop)
        {
            if( !empty($prop['options']['foreign']) && empty($values[ $name ]))
            {
                $obj->$name = null;
            } else {
                if($this->strip_slashes)
                {
                    $obj->$name = stripslashes(@$values[ $name ]);
                } else {
                    $obj->$name = @$values[ $name ];
                }
            }
        }
        
        // atualiza (pelo menos tenta)
        $obj->update( true );
        return true;
    }
    
    /**
     * remove registros 
     * @author Luiz Fernando M. de Carvalho
     * @param array $values
     * @return boolean
     */
    private function delete( $values )
    {
        // pega a lista de chaves primarias e seus valores originais
        // porque em alguns casos a pessoa poderá mudar as chaves primarias
        $obj = $this->obj;
        $obj->reset();
        //$obj->_setAlias('o');

        $pk_list = $obj->_getPrimaryKeys();
        foreach($pk_list as $pk)
        {
            // condição para atualizar
            $obj->where('{'.$pk['name'] .'} = ?', $values['_pk_' . $pk['name'] ]);
        }

        // pega os valores da matriz
        $def = $obj->_getObjectPart('_definition');
        foreach($def as $name => $prop)
        {
            if( !empty($prop['options']['foreign']) && empty($values[ $name ]))
            {
                $obj->$name = null;
            } else {
                if($this->strip_slashes)
                {
                    $obj->$name = stripslashes(@$values[ $name ]);
                } else {
                    $obj->$name = @$values[ $name ];
                }
            }
        }

        // deleta
        $obj->delete( true );
        return true;
    }
    
    private function insert( $values )
    {
        $def = $this->obj->_getObjectPart('_definition');
        foreach($def as $name => $prop)
        {
            if( !empty($prop['options']['foreign']) && empty($values[ $name ]))
            {
                $this->obj->$name = null;
            } else {
                if($this->strip_slashes)
                {
                    $this->obj->$name = stripslashes(@$values[ $name ]);
                } else {
                    $this->obj->$name = @$values[ $name ];
                }
            }
        }
        
        // $this->obj->setFrom($values);
        $res = $this->obj->validate();
        
        if($res === true)
        {
            $this->obj->insert();
            return true;
        }
        return $res;
    }
    
    
    
}



?>