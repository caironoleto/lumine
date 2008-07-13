<?php
$topstyle = ' bgcolor="#666666" style="color:#FFFFFF; font-weight:bold"';

// recupera a lista de chave primaria
$pks_list = $this->obj->_getPrimaryKeys();
?>
<form id="busca" method="get" action="<?php echo $formAction; ?>">
<table cellpadding="2" cellspacing="1" border="0" bgcolor="#CCCCCC" width="100%">
	<tr>
<?php

// definição para filtros
	foreach($def as $name => $item)
	{
?>
	<td bgcolor="#FFFFFF"><input type="text" name="<?php echo $name ?>_filter_" value="<?php echo @$_GET[ $name.'_filter_'] ?>" onblur="$('busca').submit()" /></td>
<?php
	}
?>
	</tr>
    <tr>
<?php

// definição para os labels dos campos
	foreach($def as $item)
	{
?>
	<td<?php echo $topstyle; ?>><?php echo $item['options']['label']; ?></td>
<?php
	}
?>
	</tr>
<?php
if(!empty($list))
{
	$cor = '';
	foreach($list as $item)
	{
		$cor = $cor == '#EFEFEF' ? '#FFFFFF' : '#EFEFEF';
		$link_array = array();
		$link_str   = '';
		foreach($pks_list as $pk)
		{
			$link_array[] = '_pk_' . $pk['name'] . '=' . $item[ $pk['name'] ];
			unset($_GET['_pk_' . $pk['name']]);
		}
		// agora, colocamos os itens do GET
		foreach($_GET as $key => $val)
		{
			$link_array[] = $key .'='. $val;
		}
		
		$link_str   = $formAction . '?_lumineAction=edit&amp;' . implode('&amp;', $link_array);
?>
	<tr>
        <?php
        foreach($item as $key => $val)
		{
		?>
        <td bgcolor="<?php echo $cor; ?>">
        <a href="<?php echo $link_str; ?>">
        <?php
			if(is_null($val)) {
				echo '<em>null</em>';
			} else if(strlen($val) > 50) {
				echo str_replace('<', '&lt;', substr($val, 0, 50)).'...';
			} else {
				echo str_replace('<', '&lt;', $val);
			}
		?>
        </a>
		</td>
        <?php
		}
		?>
    </tr>
<?php
	}
?>
<tr>
	<td bgcolor="#FFFFFF" colspan="<?php echo count($def)+1; ?>">
        <select id="__limit" name="limit" onchange="$('busca').submit()">
<?php
$max   = 70;
$min   = 10;
$step  = 10;

for($i=$min; $i<=$max; $i += $step)
{
	printf('<option value="%d"%s>Mostrar %s registros por página</option>'.PHP_EOL, $i, $i==$limit ? ' selected':'', $i);
}

?>
        </select>

    	<select id="_paginacao" onchange="location.href=this.value">
<?php
$paginas   = ceil($total/$limit);
$offset    = (int)@$_GET['offset'];

for($i=0; $i<$paginas; $i++)
{
	$lnk = $formAction . '?';
	foreach($_GET as $k => $v)
	{
		if($k == 'offset')
		{
			continue;
		}
		$lnk .= $k .'=' .$v. '&';
	}
	$lnk .= 'offset=' . ($i * $limit);
	printf('<option value="%s"%s>%s</option>', $lnk, $offset == $i * $limit ? ' selected' : '', 'Página ' . ($i + 1) .' de '. $paginas);
}

?>
        </select>
            </td>
</tr>
<?php
} else {
?>
<tr><td colspan="<?php echo count($def)+1; ?>" bgcolor="#FFFFFF">N&atilde;o h&aacute; registros</td></tr>
<?php
}
?>
</table>
</form>