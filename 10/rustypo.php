<?php
/********************************************
* RussianTypography mambot for Mambo/Joomla *
* Copyright (C) 2006-2011 by Denis Ryabov   *
* Email      : dryabov@yandex.ru            *
* Version    : 2.0.4                        *
* License    : Released under GPL           *
********************************************/

// ћетод "выкусывани€" тегов вз€т из статьи http://www.softportal.com/articles/item_txt.php?id=208.
// ¬ ней указан источник http://spectator.ru/, но на этом сайте данна€ стать€ обнаружена не была...

// ћетод расстановки кавычек Ч ©spectator.ru


defined( '_VALID_MOS' ) or die( 'ƒоступ запрещен.' );

$_MAMBOTS->registerFunction( 'onPrepareContent', 'botRusTypo' );


define('TAGBEGIN', "\x01");
define('TAGEND',   "\x02");
$Refs = array(); // буфер дл€ хранени€ тегов
$RefsCntr = 0;   // счЄтчик буфера
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
		'&#8222;'=>'Д','&#8219;'=>'У','&#8220;'=>'Ф','&#8216;'=>'С','&#8217;'=>'Т',
		'&laquo;'=>'Ђ','&raquo;'=>'ї','&hellip;'=>'Е','&euro;'=>'И','&permil;'=>'Й',
		'&bull;'=>'Х','&middot;'=>'Ј','&ndash;'=>'Ц','&mdash;'=>'Ч','&nbsp;'=>'†',
		'&trade;'=>'Щ','&copy;'=>'©','&reg;'=>'Ѓ','&sect;'=>'І','&#8470;'=>'є',
		'&plusmn;'=>'±','&deg;'=>'∞');
	$text = strtr( $text, $htmlents ); // ƒелаем замены html entity на символы из cp1251

// –јЅќ“ј — “≈√јћ». „ј—“№ 1
	if( $botParams->get( 'spacesatend' ) ) $text = preg_replace( '/(?> |†)+(?=$|<br|<\/p)/', '', $text ); // ”бираем лишние пробелы перед концом строки
	if( $botParams->get( 'aquotes' ) ) $text = preg_replace( '/<a +href([^>]*)> *(?:"|&quot;)([^<"]*)(?:"|&quot;) *<\/a>/', '"<a href\\1>\\2</a>"', $text ); // ¬ыносим кавычки из ссылок
	if( $botParams->get( 'shortatend' ) ) $text = preg_replace( '/([а-€ј-яa-zA-Z]) ([а-€ј-яa-zA-Z]{1,5}(?>[.!?Е]*))(?=$|<\/p>|<\/div>|<br>|<br \/>)/','\\1'.NOBRSPACE.'\\2', $text); // ѕоследнее короткое слово в абзаце прив€зывать к предыдущему

//ѕ–яћјя –≈„№
	if( $botParams->get( 'speech' ) ) $text = preg_replace( '/(^|<p>|<br>|<br \/>)[ †]?- /','\\1Ч†', $text ); // ѕр€ма€ речь - дефис в начале строки и после тегов <p>, <br> и <br />

// ¬џ–≈«ј≈ћ “≈√»
	$Refs = array();
	$RefsCntr = 0;
	$text = preg_replace_callback('/<!--.*?-->/s', 'putTag', $text); // комментарии
	$text = preg_replace_callback('/< *(script|style|pre|code|textarea).*?>.*?< *\/ *\1 *>/is', 'putTag', $text); // теги, которые вырезаютс€ вместе с содержимым
	$text = preg_replace_callback('/<(?:[^\'"\>]+|".*?"|\'.*?\')+>/s', 'putTag', $text); // обычные теги

	$text = strtr( $text, "\t\n\r", '   ' ); // «амен€ем табулюцию и перевод строки на пробел
	$text = preg_replace( '/ +/', ' ', $text ); // ”бираем лишние пробелы

	$text = str_replace( '&quot;','"', $text ); // «амен€ем &quot на "
	$text = str_replace( '&#39;',"'", $text ); // «амен€ем &#39 на '

	// ”√Ћџ (√–јƒ”—џ, ћ»Ќ”“џ » —≈ ”Ќƒџ)
	if( $botParams->get( 'angle' ) )
	{
		$text = preg_replace( '/((?>\d{1,3})) ?∞ ?((?>\d{1,2})) ?\' ?((?>\d{1,2})) ?"/','\\1∞†\\2&prime;†\\3&Prime;', $text ); // 10∞ 11' 12"
		$text = preg_replace( '/((?>\d{1,3})) ?∞ ?((?>\d{1,2})) ?\'/','\\1∞†\\2&prime;', $text ); // 10∞ 11'
		$text = preg_replace( '/((?>\d{1,3})) ∞(?![^Cc—сF])/','\\1∞', $text ); // 10∞, но не 10†∞C
		$text = preg_replace( '/((?>\d{1,2})) ?\' ?((?>\d{1,2})) ?"/','\\1&prime;†\\2&Prime;', $text ); // 11' 12"
		$text = preg_replace( '/((?>\d{1,2})) \'/','\\1&prime;', $text ); // 11'
	}

// –ј——“ј¬Ћя≈ћ  ј¬џ„ »
	if( $botParams->get( 'quotes' ) )
	{
		$text = preg_replace( '/(['.TAGEND.'\( †]|^)"([^"]*)([^ †"\(])"/', '\\1Ђ\\2\\3ї', $text ); // –асстановка кавычек-"елочек"
		if( stristr( $text, '"' ) ) // ≈сли есть вложенные кавычки
		{
			$text = preg_replace( '/(['.TAGEND.'( †]|^)"([^"]*)([^ †"(])"/', '\\1Ђ\\2\\3ї', $text );
			while( preg_match( '/Ђ[^ї]*Ђ[^ї]*ї/', $text ) )
				$text = preg_replace( '/Ђ([^ї]*)Ђ([^ї]*)ї/', 'Ђ\\1Д\\2У', $text );
		}
	}

// ƒ≈Ћј≈ћ «јћ≈Ќџ
//	$text = str_replace( 'Х ','Х'.NOBRSPACE, $text ); // ѕункт (дл€ списков)
	if( $botParams->get( 'dash' ) )
	{ // “ире
		$text = str_replace( ' - ',NOBRSPACE.DASH.' ', $text );
		$text = str_replace( '†- ',NOBRSPACE.DASH.' ', $text );
	}
	if( $botParams->get( 'dots' ) ) $text = str_replace( '...','Е', $text ); // ћноготочие
	if( $botParams->get( 'plusminus' ) ) $text = str_replace( '+/-','±', $text ); // плюс-минус

	if( $botParams->get( 'registered' ) )
	{
		$text = str_replace( '(r)','<sup>Ѓ</sup>', $text );
		$text = str_replace( '(R)','<sup>Ѓ</sup>', $text ); // registered
	}
	if( $botParams->get( 'copyright' ) ) $text = preg_replace( '/\((c|C|с|—)\)/','©', $text );
	if( $botParams->get( 'nobrcopyright' ) ) $text = str_replace( '© ','©'.NOBRSPACE, $text ); // copyright
	if( $botParams->get( 'trademark' ) )
	{ // trademark
		$text = str_replace( '(tm)','Щ', $text );
		$text = str_replace( '(TM)','Щ', $text );
	}
	
	if( $botParams->get( 'lgequals' ) )
	{
		$text = str_replace( '&lt;=','&le;', $text ); // ћеньше/равно
		$text = str_replace( '&gt;=','&ge;', $text ); // Ѕольше/равно
	}

// ƒ–ќЅ»
	if( $botParams->get( 'frac' ) ) 
	{
		$text = preg_replace( '/(^|[ †("ЂД])1\/2(?=$|[ †)"їУ.,!?:;Е])/','\\1&frac12;', $text);
		$text = preg_replace( '/(^|[ †("ЂД])1\/4(?=$|[ †)"їУ.,!?:;Е])/','\\1&frac14;', $text);
		$text = preg_replace( '/(^|[ †("ЂД])3\/4(?=$|[ †)"їУ.,!?:;Е])/','\\1&frac34;', $text);
	}
	if( $botParams->get( 'fracext' ) ) 
	{
		$text = preg_replace( '/(^|[ †("ЂД])1\/3(?=$|[ †)"їУ.,!?:;Е])/','\\1&#8531;', $text);
		$text = preg_replace( '/(^|[ †("ЂД])2\/3(?=$|[ †)"їУ.,!?:;Е])/','\\1&#8532;', $text);
		$text = preg_replace( '/(^|[ †("ЂД])1\/8(?=$|[ †)"їУ.,!?:;Е])/','\\1&#8539;', $text);
		$text = preg_replace( '/(^|[ †("ЂД])3\/8(?=$|[ †)"їУ.,!?:;Е])/','\\1&#8540;', $text);
		$text = preg_replace( '/(^|[ †("ЂД])5\/8(?=$|[ †)"їУ.,!?:;Е])/','\\1&#8541;', $text);
		$text = preg_replace( '/(^|[ †("ЂД])7\/8(?=$|[ †)"їУ.,!?:;Е])/','\\1&#8542;', $text);
	}

// »Ќ»÷»јЋџ » ‘јћ»Ћ»»
	if( $botParams->get( 'initials' ) )
	{
		$text = preg_replace( '/(?<=[^а-€ј-яЄ®a-zA-Z][ј-я®A-Z]\.|^[ј-я®A-Z]\.) ?([ј-я®A-Z]\.) ?(?=[ј-я®A-Z][а-€ј-яЄ®a-zA-Z])/', THINSP.'\\1'.NOBRSPACE, $text ); // »нициалы + фамили€
		$text = preg_replace( '/((?>[ј-я®A-Z][а-€ј-яЄ®a-zA-Z]+)) ([ј-я®A-Z]\.) ?(?=[ј-я®A-Z]\.)/', '\\1'.NOBRSPACE.'\\2'.THINSP, $text ); // ‘амили€ + инициалы
		$text = preg_replace( '/(?<=[^а-€ј-яЄ®a-zA-Z][ј-я®A-Z]\.|^[ј-я®A-Z]\.) ?(?=[ј-я®A-Z][а-€ј-яЄ®a-zA-Z])/', NOBRSPACE, $text ); // »нициал + фамили€
		$text = preg_replace( '/((?>[ј-я®A-Z][а-€ј-яЄ®a-zA-Z]+)) (?=[ј-я®A-Z]\.)/', '\\1'.NOBRSPACE, $text ); // ‘амили€ + инициал
	}

// —ќ –јў≈Ќ»я
	if( $botParams->get( 'abr' ) )
	{
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z]|^)(г\.|ул\.|пер\.|пл\.|пос\.|р\.|проф\.|доц\.|акад\.|гр\.) ?(?=[ј-я®])/', '\\1\\2'.NOBRSPACE, $text ); // —окращени€
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z]|^)(с\.|стр\.|рис\.|гл\.|илл\.|табл\.|кв\.|дом|д.\|офис|оф\.|ауд\.) ?(?=\d)/', '\\1\\2'.NOBRSPACE, $text ); // —окращени€
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z]|^)(см\.|им\.|каф\.) ?(?=[а-€ј-яЄ®a-zA-Z\d])/', '\\1\\2'.NOBRSPACE, $text ); // —окращени€
	}
	
// ≈ƒ»Ќ»÷џ »«ћ≈–≈Ќ»я
	if( $botParams->get( 'units' ) )
	{
		$text = preg_replace( '/([а-€Єa-z\d\.]) (?=экз\.|тыс\.|млн\.|млрд\.|руб\.|коп\.|у\.е\.|\$|И)/', '\\1'.NOBRSPACE, $text ); // ≈диницы измерени€
		$text = preg_replace( '/([а-€Єa-z\d\.]) (?=евро([ \.,!\?:;]|$))/', '\\1'.NOBRSPACE, $text ); // ≈вро
	}

// ѕ–»¬я«џ¬ј≈ћ  ќ–ќ“ »≈ —Ћќ¬ј
	if( $botParams->get( 'shortwords' ) )
	{
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z0-9])(€|ты|мы|вы|он|не|ни|на|но|в|во|до|от|и|а|с|со|о|об|ну|к|ко|за|их|из|ее|еЄ|ей|ой|ай|у) (?=[а-€ј-яЄ®a-zA-Z]{3})/', '\\1\\2'.NOBRSPACE, $text ); //  ороткие слова прикрепл€ем к следующим (если те сами не короткие)
		$text = preg_replace( '/([а-€ј-яЄ®]) (?=(же|ж|ли|ль|бы|б|ка|то)([\.,!\?:;])?( |$))/', '\\1'.NOBRSPACE, $text ); // „астицы
		$text = preg_replace( '/([.!?Е] [ј-я®A-Z][а-€ј-яЄ®a-zA-Z]{0,3}) /', '\\1'.NOBRSPACE, $text ); // —лова от 1 до 3 букв в начале предложени€
	}

// » “.ƒ., » “.ѕ., “. ., ...
	if( $botParams->get( 'etc' ) )
	{
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z]и) (д|п)(?=р\.)/', '\\1'.NOBRSPACE.'\\2', $text ); // и др., и пр.
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z]и) т\. ?(?=(д|п)\.)/', '\\1'.NOBRSPACE.'т.'.THINSP, $text ); // и т.д., и т.п.
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z]в) т\. ?(?=ч\.)/', '\\1'.NOBRSPACE.'т.'.THINSP, $text ); // в т.ч.
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z]т\.) ?(?=(к|н|е)\.)/', '\\1'.THINSP, $text ); // т.к., т.н., т.е.
		$text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z](?=к|д)\.) ?(ф\.-м|х|б|т|ф|п)\. ?(?=н\.)/', '\\1'.THINSP.'\\2.'.THINSP, $text ); // к.т.н., д.ф.-м.н., ...
	}

// »—ѕ–ј¬Ћ≈Ќ»≈ √–јћћј“»„≈— »’ ќЎ»Ѕќ 
	if( $botParams->get( 'spinbracket' ) ) $text = preg_replace( '/\( *([^)]+?) *\)/', '(\\1)', $text ); // удал€ем пробелы после открывающей скобки и перед закрыващей скобкой
	if( $botParams->get( 'spbeforebracket' ) ) $text = preg_replace( '/([а-€ј-яЄ®a-zA-Z.,!?:;Е])\(/', '\\1 (', $text ); // добавл€ем пробел между словом и открывающей скобкой, если его нет (отключите, если у ¬ас на сайте есть формулы)
	if( $botParams->get( 'commanum' ) ) $text = preg_replace( '/([а-€ј-яЄ®a-zA-Z]),(?=\d)/','\\1, ', $text); // ƒелает проверку в расстановке зап€тых и мен€ет слово,число на слово, число (ул. ƒружбы, 46)
	if( $botParams->get( 'dotdash' ) )
	{ // “ире от зап€той и точки не отбиваетс€
		$text = str_replace( ','.NOBRSPACE.DASH.' ',','.DASH.' ', $text );
		$text = str_replace( '.'.NOBRSPACE.DASH.' ','.'.DASH.' ', $text );
	}
	if( $botParams->get( 'exclquest' ) ) $text = str_replace( '!?','?!', $text ); // ѕравильно в таком пор€дке
	if( $botParams->get( 'exclquestdots' ) ) $text = preg_replace( '/(!|\?)(?:Е|\.\.\.)/','\\1..', $text ); // ”бираем лишние точки
	if( $botParams->get( 'sppunct' ) ) $text = preg_replace( '/ (?=[.,!?:;])/','', $text ); // ”бираем пробелы перед знаками препинани€
	if( $botParams->get( 'nummarksp' ) ) $text = preg_replace( '/(є|І) ?(?=\d)/', '\\1†', $text ); // пробел между знаком "є" или "І" и числом.
	if( $botParams->get( 'numnummark' ) ) $text = str_replace( 'є є', 'єє', $text ); // слитное написание "єє"
	if( $botParams->get( 'sectsectmark' ) ) $text = str_replace( 'І І', 'ІІ', $text ); // слитное написание "ІІ"
	//TODO: ¬ыносим знаки препинани€ (.,:;) вне кавычек, а (!?Е) в кавычки

// ¬—® ќ „»—Ћј’
	if( $botParams->get( 'sizes' ) ) $text = preg_replace( '/(\d) *(?:\*|х|x|X|’) *(?=\d)/', '\\1&times;', $text ); // обрабатываем размерные конструкции (200x500)
	// ƒелает неразрывными номера телефонов
	if( $botParams->get( 'phonenum' ) )
	{
		$text = preg_replace( '/(\+7|8) ?(\(\d+\)) ?(\d+)-(\d{2})-(?=\d{2})/','\\1'.NOBRSPACE.'\\2'.NOBRSPACE.'\\3'.NOBRHYPHEN.'\\4'.NOBRHYPHEN, $text );
		$text = preg_replace( '/(\(\d+\)) ?(\d+)-(\d{2})-(?=\d{2})/','\\1'.NOBRSPACE.'\\2'.NOBRHYPHEN.'\\3'.NOBRHYPHEN, $text );
		$text = preg_replace( '/(\d+)-(\d{2})-(?=\d{2})/','\\1'.NOBRHYPHEN.'\\2'.NOBRHYPHEN, $text );
	}
	if( $botParams->get( 'interval' ) )
	{ // ќбрабатываем диапазон численных значений
		$text = preg_replace( '/((?>\d+))-(?=(?>\d+)([ .,!?:;Е]|$))/','\\1'.NUMDASH, $text );
		$text = preg_replace( '/((?>[IVXLCDM]+))-(?=(?>[IVXLCDM]+)([ .,!?:;Е]|$))/','\\1'.NUMDASH, $text );
	}
	if( $botParams->get( 'minus' ) ) $text = preg_replace( '/ -(?=\d)/',' &minus;', $text ); // ћинус перед цифрами
	if( $botParams->get( 'numnbsp' ) ) $text = preg_replace( '/([ '.DASH.NUMDASH.']|^)((?>\d+)) /','\\1\\2'.NOBRSPACE, $text ); // Ќеразрывный пробел после арабских цифр
	if( $botParams->get( 'romenbsp' ) ) $text = preg_replace( '/([ '.DASH.NUMDASH.']|^)((?>[IVXLCDM]+)) /','\\1\\2'.NOBRSPACE, $text ); // Ќеразрывный пробел после римских цифр
	//TODO: Ќеразрывный пробел в конструкци€х вида 10 кг и т.д. (если предыдущее правило отключено)
	//TODO: ¬ставл€ть неразрывный пробел между числом и сокращением размерностью, чтобы не было 1кг (причем только дл€ общеприн€тых сокращений размерностей...)
	if( $botParams->get( 'deg' ) ) $text = preg_replace( '/([-+]?(?>\d+)(?:[.,](?>\d+))?)[ †'.NOBRSPACE.']?[C—]\b/','\\1&deg;†C', $text); // «амен€ет C в конструкци€х градусов на ∞C
	if( $botParams->get( 'percent' ) ) $text = preg_replace( '/(\d)[ †'.NOBRSPACE.'](?=%|Й)/','\\1', $text); // «наки процента (%) и промилле (Й) прикрепл€ть к числам, к которым они относ€тс€
	if( $botParams->get( 'numnum' ) ) $text = preg_replace( '/(\d) (?=\d)/','\\1'.NOBRSPACE, $text ); // Ќе разрывать 25 000

// –ј«Ќќ≈
	if( $botParams->get( 'orgs' ) ) $text = preg_replace( '/(ќќќ|ќјќ|«јќ|„ѕ) ?(?="|Ђ)/','\\1'.NOBRSPACE, $text); // ƒелает неразрывными названи€ организаций и абревиатуру формы собственности
	if( $botParams->get( 'doubleword' ) ) $text = preg_replace( '/([^а-€ј-яЄ®a-zA-Z][а-€ј-яЄ®a-zA-Z]{1,8})-(?=[а-€ј-яЄ®a-zA-Z]{1,8}[^а-€ј-яЄ®a-zA-Z])/','\\1'.NOBRHYPHEN, $text); // ƒелает неразрывными двойные слова (светло-красный, фамилии »ванов-¬асильев)
	if( $botParams->get( 'htmlents' ) ) $text = strtr( $text, array_flip( $htmlents ) ); // ƒелаем обратные замены на html-entity
	if( $botParams->get( 'xmlcomp' ) )
	{
		$text = str_replace( '"','&quot;', $text ); // «амен€ем " на &quot;
		$text = str_replace( "'",'&#39;', $text ); // «амен€ем ' на &#39;
	}

//  ќ–ќ“ »… ѕ–ќЅ≈Ћ
	switch( $botParams->get( 'typethinsp' ) )
	{
	case 1:
		$text = str_replace( THINSP,NOBRSPACE, $text ); break;
	case 2:
		$text = str_replace( THINSP,'†', $text ); break;
	case 3:
		$text = str_replace( THINSP,'&thinsp;', $text ); break;
	case 0:
	default:
		$text = str_replace( THINSP,'', $text );
	}
	
// Ќ≈–ј«–џ¬Ќџ… ѕ–ќЅ≈Ћ
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
		$text = str_replace( NOBRSPACE,'†', $text );
	}

// Ќ≈–ј«–џ¬Ќџ≈ “»–≈ » ƒ≈‘»— (≈—Ћ» NOBRSPACE=&nbsp;)
	if( $botParams->get( 'typenbsp' )==0 )
		$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRHYPHEN.DASH.NUMDASH.']+['.NOBRHYPHEN.DASH.NUMDASH.'][^ '.TAGBEGIN.']+)(?=$| |'.TAGBEGIN.')/','<nobr>\\1</nobr>', $text );
//	$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRHYPHEN.DASH.']+['.NOBRHYPHEN.DASH.'][^ '.TAGBEGIN.']+)(?=$| |'.TAGBEGIN.')/','<span style="white-space:nowrap">\\1</span>', $text );

// Ќ≈–ј«–џ¬Ќџ… ƒ≈‘»—
	$text = str_replace( NOBRHYPHEN,'-', $text );

// “»–≈
	switch( $botParams->get( 'typedash' ) )
	{
	case 1:
		$text = str_replace( DASH,'Ц', $text ); break; // ndash
	case 2:
		$text = str_replace( DASH,'&minus;', $text ); break; // minus
	case 3:
		$text = str_replace( DASH,'-', $text ); break; // hyphen
	case 0:
	default:
		$text = str_replace( DASH,'Ч', $text ); // mdash
	}
// “»–≈ ¬ ƒ»јѕј«ќЌ≈
	switch( $botParams->get( 'typenumdash' ) )
	{
	case 0:
		$text = str_replace( NUMDASH,'Ч', $text ); break; // mdash
	case 2:
		$text = str_replace( NUMDASH,'&minus;', $text ); break; // minus
	case 3:
		$text = str_replace( NUMDASH,'-', $text ); break; // hyphen
	case 1:
	default:
		$text = str_replace( NUMDASH,'Ц', $text ); // ndash
	}
	
// ¬ќ«¬–јўј≈ћ “≈√» Ќј ћ≈—“ќ
	while(preg_match('/'.TAGBEGIN.'\d+'.TAGEND.'/', $text))
		$text = preg_replace_callback('/'.TAGBEGIN.'(\d+)'.TAGEND.'/', 'getTag', $text);

// –јЅќ“ј — “≈√јћ». „ј—“№ 2
	if( $botParams->get( 'apunct' ) )
	{//Ќачальные и конечные пробелы и знаки препинани€ внутри текста ссылки выносить за пределы ссылки.
		$text = preg_replace( '/<a +href([^>]*)>([ .,!?:;Е]+)/', '\\2<a href\\1>', $text );
		$text = preg_replace( '/(!\.\.|\?\.\.)<\/a>/', '</a>\\1', $text );
		$text = preg_replace( '/([ .,!?:;Е]+)<\/a>/', '</a>\\1', $text );
	}
	if( $botParams->get( 'indices' ) ) $text = str_replace( ' <su', '<su', $text ); // Ќе отрывать верхние и нижние индексы от предыдущих символов

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