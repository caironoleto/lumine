<style type="text/css">
<!--
body, td, th, input, select, textarea {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 11px;
}
-->
</style>
<?php

// variaveis para mudar lances visuais
$this->autoincrement_string = ' [AutoIncrement] ';
$this->calendar_string      = '<a href="#" id="anc{name}" onclick="cal_{name}.show()">[Calendario]</a><script>var cal_{name} = new CalendarioInput("{name}","anc{name}","BR");</script>';

?>
<script type="text/javascript">
/**
 * Monta um calendário de eventos
 * exibindo cores diferenciadas para dias que tem eventos
 * Feito com POO
 * @author Hugo Ferreira da Silva
 * @link http://www.hufersil.com.br
 * Exemplo de uso:
 *  var oCalendario = new CalendarioEventos(20, 9, 2007); // data atual
 *  oCalendario.addEvento('30/09/2007','titulo do evento');
 *  oCalendario.exibir( 'id_da_div_para_exibir' );
 */

function CalendarioEventos(dia, mes, ano)
{
	this.estilos = {};
	this.estilos.principal    = 'agenda_calendario_principal';
	this.estilos.hoje         = 'agenda_calendario_hoje';
	this.estilos.dia_off      = 'agenda_calendario_off';
	this.estilos.dia_on       = 'agenda_calendario_on';
	this.estilos.domingo      = 'agenda_calendario_domingo';
	this.estilos.vazio        = 'agenda_calendario_vazio';
	this.estilos.comevento    = 'agenda_calendario_comevento';
	this.estilos.mes_anterior = 'agenda_calendario_mes_anterior';
	this.estilos.mes_sucessor = 'agenda_calendario_mes_sucessor';
	this.estilos.mes_nome     = 'agenda_calendario_mes_nome';
	this.estilos.mes_over     = 'agenda_calendario_mes_over';
	this.estilos.tooltip      = 'agenda_calendario_tooltip';
	
	this.dia = dia;
	this.mes = mes-1;
	this.ano = ano;
	
	this.hoje_dia = dia;
	this.hoje_mes = mes-1;
	this.hoje_ano = ano;
	
	this.tooltip   = null;
	this.eventos   = [];
	this.dias      = [31,28,31,30,31,30,31,31,30,31,30,31];
	this.cabecalho = ['D','S','T','Q','Q','S','S'];
	this.meses     = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
	
	this._container = '';
}

CalendarioEventos.prototype = {
	exibir : function( divname )
	{
		var el = CalendarioEventos.$(divname);
		if( !el )
		{
			alert('DIV não encontrada: ' + divname);
			return false;
		}
		
		this._container = divname;

		var oCalendario = this._montar();
		el.innerHTML = '';
		el.appendChild( oCalendario );
	},
	
	addEvento : function(data, nome)
	{
		var list = data.split('/');
		var dia = parseFloat(list[0]);
		var mes = parseFloat(list[1]) - 1;
		var ano = parseFloat(list[2]);
		
		this.eventos.push( new CalendarioEventos.Evento(dia, mes, ano, nome) );
	},
	
	getEventsIn : function(dia, mes, ano)
	{
		var list = [];
		for(var i=0; i<this.eventos.length; i++)
		{
			var evt = this.eventos[i];
			if(evt.dia == dia && evt.mes == mes && evt.ano == ano)
			{
				list.push( evt );
			}
		}
		
		return list;
	},
	
	onSelect : function( data )
	{

	},
	

	
	_montar : function()
	{
		this.tooltip = null;
		
		var oDate = new Date();
		oDate.setDate( 1 );
		oDate.setMonth( this.mes );
		oDate.setFullYear( this.ano );
		
		var diaSemana = oDate.getDay();
		var totalDias = this.dias[ this.mes ];
		
		if(this.mes == 1 && this.ano % 4 == 0)  // ano bissexto e é fevereiro
		{
			totalDias ++;
		}
		
		oDate.setDate( this.dia );
		
		var oTable = document.createElement('TABLE');
		oTable.className = this.estilos.principal;
		
		var oBody = document.createElement('TBODY');
		oBody.appendChild( this._header() );
		oBody.appendChild( this._cabecalhoDias() );
		
		// monta os dias vazios do mês (inicio do mes)
		var oTr = document.createElement('TR');
		var cell;
		
		for(var i=0; i<diaSemana; i++)
		{
			cell = this._getCell(null, this.estilos.vazio);
			oTr.appendChild( cell );
		}
		
		// agora iniciamos a montagem dos dias
		for(var i=1; i<=totalDias; i++)
		{
			oDate.setDate( i );
			diaSemana = oDate.getDay();
			
			if(this.getEventsIn(i, this.mes, this.ano).length > 0)
			{
				cell = this._getCell(i, this.estilos.comevento);
				cell.comeventos = true;
			} else if(this.hoje_dia == i && this.hoje_mes == this.mes && this.hoje_ano == this.ano) {
				cell = this._getCell(i, this.estilos.hoje);
				cell.hoje = true;
			} else {
				if(diaSemana == 0)
				{
					cell = this._getCell(i, this.estilos.domingo);
					cell.domingo = true;
				} else {
					cell = this._getCell(i, this.estilos.dia_off);
					cell.domingo = false;
				}
			}
			oTr.appendChild( cell );
			
			if(diaSemana == 6)
			{
				oBody.appendChild( oTr );
				oTr = document.createElement('TR');
			}
		}
		
		// coloca os dias finais
		if(diaSemana < 6)
		{
			for(var i=diaSemana; i<6; i++)
			{
				cell = this._getCell(null, this.estilos.vazio);
				oTr.appendChild( cell );
			}
			oBody.appendChild( oTr );
		}
		
		oTable.appendChild( oBody )
		
		return oTable;
	},
	
	_header : function()
	{
		var oTr   = document.createElement('TR');
		var prev  = document.createElement('TD');
		var tdmes = document.createElement('TD');
		var next  = document.createElement('TD');
		
		prev.innerHTML = '««';
		next.innerHTML = '»»';
		tdmes.innerHTML = this.meses[ this.mes ]+ ' / ' + this.ano;
		
		prev.className  = this.estilos.mes_anterior;
		next.className  = this.estilos.mes_sucessor;
		tdmes.className = this.estilos.mes_nome;
		
		tdmes.colSpan = 5;
		
		oTr.appendChild( prev );
		oTr.appendChild( tdmes );
		oTr.appendChild( next );
		
		prev.onmouseover = CalendarioEventos.delegate(this, this._mesover, prev);
		prev.onmouseout  = CalendarioEventos.delegate(this, this._mesout,  prev, this.estilos.mes_anterior);
		prev.onclick     = CalendarioEventos.delegate(this, this._mesAnterior);
		
		next.onmouseover = CalendarioEventos.delegate(this, this._mesover, next);
		next.onmouseout  = CalendarioEventos.delegate(this, this._mesout,  next, this.estilos.mes_sucessor);
		next.onclick     = CalendarioEventos.delegate(this, this._proximoMes);
		
		return oTr;
	},
	
	_cabecalhoDias : function()
	{
		var oTr = document.createElement('TR');
		
		// cabeçalho dos dias
		for(var i=0; i<this.cabecalho.length; i++)
		{
			var cell = this._getCell(null, this.estilos.domingo);
			cell.innerHTML = this.cabecalho[i];
			oTr.appendChild( cell );
		}
		
		return oTr;
	},
	
	_getCell : function(dia, classe)
	{
		var cell = document.createElement('TD');
		cell.className = classe;
		cell.innerHTML = dia == null ? '&nbsp;' : dia;
		
		if( dia != null )
		{
			cell.onmouseover = CalendarioEventos.delegate(this, this._mouseover, cell);
			cell.onmouseout  = CalendarioEventos.delegate(this, this._mouseout,  cell);
			cell.onclick     = CalendarioEventos.delegate(this, this.onSelect,   this._montaData(dia, this.mes, this.ano));
		}
		
		return cell;
	},
	
	_mouseover : function( cell )
	{
		cell.className = this.estilos.dia_on;
		this._showEventos( parseFloat( cell.innerHTML ), cell);
	},
	
	_mouseout : function( cell )
	{
		if(cell.comeventos == true) 
		{
			cell.className = this.estilos.comevento;
		} else if(cell.hoje == true) {
			cell.className = this.estilos.hoje;
		} else if(cell.domingo == false) {
			cell.className = this.estilos.dia_off;
		} else {
			cell.className = this.estilos.domingo;
		}
		
		this._hideEventos();
	},
	
	_mesAnterior : function()
	{
		this.mes--;
		if(this.mes < 0)
		{
			this.mes = 11;
			this.ano--;
		}
		this.exibir(this._container);

	},
	
	_proximoMes : function()
	{
		this.mes++;
		if(this.mes > 11)
		{
			this.mes = 0;
			this.ano++;
		}
		this.exibir(this._container);
	},
	
	_mesover : function( cell )
	{
		cell.className = this.estilos.mes_over;
	},
	
	_mesout : function( cell, classe )
	{
		cell.className = classe;
	},
	
	_showEventos : function(dia, ref)
	{
		var list  = this.getEventsIn(dia, this.mes, this.ano);
		var el    = this.tooltip;
		var nomes = '';
		
		if(el == null)
		{
			el                 = document.createElement('DIV');
			el.className       = this.estilos.tooltip;
			el.style.position  = 'absolute';
			el.style.display   = 'none';
			
			CalendarioEventos.$(this._container).appendChild( el );
			
			this.tooltip = el;
		}
		
		for(var i=0; i<list.length; i++)
		{
			nomes += list[i].nome + '<br />';
		}
		
		if(nomes != '')
		{
			el.innerHTML = nomes;
			setVisible(el, true);
			
			var p = getPosition(ref);
			var s = getSize(el);
			
			setPosition(el, p.x, p.y-s.height);
		}
	},
	
	_hideEventos : function()
	{
		this.tooltip.style.display = 'none';
	},
	
	_montaData : function(dia, mes, ano)
	{
		var data = dia < 10 ? '0' + dia : dia;
		data += '/' + ( mes + 1 < 10 ? '0' + (mes+1) : mes + 1 );
		data += '/' + ano;
		
		return data;
	}
}

/**
 * classe que representa um evento
 */
CalendarioEventos.Evento = function( dia, mes, ano, nome )
{
	this.dia = dia;
	this.mes = mes;
	this.ano = ano;
	this.nome = nome;
}

/**
 * classe para trabalhar com campos inputs
 */
CalendarioInput = function(input, button, posicao)
{
	var d = new Date();
	
	this.calendario = new CalendarioEventos(d.getDate(), d.getMonth()+1, d.getFullYear());
	this.posicao    = posicao;
	this.input      = input;
	this.button     = button;
	this.container  = null;
	this.width      = 160;
	this.intervalo  = null;
	
	this.calendario.parent = this;
	
	this.calendario.onSelect = function( data )
	{
		this.parent.alterar(data);
	}
}

CalendarioInput.prototype = {
	alterar : function(d)
	{
		var e = CalendarioEventos.$( this.input );
		if(e)
		{
			CalendarioEventos.$(this.input).value = d;
			this.esconder();
		}
	},
	
	show : function()
	{
		if(this.container == null)
		{
			var e = document.createElement('div');
			e.id = '_calendario_' + Math.random();
			e.style.position = 'absolute';
			document.body.appendChild( e );
			
			this.container = e;
		}
		
		var b = CalendarioEventos.$(this.button);
		if(b)
		{
			var p = CalendarioEventos.getPosition(b);
			this.container.style.left = p.x+'px';
			this.container.style.top  = (p.y + b.offsetHeight) + 'px';
		}
		
		this.container.style.width = this.width + 'px';
		this.calendario.exibir( this.container.id );
		this.container.style.display = '';
		
		this.bindTimeout();
	},
	
	esconder : function()
	{
		clearTimeout(this.intervalo);
		this.container.style.display = 'none';
	},
	
	bindTimeout  : function()
	{
		if(this.intervalo == null)
		{
			$( this.button ).onmouseout = CalendarioEventos.delegate(this, this.iniciaEsconde);
			$( this.button ).onmouseover = CalendarioEventos.delegate(this, function(){ clearTimeout(this.intervalo); });
	
			this.container.onmouseout = $( this.button ).onmouseout;
			this.container.onmouseover = $( this.button ).onmouseover;
		}
	},
	
	iniciaEsconde : function()
	{
		clearTimeout(this.intervalo);
		this.intervalo = setTimeout(CalendarioEventos.delegate(this, this.esconder), 500);
	}
}

/**
 * funções auxiliares
 */

CalendarioEventos.$ = function( id )
{
	return document.getElementById( id );
}

CalendarioEventos.delegate = function( escopo, fnc )
{
	var args = [];
	for(var i=2; i<arguments.length; i++)
	{
		args.push(arguments[i]);
	}
	
	return function(e)
	{
		e = window.event || e;
		args.push(e);
		
		fnc.apply( escopo, args );
	}
}

CalendarioEventos.EstilosPadrao = function()
{
	var str = ' .agenda_calendario_principal { background-color: #FFFFFF; border: 1px solid #666666; width: 100%; }';
	str += ' .agenda_calendario_principal td { font-size: 10px; font-family: Verdana, Arial; }';
	str += ' .agenda_calendario_hoje, .agenda_calendario_vazio, .agenda_calendario_on, .agenda_calendario_off, .agenda_calendario_domingo, .agenda_calendario_comevento { ';
	str += ' padding: 3px; border: 1px solid #CCCCCC; text-align: center; }';
	str += '.agenda_calendario_tooltip { padding: 4px; background-color:#FFFFCC; border:1px solid #000000; width: 200px; z-index: 1500;	text-align:left; }';
	str += ' .agenda_calendario_comevento { background-color:#00CC00; } ';
	str += ' .agenda_calendario_hoje { background-color:#FFCC33; }';
	str += ' .agenda_calendario_vazio { background-color:#CCCCCC; }';
	str += '.agenda_calendario_off { background-color:#FFFFFF; color:#000000; cursor: pointer; }';
	str += ' .agenda_calendario_on { background-color:#006699; color:#FFFFFF; cursor: pointer; border: 1px solid #000000; }';
	str += ' .agenda_calendario_domingo { background-color:#EFEFEF; }';
	str += ' .agenda_calendario_mes_nome, .agenda_calendario_mes_anterior, .agenda_calendario_mes_sucessor, .agenda_calendario_mes_over { background-color:#0066CC; color: #FFFFFF;	font-weight:bold; text-align: center; padding: 2px; }';
	str += ' .agenda_calendario_mes_anterior, .agenda_calendario_mes_sucessor { cursor:pointer; }';
	str += ' .agenda_calendario_mes_over { background-color:#003366; cursor: pointer; }';
	
	str = '<style>' + str + '</style>';
	document.write( str );
}

CalendarioEventos.getPosition = function (o)
{
	var x=0, y=0;
	while(o != null) {
		x += parseFloat(o.offsetLeft);
		y += parseFloat(o.offsetTop);
		o = o.offsetParent;
	}
	return {x:x, y:y};
}	


function $(id) { return document.getElementById(id); }
function salvar()
{
	$('_lumineAction').value = 'save';
	$('frmLumine').submit();
}

function inserir()
{
	$('_lumineAction').value = 'insert';
	$('frmLumine').submit();
}

function remover()
{
    $('_lumineAction').value = 'delete';
    $('frmLumine').submit();
}

CalendarioEventos.EstilosPadrao();
</script>

<form id="frmLumine" name="frmLumine" method="post" action="<?php echo $action; ?>">
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
			<td colspan="2" align="center" bgcolor="#FFFFFF"><input type="button" name="btInserir" value="Inserir" id="btInserir" onclick="inserir()" />
			  <input type="button" name="btSalvar" value="Salvar" id="btSalvar" onclick="salvar()">
			  <input type="button" name="btRemover" value="Remover" id="btRemover" onclick="remover()">
		    <input type="hidden" name="_lumineAction" id="_lumineAction" />
<?php
// guarda os valores das chaves primarias anteriores (se houver)
$pk_list = $this->obj->_getPrimaryKeys();

foreach($pk_list as $pk)
{
	$vlr = @$_REQUEST['_pk_' . $pk['name']];
	printf('<input type="hidden" name="_pk_%s" value="%s" />', $pk['name'], $vlr);
}

?>
            </td>
		</tr>
	</table>
</form>
