<?php

// variaveis para mudar lances visuais
$this->autoincrement_string = ' [AutoIncrement] ';
$this->calendar_string      = '<a href="#" onclick="Calendario.show(\'{name}\')">[Calendario]</a>';

?>
<script type="text/javascript">
var Calendario = {
	div: null,
	
	show:function( target )
	{
		var d = this.getDiv();
		d.style.left = document.getElementById(target).offsetLeft + 'px';
		d.style.top = (document.getElementById(target).offsetTop + 25) + 'px';
	},
	
	getDiv:function()
	{
		if(this.div == null)
		{
			this.div = document.createElement('DIV');
			this.div.id = '___calendario';
			with(this.div.style)
			{
				position = 'absolute';
				width = '300px';
				height = '150px';
				backgroundColor = '#EFEFEF';
			}
			document.body.appendChild(this.div);
		}
		return this.div;
	}
}
</script>
<form name="form1" method="post" action="<?php echo $action; ?>">
	<table width="100%" border="0" cellpadding="2" cellspacing="1" bgcolor="#C4F3FF">
		<?php
foreach($def as $name => $prop)
{

?>
		<tr>
			<td width="49%" align="right" valign="top" bgcolor="#FFFFFF"><?php if(!empty($prop['options']['notnull'])) echo '* '; ?>
					<?php echo $prop['options']['label']; ?>:</td>
			<td width="51%" bgcolor="#FFFFFF"><?php echo $this->getInputFor($name); ?></td>
		</tr>
		<?php
}
?>
		<tr>
			<td colspan="2" align="center" bgcolor="#FFFFFF"><input type="submit" name="Submit" value="Salvar"></td>
		</tr>
	</table>
</form>
