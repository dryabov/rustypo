<?php
/********************************************
* RussianTypography plugin for Joomla       *
* Copyright (C) 2006-2011 by Denis Ryabov   *
*                       [dryabov@yandex.ru] *
* Version    : 2.0.4                        *
* License    : Released under GPL           *
********************************************/

// Метод "выкусывания" тегов взят из статьи http://www.softportal.com/articles/item_txt.php?id=208.
// В ней указан источник http://spectator.ru/, но на этом сайте данная статья обнаружена не была...

// Метод расстановки кавычек — ©spectator.ru


defined( '_JEXEC' ) or die( 'Доступ запрещен.' );

jimport('joomla.plugin.plugin');

define('TAGBEGIN', "\x01");
define('TAGEND',   "\x02");

define('NOBRSPACE',  "\x03");
define('NOBRHYPHEN', "\x04");
define('THINSP',     "\x05");
define('DASH',       "\x06");
define('NUMDASH',    "\x07");

class plgContentRustypo extends JPlugin
{
	protected static $Refs = array(); // буфер для хранения тегов
	protected static $RefsCntr = 0;   // счётчик буфера

	public function onContentPrepare( $context, &$article, &$params, $page = 0 )
	{
		if( isset($article->text) )
			$article->text = $this->proof( $article->text, $this->params );
	}
	public function onContentAfterTitle( $context, &$item, &$params, $page = 0 )
	{
		if( $this->params->get( 'titles' ) && isset($item->title) )
			$item->title = $this->proof( $item->title, $this->params, false );
		return '';
	}

	static function putTag($x)
	{
		self::$Refs[] = $x[0];
		return TAGBEGIN.(self::$RefsCntr++).TAGEND;
	}
	static function getTag($x)
	{
		return self::$Refs[$x[1]];
	}
	
	protected function proof( $text, &$params, $allowtags=true )
	{
		$htmlents = array(
			'&#8222;'=>'„','&#8219;'=>'“','&#8220;'=>'”','&#8216;'=>'‘','&#8217;'=>'’',
			'&laquo;'=>'«','&raquo;'=>'»','&hellip;'=>'…','&euro;'=>'€','&permil;'=>'‰',
			'&bull;'=>'•','&middot;'=>'·','&ndash;'=>'–','&mdash;'=>'—','&nbsp;'=>' ',
			'&trade;'=>'™','&copy;'=>'©','&reg;'=>'®','&sect;'=>'§','&#8470;'=>'№',
			'&plusmn;'=>'±','&deg;'=>'°');
		$text = strtr( $text, $htmlents ); // Делаем замены html entity на соответствующие символы

		// РАБОТА С ТЕГАМИ. ЧАСТЬ 1
		if( $params->get( 'spacesatend' ) ) $text = preg_replace( '/(?> | )+(?=$|<br|<\/p)/u', '', $text ); // Убираем лишние пробелы перед концом строки
		if( $params->get( 'aquotes' ) ) $text = preg_replace( '/<a +href([^>]*)> *(?:"|&quot;)([^<"]*)(?:"|&quot;) *<\/a>/u', '"<a href\\1>\\2</a>"', $text ); // Выносим кавычки из ссылок
		if( $params->get( 'shortatend' ) ) $text = preg_replace( '/([а-яА-Яa-zA-Z]) ([а-яА-Яa-zA-Z]{1,5}(?>[.!?…]*))(?=$|<\/p>|<\/div>|<br>|<br \/>)/u','\\1'.NOBRSPACE.'\\2', $text); // Последнее короткое слово в абзаце привязывать к предыдущему

		// ПРЯМАЯ РЕЧЬ
		if( $params->get( 'speech' ) ) $text = preg_replace( '/(^|<p>|<br>|<br \/>)\s*- /u','\\1— ', $text ); // Прямая речь - дефис в начале строки и после тегов <p>, <br> и <br />

		// ВЫРЕЗАЕМ ТЕГИ
		self::$Refs = array();
		self::$RefsCntr = 0;
		$text = preg_replace_callback('/<!--.*?-->/su', array('plgContentRustypo','putTag'), $text); // комментарии
		$text = preg_replace_callback('/< *(script|style|pre|code|textarea).*?>.*?< *\/ *\1 *>/isu', array('plgContentRustypo','putTag'), $text); // теги, которые вырезаются вместе с содержимым
		$text = preg_replace_callback('/<(?:[^\'"\>]+|".*?"|\'.*?\')+>/su', array('plgContentRustypo','putTag'), $text); // обычные теги

		$text = strtr( $text, "\t\n\r", '   ' ); // Заменяем табуляцию и перевод строки на пробел
		$text = preg_replace( '/ +/u', ' ', $text ); // Убираем лишние пробелы

		$text = str_replace( '&quot;','"', $text ); // Заменяем &quot на "
		$text = str_replace( '&#39;',"'", $text ); // Заменяем &#39 на '

		// УГЛЫ (ГРАДУСЫ, МИНУТЫ И СЕКУНДЫ)
		if( $params->get( 'angle' ) )
		{
			$text = preg_replace( '/((?>\d{1,3})) ?° ?((?>\d{1,2})) ?\' ?((?>\d{1,2})) ?"/u','\\1° \\2&prime; \\3&Prime;', $text ); // 10° 11' 12"
			$text = preg_replace( '/((?>\d{1,3})) ?° ?((?>\d{1,2})) ?\'/u','\\1° \\2&prime;', $text ); // 10° 11'
			$text = preg_replace( '/((?>\d{1,3})) °(?![^CcСсF])/u','\\1°', $text ); // 10°, но не 10 °C
			$text = preg_replace( '/((?>\d{1,2})) ?\' ?((?>\d{1,2})) ?"/u','\\1&prime; \\2&Prime;', $text ); // 11' 12"
			$text = preg_replace( '/((?>\d{1,2})) \'/u','\\1&prime;', $text ); // 11'
		}

		// РАССТАВЛЯЕМ КАВЫЧКИ
		if( $params->get( 'quotes' ) )
		{
			$text = preg_replace( '/(['.TAGEND.'\(  ]|^)"([^"]*)([^  "\(])"/u', '\\1«\\2\\3»', $text ); // Расстановка кавычек-"елочек"
			if( stristr( $text, '"' ) ) // Если есть вложенные кавычки
			{
				$text = preg_replace( '/(['.TAGEND.'(  ]|^)"([^"]*)([^  "(])"/u', '\\1«\\2\\3»', $text );
				while( preg_match( '/«[^»]*«[^»]*»/u', $text ) )
					$text = preg_replace( '/«([^»]*)«([^»]*)»/u', '«\\1„\\2“', $text );
			}
		}

		// ДЕЛАЕМ ЗАМЕНЫ
		//	$text = str_replace( '• ','•'.NOBRSPACE, $text ); // Пункт (для списков)
		if( $params->get( 'dash' ) )
		{ // Тире
			$text = str_replace( ' - ',NOBRSPACE.DASH.' ', $text );
			$text = preg_replace( '/ - /u',NOBRSPACE.DASH.' ', $text );
		}
		if( $params->get( 'dots' ) ) $text = str_replace( '...','…', $text ); // Многоточие
		if( $params->get( 'plusminus' ) ) $text = str_replace( '+/-','±', $text ); // плюс-минус

		if( $params->get( 'registered' ) && $allowtags)
		{
			$text = str_replace( '(r)','<sup>®</sup>', $text );
			$text = str_replace( '(R)','<sup>®</sup>', $text ); // registered
		}
		if( $params->get( 'copyright' ) ) $text = preg_replace( '/\((c|C|с|С)\)/u','©', $text );
		if( $params->get( 'nobrcopyright' ) ) $text = preg_replace( '/© /u','©'.NOBRSPACE, $text ); // copyright
		if( $params->get( 'trademark' ) )
		{ // trademark
			$text = str_replace( '(tm)','™', $text );
			$text = str_replace( '(TM)','™', $text );
		}
	
		if( $params->get( 'lgequals' ) )
		{
			$text = str_replace( '&lt;=','&le;', $text ); // Меньше/равно
			$text = str_replace( '&gt;=','&ge;', $text ); // Больше/равно
		}

		// ДРОБИ
		if( $params->get( 'frac' ) ) 
		{
			$text = preg_replace( '/(^|[  ("«„])1\/2(?=$|[  )"»“.,!?:;…])/u','\\1½', $text);
			$text = preg_replace( '/(^|[  ("«„])1\/4(?=$|[  )"»“.,!?:;…])/u','\\1¼', $text);
			$text = preg_replace( '/(^|[  ("«„])3\/4(?=$|[  )"»“.,!?:;…])/u','\\1¾', $text);
		}
		if( $params->get( 'fracext' ) ) 
		{
			$text = preg_replace( '/(^|[  ("«„])1\/3(?=$|[  )"»“.,!?:;…])/u','\\1⅓', $text);
			$text = preg_replace( '/(^|[  ("«„])2\/3(?=$|[  )"»“.,!?:;…])/u','\\1⅔', $text);
			$text = preg_replace( '/(^|[  ("«„])1\/8(?=$|[  )"»“.,!?:;…])/u','\\1⅛', $text);
			$text = preg_replace( '/(^|[  ("«„])3\/8(?=$|[  )"»“.,!?:;…])/u','\\1⅜', $text);
			$text = preg_replace( '/(^|[  ("«„])5\/8(?=$|[  )"»“.,!?:;…])/u','\\1⅝', $text);
			$text = preg_replace( '/(^|[  ("«„])7\/8(?=$|[  )"»“.,!?:;…])/u','\\1⅞', $text);
		}

		// ИНИЦИАЛЫ И ФАМИЛИИ
		if( $params->get( 'initials' ) )
		{
			$text = preg_replace( '/(?<=[^а-яА-ЯёЁa-zA-Z][А-ЯЁA-Z]\.|^[А-ЯЁA-Z]\.) ?([А-ЯЁA-Z]\.) ?(?=[А-ЯЁA-Z][а-яА-ЯёЁa-zA-Z])/u', THINSP.'\\1'.NOBRSPACE, $text ); // Инициалы + фамилия
			$text = preg_replace( '/((?>[А-ЯЁA-Z][а-яА-ЯёЁa-zA-Z]+)) ([А-ЯЁA-Z]\.) ?(?=[А-ЯЁA-Z]\.)/u', '\\1'.NOBRSPACE.'\\2'.THINSP, $text ); // Фамилия + инициалы
			$text = preg_replace( '/(?<=[^а-яА-ЯёЁa-zA-Z][А-ЯЁA-Z]\.|^[А-ЯЁA-Z]\.) ?(?=[А-ЯЁA-Z][а-яА-ЯёЁa-zA-Z])/u', NOBRSPACE, $text ); // Инициал + фамилия
			$text = preg_replace( '/((?>[А-ЯЁA-Z][а-яА-ЯёЁa-zA-Z]+)) (?=[А-ЯЁA-Z]\.)/u', '\\1'.NOBRSPACE, $text ); // Фамилия + инициал
		}

		// СОКРАЩЕНИЯ
		if( $params->get( 'abr' ) )
		{
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z]|^)(г\.|ул\.|пер\.|пл\.|пос\.|р\.|проф\.|доц\.|акад\.|гр\.) ?(?=[А-ЯЁ])/u', '\\1\\2'.NOBRSPACE, $text ); // Сокращения
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z]|^)(с\.|стр\.|рис\.|гл\.|илл\.|табл\.|кв\.|дом|д.\|офис|оф\.|ауд\.) ?(?=\d)/u', '\\1\\2'.NOBRSPACE, $text ); // Сокращения
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z]|^)(см\.|им\.|каф\.) ?(?=[а-яА-ЯёЁa-zA-Z\d])/u', '\\1\\2'.NOBRSPACE, $text ); // Сокращения
		}
	
		// ЕДИНИЦЫ ИЗМЕРЕНИЯ
		if( $params->get( 'units' ) )
		{
			$text = preg_replace( '/([а-яёa-z\d\.]) (?=экз\.|тыс\.|млн\.|млрд\.|руб\.|коп\.|у\.е\.|\$|€)/u', '\\1'.NOBRSPACE, $text ); // Единицы измерения
			$text = preg_replace( '/([а-яёa-z\d\.]) (?=евро([ \.,!\?:;]|$))/u', '\\1'.NOBRSPACE, $text ); // Евро
		}

		// ПРИВЯЗЫВАЕМ КОРОТКИЕ СЛОВА
		if( $params->get( 'shortwords' ) )
		{
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z0-9])(я|ты|мы|вы|он|не|ни|на|но|в|во|до|от|и|а|с|со|о|об|ну|к|ко|за|их|из|ее|её|ей|ой|ай|у) (?=[а-яА-ЯёЁa-zA-Z]{3})/u', '\\1\\2'.NOBRSPACE, $text ); // Короткие слова прикрепляем к следующим (если те сами не короткие)
			$text = preg_replace( '/([а-яА-ЯёЁ]) (?=(же|ж|ли|ль|бы|б|ка|то)([\.,!\?:;])?( |$))/u', '\\1'.NOBRSPACE, $text ); // Частицы
			$text = preg_replace( '/([.!?…] [А-ЯЁA-Z][а-яА-ЯёЁa-zA-Z]{0,3}) /u', '\\1'.NOBRSPACE, $text ); // Слова от 1 до 3 букв в начале предложения
		}

		// И Т.Д., И Т.П., Т.К., ...
		if( $params->get( 'etc' ) )
		{
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z]и) (д|п)(?=р\.)/u', '\\1'.NOBRSPACE.'\\2', $text ); // и др., и пр.
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z]и) т\. ?(?=(д|п)\.)/u', '\\1'.NOBRSPACE.'т.'.THINSP, $text ); // и т.д., и т.п.
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z]в) т\. ?(?=ч\.)/u', '\\1'.NOBRSPACE.'т.'.THINSP, $text ); // в т.ч.
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z]т\.) ?(?=(к|н|е)\.)/u', '\\1'.THINSP, $text ); // т.к., т.н., т.е.
			$text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z](?=к|д)\.) ?(ф\.-м|х|б|т|ф|п)\. ?(?=н\.)/u', '\\1'.THINSP.'\\2.'.THINSP, $text ); // к.т.н., д.ф.-м.н., ...
		}

		// ИСПРАВЛЕНИЕ ГРАММАТИЧЕСКИХ ОШИБОК
		if( $params->get( 'spinbracket' ) ) $text = preg_replace( '/\( *([^)]+?) *\)/u', '(\\1)', $text ); // удаляем пробелы после открывающей скобки и перед закрыващей скобкой
		if( $params->get( 'spbeforebracket' ) ) $text = preg_replace( '/([а-яА-ЯёЁa-zA-Z.,!?:;…])\(/u', '\\1 (', $text ); // добавляем пробел между словом и открывающей скобкой, если его нет (отключите, если у Вас на сайте есть формулы)
		if( $params->get( 'commanum' ) ) $text = preg_replace( '/([а-яА-ЯёЁa-zA-Z]),(?=\d)/u','\\1, ', $text); // Делает проверку в расстановке запятых и меняет слово,число на слово, число (ул. Дружбы, 46)
		if( $params->get( 'dotdash' ) )
		{ // Тире от запятой и точки не отбивается
			$text = str_replace( ','.NOBRSPACE.DASH.' ',','.DASH.' ', $text );
			$text = str_replace( '.'.NOBRSPACE.DASH.' ','.'.DASH.' ', $text );
		}
		if( $params->get( 'exclquest' ) ) $text = str_replace( '!?','?!', $text ); // Правильно в таком порядке
		if( $params->get( 'exclquestdots' ) ) $text = preg_replace( '/(!|\?)(?:…|\.\.\.)/u','\\1..', $text ); // Убираем лишние точки
		//TODO: Обработка .NET
		if( $params->get( 'sppunct' ) ) $text = preg_replace( '/ (?=[.,!?:;])/u','', $text ); // Убираем пробелы перед знаками препинания
		if( $params->get( 'nummarksp' ) ) $text = preg_replace( '/(№|§) ?(?=\d)/u', '\\1 ', $text ); // пробел между знаком "№" или "§" и числом.
		if( $params->get( 'numnummark' ) ) $text = preg_replace( '/№ №/u', '№№', $text ); // слитное написание "№№"
		if( $params->get( 'sectsectmark' ) ) $text = preg_replace( '/§ §/u', '§§', $text ); // слитное написание "§§"
		//TODO: Выносим знаки препинания (.,:;) вне кавычек, а (!?…) в кавычки

		// ВСЁ О ЧИСЛАХ
		if( $params->get( 'sizes' ) ) $text = preg_replace( '/(\d) *(?:\*|х|x|X|Х) *(?=\d)/u', '\\1&times;', $text ); // обрабатываем размерные конструкции (200x500)
		// Делает неразрывными номера телефонов
		if( $params->get( 'phonenum' ) )
		{
			$text = preg_replace( '/(\+7|8) ?(\(\d+\)) ?(\d+)-(\d{2})-(?=\d{2})/u','\\1'.NOBRSPACE.'\\2'.NOBRSPACE.'\\3'.NOBRHYPHEN.'\\4'.NOBRHYPHEN, $text );
			$text = preg_replace( '/(\(\d+\)) ?(\d+)-(\d{2})-(?=\d{2})/u','\\1'.NOBRSPACE.'\\2'.NOBRHYPHEN.'\\3'.NOBRHYPHEN, $text );
			$text = preg_replace( '/(\d+)-(\d{2})-(?=\d{2})/u','\\1'.NOBRHYPHEN.'\\2'.NOBRHYPHEN, $text );
		}
		if( $params->get( 'interval' ) )
		{ // Обрабатываем диапазон численных значений
			$text = preg_replace( '/((?>\d+))-(?=(?>\d+)([ .,!?:;…]|$))/u','\\1'.NUMDASH, $text );
			$text = preg_replace( '/((?>[IVXLCDM]+))-(?=(?>[IVXLCDM]+)([ .,!?:;…]|$))/u','\\1'.NUMDASH, $text );
		}
		if( $params->get( 'minus' ) ) $text = preg_replace( '/ -(?=\d)/u',' &minus;', $text ); // Минус перед цифрами
		if( $params->get( 'numnbsp' ) ) $text = preg_replace( '/([ '.DASH.NUMDASH.']|^)((?>\d+)) /u','\\1\\2'.NOBRSPACE, $text ); // Неразрывный пробел после арабских цифр
		if( $params->get( 'romenbsp' ) ) $text = preg_replace( '/([ '.DASH.NUMDASH.']|^)((?>[IVXLCDM]+)) /u','\\1\\2'.NOBRSPACE, $text ); // Неразрывный пробел после римских цифр
		//TODO: Неразрывный пробел в конструкциях вида 10 кг и т.д. (если предыдущее правило отключено)
		//TODO: Вставлять неразрывный пробел между числом и сокращением размерностью, чтобы не было 1кг (причем только для общепринятых сокращений размерностей...)
		if( $params->get( 'deg' ) ) $text = preg_replace( '/([-+]?(?>\d+)(?:[.,](?>\d+))?)[  '.NOBRSPACE.']?[CС]\b/u','\\1&deg; C', $text); // Заменяет C в конструкциях градусов на °C
		if( $params->get( 'percent' ) ) $text = preg_replace( '/(\d)[  '.NOBRSPACE.'](?=%|‰)/u','\\1', $text); // Знаки процента (%) и промилле (‰) прикреплять к числам, к которым они относятся
		if( $params->get( 'numnum' ) ) $text = preg_replace( '/(\d) (?=\d)/u','\\1'.NOBRSPACE, $text ); // Не разрывать 25 000

		// РАЗНОЕ
		if( $params->get( 'orgs' ) ) $text = preg_replace( '/(ООО|ОАО|ЗАО|ЧП) ?(?="|«)/u','\\1'.NOBRSPACE, $text); // Делает неразрывными названия организаций и абревиатуру формы собственности
		if( $params->get( 'doubleword' ) ) $text = preg_replace( '/([^а-яА-ЯёЁa-zA-Z][а-яА-ЯёЁa-zA-Z]{1,8})-(?=[а-яА-ЯёЁa-zA-Z]{1,8}[^а-яА-ЯёЁa-zA-Z])/u','\\1'.NOBRHYPHEN, $text); // Делает неразрывными двойные слова (светло-красный, фамилии Иванов-Васильев)
		if( $params->get( 'htmlents' ) ) $text = strtr( $text, array_flip( $htmlents ) ); // Делаем обратные замены на html-entity
		if( $params->get( 'xmlcomp' ) )
		{
			$text = str_replace( '"','&quot;', $text ); // Заменяем " на &quot;
			$text = str_replace( "'",'&#39;', $text ); // Заменяем ' на &#39;
		}

		// КОРОТКИЙ ПРОБЕЛ
		switch( $params->get( 'typethinsp' ) )
		{
		case 1:
			$text = str_replace( THINSP,NOBRSPACE, $text ); break;
		case 2:
			$text = str_replace( THINSP,' ', $text ); break;
		case 3:
			$text = str_replace( THINSP,'&thinsp;', $text ); break;
		case 0:
		default:
			$text = str_replace( THINSP,'', $text );
		}
	
		// НЕРАЗРЫВНЫЙ ПРОБЕЛ
		if($allowtags)
			$typenbsp = $params->get( 'typenbsp' );
		else
			$typenbsp = 0;
		switch( $typenbsp )
		{
		case 1:
			$text = preg_replace( '/(^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.']+['.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.'][^ '.TAGBEGIN.']*)(?=$| |'.TAGBEGIN.')/u','\\1<nobr>\\2</nobr>', $text );
			$text = str_replace( NOBRSPACE,' ', $text );
			break;
		case 2:
			$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.']+['.NOBRSPACE.NOBRHYPHEN.DASH.NUMDASH.'][^ '.TAGBEGIN.']*)(?=$| |'.TAGBEGIN.')/u','<span style="white-space:nowrap">\\1</span>', $text );
			$text = str_replace( NOBRSPACE,' ', $text );
			break;
		case 0:
		default:
			$text = str_replace( NOBRSPACE,' ', $text );
			if($allowtags)
				$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRHYPHEN.DASH.NUMDASH.']+['.NOBRHYPHEN.DASH.NUMDASH.'][^ '.TAGBEGIN.']+)(?=$| |'.TAGBEGIN.')/u','<nobr>\\1</nobr>', $text );
			//	$text = preg_replace( '/(?<=^| |'.TAGEND.')([^ '.TAGBEGIN.TAGEND.NOBRHYPHEN.DASH.']+['.NOBRHYPHEN.DASH.'][^ '.TAGBEGIN.']+)(?=$| |'.TAGBEGIN.')/u','<span style="white-space:nowrap">\\1</span>', $text );
		}

		// НЕРАЗРЫВНЫЙ ДЕФИС
		$text = str_replace( NOBRHYPHEN,'-', $text );

		// ТИРЕ
		switch( $params->get( 'typedash' ) )
		{
		case 1:
			$text = str_replace( DASH,'–', $text ); break; // ndash
		case 2:
			$text = str_replace( DASH,'&minus;', $text ); break; // minus
		case 3:
			$text = str_replace( DASH,'-', $text ); break; // hyphen
		case 0:
		default:
			$text = str_replace( DASH,'—', $text ); // mdash
		}
		// ТИРЕ В ДИАПАЗОНЕ
		switch( $params->get( 'typenumdash' ) )
		{
		case 0:
			$text = str_replace( NUMDASH,'—', $text ); break; // mdash
		case 2:
			$text = str_replace( NUMDASH,'&minus;', $text ); break; // minus
		case 3:
			$text = str_replace( NUMDASH,'-', $text ); break; // hyphen
		case 1:
		default:
			$text = str_replace( NUMDASH,'–', $text ); // ndash
		}
	
		// ВОЗВРАЩАЕМ ТЕГИ НА МЕСТО
		while(preg_match('/'.TAGBEGIN.'\d+'.TAGEND.'/u', $text))
			$text = preg_replace_callback('/'.TAGBEGIN.'(\d+)'.TAGEND.'/u', array('plgContentRustypo','getTag'), $text);

		// РАБОТА С ТЕГАМИ. ЧАСТЬ 2
		if( $params->get( 'apunct' ) )
		{//Начальные и конечные пробелы и знаки препинания внутри текста ссылки выносить за пределы ссылки.
			$text = preg_replace( '/<a +href([^>]*)>([ .,!?:;…]+)/u', '\\2<a href\\1>', $text );
			$text = preg_replace( '/(!\.\.|\?\.\.)<\/a>/u', '</a>\\1', $text );
			$text = preg_replace( '/([ .,!?:;…]+)<\/a>/u', '</a>\\1', $text );
		}
		if( $params->get( 'indices' ) ) $text = str_replace( ' <su', '<su', $text ); // Не отрывать верхние и нижние индексы от предыдущих символов

		return trim($text);
	}
} 

