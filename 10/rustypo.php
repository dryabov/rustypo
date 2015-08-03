<?php
/********************************************
* RussianTypography mambot for Mambo/Joomla *
* Copyright (C) 2006-2011 by Denis Ryabov   *
* Email      : dryabov@yandex.ru            *
* Version    : 2.0.4                        *
* License    : Released under GPL           *
********************************************/

// ����� "�����������" ����� ���� �� ������ http://www.softportal.com/articles/item_txt.php?id=208.
// � ��� ������ �������� http://spectator.ru/, �� �� ���� ����� ������ ������ ���������� �� ����...

// ����� ����������� ������� � �spectator.ru


defined( '_VALID_MOS' ) or die( '������ ��������.' );

$_MAMBOTS->registerFunction( 'onPrepareContent', 'botRusTypo' );


define('TAGBEGIN', "\x01");
define('TAGEND',   "\x02");
$Refs = array(); // ����� ��� �������� �����
$RefsCntr = 0;   // ������� ������
function putTag($x)
{
	global $Refs, $RefsCntr;
	$Refs[] = $x[0];
	return TAGBEGIN.($RefsCntr++).TAGEND;
}
function getTag($x)
{
	global $Refs;
	return $Refs[$x[1]];
}


define('NOBRSPACE',  "\x03");
define('NOBRHYPHEN', "\x04");
define('THINSP',     "\x05");
define('DASH',       "\x06");
define('NUMDASH',    "\x07");

function Proof( $text, &$botParams )
{
	global $Refs, $RefsCntr;
	$htmlents = array(
		'&#8222;'=>'�','&#8219;'=>'�','&#8220;'=>'�','&#8216;'=>'�','&#8217;'=>'�',
		'&laquo;'=>'�','&raquo;'=>'�','&hellip;'=>'�','&euro;'=>'�','&permil;'=>'�',
		'&bull;'=>'�','&middot;'=>'�','&ndash;'=>'�','&mdash;'=>'�','&nbsp;'=>'�',
		'&trade;'=>'�','&copy;'=>'�','&reg;'=>'�','&sect;'=>'�','&#8470;'=>'�',
		'&plusmn;'=>'�','&deg;'=>'�');
	$text = strtr( $text, $htmlents ); // ������ ������ html entity �� ������� �� cp1251

// ������ � ������. ����� 1
	if( $botParams->get( 'spacesatend' ) ) $text = preg_replace( '/(?> |�)+(?=$|<br|<\/p)/', '', $text ); // ������� ������ ������� ����� ������ ������
	if( $botParams->get( 'aquotes' ) ) $text = preg_replace( '/<a +href([^>]*)> *(?:"|&quot;)([^<"]*)(?:"|&quot;) *<\/a>/', '"<a href\\1>\\2</a>"', $text ); // ������� ������� �� ������
	if( $botParams->get( 'shortatend' ) ) $text = preg_replace( '/([�-��-�a-zA-Z]) ([�-��-�a-zA-Z]{1,5}(?>[.!?�]*))(?=$|<\/p>|<\/div>|<br>|<br \/>)/','\\1'.NOBRSPACE.'\\2', $text); // ��������� �������� ����� � ������ ����������� � �����������

//������ ����
	if( $botParams->get( 'speech' ) ) $text = preg_replace( '/(^|<p>|<br>|<br \/>)[ �]?- /','\\1��', $text ); // ������ ���� - ����� � ������ ������ � ����� ����� <p>, <br> � <br />

// �������� ����
	$Refs = array();
	$RefsCntr = 0;
	$text = preg_replace_callback('/<!--.*?-->/s', 'putTag', $text); // �����������
	$text = preg_replace_callback('/< *(script|style|pre|code|textarea).*?>.*?< *\/ *\1 *>/is', 'putTag', $text); // ����, ������� ���������� ������ � ����������
	$text = preg_replace_callback('/<(?:[^\'"\>]+|".*?"|\'.*?\')+>/s', 'putTag', $text); // ������� ����

	$text = strtr( $text, "\t\n\r", '   ' ); // �������� ��������� � ������� ������ �� ������
	$text = preg_replace( '/ +/', ' ', $text ); // ������� ������ �������

	$text = str_replace( '&quot;','"', $text ); // �������� &quot �� "
	$text = str_replace( '&#39;',"'", $text ); // �������� &#39 �� '

	// ���� (�������, ������ � �������)
	if( $botParams->get( 'angle' ) )
	{
		$text = preg_replace( '/((?>\d{1,3})) ?� ?((?>\d{1,2})) ?\' ?((?>\d{1,2})) ?"/','\\1��\\2&prime;�\\3&Prime;', $text ); // 10� 11' 12"
		$text = preg_replace( '/((?>\d{1,3})) ?� ?((?>\d{1,2})) ?\'/','\\1��\\2&prime;', $text ); // 10� 11'
		$text = preg_replace( '/((?>\d{1,3})) �(?![^Cc��F])/','\\1�', $text ); // 10�, �� �� 10��C
		$text = preg_replace( '/((?>\d{1,2})) ?\' ?((?>\d{1,2})) ?"/','\\1&prime;�\\2&Prime;', $text ); // 11' 12"
		$text = preg_replace( '/((?>\d{1,2})) \'/','\\1&prime;', $text ); // 11'
	}

// ����������� �������
	if( $botParams->get( 'quotes' ) )
	{
		$text = preg_replace( '/(['.TAGEND.'\( �]|^)"([^"]*)([^ �"\(])"/', '\\1�\\2\\3�', $text ); // ����������� �������-"������"
		if( stristr( $text, '"' ) ) // ���� ���� ��������� �������
		{
			$text = preg_replace( '/(['.TAGEND.'( �]|^)"([^"]*)([^ �"(])"/', '\\1�\\2\\3�', $text );
			while( preg_match( '/�[^�]*�[^�]*�/', $text ) )
				$text = preg_replace( '/�([^�]*)�([^�]*)�/', '�\\1�\\2�', $text );
		}
	}

// ������ ������
//	$text = str_replace( '� ','�'.NOBRSPACE, $text ); // ����� (��� �������)
	if( $botParams->get( 'dash' ) )
	{ // ����
		$text = str_replace( ' - ',NOBRSPACE.DASH.' ', $text );
		$text = str_replace( '�- ',NOBRSPACE.DASH.' ', $text );
	}
	if( $botParams->get( 'dots' ) ) $text = str_replace( '...','�', $text ); // ����������
	if( $botParams->get( 'plusminus' ) ) $text = str_replace( '+/-','�', $text ); // ����-�����

	if( $botParams->get( 'registered' ) )
	{
		$text = str_replace( '(r)','<sup>�</sup>', $text );
		$text = str_replace( '(R)','<sup>�</sup>', $text ); // registered
	}
	if( $botParams->get( 'copyright' ) ) $text = preg_replace( '/\((c|C|�|�)\)/','�', $text );
	if( $botParams->get( 'nobrcopyright' ) ) $text = str_replace( '� ','�'.NOBRSPACE, $text ); // copyright
	if( $botParams->get( 'trademark' ) )
	{ // trademark
		$text = str_replace( '(tm)','�', $text );
		$text = str_replace( '(TM)','�', $text );
	}
	
	if( $botParams->get( 'lgequals' ) )
	{
		$text = str_replace( '&lt;=','&le;', $text ); // ������/�����
		$text = str_replace( '&gt;=','&ge;', $text ); // ������/�����
	}

// �����
	if( $botParams->get( 'frac' ) ) 
	{
		$text = preg_replace( '/(^|[ �("��])1\/2(?=$|[ �)"��.,!?:;�])/','\\1&frac12;', $text);
		$text = preg_replace( '/(^|[ �("��])1\/4(?=$|[ �)"��.,!?:;�])/','\\1&frac14;', $text);
		$text = preg_replace( '/(^|[ �("��])3\/4(?=$|[ �)"��.,!?:;�])/','\\1&frac34;', $text);
	}
	if( $botParams->get( 'fracext' ) ) 
	{
		$text = preg_replace( '/(^|[ �("��])1\/3(?=$|[ �)"��.,!?:;�])/','\\1&#8531;', $text);
		$text = preg_replace( '/(^|[ �("��])2\/3(?=$|[ �)"��.,!?:;�])/','\\1&#8532;', $text);
		$text = preg_replace( '/(^|[ �("��])1\/8(?=$|[ �)"��.,!?:;�])/','\\1&#8539;', $text);
		$text = preg_replace( '/(^|[ �("��])3\/8(?=$|[ �)"��.,!?:;�])/','\\1&#8540;', $text);
		$text = preg_replace( '/(^|[ �("��])5\/8(?=$|[ �)"��.,!?:;�])/','\\1&#8541;', $text);
		$text = preg_replace( '/(^|[ �("��])7\/8(?=$|[ �)"��.,!?:;�])/','\\1&#8542;', $text);
	}

// �������� � �������
	if( $botParams->get( 'initials' ) )
	{
		$text = preg_replace( '/(?<=[^�-��-߸�a-zA-Z][�-ߨA-Z]\.|^[�-ߨA-Z]\.) ?([�-ߨA-Z]\.) ?(?=[�-ߨA-Z][�-��-߸�a-zA-Z])/', THINSP.'\\1'.NOBRSPACE, $text ); // �������� + �������
		$text = preg_replace( '/((?>[�-ߨA-Z][�-��-߸�a-zA-Z]+)) ([�-ߨA-Z]\.) ?(?=[�-ߨA-Z]\.)/', '\\1'.NOBRSPACE.'\\2'.THINSP, $text ); // ������� + ��������
		$text = preg_replace( '/(?<=[^�-��-߸�a-zA-Z][�-ߨA-Z]\.|^[�-ߨA-Z]\.) ?(?=[�-ߨA-Z][�-��-߸�a-zA-Z])/', NOBRSPACE, $text ); // ������� + �������
		$text = preg_replace( '/((?>[�-ߨA-Z][�-��-߸�a-zA-Z]+)) (?=[�-ߨA-Z]\.)/', '\\1'.NOBRSPACE, $text ); // ������� + �������
	}

// ����������
	if( $botParams->get( 'abr' ) )
	{
		$text = preg_replace( '/([^�-��-߸�a-zA-Z]|^)(�\.|��\.|���\.|��\.|���\.|�\.|����\.|���\.|����\.|��\.) ?(?=[�-ߨ])/', '\\1\\2'.NOBRSPACE, $text ); // ����������
		$text = preg_replace( '/([^�-��-߸�a-zA-Z]|^)(�\.|���\.|���\.|��\.|���\.|����\.|��\.|���|�.\|����|��\.|���\.) ?(?=\d)/', '\\1\\2'.NOBRSPACE, $text ); // ����������
		$text = preg_replace( '/([^�-��-߸�a-zA-Z]|^)(��\.|��\.|���\.) ?(?=[�-��-߸�a-zA-Z\d])/', '\\1\\2'.NOBRSPACE, $text ); // ����������
	}
	
// ������� ���������
	if( $botParams->get( 'units' ) )
	{
		$text = preg_replace( '/([�-��a-z\d\.]) (?=���\.|���\.|���\.|����\.|���\.|���\.|�\.�\.|\$|�)/', '\\1'.NOBRSPACE, $text ); // ������� ���������
		$text = preg_replace( '/([�-��a-z\d\.]) (?=����([ \.,!\?:;]|$))/', '\\1'.NOBRSPACE, $text ); // ����
	}

// ����������� �������� �����
	if( $botParams->get( 'shortwords' ) )
	{
		$text = preg_replace( '/([^�-��-߸�a-zA-Z0-9])(�|��|��|��|��|��|��|��|��|�|��|��|��|�|�|�|��|�|��|��|�|��|��|��|��|��|�|��|��|��|�) (?=[�-��-߸�a-zA-Z]{3})/', '\\1\\2'.NOBRSPACE, $text ); // �������� ����� ����������� � ��������� (���� �� ���� �� ��������)
		$text = preg_replace( '/([�-��-߸�]) (?=(��|�|��|��|��|�|��|��)([\.,!\?:;])?( |$))/', '\\1'.NOBRSPACE, $text ); // �������
		$text = preg_replace( '/([.!?�] [�-ߨA-Z][�-��-߸�a-zA-Z]{0,3}) /', '\\1'.NOBRSPACE, $text ); // ����� �� 1 �� 3 ���� � ������ �����������
	}

// � �.�., � �.�., �.�., ...
	if( $botParams->get( 'etc' ) )
	{
		$text = preg_replace( '/([^�-��-߸�a-zA-Z]�) (�|�)(?=�\.)/', '\\1'.NOBRSPACE.'\\2', $text ); // � ��., � ��.
		$text = preg_replace( '/([^�-��-߸�a-zA-Z]�) �\. ?(?=(�|�)\.)/', '\\1'.NOBRSPACE.'�.'.THINSP, $text ); // � �.�., � �.�.
		$text = preg_replace( '/([^�-��-߸�a-zA-Z]�) �\. ?(?=�\.)/', '\\1'.NOBRSPACE.'�.'.THINSP, $text ); // � �.�.
		$text = preg_replace( '/([^�-��-߸�a-zA-Z]�\.) ?(?=(�|�|�)\.)/', '\\1'.THINSP, $text ); // �.�., �.�., �.�.
		$text = preg_replace( '/([^�-��-߸�a-zA-Z](?=�|�)\.) ?(�\.-�|�|�|�|�|�)\. ?(?=�\.)/', '\\1'.THINSP.'\\2.'.THINSP, $text ); // �.�.�., �.�.-�.�., ...
	}

// ����������� �������������� ������
	if( $botParams->get( 'spinbracket' ) ) $text = preg_replace( '/\( *([^)]+?) *\)/', '(\\1)', $text ); // ������� ������� ����� ����������� ������ � ����� ���������� �������
	if( $botParams->get( 'spbeforebracket' ) ) $text = preg_replace( '/([�-��-߸�a-zA-Z.,!?:;�])\(/', '\\1 (', $text ); // ��������� ������ ����� ������ � ����������� �������, ���� ��� ��� (���������, ���� � ��� �� ����� ���� �������)
	if( $botParams->get( 'commanum' ) ) $text = preg_replace( '/([�-��-߸�a-zA-Z]),(?=\d)/','\\1, ', $text); // ������ �������� � ����������� ������� � ������ �����,����� �� �����, ����� (��. ������, 46)
	if( $botParams->get( 'dotdash' ) )
	{ // ���� �� ������� � ����� �� ����������
		$text = str_replace( ','.NOBRSPACE.DASH.' ',','.DASH.' ', $text );
		$text = str_replace( '.'.NOBRSPACE.DASH.' ','.'.DASH.' ', $text );
	}
	if( $botParams->get( 'exclquest' ) ) $text = str_replace( '!?','?!', $text ); // ��������� � ����� �������
	if( $botParams->get( 'exclquestdots' ) ) $text = preg_replace( '/(!|\?)(?:�|\.\.\.)/','\\1..', $text ); // ������� ������ �����
	if( $botParams->get( 'sppunct' ) ) $text = preg_replace( '/ (?=[.,!?:;])/','', $text ); // ������� ������� ����� ������� ����������
	if( $botParams->get( 'nummarksp' ) ) $text = preg_replace( '/(�|�) ?(?=\d)/', '\\1�', $text ); // ������ ����� ������ "�" ��� "�" � ������.
	if( $botParams->get( 'numnummark' ) ) $text = str_replace( '� �', '��', $text ); // ������� ��������� "��"
	if( $botParams->get( 'sectsectmark' ) ) $text = str_replace( '� �', '��', $text ); // ������� ��������� "��"
	//TODO: ������� ����� ���������� (.,:;) ��� �������, � (!?�) � �������

// �Ѩ � ������
	if( $botParams->get( 'sizes' ) ) $text = preg_replace( '/(\d) *(?:\*|�|x|X|�) *(?=\d)/', '\\1&times;', $text ); // ������������ ��������� ����������� (200x500)
	// ������ ������������ ������ ���������
	if( $botParams->get( 'phonenum' ) )
	{
		$text = preg_replace( '/(\+7|8) ?(\(\d+\)) ?(\d+)-(\d{2})-(?=\d{2})/','\\1'.NOBRSPACE.'\\2'.NOBRSPACE.'\\3'.NOBRHYPHEN.'\\4'.NOBRHYPHEN, $text );
		$text = preg_replace( '/(\(\d+\)) ?(\d+)-(\d{2})-(?=\d{2})/','\\1'.NOBRSPACE.'\\2'.NOBRHYPHEN.'\\3'.NOBRHYPHEN, $text );
		$text = preg_replace( '/(\d+)-(\d{2})-(?=\d{2})/','\\1'.NOBRHYPHEN.'\\2'.NOBRHYPHEN, $text );
	}
	if( $botParams->get( 'interval' ) )
	{ // ������������ �������� ��������� ��������
		$text = preg_replace( '/((?>\d+))-(?=(?>\d+)([ .,!?:;�]|$))/','\\1'.NUMDASH, $text );
		$text = preg_replace( '/((?>[IVXLCDM]+))-(?=(?>[IVXLCDM]+)([ .,!?:;�]|$))/','\\1'.NUMDASH, $text );
	}
	if( $botParams->get( 'minus' ) ) $text = preg_replace( '/ -(?=\d)/',' &minus;', $text ); // ����� ����� �������
	if( $botParams->get( 'numnbsp' ) ) $text = preg_replace( '/([ '.DASH.NUMDASH.']|^)((?>\d+)) /','\\1\\2'.NOBRSPACE, $text ); // ����������� ������ ����� �������� ����
	if( $botParams->get( 'romenbsp' ) ) $text = preg_replace( '/([ '.DASH.NUMDASH.']|^)((?>[IVXLCDM]+)) /','\\1\\2'.NOBRSPACE, $text ); // ����������� ������ ����� ������� ����
	//TODO: ����������� ������ � ������������ ���� 10 �� � �.�. (���� ���������� ������� ���������)
	//TODO: ��������� ����������� ������ ����� ������ � ����������� ������������, ����� �� ���� 1�� (������ ������ ��� ������������ ���������� ������������...)
	if( $botParams->get( 'deg' ) ) $text = preg_replace( '/([-+]?(?>\d+)(?:[.,](?>\d+))?)[ �'.NOBRSPACE.']?[C�]\b/','\\1&deg;�C', $text); // �������� C � ������������ �������� �� �C
	if( $botParams->get( 'percent' ) ) $text = preg_replace( '/(\d)[ �'.NOBRSPACE.'](?=%|�)/','\\1', $text); // ����� �������� (%) � �������� (�) ����������� � ������, � ������� ��� ���������
	if( $botParams->get( 'numnum' ) ) $text = preg_replace( '/(\d) (?=\d)/','\\1'.NOBRSPACE, $text ); // �� ��������� 25 000

// ������
	if( $botParams->get( 'orgs' ) ) $text = preg_replace( '/(���|���|���|��) ?(?="|�)/','\\1'.NOBRSPACE, $text); // ������ ������������ �������� ����������� � ����������� ����� �������������
	if( $botParams->get( 'doubleword' ) ) $text = preg_replace( '/([^�-��-߸�a-zA-Z][�-��-߸�a-zA-Z]{1,8})-(?=[�-��-߸�a-zA-Z]{1,8}[^�-��-߸�a-zA-Z])/','\\1'.NOBRHYPHEN, $text); // ������ ������������ ������� ����� (������-�������, ������� ������-��������)
	if( $botParams->get( 'htmlents' ) ) $text = strtr( $text, array_flip( $htmlents ) ); // ������ �������� ������ �� html-entity
	if( $botParams->get( 'xmlcomp' ) )
	{
		$text = str_replace( '"','&quot;', $text ); // �������� " �� &quot;
		$text = str_replace( "'",'&#39;', $text ); // �������� ' �� &#39;
	}

// �������� ������
	switch( $botParams->get( 'typethinsp' ) )
	{
	case 1:
		$text = str_replace( THINSP,NOBRSPACE, $text ); break;
	case 2:
		$text = str_replace( THINSP,'�', $text ); break;
	case 3:
		$text = str_replace( THINSP,'&thinsp;', $text ); break;
	case 0:
	default:
		$text = str_replace( THINSP,'', $text );
	}
	
// ����������� ������
	switch( $botParams->get( 'typenbsp' ) )
	{
	case 1:
		$text = preg_replace( '/(^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.']+['.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.'][^ '.TAGBEGIN.']*)(?=$| |'.TAGBEGIN.')/','\\1<nobr>\\2</nobr>', $text );
		$text = str_replace( NOBRSPACE,' ', $text );
		break;
	case 2:
		$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.']+['.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.'][^ '.TAGBEGIN.']*)(?=$| |'.TAGBEGIN.')/','<span style="white-space:nowrap">\\1</span>', $text );
		$text = str_replace( NOBRSPACE,' ', $text );
		break;
	case 0:
	default:
		$text = str_replace( NOBRSPACE,'�', $text );
	}

// ����������� ���� � ����� (���� NOBRSPACE=&nbsp;)
	if( $botParams->get( 'typenbsp' )==0 )
		$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRHYPHEN.DASH.NUMDASH.']+['.NOBRHYPHEN.DASH.NUMDASH.'][^ '.TAGBEGIN.']+)(?=$| |'.TAGBEGIN.')/','<nobr>\\1</nobr>', $text );
//	$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRHYPHEN.DASH.']+['.NOBRHYPHEN.DASH.'][^ '.TAGBEGIN.']+)(?=$| |'.TAGBEGIN.')/','<span style="white-space:nowrap">\\1</span>', $text );

// ����������� �����
	$text = str_replace( NOBRHYPHEN,'-', $text );

// ����
	switch( $botParams->get( 'typedash' ) )
	{
	case 1:
		$text = str_replace( DASH,'�', $text ); break; // ndash
	case 2:
		$text = str_replace( DASH,'&minus;', $text ); break; // minus
	case 3:
		$text = str_replace( DASH,'-', $text ); break; // hyphen
	case 0:
	default:
		$text = str_replace( DASH,'�', $text ); // mdash
	}
// ���� � ���������
	switch( $botParams->get( 'typenumdash' ) )
	{
	case 0:
		$text = str_replace( NUMDASH,'�', $text ); break; // mdash
	case 2:
		$text = str_replace( NUMDASH,'&minus;', $text ); break; // minus
	case 3:
		$text = str_replace( NUMDASH,'-', $text ); break; // hyphen
	case 1:
	default:
		$text = str_replace( NUMDASH,'�', $text ); // ndash
	}
	
// ���������� ���� �� �����
	while(preg_match('/'.TAGBEGIN.'\d+'.TAGEND.'/', $text))
		$text = preg_replace_callback('/'.TAGBEGIN.'(\d+)'.TAGEND.'/', 'getTag', $text);

// ������ � ������. ����� 2
	if( $botParams->get( 'apunct' ) )
	{//��������� � �������� ������� � ����� ���������� ������ ������ ������ �������� �� ������� ������.
		$text = preg_replace( '/<a +href([^>]*)>([ .,!?:;�]+)/', '\\2<a href\\1>', $text );
		$text = preg_replace( '/(!\.\.|\?\.\.)<\/a>/', '</a>\\1', $text );
		$text = preg_replace( '/([ .,!?:;�]+)<\/a>/', '</a>\\1', $text );
	}
	if( $botParams->get( 'indices' ) ) $text = str_replace( ' <su', '<su', $text ); // �� �������� ������� � ������ ������� �� ���������� ��������

	return trim($text);
}


function botRusTypo( $published, &$row, &$params, $page=0 )
{
	global $database, $_MAMBOTS;
	if($published)
	{
		if(!isset($_MAMBOTS->_content_mambot_params['rustypo']))
		{
			$query = "SELECT params FROM #__mambots WHERE element = 'rustypo' AND folder = 'content'";
			$database->setQuery( $query );
			$database->loadObject( $mambot );
			$_MAMBOTS->_content_mambot_params['rustypo'] = $mambot;
		}
		$mambot = $_MAMBOTS->_content_mambot_params['rustypo'];
	 	$botParams = new mosParameters( $mambot->params );

		$botParams->def( 'titles', 1 );

		$botParams->def( 'typenbsp', 1 );
		$botParams->def( 'typethinsp', 1 );
		$botParams->def( 'typedash', 0 );
		$botParams->def( 'typenumdash', 1 );

	 	$botParams->def( 'quotes', 1 );
	 	$botParams->def( 'dash', 1 );
	 	$botParams->def( 'speech', 1 );

	 	$botParams->def( 'initials', 1 );
		$botParams->def( 'abr', 1 );
		$botParams->def( 'units', 1 );
		$botParams->def( 'shortatend', 1 );
		$botParams->def( 'shortwords', 1 );
		$botParams->def( 'etc', 1 );
		$botParams->def( 'nobrcopyright', 1 );
		$botParams->def( 'orgs', 1 );
		$botParams->def( 'doubleword', 1 );
		$botParams->def( 'phonenum', 1 );

		$botParams->def( 'htmlents', 0 );
		$botParams->def( 'xmlcomp', 0 );
		$botParams->def( 'dots', 1 );
		$botParams->def( 'plusminus', 1 );
		$botParams->def( 'lgequals', 1 );
		$botParams->def( 'frac', 1 );
		$botParams->def( 'fracext', 0 );
		$botParams->def( 'copyright', 1 );
		$botParams->def( 'registered', 1 );
		$botParams->def( 'trademark', 1 );

		$botParams->def( 'aquotes', 1 );
		$botParams->def( 'apunct', 1 );
		$botParams->def( 'indices', 1 );
		$botParams->def( 'spacesatend', 1 );
		$botParams->def( 'spinbracket', 1 );
		$botParams->def( 'spbeforebracket', 1 );
		$botParams->def( 'commanum', 1 );
		$botParams->def( 'dotdash', 1 );
		$botParams->def( 'exclquest', 1 );
		$botParams->def( 'exclquestdots', 1 );
		$botParams->def( 'sppunct', 1 );
		$botParams->def( 'nummarksp', 1 );
		$botParams->def( 'numnummark', 1 );
		$botParams->def( 'sectsectmark', 1 );

		$botParams->def( 'sizes', 1 );
		$botParams->def( 'interval', 1 );
		$botParams->def( 'minus', 1 );
		$botParams->def( 'numnbsp', 1 );
		$botParams->def( 'romenbsp', 1 );
		$botParams->def( 'deg', 1 );
		$botParams->def( 'percent', 1 );
		$botParams->def( 'numnum', 1 );
		$botParams->def( 'angle', 1 );

		if( $botParams->get( 'titles' ) && isset($row->title) )
			$row->title = Proof( $row->title, $botParams );
		if( isset($row->text) )
			$row->text  = Proof( $row->text,  $botParams );
	}
	return true;
}

?>