<?php

ini_set('error_reporting',E_ALL);
require_once dirname(dirname(dirname(__FILE__))) . '/Lumine.php';

if(isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name']))
{
	$_POST = unserialize(file_get_contents($_FILES['file']['tmp_name']));
	unset($_POST['acao']);
}

if(@$_POST['acao'] == 'Salvar')
{
	$txt = serialize($_POST);
	header("Content-Type: text/plain");
	header("Content-disposition: attachment; filename=\"lumine.txt\"");
	echo $txt;
	exit;
}

$init = explode(',', 'host,user,port,database,class_sufix,package,password,tipo_geracao,dialect,remove_prefix,remove_count_chars_end,remove_count_chars_start,many_to_many_style,plural,create_entities_for_many_to_many,schema_name');
$options = array('schema_name','generate_files','generate_zip','class_sufix','remove_count_chars_start','remove_count_chars_end','remove_prefix','create_entities_for_many_to_many','plural','create_entities_for_many_to_many','many_to_many_style','create_controls');
$exclude = array('chall','tables','acao','tipo_geracao');

foreach($init as $var)
{
	if(!isset($_POST[$var]))
	{
		$_POST[$var] = '';
	}
}

foreach($_POST as $key => $val)
{
	if(!is_array($val))
	{
		$_POST[$key] = stripslashes($val);
	}
}

if(!isset($_POST['class_path']))
{
	$_POST['class_path'] = dirname(__FILE__);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Lumine - Engenharia Reversa</title>
<link href="estilos.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
function checkAll() {

	var list = document.form1['tables[]'];
	var ch = document.form1.chall.checked;
	
	for(var i=0; i<list.length; i++)
	{
		list[i].checked = ch;
	}

}
</script>
</head>

<body>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" name="form1" id="form1">
	<ul class="MenuSuperior">
	  <li>Dados de acesso</li>
      <li>Op&ccedil;&otilde;es de gera&ccedil;&atilde;o</li>
      <li>Defini&ccedil;&atilde;o de caminhos</li>
      <li>Miscel&acirc;nia </li>
  </ul>
	<div class="ConteudoTab">Content for  class "ConteudoTab" Goes Here</div>
	<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr>
			<td colspan="2" align="center"><img src="imagens/lumine.gif" width="156" height="45" alt="Lumine" /></td>
		</tr>
		<tr>
			<td align="right">Abrir configura&ccedil;&atilde;o: </td>
			<td><input type="file" name="file" onchange="document.form1.submit()" /></td>
		</tr>
		<tr>
			<td width="46%" align="right">Dialeto:</td>
			<td width="54%"><select name="dialect" id="dialect">
					<?php
		$dh = opendir(LUMINE_INCLUDE_PATH.'/lib/Connection');
		$nopes = array('.','..','IConnection.php');
		
		while($file = readdir($dh))
		{
			if(in_array($file , $nopes))
			{
				continue;
			}
			
			$name = str_replace('.php','',$file);
			
			echo '<option value="'.$name.'"';
			if($_POST['dialect'] == $name)
			{
				echo ' selected="selected"';
			}
			echo '>'.$name.'</option>'.PHP_EOL;
		}
		
		?>
				</select>			</td>
		</tr>
		<tr>
			<td align="right">Nome do banco de dados: </td>
			<td><input name="database" type="text" id="database" value="<?php echo $_POST['database']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Usu&aacute;rio do banco de dados: </td>
			<td><input name="user" type="text" id="user" value="<?php echo $_POST['user']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Senha do banco de dados: </td>
			<td><input name="password" type="text" id="password" value="<?php echo $_POST['password']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Porta:</td>
			<td><input name="port" type="text" id="port" value="<?php echo $_POST['port']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Host:</td>
			<td><input name="host" type="text" id="host" value="<?php echo $_POST['host']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Utilizar sufixo na cria&ccedil;&atilde;o dos arquivos: </td>
			<td><select name="class_sufix">
					<option value="">Nenhum</option>
					<option value="class"<?php echo $_POST['class_sufix'] =='class' ? ' selected="selected"' :''; ?>>class</option>
					<option value="inc"<?php echo $_POST['class_sufix'] =='inc' ? ' selected="selected"' :''; ?>>inc</option>
				</select>			</td>
		</tr>
		<tr>
			<td align="right">Diret&oacute;rio raiz (class-path): </td>
			<td><input name="class_path" type="text" id="class_path" value="<?php echo $_POST['class_path']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Nome do pacote: </td>
			<td><input name="package" type="text" id="package" value="<?php echo $_POST['package']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Schema: </td>
			<td><input name="schema_name" type="text" id="schema_name" value="<?php echo $_POST['schema_name']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Gerar arquivos: </td>
			<td><select name="tipo_geracao" id="tipo_geracao">
				<option value="1"<?php echo $_POST['tipo_geracao']=='1'?' selected="selected"' : ''; ?>>Direto na pasta de destino</option>
				<option value="2"<?php echo $_POST['tipo_geracao']=='2'?' selected="selected"' : ''; ?>>Em um arquivo ZIP dentro de &quot;CLASS-PATH&quot;</option>
			</select>			</td>
		</tr>
		<tr>
			<td align="right">Remover prefixo das tabelas: </td>
			<td><input name="remove_prefix" type="text" id="remove_prefix" value="<?php echo $_POST['remove_prefix']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Quantidade de caracteres para remover do inicio das tabelas: </td>
			<td><input name="remove_count_chars_start" type="text" id="remove_count_chars_start" value="<?php echo $_POST['remove_count_chars_start']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Quantidade de caracteres para remover do final das tabelas:</td>
			<td><input name="remove_count_chars_end" type="text" id="remove_count_chars_end" value="<?php echo $_POST['remove_count_chars_end']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">Formato de nome para auto-identificar tabelas Many-To-Many: </td>
			<td><input name="many_to_many_style" type="text" id="many_to_many_style" value="<?php echo $_POST['many_to_many_style']; ?>" />
				(Ex: tabela1_tabela2 = %s_%s) </td>
		</tr>
		<tr>
			<td align="right">Gerar entidades para tabelas many-to-many? </td>
			<td><select name="create_entities_for_many_to_many" id="create_entities_for_many_to_many">
				<option value="1"<?php echo $_POST['create_entities_for_many_to_many']=='1'?' selected="selected"' : ''; ?>>Sim</option>
				<option value="0"<?php echo $_POST['create_entities_for_many_to_many']=='0'?' selected="selected"' : ''; ?>>N&atilde;o</option>
			</select>			</td>
		</tr>
		<tr>
			<td align="right">Gerar controles utilizando: </td>
			<td><select name="create_controls" id="create_controls">
				<option value="">N&atilde;o gerar controles</option>
				<?php
				$dir = LUMINE_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Form' . DIRECTORY_SEPARATOR;
				$files = glob($dir . '*.php');
				
				foreach($files as $file)
				{
					preg_match('@([\w,\.]+)\.php$@', $file, $reg);
					if($reg[1] != 'IForm')
					{
						printf('<option value="%s"%s>%s</option>',
							$reg[1],
							$reg[1] == @$_POST['create_controls'] ? ' selected="selected"' : '',
							$reg[1]);
					}
				}
				
				?>
			</select>			</td>
		</tr>
		<tr>
			<td align="right">String para converter em plural relacionamentos MTM e OTM: </td>
			<td><input name="plural" type="text" id="plural" value="<?php echo $_POST['plural']; ?>" /></td>
		</tr>
		<tr>
			<td align="right">&nbsp;</td>
			<td><?php if($_SERVER['REQUEST_METHOD'] == 'GET') {?>
			<input name="acao" type="submit" id="acao" value="Continuar" /><?php } ?>
			<input name="acao" type="submit" id="acao" value="Salvar" /></td>
		</tr>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['tables'])) {
	$res = false;
	$message = '';
	$list = array();
	
	try 
	{
		$dbh = new Lumine_Configuration( $_POST );
		$conn = $dbh->getConnection();
		$res = $conn->connect();
		$list = $conn->getTables();
		
	} catch(Exception $e) {
		$message = $e->getMessage();
		$res = false;
	}
	
	if($res == false)
	{
?>
		<tr>
			<td align="right">Não foi possível conectar com "<?php echo $_POST['host']; ?>": <?php echo $message; ?> </td>
			<td><input type="submit" name="Submit" value="Tentar novamente" /></td>
		</tr>
<?php
	} else {
?>
		<tr>
			<td align="right" valign="top">Selecione as tabelas para realizar a engenharia reversa: </td>
			<td>
			<input name="chall" type="checkbox" id="chall" value="1" onclick="checkAll()" />
			------ Selecionar todas as tabelas<br />
			<?php
			foreach($list as $table)
			{
				printf('<input type="checkbox" name="tables[]" value="%1$s" /> %1$s <br />', $table);
			}
			?>			</td>
		</tr>
		<tr>
			<td align="right">&nbsp;</td>
			<td><input name="acao" type="submit" id="acao" value="Concluir" /></td>
		</tr>
<?php
	}
}

?>
	</table>
<?php
if(@$_POST['acao'] == 'Concluir' && isset($_POST['tables']) && is_array($_POST['tables']))
{
?>
<table width="100%" border="0" cellspacing="1" cellpadding="2">
	<tr>
		<td align="center"><input name="bt" type="submit" id="bt" value="Voltar" /></td>
	</tr>
	<tr>
		<td>
		<?php
		try
		{
			Lumine::load('Reverse');
			$table_list = $_POST['tables'];
			
			// ajusta algumas configurações
			if($_POST['tipo_geracao'] == 1)	// direto na pasta
			{
				$_POST['generate_files'] = 1;
			} else if($_POST['tipo_geracao'] == 2) { // arquivo zip
				$_POST['generate_zip'] = 1;
			}
			
			foreach($options as $key)
			{
				$_POST['options'][$key] = @$_POST[ $key ];
				unset($_POST[ $key ]);
			}
			foreach($exclude as $key)
			{
				unset($_POST[ $key ]);
			}
			
			Lumine_Log::setLevel(Lumine_Log::ERROR);
			$cfg = new Lumine_Reverse($_POST);
			$cfg->setTables($table_list);
			$cfg->start();
			
		} catch (Exception $e) {
			echo "Falha na engenharia reversa: " . $e->getMessage();
		}
		?>		</td>
	</tr>
</table>
<?php
}
?>
</form>
</body>
</html>
