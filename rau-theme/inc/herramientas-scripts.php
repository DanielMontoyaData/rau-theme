<?php
/**
 * Todo el JavaScript de Herramientas de Radio — separado de
 * page-herramientas.php solo por mantenibilidad. Se incluye dentro del
 * mismo <script> en el mismo lugar exacto, el HTML final es idéntico.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
/* Nombre e icono de la fase lunar a partir de la fracción del ciclo sinódico (0 = luna nueva).
   Estaba duplicado en el panel Luna de la consola y en la herramienta Fase Lunar (EME): mismos
   ocho umbrales escritos dos veces. Séptima duplicación encontrada en esta revisión. */


/* PFX_GEO y PFX se declaran aquí arriba, antes que cualquier módulo, por la misma razón que la
   tabla Morse: son datos compartidos por varios módulos repartidos por todo el archivo (Localizador
   de Indicativo, búsqueda de prefijos, mapa y el panel DX de la consola). Un 'const' declarado a
   media altura solo existe a partir de su línea, y cualquier consumidor anterior recibiría un
   ReferenceError que aborta el resto del script. Declarándolas primero, el orden deja de importar. */
const PFX_GEO={
	/* ── Bloques completados ───────────────────────────────────────────────────
	   Un spot griego (SY1EZC) apareció sin país y al revisarlo faltaban prefijos de once países
	   que YA estaban en la tabla, pero con solo uno o dos de su bloque UIT. Es el mismo hueco que
	   dejó a Japón, España y Polonia sin resolver, una capa más abajo. */
	'SW':'🇬🇷 Grecia|EU|28|20|37.98|23.73','SX':'🇬🇷 Grecia|EU|28|20|37.98|23.73',
	'SY':'🇬🇷 Grecia|EU|28|20|37.98|23.73','SZ':'🇬🇷 Grecia|EU|28|20|37.98|23.73',
	'J4':'🇬🇷 Grecia|EU|28|20|37.98|23.73',
	'TM':'🇫🇷 Francia|EU|27|14|48.86|2.35','TP':'🇫🇷 Francia|EU|27|14|48.86|2.35',
	'TQ':'🇫🇷 Francia|EU|27|14|48.86|2.35','TV':'🇫🇷 Francia|EU|27|14|48.86|2.35',
	'TB':'🇹🇷 Turquía|AS|39|20|39.93|32.86','TC':'🇹🇷 Turquía|AS|39|20|39.93|32.86',
	'YM':'🇹🇷 Turquía|AS|39|20|39.93|32.86',
	'US':'🇺🇦 Ucrania|EU|29|16|50.45|30.52','UT':'🇺🇦 Ucrania|EU|29|16|50.45|30.52',
	'UU':'🇺🇦 Ucrania|EU|29|16|50.45|30.52','UV':'🇺🇦 Ucrania|EU|29|16|50.45|30.52',
	'UW':'🇺🇦 Ucrania|EU|29|16|50.45|30.52','UX':'🇺🇦 Ucrania|EU|29|16|50.45|30.52',
	'UY':'🇺🇦 Ucrania|EU|29|16|50.45|30.52','UZ':'🇺🇦 Ucrania|EU|29|16|50.45|30.52',
	'EM':'🇺🇦 Ucrania|EU|29|16|50.45|30.52','EN':'🇺🇦 Ucrania|EU|29|16|50.45|30.52',
	'EO':'🇺🇦 Ucrania|EU|29|16|50.45|30.52',
	'YP':'🇷🇴 Rumanía|EU|28|20|44.43|26.10','YQ':'🇷🇴 Rumanía|EU|28|20|44.43|26.10',
	'YR':'🇷🇴 Rumanía|EU|28|20|44.43|26.10',
	'OL':'🇨🇿 Chequia|EU|28|15|50.08|14.44','HG':'🇭🇺 Hungría|EU|28|15|47.50|19.04',
	'YT':'🇷🇸 Serbia|EU|28|15|44.79|20.45',
	'ZR':'🇿🇦 Sudáfrica|AF|57|38|-25.75|28.19','ZT':'🇿🇦 Sudáfrica|AF|57|38|-25.75|28.19',
	'ZU':'🇿🇦 Sudáfrica|AF|57|38|-25.75|28.19',
	'ZM':'🇳🇿 Nueva Zelanda|OC|60|32|-41.29|174.78','8T':'🇮🇳 India|AS|41|22|28.61|77.21',

	/* ── Cobertura DXCC ampliada ───────────────────────────────────────────────
	   Comprobé la tabla contra una lista de entidades DXCC que aparecen con frecuencia en las
	   bandas y faltaban 67. Un spot de Vietnam (3W9C) apareció en el panel sin país y eso
	   destapó el hueco. Coordenadas de la capital o del centro del territorio.
	   Las zonas ITU/CQ son las de la capital: para entidades que abarcan varias, aproximadas. */

	/* Asia */
	'3W':'🇻🇳 Vietnam|AS|49|26|21.03|105.85','XV':'🇻🇳 Vietnam|AS|49|26|21.03|105.85',
	'XU':'🇰🇭 Camboya|AS|49|26|11.56|104.92','XW':'🇱🇦 Laos|AS|49|26|17.97|102.63',
	'XZ':'🇲🇲 Myanmar|AS|49|26|16.87|96.20','S2':'🇧🇩 Bangladés|AS|41|22|23.81|90.41',
	'9N':'🇳🇵 Nepal|AS|42|22|27.72|85.32','AP':'🇵🇰 Pakistán|AS|41|21|33.68|73.05',
	'A9':'🇧🇭 Baréin|AS|39|21|26.23|50.59','A7':'🇶🇦 Catar|AS|39|21|25.29|51.53',
	'YK':'🇸🇾 Siria|AS|39|20|33.51|36.28','OD':'🇱🇧 Líbano|AS|39|20|33.89|35.50',
	'JY':'🇯🇴 Jordania|AS|39|20|31.95|35.93','E4':'🇵🇸 Palestina|AS|39|20|31.90|35.20',
	'EK':'🇦🇲 Armenia|AS|29|21|40.18|44.51','4J':'🇦🇿 Azerbaiyán|AS|29|21|40.41|49.87',
	'4L':'🇬🇪 Georgia|AS|29|21|41.72|44.78','EX':'🇰🇬 Kirguistán|AS|30|17|42.87|74.59',
	'EY':'🇹🇯 Tayikistán|AS|30|17|38.56|68.79','EZ':'🇹🇲 Turkmenistán|AS|30|17|37.95|58.38',
	'UK':'🇺🇿 Uzbekistán|AS|30|17|41.30|69.24','BD':'🇨🇳 China|AS|33|24|39.90|116.41',
	'BG':'🇨🇳 China|AS|33|24|39.90|116.41','DS':'🇰🇷 Corea del Sur|AS|44|25|37.57|126.98',
	'5B':'🇨🇾 Chipre|AS|39|20|35.19|33.38',

	/* África */
	'5X':'🇺🇬 Uganda|AF|48|37|0.35|32.58','9U':'🇧🇮 Burundi|AF|52|36|-3.38|29.36',
	'9X':'🇷🇼 Ruanda|AF|52|36|-1.95|30.06','ST':'🇸🇩 Sudán|AF|47|34|15.50|32.56',
	'7Q':'🇲🇼 Malaui|AF|53|37|-13.97|33.79','C9':'🇲🇿 Mozambique|AF|53|37|-25.97|32.58',
	'A2':'🇧🇼 Botsuana|AF|57|38|-24.65|25.91','3DA':'🇸🇿 Esuatini|AF|57|38|-26.32|31.14',
	'7P':'🇱🇸 Lesoto|AF|57|38|-29.31|27.48','S9':'🇸🇹 Santo Tomé|AF|47|36|0.34|6.73',
	'TY':'🇧🇯 Benín|AF|46|35|6.50|2.60','XT':'🇧🇫 Burkina Faso|AF|46|35|12.37|-1.52',
	'TZ':'🇲🇱 Malí|AF|46|35|12.65|-8.00','5T':'🇲🇷 Mauritania|AF|46|35|18.08|-15.98',
	'5U':'🇳🇪 Níger|AF|46|35|13.51|2.11','TT':'🇹🇩 Chad|AF|47|36|12.13|15.06',
	'TL':'🇨🇫 Rep. Centroafricana|AF|47|36|4.39|18.56','TN':'🇨🇬 Congo|AF|52|36|-4.26|15.28',
	'9Q':'🇨🇩 RD Congo|AF|52|36|-4.44|15.27','TJ':'🇨🇲 Camerún|AF|47|36|3.85|11.50',
	'3C':'🇬🇶 Guinea Ecuatorial|AF|47|36|3.75|8.78','C5':'🇬🇲 Gambia|AF|46|35|13.45|-16.58',
	'J5':'🇬🇼 Guinea-Bisáu|AF|46|35|11.86|-15.60','6V':'🇸🇳 Senegal|AF|46|35|14.72|-17.47',
	'EL':'🇱🇷 Liberia|AF|46|35|6.31|-10.80','9L':'🇸🇱 Sierra Leona|AF|46|35|8.48|-13.23',
	'ZD8':'🇸🇭 Ascensión|AF|66|36|-7.95|-14.36','ZD7':'🇸🇭 Santa Elena|AF|66|36|-15.96|-5.72',

	/* Europa */
	'ER':'🇲🇩 Moldavia|EU|29|16|47.01|28.86','EW':'🇧🇾 Bielorrusia|EU|29|16|53.90|27.57',
	'E7':'🇧🇦 Bosnia y Herzegovina|EU|28|15|43.86|18.41','4O':'🇲🇪 Montenegro|EU|28|15|42.44|19.26',
	'ZA':'🇦🇱 Albania|EU|28|15|41.33|19.82','9H':'🇲🇹 Malta|EU|28|15|35.90|14.51',
	'T7':'🇸🇲 San Marino|EU|28|15|43.94|12.45','ZB':'🇬🇮 Gibraltar|EU|37|14|36.14|-5.35',
	'TF':'🇮🇸 Islandia|EU|17|40|64.15|-21.94','OY':'🇫🇴 Feroe|EU|18|14|62.01|-6.77',
	'OX':'🇬🇱 Groenlandia|NA|5|40|64.18|-51.72','JW':'🇸🇯 Svalbard|EU|18|40|78.22|15.65',
	'JX':'🇸🇯 Jan Mayen|EU|18|40|70.92|-8.72','UA2':'🇷🇺 Kaliningrado|EU|29|15|54.71|20.51',

	/* América */
	'9Z':'🇹🇹 Trinidad y Tobago|SA|11|9|10.65|-61.51','V2':'🇦🇬 Antigua y Barbuda|NA|11|8|17.12|-61.85',
	'VP2':'🇦🇮 Anguila|NA|11|8|18.22|-63.07','VP5':'🇹🇨 Turcas y Caicos|NA|11|8|21.47|-71.14',
	'VP9':'🇧🇲 Bermudas|NA|11|5|32.29|-64.78','VP8':'🇫🇰 Islas Malvinas|SA|16|13|-51.70|-57.85',
	'FP':'🇵🇲 San Pedro y Miquelón|NA|9|5|46.78|-56.17','KL':'🇺🇸 Alaska|NA|1|1|61.22|-149.90',
	'KP2':'🇻🇮 Islas Vírgenes|NA|11|8|18.34|-64.93','KH8':'🇼🇸 Samoa Americana|OC|62|32|-14.28|-170.70',
	'KH0':'🇲🇵 Marianas|OC|64|27|15.19|145.75',

	/* ── Bloques de prefijo completos ──────────────────────────────────────────
	   Los países no usan un solo prefijo sino rangos enteros asignados por la UIT, y la tabla
	   solo tenía uno o dos por país. En una captura real del panel, 3 de 5 spots quedaban sin
	   resolver por esto: EG1SDC (España usa EA–EH), JE9KZK (Japón usa JA–JS) y SO18OPKP
	   (Polonia usa SN–SR, HF y 3Z). Se añaden los bloques de los países que más DX generan.
	   Los prefijos de 3 caracteres ya presentes (EA8 Canarias, CT3 Madeira) siguen ganando,
	   porque la búsqueda prueba de 4 caracteres hacia abajo. */

	/* Japón — JA a JS */
	'JE':'🇯🇵 Japón|AS|45|25|35.68|139.69','JF':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'JG':'🇯🇵 Japón|AS|45|25|35.68|139.69','JH':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'JI':'🇯🇵 Japón|AS|45|25|35.68|139.69','JJ':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'JK':'🇯🇵 Japón|AS|45|25|35.68|139.69','JL':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'JM':'🇯🇵 Japón|AS|45|25|35.68|139.69','JN':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'JO':'🇯🇵 Japón|AS|45|25|35.68|139.69','JP':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'JQ':'🇯🇵 Japón|AS|45|25|35.68|139.69','JR':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'JS':'🇯🇵 Japón|AS|45|25|35.68|139.69',

	/* España — EA a EH */
	'EC':'🇪🇸 España|EU|37|14|40.42|-3.70','ED':'🇪🇸 España|EU|37|14|40.42|-3.70',
	'EE':'🇪🇸 España|EU|37|14|40.42|-3.70','EF':'🇪🇸 España|EU|37|14|40.42|-3.70',
	'EG':'🇪🇸 España|EU|37|14|40.42|-3.70','EH':'🇪🇸 España|EU|37|14|40.42|-3.70',

	/* Polonia — SN a SR, más HF y 3Z */
	'SN':'🇵🇱 Polonia|EU|28|15|52.23|21.01','SO':'🇵🇱 Polonia|EU|28|15|52.23|21.01',
	'SQ':'🇵🇱 Polonia|EU|28|15|52.23|21.01','SR':'🇵🇱 Polonia|EU|28|15|52.23|21.01',
	'HF':'🇵🇱 Polonia|EU|28|15|52.23|21.01','3Z':'🇵🇱 Polonia|EU|28|15|52.23|21.01',

	/* Alemania — DA a DR */
	'DB':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DC':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'DD':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DE':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'DF':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DG':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'DH':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DJ':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'DK':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DM':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'DN':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DO':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'DP':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DQ':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'DR':'🇩🇪 Alemania|EU|28|14|52.52|13.40',

	/* Países Bajos — PA a PI */
	'PB':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90','PC':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90',
	'PD':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90','PE':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90',
	'PF':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90','PG':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90',
	'PH':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90','PI':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90',

	/* Suecia — SA a SM (SN–SR son de Polonia) */
	'SA':'🇸🇪 Suecia|EU|18|14|59.33|18.07','SB':'🇸🇪 Suecia|EU|18|14|59.33|18.07',
	'SC':'🇸🇪 Suecia|EU|18|14|59.33|18.07','SD':'🇸🇪 Suecia|EU|18|14|59.33|18.07',
	'SE':'🇸🇪 Suecia|EU|18|14|59.33|18.07','SF':'🇸🇪 Suecia|EU|18|14|59.33|18.07',
	'SG':'🇸🇪 Suecia|EU|18|14|59.33|18.07','SH':'🇸🇪 Suecia|EU|18|14|59.33|18.07',
	'SI':'🇸🇪 Suecia|EU|18|14|59.33|18.07','SJ':'🇸🇪 Suecia|EU|18|14|59.33|18.07',
	'SK':'🇸🇪 Suecia|EU|18|14|59.33|18.07','SL':'🇸🇪 Suecia|EU|18|14|59.33|18.07',

	/* Bélgica — OO a OT */
	'OO':'🇧🇪 Bélgica|EU|27|14|50.85|4.35','OP':'🇧🇪 Bélgica|EU|27|14|50.85|4.35',
	'OQ':'🇧🇪 Bélgica|EU|27|14|50.85|4.35','OR':'🇧🇪 Bélgica|EU|27|14|50.85|4.35',
	'OS':'🇧🇪 Bélgica|EU|27|14|50.85|4.35','OT':'🇧🇪 Bélgica|EU|27|14|50.85|4.35',

	/* Brasil — PR a PW y ZV a ZZ */
	'PR':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88','PS':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88',
	'PT':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88','PV':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88',
	'PW':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88','ZV':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88',
	'ZW':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88','ZX':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88',
	'ZY':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88','ZZ':'🇧🇷 Brasil|SA|15|11|-15.79|-47.88',

	/* Argentina — LP a LW (LA es Noruega, LY Lituania, LZ Bulgaria) */
	'LP':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38','LQ':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38',
	'LR':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38','LS':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38',
	'LT':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38','LV':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38',
	'LW':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38',

	/* Otros bloques frecuentes */
	'2E':'🇬🇧 Reino Unido|EU|27|14|51.51|-0.13','VO':'🇨🇦 Canadá|NA|9|5|47.56|-52.71',
	'CA':'🇨🇱 Chile|SA|14|12|-33.45|-70.67','CB':'🇨🇱 Chile|SA|14|12|-33.45|-70.67',
	'CC':'🇨🇱 Chile|SA|14|12|-33.45|-70.67','CD':'🇨🇱 Chile|SA|14|12|-33.45|-70.67',
	'YX':'🇻🇪 Venezuela|SA|12|9|10.48|-66.90','YY':'🇻🇪 Venezuela|SA|12|9|10.48|-66.90',
	'XA':'🇲🇽 México|NA|10|6|19.43|-99.13','XB':'🇲🇽 México|NA|10|6|19.43|-99.13',
	'XC':'🇲🇽 México|NA|10|6|19.43|-99.13','XD':'🇲🇽 México|NA|10|6|19.43|-99.13',

	/* ── Ampliación de cobertura ──────────────────────────────────────────────
	   La tabla tenía 57 entradas con un sesgo fuerte hacia América: 22 de Sudamérica,
	   17 de Norteamérica, 10 de Europa, 5 de Asia, 3 de Oceanía y NINGUNA de África.
	   En una captura real del panel de DX, 3 de 5 spots (C6 Bahamas, TA Turquía,
	   YE Indonesia) no se podían resolver. Estas entradas cubren los DXCC que más
	   aparecen en las bandas desde Colombia.
	   Zonas ITU/CQ: para países que abarcan varias se indica la de la capital. */

	/* África */
	'ZS':'🇿🇦 Sudáfrica|AF|57|38|-25.75|28.19','5Z':'🇰🇪 Kenia|AF|48|37|-1.29|36.82',
	'SU':'🇪🇬 Egipto|AF|38|34|30.04|31.24','CN':'🇲🇦 Marruecos|AF|37|33|33.97|-6.85',
	'7X':'🇩🇿 Argelia|AF|37|33|36.75|3.06','3V':'🇹🇳 Túnez|AF|37|33|36.80|10.18',
	'5N':'🇳🇬 Nigeria|AF|46|35|9.06|7.49','9J':'🇿🇲 Zambia|AF|53|36|-15.39|28.32',
	'D2':'🇦🇴 Angola|AF|52|36|-8.84|13.23','5H':'🇹🇿 Tanzania|AF|53|37|-6.79|39.21',
	'ET':'🇪🇹 Etiopía|AF|48|37|9.03|38.74','V5':'🇳🇦 Namibia|AF|57|38|-22.56|17.08',
	'Z2':'🇿🇼 Zimbabue|AF|53|38|-17.83|31.05','9G':'🇬🇭 Ghana|AF|46|35|5.60|-0.19',
	'6W':'🇸🇳 Senegal|AF|46|35|14.72|-17.47','TU':'🇨🇮 Costa de Marfil|AF|46|35|5.35|-4.03',
	'EA8':'🇮🇨 Canarias|AF|36|33|28.29|-16.62','CT3':'🇵🇹 Madeira|AF|36|33|32.65|-16.91',
	'D4':'🇨🇻 Cabo Verde|AF|46|35|14.93|-23.51','3B8':'🇲🇺 Mauricio|AF|53|39|-20.16|57.50',
	'FR':'🇷🇪 Reunión|AF|53|39|-20.88|55.45','5R':'🇲🇬 Madagascar|AF|53|39|-18.88|47.51',

	/* Asia */
	'TA':'🇹🇷 Turquía|AS|39|20|39.93|32.86','4X':'🇮🇱 Israel|AS|39|20|31.77|35.21',
	'4Z':'🇮🇱 Israel|AS|39|20|31.77|35.21','VU':'🇮🇳 India|AS|41|22|28.61|77.21',
	'BV':'🇹🇼 Taiwán|AS|44|24|25.03|121.57','VR2':'🇭🇰 Hong Kong|AS|44|24|22.32|114.17',
	'HS':'🇹🇭 Tailandia|AS|49|26|13.75|100.50','9V':'🇸🇬 Singapur|AS|54|28|1.35|103.82',
	'9M':'🇲🇾 Malasia|AS|54|28|3.14|101.69','UN':'🇰🇿 Kazajistán|AS|30|17|51.16|71.47',
	'4S':'🇱🇰 Sri Lanka|AS|41|22|6.93|79.86','A4':'🇴🇲 Omán|AS|39|21|23.59|58.41',
	'A6':'🇦🇪 Emiratos Árabes|AS|39|21|24.47|54.37','9K':'🇰🇼 Kuwait|AS|39|21|29.38|47.99',
	'HZ':'🇸🇦 Arabia Saudita|AS|39|21|24.71|46.68','EP':'🇮🇷 Irán|AS|40|21|35.69|51.39',
	'JT':'🇲🇳 Mongolia|AS|32|23|47.89|106.91','UA':'🇷🇺 Rusia|EU|29|16|55.76|37.62',
	'RA':'🇷🇺 Rusia|EU|29|16|55.76|37.62','R':'🇷🇺 Rusia|EU|29|16|55.76|37.62',

	/* Oceanía */
	'YB':'🇮🇩 Indonesia|OC|54|28|-6.21|106.85','YE':'🇮🇩 Indonesia|OC|54|28|-6.21|106.85',
	'DU':'🇵🇭 Filipinas|OC|50|27|14.60|120.98','KH6':'🇺🇸 Hawái|OC|61|31|21.31|-157.86',
	'KH2':'🇬🇺 Guam|OC|64|27|13.44|144.79','FK':'🇳🇨 Nueva Caledonia|OC|56|32|-22.28|166.46',
	'3D2':'🇫🇯 Fiyi|OC|56|32|-18.14|178.44','V7':'🇲🇭 Islas Marshall|OC|65|31|7.09|171.38',
	'YJ':'🇻🇺 Vanuatu|OC|56|32|-17.73|168.32','T8':'🇵🇼 Palaos|OC|64|27|7.50|134.62',

	/* Caribe y Centroamérica */
	'C6':'🇧🇸 Bahamas|NA|11|8|25.04|-77.35','CO':'🇨🇺 Cuba|NA|11|8|23.11|-82.37',
	'CM':'🇨🇺 Cuba|NA|11|8|23.11|-82.37','HI':'🇩🇴 Rep. Dominicana|NA|11|8|18.49|-69.93',
	'HH':'🇭🇹 Haití|NA|11|8|18.59|-72.31','6Y':'🇯🇲 Jamaica|NA|11|8|18.02|-76.80',
	'KP4':'🇵🇷 Puerto Rico|NA|11|8|18.47|-66.11','KP3':'🇵🇷 Puerto Rico|NA|11|8|18.47|-66.11',
	'8P':'🇧🇧 Barbados|NA|11|9|13.10|-59.62','9Y':'🇹🇹 Trinidad y Tobago|SA|11|9|10.65|-61.51',
	'P4':'🇦🇼 Aruba|SA|11|9|12.52|-70.03','PJ2':'🇨🇼 Curazao|SA|11|9|12.11|-68.93',
	'V3':'🇧🇿 Belice|NA|11|7|17.25|-88.77','ZF':'🇰🇾 Islas Caimán|NA|11|8|19.29|-81.38',
	'FM':'🇲🇶 Martinica|NA|11|8|14.61|-61.07','FG':'🇬🇵 Guadalupe|NA|11|8|16.24|-61.53',
	'J3':'🇬🇩 Granada|NA|11|8|12.06|-61.75','J6':'🇱🇨 Santa Lucía|NA|11|8|14.01|-60.99',
	'V4':'🇰🇳 San Cristóbal|NA|11|8|17.30|-62.72','8R':'🇬🇾 Guyana|SA|12|9|6.80|-58.16',
	'PZ':'🇸🇷 Surinam|SA|12|9|5.85|-55.20','FY':'🇬🇫 Guayana Francesa|SA|12|9|4.92|-52.33',

	/* Europa (faltaban muchas de las que más se trabajan) */
	'EB':'🇪🇸 España|EU|37|14|40.42|-3.70',
	'CT':'🇵🇹 Portugal|EU|37|14|38.72|-9.14','SP':'🇵🇱 Polonia|EU|28|15|52.23|21.01',
	'OK':'🇨🇿 Chequia|EU|28|15|50.08|14.44','OM':'🇸🇰 Eslovaquia|EU|28|15|48.15|17.11',
	'HA':'🇭🇺 Hungría|EU|28|15|47.50|19.04','YO':'🇷🇴 Rumanía|EU|28|20|44.43|26.10',
	'LZ':'🇧🇬 Bulgaria|EU|28|20|42.70|23.32','SV':'🇬🇷 Grecia|EU|28|20|37.98|23.73',
	'OE':'🇦🇹 Austria|EU|28|15|48.21|16.37','HB':'🇨🇭 Suiza|EU|28|14|46.95|7.45',
	'OH':'🇫🇮 Finlandia|EU|18|15|60.17|24.94','LA':'🇳🇴 Noruega|EU|18|14|59.91|10.75',
	'OZ':'🇩🇰 Dinamarca|EU|18|14|55.68|12.57','EI':'🇮🇪 Irlanda|EU|27|14|53.35|-6.26',
	'LY':'🇱🇹 Lituania|EU|29|15|54.69|25.28','YL':'🇱🇻 Letonia|EU|29|15|56.95|24.11',
	'ES':'🇪🇪 Estonia|EU|29|15|59.44|24.75','UR':'🇺🇦 Ucrania|EU|29|16|50.45|30.52',
	'S5':'🇸🇮 Eslovenia|EU|28|15|46.06|14.51','9A':'🇭🇷 Croacia|EU|28|15|45.81|15.98',
	'YU':'🇷🇸 Serbia|EU|28|15|44.79|20.45','Z3':'🇲🇰 Macedonia del Norte|EU|28|15|41.99|21.43',

	'HK':'🇨🇴 Colombia|SA|12|9|4.71|-74.07','HJ':'🇨🇴 Colombia|SA|12|9|4.71|-74.07','HK0':'🇨🇴 Col. (San Andrés)|SA|12|9|12.58|-81.70',
	'5K':'🇨🇴 Colombia (especial)|SA|12|9|4.71|-74.07',
	'YV':'🇻🇪 Venezuela|SA|12|9|10.48|-66.90','YW':'🇻🇪 Venezuela|SA|12|9|10.48|-66.90',
	'OA':'🇵🇪 Perú|SA|12|10|-12.05|-77.04','OB':'🇵🇪 Perú|SA|12|10|-12.05|-77.04','OC':'🇵🇪 Perú|SA|12|10|-12.05|-77.04',
	'HC':'🇪🇨 Ecuador|SA|12|10|-0.23|-78.52','HD':'🇪🇨 Ecuador|SA|12|10|-0.23|-78.52',
	'HP':'🇵🇦 Panamá|NA|11|7|8.98|-79.52','3E':'🇵🇦 Panamá|NA|11|7|8.98|-79.52',
	'TI':'🇨🇷 Costa Rica|NA|11|7|9.93|-84.08',
	'TG':'🇬🇹 Guatemala|NA|11|7|14.63|-90.51',
	'YN':'🇳🇮 Nicaragua|NA|11|7|12.11|-86.24',
	'HQ':'🇭🇳 Honduras|NA|11|7|14.07|-87.19','HR':'🇭🇳 Honduras|NA|11|7|14.07|-87.19',
	'YS':'🇸🇻 El Salvador|NA|11|7|13.69|-89.22',
	'PY':'🇧🇷 Brasil|SA|13|11|-15.79|-47.88','PP':'🇧🇷 Brasil|SA|13|11|-15.79|-47.88','PU':'🇧🇷 Brasil|SA|13|11|-15.79|-47.88',
	'CE':'🇨🇱 Chile|SA|14|12|-33.45|-70.67','XQ':'🇨🇱 Chile|SA|14|12|-33.45|-70.67',
	'LU':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38','LO':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38','L2':'🇦🇷 Argentina|SA|14|13|-34.60|-58.38',
	'ZP':'🇵🇾 Paraguay|SA|14|11|-25.30|-57.64',
	'CX':'🇺🇾 Uruguay|SA|14|13|-34.90|-56.16',
	'CP':'🇧🇴 Bolivia|SA|12|10|-16.50|-68.15',
	'W':'🇺🇸 Estados Unidos|NA|7-9|3-5|38.90|-77.04','K':'🇺🇸 Estados Unidos|NA|7-9|3-5|38.90|-77.04','N':'🇺🇸 Estados Unidos|NA|7-9|3-5|38.90|-77.04','A':'🇺🇸 Estados Unidos|NA|7-9|3-5|38.90|-77.04',
	'XE':'🇲🇽 México|NA|10|6|19.43|-99.13','XF':'🇲🇽 México|NA|10|6|19.43|-99.13',
	'VE':'🇨🇦 Canadá|NA|1-5|1-5|45.42|-75.70','VA':'🇨🇦 Canadá|NA|1-5|1-5|45.42|-75.70','VY':'🇨🇦 Canadá|NA|1-5|1-5|45.42|-75.70',
	'G':'🇬🇧 Reino Unido|EU|27|14|51.51|-0.13','M':'🇬🇧 Reino Unido|EU|27|14|51.51|-0.13',
	'F':'🇫🇷 Francia|EU|27|14|48.85|2.35',
	'DL':'🇩🇪 Alemania|EU|28|14|52.52|13.40','DA':'🇩🇪 Alemania|EU|28|14|52.52|13.40',
	'EA':'🇪🇸 España|EU|37|14|40.42|-3.70',
	'I':'🇮🇹 Italia|EU|28|15|41.90|12.50',
	'ON':'🇧🇪 Bélgica|EU|27|14|50.85|4.35',
	'PA':'🇳🇱 Países Bajos|EU|27|14|52.37|4.90',
	'SM':'🇸🇪 Suecia|EU|18|14|59.33|18.07',
	'JA':'🇯🇵 Japón|AS|45|25|35.68|139.69','7J':'🇯🇵 Japón|AS|45|25|35.68|139.69',
	'VK':'🇦🇺 Australia|OC|55-60|29|-35.28|149.13','AX':'🇦🇺 Australia|OC|55-60|29|-35.28|149.13',
	'ZL':'🇳🇿 Nueva Zelanda|OC|60|32|-41.29|174.78',
	'BY':'🇨🇳 China|AS|33|24|39.90|116.41','BA':'🇨🇳 China|AS|33|24|39.90|116.41',
	'HL':'🇰🇷 Corea del Sur|AS|44|25|37.57|126.98',
};

/* Deriva la tabla sin coordenadas a partir de la completa: mismos nombres, continente,
   zona ITU y zona CQ, quitando los dos últimos campos. */
const PFX = Object.fromEntries(
	Object.entries(PFX_GEO).map(function(par){
		return [ par[0], par[1].split('|').slice(0,4).join('|') ];
	})
);

/* La tabla se declara aquí arriba, antes que cualquier módulo, y no junto al reproductor CW.
   Un 'const' no está inicializado hasta que su línea se ejecuta (zona muerta temporal), así que
   si algún consumidor llegara a correr antes se obtendría "MORSE is not defined" y el navegador
   abortaría el resto del script. Declarándola primero, ese orden deja de importar. */
/* Tabla Morse única y compartida (ITU-R M.1677-1).
   Estaba copiada tres veces —reproductor CW, decodificador en tiempo real y entrenamiento— y
   las copias YA habían divergido: solo la primera incluía la barra '/', que aparece en cualquier
   indicativo portátil (HK4RAU/P, W6AWG/4). Escribir eso en las otras dos producía un carácter
   mudo, sin aviso. Quinta duplicación de datos o fórmulas encontrada en esta revisión. */
const MORSE={
	A:'.-',B:'-...',C:'-.-.',D:'-..',E:'.',F:'..-.',G:'--.',H:'....',I:'..',J:'.---',
	K:'-.-',L:'.-..',M:'--',N:'-.',O:'---',P:'.--.',Q:'--.-',R:'.-.',S:'...',T:'-',
	U:'..-',V:'...-',W:'.--',X:'-..-',Y:'-.--',Z:'--..',
	0:'-----',1:'.----',2:'..---',3:'...--',4:'....-',5:'.....',
	6:'-....',7:'--...',8:'---..',9:'----.',
	'/':'-..-.','.':'.-.-.-',',':'--..--','?':'..--..','=':'-...-','-':'-....-',
	'+':'.-.-.','@':'.--.-.',' ':' '
};

/* Enlaza un manejador solo si el elemento existe.
   Había 21 llamadas del tipo document.getElementById('x').addEventListener(...) sueltas a nivel
   superior del script. Si UNO solo de esos elementos falta —porque se renombró un id, porque un
   panel se quitó, o porque el HTML no llegó a renderizarse— getElementById devuelve null,
   .addEventListener lanza un TypeError y el navegador ABORTA el resto del script. Todo lo que
   viniera después dejaba de inicializarse: por eso los paneles del final de la página (DX Spots,
   DMR, WSPR, D-STAR) se quedaban en "Cargando…" indefinidamente mientras los de arriba
   funcionaban bien. Con esto, un id que falte deja un botón inerte en vez de tumbar la página. */
/* QTH del usuario (Mi QTH, guardado en el navegador) o Medellín si no hay. La consola tenía su
   propia copia, RAU_QTH_MAPA, encerrada en el bloque del mapa: "Ahora mismo, ¿qué trabajo?" y el
   botón "Mi QTH" del conversor de coordenadas la leían desde fuera y fallaban con ReferenceError.
   La primera mostraba "No se pudieron leer las condiciones" aunque los datos llegaban bien. */
function rauQth(){
	try {
		var g=JSON.parse(localStorage.getItem('rau_mi_qth')||'null');
		if(g && typeof g.lat==='number' && typeof g.lng==='number') return g;
	} catch(e){}
	return {lat:6.2442,lng:-75.5812};
}

/* Ejecuta fn la primera vez que el elemento se ve (o está a punto de verse). Los paneles en vivo de
   la consola (D-STAR, WSPR, BrandMeister, Sol, aurora) arrancaban en cada una de las 61 URLs de
   herramientas aunque la consola estuviera plegada: peticiones y un WebSocket que nadie miraba. Un
   elemento dentro de algo oculto con display:none nunca intersecta, así que espera de verdad. */
function rauCuandoVisible(id, fn){
	var el = typeof id==='string' ? document.getElementById(id) : id;
	if(!el) return;
	if(!window.IntersectionObserver){ fn(); return; }
	var io=new IntersectionObserver(function(entradas){
		if(entradas.some(function(e){ return e.isIntersecting; })){ io.disconnect(); fn(); }
	}, {rootMargin:'300px'});
	io.observe(el);
}

function rauOn(id, evento, fn){
	var el=document.getElementById(id);
	if(!el){
		// Deja rastro en la consola para poder localizar el id que falta, sin romper nada
		if(window.console && console.warn) console.warn('[RAU] No existe el elemento #'+id+'; se omite el manejador "'+evento+'".');
		return null;
	}
	el.addEventListener(evento, fn);
	return el;
}

function rauFaseLunar(frac){
	if(frac<0.03||frac>0.97) return {nombre:'Luna Nueva',       icono:'🌑'};
	if(frac<0.22)            return {nombre:'Creciente',        icono:'🌒'};
	if(frac<0.28)            return {nombre:'Cuarto Creciente', icono:'🌓'};
	if(frac<0.47)            return {nombre:'Gibosa Creciente', icono:'🌔'};
	if(frac<0.53)            return {nombre:'Luna Llena',       icono:'🌕'};
	if(frac<0.72)            return {nombre:'Gibosa Menguante', icono:'🌖'};
	if(frac<0.78)            return {nombre:'Cuarto Menguante', icono:'🌗'};
	return {nombre:'Menguante', icono:'🌘'};
}

(function(){

/* ── CONSOLA: SELECTOR DE MODO (reemplaza contenido, no apila — incluye el catálogo como una opción más) ── */
(function(){
	var botones=document.querySelectorAll('.consola-modo-btn');
	if(!botones.length) return;
	var catalogo=document.getElementById('rau-cat-grid-wrap');

	function activar(btn){
		var modo=btn.dataset.modo;
		botones.forEach(function(b){
			var esActivo = (b===btn);
			b.classList.toggle('is-active', esActivo);
			// El estado visual ('is-active') no lo percibe un lector de pantalla; aria-selected sí.
			// Sin esto, las cinco pestañas se anunciaban todas igual y no había forma de saber
			// cuál estaba abierta. El tabindex móvil hace que Tab entre y salga del grupo de una
			// vez, en lugar de obligar a recorrer las cinco.
			b.setAttribute('aria-selected', esActivo ? 'true' : 'false');
			b.setAttribute('tabindex', esActivo ? '0' : '-1');
		});
		document.querySelectorAll('.consola-panel').forEach(function(panel){
			panel.classList.toggle('hidden', panel.dataset.modoPanel !== modo);
		});
		if(catalogo) catalogo.classList.toggle('hidden', modo!=='catalogo');
		// Un modo de trabajo vuelve a mostrar la consola completa (si había una herramienta abierta)
		if(modo!=='catalogo'){
			var tools=document.getElementById('rau-tools');
			if(tools) tools.classList.remove('rau-vista-herramienta');
			if(typeof window.rauUbicarReceptor==='function') window.rauUbicarReceptor();
		}
	}

	botones.forEach(function(btn, i){
		btn.addEventListener('click', function(){ activar(btn); });
		// Flechas para moverse entre pestañas, que es lo que espera quien navega con teclado
		btn.addEventListener('keydown', function(e){
			var salto = e.key==='ArrowRight' ? 1 : e.key==='ArrowLeft' ? -1 : 0;
			if(!salto){
				if(e.key==='Home') salto=-i;
				else if(e.key==='End') salto=botones.length-1-i;
				else return;
			}
			e.preventDefault();
			var destino=botones[(i+salto+botones.length)%botones.length];
			activar(destino);
			destino.focus();
		});
	});
})();

/* ── CONSOLA: BOTÓN "+ MÁS HERRAMIENTAS" — reutiliza el clic real sobre el modo Catálogo, sin duplicar lógica ── */
(function(){
	var btnMas=document.getElementById('consola-mas-herramientas');
	if(!btnMas) return;
	btnMas.addEventListener('click', function(){
		var btnCatalogo=document.querySelector('.consola-modo-btn[data-modo="catalogo"]');
		if(btnCatalogo){
			btnCatalogo.click();
			btnCatalogo.scrollIntoView({ behavior:'smooth', block:'start' });
		}
	});
})();

/* ── CONSOLA: MI QTH — configurable, con Medellín como fallback existente (no se rompe nada si no se configura) ── */
(function(){
	var toggle=document.getElementById('mi-qth-toggle');
	var panel=document.getElementById('mi-qth-panel');
	if(!toggle || !panel) return;
	toggle.addEventListener('click', function(){
		panel.classList.toggle('hidden');
		panel.classList.toggle('flex');
	});

	function gridACoordenadas(gridOriginal){
		var campo=(gridOriginal||'').slice(0,2).toUpperCase();
		var cuadro=(gridOriginal||'').slice(2,4);
		var sub=(gridOriginal||'').slice(4,6).toLowerCase();
		if(campo.length<2 || cuadro.length<2 || isNaN(parseInt(cuadro[0],10)) || isNaN(parseInt(cuadro[1],10))) return null;
		var A='ABCDEFGHIJKLMNOPQR', a='abcdefghijklmnopqr';
		if(A.indexOf(campo[0])<0 || A.indexOf(campo[1])<0) return null;
		var lng=A.indexOf(campo[0])*20-180, lat=A.indexOf(campo[1])*10-90;
		lng+=parseInt(cuadro[0],10)*2; lat+=parseInt(cuadro[1],10)*1;
		if(sub.length>=2 && a.indexOf(sub[0])>-1 && a.indexOf(sub[1])>-1){
			lng+=a.indexOf(sub[0])*(2/24)+(2/24)/2; lat+=a.indexOf(sub[1])*(1/24)+(1/24)/2;
		} else { lng+=1; lat+=0.5; }
		return { lat:lat, lng:lng };
	}

	var estadoEl=document.getElementById('mi-qth-estado');
	document.getElementById('mi-qth-guardar').addEventListener('click', function(){
		var grid=(document.getElementById('mi-qth-grid').value||'').trim();
		var coords=gridACoordenadas(grid);
		if(!coords){ estadoEl.textContent='Grid inválido — usa formato como FJ26 o FJ26ff.'; return; }
		try { localStorage.setItem('rau_mi_qth', JSON.stringify(coords)); } catch(e){}
		estadoEl.textContent='Guardado — recargando…';
		setTimeout(function(){ location.reload(); }, 600);
	});

	document.getElementById('mi-qth-geoloc').addEventListener('click', function(){
		if(!navigator.geolocation){ estadoEl.textContent='Este dispositivo no soporta geolocalización.'; return; }
		estadoEl.textContent='Obteniendo tu ubicación…';
		navigator.geolocation.getCurrentPosition(function(pos){
			var coords={ lat:pos.coords.latitude, lng:pos.coords.longitude };
			try { localStorage.setItem('rau_mi_qth', JSON.stringify(coords)); } catch(e){}
			estadoEl.textContent='Ubicación guardada — recargando…';
			setTimeout(function(){ location.reload(); }, 600);
		}, function(){
			estadoEl.textContent='No se pudo obtener tu ubicación — intenta con el Grid manual.';
		}, { timeout:8000 });
	});
})();

/* ── CONSOLA: SOL EN VIVO (NASA SDO, URL real confirmada) ── */
(function(){
	var img=document.getElementById('sol-vivo-img');
	if(!img) return;
	var canalActual='hmiif';
	function actualizarSol(){
		img.src='https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512_'+canalActual.toUpperCase()+'.jpg?t='+Date.now();
	}
	document.querySelectorAll('.rau-sol-canal').forEach(function(btn){
		btn.addEventListener('click', function(){
			canalActual=btn.dataset.canal;
			document.querySelectorAll('.rau-sol-canal').forEach(function(b){ b.classList.toggle('is-active', b===btn); b.classList.toggle('text-primary', b===btn); });
			actualizarSol();
		});
	});
	rauCuandoVisible(img, function(){ actualizarSol(); setInterval(actualizarSol, 15*60*1000); });

	// Óvalo auroral — URL confirmada directamente en la página oficial de NOAA/SWPC
	var imgAurora=document.getElementById('aurora-img');
	if(imgAurora){
		function actualizarAurora(){
			imgAurora.src='https://services.swpc.noaa.gov/images/animations/ovation/north/latest.jpg?t='+Date.now();
		}
		rauCuandoVisible(imgAurora, function(){ actualizarAurora(); setInterval(actualizarAurora, 15*60*1000); });
	}
})();

/* ── CONSOLA: FASE LUNAR (misma fórmula ya verificada en la herramienta Fase Lunar) ── */
(function(){
	var iconoEl=document.getElementById('consola-luna-icono');
	if(!iconoEl) return;
	var SINODICO=29.530588853;
	var NUEVA_REF=Date.UTC(2000,0,6,18,14);

	function actualizar(){
		var diasDesde=(Date.now()-NUEVA_REF)/86400000;
		var fase=(diasDesde % SINODICO + SINODICO) % SINODICO;
		var frac=fase/SINODICO;
		var iluminacion=Math.round((1-Math.cos(frac*2*Math.PI))/2*100);

		var _f=rauFaseLunar(frac), nombre=_f.nombre, icono=_f.icono;

		iconoEl.textContent=icono;
		document.getElementById('consola-luna-nombre').textContent=nombre;
		document.getElementById('consola-luna-ilum').textContent=iluminacion;
	}
	actualizar();
	setInterval(actualizar, 60*60*1000);

	// Posición en el cielo (Az/El) — usa SunCalc real (Meeus, precisión de USNO/timeanddate.com),
	// no una fórmula simplificada propia — la posición lunar es demasiado compleja para eso.
	function obtenerQthLuna(){
		try {
			var g=JSON.parse(localStorage.getItem('rau_mi_qth')||'null');
			if(g && typeof g.lat==='number' && typeof g.lng==='number') return g;
		} catch(e){}
		return {lat:6.2442,lng:-75.5812}; // Medellín — mismo fallback exacto que RAU_QTH_MAPA
	}
	function cargarSunCalc(cb){
		if(window.SunCalc){ cb(); return; }
		// El paquete suncalc dejó de traer "suncalc.js" en su raíz a partir de la v2: ahora publica
		// ./index.js (módulo ES) y ./suncalc.cjs. La URL que se pedía antes
		// (…/suncalc@2.0.1/suncalc.js) devuelve 404, se disparaba el manejador de error y por eso
		// el panel de la Luna mostraba siempre "Posición no disponible".
		// Se usa el endpoint +esm de jsDelivr, que es el que documenta el propio proyecto.
		// OJO al actualizar la versión: la v2 devuelve GRADOS con azimut desde el norte, mientras
		// que la v1 devolvía RADIANES desde el sur. Si algún día se baja a 1.x hay que convertir.
		import('https://cdn.jsdelivr.net/npm/suncalc@2.0.2/+esm')
			.then(function(mod){ window.SunCalc=mod.default||mod; cb(); })
			.catch(function(e){ rauErr(e);
				var posEl=document.getElementById('consola-luna-posicion');
				if(posEl) posEl.textContent='Posición no disponible';
			});
	}
	cargarSunCalc(function(){
		function actualizarPosicion(){
			var qth=obtenerQthLuna();
			var pos=SunCalc.getMoonPosition(new Date(), qth.lat, qth.lng);
			var posEl=document.getElementById('consola-luna-posicion');
			if(!posEl) return;
			// v2 entrega grados y azimut horario desde el norte, que es justo lo que se muestra
			if(pos.altitude<0){
				posEl.textContent='Bajo el horizonte ahora';
			} else {
				posEl.textContent='Posición: az '+Math.round(pos.azimuth)+'° · elev '+Math.round(pos.altitude)+'°';
			}
		}
		actualizarPosicion();
		setInterval(actualizarPosicion, 5*60*1000);
	});
})();

/* ── CONSOLA: PRÓXIMO EVENTO (malla o concurso, el que esté más cerca) ── */
(function(){
	var el=document.getElementById('consola-proximo-evento');
	if(!el) return;

	function finesDeSemanaCompletos(year, month){
		var lastDay=new Date(Date.UTC(year, month+1, 0)).getUTCDate();
		var out=[];
		for(var d=1; d<=lastDay; d++){
			var dt=new Date(Date.UTC(year, month, d));
			if(dt.getUTCDay()===6 && d+1<=lastDay) out.push(dt);
		}
		return out;
	}
	function nEsimoFinDeSemana(year, month, n){
		var ws=finesDeSemanaCompletos(year, month);
		return n<0 ? ws[ws.length+n] : ws[n-1];
	}
	function proximoConcurso(month, n){
		var ahora=new Date();
		var candidato=nEsimoFinDeSemana(ahora.getUTCFullYear(), month, n);
		if(!candidato || (candidato.getTime()+2*86400000) < ahora.getTime()){
			candidato=nEsimoFinDeSemana(ahora.getUTCFullYear()+1, month, n);
		}
		return candidato;
	}
	var CONCURSOS_INSTR=[
		{n:'ARRL Int. DX (Phone)', mes:2, n_semana:1}, {n:'CQ WPX (SSB)', mes:2, n_semana:-1},
		{n:'CQ WPX (CW)', mes:4, n_semana:-1}, {n:'ARRL Field Day', mes:5, n_semana:4},
		{n:'IARU HF Championship', mes:6, n_semana:2}, {n:'CQ WW DX (SSB)', mes:9, n_semana:-1},
		{n:'ARRL Sweepstakes (CW)', mes:10, n_semana:1}, {n:'CQ WW DX (CW)', mes:10, n_semana:-1},
	];

	function actualizar(){
		var ahora=new Date();

		// Próxima malla: martes 19:00 Colombia = miércoles 00:00 UTC (misma lógica ya verificada en Check-in)
		var diasHasta=(3 - ahora.getUTCDay() + 7) % 7;
		var inicioMallaEstaSemana=new Date(Date.UTC(ahora.getUTCFullYear(), ahora.getUTCMonth(), ahora.getUTCDate()+diasHasta, 0, 0, 0));
		var finMallaEstaSemana=new Date(inicioMallaEstaSemana.getTime()+2*3600000); // dura 2 horas (19:00-21:00 Colombia)
		var mallaEnCurso = ahora.getTime()>=inicioMallaEstaSemana.getTime() && ahora.getTime()<finMallaEstaSemana.getTime();

		var proximaMalla=inicioMallaEstaSemana;
		if(!mallaEnCurso && proximaMalla.getTime() <= ahora.getTime()) proximaMalla=new Date(proximaMalla.getTime()+7*86400000);

		var proximoConc=CONCURSOS_INSTR.map(function(c){ return {nombre:c.n, fecha:proximoConcurso(c.mes, c.n_semana)}; })
			.sort(function(a,b){ return a.fecha-b.fecha; })[0];

		// Un concurso de fin de semana corre ~48 h desde el sábado 00:00 UTC: mientras dure, eso manda
		var enCurso=CONCURSOS_INSTR.map(function(c){ return {nombre:c.n, fecha:nEsimoFinDeSemana(ahora.getUTCFullYear(), c.mes, c.n_semana)}; })
			.filter(function(c){ return c.fecha && ahora>=c.fecha && ahora<c.fecha.getTime()+48*3600000; })[0];
		if(mallaEnCurso){
			el.innerHTML='<span class="text-emerald-400 font-bold">🔴 Malla RAU EN VIVO ahora</span>';
		} else if(enCurso){
			var horasFin=Math.max(1, Math.round((enCurso.fecha.getTime()+48*3600000-ahora)/3600000));
			el.innerHTML='<a href="<?php echo esc_url( home_url( '/herramientas/concursos/' ) ); ?>" class="font-bold text-amber-400 hover:underline">🏆 En curso: '+enCurso.nombre+' · termina en '+horasFin+' h</a>';
		} else {
			var candidatos=[{nombre:'Malla RAU', fecha:proximaMalla}];
			if(proximoConc) candidatos.push(proximoConc);
			candidatos.sort(function(a,b){ return a.fecha-b.fecha; });
			var elegido=candidatos[0];
			var diasFaltan=Math.ceil((elegido.fecha-ahora)/86400000);
			el.textContent='Próximo: '+elegido.nombre+' — '+(diasFaltan<=0?'hoy':'en '+diasFaltan+' día'+(diasFaltan===1?'':'s'));
		}

		// Lista de próximos 3 concursos, reutilizando el mismo cálculo (sin duplicar la lógica de fechas)
		var listaEl=document.getElementById('consola-concursos-lista');
		if(listaEl){
			var todosOrdenados=CONCURSOS_INSTR.map(function(c){ return {nombre:c.n, fecha:proximoConcurso(c.mes, c.n_semana)}; })
				.sort(function(a,b){ return a.fecha-b.fecha; })
				.slice(0,3);
			listaEl.innerHTML=todosOrdenados.map(function(c){
				var dias=Math.ceil((c.fecha-ahora)/86400000), corre=ahora>=c.fecha;
				return '<div class="flex justify-between"><span>'+c.nombre+'</span><span class="'+(corre?'font-bold text-amber-400':'text-primary')+'">'+(corre?'en curso':dias+'d')+'</span></div>';
			}).join('');
		}
	}
	actualizar();
	setInterval(actualizar, 60*60*1000);
})();

/* ── CONSOLA: TENDENCIA SFI (14 días, reutiliza el historial ya existente) ── */
(function(){
	var svgEl=document.getElementById('consola-tendencia-svg');
	if(!svgEl) return;

	function dibujarTendencia(historial){
		var fechas=Object.keys(historial).sort().slice(-14);
		var valores=fechas.map(function(f){ return parseFloat(historial[f].sfi); }).filter(function(v){ return !isNaN(v); });
		if(valores.length<2){
			document.getElementById('consola-tendencia-texto').textContent='Sin suficientes datos históricos todavía';
			return;
		}
		var min=Math.min.apply(null,valores), max=Math.max.apply(null,valores);
		var rango=(max-min)||1;
		var ancho=280, alto=50, paso=ancho/(valores.length-1);
		var puntos=valores.map(function(v,i){
			var x=i*paso;
			var y=alto-4-((v-min)/rango)*(alto-8);
			return x.toFixed(1)+','+y.toFixed(1);
		}).join(' ');

		svgEl.innerHTML=
			'<defs><linearGradient id="gradTendencia" x1="0" y1="0" x2="0" y2="1">'
			+'<stop offset="0%" stop-color="#1fd5f9" stop-opacity="0.35"/>'
			+'<stop offset="100%" stop-color="#1fd5f9" stop-opacity="0"/>'
			+'</linearGradient></defs>'
			+'<polygon points="0,'+alto+' '+puntos+' '+ancho+','+alto+'" fill="url(#gradTendencia)"/>'
			+'<polyline points="'+puntos+'" fill="none" stroke="#1fd5f9" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';
		var tendenciaTexto = valores[valores.length-1]>valores[0] ? '📈 subiendo' : (valores[valores.length-1]<valores[0] ? '📉 bajando' : '➡️ estable');
		document.getElementById('consola-tendencia-texto').textContent='SFI '+valores[0]+' → '+valores[valores.length-1]+' ('+tendenciaTexto+')';
	}

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar_history', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(json.success) dibujarTendencia(json.data);
			else document.getElementById('consola-tendencia-texto').textContent='Sin datos disponibles';
		}).catch(function(e){ rauErr(e);
			document.getElementById('consola-tendencia-texto').textContent='No se pudo cargar el historial';
		});
})();

/* ── CONSOLA: LEADERBOARD DE RACHA (exclusivo de RAU) ── */
(function(){
	var lista=document.getElementById('consola-racha-lista');
	if(!lista) return;

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_racha_top', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success || !json.data.length){
				lista.innerHTML='<p class="text-[11px] text-muted-foreground">Nadie tiene racha activa todavía — sé el primero.</p>';
				return;
			}
			lista.innerHTML=json.data.map(function(r, i){
				return '<div class="flex items-center justify-between font-mono text-xs">'
					+'<span><span class="text-muted-foreground">'+(i+1)+'.</span> <strong class="text-foreground">'+r.indicativo+'</strong></span>'
					+'<span class="text-amber-400">🔥 '+r.racha+'</span>'
					+'</div>';
			}).join('');
		}).catch(function(e){ rauErr(e);
			lista.innerHTML='<p class="text-[11px] text-muted-foreground">No se pudo cargar.</p>';
		});
})();

/* ── CONSOLA: FRANJA DE INSTRUMENTOS (siempre activa, independiente del modo) ── */
(function(){
	var utcEl=document.getElementById('consola-utc');
	if(!utcEl) return;
	var localEl=document.getElementById('consola-local');

	function tick(){
		var d=new Date();
		var p=function(n){return String(n).padStart(2,'0');};
		utcEl.textContent=p(d.getUTCHours())+':'+p(d.getUTCMinutes())+':'+p(d.getUTCSeconds());
		if(localEl) localEl.textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
	}
	tick();
	setInterval(tick, 1000);

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success) return;
			document.getElementById('consola-sfi').textContent=json.data.sfi;
			document.getElementById('consola-ssn').textContent=json.data.ssn;
			document.getElementById('consola-k').textContent=json.data.kindex;
			document.getElementById('consola-aindex').textContent=json.data.aindex;
			document.getElementById('consola-xray').textContent=json.data.xray;

			// Mismos umbrales de SFI ya verificados en la fusión de propagación del mapa
			var sfiNum=parseFloat(json.data.sfi);
			var mejorBanda='—';
			if(!isNaN(sfiNum)){
				if(sfiNum<80) mejorBanda='40m / 80m';
				else if(sfiNum<100) mejorBanda='20m / 40m';
				else if(sfiNum<130) mejorBanda='20m / 17m / 15m';
				else mejorBanda='15m / 12m / 10m';
			}
			// Si el receptor ya midió una banda abierta, esa manda sobre la regla del índice solar
			if(!window.rauMejorMedida) document.getElementById('consola-mejor-banda').textContent=mejorBanda;
			var rnBandaEl=document.getElementById('rn-banda');
			if(rnBandaEl) rnBandaEl.innerHTML='📡 Banda: <span class="text-primary font-bold">'+mejorBanda+'</span>';
			var quehacerEl=document.getElementById('consola-que-hacer');
			if(quehacerEl && mejorBanda!=='—'){
				var primeraBanda=mejorBanda.split(' / ')[0];
				var chipExistente=document.getElementById('rn-chip-dinamico');
				if(chipExistente) chipExistente.remove();
				var chip=document.createElement('a');
				chip.id='rn-chip-dinamico';
				chip.href='<?php echo esc_url( home_url( '/herramientas/bandalert/' ) ); ?>';
				chip.className='rounded-full border border-primary/40 bg-primary/10 px-2.5 py-1 font-mono text-[10px] font-bold text-primary hover:bg-primary/20';
				chip.textContent='🟢 Explorar '+primeraBanda;
				quehacerEl.insertBefore(chip, quehacerEl.firstChild);
			}

			// kNum se calcula aquí, ANTES del semáforo de bandas que lo usa para la baja por tormenta
			// (bug real encontrado: antes se calculaba más abajo, después de usarse — la tormenta nunca se aplicaba)
			var kNum=parseFloat(json.data.kindex);

			// Impacto en Radio — misma regla ya verificada (SFI/K), sin inventar una clasificación nueva
			var impactoEl=document.getElementById('consola-impacto-radio');
			if(impactoEl && !isNaN(sfiNum) && !isNaN(kNum)){
				if(kNum>=5){ impactoEl.innerHTML='🔴 <span class="text-rose-400">Desfavorable HF</span>'; }
				else if(sfiNum>=100 && kNum<=2){ impactoEl.innerHTML='🟢 <span class="text-emerald-400">Favorable HF</span>'; }
				else { impactoEl.innerHTML='🟡 <span class="text-amber-400">Variable</span>'; }
			} else if(impactoEl){
				impactoEl.textContent='Datos insuficientes';
			}

			// Semáforo de 8 bandas — calibrado con los mismos umbrales de SFI ya verificados en "mejor banda",
			// desglosados banda por banda. K≥5 (tormenta) baja las bandas altas a rojo, mismo umbral ya usado en la alerta.
			if(!isNaN(sfiNum)){
				var tormenta = !isNaN(kNum) && kNum>=5;
				var umbrales = {
					'80m':{v:0},'40m':{v:0},                    // siempre razonables (NVIS/regional)
					'30m':{v:70},'20m':{v:80},                  // WARC y la banda más confiable
					'17m':{v:100},'15m':{v:100},
					'12m':{v:130},'10m':{v:130}
				};
				Object.keys(umbrales).forEach(function(banda){
					var el=document.querySelector('#consola-estado-bandas [data-banda="'+banda+'"] span:last-child');
					if(!el) return;
					var esAlta = ['17m','15m','12m','10m'].indexOf(banda)>-1;
					var semaforo;
					if(tormenta && esAlta){ semaforo='🔴'; }
					else if(sfiNum>=umbrales[banda].v){ semaforo='🟢'; }
					else if(sfiNum>=umbrales[banda].v-20){ semaforo='🟡'; }
					else { semaforo='🔴'; }
					if(window.rauOidoSemaforo && window.rauOidoSemaforo[banda]) semaforo=window.rauOidoSemaforo[banda];
					el.textContent=semaforo;
					window.rauBandaEstado = window.rauBandaEstado || {};
					window.rauBandaEstado[banda] = { semaforo:semaforo, umbral:umbrales[banda].v, sfi:sfiNum, tormenta:tormenta, k:kNum };
				});

				// Jerarquía visual: la banda #1 recomendada (la primera de mejorBanda) se destaca ligeramente sobre las otras 7
				document.querySelectorAll('.rau-banda-tile').forEach(function(t){
					t.classList.remove('ring-2','ring-primary','scale-105');
				});
				if(mejorBanda && mejorBanda!=='—'){
					var primeraBanda=mejorBanda.split(' / ')[0];
					var tileDestacado=document.querySelector('.rau-banda-tile[data-banda="'+primeraBanda+'"]');
					if(tileDestacado) tileDestacado.classList.add('ring-2','ring-primary','scale-105');
				}
			}

			// Alerta de tormenta geomagnética — mismo umbral K≥5 ya verificado en estimarPropagacion
			var alertaEl=document.getElementById('consola-alerta-storm');
			if(alertaEl){
				if(!isNaN(kNum) && kNum>=5){
					alertaEl.classList.remove('hidden'); alertaEl.classList.add('flex');
				} else {
					alertaEl.classList.add('hidden'); alertaEl.classList.remove('flex');
				}
			}
		}).catch(function(e){ rauErr(e);
			// Antes: catch vacío, dejaba "Calculando…" para siempre si esto fallaba
			var rnBandaErr=document.getElementById('rn-banda');
			if(rnBandaErr) rnBandaErr.innerHTML='📡 Banda: <span class="text-rose-400">sin datos</span>';
			var impactoErr=document.getElementById('consola-impacto-radio');
			if(impactoErr) impactoErr.textContent='Sin datos disponibles';
			var mejorBandaErr=document.getElementById('consola-mejor-banda');
			if(mejorBandaErr && mejorBandaErr.textContent==='—') mejorBandaErr.textContent='Sin datos';
		});
})();

/* ── CONSOLA: EXPLICACIÓN "¿POR QUÉ?" AL HACER CLIC EN UNA BANDA ── */
(function(){
	var explEl=document.getElementById('consola-banda-explicacion');
	if(!explEl) return;
	var nombresRegion={NA:'Norteamérica', EU:'Europa', SA:'Sur y Centroamérica', AF:'África', AS:'Asia', OC:'Oceanía'};
	function explicarBanda(banda){
		// Primero lo medido por el receptor del club; la regla del SFI solo donde no hay medición
		var o=window.rauOido;
		if(o && o.vigiladas.indexOf(banda)>-1){
			var m=o.bandas[banda];
			explEl.textContent = m
				? banda+': el receptor del club oyó '+m.n+(m.n===1?' estación':' estaciones')+' en los últimos 30 min ('+Object.keys(m.regiones).sort(function(a,b){ return m.regiones[b]-m.regiones[a]; }).map(function(r){ return nombresRegion[r]+' '+m.regiones[r]; }).join(', ')+'). La más lejana: '+m.lejos.c+' a '+m.lejos.km.toLocaleString('es-CO')+' km.'
				: banda+' 🔴: el receptor del club vigila esta banda y no oyó ninguna estación en los últimos 30 minutos.';
			explEl.classList.remove('hidden');
			return;
		}
		var estado=window.rauBandaEstado && window.rauBandaEstado[banda];
		if(!estado){ explEl.textContent='Datos insuficientes para explicar '+banda+' todavía.'; explEl.classList.remove('hidden'); return; }
		var razon;
		if(estado.tormenta && ['17m','15m','12m','10m'].indexOf(banda)>-1){
			razon=banda+' 🔴: hay una tormenta geomagnética (K='+estado.k+'), que afecta especialmente a las bandas altas.';
		} else if(estado.semaforo==='🟢'){
			razon=banda+' 🟢: el SFI actual ('+estado.sfi+') está en o por encima del umbral favorable para esta banda ('+estado.umbral+').';
		} else if(estado.semaforo==='🟡'){
			razon=banda+' 🟡: el SFI actual ('+estado.sfi+') está cerca pero debajo del umbral favorable ('+estado.umbral+') — condición variable.';
		} else {
			razon=banda+' 🔴: el SFI actual ('+estado.sfi+') está bien debajo del umbral necesario ('+estado.umbral+') para esta banda ahora mismo.';
		}
		explEl.textContent=razon;
		explEl.classList.remove('hidden');
	}
	document.querySelectorAll('.rau-banda-tile').forEach(function(tile){
		tile.addEventListener('click', function(){ explicarBanda(tile.dataset.banda); });
		tile.addEventListener('keydown', function(e){
			if(e.key==='Enter' || e.key===' '){ e.preventDefault(); explicarBanda(tile.dataset.banda); }
		});
	});
})();



/* ── CENTRO DE MANDO: MODO "EN CONCURSO" ── */
(function(){
	var nombreEl=document.getElementById('cm-proximo-nombre');
	if(!nombreEl) return;

	// Misma lógica de fecha ya verificada contra 3 fechas oficiales reales (CQ WW, WPX)
	function finesDeSemanaCompletos(year, month){
		var lastDay=new Date(Date.UTC(year, month+1, 0)).getUTCDate();
		var out=[];
		for(var d=1; d<=lastDay; d++){
			var dt=new Date(Date.UTC(year, month, d));
			if(dt.getUTCDay()===6 && d+1<=lastDay) out.push(dt);
		}
		return out;
	}
	function nEsimoFinDeSemana(year, month, n){
		var ws=finesDeSemanaCompletos(year, month);
		return n<0 ? ws[ws.length+n] : ws[n-1];
	}
	function proximo(month, n){
		var ahora=new Date();
		var candidato=nEsimoFinDeSemana(ahora.getUTCFullYear(), month, n);
		if(!candidato || (candidato.getTime()+2*86400000) < ahora.getTime()){
			candidato=nEsimoFinDeSemana(ahora.getUTCFullYear()+1, month, n);
		}
		return candidato;
	}
	function diasFaltantes(dt){
		return Math.ceil((dt.getTime()-new Date().getTime())/86400000);
	}

	// Lista compartida con la portada (rau_get_concursos() en functions.php)
	var CONCURSOS_CM=<?php echo wp_json_encode( rau_get_concursos() ); ?>;
	var proximos=CONCURSOS_CM.map(function(c){
		var dt=proximo(c.mes, c.n_semana);
		return {nombre:c.n, fecha:dt, faltan:diasFaltantes(dt)};
	}).sort(function(a,b){ return a.fecha-b.fecha; });

	if(proximos.length){
		var sig=proximos[0];
		nombreEl.textContent=sig.nombre;
		document.getElementById('cm-proximo-faltan').textContent = sig.faltan<=0 ? '¡Es este fin de semana!' : 'Faltan '+sig.faltan+' días';
	}

	// Contador de QSO — misma fórmula ya verificada en Contador de Tasa de QSO
	var total=0, inicio=Date.now();
	document.getElementById('cm-qso-add').addEventListener('click', function(){
		total++;
		document.getElementById('cm-qso-total').textContent=total;
		var segundos=(Date.now()-inicio)/1000;
		if(segundos<30){
			document.getElementById('cm-qso-rate').textContent='calculando…';
		} else {
			var horas=segundos/3600;
			document.getElementById('cm-qso-rate').textContent=Math.round(total/horas)+' QSO/hora';
		}
	});
})();

/* ── CONSOLA: MAPA MUNDIAL CON GRAYLINE + FUSIÓN DE PROPAGACIÓN + ACTIVIDAD DE RAU ── */
(function(){
	var mapaEl=document.getElementById('consola-mapa');
	if(!mapaEl) return;

	var RAU_QTH_MAPA=(function(){
		try {
			var guardado=JSON.parse(localStorage.getItem('rau_mi_qth')||'null');
			if(guardado && typeof guardado.lat==='number' && typeof guardado.lng==='number') return guardado;
		} catch(e){}
		return {lat:6.2442,lng:-75.5812}; // Medellín — fallback existente, sin cambios
	})();

	// Unidad de distancia — persistida en localStorage, mismo patrón ya usado en Favoritos
	var UNIDAD_KEY='rau_unidad_distancia';
	function getUnidad(){ try { return localStorage.getItem(UNIDAD_KEY)||'km'; } catch(e){ return 'km'; } }
	function formatearDistancia(km){
		if(getUnidad()==='mi'){
			return Math.round(km*0.621371).toLocaleString('es-CO')+' mi';
		}
		return Math.round(km).toLocaleString('es-CO')+' km';
	}
	var btnUnidad=document.getElementById('mapa-unidad-toggle');
	if(btnUnidad){
		btnUnidad.textContent=getUnidad();
		btnUnidad.addEventListener('click', function(){
			var nueva = getUnidad()==='km' ? 'mi' : 'km';
			try { localStorage.setItem(UNIDAD_KEY, nueva); } catch(e){}
			btnUnidad.textContent=nueva;
		});
	}

	// Clima — Open-Meteo (verificado: gratis, sin clave, 10,000 llamadas/día, CORS habilitado)
	var WMO_CODES={
		0:'☀️ Despejado', 1:'🌤️ Mayormente despejado', 2:'⛅ Parcialmente nublado', 3:'☁️ Nublado',
		45:'🌫️ Niebla', 48:'🌫️ Niebla escarchada',
		51:'🌦️ Llovizna ligera', 53:'🌦️ Llovizna', 55:'🌧️ Llovizna densa',
		61:'🌧️ Lluvia ligera', 63:'🌧️ Lluvia', 65:'🌧️ Lluvia fuerte',
		71:'🌨️ Nieve ligera', 73:'🌨️ Nieve', 75:'❄️ Nieve fuerte',
		80:'🌦️ Chubascos ligeros', 81:'🌧️ Chubascos', 82:'⛈️ Chubascos fuertes',
		95:'⛈️ Tormenta eléctrica',
	};
	function climaTexto(codigo){ return WMO_CODES[codigo] || '🌡️ Condición ' + codigo; }
	function obtenerClima(lat, lng, cb){
		fetch('https://api.open-meteo.com/v1/forecast?latitude='+lat.toFixed(2)+'&longitude='+lng.toFixed(2)+'&current_weather=true', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(json && json.current_weather) cb(json.current_weather);
				else cb(null);
			}).catch(function(e){ rauErr(e); cb(null); });
	}
	var REPS_MAPA=<?php echo wp_json_encode( $repetidores_consola ?: [] ); ?>;
	var estadoSolar={sfi:null, kindex:null}; // se llena async desde rau_solar

	function diaDelAnio(fecha){
		var inicio=Date.UTC(fecha.getUTCFullYear(),0,1);
		return Math.floor((fecha.getTime()-inicio)/86400000)+1;
	}
	function declinacionSolar(N){
		return 23.45*Math.sin((360/365)*(284+N)*Math.PI/180);
	}
	function subsolarLng(fecha){
		var horasDecimal=fecha.getUTCHours()+fecha.getUTCMinutes()/60;
		return -(horasDecimal-12)*15;
	}
	// Fórmula del terminador verificada contra jgiesen.de: tan(lat)=-cos(tau)/tan(declinación)
	function latTerminador(lng, declinacion, subLng){
		if(Math.abs(declinacion)<0.01) return 0;
		var decR=declinacion*Math.PI/180;
		var tau=(lng-subLng)*Math.PI/180;
		return Math.atan(-Math.cos(tau)/Math.tan(decR))*180/Math.PI;
	}
	// Elevación solar en un punto — verificada: da 87.4° al mediodía local exacto (teórico: 90-|lat-decl|=87.38°)
	// y ~1° cuando el sol está a 90° de longitud (el terminador, tal como se espera)
	function elevacionSolar(lat, lng, declinacion, subLng){
		var latR=lat*Math.PI/180, decR=declinacion*Math.PI/180;
		var tau=(lng-subLng)*Math.PI/180;
		var cosCenit=Math.sin(latR)*Math.sin(decR)+Math.cos(latR)*Math.cos(decR)*Math.cos(tau);
		return 90-Math.acos(Math.max(-1,Math.min(1,cosCenit)))*180/Math.PI;
	}

	// Amanecer/atardecer — fórmula NOAA GML, verificada contra Medellín (da 5:58am/6:06pm, esperado ~6:00/~18:00)
	function amanecerAtardecer(lat, lng, declinacion){
		var latR=lat*Math.PI/180, decR=declinacion*Math.PI/180;
		var cosH0=-Math.tan(latR)*Math.tan(decR);
		if(cosH0<-1 || cosH0>1) return null; // sol de medianoche o noche polar
		var h0=Math.acos(cosH0)*180/Math.PI;
		var medioDiaUTC=12-lng/15;
		var amanecer=((medioDiaUTC-h0/15)%24+24)%24;
		var atardecer=((medioDiaUTC+h0/15)%24+24)%24;
		return {amanecer:amanecer, atardecer:atardecer};
	}
	function formatearHoraUTC(horaDecimal){
		var h=Math.floor(horaDecimal), m=Math.round((horaDecimal-h)*60);
		if(m===60){ m=0; h=(h+1)%24; }
		return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0');
	}

	// Misma conversión a grid Maidenhead ya verificada varias veces (da FJ26 para Medellín)
	function latLngAGrid(lat,lng){
		lat+=90; lng+=180;
		var A='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		var campo=A[Math.floor(lng/20)]+A[Math.floor(lat/10)];
		var cuadro=Math.floor((lng%20)/2)+''+Math.floor(lat%10);
		return campo+cuadro;
	}

	var distanciaYRumbo=rauDistanciaRumbo;

	/* Heurística de propagación — basada en umbrales cruzados de múltiples fuentes reales
	   (ARRL, OERadio, etc.): SFI define el "techo" de bandas, K define qué tan turbulento
	   está el camino, y el grayline es un efecto real y documentado de baja absorción.
	   También considera NVIS para trayectos cortos (<500km, verificado contra CloudRF/Wikipedia)
	   y trayectos con componente polar (vértice del círculo máximo, donde el K-index pega más fuerte). */
	function latMaximaCirculo(lat1, bearing){
		var l1=lat1*Math.PI/180, b=bearing*Math.PI/180;
		var coslat=Math.max(-1,Math.min(1, Math.cos(l1)*Math.sin(b)));
		return Math.acos(coslat)*180/Math.PI;
	}

	function estimarPropagacion(sfi, kindex, elevRau, elevDestino, distanciaKm, rumbo){
		// Trayecto corto: aplica NVIS, no skip ionosférico — nunca recomendar bandas altas aquí
		if(distanciaKm<500){
			var esDeDiaEnRau = elevRau>0;
			return {
				bandas: esDeDiaEnRau ? '40m (NVIS)' : '80m / 160m (NVIS)',
				calidad: 'Trayecto corto — no aplica skip',
				colorCalidad: 'text-amber-400',
				notaGrayline: null,
				notaEspecial: '📡 A menos de 500 km, el trayecto es territorio NVIS (antena horizontal baja), no propagación por salto ionosférico normal.'
			};
		}

		var bandas;
		if(sfi===null) bandas='(sin datos de SFI)';
		else if(sfi<80) bandas='40m / 80m';
		else if(sfi<100) bandas='20m / 40m';
		else if(sfi<130) bandas='20m / 17m / 15m';
		else bandas='15m / 12m / 10m';

		var latVertice = latMaximaCirculo(RAU_QTH_MAPA.lat, rumbo);
		var esPolar = latVertice>60;

		var calidad, colorCalidad;
		if(kindex===null){ calidad='sin datos de K'; colorCalidad='text-muted-foreground'; }
		else if(kindex<=1){ calidad=esPolar?'Buena (revisar, camino con componente polar)':'Excelente'; colorCalidad='text-emerald-400'; }
		else if(kindex===2){ calidad=esPolar?'Regular (camino polar, K ya afecta aquí)':'Buena'; colorCalidad = esPolar?'text-amber-400':'text-emerald-400'; }
		else if(kindex<=4){ calidad='Regular (geomagnéticamente inquieto)'+(esPolar?' — camino polar, más afectado':''); colorCalidad='text-amber-400'; }
		else { calidad='Mala (tormenta geomagnética)'+(esPolar?' — camino polar, muy afectado':''); colorCalidad='text-rose-400'; }

		var rauEnGrayline = Math.abs(elevRau)<8;
		var destinoEnGrayline = Math.abs(elevDestino)<8;
		var notaGrayline = null;
		if(rauEnGrayline && destinoEnGrayline) notaGrayline='✨✨ Ambos extremos en grayline — condiciones ideales para DX en bandas bajas ahora mismo';
		else if(rauEnGrayline || destinoEnGrayline) notaGrayline='✨ Un extremo está en grayline — posible mejora en bandas bajas';

		return {bandas:bandas, calidad:calidad, colorCalidad:colorCalidad, notaGrayline:notaGrayline, notaEspecial:null};
	}

	function cargarLeaflet(cb){
		if(window.L){ cb(); return; }
		var link=document.createElement('link');
		link.rel='stylesheet'; link.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
		document.head.appendChild(link);
		var script=document.createElement('script');
		script.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
		script.onload=cb;
		document.head.appendChild(script);
	}

	function cargarSatelliteJs(cb){
		if(window.satellite){ cb(); return; }
		var script=document.createElement('script');
		script.src='https://cdn.jsdelivr.net/npm/satellite.js@5.0.0/dist/satellite.min.js';
		script.onload=cb;
		document.head.appendChild(script);
	}

	// Estado SFI/K compartido con la franja de instrumentos — misma fuente, sin inventar datos
	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(json.success){
				estadoSolar.sfi=parseFloat(json.data.sfi);
				estadoSolar.kindex=parseFloat(json.data.kindex);
			}
		}).catch(function(e){ rauErr(e);});

	cargarLeaflet(function(){
		var map=L.map('consola-mapa', { scrollWheelZoom:false, worldCopyJump:true }).setView([15,-60], 2);
		// En celular el mapa está en la pestaña «Mirar», oculta al cargar: Leaflet lo midió en 0 px y
		// lo dejaba gris. Se vuelve a medir cada vez que cambia de tamaño (al mostrarse la pestaña).
		if(window.ResizeObserver) new ResizeObserver(function(){ map.invalidateSize(); }).observe(mapaEl);

		/* Lo que oye HK4RAU (19.47.0): una línea por círculo máximo desde el receptor hasta cada
		   estación oída en la banda sintonizada; más intensa cuanto mejor la señal. Tocar una la
		   sintoniza en el receptor. */
		var capaOido=L.layerGroup().addTo(map), bandaOido=null;
		var bandasOido=[['160m',1800,2000],['80m',3500,4000],['40m',7000,7300],['30m',10100,10150],['20m',14000,14350],['17m',18068,18168],['15m',21000,21450],['12m',24890,24990],['10m',28000,29700]];
		function bandaDeKhz(k){ var b=null; bandasOido.forEach(function(x){ if(k>=x[1] && k<=x[2]) b=x[0]; }); return b; }
		function arcoMaximo(a, b){
			var r=Math.PI/180, f1=a[0]*r, l1=a[1]*r, f2=b[0]*r, l2=b[1]*r;
			var d=2*Math.asin(Math.sqrt(Math.pow(Math.sin((f2-f1)/2),2)+Math.cos(f1)*Math.cos(f2)*Math.pow(Math.sin((l2-l1)/2),2)));
			if(!d) return [a, b];
			var pts=[], prev=null;
			for(var i=0;i<=40;i++){
				var t=i/40, A=Math.sin((1-t)*d)/Math.sin(d), B=Math.sin(t*d)/Math.sin(d);
				var x=A*Math.cos(f1)*Math.cos(l1)+B*Math.cos(f2)*Math.cos(l2), y=A*Math.cos(f1)*Math.sin(l1)+B*Math.cos(f2)*Math.sin(l2), z=A*Math.sin(f1)+B*Math.sin(f2);
				var lat=Math.atan2(z, Math.sqrt(x*x+y*y))/r, lng=Math.atan2(y, x)/r;
				// Longitudes continuas: sin esto, una ruta que cruza el antimeridiano atraviesa todo el mapa
				if(prev!==null){ while(lng-prev>180) lng-=360; while(prev-lng>180) lng+=360; }
				prev=lng; pts.push([lat, lng]);
			}
			return pts;
		}
		function pintarOido(){
			capaOido.clearLayers();
			var o=window.rauOido, chk=document.getElementById('mapa-mostrar-oido');
			if(!o || (chk && !chk.checked)) return;
			var rx=[o.rx.lat, o.rx.lng];
			o.reportes.filter(function(x){ return !bandaOido || x.b===bandaOido; }).forEach(function(x){
				var fuerza=Math.max(0.15, Math.min(1, (x.snr+28)/26));
				L.polyline(arcoMaximo(rx, [x.lat, x.lng]), {color:'#38bdf8', weight:1.2, opacity:0.25+0.55*fuerza, interactive:false}).addTo(capaOido);
				L.circleMarker([x.lat, x.lng], {radius:3.5, color:'#38bdf8', weight:1, fillColor:'#38bdf8', fillOpacity:fuerza})
					.bindTooltip(x.c+(x.pais?' · '+x.pais:'')+' · '+x.km.toLocaleString('es-CO')+' km · '+x.snr+' dB · '+x.m+' en '+x.b)
					.on('click', function(){ if(window.RauRx) window.RauRx.sintonizar(x.f, 'usb'); })
					.addTo(capaOido);
			});
			L.circleMarker(rx, {radius:6, color:'#0ea5e9', weight:2, fillColor:'#e0f2fe', fillOpacity:1}).bindTooltip('Receptor HK4RAU · '+o.rx.lugar+(bandaOido?' · mostrando '+bandaOido:'')).addTo(capaOido);
		}
		document.addEventListener('rau:oido', pintarOido);
		document.addEventListener('rau:frecuencia', function(ev){ var b=bandaDeKhz(ev.detail.khz); if(b!==bandaOido){ bandaOido=b; pintarOido(); } });
		var chkOidoMapa=document.getElementById('mapa-mostrar-oido');
		if(chkOidoMapa) chkOidoMapa.addEventListener('change', pintarOido);
		if(window.RauRx && window.RauRx.estado) bandaOido=bandaDeKhz(window.RauRx.estado().khz);
		pintarOido();
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap' }).addTo(map);

		L.circleMarker([RAU_QTH_MAPA.lat, RAU_QTH_MAPA.lng], { radius:7, color:'#fff', weight:2, fillColor:'#1fd5f9', fillOpacity:1 })
			.addTo(map).bindPopup('<strong>RAU — Medellín</strong><br>Tu QTH');

		// ── Modos del mapa: Propagación (por defecto) / Repetidores / DX — reutilizan el mismo mapa, sin cargar capas hasta pedirse ──
		var capaRepetidoresMapa=L.layerGroup();
		var capaSateliteMapa=L.layerGroup().addTo(map); // visible por defecto — mantiene el comportamiento actual
		var chkSatelite=document.getElementById('mapa-mostrar-satelite');
		if(chkSatelite) chkSatelite.addEventListener('change', function(){
			map[this.checked?'addLayer':'removeLayer'](capaSateliteMapa);
		});
		var capaDxMapa=L.layerGroup();
		var repetidoresYaCargados=false;

		function cargarCapaRepetidores(){
			if(repetidoresYaCargados) return;
			repetidoresYaCargados=true;
			fetch('/wp-json/rau/v1/repetidores').then(function(r){ return r.json(); }).then(function(json){
				var lista = json.repetidores || [];
				lista.forEach(function(r){
					if(!r.lat || !r.lng) return;
					var color = r.estado==='activo' ? '#34d399' : r.estado==='mantenimiento' ? '#fbbf24' : '#fb7185';
					L.circleMarker([r.lat, r.lng], { radius:7, color:'#0b0f1a', weight:2, fillColor:color, fillOpacity:1 })
						.addTo(capaRepetidoresMapa)
						.bindPopup('<strong>'+(r.nombre||r.indicativo||'Repetidor')+'</strong><br>'+(r.frecuencia||'')+' '+(r.offset||''));
				});
			}).catch(function(e){ rauErr(e);});
		}

		function cargarCapaDx(){
			capaDxMapa.clearLayers();
			var spots = window.rauUltimosDxSpots || [];
			spots.forEach(function(s){
				if(!s.dx) return;
				var raw=s.dx.toUpperCase();
				var found=null;
				for(var l=4; l>=1; l--){
					var probar=raw.slice(0,l);
					if(typeof PFX_GEO!=='undefined' && PFX_GEO[probar]){ found=PFX_GEO[probar]; break; }
				}
				if(!found) return;
				var partes=found.split('|');
				if(partes.length<6) return;
				var lat=parseFloat(partes[4]), lng=parseFloat(partes[5]);
				if(isNaN(lat)||isNaN(lng)) return;
				L.circleMarker([lat,lng], { radius:6, color:'#0b0f1a', weight:1.5, fillColor:'#a78bfa', fillOpacity:0.9 })
					.addTo(capaDxMapa)
					.bindPopup('<strong>'+s.dx+'</strong><br>'+(s.freq||'')+' MHz');
			});
		}

		document.querySelectorAll('.rau-mapa-modo').forEach(function(btn){
			btn.addEventListener('click', function(){
				document.querySelectorAll('.rau-mapa-modo').forEach(function(b){
					b.classList.remove('is-active','border-primary/50','bg-primary/15','text-primary','font-bold');
					b.classList.add('border-border/70','bg-background','text-muted-foreground');
				});
				btn.classList.add('is-active','border-primary/50','bg-primary/15','text-primary','font-bold');
				btn.classList.remove('border-border/70','bg-background','text-muted-foreground');

				map.removeLayer(capaRepetidoresMapa);
				map.removeLayer(capaDxMapa);
				var modo=btn.dataset.modoMapa;
				if(modo==='repetidores'){ cargarCapaRepetidores(); map.addLayer(capaRepetidoresMapa); }
				else if(modo==='dx'){ cargarCapaDx(); map.addLayer(capaDxMapa); }
				// modo 'propagacion' no necesita capa extra — es el comportamiento de clic ya existente
			});
		});

		obtenerClima(RAU_QTH_MAPA.lat, RAU_QTH_MAPA.lng, function(c){
			var el=document.getElementById('mapa-clima-qth');
			if(!el) return;
			if(c) el.textContent='Clima en tu QTH: '+climaTexto(c.weathercode)+' · '+Math.round(c.temperature)+'°C';
			else el.textContent='Clima en tu QTH: no disponible';
		});

		function formatearFreq(freq){
			var limpio=(freq||'').toString().replace(/\s*mhz\s*$/i,'').trim();
			return limpio ? limpio+' MHz' : '—';
		}
		REPS_MAPA.forEach(function(r){
			L.circleMarker([r.lat, r.lng], { radius:4, color:'#0b0f1a', weight:1, fillColor:'#34d399', fillOpacity:1 })
				.addTo(map).bindPopup('<strong>'+r.name+'</strong><br>'+formatearFreq(r.freq));
		});

		// Actividad de RAU hoy — el diferenciador real, ningún HamClock del mundo tiene esto
		var marcadoresComunidad=[];
		function gridACoordenadas(grid){
			grid=grid.toUpperCase();
			var A='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			var lng=A.indexOf(grid[0])*20 + parseInt(grid[2])*2 - 180;
			var lat=A.indexOf(grid[1])*10 + parseInt(grid[3]) - 90;
			var lngSize=2, latSize=1;
			if(grid.length>=6){
				lng += A.indexOf(grid[4])*(2/24);
				lat += A.indexOf(grid[5])*(1/24);
				lngSize=2/24; latSize=1/24;
			}
			return { lat: lat+latSize/2, lng: lng+lngSize/2 };
		}
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_checkin_hoy_mapa', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(!json.success) return;
				json.data.forEach(function(p){
					if(!p.grid) return;
					var c=gridACoordenadas(p.grid);
					var m=L.circleMarker([c.lat,c.lng], { radius:6, color:'#0b0f1a', weight:1.5, fillColor:'#fbbf24', fillOpacity:1 })
						.bindPopup('<strong>'+p.indicativo+'</strong><br>'+(p.ciudad||'')+(p.hora ? ' · reportado a las '+p.hora+' (hora Colombia)' : ' · reportado hoy'));
					marcadoresComunidad.push(m);
					m.addTo(map);
				});
			}).catch(function(e){ rauErr(e);});

		document.getElementById('mapa-mostrar-comunidad').addEventListener('change', function(){
			marcadoresComunidad.forEach(function(m){
				if(this.checked) m.addTo(map); else map.removeLayer(m);
			}.bind(this));
		});

		// Seguimiento en vivo de la ISS — misma API de satellite.js verificada contra 6 fuentes
		// independientes (eciToGeodetic + degreesLat/degreesLong), mismo endpoint rau_tle ya usado
		// en Predicción de Pasadas.
		var marcadorISS=null, trazaISS=null, satrecISS=null, huellaISS=null;
		function iconoISS(){
			return L.divIcon({
				html:'<svg width="20" height="20" viewBox="0 0 20 20"><circle cx="10" cy="10" r="8" fill="none" stroke="#a78bfa" stroke-width="2"/><circle cx="10" cy="10" r="3" fill="#a78bfa"/></svg>',
				className:'', iconSize:[20,20], iconAnchor:[10,10]
			});
		}
		// Radio de la huella de cobertura — verificado: da ~2,252 km para la ISS (420km),
		// dentro del rango de referencia conocido en la comunidad satelital (~2,000-2,300 km)
		function radioHuellaKm(alturaKm){
			var R=6371;
			var anguloCentral=Math.acos(R/(R+alturaKm));
			return R*anguloCentral;
		}
		function actualizarISS(){
			if(!satrecISS || !window.satellite) return;
			var ahora=new Date();
			var gmst=satellite.gstime(ahora);
			var pv=satellite.propagate(satrecISS, ahora);
			if(!pv || !pv.position) return;
			var geo=satellite.eciToGeodetic(pv.position, gmst);
			var lat=satellite.degreesLat(geo.latitude), lng=satellite.degreesLong(geo.longitude);

			if(!marcadorISS){
				marcadorISS=L.marker([lat,lng], { icon:iconoISS(), title:'Posición actual de la ISS' }).addTo(capaSateliteMapa).bindPopup('<strong>ISS</strong><br>Altura: '+geo.height.toFixed(0)+' km');
			} else {
				marcadorISS.setLatLng([lat,lng]);
			}

			if(huellaISS) capaSateliteMapa.removeLayer(huellaISS);
			huellaISS=L.circle([lat,lng], { radius: radioHuellaKm(geo.height)*1000, color:'#a78bfa', weight:1, fillColor:'#a78bfa', fillOpacity:0.05, interactive:false }).addTo(capaSateliteMapa);

			// Traza de tierra: 20 min atrás a 20 min adelante, cada 2 min
			var puntosTraza=[];
			for(var m=-20; m<=20; m+=2){
				var t=new Date(ahora.getTime()+m*60000);
				var pv2=satellite.propagate(satrecISS, t);
				if(!pv2 || !pv2.position) continue;
				var gmst2=satellite.gstime(t);
				var geo2=satellite.eciToGeodetic(pv2.position, gmst2);
				puntosTraza.push([satellite.degreesLat(geo2.latitude), satellite.degreesLong(geo2.longitude)]);
			}
			if(trazaISS) capaSateliteMapa.removeLayer(trazaISS);
			trazaISS=L.polyline(puntosTraza, { color:'#a78bfa', weight:1.5, dashArray:'2 4', interactive:false }).addTo(capaSateliteMapa);
		}
		var todosLosTLE=[];
		var selectSat=document.getElementById('mapa-satelite-select');

		// Frecuencias verificadas contra AMSAT/AMSAT-UK (ver notas por satélite). FM = repetidor de frecuencia
		// única, Doppler completo TX+RX. Lineal = transpondedor SSB/CW de banda ancha — no existe "la"
		// frecuencia de QSO, así que se muestra el beacon CW (una señal real de frecuencia única) con Doppler.
		var FRECUENCIAS_SAT = {
			'ZARYA': { tipo:'fm', up:145.990, down:437.800, tono:'PL 67.0', nota:'Repetidor de voz — no siempre activo. También tiene APRS en 145.825 simplex.' },
			'SO-50': { tipo:'fm', up:145.850, down:436.795, tono:'PL 67.0' },
			'AO-91': { tipo:'fm', up:435.250, down:145.960, tono:'PL 67.0' },
			'FO-29': { tipo:'lineal', beacon:435.795, nota:'Transpondedor lineal invertido 145.900-146.000↑/435.800-435.900↓ — el beacon es una referencia de frecuencia única, no "la" frecuencia de QSO.' },
			'RS-44': { tipo:'lineal', beacon:435.605, nota:'Transpondedor lineal invertido ~145.965↑/435.640↓ — el beacon es una referencia de frecuencia única, no "la" frecuencia de QSO.' },
			'AO-7':  { tipo:'lineal', beacon:145.9775, nota:'Modo B (145.925-145.975↓/432.125-432.175↑ invertido). También tiene Modo A con bajada en 10m HF, alterna cada 24h en luz solar continua.' },
		};
		var C_KM_S = 299792.458; // velocidad de la luz, km/s
		var satelitePatronActual = null;
		var aosCalculado = null; // Date del próximo AOS, para la cuenta regresiva de 1s sin recalcular la búsqueda completa

		function seleccionarSatelite(patron){
			satelitePatronActual = patron || null;
			aosCalculado = null;
			var elVivo=document.getElementById('mapa-satelite-vivo');
			if(!patron){
				satrecISS=null;
				if(marcadorISS){ capaSateliteMapa.removeLayer(marcadorISS); marcadorISS=null; }
				if(trazaISS){ capaSateliteMapa.removeLayer(trazaISS); trazaISS=null; }
				if(huellaISS){ capaSateliteMapa.removeLayer(huellaISS); huellaISS=null; }
				if(elVivo) elVivo.classList.add('hidden');
				return;
			}
			if(elVivo) elVivo.classList.remove('hidden');
			var sat=todosLosTLE.find(function(s){
				if(patron==='ZARYA') return /ISS|ZARYA/i.test(s.name);
				if(patron==='AO-91') return /RADFXSAT/i.test(s.name);
				if(patron==='RS-44') return /RS-?44/i.test(s.name);
				if(patron==='AO-7') return /\(AO-7\)/.test(s.name);
				return s.name.indexOf(patron)>-1;
			});
			if(!sat) return;
			satrecISS=satellite.twoline2satrec(sat.line1, sat.line2);
			actualizarISS();
			calcularProximaPasada();
		}

		// Misma lógica AOS/LOS ya verificada en Predicción de Pasadas — solo busca la primera pasada
		function calcularProximaPasada(){
			var elPasada=document.getElementById('mapa-satelite-pasada');
			var elDetalle=document.getElementById('mapa-satelite-detalle');
			if(!elPasada || !satrecISS) return;
			elPasada.textContent='calculando…';
			var observerGd={longitude:satellite.degreesToRadians(RAU_QTH_MAPA.lng), latitude:satellite.degreesToRadians(RAU_QTH_MAPA.lat), height:0.15};
			var ahora=new Date();
			var pasoMs=30*1000;
			var totalPasos=Math.floor(12*60*60*1000/pasoMs); // buscar en las próximas 12 horas
			function elevacionEn(t){
				var pv=satellite.propagate(satrecISS, t);
				if(!pv || !pv.position) return null;
				var gmst=satellite.gstime(t);
				var posEcf=satellite.eciToEcf(pv.position, gmst);
				var look=satellite.ecfToLookAngles(observerGd, posEcf);
				return satellite.radiansToDegrees(look.elevation);
			}
			for(var i=0;i<totalPasos;i++){
				var t=new Date(ahora.getTime()+i*pasoMs);
				var elDeg=elevacionEn(t);
				if(elDeg===null) continue;
				if(elDeg>=0){
					var minFaltan=Math.round((t.getTime()-ahora.getTime())/60000);
					elPasada.textContent = minFaltan<1 ? '¡visible ahora!' : 'próxima pasada en '+minFaltan+' min';
					var rnSatEl=document.getElementById('rn-satelite');
					if(rnSatEl && selectSat){
						var nombreSel=selectSat.options[selectSat.selectedIndex].text.replace('🛰','').trim();
						rnSatEl.innerHTML='🛰️ '+nombreSel+': <span class="text-primary font-bold">'+elPasada.textContent+'</span>';
					}

					// Seguir la pasada completa con pasos más finos (5s) para encontrar LOS y elevación máxima real
					var aos=t, elevMax=elDeg, los=t;
					aosCalculado = aos;
					var pasoFinoMs=5*1000;
					var maxPasosFino=Math.ceil(20*60*1000/pasoFinoMs); // una pasada dura como máximo ~20 min
					for(var j=1;j<=maxPasosFino;j++){
						var tf=new Date(aos.getTime()+j*pasoFinoMs);
						var elf=elevacionEn(tf);
						if(elf===null) break;
						if(elf<0){ los=tf; break; }
						if(elf>elevMax) elevMax=elf;
						los=tf;
					}
					if(elDetalle){
						var duracionMin=Math.round((los.getTime()-aos.getTime())/60000);
						elDetalle.textContent='AOS '+aos.toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'})+' · LOS '+los.toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'})+' · '+duracionMin+' min · elev. máx '+Math.round(elevMax)+'°';
						elDetalle.classList.remove('hidden');
					}
					return;
				}
			}
			elPasada.textContent='sin pasada en las próximas 12h';
			if(elDetalle) elDetalle.classList.add('hidden');
			aosCalculado=null;
		}

		// Corrección Doppler real — verificada contra los valores típicos documentados (~±3.5kHz en 2m, ~±10kHz en 70cm)
		// usando velocidad radial numérica (diferencia de rango en 1s), no proyección de vector de velocidad.
		function calcularDoppler(observerGd, ahora, freqMHzNominal, esUplink){
			var pv1=satellite.propagate(satrecISS, ahora);
			var pv2=satellite.propagate(satrecISS, new Date(ahora.getTime()+1000));
			if(!pv1 || !pv1.position || !pv2 || !pv2.position) return freqMHzNominal;
			var gmst1=satellite.gstime(ahora), gmst2=satellite.gstime(new Date(ahora.getTime()+1000));
			var look1=satellite.ecfToLookAngles(observerGd, satellite.eciToEcf(pv1.position, gmst1));
			var look2=satellite.ecfToLookAngles(observerGd, satellite.eciToEcf(pv2.position, gmst2));
			var vRadial=look2.rangeSat-look1.rangeSat; // km/s, positivo = alejándose
			if(esUplink){
				// Lo que debo transmitir para que el satélite reciba exactamente la frecuencia nominal
				return freqMHzNominal/(1-vRadial/C_KM_S);
			}
			// Dónde debo sintonizar mi receptor para captar lo que el satélite transmite en su nominal
			return freqMHzNominal*(1-vRadial/C_KM_S);
		}

		function actualizarSeguimientoVivo(){
			var elVivo=document.getElementById('mapa-satelite-vivo');
			var elEstado=document.getElementById('mapa-satelite-vivo-estado');
			var elAzEl=document.getElementById('mapa-satelite-vivo-azel');
			var elDoppler=document.getElementById('mapa-satelite-vivo-doppler');
			if(!elVivo || !satrecISS || !satelitePatronActual) return;
			var observerGd={longitude:satellite.degreesToRadians(RAU_QTH_MAPA.lng), latitude:satellite.degreesToRadians(RAU_QTH_MAPA.lat), height:0.15};
			var ahora=new Date();
			var pv=satellite.propagate(satrecISS, ahora);
			if(!pv || !pv.position) return;
			var gmst=satellite.gstime(ahora);
			var look=satellite.ecfToLookAngles(observerGd, satellite.eciToEcf(pv.position, gmst));
			var elDeg=satellite.radiansToDegrees(look.elevation);
			var azDeg=satellite.radiansToDegrees(look.azimuth);

			if(elDeg>=0){
				// En pasada ahora — indicador visual + az/el en vivo + Doppler real
				elVivo.classList.add('border-primary/60','bg-primary/5');
				elEstado.innerHTML='🟢 <span class="text-primary font-bold">En pasada ahora</span>';
				elAzEl.textContent='Az '+Math.round(azDeg)+'° · El '+Math.round(elDeg)+'°';

				var datosSat=FRECUENCIAS_SAT[satelitePatronActual];
				if(datosSat){
					if(datosSat.tipo==='fm'){
						var txCorr=calcularDoppler(observerGd, ahora, datosSat.up, true);
						var rxCorr=calcularDoppler(observerGd, ahora, datosSat.down, false);
						elDoppler.textContent='TX '+txCorr.toFixed(4)+' · RX '+rxCorr.toFixed(4)+' MHz · '+datosSat.tono;
					} else if(datosSat.tipo==='lineal'){
						var beaconCorr=calcularDoppler(observerGd, ahora, datosSat.beacon, false);
						elDoppler.textContent='Beacon CW: '+beaconCorr.toFixed(4)+' MHz (referencia, transpondedor lineal)';
					}
				}
			} else {
				elVivo.classList.remove('border-primary/60','bg-primary/5');
				elAzEl.textContent='';
				elDoppler.textContent='';
				if(aosCalculado){
					var faltanMs=aosCalculado.getTime()-ahora.getTime();
					if(faltanMs>0){
						var totalSeg=Math.floor(faltanMs/1000);
						var mm=Math.floor(totalSeg/60), ss=totalSeg%60;
						elEstado.textContent='⏱️ Próxima pasada en '+mm+':'+String(ss).padStart(2,'0');
					} else {
						// La pasada calculada ya pasó (terminó, o el usuario dejó la pestaña abierta) —
						// volver a buscar la siguiente en vez de quedarse en "Calculando" para siempre.
						elEstado.textContent='Calculando…';
						calcularProximaPasada();
					}
				} else {
					elEstado.textContent='Sin pasada calculada';
				}
			}
		}
		setInterval(actualizarSeguimientoVivo, 1000);

		cargarSatelliteJs(function(){
			fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_tle', {cache:'no-store'})
				.then(function(r){ return r.json(); })
				.then(function(json){
					if(!json.success || !json.data) return;
					todosLosTLE=json.data;
					seleccionarSatelite(selectSat ? selectSat.value : 'ZARYA');
					setInterval(function(){ if(satrecISS) actualizarISS(); }, 5000);
					actualizarListaSatelites();
					setInterval(actualizarListaSatelites, 30000);

					// QO-100 — geoestacionario a 25.9°E (posición real, verificada con AMSAT-UK/BATC).
					// A diferencia de los satélites LEO, su posición relativa a un QTH fijo NO cambia con el tiempo,
					// así que se calcula una sola vez. Reutiliza ecfToLookAngles, la misma función ya usada arriba.
					(function(){
						var qo100El=document.getElementById('qo100-widget');
						if(!qo100El) return;
						var lonQO100=25.9*Math.PI/180;
						var Rgeo=42164.0;
						var ecfQO100={ x:Rgeo*Math.cos(lonQO100), y:Rgeo*Math.sin(lonQO100), z:0 };
						var observerGd={longitude:satellite.degreesToRadians(RAU_QTH_MAPA.lng), latitude:satellite.degreesToRadians(RAU_QTH_MAPA.lat), height:0.15};
						var look=satellite.ecfToLookAngles(observerGd, ecfQO100);
						var azDeg=satellite.radiansToDegrees(look.azimuth);
						var elDeg=satellite.radiansToDegrees(look.elevation);
						if(elDeg>0){
							qo100El.innerHTML='🛰️ <span class="text-primary font-bold">QO-100</span> (geoestacionario, fijo): Az '+azDeg.toFixed(0)+'° · El '+elDeg.toFixed(0)+'°';
						} else {
							var notaGeografica = (elDeg < -10) ? ' — ni el punto más oriental de Colombia se acerca a verlo' : ' — un QTH más al oriente podría estar cerca del límite';
							qo100El.innerHTML='🛰️ QO-100 (geoestacionario): <span class="text-rose-400">no visible desde tu QTH</span> (elev. '+elDeg.toFixed(0)+'°, bajo el horizonte)'+notaGeografica;
						}
					})();

				// Mostrar de qué fuente vienen realmente los datos orbitales — se actualiza solo, refleja el estado real
				fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_tle_fuente', {cache:'no-store'})
					.then(function(r){ return r.json(); })
					.then(function(json){
						var badge=document.getElementById('sat-fuente-badge');
						if(!badge || !json.success) return;
						var nombres={ celestrak:'CelesTrak', pu4elt_respaldo:'PU4ELT (respaldo)' };
						badge.textContent='📡 '+(nombres[json.data.fuente] || json.data.fuente);
						badge.classList.remove('hidden');
						if(json.data.fuente==='pu4elt_respaldo') badge.classList.add('text-amber-400');
					}).catch(function(e){ rauErr(e);});

				// Actividad real de AMSAT — barras proporcionales, con manejo honesto si falla o si AMSAT cambia su formato
				setTimeout(function(){
				fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_amsat_actividad', {cache:'no-store'})
					.then(function(r){ return r.json(); })
					.then(function(json){
						var el=document.getElementById('amsat-actividad-lista');
						if(!el) return;
						if(!json.success || !json.data || !json.data.length){
							el.innerHTML='<p class="font-mono text-[9px] text-muted-foreground">No disponible ahora mismo — <a href="https://www.amsat.org/status/" target="_blank" rel="noopener" class="text-primary hover:underline">ver en AMSAT ↗</a></p>';
							return;
						}
						var top5=json.data.slice(0,5);
						var max=Math.max.apply(null, top5.map(function(s){ return s.conteo; }));
						function mapearNombreSatelite(nombreCrudo){
							// AMSAT usa nombres como "RS-44_[V/u]" — tomamos solo la parte antes del guion bajo, comparación EXACTA (no substring) para no confundir AO-7 con AO-91/AO-73
							var base=nombreCrudo.split('_')[0].trim().toUpperCase();
							var mapa={ 'ISS':'ZARYA', 'SO-50':'SO-50', 'AO-91':'AO-91', 'RS-44':'RS-44', 'FO-29':'FO-29', 'AO-7':'AO-7', 'AO-07':'AO-7' };
							return mapa[base] || null;
						}
						el.innerHTML=top5.map(function(s){
							var pct=Math.round((s.conteo/max)*100);
							var valorDropdown=mapearNombreSatelite(s.nombre);
							var etiqueta='<span class="w-16 shrink-0 truncate font-mono text-[8px] text-muted-foreground" title="'+s.nombre+'">'+s.nombre+'</span>';
							if(valorDropdown) etiqueta='<button type="button" class="rau-amsat-seleccionar w-16 shrink-0 truncate text-left font-mono text-[8px] text-primary hover:underline" data-valor="'+valorDropdown+'" title="Rastrear '+s.nombre+'">'+s.nombre+'</button>';
							return '<div class="flex items-center gap-1.5">'
								+etiqueta
								+'<div class="h-2.5 flex-1 overflow-hidden rounded-full bg-border/40"><div class="h-full rounded-full bg-primary" style="width:'+pct+'%"></div></div>'
								+'<span class="w-5 shrink-0 text-right font-mono text-[8px] text-muted-foreground">'+s.conteo+'</span>'
								+'</div>';
						}).join('');
						el.querySelectorAll('.rau-amsat-seleccionar').forEach(function(btn){
							btn.addEventListener('click', function(){
								if(typeof window.rauSeleccionarSatelite==='function') window.rauSeleccionarSatelite(btn.dataset.valor);
							});
						});
					}).catch(function(e){ rauErr(e);
						var el=document.getElementById('amsat-actividad-lista');
						if(el) el.innerHTML='<p class="font-mono text-[9px] text-muted-foreground">No disponible ahora mismo — <a href="https://www.amsat.org/status/" target="_blank" rel="noopener" class="text-primary hover:underline">ver en AMSAT ↗</a></p>';
					});
				}, 1500);

				// Próximas activaciones de hams.at — para los satélites que ya rastreamos, sin necesitar clave de API
				setTimeout(function(){
				fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_hamsat_activaciones', {cache:'no-store'})
					.then(function(r){ return r.json(); })
					.then(function(json){
						var el=document.getElementById('hamsat-activaciones-lista');
						if(!el) return;
						if(!json.success || !json.data || !json.data.length){
							el.innerHTML='<p class="font-mono text-[9px] text-muted-foreground">Sin activaciones próximas detectadas — <a href="https://hams.at/" target="_blank" rel="noopener" class="text-primary hover:underline">ver en hams.at ↗</a></p>';
							return;
						}
						el.innerHTML=json.data.slice(0,5).map(function(a){
							var limpio=a.contexto.replace(new RegExp(a.satelite.replace('-','-0?'),'i'),'').trim();
							var valorDropdown = (a.satelite==='ISS') ? 'ZARYA' : a.satelite;
							return '<div class="font-mono text-[8px] text-muted-foreground"><button type="button" class="rau-hamsat-seleccionar text-primary font-bold hover:underline" data-valor="'+valorDropdown+'">🛰️ '+a.satelite+'</button> — '+limpio+'</div>';
						}).join('');
						el.querySelectorAll('.rau-hamsat-seleccionar').forEach(function(btn){
							btn.addEventListener('click', function(){
								if(typeof window.rauSeleccionarSatelite==='function') window.rauSeleccionarSatelite(btn.dataset.valor);
							});
						});
					}).catch(function(e){ rauErr(e);
						var el=document.getElementById('hamsat-activaciones-lista');
						if(el) el.innerHTML='<p class="font-mono text-[9px] text-muted-foreground">No disponible ahora mismo — <a href="https://hams.at/" target="_blank" rel="noopener" class="text-primary hover:underline">ver en hams.at ↗</a></p>';
					});
				}, 2000);
				}).catch(function(e){ rauErr(e);
					var rnSatErr=document.getElementById('rn-satelite');
					if(rnSatErr) rnSatErr.innerHTML='🛰️ <span class="text-rose-400">sin datos</span>';
					var listaSatErr=document.getElementById('mapa-satelites-lista');
					if(listaSatErr) listaSatErr.innerHTML='<p class="text-muted-foreground">No se pudo cargar la posición de los satélites.</p>';
				});
		});

		// Lista de varios satélites con distancia — reutiliza satellite.js y distanciaYRumbo ya cargados, sin paneles interactivos duplicados
		var SATS_LISTA=[{patron:'ZARYA', nombre:'ISS'}, {patron:'SO-50', nombre:'SO-50'}, {patron:'RADFXSAT', nombre:'AO-91'}, {patron:'RS-44', nombre:'RS-44'}, {patron:'(FO-29)', nombre:'FO-29'}, {patron:'(AO-7)', nombre:'AO-7'}];
		function actualizarListaSatelites(){
			var el=document.getElementById('mapa-satelites-lista');
			if(!el || !window.satellite || !todosLosTLE.length) return;
			var ahora=new Date();
			var gmst=satellite.gstime(ahora);
			var observerGd={longitude:satellite.degreesToRadians(RAU_QTH_MAPA.lng), latitude:satellite.degreesToRadians(RAU_QTH_MAPA.lat), height:0.15};
			var datos=SATS_LISTA.map(function(s){
				var sat=todosLosTLE.find(function(t){ return t.name.indexOf(s.patron)>-1 || (s.patron==='ZARYA' && /ISS|ZARYA/i.test(t.name)) || (s.patron==='RS-44' && /RS-?44/i.test(t.name)); });
				if(!sat) return { nombre:s.nombre, dist:null };
				var satrec=satellite.twoline2satrec(sat.line1, sat.line2);
				var pv=satellite.propagate(satrec, ahora);
				if(!pv || !pv.position) return { nombre:s.nombre, dist:null };
				// Distancia real antena→satélite (slant range) y elevación sobre el horizonte.
				// Antes se medía la distancia SOBRE EL SUELO hasta el punto subsatelital: eso ignora
				// por completo la altura de la órbita y da cifras absurdas (la ISS aparecía a 9.411 km
				// cuando su alcance máximo real ronda los 2.300 km) para satélites que en ese momento
				// estaban al otro lado del planeta y no se podían trabajar de ninguna forma.
				var look=satellite.ecfToLookAngles(observerGd, satellite.eciToEcf(pv.position, gmst));
				return {
					nombre : s.nombre,
					dist   : look.rangeSat,
					az     : satellite.radiansToDegrees(look.azimuth),
					el     : satellite.radiansToDegrees(look.elevation)
				};
			});
			// Solo vale la pena destacar uno que esté realmente sobre el horizonte; entre esos, el de
			// mayor elevación es el más fácil de trabajar (menos obstáculos, menos pérdida de trayecto).
			var visibles=datos.filter(function(d){ return d.dist!==null && d.el>0; });
			var masCercano=visibles.length ? visibles.reduce(function(a,b){ return a.el>b.el?a:b; }) : null;
			el.innerHTML=datos.map(function(d){
				if(d.dist===null) return '<div class="flex justify-between text-muted-foreground">'+d.nombre+' <span>sin datos</span></div>';
				var esElMasCercano = masCercano && d.nombre===masCercano.nombre;
				if(esElMasCercano){
					return '<div class="flex justify-between rounded-md bg-primary/10 px-1.5 py-1 -mx-1.5"><span class="font-bold text-primary">⭐ '+d.nombre+'</span><span class="font-bold text-primary">'+formatearDistancia(d.dist)+' · Az '+d.az.toFixed(0)+'° · El '+d.el.toFixed(0)+'°</span></div>';
				}
				if(d.el>0){
					return '<div class="flex justify-between"><span class="text-foreground">'+d.nombre+'</span><span class="text-primary">'+formatearDistancia(d.dist)+' · El '+d.el.toFixed(0)+'°</span></div>';
				}
				// Bajo el horizonte no hay nada que hacer con él: mostrar una distancia ahí sugiere
				// que se puede trabajar y es justo lo contrario.
				return '<div class="flex justify-between"><span class="text-muted-foreground">'+d.nombre+'</span><span class="text-muted-foreground">bajo el horizonte</span></div>';
			}).join('')
			+ '<div class="mt-1.5 flex gap-1 border-t border-border/50 pt-1.5"><a href="<?php echo esc_url( home_url( "/herramientas/satfreq/" ) ); ?>" class="text-[9px] text-muted-foreground hover:text-primary hover:underline">Ver frecuencias</a><span class="text-[9px] text-muted-foreground">·</span><a href="<?php echo esc_url( home_url( "/herramientas/doppler/" ) ); ?>" class="text-[9px] text-muted-foreground hover:text-primary hover:underline">Calcular Doppler</a></div>';
		}
		if(selectSat) selectSat.addEventListener('change', function(){ seleccionarSatelite(this.value); });
		window.rauSeleccionarSatelite=function(valorDropdown){
			if(selectSat) selectSat.value=valorDropdown;
			seleccionarSatelite(valorDropdown);
			var mapaEl=document.getElementById('consola-mapa');
			if(mapaEl) mapaEl.scrollIntoView({ behavior:'smooth', block:'center' });
		};

		var poligonoNoche=null, rumboLinea=null;
		function dibujarGrayline(){
			var ahora=new Date();
			var decl=declinacionSolar(diaDelAnio(ahora));
			var subLng=subsolarLng(ahora);
			var puntos=[];
			for(var lng=-180; lng<=180; lng+=2){
				puntos.push([latTerminador(lng, decl, subLng), lng]);
			}
			var poloOscuro = decl>=0 ? -90 : 90;
			var poligono = [[poloOscuro,-180]].concat(puntos).concat([[poloOscuro,180]]);

			if(poligonoNoche) map.removeLayer(poligonoNoche);
			poligonoNoche=L.polygon(poligono, { color:'transparent', fillColor:'#000', fillOpacity:0.35, interactive:false }).addTo(map);
		}
		dibujarGrayline();
		setInterval(dibujarGrayline, 5*60*1000);

		// Amanecer/atardecer de hoy para el QTH — reutiliza amanecerAtardecer y formatearHoraUTC ya verificadas
		(function(){
			var el=document.getElementById('mapa-sol-qth-hoy');
			if(!el) return;
			function actualizar(){
				var ahora=new Date();
				var decl=declinacionSolar(diaDelAnio(ahora));
				var sol=amanecerAtardecer(RAU_QTH_MAPA.lat, RAU_QTH_MAPA.lng, decl);
				el.textContent = sol
					? '☀ Hoy en tu QTH: ↑'+formatearHoraUTC(sol.amanecer)+' ↓'+formatearHoraUTC(sol.atardecer)+' UTC'
					: '☀ Sol de medianoche o noche polar hoy en tu QTH';
			}
			actualizar();
			setInterval(actualizar, 60*60*1000);
		})();

		map.on('click', function(e){
			var ahora=new Date();
			var decl=declinacionSolar(diaDelAnio(ahora));
			var subLng=subsolarLng(ahora);

			var r=distanciaYRumbo(RAU_QTH_MAPA.lat, RAU_QTH_MAPA.lng, e.latlng.lat, e.latlng.lng);
			var elevRau=elevacionSolar(RAU_QTH_MAPA.lat, RAU_QTH_MAPA.lng, decl, subLng);
			var elevDestino=elevacionSolar(e.latlng.lat, e.latlng.lng, decl, subLng);
			var prop=estimarPropagacion(estadoSolar.sfi, estadoSolar.kindex, elevRau, elevDestino, r.dist, r.az);
			var solRau=amanecerAtardecer(RAU_QTH_MAPA.lat, RAU_QTH_MAPA.lng, decl);
			var solDestino=amanecerAtardecer(e.latlng.lat, e.latlng.lng, decl);

			// Compartir el punto de DX vía query param — no choca con el sistema de hash existente (categorías/herramientas)
			var url=new URL(window.location.href);
			url.searchParams.set('dx', e.latlng.lat.toFixed(4)+','+e.latlng.lng.toFixed(4));
			history.replaceState(null,'',url.toString());

			if(rumboLinea) map.removeLayer(rumboLinea);
			var colorLinea = { 'text-emerald-400':'#34d399', 'text-amber-400':'#fbbf24', 'text-rose-400':'#fb7185' }[prop.colorCalidad] || '#1fd5f9';
			rumboLinea=L.polyline([[RAU_QTH_MAPA.lat,RAU_QTH_MAPA.lng],[e.latlng.lat,e.latlng.lng]], { color:colorLinea, weight:2.5, dashArray:'4 4' }).addTo(map);

			var panel=document.getElementById('mapa-clic-panel');
			panel.classList.remove('hidden');
			panel.innerHTML=
				'<div class="mb-1 flex flex-wrap gap-x-4 text-muted-foreground">'
				+'<span>Tu grid: <span class="text-primary">'+latLngAGrid(RAU_QTH_MAPA.lat,RAU_QTH_MAPA.lng)+'</span></span>'
				+'<span>Grid destino: <span class="text-primary">'+latLngAGrid(e.latlng.lat,e.latlng.lng)+'</span></span>'
				+'</div>'
				+'<div class="mb-1 flex flex-wrap justify-between gap-2">'
				+'<span>'+formatearDistancia(r.dist)+' · corto '+Math.round(r.az)+'° · largo '+Math.round((r.az+180)%360)+'°</span>'
				+'<span class="'+prop.colorCalidad+'">Calidad estimada: '+prop.calidad+'</span>'
				+'</div>'
				+'<div class="text-muted-foreground">Bandas recomendadas ahora: <span class="text-primary">'+prop.bandas+'</span></div>'
				+'<div class="mt-1 flex flex-wrap gap-x-4 text-muted-foreground">'
				+'<span>☀ Tu QTH: '+(solRau ? '↑'+formatearHoraUTC(solRau.amanecer)+' ↓'+formatearHoraUTC(solRau.atardecer)+' UTC' : 'sol de medianoche/noche polar')+'</span>'
				+'<span>☀ Destino: '+(solDestino ? '↑'+formatearHoraUTC(solDestino.amanecer)+' ↓'+formatearHoraUTC(solDestino.atardecer)+' UTC' : 'sol de medianoche/noche polar')+'</span>'
				+'</div>'
				+(prop.notaGrayline ? '<div class="mt-1 text-amber-300">'+prop.notaGrayline+'</div>' : '')
				+(prop.notaEspecial ? '<div class="mt-1 text-amber-300">'+prop.notaEspecial+'</div>' : '')
				+'<div class="mt-1 text-muted-foreground" id="mapa-clima-destino">Clima en el destino: cargando…</div>'
				+'<div class="mt-1 text-[10px] text-muted-foreground">* Estimación por reglas generales (SFI/K/grayline) — no es un modelo de propagación real como VOACAP.</div>'
				+'<button type="button" id="mapa-copiar-enlace" class="mt-2 rounded-md border border-border/70 bg-background px-2 py-1 font-mono text-[10px] text-muted-foreground hover:border-primary/40 hover:text-primary">🔗 Copiar enlace a este punto</button>';

			var btnCopiar=document.getElementById('mapa-copiar-enlace');

			// Previsión 24h — misma función estimarPropagacion ya verificada, variando solo la hora
			// (SFI/K se mantienen constantes; lo que cambia hora a hora es el grayline en ambos extremos)
			(function(){
				var barrasEl=document.getElementById('mapa-prevision-barras');
				var wrapEl=document.getElementById('mapa-prevision-24h');
				if(!barrasEl || !wrapEl) return;
				var COLOR_HEX={ 'text-emerald-400':'#34d399', 'text-amber-400':'#fbbf24', 'text-rose-400':'#fb7185' };
				var RANGO={ 'text-emerald-400':3, 'text-amber-400':2, 'text-rose-400':1 };
				var html='';
				var rangos=[];
				for(var h=0; h<24; h++){
					var tHora=new Date(Date.now()+h*3600000);
					var declHora=declinacionSolar(diaDelAnio(tHora));
					var subLngHora=subsolarLng(tHora);
					var elevRauHora=elevacionSolar(RAU_QTH_MAPA.lat, RAU_QTH_MAPA.lng, declHora, subLngHora);
					var elevDestinoHora=elevacionSolar(e.latlng.lat, e.latlng.lng, declHora, subLngHora);
					var pHora=estimarPropagacion(estadoSolar.sfi, estadoSolar.kindex, elevRauHora, elevDestinoHora, r.dist, r.az);
					var colorHex=COLOR_HEX[pHora.colorCalidad]||'#6b7280';
					html+='<div style="flex:1;background:'+colorHex+'" title="+'+h+'h ('+tHora.getUTCHours()+':00 UTC): '+pHora.calidad+'"></div>';
					rangos.push(RANGO[pHora.colorCalidad]||0);
				}
				barrasEl.innerHTML=html;
				wrapEl.classList.remove('hidden');

				// Encontrar la ventana contigua más larga con el rango más alto disponible en las 24h
				var mejorRango=Math.max.apply(null, rangos);
				var mejorInicio=-1, mejorLargo=0, inicioActual=-1, largoActual=0;
				for(var i=0;i<rangos.length;i++){
					if(rangos[i]===mejorRango){
						if(inicioActual===-1) inicioActual=i;
						largoActual++;
						if(largoActual>mejorLargo){ mejorLargo=largoActual; mejorInicio=inicioActual; }
					} else {
						inicioActual=-1; largoActual=0;
					}
				}
				var resumenEl=document.getElementById('mapa-prevision-resumen');
				var textoResumen='';
				if(resumenEl && mejorInicio>-1){
					var etiquetaRango = mejorRango===3 ? 'Mejor ventana' : (mejorRango===2 ? 'Ventana menos mala' : 'Sin buenas ventanas —');
					if(mejorInicio===0){
						textoResumen=etiquetaRango+': ahora mismo, por '+mejorLargo+'h más';
					} else {
						textoResumen=etiquetaRango+': en '+mejorInicio+'h, durante '+mejorLargo+'h';
					}
					resumenEl.textContent=textoResumen;
				}
				var btnCompartir=document.getElementById('mapa-compartir-texto');
				if(btnCompartir) btnCompartir.addEventListener('click', function(){
					var grid1=latLngAGrid(RAU_QTH_MAPA.lat,RAU_QTH_MAPA.lng);
					var grid2=latLngAGrid(e.latlng.lat,e.latlng.lng);
					var texto='📡 Propagación '+grid1+' → '+grid2+'\n'
						+formatearDistancia(r.dist)+' · '+prop.calidad+'\n'
						+'Bandas recomendadas: '+prop.bandas+'\n'
						+(textoResumen ? textoResumen+'\n' : '')
						+window.location.href;
					navigator.clipboard.writeText(texto).then(function(){
						btnCompartir.textContent='✅ Copiado';
						setTimeout(function(){ btnCompartir.textContent='💬 Copiar como texto'; }, 2000);
					}).catch(function(e){ rauErr(e);});
				});
			})();
			if(btnCopiar) btnCopiar.addEventListener('click', function(){
				navigator.clipboard.writeText(window.location.href).then(function(){
					btnCopiar.textContent='✅ Enlace copiado';
					setTimeout(function(){ btnCopiar.textContent='🔗 Copiar enlace a este punto'; }, 2000);
				}).catch(function(e){ rauErr(e);});
			});

			obtenerClima(e.latlng.lat, e.latlng.lng, function(c){
				var elClima=document.getElementById('mapa-clima-destino');
				if(!elClima) return;
				if(c) elClima.textContent='Clima en el destino: '+climaTexto(c.weathercode)+' · '+Math.round(c.temperature)+'°C';
				else elClima.textContent='Clima en el destino: no disponible';
			});
		});

		// Si la URL trae un punto compartido (?dx=lat,lng), lo carga automáticamente al abrir la página
		var dxParam=new URLSearchParams(window.location.search).get('dx');
		if(dxParam){
			var partes=dxParam.split(',').map(parseFloat);
			if(partes.length===2 && !isNaN(partes[0]) && !isNaN(partes[1])){
				map.setView([partes[0], partes[1]], 4);
				map.fire('click', { latlng: L.latLng(partes[0], partes[1]) });
			}
		}

		// Entrada manual de grid — reutiliza el mismo manejador de clic vía map.fire(), sin duplicar lógica
		var inputGrid=document.getElementById('mapa-grid-manual');
		var btnGrid=document.getElementById('mapa-grid-manual-btn');
		function irAGrid(){
			var grid=(inputGrid.value||'').trim().toUpperCase();
			if(!/^[A-R]{2}[0-9]{2}([A-X]{2})?$/.test(grid)){
				alert('Grid inválido. Formato: 2 letras + 2 números (ej. JN58), opcionalmente 2 letras más.');
				return;
			}
			var coord=gridACoordenadas(grid);
			map.setView([coord.lat, coord.lng], 4);
			map.fire('click', { latlng: L.latLng(coord.lat, coord.lng) });
		}
		if(btnGrid) btnGrid.addEventListener('click', irAGrid);
		if(inputGrid) inputGrid.addEventListener('keydown', function(e){ if(e.key==='Enter') irAGrid(); });

		// Búsqueda de indicativo DX — reutiliza PFX_GEO (global, ya auditada) y el mismo algoritmo de coincidencia ya usado en el Localizador de Indicativo
		var inputCall=document.getElementById('mapa-indicativo-manual');
		var btnCall=document.getElementById('mapa-indicativo-manual-btn');
		function irAIndicativo(){
			var raw=(inputCall.value||'').trim().toUpperCase();
			if(!raw){ return; }
			var found=null;
			for(var l=4; l>=1; l--){
				var probar=raw.slice(0,l);
				if(typeof PFX_GEO!=='undefined' && PFX_GEO[probar]){ found=PFX_GEO[probar]; break; }
			}
			if(!found){ alert('No se encontró el país para ese indicativo en la base de datos.'); return; }
			var partes=found.split('|');
			if(partes.length<6){ alert('Ese país no tiene coordenadas de referencia guardadas todavía.'); return; }
			var lat=parseFloat(partes[4]), lng=parseFloat(partes[5]);
			if(isNaN(lat)||isNaN(lng)) return;
			map.setView([lat,lng], 4);
			map.fire('click', { latlng: L.latLng(lat,lng) });
		}
		if(btnCall) btnCall.addEventListener('click', irAIndicativo);
		if(inputCall) inputCall.addEventListener('keydown', function(e){ if(e.key==='Enter') irAIndicativo(); });
		window.rauIrAIndicativo=function(callsign){
			if(inputCall) inputCall.value=callsign;
			irAIndicativo();
		};
	});
})();

/* ── CENTRO DE MANDO: MODO "EN EL CAMPO" ── */
(function(){
	var gpsBtn=document.getElementById('campo-gps');
	if(!gpsBtn) return;
	var REPS_CONSOLA=<?php echo wp_json_encode( $repetidores_consola ?: [] ); ?>;

	function latLngAGrid(lat,lng){
		lat+=90; lng+=180;
		var A='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		var campo=A[Math.floor(lng/20)]+A[Math.floor(lat/10)];
		var cuadro=Math.floor((lng%20)/2)+''+Math.floor(lat%10);
		var sub=A[Math.floor((lng%2)*12)].toLowerCase()+A[Math.floor((lat%1)*24)].toLowerCase();
		return campo+cuadro+sub;
	}

	function distanciaKm(lat1,lng1,lat2,lng2){ return rauDistanciaRumbo(lat1,lng1,lat2,lng2).dist; }

	gpsBtn.addEventListener('click', function(){
		if(!navigator.geolocation){ alert('Tu navegador no soporta geolocalización.'); return; }
		gpsBtn.textContent='Buscando…';
		navigator.geolocation.getCurrentPosition(function(pos){
			var lat=pos.coords.latitude, lng=pos.coords.longitude;
			document.getElementById('campo-grid').textContent=latLngAGrid(lat,lng);
			gpsBtn.textContent='📍 Usar mi ubicación';

			if(REPS_CONSOLA.length){
				var cercano=null, distMin=Infinity;
				REPS_CONSOLA.forEach(function(r){
					var d=distanciaKm(lat,lng,r.lat,r.lng);
					if(d<distMin){ distMin=d; cercano=r; }
				});
				if(cercano){
					document.getElementById('campo-repetidor').textContent=cercano.name;
					document.getElementById('campo-repetidor-detalle').textContent=cercano.freq+' MHz · '+distMin.toFixed(1)+' km · '+(cercano.callsign||'');
				}
			} else {
				document.getElementById('campo-repetidor').textContent='Sin repetidores con coordenadas registradas';
			}
		}, function(){ gpsBtn.textContent='📍 Usar mi ubicación'; alert('No se pudo obtener tu ubicación.'); });
	});

	// Batería — misma fórmula ya verificada en Autonomía de Batería (duty fijo 25%, factor 0.85 de eficiencia real)
	function calcBateria(){
		// Campo vacío = sin resultado. Con ||0 un TX borrado contaba como 0 mA e inflaba la autonomía.
		var cap=parseFloat(document.getElementById('campo-bat-cap').value);
		var tx=parseFloat(document.getElementById('campo-bat-tx').value);
		if(isNaN(cap) || isNaN(tx)){ document.getElementById('campo-bat-horas').textContent='—'; return; }
		var rx=300, duty=0.25;
		var promedio=duty*tx+(1-duty)*rx;
		var horas=promedio>0 ? (cap/promedio)*0.85 : 0;
		var h=Math.floor(horas), m=Math.round((horas-h)*60);
		document.getElementById('campo-bat-horas').textContent = horas>0 ? h+'h '+m+'m' : '—';
	}
	document.getElementById('campo-bat-cap').addEventListener('input', calcBateria);
	document.getElementById('campo-bat-tx').addEventListener('input', calcBateria);
	calcBateria();
})();

/* ── CENTRO DE MANDO: MODO "ANTES DE TRANSMITIR" ── */
(function(){
	var utcEl=document.getElementById('atx-utc');
	if(!utcEl) return;

	function tick(){
		var d=new Date();
		var p=function(n){return String(n).padStart(2,'0');};
		utcEl.textContent=p(d.getUTCHours())+':'+p(d.getUTCMinutes())+':'+p(d.getUTCSeconds());
		document.getElementById('atx-local').textContent='hora local '+p(d.getHours())+':'+p(d.getMinutes());
	}
	tick();
	setInterval(tick, 1000);

	// Misma tabla ESCALA ya verificada contra la fuente oficial NOAA en el Predictor de Apagón
	var ESCALA_ATX=[
		{clase:'A / B', nivel:'Sin efecto', color:'text-muted-foreground'},
		{clase:'C', nivel:'Cambios apenas perceptibles', color:'text-muted-foreground'},
		{clase:'M1–M4', nivel:'R1 Menor', color:'text-amber-400'},
		{clase:'M5–M9', nivel:'R2 Moderado', color:'text-amber-400'},
		{clase:'X1–X9', nivel:'R3 Fuerte', color:'text-rose-400'},
		{clase:'X10–X19', nivel:'R4 Severo', color:'text-rose-400'},
		{clase:'X20+', nivel:'R5 Extremo', color:'text-rose-400'},
	];
	function clasificarAtx(xrayRaw){
		var m=(xrayRaw||'').toString().trim().match(/^([ABCMX])([\d.]+)/i);
		if(!m) return null;
		var clase=m[1].toUpperCase(), numero=parseFloat(m[2]);
		if(clase==='A'||clase==='B') return ESCALA_ATX[0];
		if(clase==='C') return ESCALA_ATX[1];
		if(clase==='M') return numero<5 ? ESCALA_ATX[2] : ESCALA_ATX[3];
		if(clase==='X') return numero<10 ? ESCALA_ATX[4] : (numero<20 ? ESCALA_ATX[5] : ESCALA_ATX[6]);
		return null;
	}

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success) return;
			var d=json.data;
			document.getElementById('atx-sfi').textContent=d.sfi;
			document.getElementById('atx-ka').textContent='K='+d.kindex+' · A='+d.aindex;
			document.getElementById('atx-xray').textContent=d.xray;

			var clasif=clasificarAtx(d.xray);
			var apagonEl=document.getElementById('atx-apagon');
			if(clasif){ apagonEl.textContent=clasif.nivel; apagonEl.className='text-[10px] '+clasif.color; }

			var bandasEl=document.getElementById('atx-bandas');
			var buenas=(d.bands||[]).filter(function(b){ return b.day==='Good'||b.night==='Good'; });
			if(buenas.length){
				bandasEl.innerHTML=buenas.map(function(b){
					return '<span class="rounded-full bg-emerald-400/15 px-2 py-0.5 text-[11px] font-mono text-emerald-400">'+b.name+'</span>';
				}).join('');
			} else {
				bandasEl.innerHTML='<span class="text-[11px] text-muted-foreground">Ninguna en "Good" ahora mismo</span>';
			}

			function colorCondicion(cond){
				if(cond==='Good') return 'text-emerald-400';
				if(cond==='Fair') return 'text-amber-400';
				if(cond==='Poor') return 'text-rose-400';
				return 'text-muted-foreground';
			}
			var tablaEl=document.getElementById('atx-tabla-bandas');
			if(tablaEl && d.bands && d.bands.length){
				tablaEl.innerHTML=d.bands.map(function(b){
					return '<tr class="border-t border-border/40">'
						+'<td class="px-3 py-1.5 font-bold">'+b.name+'</td>'
						+'<td class="px-3 py-1.5 '+colorCondicion(b.day)+'">'+b.day+'</td>'
						+'<td class="px-3 py-1.5 '+colorCondicion(b.night)+'">'+b.night+'</td>'
						+'</tr>';
				}).join('');
			}

			if(d.updated) document.getElementById('atx-actualizado').textContent='Actualizado: '+d.updated;
		}).catch(function(e){ rauErr(e);});
})();

/* ── CENTRO DE MANDO: BANCO DE TRABAJO "CONSTRUIR ANTENA" (piloto) ── */
(function(){
	var freqEl=document.getElementById('banco-freq');
	if(!freqEl) return;

	function actualizar(){
		var f=parseFloat(freqEl.value)||14.15;
		var wl=299.792458/f;
		var vf=0.95;

		// Dipolo λ/2 — misma fórmula ya verificada en la calculadora de Antenas
		var L=wl*0.5*vf;
		document.getElementById('banco-dipolo').textContent=L.toFixed(2)+' m';

		// Yagi 2 elementos — el reflector va ~4.7% MÁS LARGO que el radiador (relación clásica
		// 0.495λ / 0.473λ del diseño de referencia). Si ambos midieran igual no habría desfase
		// de corriente entre elementos y la antena no tendría directividad: sería un dipolo caro.
		var driven=wl*0.5*vf, reflector=driven*1.047;
		document.getElementById('banco-yagi').innerHTML=driven.toFixed(2)+'m / '+reflector.toFixed(2)+'m';

		// Radiales de tierra — misma fórmula ya verificada
		document.getElementById('banco-radiales').textContent=(wl*0.25).toFixed(2)+' m';

		actualizarSmith();
	}

	function actualizarSmith(){
		// R vacío no es un cortocircuito: con ||0 la ROE salía ∞:1. X vacío sí es 0 (carga resistiva).
		var R=parseFloat(document.getElementById('banco-r').value);
		var X=parseFloat(document.getElementById('banco-x').value)||0; /* 0 legítimo */
		if(isNaN(R)){ document.getElementById('banco-vswr').textContent='—'; return; }
		var Z0=50;
		var zr=R/Z0, zi=X/Z0;
		var numR=zr-1, numI=zi, denR=zr+1, denI=zi;
		var denMag2=denR*denR+denI*denI;
		var gammaR=(numR*denR+numI*denI)/denMag2;
		var gammaI=(numI*denR-numR*denI)/denMag2;
		var gammaMag=Math.sqrt(gammaR*gammaR+gammaI*gammaI);
		var vswr = gammaMag<1 ? (1+gammaMag)/(1-gammaMag) : Infinity;
		document.getElementById('banco-vswr').textContent=isFinite(vswr) ? vswr.toFixed(2)+':1' : '∞';
	}

	freqEl.addEventListener('input', actualizar);
	document.getElementById('banco-r').addEventListener('input', actualizarSmith);
	document.getElementById('banco-x').addEventListener('input', actualizarSmith);
	actualizar();
})();

/* ── NAVEGACIÓN POR CATEGORÍAS: cada categoría abre todas sus herramientas juntas ── */
(function(){
	var TOOLS = <?php echo wp_json_encode( array_map( fn( $t ) => [
		'id'      => $t['id'],
		'label'   => $t['label'],
		'cat'     => $t['cat'],
		// 'incluye': ids que se muestran junto a esta. 'oculta': no sale en el catálogo pero
		// conserva panel y URL propios, así que los enlaces ya compartidos siguen funcionando.
		'incluye' => $t['incluye'] ?? [],
		'oculta'  => ! empty( $t['oculta'] ),
	], $tabs ) ); ?>;
	var TOOLS_BY_ID = {};
	TOOLS.forEach(function(t){ TOOLS_BY_ID[t.id] = t; });
	var BASE_URL = '<?php echo esc_js( trailingslashit( home_url( '/herramientas/' ) ) ); ?>';
	// Este archivo se sirve desde /rau-herramientas.js, donde la variable de consulta
	// 'rau_tool' no existe. La página la publica en window.RAU_INIT antes de cargarlo.
	var INITIAL_TOOL = (window.RAU_INIT && window.RAU_INIT.tool) || '';

	var gridEl = document.getElementById('rau-cat-grid');
	var dashEl = document.getElementById('rau-cat-dashboard');
	var panelsEl = document.getElementById('rau-cat-panels');
	var jumpsEl = document.getElementById('rau-cat-jumps');
	var storageEl = document.getElementById('rau-panel-storage');
	var backBtn = document.getElementById('rau-cat-back');
	var toolsEl = document.getElementById('rau-tools');
	var satLibState = 'idle'; // idle | loading | ready

	/* Centro de mando (19.46.0). Al abrir una herramienta la consola se pliega y la herramienta
	   ocupa el centro; al volver, la consola reaparece. Hay un solo receptor en la página: vive en
	   el centro de la consola y pasa al panel «Receptor KiwiSDR» cuando se abre esa herramienta. Moverlo
	   no corta el audio. */
	function vistaHerramienta(on){ if(toolsEl) toolsEl.classList.toggle('rau-vista-herramienta', !!on); }
	function ubicarReceptor(){
		var comp=document.getElementById('rx-componente');
		var slotConsola=document.getElementById('consola-receptor-slot'), slotPanel=document.getElementById('panel-receptor-slot');
		if(!comp || !slotConsola || !slotPanel) return;
		var panel=document.getElementById('panel-receptor');
		var enPanel=toolsEl && toolsEl.classList.contains('rau-vista-herramienta') && panel && panel.parentNode===panelsEl;
		var destino=enPanel ? slotPanel : slotConsola;
		if(comp.parentNode!==destino) destino.appendChild(comp);
		comp.classList.toggle('rx-en-consola', !enPanel);
	}
	window.rauUbicarReceptor = ubicarReceptor;

	function loadSatelliteLib(){
		if(satLibState!=='idle') return;
		satLibState='loading';
		var calcBtn=document.getElementById('sp-calc');
		var statusEl=document.getElementById('sp-status');
		if(calcBtn) calcBtn.disabled=true;
		if(statusEl && !statusEl.dataset.custom) statusEl.textContent='Cargando librería de cálculo orbital…';

		var s=document.createElement('script');
		s.src='https://cdn.jsdelivr.net/npm/satellite.js@5.0.0/dist/satellite.min.js';
		s.onload=function(){
			satLibState='ready';
			if(calcBtn) calcBtn.disabled=false;
			if(typeof window.rauOnSatReady==='function') window.rauOnSatReady();
		};
		s.onerror=function(){
			satLibState='idle';
			if(statusEl){ statusEl.textContent='No se pudo cargar la librería de cálculo orbital. Recarga la página e intenta de nuevo.'; statusEl.dataset.custom='1'; }
		};
		document.head.appendChild(s);
	}

	function showGrid(opts){
		opts = opts || {};
		gridEl.classList.remove('hidden');
		dashEl.classList.add('hidden');
		// Devolver todos los paneles abiertos a su almacenamiento (nunca se destruyen, solo se mueven)
		Array.prototype.slice.call(panelsEl.children).forEach(function(p){ storageEl.appendChild(p); });
		if(!opts.skipHash) history.replaceState(null,'',BASE_URL);
		vistaHerramienta(false); ubicarReceptor();
	}

	// Los iframes de terceros llevan data-src y se cargan la primera vez que su panel se muestra.
	// loading="lazy" no basta: Chromium no aplaza un iframe oculto con display:none, así que el
	// widget de DXheat (hasta 1,8 MB, con su propio contador de visitas) se descargaba en las 60
	// URLs de herramientas aunque nadie abriera el panel de DX.
	function marcarVisible(p){
		if(p.dataset.rauVisto) return;
		p.dataset.rauVisto='1';
		p.querySelectorAll('iframe[data-src]').forEach(function(f){ f.src=f.dataset.src; f.removeAttribute('data-src'); });
	}

	function activateCategory(catName, opts){
		opts = opts || {};
		// Las agrupadas no generan su propia entrada ni su propio salto: aparecen dentro de la
		// herramienta que las incluye.
		var catTools = TOOLS.filter(function(t){ return t.cat===catName && !t.oculta; });
		if(!catTools.length) return false;
		// Una herramienta por dirección: /herramientas/ahora/ muestra «Ahora mismo» y no las otras
		// nueve de Operar (eran 14.500 px de página en un celular). Las agrupadas abren a su madre.
		var mostrar = catTools, objetivo = opts.solo ? TOOLS_BY_ID[opts.solo] : null;
		if(objetivo){
			if(objetivo.oculta){
				var madre = TOOLS.find(function(t){ return (t.incluye||[]).indexOf(objetivo.id)>-1; });
				if(madre) objetivo = madre;
			}
			mostrar = [objetivo];
		}

		// El catálogo vive dentro del modo "Catálogo" de la consola. Sin pasar a ese modo, el panel
		// se activaba dentro de un contenedor oculto: /herramientas/qsl/ abría la página y mostraba
		// el tablero "Radio Now", no la QSL. Pasaba con las 60 URLs de herramienta. Se reutiliza el
		// clic real sobre el modo, igual que el botón "+ Más herramientas".
		var btnCatalogo=document.querySelector('.consola-modo-btn[data-modo="catalogo"]');
		if(btnCatalogo && btnCatalogo.getAttribute('aria-selected')!=='true') btnCatalogo.click();

		gridEl.classList.add('hidden');
		dashEl.classList.remove('hidden');

		// Devolver lo que estuviera mostrado antes, y traer los paneles de esta categoría
		Array.prototype.slice.call(panelsEl.children).forEach(function(p){ storageEl.appendChild(p); });
		jumpsEl.innerHTML='';
		vistaHerramienta(true);
		// Accesos a las demás herramientas de la categoría
		if(catTools.length>1) catTools.forEach(function(t){
			var pill=document.createElement('button');
			pill.type='button';
			var activa = objetivo && objetivo.id===t.id;
			pill.className='inline-flex items-center rounded-lg border px-3 py-1.5 font-mono text-xs font-medium '+(activa?'border-primary/60 bg-primary/15 text-primary':'border-border/70 bg-card text-muted-foreground hover:border-primary/50 hover:text-primary');
			if(activa) pill.setAttribute('aria-current','page');
			pill.textContent=t.label;
			pill.addEventListener('click', function(){ openTool(t.id); });
			jumpsEl.appendChild(pill);
		});
		mostrar.forEach(function(t, idx){
			var panel = document.getElementById('panel-'+t.id);
			if(!panel) return;
			panel.style.display='';
			panel.classList.toggle('pt-8', idx>0);
			panel.classList.toggle('border-t', idx>0);
			panel.classList.toggle('border-border/40', idx>0);
			panelsEl.appendChild(panel);
			marcarVisible(panel);

			// Tras la herramienta van sus agrupadas, sin separador propio: se leen como partes de
			// la misma respuesta y no como herramientas distintas.
			(t.incluye || []).forEach(function(subId){
				var sub = document.getElementById('panel-'+subId);
				if(!sub) return;
				sub.style.display='';
				sub.classList.remove('pt-8','border-t','border-border/40');
				sub.classList.add('pt-6');
				panelsEl.appendChild(sub);
				marcarVisible(sub);
			});

			if(t.id==='satpass' || t.id==='dashboard') loadSatelliteLib();
		});

		ubicarReceptor();
		if(!opts.skipHash) history.replaceState(null,'','#'+catName.toLowerCase().replace(/[^a-z0-9]+/g,'-'));
		if(!opts.skipScroll) dashEl.scrollIntoView({block:'start', behavior:'smooth'});
		return true;
	}

	function openTool(id, opts){
		opts = opts || {};
		var t = TOOLS_BY_ID[id];
		if(!t) return false;
		// «Dashboard en Vivo» quedó dentro del centro de mando: su dirección lleva a la consola
		if(id==='dashboard'){ volverConsola(); return true; }
		activateCategory(t.cat, {skipScroll:true, skipHash:true, solo:id});
		// URL propia real (/herramientas/{id}/) — no un ancla. pushState para que
		// atrás/adelante del navegador funcione como si fueran páginas distintas.
		if(!opts.skipHash){
			if(opts.replace) history.replaceState(null,'',BASE_URL+id+'/');
			else history.pushState(null,'',BASE_URL+id+'/');
		}
		if(!opts.skipScroll) dashEl.scrollIntoView({block:'start', behavior:'smooth'});
		return true;
	}
	function volverConsola(){
		var antena=document.querySelector('.consola-modo-btn[data-modo="antena"]');
		if(antena) antena.click();
		showGrid({skipHash:true});
		history.pushState(null,'',BASE_URL);
		var consola=document.getElementById('rau-consola');
		if(consola) consola.scrollIntoView({block:'start', behavior:'smooth'});
	}
	window.rauVolverConsola = volverConsola;
	var volverBtn=document.getElementById('rau-volver-consola');
	if(volverBtn) volverBtn.addEventListener('click', volverConsola);
	/* El receptor KiwiSDR (inc/receptor-kiwi.php) abre su panel con esto desde rauEscuchar() */
	window.rauAbrirHerramienta = openTool;
	/* Botones «Escuchar» de spots DX, POTA y «Ahora mismo»: data-khz y, si se sabe, data-modo */
	document.addEventListener('click', function(ev){
		var b = ev.target.closest ? ev.target.closest('.rau-escuchar') : null;
		if(!b || typeof window.rauEscuchar !== 'function') return;
		ev.preventDefault();
		window.rauEscuchar(parseFloat(b.dataset.khz), b.dataset.modo || '');
	});
	var irSdr = document.getElementById('dash-sdr-ir');
	if(irSdr) irSdr.addEventListener('click', function(ev){ if(TOOLS_BY_ID.receptor){ ev.preventDefault(); openTool('receptor'); } });

	/* Clics: encabezado de categoría (abre todo), etiqueta de herramienta (acceso directo), accesos rápidos, volver */
	document.querySelectorAll('.rau-cat-open-btn').forEach(function(btn){
		var card = btn.closest('.rau-cat-card');
		btn.addEventListener('click', function(){ activateCategory(card.dataset.cat); });
	});
	document.querySelectorAll('.rau-tool-pill').forEach(function(pill){
		pill.addEventListener('click', function(){ openTool(pill.dataset.tab); });
	});
	document.querySelectorAll('.rau-quick-chip').forEach(function(btn){
		btn.addEventListener('click', function(){ openTool(btn.dataset.tab); });
	});
	if(backBtn) backBtn.addEventListener('click', function(){ showGrid(); });

	/* Atrás/adelante del navegador: tanto con URLs reales (pushState) como con el hash viejo (compatibilidad con links ya compartidos) */
	window.addEventListener('popstate', function(){
		var pathId = location.pathname.replace(BASE_URL.replace(/^https?:\/\/[^/]+/,''), '').replace(/\/$/,'');
		if(pathId && TOOLS_BY_ID[pathId]){ openTool(pathId, {skipHash:true}); return; }
		var id = location.hash.replace('#','');
		if(!id){ showGrid({skipHash:true}); return; }
		if(TOOLS_BY_ID[id]){ openTool(id, {skipHash:true}); return; }
		var cat = Object.keys(TOOLS.reduce(function(a,t){a[t.cat]=1;return a;},{})).find(function(c){ return c.toLowerCase().replace(/[^a-z0-9]+/g,'-')===id; });
		if(cat) activateCategory(cat, {skipHash:true});
		else showGrid({skipHash:true});
	});
	// Por si alguien edita solo el fragmento #... de la URL sin usar atrás/adelante
	window.addEventListener('hashchange', function(){
		var id = location.hash.replace('#','');
		if(!id) return;
		if(TOOLS_BY_ID[id]){ openTool(id, {skipHash:true}); return; }
		var cat = Object.keys(TOOLS.reduce(function(a,t){a[t.cat]=1;return a;},{})).find(function(c){ return c.toLowerCase().replace(/[^a-z0-9]+/g,'-')===id; });
		if(cat) activateCategory(cat, {skipHash:true});
	});

	/* Estado inicial: prioriza la URL real (/herramientas/{id}/) sobre el hash viejo (compatibilidad) */
	(function(){
		if(INITIAL_TOOL && TOOLS_BY_ID[INITIAL_TOOL]){
			openTool(INITIAL_TOOL, {skipHash:true, skipScroll:true});
			// Quien llega a /herramientas/{id}/ viene por esa herramienta: se lleva la vista al panel
			// (sin animación, para que no parezca un salto después de cargar).
			var pInicial=dashEl;
			if(pInicial){
				pInicial.scrollIntoView({block:'start'});
				// Las herramientas de arriba terminan de cargar después del salto y empujan el panel
				// hacia abajo: /herramientas/dxcc/ quedaba bajo el borde de la pantalla. Se vuelve a
				// alinear al terminar la carga, salvo que la persona ya haya empezado a moverse.
				var tocado=false;
				['wheel','touchstart','keydown'].forEach(function(ev){ window.addEventListener(ev, function(){ tocado=true; }, { once:true, passive:true }); });
				var realinear=function(){ if(!tocado && Math.abs(pInicial.getBoundingClientRect().top-80)>40) pInicial.scrollIntoView({block:'start'}); };
				window.addEventListener('load', function(){ realinear(); setTimeout(realinear, 1500); });
			}
			return;
		}
		var startId = location.hash.replace('#','');
		if(!startId){ ubicarReceptor(); return; } // se queda en la consola, que es la pantalla de entrada
		if(TOOLS_BY_ID[startId]){ openTool(startId, {skipScroll:true}); return; }
		var catNames = TOOLS.reduce(function(a,t){ if(a.indexOf(t.cat)===-1) a.push(t.cat); return a; }, []);
		var cat = catNames.find(function(c){ return c.toLowerCase().replace(/[^a-z0-9]+/g,'-')===startId; });
		if(cat) activateCategory(cat, {skipHash:true, skipScroll:true});
	})();

	/* ── Recorrido guiado inicial (solo la primera vez, localStorage) ── */
	(function(){
		try {
			if(!localStorage.getItem('rau_tour_visto')){
				document.getElementById('rau-tour-banner').classList.remove('hidden');
				document.getElementById('rau-tour-banner').classList.add('flex');
			}
		} catch(e){}
		var cerrarBtn=document.getElementById('rau-tour-cerrar');
		if(cerrarBtn){
			cerrarBtn.addEventListener('click', function(){
				document.getElementById('rau-tour-banner').classList.add('hidden');
				try { localStorage.setItem('rau_tour_visto','1'); } catch(e){}
			});
		}
	})();

	/* ── Favoritos (localStorage, sin cuenta) ── */
	var FAV_KEY='rau_favoritos';
	function getFavoritos(){
		try { return JSON.parse(localStorage.getItem(FAV_KEY)||'[]'); } catch(e){ return []; }
	}
	function setFavoritos(arr){
		try { localStorage.setItem(FAV_KEY, JSON.stringify(arr)); } catch(e){}
	}
	function esFavorito(id){ return getFavoritos().indexOf(id)>-1; }

	var NOMBRES_MODOS={
		'modo:antena':'🔧 Construir antena', 'modo:transmitir':'📡 Antes de transmitir',
		'modo:campo':'📻 En el campo', 'modo:concurso':'🏆 En concurso',
	};

	function renderFavoritos(){
		var favs=getFavoritos();
		var cont=document.getElementById('rau-favoritos');
		var lista=document.getElementById('rau-favoritos-lista');
		var soloHerramientas=favs.filter(function(id){ return id.indexOf('modo:')!==0; });
		if(!soloHerramientas.length){ cont.classList.add('hidden'); return; }
		lista.innerHTML=soloHerramientas.map(function(id){
			var t=TOOLS_BY_ID[id];
			if(!t) return '';
			return '<button type="button" data-tab="'+id+'" class="rau-fav-chip inline-flex items-center gap-1.5 rounded-xl border-2 border-amber-400/40 bg-amber-400/10 px-4 py-2 text-sm font-bold text-amber-400 hover:bg-amber-400/20">★ '+t.label+'</button>';
		}).join('');
		cont.classList.remove('hidden');
		lista.querySelectorAll('.rau-fav-chip').forEach(function(btn){
			btn.addEventListener('click', function(){ openTool(btn.dataset.tab); });
		});
	}

	/* ── MI ESPACIO DE TRABAJO: barra siempre visible, incluye modos Y herramientas fijadas ── */
	function renderConsolaFijados(){
		var wrap=document.getElementById('consola-fijados-wrap');
		var lista=document.getElementById('consola-fijados-lista');
		if(!wrap || !lista) return;
		var favs=getFavoritos();
		if(!favs.length){ wrap.classList.add('hidden'); wrap.classList.remove('flex'); return; }

		lista.innerHTML=favs.map(function(id){
			var esModo=id.indexOf('modo:')===0;
			var nombre = esModo ? NOMBRES_MODOS[id] : (TOOLS_BY_ID[id] ? TOOLS_BY_ID[id].label : null);
			if(!nombre) return '';
			return '<button type="button" data-fijado="'+id+'" class="inline-flex items-center gap-1 rounded-lg border border-amber-400/30 bg-amber-400/5 px-2.5 py-1 font-mono text-[11px] font-bold text-amber-400 hover:bg-amber-400/15">'+nombre+'</button>';
		}).join('');

		lista.querySelectorAll('[data-fijado]').forEach(function(btn){
			btn.addEventListener('click', function(){
				var id=btn.dataset.fijado;
				if(id.indexOf('modo:')===0){
					var modoBtn=document.querySelector('.consola-modo-btn[data-modo="'+id.replace('modo:','')+'"]');
					if(modoBtn) modoBtn.click();
				} else {
					var catalogoBtn=document.querySelector('.consola-modo-btn[data-modo="catalogo"]');
					if(catalogoBtn) catalogoBtn.click();
					openTool(id);
				}
			});
		});
		wrap.classList.remove('hidden'); wrap.classList.add('flex');
	}

	function actualizarEstrellas(){
		document.querySelectorAll('.rau-fav-star').forEach(function(star){
			var activo=esFavorito(star.dataset.tab);
			star.textContent = activo ? '★' : '☆';
			star.classList.toggle('text-amber-400', activo);
		});
		document.querySelectorAll('.rau-fijar-modo').forEach(function(star){
			var activo=esFavorito('modo:'+star.dataset.modoFijar);
			star.textContent = activo ? '★' : '☆';
			star.classList.toggle('text-amber-400', activo);
		});
	}
	document.querySelectorAll('.rau-fav-star').forEach(function(star){
		star.addEventListener('click', function(e){
			e.stopPropagation();
			var id=star.dataset.tab;
			var favs=getFavoritos();
			var idx=favs.indexOf(id);
			if(idx>-1) favs.splice(idx,1); else favs.push(id);
			setFavoritos(favs);
			actualizarEstrellas();
			renderFavoritos();
			renderConsolaFijados();
		});
	});
	document.querySelectorAll('.rau-fijar-modo').forEach(function(star){
		star.addEventListener('click', function(e){
			e.stopPropagation();
			var id='modo:'+star.dataset.modoFijar;
			var favs=getFavoritos();
			var idx=favs.indexOf(id);
			if(idx>-1) favs.splice(idx,1); else favs.push(id);
			setFavoritos(favs);
			actualizarEstrellas();
			renderConsolaFijados();
		});
	});
	actualizarEstrellas();
	renderFavoritos();
	renderConsolaFijados();

	/* ── Buscador: filtra las cards de categoría visibles en el grid ── */
	var searchEl = document.getElementById('rau-tool-search');
	var noResultsEl = document.getElementById('rau-tool-noresults');
	var resultsEl = document.getElementById('rau-search-results');

	if(searchEl){
		searchEl.addEventListener('input', function(){
			var q = this.value.trim().toLowerCase();

			// Dropdown de herramientas específicas (no solo categorías)
			if(resultsEl){
				if(!q){
					resultsEl.classList.add('hidden');
					resultsEl.innerHTML='';
				} else {
					var coincidencias = TOOLS.filter(function(t){
						return t.label.toLowerCase().indexOf(q) > -1;
					}).slice(0, 8);
					if(coincidencias.length){
						resultsEl.innerHTML = coincidencias.map(function(t){
							return '<button type="button" data-tab="'+t.id+'" class="rau-search-item flex w-full items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-muted/40 border-b border-border/40 last:border-0">'
								+'<span>'+t.label+'</span>'
								+'<span class="font-mono text-[10px] uppercase text-muted-foreground">'+t.cat+'</span>'
								+'</button>';
						}).join('');
						resultsEl.classList.remove('hidden');
					} else {
						resultsEl.innerHTML='<p class="px-4 py-3 text-sm text-muted-foreground">Sin resultados para "'+this.value+'"</p>';
						resultsEl.classList.remove('hidden');
					}
				}
			}

			// Filtro de tarjetas de categoría (comportamiento existente, se mantiene)
			var visibles = 0;
			document.querySelectorAll('.rau-cat-card').forEach(function(card){
				var match = !q || card.dataset.label.indexOf(q) > -1;
				card.style.display = match ? '' : 'none';
				if(match) visibles++;
			});
			if(noResultsEl) noResultsEl.classList.toggle('hidden', visibles > 0);
		});

		// Clic en un resultado del dropdown -> abre la herramienta directamente
		if(resultsEl){
			resultsEl.addEventListener('click', function(e){
				var btn = e.target.closest('.rau-search-item');
				if(!btn) return;
				searchEl.value='';
				resultsEl.classList.add('hidden');
				resultsEl.innerHTML='';
				openTool(btn.dataset.tab);
			});
		}

		// Cerrar el dropdown al hacer clic afuera
		document.addEventListener('click', function(e){
			if(resultsEl && !resultsEl.contains(e.target) && e.target !== searchEl){
				resultsEl.classList.add('hidden');
			}
		});
	}
})();

/* ── RELOJ UTC ── */
function pad(n){return String(n).padStart(2,'0');}
function tickClock(){
	const now=new Date();
	const days=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
	const months=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
	const utcH=now.getUTCHours(),utcM=now.getUTCMinutes(),utcS=now.getUTCSeconds();
	const utcDay=days[now.getUTCDay()];
	const utcDate=`${utcDay} ${pad(now.getUTCDate())} ${months[now.getUTCMonth()]} ${now.getUTCFullYear()}`;
	document.getElementById('clock-utc').textContent=`${pad(utcH)}:${pad(utcM)}:${pad(utcS)}`;
	document.getElementById('date-utc').textContent=utcDate;
	// Colombia UTC-5
	const col=new Date(now.getTime()-5*3600000);
	const colH=col.getUTCHours(),colM=col.getUTCMinutes(),colS=col.getUTCSeconds();
	const colDay=days[col.getUTCDay()];
	const colDate=`${colDay} ${pad(col.getUTCDate())} ${months[col.getUTCMonth()]} ${col.getUTCFullYear()}`;
	document.getElementById('clock-local').textContent=`${pad(colH)}:${pad(colM)}:${pad(colS)}`;
	document.getElementById('date-local').textContent=colDate;
}
tickClock(); setInterval(tickClock,1000);

/* ── PROPAGACIÓN — via proxy PHP (evita CORS) ── */
(function(){
	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar', {cache:'no-store'})
		.then(r=>r.json())
		.then(json=>{
			if(!json.success) throw new Error('proxy_error');
			const d=json.data;
			document.getElementById('prop-sfi').textContent=d.sfi||'—';
			document.getElementById('prop-a').textContent=d.aindex||'—';
			document.getElementById('prop-k').textContent=d.kindex||'—';
			document.getElementById('prop-xray').textContent=d.xray||'—';
			const ki=parseInt(d.kindex,10);
			let status='', cls='text-muted-foreground';
			if(isNaN(ki)){
				// parseInt(...)||0 convertía un dato ausente en K=0, o sea que si HamQSL no
				// reportaba el índice el panel anunciaba "condiciones excelentes" en verde.
				status='HamQSL no reportó el índice K en esta actualización.';
			}
			else if(ki<=1){status='Condiciones excelentes para HF.';cls='text-emerald-400';}
			else if(ki<=3){status='Condiciones buenas para HF.';cls='text-primary';}
			else if(ki===4){status='Condiciones perturbadas. Posible degradación en HF.';cls='text-amber-400';}
			else{status='Tormenta geomagnética activa. HF muy degradado.';cls='text-rose-400';}
			if(d.updated) status+=' · Actualizado: '+d.updated;
			const el=document.getElementById('prop-status');
			el.textContent=status;
			// Se quitan solo las clases de color conocidas. El replace anterior, /text-\S+/g,
			// barría también las de tamaño (text-sm, text-xs) y desarmaba la tipografía.
			el.className=el.className.split(/\s+/).filter(function(c){
				return !/^text-(emerald|amber|rose|primary|muted)/.test(c);
			}).join(' ')+' '+cls;
		}).catch((e)=>{ rauErr(e);
			document.getElementById('prop-status').textContent='No se pudo cargar el panel solar. Consulta los enlaces externos abajo.';
		});
})();

/* ── ALERTAS DE BANDA ABIERTA — reutiliza el mismo proxy rau_solar, ahora con notificaciones de escritorio opcionales ── */
(function(){
	var grid=document.getElementById('bandalert-grid');
	if(!grid) return;

	function colorFor(cond){
		// HamQSL no siempre reporta ambos periodos: el proxy PHP rellena con '—' lo que falta.
		// Sin este caso, un dato ausente caía en el "return" final y se pintaba rojo como
		// "Pobre", que es afirmar algo que la fuente nunca dijo.
		if(!cond || /^[\s—-]*$/.test(cond)) return {bar:'bg-muted',text:'text-muted-foreground',label:'Sin dato'};
		if(/good/i.test(cond)) return {bar:'bg-emerald-400',text:'text-emerald-400',label:'Buena'};
		if(/fair/i.test(cond)) return {bar:'bg-amber-400',text:'text-amber-400',label:'Regular'};
		return {bar:'bg-rose-400',text:'text-rose-400',label:'Pobre'};
	}

	var yaEstabaAbierta=false;

	function checkBands(){
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar', {cache:'no-store'})
			.then(r=>r.json())
			.then(json=>{
				if(!json.success) throw new Error('proxy_error');
				var d=json.data;
				var bands=d.bands||[];
				if(!bands.length){
					document.getElementById('bandalert-status').textContent='HamQSL no reportó condiciones por banda en esta actualización. Intenta de nuevo en unos minutos.';
					return;
				}
				var html='';
				var hayAlerta=false;
				bands.forEach(function(b){
					var day=colorFor(b.day), night=colorFor(b.night);
					if(/good/i.test(b.day) && /12m-10m|17m-15m/.test(b.name)) hayAlerta=true;
					html+='<div class="rounded-xl border border-border/70 bg-background p-4">'
						+'<p class="mb-3 font-mono text-sm font-bold text-foreground">'+b.name+'</p>'
						+'<div class="space-y-2">'
						+'<div class="flex items-center justify-between"><span class="text-xs text-muted-foreground">Día</span>'
						+'<span class="inline-flex items-center gap-1.5 font-mono text-xs font-medium '+day.text+'"><span class="h-2 w-2 rounded-full '+day.bar+'"></span>'+day.label+'</span></div>'
						+'<div class="flex items-center justify-between"><span class="text-xs text-muted-foreground">Noche</span>'
						+'<span class="inline-flex items-center gap-1.5 font-mono text-xs font-medium '+night.text+'"><span class="h-2 w-2 rounded-full '+night.bar+'"></span>'+night.label+'</span></div>'
						+'</div></div>';
				});
				grid.innerHTML=html;
				document.getElementById('bandalert-status').textContent='Actualizado: '+(d.updated||'—')+' · Índice K actual: '+(d.kindex||'—');
				document.getElementById('bandalert-banner').classList.toggle('hidden', !hayAlerta);

				if(hayAlerta && !yaEstabaAbierta && typeof Notification!=='undefined' && Notification.permission==='granted'){
					new Notification('¡Banda alta abierta! 📡', { body:'RAU detectó buena condición en 15-10m ahora mismo.', icon:'<?php echo esc_url( get_template_directory_uri() . "/assets/images/favicon-32x32.png" ); ?>' });
				}
				yaEstabaAbierta=hayAlerta;
			}).catch((e)=>{ rauErr(e);
				document.getElementById('bandalert-status').textContent='No se pudo cargar el panel de condiciones. Intenta de nuevo más tarde.';
			});
	}
	checkBands();
	setInterval(checkBands, 5*60*1000);

	var notifBtn=document.getElementById('bandalert-notif-btn');
	var notifStatus=document.getElementById('bandalert-notif-status');

	function urlBase64ToUint8Array(base64String){
		var padding='='.repeat((4 - base64String.length % 4) % 4);
		var base64=(base64String+padding).replace(/-/g,'+').replace(/_/g,'/');
		var rawData=window.atob(base64);
		var outputArray=new Uint8Array(rawData.length);
		for(var i=0;i<rawData.length;++i){ outputArray[i]=rawData.charCodeAt(i); }
		return outputArray;
	}

	function mostrarEstadoNotif(texto, esError){
		notifStatus.classList.remove('hidden');
		notifStatus.textContent=texto;
		notifStatus.className='mb-6 text-xs '+(esError?'text-rose-400':'text-emerald-400');
	}

	function actualizarBotonNotif(){
		if(!('serviceWorker' in navigator) || !('PushManager' in window)){
			notifBtn.disabled=true; notifBtn.textContent='Tu navegador no soporta notificaciones push reales';
			return;
		}
		if(Notification.permission==='denied'){
			mostrarEstadoNotif('Bloqueaste las notificaciones para este sitio — actívalas desde la configuración del navegador si cambias de opinión.', true);
		}
	}
	actualizarBotonNotif();

	if(notifBtn){
		notifBtn.addEventListener('click', function(){
			if(!('serviceWorker' in navigator) || !('PushManager' in window)) return;
			notifBtn.disabled=true;
			notifBtn.textContent='Activando…';

			navigator.serviceWorker.ready.then(function(registration){
				return fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_vapid_public_key')
					.then(function(r){ return r.json(); })
					.then(function(json){
						if(!json.success || !json.data.key) throw new Error('sin clave VAPID');
						return registration.pushManager.subscribe({
							userVisibleOnly: true,
							applicationServerKey: urlBase64ToUint8Array(json.data.key)
						});
					});
			}).then(function(subscription){
				return fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_push_subscribe', {
					method:'POST',
					body: JSON.stringify(subscription)
				});
			}).then(function(){
				notifBtn.textContent='🔔 Notificaciones activadas — llegan aunque cierres el navegador';
				mostrarEstadoNotif('Listo. Si quieres desactivarlas, hazlo desde la configuración de notificaciones de tu navegador para este sitio.', false);
			}).catch(function(err){
				notifBtn.disabled=false;
				notifBtn.textContent='🔔 Avisarme (aunque tenga el navegador cerrado) cuando abra una banda alta';
				mostrarEstadoNotif('No se pudo activar. '+(Notification.permission==='denied' ? 'Bloqueaste el permiso de notificaciones.' : 'Intenta de nuevo.'), true);
			});
		});
	}
})();

/* ── ANTENAS ── */
rauOn('ant-calc','click',()=>{
	const f=parseFloat(document.getElementById('ant-freq').value);
	const t=document.getElementById('ant-type').value;
	const vf=parseFloat(document.getElementById('ant-vf').value)||0.95;
	const res=document.getElementById('ant-result');
	if(!f||f<=0){res.innerHTML='<p class="text-rose-400 text-sm">Ingresa una frecuencia válida.</p>';return;}
	const c=299.792458;
	const wl=c/f;
	let html='';
	const fmt=n=>n.toFixed(3);
	const fmt2=n=>n.toFixed(1);
	if(t==='dipole'){
		const L=wl*0.5*vf,half=L/2;
		html=`<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Dipolo λ/2 — ${f} MHz</p>
		<div class="space-y-2">
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Longitud total</span><span class="font-mono font-bold text-primary">${fmt(L)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Cada brazo</span><span class="font-mono font-bold text-foreground">${fmt(half)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Longitud en cm</span><span class="font-mono text-sm text-muted-foreground">${fmt2(L*100)} cm</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Cada brazo en cm</span><span class="font-mono text-sm text-muted-foreground">${fmt2(half*100)} cm</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Longitud de onda</span><span class="font-mono text-sm text-muted-foreground">${fmt(wl)} m</span></div>
		</div>`;
	} else if(t==='vertical'){
		const L=wl*0.25*vf;
		html=`<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Vertical λ/4 — ${f} MHz</p>
		<div class="space-y-2">
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Longitud del radiador</span><span class="font-mono font-bold text-primary">${fmt(L)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">En cm</span><span class="font-mono text-sm text-muted-foreground">${fmt2(L*100)} cm</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Radiales (mín. 4)</span><span class="font-mono text-sm text-muted-foreground">${fmt(L)} m c/u</span></div>
		</div>`;
	} else if(t==='5_8'){
		const L=wl*0.625*vf;
		html=`<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Vertical 5/8λ — ${f} MHz</p>
		<div class="space-y-2">
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Longitud del radiador</span><span class="font-mono font-bold text-primary">${fmt(L)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">En cm</span><span class="font-mono text-sm text-muted-foreground">${fmt2(L*100)} cm</span></div>
		</div>`;
	} else if(t==='loop'){
		// Un loop de onda completa necesita ser ~2% MÁS LARGO que 1λ (no más corto
		// como las antenas de alambre recto) — no se usa el factor de acortamiento vf aquí.
		const L=wl*1.02;
		html=`<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Loop onda completa — ${f} MHz</p>
		<div class="space-y-2">
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Perímetro total</span><span class="font-mono font-bold text-primary">${fmt(L)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Lado (cuadrado)</span><span class="font-mono text-sm text-muted-foreground">${fmt(L/4)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Radio (circular)</span><span class="font-mono text-sm text-muted-foreground">${fmt(L/(2*Math.PI))} m</span></div>
		</div>`;
	} else if(t==='yagi2'){
		const drv=wl*0.5*vf, ref=drv*1.047, spacing=wl*0.25;
		html=`<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Yagi 2 elementos — ${f} MHz</p>
		<div class="space-y-2">
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Driven (radiador)</span><span class="font-mono font-bold text-primary">${fmt(drv)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Reflector</span><span class="font-mono font-bold text-foreground">${fmt(ref)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Espaciado</span><span class="font-mono text-sm text-muted-foreground">~${fmt(spacing)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Ganancia aprox.</span><span class="font-mono text-sm text-muted-foreground">~5 dBi</span></div>
		</div>`;
	} else if(t==='yagi3'){
		const drv=wl*0.5*vf, ref=drv*1.047, dir=drv*0.930, sp1=wl*0.25, sp2=wl*0.3;
		html=`<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Yagi 3 elementos — ${f} MHz</p>
		<div class="space-y-2">
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Driven (radiador)</span><span class="font-mono font-bold text-primary">${fmt(drv)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Reflector</span><span class="font-mono font-bold text-foreground">${fmt(ref)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Director</span><span class="font-mono font-bold text-foreground">${fmt(dir)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Esp. refl.–driven</span><span class="font-mono text-sm text-muted-foreground">~${fmt(sp1)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Esp. driven–dir.</span><span class="font-mono text-sm text-muted-foreground">~${fmt(sp2)} m</span></div>
		<div class="flex justify-between"><span class="text-sm text-muted-foreground">Ganancia aprox.</span><span class="font-mono text-sm text-muted-foreground">~7–8 dBi</span></div>
		</div>`;
	}
	res.innerHTML=html;
});

/* ── POTENCIA ── */
rauOn('pw-watts','input',function(){
	const w=parseFloat(this.value);
	const res=document.getElementById('pw-result');
	if(!w||w<=0){res.classList.add('hidden');return;}
	res.classList.remove('hidden');
	document.getElementById('pw-dbm').textContent=(10*Math.log10(w*1000)).toFixed(2)+' dBm';
	document.getElementById('pw-dbw').textContent=(10*Math.log10(w)).toFixed(2)+' dBW';
	document.getElementById('pw-mw').textContent=(w*1000).toFixed(0)+' mW';
});
// Ganancia dBi ↔ dBd
rauOn('gain-dbi','input',function(){
	const v=parseFloat(this.value);
	if(!isNaN(v)) document.getElementById('gain-dbd').value=(v-2.15).toFixed(2);
});
rauOn('gain-dbd','input',function(){
	const v=parseFloat(this.value);
	if(!isNaN(v)) document.getElementById('gain-dbi').value=(v+2.15).toFixed(2);
});

/* ── CONVERSOR RF: potencia (dBm/W/mW/Vrms) y ROE/pérdida de retorno ── */
(function(){
	var R=50; // impedancia de referencia
	var dbmEl=document.getElementById('rf-dbm'), wEl=document.getElementById('rf-w'),
		mwEl=document.getElementById('rf-mw'), vrmsEl=document.getElementById('rf-vrms');
	if(!dbmEl) return;

	function fromDbm(dbm){
		var mw=Math.pow(10, dbm/10);
		var w=mw/1000;
		var vrms=Math.sqrt(w*R);
		return {mw:mw, w:w, vrms:vrms};
	}
	function setFromMw(mw){
		var dbm=10*Math.log10(mw);
		var w=mw/1000;
		var vrms=Math.sqrt(w*R);
		dbmEl.value=dbm.toFixed(2);
		wEl.value=w<0.001?w.toExponential(3):w.toFixed(w<1?4:2);
		mwEl.value=mw<0.001?mw.toExponential(3):mw.toFixed(mw<1?4:2);
		vrmsEl.value=vrms.toFixed(3);
	}

	dbmEl.addEventListener('input',function(){
		var v=parseFloat(this.value); if(isNaN(v)) return;
		var r=fromDbm(v);
		wEl.value=r.w<0.001?r.w.toExponential(3):r.w.toFixed(r.w<1?4:2);
		mwEl.value=r.mw<0.001?r.mw.toExponential(3):r.mw.toFixed(r.mw<1?4:2);
		vrmsEl.value=r.vrms.toFixed(3);
	});
	wEl.addEventListener('input',function(){
		var v=parseFloat(this.value); if(isNaN(v)||v<=0) return;
		setFromMw(v*1000);
	});
	mwEl.addEventListener('input',function(){
		var v=parseFloat(this.value); if(isNaN(v)||v<=0) return;
		setFromMw(v);
	});
	vrmsEl.addEventListener('input',function(){
		var v=parseFloat(this.value); if(isNaN(v)||v<=0) return;
		var w=(v*v)/R;
		setFromMw(w*1000);
	});
	// Valor inicial (37 dBm = 5 W, típico móvil VHF)
	(function(){
		var r=fromDbm(parseFloat(dbmEl.value)||37);
		wEl.value=r.w.toFixed(2); mwEl.value=r.mw.toFixed(0); vrmsEl.value=r.vrms.toFixed(2);
	})();

	// ── ROE / Pérdida de retorno ──
	var vswrEl=document.getElementById('rf-vswr'), rlEl=document.getElementById('rf-rl'),
		reflEl=document.getElementById('rf-refl'), transEl=document.getElementById('rf-trans');

	function updateFromVSWR(vswr){
		if(vswr<1) vswr=1;
		var gamma=(vswr-1)/(vswr+1);
		var rl=gamma>0?-20*Math.log10(gamma):99;
		var reflPct=gamma*gamma*100;
		rlEl.value=rl>90?'∞':rl.toFixed(2);
		reflEl.textContent=reflPct.toFixed(2)+'%';
		transEl.textContent=(100-reflPct).toFixed(2)+'%';
	}
	function updateFromRL(rl){
		var gamma=Math.pow(10,-rl/20);
		var vswr=(1+gamma)/(1-gamma);
		var reflPct=gamma*gamma*100;
		vswrEl.value=vswr.toFixed(3);
		reflEl.textContent=reflPct.toFixed(2)+'%';
		transEl.textContent=(100-reflPct).toFixed(2)+'%';
	}
	vswrEl.addEventListener('input',function(){
		var v=parseFloat(this.value); if(isNaN(v)||v<1) return;
		updateFromVSWR(v);
	});
	rlEl.addEventListener('input',function(){
		var v=parseFloat(this.value); if(isNaN(v)||v<=0) return;
		updateFromRL(v);
	});
	updateFromVSWR(parseFloat(vswrEl.value)||1.5);
})();
/* ── HISTORIAL SFI/K (sin cuenta, guardado del lado del servidor una vez al día) ── */
(function(){
	var el=document.getElementById('sfihist-content');
	if(!el) return;

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar_history', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success) throw 0;
			var hist=json.data;
			var dias=Object.keys(hist).sort();
			if(!dias.length){
				el.innerHTML='<p class="text-sm text-muted-foreground">Todavía no hay historial guardado — vuelve mañana, se toma una foto automática cada día.</p>';
				return;
			}
			var W=600,H=160,pad=24;
			// Un día sin dato entraba como 0 y la gráfica dibujaba una caída a fondo, que se lee
			// como un desplome solar en vez de como un hueco en el registro. Se marcan como null
			// y la línea se corta ahí.
			var sfiVals=dias.map(function(d){ var v=parseFloat(hist[d].sfi); return isNaN(v)?null:v; });
			var kVals=dias.map(function(d){ var v=parseFloat(hist[d].k); return isNaN(v)?null:v; });
			var maxSfi=Math.max.apply(null, sfiVals.filter(function(v){return v!==null;}).concat([150]));
			var maxK=9;

			function pathFor(vals, maxVal){
				var d='', reanudar=true;
				vals.forEach(function(v,i){
					if(v===null){ reanudar=true; return; } // hueco: la próxima empieza con M
					var x=pad+(i/(Math.max(vals.length-1,1)))*(W-pad*2);
					var y=H-pad-(v/maxVal)*(H-pad*2);
					d+=(reanudar?'M':'L')+x.toFixed(1)+','+y.toFixed(1)+' ';
					reanudar=false;
				});
				return d.trim();
			}

			var svg='<svg viewBox="0 0 '+W+' '+H+'" class="w-full rounded-lg border border-border/50 bg-background">'
				+'<line x1="'+pad+'" y1="'+(H-pad)+'" x2="'+(W-pad)+'" y2="'+(H-pad)+'" stroke="hsl(var(--border)/.5)" stroke-width="1"/>'
				+'<path d="'+pathFor(sfiVals,maxSfi)+'" fill="none" stroke="hsl(var(--primary))" stroke-width="2"/>'
				+'<path d="'+pathFor(kVals,maxK)+'" fill="none" stroke="#fb7185" stroke-width="2" stroke-dasharray="4 3"/>'
				+'</svg>';

			var ultimo=hist[dias[dias.length-1]];
			el.innerHTML='<div class="mb-3 flex flex-wrap items-center gap-4 text-xs">'
				+'<span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-primary"></span> SFI (últimos '+dias.length+' días, hoy: '+(ultimo.sfi||'—')+')</span>'
				+'<span class="flex items-center gap-1.5"><span class="h-2 w-4 rounded-full bg-rose-400"></span> Índice K (hoy: '+(ultimo.k||'—')+')</span>'
				+'</div>'+svg
				+'<p class="mt-2 text-xs text-muted-foreground">El historial empieza a acumularse desde que se instaló esta versión del tema — entre más días pasen, más útil se vuelve la gráfica.</p>';
		}).catch(function(e){ rauErr(e);
			el.innerHTML='<p class="text-sm text-muted-foreground">No se pudo cargar el historial. Intenta de nuevo más tarde.</p>';
		});
})();

/* ── GRAYLINE (amanecer/atardecer para Medellín, ecuación solar estándar, sin API externa) ── */
(function(){
	var amEl=document.getElementById('gl-amanecer');
	if(!amEl) return;
	var RAU_QTH_GL=(function(){
		try {
			var g=JSON.parse(localStorage.getItem('rau_mi_qth')||'null');
			if(g && typeof g.lat==='number' && typeof g.lng==='number') return g;
		} catch(e){}
		return {lat:6.2442,lng:-75.5812};
	})();
	var LAT=RAU_QTH_GL.lat, LNG=RAU_QTH_GL.lng;
	// Huso horario ESTIMADO por longitud (convención astronómica: 15° = 1 hora) — no es el huso horario
	// civil real (que depende de fronteras políticas), pero es mucho más cercano que asumir Colombia
	// siempre. Para Colombia (LNG≈-75°) da exactamente UTC-5, igual que antes.
	var husoEstimado = Math.round(LNG/15);

	function solarNoonYAngulo(dia){
		var gamma = 2*Math.PI/365 * (dia-1);
		var eqTime = 229.18*(0.000075+0.001868*Math.cos(gamma)-0.032077*Math.sin(gamma)-0.014615*Math.cos(2*gamma)-0.040849*Math.sin(2*gamma));
		var decl = 0.006918-0.399912*Math.cos(gamma)+0.070257*Math.sin(gamma)-0.006758*Math.cos(2*gamma)+0.000907*Math.sin(2*gamma)-0.002697*Math.cos(3*gamma)+0.00148*Math.sin(3*gamma);
		return {eqTime:eqTime, decl:decl};
	}
	function horaSalidaPuesta(){
		var ahora=new Date();
		var inicioAno=new Date(Date.UTC(ahora.getUTCFullYear(),0,1));
		var dia=Math.floor((ahora-inicioAno)/86400000)+1;
		var s=solarNoonYAngulo(dia);
		var latRad=LAT*Math.PI/180;
		var cosH=(Math.cos(90.833*Math.PI/180)/(Math.cos(latRad)*Math.cos(s.decl))) - Math.tan(latRad)*Math.tan(s.decl);
		cosH=Math.max(-1,Math.min(1,cosH));
		var haAtardecer=Math.acos(cosH)*180/Math.PI;
		// Convención NOAA: longitud positiva al ESTE y el ángulo horario se RESTA para la salida
		// y se SUMA para la puesta. La versión anterior escribía 720-4*(-LNG∓ha)-eqTime, que
		// invertía el signo de la longitud y además cruzaba el del ángulo horario. Para Medellín
		// daba amanecer 08:03 y atardecer 19:50, cuando lo real es 05:55 y 18:07 — más de dos
		// horas corrido en ambos extremos, justo en la herramienta que sirve para cronometrar
		// el paso del grayline.
		var salidaMin=720-4*(LNG+haAtardecer)-s.eqTime;
		var puestaMin=720-4*(LNG-haAtardecer)-s.eqTime;
		return {salida:salidaMin, puesta:puestaMin};
	}
	function fmtMin(totalMin){
		totalMin=((totalMin%1440)+1440)%1440;
		var localMin=totalMin+husoEstimado*60;
		localMin=((localMin%1440)+1440)%1440;
		var h=Math.floor(localMin/60), m=Math.round(localMin%60);
		return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0');
	}
	var r=horaSalidaPuesta();
	amEl.textContent=fmtMin(r.salida);
	document.getElementById('gl-atardecer').textContent=fmtMin(r.puesta);
})();

/* ── CICLO SOLAR 25 (fechas verificadas: NASA/NOAA) ── */
(function(){
	var el=document.getElementById('ciclo-anos');
	if(!el) return;
	var INICIO=new Date(Date.UTC(2019,11,1)); // diciembre 2019
	var MAXIMO=new Date(Date.UTC(2024,9,15)); // 15 de octubre de 2024, declarado oficialmente
	var FIN_ESTIMADO=new Date(Date.UTC(2030,5,1)); // ~2030, punto medio del rango oficial 2029-2032

	var ahora=new Date();
	var anos=(ahora-INICIO)/(365.25*86400000);
	document.getElementById('ciclo-anos').textContent=anos.toFixed(1);

	var mesesDesdeMax=(ahora-MAXIMO)/(30*86400000);
	if(ahora<MAXIMO){
		document.getElementById('ciclo-fase').textContent='Ascenso ↗';
		document.getElementById('ciclo-desde-max').textContent='—';
	} else {
		document.getElementById('ciclo-fase').textContent='Descenso ↘';
		document.getElementById('ciclo-desde-max').textContent=Math.round(mesesDesdeMax)+' meses';
	}

	var progreso=Math.min(100, Math.max(0, (ahora-INICIO)/(FIN_ESTIMADO-INICIO)*100));
	document.getElementById('ciclo-progreso').style.width=progreso.toFixed(0)+'%';
})();

/* ── PREDICTOR DE APAGÓN HF (escala oficial NOAA R1-R5, verificada) ── */
(function(){
	var el=document.getElementById('apagon-resultado');
	if(!el) return;

	var ESCALA=[
		{clase:'A / B', nivel:'—', color:'text-muted-foreground', desc:'Sin efecto perceptible en HF', duracion:'—'},
		{clase:'C', nivel:'—', color:'text-muted-foreground', desc:'Cambios apenas perceptibles en 160m/80m', duracion:'—'},
		{clase:'M1–M4', nivel:'R1 Menor', color:'text-amber-400', desc:'Degradación débil de HF en cara diurna', duracion:'Minutos'},
		{clase:'M5–M9', nivel:'R2 Moderado', color:'text-amber-400', desc:'Apagón limitado de HF en cara diurna', duracion:'Decenas de minutos'},
		{clase:'X1–X9', nivel:'R3 Fuerte', color:'text-rose-400', desc:'Apagón amplio de HF, pérdida de contacto', duracion:'~1 hora'},
		{clase:'X10–X19', nivel:'R4 Severo', color:'text-rose-400', desc:'Apagón en casi toda la cara diurna, errores de GPS', duracion:'1-2 horas'},
		{clase:'X20+', nivel:'R5 Extremo', color:'text-rose-400', desc:'Apagón completo de la cara diurna', duracion:'Varias horas'},
	];

	document.getElementById('apagon-tabla').innerHTML=ESCALA.map(function(e){
		return '<tr class="border-b border-border/30"><td class="py-1.5">'+e.clase+'</td><td class="'+e.color+'">'+e.nivel+'</td><td class="text-muted-foreground">'+e.duracion+'</td></tr>';
	}).join('');

	function clasificar(xrayRaw){
		var m=trim(xrayRaw).match(/^([ABCMX])([\d.]+)/i);
		if(!m) return null;
		var clase=m[1].toUpperCase(), numero=parseFloat(m[2]);
		if(clase==='A'||clase==='B') return ESCALA[0];
		if(clase==='C') return ESCALA[1];
		if(clase==='M') return numero<5 ? ESCALA[2] : ESCALA[3];
		if(clase==='X') return numero<10 ? ESCALA[4] : (numero<20 ? ESCALA[5] : ESCALA[6]);
		return null;
	}
	function trim(s){ return (s||'').toString().trim(); }

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success){ el.innerHTML='<p class="text-sm text-muted-foreground">No se pudo cargar el dato actual.</p>'; return; }
			var xray=json.data.xray||'';
			var resultado=clasificar(xray);
			if(!resultado){ el.innerHTML='<p class="text-sm text-muted-foreground">Sin dato de rayos X disponible ahora mismo.</p>'; return; }
			el.innerHTML='<p class="font-mono text-3xl font-bold">'+xray+'</p>'
				+'<p class="mt-1 font-mono text-lg font-bold '+resultado.color+'">'+resultado.nivel+'</p>'
				+'<p class="mt-2 text-sm text-muted-foreground">'+resultado.desc+' — duración típica: '+resultado.duracion+'</p>';
		}).catch(function(e){ rauErr(e);
			el.innerHTML='<p class="text-sm text-muted-foreground">No se pudo cargar el dato actual.</p>';
		});
})();

/* ── RADIOFAROS NCDXF/IARU (lista real verificada contra 4 fuentes independientes) ── */
(function(){
	var relojEl=document.getElementById('faro-reloj');
	if(!relojEl) return;

	// [indicativo, ubicación, segundo de inicio en 20m dentro del ciclo de 180s]
	var FAROS=[
		['4U1UN','Nueva York, EE.UU.',0],
		['VE8AT','N. Canadá',10],
		['W6WX','California, EE.UU.',20],
		['KH6RS','Hawái, EE.UU.',30],
		['ZL6B','Nueva Zelanda',40],
		['VK6RBP','Perth, Australia',50],
		['JA2IGY','Japón',60],
		['RR9O','Rusia',70],
		['VR2B','Hong Kong',80],
		['4S7B','Sri Lanka',90],
		['ZS6DN','Sudáfrica',100],
		['5Z4B','Kenia',110],
		['4X6TU','Israel',120],
		['OH2B','Finlandia',130],
		['CS3B','Madeira',140],
		['LU4AA','Argentina',150],
		['OA4B','Perú',160],
		['YV5B','Venezuela',170],
	];
	// Cada banda siguiente empieza 10s después que la anterior en la secuencia global
	var BANDAS=[
		{nombre:'20m',freq:'14.100',offset:0},
		{nombre:'17m',freq:'18.110',offset:10},
		{nombre:'15m',freq:'21.150',offset:20},
		{nombre:'12m',freq:'24.930',offset:30},
		{nombre:'10m',freq:'28.200',offset:40},
	];

	function faroEnPosicion(pos){
		// pos = segundo dentro del ciclo de 180s (ya ajustado por el offset de banda)
		for(var i=0;i<FAROS.length;i++){
			if(pos>=FAROS[i][2] && pos<FAROS[i][2]+10) return FAROS[i];
		}
		return null;
	}

	function actualizar(){
		var ahora=new Date();
		var segCiclo=(ahora.getUTCMinutes()*60+ahora.getUTCSeconds()) % 180;
		relojEl.textContent=Math.floor(segCiclo/60)+':'+String(segCiclo%60).padStart(2,'0')+' / 3:00';

		document.getElementById('faro-bandas').innerHTML=BANDAS.map(function(b){
			var pos=(segCiclo - b.offset + 180) % 180;
			var faro=faroEnPosicion(pos);
			return '<div class="rounded-lg border border-border/50 bg-background p-3 text-center">'
				+'<p class="font-mono text-[10px] uppercase text-muted-foreground">'+b.nombre+' · '+b.freq+'</p>'
				+'<p class="mt-1 font-mono text-sm font-bold text-primary">'+(faro?faro[0]:'—')+'</p>'
				+'<p class="text-[10px] text-muted-foreground">'+(faro?faro[1]:'')+'</p>'
				+'</div>';
		}).join('');

		// Próximos 4 en 20m (maneja correctamente el caso de fin de ciclo)
		var idx=FAROS.findIndex(function(f){ return f[2] > segCiclo; });
		if(idx===-1) idx=0;
		var proximos=[];
		for(var i=0;i<4;i++){ proximos.push(FAROS[(idx+i) % FAROS.length]); }
		document.getElementById('faro-proximos').innerHTML=proximos.map(function(f){
			var faltan=((f[2]-segCiclo)+180)%180;
			return '<div class="flex justify-between border-b border-border/30 py-1 last:border-0"><span>'+f[0]+' — '+f[1]+'</span><span class="text-muted-foreground">en '+faltan+'s</span></div>';
		}).join('');
	}
	actualizar();
	setInterval(actualizar, 1000);
})();

/* ── PLAN DE BANDAS VISUAL (reutiliza el mismo array $bandas de la tabla, sin duplicar datos) ── */
(function(){
	var hfEl=document.getElementById('pb-hf');
	if(!hfEl) return;
	var BANDAS=<?php echo wp_json_encode( $bandas ?? [] ); ?>;

	var COLORES=['#1fd5f9','#34d399','#fbbf24','#fb7185','#a78bfa','#f472b6','#60a5fa','#facc15'];

	function parseRango(rango){
		var partes=rango.replace(/\s/g,'').split('–');
		return { min:parseFloat(partes[0]), max:parseFloat(partes[1]) };
	}

	function dibujarGrupo(el, bandasGrupo, escalaMin, escalaMax){
		var totalMhz=escalaMax-escalaMin;
		el.style.position='relative';
		el.innerHTML=bandasGrupo.map(function(b, i){
			var r=parseRango(b[1]);
			var izq=((r.min-escalaMin)/totalMhz)*100;
			var ancho=Math.max(0.8,((r.max-r.min)/totalMhz)*100);
			var color=COLORES[i%COLORES.length];
			return '<div class="pb-bloque" data-nombre="'+b[0]+'" data-rango="'+b[1]+' MHz" data-modos="'+b[2]+'" data-grupo="'+b[3]+'" data-atribucion="'+b[4]+'" data-lic="'+b[5]+'" data-novicio="'+b[6]+'" '
				+'style="position:absolute;left:'+izq+'%;width:'+ancho+'%;top:0;bottom:0;background:'+color+';border-radius:4px;cursor:pointer;opacity:0.85;" '
				+'title="'+b[0]+'"></div>';
		}).join('');
	}

	var hf=BANDAS.filter(function(b){ return parseRango(b[1]).min < 30; });
	var vhf=BANDAS.filter(function(b){ return parseRango(b[1]).min >= 30; });

	dibujarGrupo(document.getElementById('pb-hf'), hf, 1.8, 30);
	dibujarGrupo(document.getElementById('pb-vhf'), vhf, 50, 1300);

	document.querySelectorAll('.pb-bloque').forEach(function(bloque){
		bloque.addEventListener('click', function(){
			var tt=document.getElementById('pb-tooltip');
			tt.classList.remove('hidden');
			tt.innerHTML='<p class="font-mono text-lg font-bold text-primary">'+this.dataset.nombre+' — '+this.dataset.rango+'</p>'
				+'<p class="text-muted-foreground">Modos: '+this.dataset.modos+' · Grupo de potencia: '+this.dataset.grupo+' · Atribución: '+this.dataset.atribucion+'</p>'
				+'<p class="text-muted-foreground">Licencia: '+this.dataset.lic+' · Novicio (A): '+this.dataset.novicio+'</p>';
		});
	});
})();

/* ── BRANDMEISTER LAST HEARD (arma el link con la búsqueda puesta) ── */
(function(){
	var input=document.getElementById('bm-buscar');
	if(!input) return;
	var link=document.getElementById('bm-link');
	input.addEventListener('input', function(){
		var q=this.value.trim();
		link.href = q ? 'https://brandmeister.network/?page=lh&q='+encodeURIComponent(q) : 'https://brandmeister.network/?page=lh';
	});
})();

/* ── SMITH CHART ── */
(function(){
	var rInput=document.getElementById('smith-r');
	if(!rInput) return;
	var svg=document.getElementById('smith-svg');
	var CX=150, CY=150, RADIO=120;

	function dibujarFondo(){
		var html='';
		// Círculo unitario
		html+='<circle cx="'+CX+'" cy="'+CY+'" r="'+RADIO+'" fill="none" stroke="hsl(var(--border))" stroke-width="1.5"></circle>';
		// Eje real
		html+='<line x1="'+(CX-RADIO)+'" y1="'+CY+'" x2="'+(CX+RADIO)+'" y2="'+CY+'" stroke="hsl(var(--border))" stroke-width="1"></line>';
		// Círculos de ROE constante (1.5, 2, 3, 5) — son círculos concéntricos en el plano de Γ
		[1.5,2,3,5].forEach(function(vswr){
			var gamma=(vswr-1)/(vswr+1);
			html+='<circle cx="'+CX+'" cy="'+CY+'" r="'+(gamma*RADIO)+'" fill="none" stroke="hsl(var(--border)/.4)" stroke-width="0.75" stroke-dasharray="3 3"></circle>';
			html+='<text x="'+(CX+gamma*RADIO+2)+'" y="'+(CY-2)+'" font-size="8" fill="hsl(var(--muted-foreground))">'+vswr+'</text>';
		});
		html+='<text x="'+(CX+RADIO+4)+'" y="'+(CY+4)+'" font-size="9" fill="hsl(var(--muted-foreground))" font-family="monospace">Z=∞</text>';
		html+='<text x="'+(CX-RADIO-4)+'" y="'+(CY+4)+'" text-anchor="end" font-size="9" fill="hsl(var(--muted-foreground))" font-family="monospace">Z=0</text>';
		return html;
	}

	function actualizar(){
		// R vacío no es un cortocircuito: con ||0 la ROE salía ∞:1. X vacío sí es 0 (carga resistiva).
		var R=parseFloat(rInput.value);
		var X=parseFloat(document.getElementById('smith-x').value)||0; /* 0 legítimo */
		var Z0=parseFloat(document.getElementById('smith-z0').value)||50;
		if(isNaN(R)){ svg.innerHTML=dibujarFondo(); document.getElementById('smith-resultado').innerHTML='<p class="text-muted-foreground">Indica la resistencia R.</p>'; return; }

		// Impedancia normalizada z = (R+jX)/Z0, luego Γ = (z-1)/(z+1) (aritmética compleja)
		var zr=R/Z0, zi=X/Z0;
		var numR=zr-1, numI=zi;
		var denR=zr+1, denI=zi;
		var denMag2=denR*denR+denI*denI;
		var gammaR=(numR*denR+numI*denI)/denMag2;
		var gammaI=(numI*denR-numR*denI)/denMag2;

		var gammaMag=Math.sqrt(gammaR*gammaR+gammaI*gammaI);
		var gammaFase=Math.atan2(gammaI,gammaR)*180/Math.PI;
		var vswr = gammaMag<1 ? (1+gammaMag)/(1-gammaMag) : Infinity;
		var rl = gammaMag>0 ? -20*Math.log10(gammaMag) : Infinity;

		var px=CX+gammaR*RADIO, py=CY-gammaI*RADIO; // -gammaI: eje Y de SVG invertido

		svg.innerHTML=dibujarFondo()
			+'<line x1="'+CX+'" y1="'+CY+'" x2="'+px+'" y2="'+py+'" stroke="hsl(var(--primary))" stroke-width="1.5" stroke-dasharray="2 2"></line>'
			+'<circle cx="'+px+'" cy="'+py+'" r="6" fill="hsl(var(--primary))" stroke="white" stroke-width="1.5"></circle>';

		document.getElementById('smith-resultado').innerHTML=
			'<div class="flex justify-between"><span class="text-muted-foreground">|Γ|</span><span class="font-mono">'+gammaMag.toFixed(3)+'</span></div>'
			+'<div class="flex justify-between"><span class="text-muted-foreground">Fase de Γ</span><span class="font-mono">'+gammaFase.toFixed(1)+'°</span></div>'
			+'<div class="flex justify-between"><span class="text-muted-foreground">ROE</span><span class="font-mono font-bold text-primary">'+(isFinite(vswr)?vswr.toFixed(2):'∞')+':1</span></div>'
			+'<div class="flex justify-between"><span class="text-muted-foreground">Pérdida de retorno</span><span class="font-mono">'+(isFinite(rl)?rl.toFixed(1)+' dB':'∞')+'</span></div>';
	}
	['smith-r','smith-x','smith-z0'].forEach(function(id){ document.getElementById(id).addEventListener('input', actualizar); });
	actualizar();
})();

/* ── PSK REPORTER (consulta real vía proxy propio) ── */
(function(){
	var btn=document.getElementById('psk-buscar');
	if(!btn) return;
	function buscar(){
		var ind=document.getElementById('psk-indicativo').value.trim().toUpperCase();
		if(!ind) return;
		var el=document.getElementById('psk-resultado');
		el.innerHTML='<p class="text-sm text-muted-foreground">Consultando PSK Reporter (puede tardar hasta 1 minuto)…</p>';
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_pskreporter&indicativo='+encodeURIComponent(ind), {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(!json.success){ el.innerHTML='<p class="text-sm text-rose-400">'+(json.data||'No se pudo consultar.')+'</p>'; return; }
				if(!json.data.length){ el.innerHTML='<p class="text-sm text-muted-foreground">Nadie ha reportado escucharte en los últimos 30 minutos. Intenta transmitir FT8 y consulta de nuevo.</p>'; return; }
				el.innerHTML='<div class="space-y-1.5">'+json.data.map(function(r){
					return '<div class="flex items-center justify-between border-b border-border/30 py-1.5 last:border-0 text-sm">'
						+'<span><strong class="font-mono text-primary">'+r.receptor+'</strong> <span class="text-muted-foreground font-mono text-xs">'+(r.grid||'')+'</span></span>'
						+'<span class="font-mono text-xs text-muted-foreground">'+r.freq+' MHz · '+r.modo+' · SNR '+r.snr+'dB · hace '+Math.round(r.hace_seg/60)+'min</span>'
						+'</div>';
				}).join('')+'</div>';
			}).catch(function(e){ rauErr(e); el.innerHTML='<p class="text-sm text-rose-400">Error de conexión.</p>'; });
	}
	btn.addEventListener('click', buscar);
	document.getElementById('psk-indicativo').addEventListener('keydown', function(e){ if(e.key==='Enter') buscar(); });
})();

/* ── REVERSE BEACON NETWORK (arma el link con el indicativo puesto) ── */
(function(){
	var input=document.getElementById('rbn-indicativo');
	if(!input) return;
	var link=document.getElementById('rbn-link');
	input.addEventListener('input', function(){
		var ind=this.value.trim().toUpperCase();
		link.href = ind ? 'https://www.reversebeacon.net/main.php?callFilter='+encodeURIComponent(ind) : 'https://www.reversebeacon.net/main.php';
	});
})();

/* ── RADIALES DE TIERRA ── */
(function(){
	var input=document.getElementById('rad-freq');
	if(!input) return;
	function calc(){
		var f=parseFloat(input.value)||14.15;
		var wl=299.792458/f;
		document.getElementById('rad-longitud').textContent=(wl*0.25).toFixed(2)+' m';
	}
	input.addEventListener('input', calc);
	calc();
})();

/* ── AYUDANTE DE SPLIT ── */
(function(){
	var freqEl=document.getElementById('split-freq');
	if(!freqEl) return;
	function calc(){
		var freq=parseFloat(freqEl.value);
		var offset=parseFloat(document.getElementById('split-offset').value)||0; /* 0 legítimo: sin split */
		if(isNaN(freq)){ document.getElementById('split-rx').textContent='—'; document.getElementById('split-tx').textContent='—'; return; }
		document.getElementById('split-rx').textContent=freq.toFixed(1)+' kHz';
		document.getElementById('split-tx').textContent=(freq+offset).toFixed(1)+' kHz';
	}
	freqEl.addEventListener('input', calc);
	document.getElementById('split-offset').addEventListener('input', calc);
	calc();
})();

/* ── CONTADOR DE TASA DE QSO ── */
(function(){
	var addBtn=document.getElementById('qso-add');
	if(!addBtn) return;
	var total=0, inicio=null, intervalo=null;

	function actualizarTiempo(){
		if(!inicio) return;
		var seg=Math.floor((Date.now()-inicio)/1000);
		var m=Math.floor(seg/60), s=seg%60;
		document.getElementById('qso-tiempo').textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
		var horas=seg/3600;
		document.getElementById('qso-rate').textContent=horas>0 ? Math.round(total/horas) : 0;
	}

	document.getElementById('qso-start').addEventListener('click', function(){
		if(!inicio){
			inicio=Date.now();
			intervalo=setInterval(actualizarTiempo, 1000);
			this.textContent='Pausar';
		} else {
			clearInterval(intervalo);
			inicio=null;
			this.textContent='Reanudar';
		}
	});
	addBtn.addEventListener('click', function(){
		total++;
		document.getElementById('qso-total').textContent=total;
		actualizarTiempo();
	});
	document.getElementById('qso-reset').addEventListener('click', function(){
		total=0; inicio=null; clearInterval(intervalo);
		document.getElementById('qso-total').textContent='0';
		document.getElementById('qso-tiempo').textContent='00:00';
		document.getElementById('qso-rate').textContent='0';
		document.getElementById('qso-start').textContent='Iniciar';
	});
})();

/* ── RASTREADOR DXCC/WAS (localStorage, sin cuenta) ── */
(function(){
	var dxccTrab=document.getElementById('dxcc-trabajadas');
	if(!dxccTrab) return;

	function cargar(){
		try {
			var datos=JSON.parse(localStorage.getItem('rau_dxcc_was')||'{}');
			if(datos.dxccTrab!==undefined) document.getElementById('dxcc-trabajadas').value=datos.dxccTrab;
			if(datos.dxccConf!==undefined) document.getElementById('dxcc-confirmadas').value=datos.dxccConf;
			if(datos.wasTrab!==undefined) document.getElementById('was-trabajados').value=datos.wasTrab;
			if(datos.wasConf!==undefined) document.getElementById('was-confirmados').value=datos.wasConf;
		} catch(e){}
	}
	function guardar(){
		try {
			localStorage.setItem('rau_dxcc_was', JSON.stringify({
				dxccTrab: document.getElementById('dxcc-trabajadas').value,
				dxccConf: document.getElementById('dxcc-confirmadas').value,
				wasTrab: document.getElementById('was-trabajados').value,
				wasConf: document.getElementById('was-confirmados').value,
			}));
		} catch(e){}
	}
	function actualizar(){
		var dTrab=parseInt(document.getElementById('dxcc-trabajadas').value)||0; /* 0 legítimo: ninguna */
		var dConf=parseInt(document.getElementById('dxcc-confirmadas').value)||0; /* 0 legítimo: ninguna */
		document.getElementById('dxcc-barra').style.width=Math.min(100,(dConf/340*100)).toFixed(0)+'%';
		var faltanHonor=331-dConf;
		document.getElementById('dxcc-texto').textContent = dConf>=331
			? '¡Ya alcanzaste Honor Roll! ('+dConf+'/340)'
			: dConf+'/340 confirmadas · faltan '+faltanHonor+' para Honor Roll (331)';

		var wTrab=parseInt(document.getElementById('was-trabajados').value)||0; /* 0 legítimo: ninguna */
		var wConf=parseInt(document.getElementById('was-confirmados').value)||0; /* 0 legítimo: ninguna */
		document.getElementById('was-barra').style.width=Math.min(100,(wConf/50*100)).toFixed(0)+'%';
		document.getElementById('was-texto').textContent = wConf>=50 ? '¡WAS completo!' : wConf+'/50 estados confirmados';

		guardar();
	}
	['dxcc-trabajadas','dxcc-confirmadas','was-trabajados','was-confirmados'].forEach(function(id){
		document.getElementById(id).addEventListener('input', actualizar);
	});
	cargar();
	actualizar();
})();

/* ── VALIDADOR DE LOG CABRILLO ── */
(function(){
	var btn=document.getElementById('cab-validar');
	if(!btn) return;

	var MODOS_VALIDOS=['CW','PH','FM','RY','DG'];

	function validarCabrillo(texto){
		var errores=[], avisos=[];
		var lineas=texto.split('\n').map(function(l){ return l.replace(/\r$/,''); }).filter(function(l){ return l.trim()!==''; });

		if(!lineas.length){ errores.push('El log está vacío.'); return {errores:errores, avisos:avisos, qsos:0}; }

		if(!/^START-OF-LOG:\s*[23]\.0/i.test(lineas[0])){
			errores.push('La primera línea debe ser "START-OF-LOG: 3.0" (o 2.0) — encontré: "'+lineas[0]+'"');
		}
		var ultima=lineas[lineas.length-1];
		if(!/^END-OF-LOG:/i.test(ultima)){
			errores.push('La última línea debe ser "END-OF-LOG:" — encontré: "'+ultima+'"');
		}

		var tags={};
		lineas.forEach(function(l){
			var m=l.match(/^([A-Z0-9-]+):\s*(.*)$/i);
			if(m) tags[m[1].toUpperCase()]=(tags[m[1].toUpperCase()]||[]).concat(m[2]);
		});

		if(!tags['CALLSIGN'] || !tags['CALLSIGN'][0].trim()) errores.push('Falta la etiqueta CALLSIGN: (tu indicativo usado en el concurso).');
		if(!tags['CONTEST'] || !tags['CONTEST'][0].trim()) errores.push('Falta la etiqueta CONTEST: (nombre del concurso, ej. CQ-WW-SSB).');
		if(!tags['CATEGORY-OPERATOR']) avisos.push('No encontré CATEGORY-OPERATOR: — revisa si el organizador la exige.');
		if(!tags['CLAIMED-SCORE']) avisos.push('No encontré CLAIMED-SCORE: — muchos organizadores lo piden aunque sea opcional en algunos concursos.');

		var qsoLineas=lineas.filter(function(l){ return /^QSO:/i.test(l); });
		var fechaAnterior=null;
		qsoLineas.forEach(function(l, idx){
			var partes=l.replace(/^QSO:\s*/i,'').trim().split(/\s+/);
			var n=idx+1;
			if(partes.length<8){
				errores.push('Línea QSO #'+n+' tiene muy pocos campos ('+partes.length+'): "'+l+'"');
				return;
			}
			var freq=partes[0], modo=partes[1], fecha=partes[2], hora=partes[3];

			if(!/^\d+$/.test(freq)) avisos.push('QSO #'+n+': la frecuencia "'+freq+'" no parece un número en kHz.');
			if(MODOS_VALIDOS.indexOf(modo.toUpperCase())===-1) avisos.push('QSO #'+n+': el modo "'+modo+'" no es uno de los estándar (CW, PH, FM, RY, DG).');
			if(!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) errores.push('QSO #'+n+': la fecha "'+fecha+'" no tiene el formato AAAA-MM-DD.');
			if(!/^\d{4}$/.test(hora)) errores.push('QSO #'+n+': la hora "'+hora+'" no tiene el formato HHMM (4 dígitos, 24h).');

			if(/^\d{4}-\d{2}-\d{2}$/.test(fecha) && /^\d{4}$/.test(hora)){
				var actual=fecha+hora;
				if(fechaAnterior && actual<fechaAnterior){
					avisos.push('QSO #'+n+': esta línea tiene fecha/hora anterior a la #'+n+' — el log no está en orden cronológico (algunos robots lo exigen).');
				}
				fechaAnterior=actual;
			}
		});

		if(!qsoLineas.length) errores.push('No encontré ninguna línea QSO: en el log.');

		return {errores:errores, avisos:avisos, qsos:qsoLineas.length};
	}

	btn.addEventListener('click', function(){
		var texto=document.getElementById('cab-input').value;
		var r=validarCabrillo(texto);
		var el=document.getElementById('cab-resultado');
		el.classList.remove('hidden');

		var html='';
		html+='<div class="mb-4 grid grid-cols-3 gap-3 text-center">'
			+'<div class="rounded-lg border border-border/50 p-3"><p class="font-mono text-2xl font-bold">'+r.qsos+'</p><p class="text-xs text-muted-foreground">QSOs encontrados</p></div>'
			+'<div class="rounded-lg border p-3 '+(r.errores.length?'border-rose-400/30 bg-rose-400/5':'border-emerald-400/30 bg-emerald-400/5')+'"><p class="font-mono text-2xl font-bold '+(r.errores.length?'text-rose-400':'text-emerald-400')+'">'+r.errores.length+'</p><p class="text-xs text-muted-foreground">Errores</p></div>'
			+'<div class="rounded-lg border '+(r.avisos.length?'border-amber-400/30 bg-amber-400/5':'border-border/50')+' p-3"><p class="font-mono text-2xl font-bold '+(r.avisos.length?'text-amber-400':'')+'">'+r.avisos.length+'</p><p class="text-xs text-muted-foreground">Avisos</p></div>'
			+'</div>';

		if(!r.errores.length && !r.avisos.length){
			html+='<div class="rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-4 text-sm text-emerald-400">✅ No encontré problemas de formato. Recuerda: esto no reemplaza la validación oficial del robot del concurso.</div>';
		} else {
			if(r.errores.length){
				html+='<p class="mb-2 font-mono text-xs font-bold uppercase text-rose-400">Errores (probablemente el robot va a rechazar el log)</p><ul class="mb-4 space-y-1 text-sm">';
				r.errores.forEach(function(e){ html+='<li class="text-rose-400">✗ '+e+'</li>'; });
				html+='</ul>';
			}
			if(r.avisos.length){
				html+='<p class="mb-2 font-mono text-xs font-bold uppercase text-amber-400">Avisos (revisa, pero puede que estén bien)</p><ul class="space-y-1 text-sm">';
				r.avisos.forEach(function(a){ html+='<li class="text-amber-400">⚠ '+a+'</li>'; });
				html+='</ul>';
			}
		}
		el.innerHTML=html;
	});
})();

/* ── FASE LUNAR (referencia para EME) ── */
(function(){
	var nombreEl=document.getElementById('luna-nombre');
	if(!nombreEl) return;
	var SINODICO=29.530588853;
	var NUEVA_REF=Date.UTC(2000,0,6,18,14); // luna nueva de referencia conocida

	var diasDesde=(Date.now()-NUEVA_REF)/86400000;
	var fase=(diasDesde % SINODICO + SINODICO) % SINODICO;
	var frac=fase/SINODICO;
	var iluminacion=Math.round((1-Math.cos(frac*2*Math.PI))/2*100);

	var _f=rauFaseLunar(frac), nombre=_f.nombre, icono=_f.icono;

	document.getElementById('luna-icono').textContent=icono;
	nombreEl.textContent=nombre;
	document.getElementById('luna-iluminacion').textContent=iluminacion+'% iluminada';
	document.getElementById('luna-edad').textContent=fase.toFixed(1)+' días desde luna nueva (ciclo de '+SINODICO.toFixed(1)+' días)';
})();

/* ── GLOSARIO DE TÉRMINOS ── */
(function(){
	var listaEl=document.getElementById('glo-lista');
	if(!listaEl) return;
	var TERMINOS=[
		['QSO','Un contacto radial entre dos estaciones.'],
		['QTH','Tu ubicación / dirección.'],
		['QRZ','"¿Quién me llama?" — también el nombre de la base de datos de indicativos más usada.'],
		['Indicativo','Identificador único asignado a cada radioaficionado (ej. HK4RAU).'],
		['Repetidor','Estación que recibe en una frecuencia y retransmite en otra, ampliando el alcance.'],
		['Simplex','Comunicación directa entre dos radios, sin repetidor.'],
		['CTCSS','Tono sub-audible que abre un repetidor (evita interferencia de otros usuarios).'],
		['DX','Contacto a larga distancia.'],
		['Malla','Red de estaciones que se reúne en una frecuencia y horario fijo.'],
		['Net Control','Quien coordina y modera una malla o red.'],
		['Grid Square / Locator','Sistema Maidenhead para indicar tu ubicación aproximada (ej. FJ26ff).'],
		['ROE / VSWR','Relación de Onda Estacionaria — qué tan bien está acoplada tu antena.'],
		['Propagación','Cómo viajan las ondas de radio según la ionosfera y el clima espacial.'],
		['SFI','Índice de Flujo Solar — a mayor número, generalmente mejor propagación en HF.'],
		['Índice K','Mide actividad geomagnética — a mayor número, peor la propagación (más perturbada).'],
		['Banda','Rango de frecuencias asignado para un uso específico (ej. 2 metros, 40 metros).'],
		['Handie / HT','Radio portátil de mano.'],
		['Base','Radio instalado de forma fija, normalmente con más potencia que un HT.'],
		['Móvil','Radio instalado en un vehículo.'],
		['QSL','Confirmación de un contacto, tradicionalmente por tarjeta postal.'],
		['Field Day','Actividad de operación en exteriores, típicamente para practicar en condiciones de emergencia.'],
		['POTA','"Parks on the Air" — programa de activación de parques.'],
		['Elmer','Radioaficionado experimentado que guía y enseña a los nuevos.'],
		['73','Despedida cordial en radioafición ("saludos").'],
		['EME','"Earth-Moon-Earth" — comunicación rebotando la señal en la Luna.'],
	];
	function render(filtro){
		var f=(filtro||'').toLowerCase();
		var items=TERMINOS.filter(function(t){ return !f || t[0].toLowerCase().indexOf(f)>-1 || t[1].toLowerCase().indexOf(f)>-1; });
		listaEl.innerHTML=items.map(function(t){
			return '<div class="rounded-lg border border-border/70 bg-background p-4"><p class="font-mono text-sm font-bold text-primary">'+t[0]+'</p><p class="mt-1 text-sm text-muted-foreground">'+t[1]+'</p></div>';
		}).join('') || '<p class="col-span-2 text-sm text-muted-foreground">Sin resultados.</p>';
	}
	render();
	document.getElementById('glo-buscar').addEventListener('input', function(){ render(this.value); });
})();

/* ── CÓDIGO DE COLORES (resistencias + capacitores) ── */
(function(){
	var d1=document.getElementById('col-d1');
	if(!d1) return;

	var COLORES=[
		{n:'Negro',    v:0, mult:1,          hex:'#1a1a1a'},
		{n:'Marrón',   v:1, mult:10,         hex:'#8b5a2b', tol:1},
		{n:'Rojo',     v:2, mult:100,        hex:'#dc2626', tol:2},
		{n:'Naranja',  v:3, mult:1000,       hex:'#ea580c'},
		{n:'Amarillo', v:4, mult:10000,      hex:'#eab308'},
		{n:'Verde',    v:5, mult:100000,     hex:'#16a34a', tol:0.5},
		{n:'Azul',     v:6, mult:1000000,    hex:'#2563eb', tol:0.25},
		{n:'Violeta',  v:7, mult:10000000,   hex:'#7c3aed', tol:0.1},
		{n:'Gris',     v:8, mult:100000000,  hex:'#6b7280'},
		{n:'Blanco',   v:9, mult:1000000000, hex:'#f3f4f6'},
		{n:'Dorado',   v:null, mult:0.1,  hex:'#ca8a04', tol:5},
		{n:'Plateado', v:null, mult:0.01, hex:'#9ca3af', tol:10},
	];

	function fillSelect(sel, filtro){
		sel.innerHTML=COLORES.map(function(c,i){
			if(filtro && !filtro(c)) return '';
			return '<option value="'+i+'">'+c.n+'</option>';
		}).join('');
	}
	fillSelect(document.getElementById('col-d1'), function(c){ return c.v!==null; });
	fillSelect(document.getElementById('col-d2'), function(c){ return c.v!==null; });
	fillSelect(document.getElementById('col-d3'), function(c){ return c.v!==null; });
	fillSelect(document.getElementById('col-mult'), function(){ return true; });
	fillSelect(document.getElementById('col-tol'), function(c){ return c.tol!==undefined; });
	document.getElementById('col-d2').value=6; // negro/violeta por defecto solo ejemplo
	document.getElementById('col-mult').value=2;
	document.getElementById('col-tol').value=1;

	var bandas=4;
	document.querySelectorAll('.col-bands-btn').forEach(function(btn){
		btn.addEventListener('click', function(){
			bandas=parseInt(btn.dataset.bands);
			document.querySelectorAll('.col-bands-btn').forEach(function(b){
				b.className=b.className.replace('border-primary bg-primary/10 text-primary','border-border bg-background text-foreground');
			});
			btn.className=btn.className.replace('border-border bg-background text-foreground','border-primary bg-primary/10 text-primary');
			document.getElementById('col-d3-wrap').classList.toggle('hidden', bandas!==5);
			calcResistencia();
		});
	});

	function calcResistencia(){
		var i1=parseInt(document.getElementById('col-d1').value)||0; /* 0 legítimo: índice de <select> */
		var i2=parseInt(document.getElementById('col-d2').value)||0; /* 0 legítimo: índice de <select> */
		var iMult=parseInt(document.getElementById('col-mult').value)||0; /* 0 legítimo: índice de <select> */
		var iTol=parseInt(document.getElementById('col-tol').value)||0; /* 0 legítimo: índice de <select> */
		var digitos = bandas===5 ? (COLORES[i1].v*100 + COLORES[i2].v*10 + COLORES[parseInt(document.getElementById('col-d3').value)||0].v) : (COLORES[i1].v*10 + COLORES[i2].v); /* 0 legítimo: índice de <select> */
		var valor = digitos * COLORES[iMult].mult;
		var tol = COLORES[iTol] && COLORES[iTol].tol!==undefined ? COLORES[iTol].tol : 20;

		var texto, unidad;
		if(valor>=1000000){ texto=(valor/1000000).toFixed(2).replace(/\.?0+$/,''); unidad='MΩ'; }
		else if(valor>=1000){ texto=(valor/1000).toFixed(2).replace(/\.?0+$/,''); unidad='kΩ'; }
		else { texto=valor.toFixed(2).replace(/\.?0+$/,''); unidad='Ω'; }

		document.getElementById('col-result').textContent=texto+' '+unidad;
		document.getElementById('col-result-tol').textContent='± '+tol+'%';
	}
	document.querySelectorAll('.col-select').forEach(function(sel){ sel.addEventListener('change', calcResistencia); });
	calcResistencia();

	/* Capacitores */
	var capEl=document.getElementById('cap-code');
	function calcCapacitor(){
		var code=capEl.value.trim();
		if(!/^\d{2,4}$/.test(code)){ return; }
		var pf;
		if(code.length<=2){ pf=parseFloat(code); }
		else {
			var digitos=code.slice(0,-1);
			var mult=Math.pow(10, parseInt(code.slice(-1)));
			pf=parseFloat(digitos)*mult;
		}
		document.getElementById('cap-pf').textContent=pf.toLocaleString('es-CO');
		document.getElementById('cap-nf').textContent=(pf/1000).toLocaleString('es-CO',{maximumFractionDigits:4});
		document.getElementById('cap-uf').textContent=(pf/1000000).toLocaleString('es-CO',{maximumFractionDigits:6});
	}
	capEl.addEventListener('input', calcCapacitor);
	calcCapacitor();
})();

/* ── AUTONOMÍA DE BATERÍA ── */
(function(){
	var capEl=document.getElementById('bat-cap');
	if(!capEl) return;
	var txEl=document.getElementById('bat-tx'), rxEl=document.getElementById('bat-rx'),
		dutyEl=document.getElementById('bat-duty'), dutyVal=document.getElementById('bat-duty-val'),
		horasEl=document.getElementById('bat-horas'), promEl=document.getElementById('bat-promedio');

	function calc(){
		// Campo vacío = sin resultado. Con ||0 un consumo en RX borrado contaba como 0 mA y la
		// autonomía salía inflada: justo la cifra con la que se planea una emergencia.
		var cap=parseFloat(capEl.value), tx=parseFloat(txEl.value), rx=parseFloat(rxEl.value);
		var duty=parseFloat(dutyEl.value);
		dutyVal.textContent=duty+'%';
		if(isNaN(cap) || isNaN(tx) || isNaN(rx) || isNaN(duty)){ promEl.textContent='—'; horasEl.textContent='—'; return; }
		var promedio = (duty/100)*tx + (1-duty/100)*rx;
		var horas = promedio>0 ? (cap/promedio)*0.85 : 0;
		promEl.textContent=promedio.toFixed(0)+' mA';
		if(horas<=0){ horasEl.textContent='—'; return; }
		var h=Math.floor(horas), m=Math.round((horas-h)*60);
		horasEl.textContent=h+'h '+m+'m';
	}
	[capEl,txEl,rxEl,dutyEl].forEach(function(el){ el.addEventListener('input', calc); });
	calc();
})();

/* ── ÁNGULO DE RADIACIÓN (antena horizontal sobre suelo reflectante) ──
   El rayo directo y el reflejado en el suelo se suman o se cancelan según la diferencia de
   camino. Con la inversión de fase de 180° que impone la reflexión, los máximos quedan en
   sen θ = (2n−1)·λ/(4h). El primero (n=1) es el lóbulo principal, el que interesa para DX.
   Si h < λ/4 no hay solución: el máximo apunta al cenit y la antena trabaja en NVIS.
   Contrastado con la tabla de referencia clásica: λ/2 → 30°, 1λ → 14°, 2λ → 7°. */
(function(){
	var freqEl=document.getElementById('ang-freq');
	if(!freqEl) return;
	var altEl=document.getElementById('ang-altura'), altVal=document.getElementById('ang-altura-val'),
		princEl=document.getElementById('ang-principal'), lambdaEl=document.getElementById('ang-lambda'),
		enLambdasEl=document.getElementById('ang-enlambdas'), lobulosEl=document.getElementById('ang-lobulos'),
		lecturaEl=document.getElementById('ang-lectura');

	function calc(){
		var f=parseFloat(freqEl.value), h=parseFloat(altEl.value);
		if(isNaN(f)||f<=0||isNaN(h)||h<=0) return;
		altVal.textContent=h.toFixed(1)+' m';
		var wl=299.792458/f;
		var enLambdas=h/wl;
		lambdaEl.textContent=wl.toFixed(2)+' m';
		enLambdasEl.textContent=enLambdas.toFixed(2)+' λ';

		var seno=wl/(4*h);
		if(seno>=1){
			// Por debajo de λ/4 el lóbulo apunta directo hacia arriba
			princEl.textContent='90°';
			lobulosEl.textContent='ninguno';
			lecturaEl.className='rounded-lg border border-amber-400/30 bg-amber-400/5 px-4 py-3 text-sm text-amber-400';
			lecturaEl.textContent='Por debajo de λ/4 de altura ('+(wl/4).toFixed(1)+' m en esta banda) toda la señal sale casi vertical. Es NVIS: excelente para cubrir Colombia en 40m/80m sin puntos ciegos, inútil para DX lejano.';
			return;
		}
		var principal=Math.asin(seno)*180/Math.PI;
		princEl.textContent=principal.toFixed(0)+'°';

		// Lóbulos superiores: sen θ = 3λ/4h, 5λ/4h, … mientras el seno siga siendo válido
		var extras=[];
		for(var n=2; n<=6; n++){
			var s=(2*n-1)*wl/(4*h);
			if(s>=1) break;
			extras.push(Math.round(Math.asin(s)*180/Math.PI)+'°');
		}
		lobulosEl.textContent=extras.length ? extras.join(' · ') : 'ninguno';

		if(principal<=15){
			lecturaEl.className='rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-4 py-3 text-sm text-emerald-400';
			lecturaEl.textContent='Ángulo bajo — es lo que quieres para DX de larga distancia. A esta altura la antena tira lejos y rasante.';
		} else if(principal<=30){
			lecturaEl.className='rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm text-primary';
			lecturaEl.textContent='Ángulo medio — buen equilibrio: sirve para DX de media distancia y para trabajar el continente. Es donde termina la mayoría de las antenas de patio.';
		} else {
			lecturaEl.className='rounded-lg border border-amber-400/30 bg-amber-400/5 px-4 py-3 text-sm text-amber-400';
			lecturaEl.textContent='Ángulo alto — cubre bien lo cercano (regional y nacional), pero el DX lejano te va a costar. Subirla a '+(wl/2).toFixed(1)+' m la llevaría a unos 30°.';
		}
	}
	freqEl.addEventListener('input', calc);
	altEl.addEventListener('input', calc);
	calc();
})();

/* ── AHORA MISMO, ¿QUÉ TRABAJO? ──────────────────────────────────────────────
   No es un predictor. Junta tres cosas que el sitio ya tiene y que hoy hay que leer por separado:
   las condiciones por banda de HamQSL, la hora solar del QTH (que decide si aplican las de día o
   las de noche) y los spots reales del cluster como comprobación de que algo se está trabajando.
   La regla de fondo es la clásica: de día las bandas altas, de noche las bajas, y en el paso del
   grayline las bajas se realzan. Todo lo que se afirma sale de un dato medido, no de un modelo. */
(function(){
	var cargando=document.getElementById('ahora-cargando');
	if(!cargando) return;
	var contenido=document.getElementById('ahora-contenido');

	// Orden de mayor a menor frecuencia: sirve para elegir "la banda más alta que esté abierta",
	// que es la que da más distancia con menos potencia cuando la ionosfera la soporta.
	var ORDEN=['10m-6m','12m-10m','17m-15m','30m-20m','40m-30m','80m-40m'];

	function calidad(txt){
		if(!txt || /^[\s—-]*$/.test(txt)) return null;      // sin dato no es lo mismo que malo
		if(/good/i.test(txt)) return 2;
		if(/fair/i.test(txt)) return 1;
		return 0;
	}
	var ETIQUETA={2:'Buena',1:'Regular',0:'Pobre'};
	var COLOR={2:'text-emerald-400',1:'text-amber-400',0:'text-rose-400'};

	// Amanecer y atardecer del QTH con la ecuación solar estándar (convención NOAA, la misma
	// corregida en la herramienta Grayline).
	function solEventos(fecha, lat, lng){
		var dia=Math.floor((fecha-new Date(fecha.getFullYear(),0,0))/86400000);
		var g=2*Math.PI/365*(dia-1);
		var eq=229.18*(0.000075+0.001868*Math.cos(g)-0.032077*Math.sin(g)-0.014615*Math.cos(2*g)-0.040849*Math.sin(2*g));
		var d=0.006918-0.399912*Math.cos(g)+0.070257*Math.sin(g)-0.006758*Math.cos(2*g)+0.000907*Math.sin(2*g)-0.002697*Math.cos(3*g)+0.00148*Math.sin(3*g);
		var la=lat*Math.PI/180;
		var cosH=(Math.cos(90.833*Math.PI/180)/(Math.cos(la)*Math.cos(d)))-Math.tan(la)*Math.tan(d);
		if(cosH>1||cosH<-1) return null;                    // sol siempre bajo o siempre alto
		var ha=Math.acos(cosH)*180/Math.PI;
		return { salida:720-4*(lng+ha)-eq, puesta:720-4*(lng-ha)-eq };   // minutos UTC
	}

	function textoRestante(min){
		min=Math.round(min);
		if(min<60) return min+' min';
		var h=Math.floor(min/60), m=min%60;
		return h+' h'+(m?' '+m+' min':'');
	}

	function pintar(solar, spots){
		var qth=rauQth(), lat=qth.lat, lng=qth.lng;
		var ahora=new Date();
		var minUTC=ahora.getUTCHours()*60+ahora.getUTCMinutes();
		var ev=solEventos(ahora, lat, lng);

		var esDia=true, cambio='', momento='Día';
		if(ev){
			var sal=((ev.salida%1440)+1440)%1440, pue=((ev.puesta%1440)+1440)%1440;
			esDia = sal<pue ? (minUTC>=sal && minUTC<pue) : (minUTC>=sal || minUTC<pue);
			var faltan = esDia ? (pue-minUTC+1440)%1440 : (sal-minUTC+1440)%1440;
			momento = esDia ? 'Día' : 'Noche';
			cambio = esDia ? 'Atardece en '+textoRestante(faltan) : 'Amanece en '+textoRestante(faltan);
			// El grayline es la franja de en torno a una hora alrededor del orto y el ocaso. Hay que
			// medir la distancia circular a CADA evento por separado y quedarse con la menor: usar
			// min() sobre las distancias "hacia adelante" hace que seis minutos DESPUÉS del
			// amanecer se lean como 1434 minutos y el grayline pase desapercibido.
			var distSal=Math.min((sal-minUTC+1440)%1440, (minUTC-sal+1440)%1440);
			var distPue=Math.min((pue-minUTC+1440)%1440, (minUTC-pue+1440)%1440);
			var enGrayline=Math.min(distSal, distPue)<60;
		}
		document.getElementById('ahora-momento').textContent=momento;
		document.getElementById('ahora-cambio').textContent=cambio;

		// Condiciones por banda para el periodo que toca ahora
		var periodo = esDia ? 'day' : 'night';
		var mapa={};
		(solar.bands||[]).forEach(function(b){ mapa[b.name]=calidad(b[periodo]); });

		var cont=document.getElementById('ahora-bandas');
		cont.innerHTML=ORDEN.filter(function(n){ return n in mapa; }).map(function(n){
			var q=mapa[n];
			return '<div class="flex items-center justify-between rounded border border-border/50 px-2 py-1">'
				+'<span class="text-muted-foreground">'+n+'</span>'
				+'<span class="'+(q===null?'text-muted-foreground':COLOR[q])+'">'+(q===null?'sin dato':ETIQUETA[q])+'</span></div>';
		}).join('');

		// La mejor apuesta: la banda MÁS ALTA con la mejor calificación disponible
		var mejor=null;
		[2,1].forEach(function(nivel){
			if(mejor) return;
			for(var i=0;i<ORDEN.length;i++){ if(mapa[ORDEN[i]]===nivel){ mejor={banda:ORDEN[i], q:nivel}; return; } }
		});

		var k=parseInt(solar.kindex,10);
		var geoEl=document.getElementById('ahora-geo'), geoNota=document.getElementById('ahora-geo-nota');
		if(isNaN(k)){
			geoEl.textContent='sin dato'; geoEl.className='mt-1 font-mono text-lg font-bold text-muted-foreground';
			geoNota.textContent='HamQSL no reportó el índice K';
		} else {
			geoEl.textContent='K='+k;
			geoEl.className='mt-1 font-mono text-lg font-bold '+(k<=3?'text-emerald-400':k===4?'text-amber-400':'text-rose-400');
			geoNota.textContent = k<=3 ? 'Campo tranquilo' : k===4 ? 'Perturbado; las rutas polares sufren primero'
				: 'Tormenta: HF degradado, las bandas bajas aguantan mejor';
		}

		// Comprobación con la realidad: ¿hay spots en la banda recomendada?
		var RANGO={'80m-40m':[3.5,7.3],'40m-30m':[7,10.15],'30m-20m':[10.1,14.35],
		           '17m-15m':[18,21.45],'12m-10m':[24.8,29.7],'10m-6m':[28,54]};
		var enBanda=0;
		if(mejor && RANGO[mejor.banda] && spots){
			enBanda=spots.filter(function(s){
				var f=parseFloat(s.f); return f>=RANGO[mejor.banda][0] && f<=RANGO[mejor.banda][1];
			}).length;
		}
		document.getElementById('ahora-actividad').textContent = spots ? enBanda+' spot'+(enBanda===1?'':'s') : 'sin datos';
		document.getElementById('ahora-actividad-nota').textContent = spots
			? (enBanda ? 'reportados en esa banda ahora mismo' : 'nadie reporta en esa banda todavía')
			: 'el cluster no respondió';

		var titular=document.getElementById('ahora-titular');
		var bandaEl=document.getElementById('ahora-banda'), razonEl=document.getElementById('ahora-razon');
		if(!mejor){
			titular.className='rounded-xl border border-rose-400/40 bg-rose-400/5 p-6 text-rose-400';
			bandaEl.textContent='Ninguna';
			razonEl.textContent='Ninguna banda aparece como buena ni regular en este momento. Es buen rato para trabajar satélites o la repetidora local.';
		} else {
			var bueno = mejor.q===2;
			titular.className='rounded-xl border p-6 '+(bueno?'border-emerald-400/40 bg-emerald-400/5 text-emerald-400':'border-amber-400/40 bg-amber-400/5 text-amber-400');
			bandaEl.textContent=mejor.banda;
			var razon='Es la banda más alta con propagación '+ETIQUETA[mejor.q].toLowerCase()+' de '+(esDia?'día':'noche')+'.';
			if(enBanda) razon+=' Además hay '+enBanda+' spot'+(enBanda===1?'':'s')+' reciente'+(enBanda===1?'':'s')+' ahí, así que no es solo teoría.';
			else razon+=' Aún no hay spots ahí: puede estar abierta y desierta, que es cuando conviene llamar CQ.';
			if(!isNaN(k) && k>=5) razon+=' Ojo: con K='+k+' las rutas hacia el norte pueden estar cerradas aunque la banda figure abierta.';
			var oidoAhora=window.rauOido, oidas=0;
			if(oidoAhora) mejor.banda.split('-').forEach(function(b){ if(oidoAhora.bandas[b]) oidas+=oidoAhora.bandas[b].n; });
			if(oidas) razon+=' El receptor del club oyó '+oidas+' estaciones en esa banda en la última media hora.';
			razonEl.textContent=razon;
		}
		var FT8_AHORA={'80m-40m':7074,'40m-30m':10136,'30m-20m':14074,'17m-15m':21074,'12m-10m':28074,'10m-6m':28074};
		var escAhora=document.getElementById('ahora-escuchar');
		if(escAhora){
			var fk=mejor && FT8_AHORA[mejor.banda];
			escAhora.classList.toggle('hidden', !(fk && window.rauEscuchar));
			if(fk){ escAhora.dataset.khz=fk; escAhora.textContent='🎧 Compruébalo en el receptor del club ('+(fk/1000).toFixed(3)+' MHz, FT8)'; }
		}

		var gl=document.getElementById('ahora-grayline');
		if(ev && enGrayline){
			gl.className='rounded-lg border border-primary/40 bg-primary/10 p-4 text-sm text-primary';
			gl.textContent='Estás en el paso del grayline. Es la ventana en que 40m y 80m alcanzan distancias que no dan en ningún otro momento del día. Dura poco: aprovéchala ahora.';
		} else if(ev){
			gl.className='rounded-lg border border-border/60 bg-background p-4 text-sm text-muted-foreground';
			gl.textContent='Fuera del grayline. '+cambio+', y en esa franja las bandas bajas se realzan durante una hora escasa.';
		} else {
			gl.className='hidden';
		}

		if(solar.updated) document.getElementById('ahora-sello').textContent='Datos solares de: '+solar.updated;
		cargando.classList.add('hidden');
		contenido.classList.remove('hidden');
	}

	function cargar(){
		var base='<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';
		// Los spots son un extra: si el cluster falla, la síntesis sigue teniendo sentido sin ellos.
		Promise.all([
			fetch(base+'?action=rau_solar', {cache:'no-store'}).then(function(r){ return r.json(); }),
			fetch(base+'?action=rau_dx_spots', {cache:'no-store'}).then(function(r){ return r.json(); }).catch(function(e){ rauErr(e); return null; })
		]).then(function(res){
			if(!res[0] || !res[0].success) throw new Error('sin datos solares');
			var spots=(res[1] && res[1].success) ? res[1].data : null;
			pintar(res[0].data, spots);
		}).catch(function(e){ rauErr(e);
			cargando.textContent='No se pudieron leer las condiciones. Vuelve a intentarlo en un momento.';
		});
	}
	cargar();
	setInterval(cargar, 10*60*1000);
})();

/* ── CONVERSOR DE COORDENADAS ─────────────────────────────────────────────────
   Decimal, grados/minutos/segundos, UTM y grid square, todo a la vez. Existe porque el locator
   no le sirve a nadie fuera del mundo radioaficionado: los organismos de socorro colombianos
   trabajan en decimales o en UTM.
   La proyección UTM usa el elipsoide GRS80, que es el de MAGNA-SIRGAS (datum oficial de Colombia)
   y coincide con WGS84 por debajo del metro. Fórmulas estándar de Snyder; verificadas por ida y
   vuelta sobre 575 puntos del territorio con un error máximo de 0.04 mm. */
(function(){
	var entrada=document.getElementById('coords-entrada');
	if(!entrada) return;
	var salida=document.getElementById('coords-salida'), errEl=document.getElementById('coords-error');

	var A=6378137.0, F=1/298.257223563, E2=F*(2-F), K0=0.9996, RAD=Math.PI/180;

	function aUTM(lat,lng){
		var z=Math.floor((lng+180)/6)+1, lm=((z-1)*6-180+3)*RAD, la=lat*RAD, lo=lng*RAD;
		var N=A/Math.sqrt(1-E2*Math.pow(Math.sin(la),2)), T=Math.pow(Math.tan(la),2);
		var C=E2/(1-E2)*Math.pow(Math.cos(la),2), a1=Math.cos(la)*(lo-lm);
		var M=A*((1-E2/4-3*E2*E2/64-5*Math.pow(E2,3)/256)*la
			-(3*E2/8+3*E2*E2/32+45*Math.pow(E2,3)/1024)*Math.sin(2*la)
			+(15*E2*E2/256+45*Math.pow(E2,3)/1024)*Math.sin(4*la)
			-(35*Math.pow(E2,3)/3072)*Math.sin(6*la));
		var E=K0*N*(a1+(1-T+C)*Math.pow(a1,3)/6+(5-18*T+T*T+72*C-58*E2/(1-E2))*Math.pow(a1,5)/120)+500000;
		var Nn=K0*(M+N*Math.tan(la)*(a1*a1/2+(5-T+9*C+4*C*C)*Math.pow(a1,4)/24
			+(61-58*T+T*T+600*C-330*E2/(1-E2))*Math.pow(a1,6)/720));
		if(lat<0) Nn+=10000000;
		return { zona:z, hemi:lat>=0?'N':'S', E:E, N:Nn };
	}
	function deUTM(z,hemi,E,N){
		var x=E-500000, y=(hemi==='S')?N-10000000:N;
		var e1=(1-Math.sqrt(1-E2))/(1+Math.sqrt(1-E2)), M=y/K0;
		var mu=M/(A*(1-E2/4-3*E2*E2/64-5*Math.pow(E2,3)/256));
		var p1=mu+(3*e1/2-27*Math.pow(e1,3)/32)*Math.sin(2*mu)
			+(21*e1*e1/16-55*Math.pow(e1,4)/32)*Math.sin(4*mu)
			+(151*Math.pow(e1,3)/96)*Math.sin(6*mu)+(1097*Math.pow(e1,4)/512)*Math.sin(8*mu);
		var C1=E2/(1-E2)*Math.pow(Math.cos(p1),2), T1=Math.pow(Math.tan(p1),2);
		var N1=A/Math.sqrt(1-E2*Math.pow(Math.sin(p1),2));
		var R1=A*(1-E2)/Math.pow(1-E2*Math.pow(Math.sin(p1),2),1.5), D=x/(N1*K0);
		var lat=p1-(N1*Math.tan(p1)/R1)*(D*D/2-(5+3*T1+10*C1-4*C1*C1-9*E2/(1-E2))*Math.pow(D,4)/24
			+(61+90*T1+298*C1+45*T1*T1-252*E2/(1-E2)-3*C1*C1)*Math.pow(D,6)/720);
		var lng=((z-1)*6-180+3)*RAD+(D-(1+2*T1+C1)*Math.pow(D,3)/6
			+(5-2*C1+28*T1-3*C1*C1+8*E2/(1-E2)+24*T1*T1)*Math.pow(D,5)/120)/Math.cos(p1);
		return { lat:lat/RAD, lng:lng/RAD };
	}
	function aGMS(v,ejeNS){
		var hemi = ejeNS ? (v>=0?'N':'S') : (v>=0?'E':'W');
		v=Math.abs(v);
		var g=Math.floor(v), m=Math.floor((v-g)*60), s=((v-g)*60-m)*60;
		return g+'°'+String(m).padStart(2,'0')+"'"+s.toFixed(1).padStart(4,'0')+'"'+hemi;
	}

	// Se detecta el formato por su forma; el orden importa porque UTM y decimal comparten dígitos
	function interpretar(txt){
		txt=(txt||'').trim();
		if(!txt) return null;
		// Grid square: 4 o 6 caracteres alternando letras y cifras
		if(/^[A-Ra-r]{2}\d{2}([A-Xa-x]{2})?$/.test(txt)){
			var c=rauGridACoords(txt);
			return c ? { lat:c.lat, lng:c.lng, desde:'grid square (centro de la cuadrícula)' } : null;
		}
		// UTM: zona con letra de hemisferio, este y norte
		var u=txt.match(/^(\d{1,2})\s*([NSns])\s+(-?[\d.]+)\s*,?\s+(-?[\d.]+)$/);
		if(u){
			var z=parseInt(u[1],10);
			if(z<1||z>60) return null;
			var r=deUTM(z, u[2].toUpperCase(), parseFloat(u[3]), parseFloat(u[4]));
			return { lat:r.lat, lng:r.lng, desde:'UTM' };
		}
		// Grados, minutos y segundos
		var d=txt.match(/(-?\d+)[°\s]+(\d+)['\s]+([\d.]+)"?\s*([NSns])[\s,]+(-?\d+)[°\s]+(\d+)['\s]+([\d.]+)"?\s*([EWOewo])/);
		if(d){
			var la=(+d[1])+(+d[2])/60+(+d[3])/3600; if(/[Ss]/.test(d[4])) la=-la;
			var lo=(+d[5])+(+d[6])/60+(+d[7])/3600; if(/[WOwo]/.test(d[8])) lo=-lo;
			return { lat:la, lng:lo, desde:'grados, minutos y segundos' };
		}
		// Decimal
		var dec=txt.match(/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/);
		if(dec){
			var a1=parseFloat(dec[1]), b1=parseFloat(dec[2]);
			if(Math.abs(a1)>90||Math.abs(b1)>180) return null;
			return { lat:a1, lng:b1, desde:'grados decimales' };
		}
		return null;
	}

	function fila(rotulo, valor, nota){
		return '<div class="flex items-start justify-between gap-3 rounded-lg border border-border/60 bg-background px-4 py-3">'
			+'<div><p class="font-mono text-[10px] uppercase tracking-wider text-muted-foreground">'+rotulo+'</p>'
			+'<p class="mt-0.5 font-mono text-base font-bold text-foreground">'+valor+'</p>'
			+(nota?'<p class="text-xs text-muted-foreground">'+nota+'</p>':'')+'</div>'
			+'<button type="button" class="coords-copiar shrink-0 rounded-md border border-border/70 px-2.5 py-1 font-mono text-[10px] text-muted-foreground hover:border-primary/50 hover:text-primary" data-valor="'+valor.replace(/"/g,'&quot;')+'">copiar</button></div>';
	}

	function convertir(){
		var r=interpretar(entrada.value);
		if(!r){
			errEl.textContent='No reconozco ese formato. Prueba con 6.2442, -75.5812 o con 18N 435707 690235.';
			errEl.classList.remove('hidden'); salida.innerHTML=''; return;
		}
		errEl.classList.add('hidden');
		var u=aUTM(r.lat, r.lng);
		var grid = (typeof latLngToMH==='function') ? latLngToMH(r.lat, r.lng) : '—';
		salida.innerHTML =
			  fila('Grados decimales', r.lat.toFixed(5)+', '+r.lng.toFixed(5), 'lo que piden en la mayoría de formularios y apps')
			+ fila('Grados, minutos y segundos', aGMS(r.lat,true)+' '+aGMS(r.lng,false), 'formato hablado por radio')
			+ fila('UTM (MAGNA-SIRGAS / WGS84)', u.zona+u.hemi+' '+Math.round(u.E)+'E '+Math.round(u.N)+'N', 'lo que usan los organismos de socorro y el IGAC')
			+ fila('Grid square', grid, 'para el mundo radioaficionado')
			+ '<p class="px-1 pt-1 font-mono text-[10px] text-muted-foreground">Interpretado desde: '+r.desde+'</p>';

		salida.querySelectorAll('.coords-copiar').forEach(function(b){
			b.addEventListener('click', function(){
				navigator.clipboard.writeText(b.dataset.valor).then(function(){
					var antes=b.textContent; b.textContent='copiado';
					setTimeout(function(){ b.textContent=antes; }, 1200);
				});
			});
		});
	}

	entrada.addEventListener('input', convertir);
	rauOn('coords-gps','click',function(){
		if(!navigator.geolocation) return;
		navigator.geolocation.getCurrentPosition(function(pos){
			entrada.value=pos.coords.latitude.toFixed(5)+', '+pos.coords.longitude.toFixed(5);
			convertir();
		}, function(){
			errEl.textContent='No se pudo obtener tu ubicación. Revisa los permisos del navegador.';
			errEl.classList.remove('hidden');
		}, { enableHighAccuracy:true, timeout:10000 });
	});
	rauOn('coords-qth','click',function(){
		var qth=rauQth();
		entrada.value=qth.lat.toFixed(5)+', '+qth.lng.toFixed(5);
		convertir();
	});
	convertir();
})();

/* ── DIAGNÓSTICO DE ROE ───────────────────────────────────────────────────────
   Interpreta un síntoma y termina en una acción, que es lo que hace un elmer y lo que ninguna
   de las otras calculadoras hacía. Los patrones son los clásicos de resolución de averías:
   la curva de ROE a lo largo de la banda dice si la antena está larga o corta, mientras que una
   curva plana y alta apunta al cable o a los conectores, no a la antena. */
(function(){
	var cont=document.getElementById('roe-resultado');
	if(!cont) return;

	var DIAGNOSTICOS={
		'baja-alta':{
			causa:'La antena está LARGA.',
			detalle:'Resuena por debajo de la banda, así que la ROE es buena en la parte baja y empeora al subir. Es el caso más común y el más fácil de arreglar.',
			pasos:['Mide en qué frecuencia está el mínimo real de ROE.',
			       'Usa el ajuste de abajo para saber cuántos centímetros quitar.',
			       'Corta la MITAD por cada lado, no todo de un lado.',
			       'Corta de a poco y vuelve a medir: quitar alambre es fácil, devolverlo no.'],
			// Ejemplo coherente con el síntoma: resuena POR DEBAJO de donde la quieres
			ejemplo:{fres:7.000, fobj:7.100}, ajuste:true, tono:'ok'
		},
		'alta-baja':{
			causa:'La antena está CORTA.',
			detalle:'Resuena por encima de la banda. Pasa mucho cuando se corta con la fórmula teórica sin tener en cuenta el efecto del entorno, o al bajar una antena que estaba más alta.',
			pasos:['Mide en qué frecuencia está el mínimo real de ROE.',
			       'Usa el ajuste de abajo para saber cuánto alambre añadir.',
			       'Añade la mitad por cada lado.',
			       'Si no puedes alargar, bajar un poco la antena o acercarla a objetos también baja la resonancia.'],
			// Ejemplo coherente: resuena POR ENCIMA de donde la quieres
			ejemplo:{fres:7.150, fobj:7.050}, ajuste:true, tono:'ok'
		},
		'centro':{
			causa:'La antena resuena donde toca, pero la impedancia no es 50 ohmios.',
			detalle:'El mínimo está en su sitio, así que la longitud es correcta. Lo que no cuadra es la impedancia en el punto de alimentación, y eso depende sobre todo de la altura sobre el suelo y del entorno.',
			pasos:['Revisa la altura: un dipolo muy bajo o muy alto se aleja de los 50 ohmios.',
			       'Comprueba que el balun sea el adecuado (1:1 para dipolo, 4:1 para lazo o V invertida muy cerrada).',
			       'Un ángulo muy cerrado en una V invertida baja la impedancia; ábrelo si puedes.',
			       'Si nada de eso se puede cambiar, un acoplador resuelve el síntoma — pero la pérdida sigue ahí.'],
			ajuste:false, tono:'aviso'
		},
		'plana-alta':{
			causa:'Casi seguro no es la antena: es el cable, un conector o la conexión.',
			detalle:'Una antena real siempre tiene una curva con mínimo en alguna parte. Si la ROE es alta y casi igual en toda la banda, lo que estás midiendo probablemente no llega a la antena.',
			pasos:['Revisa los conectores, uno por uno, sobre todo el del extremo de la antena.',
			       'Busca cortes, aplastones o dobleces cerrados en el coaxial.',
			       'Comprueba continuidad entre vivo y malla: ni cortocircuito ni circuito abierto.',
			       'Mide directamente en la base de la antena con un cable corto: si ahí la ROE es normal, el problema está en la línea.'],
			ajuste:false, tono:'malo'
		},
		'todas-baja':{
			causa:'Sospecha de cable con pérdidas o de una carga que no es tu antena.',
			detalle:'Una ROE perfecta en todas las bandas, incluidas aquellas donde la antena no debería funcionar, casi nunca es buena noticia: la pérdida del cable se come la onda reflejada y disimula el desajuste. Tu ROE parece excelente mientras la potencia se pierde calentando el coaxial.',
			pasos:['Comprueba si el coaxial tiene agua dentro: es la causa más frecuente.',
			       'Mide la pérdida de la línea con la calculadora de línea de transmisión.',
			       'Descarta que estés midiendo contra una carga fantasma o un acoplador automático que ya cerró.',
			       'Compara la señal recibida con la de otra estación cercana: si oyes mucho menos, es la línea.'],
			ajuste:false, tono:'malo'
		}
	};

	var EXTRAS={
		lluvia:'Que cambie con la lluvia señala entrada de agua: revisa el sellado de los conectores y del balun. Un conector mal sellado es la avería más común de todas y la más barata de arreglar.',
		potencia:'Que suba con la potencia indica arco eléctrico. Busca puntos de arco en el balun, en los conectores o en el aislador central. A potencia baja no se nota; a potencia alta destruye componentes.',
		subito:'Un cambio repentino apunta a algo mecánico: un elemento partido, una soldadura suelta, viento o un animal. Inspecciona la antena visualmente antes de medir nada más.',
		tocar:'Que cambie al tocar el equipo es RF en la estación, no un problema de antena: falta un buen plano de tierra o hay corriente por el exterior de la malla. Un choque de ferrita en el coaxial cerca del equipo suele resolverlo.'
	};

	var TONOS={ok:'border-emerald-400/40 bg-emerald-400/5 text-emerald-400',
	           aviso:'border-amber-400/40 bg-amber-400/5 text-amber-400',
	           malo:'border-rose-400/40 bg-rose-400/5 text-rose-400'};

	function diagnosticar(){
		var sel=document.querySelector('input[name="roe-sintoma"]:checked');
		if(!sel) return;
		var d=DIAGNOSTICOS[sel.value];
		var html='<div class="rounded-xl border p-5 '+TONOS[d.tono]+'">'
			+'<p class="font-mono text-base font-bold">'+d.causa+'</p>'
			+'<p class="mt-1.5 text-sm opacity-90">'+d.detalle+'</p></div>'
			+'<div class="rounded-xl border border-border/60 bg-background p-5">'
			+'<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Qué revisar, en este orden</p>'
			+'<ol class="list-decimal space-y-1.5 pl-5 text-sm text-foreground">'
			+d.pasos.map(function(p){ return '<li>'+p+'</li>'; }).join('')+'</ol></div>';

		var marcados=Array.prototype.slice.call(document.querySelectorAll('.roe-extra:checked'));
		if(marcados.length){
			html+='<div class="rounded-xl border border-primary/30 bg-primary/5 p-5">'
				+'<p class="mb-2 font-mono text-[11px] uppercase tracking-wider text-primary">Además, por lo que marcaste</p>'
				+'<ul class="space-y-2 text-sm text-foreground">'
				+marcados.map(function(m){ return '<li>'+EXTRAS[m.value]+'</li>'; }).join('')+'</ul></div>';
		}
		cont.innerHTML=html;
		document.getElementById('roe-ajuste').classList.toggle('hidden', !d.ajuste);
		// Los valores de ejemplo se ajustan al síntoma elegido. Con valores fijos, el diagnóstico
		// "está larga, hay que quitar" convivía con una calculadora diciendo "alargar 14 cm":
		// dos partes de la misma pantalla contradiciéndose.
		if(d.ajuste && d.ejemplo && !ajusteTocado){
			document.getElementById('roe-fres').value=d.ejemplo.fres.toFixed(3);
			document.getElementById('roe-fobj').value=d.ejemplo.fobj.toFixed(3);
			calcularAjuste();
		}
	}

	function calcularAjuste(){
		var fr=parseFloat(document.getElementById('roe-fres').value);
		var fo=parseFloat(document.getElementById('roe-fobj').value);
		var l =parseFloat(document.getElementById('roe-largo').value);
		var out=document.getElementById('roe-ajuste-salida');
		if(isNaN(fr)||isNaN(fo)||isNaN(l)||fr<=0||fo<=0||l<=0){ out.textContent='—'; return; }
		// La resonancia es inversamente proporcional a la longitud: l2 = l1 · f1/f2
		var nuevo=l*(fr/fo), delta=nuevo-l;
		if(Math.abs(delta)<0.005){ out.textContent='Ya está donde la quieres: no toques nada.'; return; }
		var accion = delta>0 ? 'Alargar' : 'Acortar';
		out.textContent = accion+' '+Math.abs(delta*100).toFixed(0)+' cm en total — '
			+Math.abs(delta*100/2).toFixed(0)+' cm por cada lado. Largo final: '+nuevo.toFixed(2)+' m.';
	}

	// Si el usuario escribe sus propias medidas, dejamos de sobrescribirlas al cambiar de síntoma
	var ajusteTocado=false;
	document.querySelectorAll('input[name="roe-sintoma"]').forEach(function(r){ r.addEventListener('change', diagnosticar); });
	document.querySelectorAll('.roe-extra').forEach(function(c){ c.addEventListener('change', diagnosticar); });
	['roe-fres','roe-fobj','roe-largo'].forEach(function(id){
		rauOn(id,'input',function(){ ajusteTocado=true; calcularAjuste(); });
	});
	diagnosticar();
	calcularAjuste();
})();

/* ── FRECUENCIAS DE EMERGENCIA ── */
(function(){
	var botones=document.querySelectorAll('.fe-copiar');
	if(!botones.length) return;
	var aviso=document.getElementById('fe-aviso');
	botones.forEach(function(b){
		b.addEventListener('click', function(){
			var f=b.dataset.freq;
			// En una emergencia lo que se quiere es meter el número en el radio sin escribirlo mal
			navigator.clipboard.writeText(f).then(function(){
				if(!aviso) return;
				aviso.textContent='Copiado: '+f+' MHz';
				aviso.classList.remove('hidden');
				clearTimeout(aviso._t);
				aviso._t=setTimeout(function(){ aviso.classList.add('hidden'); }, 2500);
			});
		});
	});
})();

/* ── MENSAJE FORMAL ICS-213 ───────────────────────────────────────────────────
   El valor operativo no está en el formulario sino en el conteo de palabras: es el "check" que
   permite a quien recibe verificar que no se perdió nada por el camino. Si el número no coincide
   con lo que anotó, pide repetición antes de dar el mensaje por bueno. */
(function(){
	var msg=document.getElementById('ics-mensaje');
	if(!msg) return;
	var CAMPOS=['ics-precedencia','ics-numero','ics-incidente','ics-para','ics-de','ics-asunto','ics-mensaje','ics-firma'];
	var CLAVE='rau_ics213';

	function contar(t){
		t=(t||'').trim();
		return t ? t.split(/\s+/).length : 0;
	}
	function dosDig(n){ return String(n).padStart(2,'0'); }

	function render(){
		var v={};
		CAMPOS.forEach(function(id){ var e=document.getElementById(id); v[id]=e?e.value.trim():''; });
		var n=contar(v['ics-mensaje']);
		document.getElementById('ics-check').textContent=n+' palabra'+(n===1?'':'s');

		var ahora=new Date();
		var utc=dosDig(ahora.getUTCHours())+dosDig(ahora.getUTCMinutes())+'Z';
		var fecha=ahora.getUTCFullYear()+'-'+dosDig(ahora.getUTCMonth()+1)+'-'+dosDig(ahora.getUTCDate());

		var lineas=[
			'--- MENSAJE ICS-213 ---',
			'NUMERO ....... '+(v['ics-numero']||'—'),
			'PRECEDENCIA .. '+v['ics-precedencia'],
			'FECHA/HORA ... '+fecha+' '+utc,
			'INCIDENTE .... '+(v['ics-incidente']||'—'),
			'PARA ......... '+(v['ics-para']||'—'),
			'DE ........... '+(v['ics-de']||'—'),
			'ASUNTO ....... '+(v['ics-asunto']||'—'),
			'CHECK ........ '+n,
			'',
			'TEXTO:',
			v['ics-mensaje']||'(sin texto)',
			'',
			'APROBADO POR . '+(v['ics-firma']||'—'),
			'--- FIN DEL MENSAJE '+(v['ics-numero']||'')+' ---'
		];
		document.getElementById('ics-salida').textContent=lineas.join('\n');

		// Guardado local: si se corta la luz o se recarga, el mensaje a medias no se pierde.
		// No sale del navegador — es tráfico que puede ser sensible.
		try { localStorage.setItem(CLAVE, JSON.stringify(v)); } catch(e){}
	}

	function restaurar(){
		try {
			var g=JSON.parse(localStorage.getItem(CLAVE)||'{}');
			CAMPOS.forEach(function(id){
				var e=document.getElementById(id);
				if(e && typeof g[id]==='string' && g[id]!=='') e.value=g[id];
			});
		} catch(e){}
	}

	CAMPOS.forEach(function(id){ rauOn(id,'input',render); rauOn(id,'change',render); });

	rauOn('ics-copiar','click',function(){
		var b=document.getElementById('ics-copiar'), antes=b.textContent;
		navigator.clipboard.writeText(document.getElementById('ics-salida').textContent).then(function(){
			b.textContent='Copiado'; setTimeout(function(){ b.textContent=antes; }, 1500);
		});
	});
	rauOn('ics-imprimir','click',function(){
		// Ventana aparte con solo el mensaje: en papel no sirve la interfaz del sitio alrededor
		var w=window.open('','_blank','width=700,height=800');
		if(!w) return;
		w.document.write('<!DOCTYPE html><meta charset="utf-8"><title>Mensaje ICS-213</title>'
			+'<style>body{font:13px/1.6 ui-monospace,Menlo,monospace;padding:32px;white-space:pre-wrap}</style>'
			+document.getElementById('ics-salida').textContent.replace(/[<>&]/g, function(c){
				return {'<':'&lt;','>':'&gt;','&':'&amp;'}[c];
			}));
		w.document.close();
		w.focus();
		w.print();
	});
	rauOn('ics-limpiar','click',function(){
		if(!confirm('¿Borrar el mensaje?')) return;
		CAMPOS.forEach(function(id){
			var e=document.getElementById(id);
			if(!e) return;
			if(e.tagName==='SELECT') e.selectedIndex=0;
			else if(id==='ics-numero') e.value='1';
			else e.value='';
		});
		try { localStorage.removeItem(CLAVE); } catch(e){}
		render();
	});

	restaurar();
	render();
	// La hora del encabezado se refresca sola mientras el mensaje está abierto
	setInterval(render, 30000);
})();

/* ── PERFIL DE TERRENO ────────────────────────────────────────────────────────
   La única herramienta del catálogo que consulta un servicio externo NUEVO, y por eso está
   construida para fallar bien: la petición la hace el navegador del visitante (no el servidor),
   y si el servicio no responde el panel lo dice sin afectar a nada más de la página.
   Radio terrestre efectivo 4/3: la atmósfera curva las ondas de VHF/UHF hacia el suelo, así que
   el horizonte de radio queda más lejos que el óptico. Comprobado: 23.5 m de abombamiento en un
   trayecto de 40 km, que coincide con la referencia clásica.
   Zona de Fresnel con λ explícita en vez de la constante 17.31, que va con la frecuencia en GHz
   y es una trampa de unidades fácil de pasar por alto. */
(function(){
	var btn=document.getElementById('ter-calcular');
	if(!btn) return;
	var estado=document.getElementById('ter-estado'), resultado=document.getElementById('ter-resultado');
	var R=6371, RE=R*4/3, MUESTRAS=80;

	function leerPunto(txt){
		txt=(txt||'').trim();
		if(/^[A-Ra-r]{2}\d{2}([A-Xa-x]{2})?$/.test(txt)) return rauGridACoords(txt);
		var m=txt.match(/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/);
		if(m){
			var la=parseFloat(m[1]), lo=parseFloat(m[2]);
			if(Math.abs(la)<=90 && Math.abs(lo)<=180) return {lat:la, lng:lo};
		}
		return null;
	}
	// Interpolación sobre el círculo máximo: en trayectos largos la línea recta en lat/lng se
	// desvía del camino real que sigue la señal.
	function interpolar(a,b,f){
		var p=Math.PI/180;
		var la1=a.lat*p, lo1=a.lng*p, la2=b.lat*p, lo2=b.lng*p;
		var d=2*Math.asin(Math.sqrt(Math.pow(Math.sin((la2-la1)/2),2)+Math.cos(la1)*Math.cos(la2)*Math.pow(Math.sin((lo2-lo1)/2),2)));
		if(d===0) return {lat:a.lat, lng:a.lng};
		var A=Math.sin((1-f)*d)/Math.sin(d), B=Math.sin(f*d)/Math.sin(d);
		var x=A*Math.cos(la1)*Math.cos(lo1)+B*Math.cos(la2)*Math.cos(lo2);
		var y=A*Math.cos(la1)*Math.sin(lo1)+B*Math.cos(la2)*Math.sin(lo2);
		var z=A*Math.sin(la1)+B*Math.sin(la2);
		return { lat:Math.atan2(z,Math.sqrt(x*x+y*y))/p, lng:Math.atan2(y,x)/p };
	}
	function aviso(texto, tono){
		estado.className='mb-4 rounded-lg border px-4 py-3 text-sm '+
			(tono==='error'?'border-rose-400/40 bg-rose-400/5 text-rose-400':'border-border/60 bg-background text-muted-foreground');
		estado.textContent=texto;
		estado.classList.remove('hidden');
	}

	function dibujar(perfil, distTotal, h1, h2, freq){
		var n=perfil.length;
		var elevA=perfil[0]+h1, elevB=perfil[n-1]+h2;
		var lam=299.792458/freq;

		// Para cada muestra: altura del terreno, altura de la visual y abombamiento terrestre
		var puntos=perfil.map(function(z,i){
			var f=i/(n-1), d1=distTotal*f, d2=distTotal-d1;
			var visual=elevA+(elevB-elevA)*f;
			var bulto=(d1*d2*1000000)/(2*RE*1000);            // metros
			var fres=Math.sqrt(lam*(d1*1000)*(d2*1000)/(distTotal*1000||1));
			return { d:d1, terreno:z, efectivo:z+bulto, visual:visual, fresnel:fres, holgura:visual-(z+bulto) };
		});

		var peor=puntos.slice(1,-1).reduce(function(a,b){ return (a && a.holgura<=b.holgura)?a:b; }, null) || puntos[0];
		var despeje60=0.6*peor.fresnel;
		var bloqueado=peor.holgura<0;
		var rozando=!bloqueado && peor.holgura<despeje60;

		var v=document.getElementById('ter-veredicto');
		if(bloqueado){
			v.className='rounded-xl border border-rose-400/40 bg-rose-400/5 p-5 text-rose-400';
			v.innerHTML='<p class="font-mono text-lg font-bold">Sin línea de vista</p><p class="mt-1.5 text-sm opacity-90">El terreno bloquea el trayecto por '+Math.abs(peor.holgura).toFixed(0)+' m a los '+peor.d.toFixed(1)+' km. Necesitarías subir la antena, cambiar de sitio o pasar por una repetidora.</p>';
		} else if(rozando){
			v.className='rounded-xl border border-amber-400/40 bg-amber-400/5 p-5 text-amber-400';
			v.innerHTML='<p class="font-mono text-lg font-bold">Línea de vista justa</p><p class="mt-1.5 text-sm opacity-90">Pasa por encima del terreno ('+peor.holgura.toFixed(0)+' m a los '+peor.d.toFixed(1)+' km), pero por debajo del 60% de la primera zona de Fresnel ('+despeje60.toFixed(0)+' m), que es el despeje recomendado. Funcionará con pérdida; unos metros más de antena ayudarían.</p>';
		} else {
			v.className='rounded-xl border border-emerald-400/40 bg-emerald-400/5 p-5 text-emerald-400';
			v.innerHTML='<p class="font-mono text-lg font-bold">Línea de vista despejada</p><p class="mt-1.5 text-sm opacity-90">El punto más ajustado deja '+peor.holgura.toFixed(0)+' m de holgura a los '+peor.d.toFixed(1)+' km, por encima del despeje recomendado de '+despeje60.toFixed(0)+' m.</p>';
		}

		// Gráfico
		var W=800, H=300, pad=34;
		var maxY=Math.max.apply(null, puntos.map(function(p){ return Math.max(p.efectivo, p.visual); }))*1.08;
		var minY=Math.min.apply(null, puntos.map(function(p){ return p.terreno; }))*0.92;
		function X(i){ return pad+(i/(n-1))*(W-pad*2); }
		function Y(z){ return H-pad-((z-minY)/(maxY-minY||1))*(H-pad*2); }

		var suelo=puntos.map(function(p,i){ return X(i)+','+Y(p.efectivo).toFixed(1); }).join(' ');
		var svg='<polygon points="'+pad+','+(H-pad)+' '+suelo+' '+(W-pad)+','+(H-pad)+'" fill="hsl(var(--muted))" opacity="0.55"/>'
			+'<polyline points="'+suelo+'" fill="none" stroke="hsl(var(--muted-foreground))" stroke-width="1.5"/>'
			+'<line x1="'+X(0)+'" y1="'+Y(elevA)+'" x2="'+X(n-1)+'" y2="'+Y(elevB)+'" stroke="'+(bloqueado?'#fb7185':rozando?'#fbbf24':'#34d399')+'" stroke-width="2" stroke-dasharray="6 3"/>'
			+'<circle cx="'+X(0)+'" cy="'+Y(elevA)+'" r="4" fill="hsl(var(--primary))"/>'
			+'<circle cx="'+X(n-1)+'" cy="'+Y(elevB)+'" r="4" fill="hsl(var(--primary))"/>';
		var iPeor=puntos.indexOf(peor);
		svg+='<line x1="'+X(iPeor)+'" y1="'+Y(peor.efectivo)+'" x2="'+X(iPeor)+'" y2="'+Y(peor.visual)+'" stroke="'+(bloqueado?'#fb7185':'#fbbf24')+'" stroke-width="1.5"/>';
		svg+='<text x="'+pad+'" y="'+(H-10)+'" fill="hsl(var(--muted-foreground))" font-size="11" font-family="monospace">0 km</text>'
			+'<text x="'+(W-pad)+'" y="'+(H-10)+'" text-anchor="end" fill="hsl(var(--muted-foreground))" font-size="11" font-family="monospace">'+distTotal.toFixed(1)+' km</text>'
			+'<text x="6" y="'+(pad+4)+'" fill="hsl(var(--muted-foreground))" font-size="11" font-family="monospace">'+Math.round(maxY)+' m</text>';
		document.getElementById('ter-grafico').innerHTML=svg;

		document.getElementById('ter-datos').innerHTML=[
			['Distancia', distTotal.toFixed(1)+' km'],
			['Punto más alto', Math.round(Math.max.apply(null, perfil))+' m'],
			['Obstáculo crítico', peor.d.toFixed(1)+' km'],
			['Despeje necesario', despeje60.toFixed(0)+' m']
		].map(function(d){
			return '<div class="rounded-lg border border-border/50 bg-background px-3 py-2">'
				+'<p class="text-[10px] uppercase text-muted-foreground">'+d[0]+'</p>'
				+'<p class="font-bold text-foreground">'+d[1]+'</p></div>';
		}).join('');

		estado.classList.add('hidden');
		resultado.classList.remove('hidden');
	}

	btn.addEventListener('click', function(){
		var a=leerPunto(document.getElementById('ter-origen').value);
		var b=leerPunto(document.getElementById('ter-destino').value);
		if(!a||!b){ aviso('No entiendo alguno de los dos puntos. Usa un grid (FJ26ff) o coordenadas (6.2442, -75.5812).','error'); resultado.classList.add('hidden'); return; }
		var d=rauDistanciaRumbo(a.lat,a.lng,b.lat,b.lng).dist;
		if(d<0.5){ aviso('Los dos puntos están casi encima uno del otro.','error'); resultado.classList.add('hidden'); return; }
		if(d>400){ aviso('Más de 400 km: a esa distancia la línea de vista deja de ser el factor y manda la propagación ionosférica. Esta herramienta no aplica.','error'); resultado.classList.add('hidden'); return; }
		// Altura vacía no es "antena en el suelo": con ||0 el perfil se calculaba a 0 m sin avisar.
		var h1=parseFloat(document.getElementById('ter-h1').value), h2=parseFloat(document.getElementById('ter-h2').value);
		if(isNaN(h1)||isNaN(h2)){ aviso('Indica la altura de las dos antenas sobre el suelo (0 si están a ras de suelo).','error'); resultado.classList.add('hidden'); return; }

		aviso('Consultando elevaciones…');
		resultado.classList.add('hidden');
		var lats=[], lngs=[];
		for(var i=0;i<MUESTRAS;i++){ var p=interpolar(a,b,i/(MUESTRAS-1)); lats.push(p.lat.toFixed(5)); lngs.push(p.lng.toFixed(5)); }

		var url='https://api.open-meteo.com/v1/elevation?latitude='+lats.join(',')+'&longitude='+lngs.join(',');
		var ctrl=new AbortController();
		var corte=setTimeout(function(){ ctrl.abort(); }, 15000);
		fetch(url, { signal:ctrl.signal })
			.then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
			.then(function(j){
				clearTimeout(corte);
				if(!j || !j.elevation || j.elevation.length<2) throw new Error('respuesta vacía');
				dibujar(j.elevation, d, h1, h2,
					parseFloat(document.getElementById('ter-freq').value)||146);
			})
			.catch(function(e){ rauErr(e);
				clearTimeout(corte);
				// Falla contenida: solo este panel. El resto de la página no se entera.
				aviso('No se pudieron obtener las elevaciones. El servicio de Open-Meteo puede estar caído o bloqueado por tu red. El resto del sitio funciona igual.','error');
			});
	});

	rauOn('ter-gps','click',function(){
		if(!navigator.geolocation) return;
		navigator.geolocation.getCurrentPosition(function(pos){
			document.getElementById('ter-origen').value=pos.coords.latitude.toFixed(5)+', '+pos.coords.longitude.toFixed(5);
		}, null, { enableHighAccuracy:true, timeout:10000 });
	});
})();

/* ── CALENDARIO DE CONCURSOS ── */
(function(){
	var listEl=document.getElementById('concursos-list');
	if(!listEl) return;

	function finesDeSemanaCompletos(year, month){
		var lastDay=new Date(Date.UTC(year, month+1, 0)).getUTCDate();
		var out=[];
		for(var d=1; d<=lastDay; d++){
			var dt=new Date(Date.UTC(year, month, d));
			if(dt.getUTCDay()===6 && d+1<=lastDay) out.push(dt);
		}
		return out;
	}
	function nEsimoFinDeSemana(year, month, n){
		var ws=finesDeSemanaCompletos(year, month);
		return n<0 ? ws[ws.length+n] : ws[n-1];
	}
	function proximo(month, n){
		var ahora=new Date();
		var candidato=nEsimoFinDeSemana(ahora.getUTCFullYear(), month, n);
		if(!candidato || (candidato.getTime()+2*86400000) < ahora.getTime()){
			candidato=nEsimoFinDeSemana(ahora.getUTCFullYear()+1, month, n);
		}
		return candidato;
	}
	function fmt(dt){
		return dt.toLocaleDateString('es-CO', {day:'numeric', month:'long', year:'numeric', timeZone:'UTC'});
	}
	function diasFaltantes(dt){
		return Math.ceil((dt.getTime()-new Date().getTime())/86400000);
	}

	var CONCURSOS=[
		{n:'ARRL International DX (Phone)', mes:2, n_semana:1,  url:'https://contests.arrl.org/'},
		{n:'CQ WPX Contest (SSB)',          mes:2, n_semana:-1, url:'https://cqwpx.com/'},
		{n:'CQ WPX Contest (CW)',           mes:4, n_semana:-1, url:'https://cqwpx.com/'},
		{n:'ARRL Field Day',                mes:5, n_semana:4,  url:'https://www.arrl.org/field-day'},
		{n:'IARU HF World Championship',    mes:6, n_semana:2,  url:'https://www.iaru.org/'},
		{n:'CQ WW DX Contest (SSB)',        mes:9, n_semana:-1, url:'https://cqww.com/'},
		{n:'ARRL November Sweepstakes (CW)',mes:10,n_semana:1,  url:'https://contests.arrl.org/'},
		{n:'CQ WW DX Contest (CW)',         mes:10,n_semana:-1, url:'https://cqww.com/'},
	];

	var items = CONCURSOS.map(function(c){
		var dt=proximo(c.mes, c.n_semana);
		return {nombre:c.n, url:c.url, fecha:dt, faltan:diasFaltantes(dt)};
	}).sort(function(a,b){ return a.fecha-b.fecha; });

	listEl.innerHTML = items.map(function(it){
		return '<div class="rounded-xl border border-border/70 bg-background p-4">'
			+'<p class="font-semibold">'+it.nombre+'</p>'
			+'<p class="mt-1 font-mono text-xs text-muted-foreground">'+fmt(it.fecha)+'</p>'
			+'<div class="mt-2 flex items-center justify-between">'
			+'<span class="font-mono text-sm font-bold text-primary">'+(it.faltan<=0?'¡Es este fin de semana!':'Faltan '+it.faltan+' días')+'</span>'
			+'<a href="'+it.url+'" target="_blank" rel="noopener" class="text-xs font-medium text-primary hover:underline">Reglas →</a>'
			+'</div></div>';
	}).join('');
})();

/* ── RECORDATORIO DE LICENCIA (localStorage, sin cuenta) ── */
(function(){
	var fechaEl=document.getElementById('lic-fecha');
	if(!fechaEl) return;
	var guardarBtn=document.getElementById('lic-guardar'), resultado=document.getElementById('lic-resultado'),
		estadoEl=document.getElementById('lic-estado'), icsBtn=document.getElementById('lic-ics');

	function mostrarEstado(fechaStr){
		var venc=new Date(fechaStr+'T00:00:00');
		var hoy=new Date(); hoy.setHours(0,0,0,0);
		var dias=Math.round((venc-hoy)/86400000);
		resultado.classList.remove('hidden');
		var plural=function(n){ return n===1 ? '1 día' : n+' días'; };
		if(dias<0){
			estadoEl.className='font-mono text-2xl font-bold text-rose-400';
			estadoEl.textContent='Vencida hace '+plural(Math.abs(dias));
		} else if(dias===0){
			estadoEl.className='font-mono text-2xl font-bold text-rose-400';
			estadoEl.textContent='Vence hoy';
		} else if(dias<=60){
			estadoEl.className='font-mono text-2xl font-bold text-amber-400';
			estadoEl.textContent='Vence en '+plural(dias);
		} else {
			estadoEl.className='font-mono text-2xl font-bold text-emerald-400';
			estadoEl.textContent='Vence en '+plural(dias);
		}
	}

	try {
		var guardada=localStorage.getItem('rau_licencia_vencimiento');
		if(guardada){ fechaEl.value=guardada; mostrarEstado(guardada); }
	} catch(e){}

	guardarBtn.addEventListener('click', function(){
		if(!fechaEl.value) return;
		try { localStorage.setItem('rau_licencia_vencimiento', fechaEl.value); } catch(e){}
		mostrarEstado(fechaEl.value);
	});

	icsBtn.addEventListener('click', function(){
		if(!fechaEl.value) return;
		var d=fechaEl.value.replace(/-/g,'');
		// RFC 5545 exige PRODID en el VCALENDAR y UID + DTSTAMP en cada VEVENT. Este archivo no
		// llevaba ninguno de los tres y varios calendarios rechazan o importan mal un .ics así.
		// El generador de /calendario.ics del tema sí los incluye — este se había quedado atrás.
		var ahora=new Date().toISOString().replace(/[-:]/g,'').split('.')[0]+'Z';
		var uid='licencia-'+d+'-'+Date.now()+'@hk4rau.com';
		var ics=[
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//RAU//Recordatorio de Licencia//ES',
			'CALSCALE:GREGORIAN',
			'BEGIN:VEVENT',
			'UID:'+uid,
			'DTSTAMP:'+ahora,
			'DTSTART;VALUE=DATE:'+d,
			'SUMMARY:Vencimiento licencia RABCA',
			'DESCRIPTION:Renovar licencia de radioaficionado (RABCA)',
			'BEGIN:VALARM',
			'TRIGGER:-P30D',
			'ACTION:DISPLAY',
			'DESCRIPTION:Tu licencia RABCA vence en 30 días',
			'END:VALARM',
			'END:VEVENT',
			'END:VCALENDAR'
		].join('\r\n');
		var blob=new Blob([ics], {type:'text/calendar'});
		var a=document.createElement('a');
		a.href=URL.createObjectURL(blob);
		a.download='vencimiento-licencia-rabca.ics';
		a.click();
		// Sin esto el objeto queda retenido en memoria hasta que se cierre la pestaña
		setTimeout(function(){ URL.revokeObjectURL(a.href); }, 1000);
	});
})();

/* ── VERIFICADOR DE DIPLOMAS ── */
(function(){
	var buscarBtn=document.getElementById('dip-buscar');
	if(!buscarBtn) return;
	var input=document.getElementById('dip-numero'), resultado=document.getElementById('dip-resultado');

	// Los valores vienen del título y los metadatos del diploma, que solo edita un administrador,
	// pero se escapan igual: un '<' escrito por descuido rompería la tarjeta, y no hay ninguna
	// razón para renderizar como HTML algo que es texto plano.
	function esc(t){
		return String(t==null?'':t).replace(/&/g,'&amp;').replace(/</g,'&lt;')
			.replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
	}

	function buscar(){
		var numero=input.value.trim();
		if(!numero) return;
		resultado.innerHTML='<p class="text-sm text-muted-foreground">Buscando…</p>';
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_diploma_lookup&numero='+encodeURIComponent(numero), {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(!json.success){
					resultado.innerHTML='<div class="rounded-xl border border-rose-400/30 bg-rose-400/5 p-4 text-sm text-rose-400">'+esc(json.data||'No encontrado.')+'</div>';
					return;
				}
				var d=json.data;
				resultado.innerHTML='<div class="rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-4">'
					+'<p class="font-mono text-xs font-bold uppercase tracking-wider text-emerald-400">✓ Diploma auténtico</p>'
					+'<p class="mt-2 font-display text-lg font-bold">'+esc(d.titulo)+'</p>'
					+'<p class="text-sm text-muted-foreground">'+esc(d.nombre)+'</p>'
					+(d.evento?'<p class="mt-1 text-xs text-muted-foreground">Actividad: '+esc(d.evento)+'</p>':'')
					+(d.fecha?'<p class="text-xs text-muted-foreground">Fecha: '+esc(d.fecha)+'</p>':'')
					+'</div>';
			}).catch(function(e){ rauErr(e);
				resultado.innerHTML='<div class="rounded-xl border border-rose-400/30 bg-rose-400/5 p-4 text-sm text-rose-400">No se pudo verificar. Intenta de nuevo.</div>';
			});
	}
	buscarBtn.addEventListener('click', buscar);
	input.addEventListener('keydown', function(e){ if(e.key==='Enter') buscar(); });
})();

/* ── MAIDENHEAD ── */
function latLngToMH(lat,lng,prec){
	prec=prec||6;
	lng+=180; lat+=90;
	let grid='';
	grid+=String.fromCharCode(65+Math.floor(lng/20));
	grid+=String.fromCharCode(65+Math.floor(lat/10));
	grid+=String(Math.floor((lng%20)/2));
	grid+=String(Math.floor(lat%10));
	if(prec>=6){
		// Se multiplica por 12 y 24 en vez de dividir por 0.0833333 y 0.0416667. Esas constantes
		// son 1/12 y 1/24 truncadas, y al dividir dan un pelo MENOS: con una coordenada que cae
		// justo en el límite de un subcuadro (lat%1 = 0.5, por ejemplo) el cálculo daba 11.99999
		// en vez de 12 y devolvía la letra anterior. Multiplicar es exacto.
		grid+=String.fromCharCode(97+Math.floor((lng%2)*12));
		grid+=String.fromCharCode(97+Math.floor((lat%1)*24));
	}
	return grid.toUpperCase();
}
/* Grid square → coordenadas del CENTRO de la cuadrícula.
   Función única y compartida: antes había cuatro copias de este cálculo (Maidenhead, Azimuth,
   Cobertura de Repetidor y Predicción de Pasadas) y las cuatro estaban mal, de dos formas distintas.
   Tres de ellas sumaban el desplazamiento al centro del cuadro de 2°×1° (+1 / +0.5) y ENCIMA le
   sumaban el del subcuadro, quedando hasta 1° de longitud y 0.5° de latitud corridas — unos 120 km
   en la latitud de Medellín, más que el alcance típico de un repetidor de 2m. La cuarta (mhToLatLng)
   además dividía el ancho del subcuadro entre 12 sin razón, encogiéndolo 12 veces.
   Comprobado: FJ26ff → 6.2292 N, 75.5417 W (centro real de la cuadrícula de Medellín). */
/* Distancia de círculo máximo y rumbo inicial entre dos puntos (Haversine + rumbo directo).
   Función única y compartida: este mismo bloque estaba copiado cinco veces a lo largo del archivo
   (mapa de la consola, modo "En el campo", Localizador DX, Azimuth y Cobertura de Repetidor).
   Las cinco copias eran correctas, pero es exactamente la duplicación que dejó pasar el error de
   los grid squares — con una sola versión, verificarla una vez alcanza para todas. */
function rauDistanciaRumbo(lat1,lng1,lat2,lng2){
	var R=6371; // radio medio terrestre, km
	var la1=lat1*Math.PI/180, la2=lat2*Math.PI/180;
	var dLat=(lat2-lat1)*Math.PI/180, dLng=(lng2-lng1)*Math.PI/180;
	var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(la1)*Math.cos(la2)*Math.sin(dLng/2)*Math.sin(dLng/2);
	var dist=R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
	var y=Math.sin(dLng)*Math.cos(la2);
	var x=Math.cos(la1)*Math.sin(la2)-Math.sin(la1)*Math.cos(la2)*Math.cos(dLng);
	var az=(Math.atan2(y,x)*180/Math.PI+360)%360;
	return {dist:dist, az:az};
}
function rauGridACoords(g){
	g=(g||'').toUpperCase().trim();
	if(g.length<4) return null;
	var campoLng=g.charCodeAt(0)-65, campoLat=g.charCodeAt(1)-65;
	var cuadLng=parseInt(g[2],10), cuadLat=parseInt(g[3],10);
	if(isNaN(cuadLng)||isNaN(cuadLat)) return null;
	var lng=-180+campoLng*20+cuadLng*2;
	var lat=-90+campoLat*10+cuadLat*1;
	if(g.length>=6){
		// Subcuadro: 24 divisiones, así que mide 2/24° en longitud y 1/24° en latitud
		var subLng=g.charCodeAt(4)-65, subLat=g.charCodeAt(5)-65;
		lng+=subLng*(2/24)+(1/24);
		lat+=subLat*(1/24)+(0.5/24);
	} else {
		// Sin subcuadro, el centro del cuadro de 2°×1°
		lng+=1; lat+=0.5;
	}
	return {lat:lat, lng:lng};
}
function mhToLatLng(grid){
	var c=rauGridACoords(grid);
	if(!c) return null;
	return {lat:c.lat.toFixed(4), lng:c.lng.toFixed(4)};
}
rauOn('mh-calc','click',()=>{
	const lat=parseFloat(document.getElementById('mh-lat').value);
	const lng=parseFloat(document.getElementById('mh-lng').value);
	if(isNaN(lat)||isNaN(lng)){return;}
	const grid=latLngToMH(lat,lng,6);
	document.getElementById('mh-grid').textContent=grid;
	document.getElementById('mh-result').classList.remove('hidden');
});
rauOn('mh-gps','click',()=>{
	if(!navigator.geolocation){alert('Tu navegador no soporta geolocalización.');return;}
	navigator.geolocation.getCurrentPosition(pos=>{
		document.getElementById('mh-lat').value=pos.coords.latitude.toFixed(4);
		document.getElementById('mh-lng').value=pos.coords.longitude.toFixed(4);
		const grid=latLngToMH(pos.coords.latitude,pos.coords.longitude,6);
		document.getElementById('mh-grid').textContent=grid;
		document.getElementById('mh-result').classList.remove('hidden');
	},()=>alert('No se pudo obtener la ubicación.'));
});
rauOn('mh-decode','click',()=>{
	const g=document.getElementById('mh-grid-in').value.trim();
	if(g.length<4){return;}
	const r=mhToLatLng(g);
	if(!r){return;}
	document.getElementById('mh-dlat').textContent=r.lat+'°';
	document.getElementById('mh-dlng').textContent=r.lng+'°';
	document.getElementById('mh-decode-result').classList.remove('hidden');
});
rauOn('mh-grid-in','input',function(){this.value=this.value.toUpperCase();});

/* ── PREFIJOS (base de América Latina + más comunes) ── */
/* PFX se deriva de PFX_GEO (declarada más abajo) en vez de mantenerse aparte.
   Antes eran dos objetos literales con LAS MISMAS 57 claves, uno con coordenadas y otro sin
   ellas: el mismo patrón de duplicación que ya había dejado pasar errores en los grid squares
   y en la fórmula de Haversine. Agregar un país ahora es editar un solo sitio.
   El único consumidor de PFX es un manejador de clic, que se ejecuta mucho después de que
   ambas declaraciones hayan corrido, así que el orden no importa. */

rauOn('pfx-search','click',()=>{
	const raw=document.getElementById('pfx-input').value.trim().toUpperCase();
	let found=null,key='';
	for(let l=4;l>=1;l--){
		const try_=raw.slice(0,l);
		if(PFX[try_]){found=PFX[try_];key=try_;break;}
	}
	document.getElementById('pfx-result').classList.add('hidden');
	document.getElementById('pfx-notfound').classList.add('hidden');
	if(!found){document.getElementById('pfx-notfound').classList.remove('hidden');return;}
	const parts=found.split('|');
	const flagName=parts[0].split(' ');
	document.getElementById('pfx-flag').textContent=flagName[0];
	document.getElementById('pfx-country').textContent=flagName.slice(1).join(' ');
	document.getElementById('pfx-prefix').textContent=key;
	document.getElementById('pfx-cont').textContent=parts[1];
	document.getElementById('pfx-itu').textContent=parts[2];
	document.getElementById('pfx-cq').textContent=parts[3];
	document.getElementById('pfx-result').classList.remove('hidden');
});
rauOn('pfx-input','keydown',e=>{if(e.key==='Enter')document.getElementById('pfx-search').click();});
rauOn('pfx-input','input',function(){this.value=this.value.toUpperCase();});

/* ── LOCALIZADOR DE INDICATIVO (país + azimut/distancia automáticos desde Medellín) ── */

const RAU_QTH=(function(){
	try {
		var g=JSON.parse(localStorage.getItem('rau_mi_qth')||'null');
		if(g && typeof g.lat==='number' && typeof g.lng==='number') return g;
	} catch(e){}
	return {lat:6.2442,lng:-75.5812}; // Medellín, Colombia — fallback existente
})();

function locBearingDist(o,d){ return rauDistanciaRumbo(o.lat,o.lng,d.lat,d.lng); }

function locRun(){
	const raw=document.getElementById('loc-input').value.trim().toUpperCase();
	let found=null,key='';
	for(let l=4;l>=1;l--){
		const try_=raw.slice(0,l);
		if(PFX_GEO[try_]){found=PFX_GEO[try_];key=try_;break;}
	}
	document.getElementById('loc-result').classList.add('hidden');
	document.getElementById('loc-notfound').classList.add('hidden');
	document.getElementById('loc-self').classList.add('hidden');
	if(!raw){return;}
	if(!found){document.getElementById('loc-notfound').classList.remove('hidden');return;}

	const parts=found.split('|');
	const flagName=parts[0].split(' ');
	const destLat=parseFloat(parts[4]),destLng=parseFloat(parts[5]);

	// Si el prefijo es colombiano, no tiene sentido calcular distancia contra nosotros mismos
	if(key==='HK'||key==='HJ'||key==='HK0'||key==='5K'){
		document.getElementById('loc-self').classList.remove('hidden');
		return;
	}

	const r=locBearingDist(RAU_QTH,{lat:destLat,lng:destLng});
	const dirs=['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSO','SO','OSO','O','ONO','NO','NNO'];
	const dir=dirs[Math.round(r.az/22.5)%16];
	const azlp=(r.az+180)%360;

	document.getElementById('loc-flag').textContent=flagName[0];
	document.getElementById('loc-country').textContent=flagName.slice(1).join(' ');
	document.getElementById('loc-prefix').textContent=key;
	document.getElementById('loc-dist').textContent=Math.round(r.dist).toLocaleString('es-CO')+' km';
	document.getElementById('loc-az').textContent=r.az.toFixed(1)+'°';
	document.getElementById('loc-dir').textContent=dir;
	document.getElementById('loc-azlp').textContent=azlp.toFixed(1)+'°';
	document.getElementById('loc-cont').textContent=parts[1];
	document.getElementById('loc-itu').textContent=parts[2];
	document.getElementById('loc-cq').textContent=parts[3];
	document.getElementById('loc-result').classList.remove('hidden');
}
rauOn('loc-search','click',locRun);
rauOn('loc-input','keydown',e=>{if(e.key==='Enter')locRun();});
rauOn('loc-input','input',function(){this.value=this.value.toUpperCase();});

/* ── CTCSS TABLE ── */
(function(){
	const tones=[67.0,71.9,74.4,77.0,79.7,82.5,85.4,88.5,91.5,94.8,97.4,100.0,103.5,107.2,110.9,114.8,118.8,123.0,127.3,131.8,136.5,141.3,146.2,151.4,156.7,162.2,167.9,173.8,179.9,186.2,192.8,203.5,210.7,218.1,225.7,233.6,241.8,250.3];
	const tb=document.getElementById('ctcss-table');
	let html='';
	for(let i=0;i<tones.length;i+=3){
		html+='<tr class="border-b border-border/50 last:border-0">';
		for(let j=0;j<3;j++){
			const idx=i+j, tone=tones[idx];
			if(tone!==undefined){
				const highlight=tone===100.0?' class="text-primary font-bold"':'';
				html+=`<td class="px-3 py-1.5 font-mono text-xs text-muted-foreground">${idx+1}</td><td${highlight} class="px-3 py-1.5 font-mono text-xs">${tone.toFixed(1)}</td>`;
			} else html+='<td></td><td></td>';
		}
		html+='</tr>';
	}
	tb.innerHTML=html;
})();


/* ── CW PLAYER ── */
(function(){
	const CODE=MORSE;
	const table=document.getElementById('cw-table');
	if(table){
		let html='';
		Object.entries(CODE).forEach(([k,v])=>{if(k!==' ')html+=`<div class="flex gap-2 rounded px-2 py-1 border border-border/40 bg-background"><span class="font-bold text-primary w-4">${k}</span><span class="text-muted-foreground">${v}</span></div>`;});
		table.innerHTML=html;
	}

	document.getElementById('cw-wpm').addEventListener('input',function(){document.getElementById('cw-wpm-val').textContent=this.value;});
	document.getElementById('cw-freq').addEventListener('input',function(){document.getElementById('cw-freq-val').textContent=this.value;});

	let ctx=null,playing=false,stopReq=false;
	document.getElementById('cw-play').addEventListener('click',async()=>{
		if(playing){return;}
		const text=document.getElementById('cw-text').value.toUpperCase()||'HK4RAU DE HK4RAU AR';
		const wpm=parseInt(document.getElementById('cw-wpm').value)||15;
		const freq=parseInt(document.getElementById('cw-freq').value)||600;
		const dot=1.2/wpm;
		ctx=new (window.AudioContext||window.webkitAudioContext)();
		playing=true; stopReq=false;
		const disp=document.getElementById('cw-display');
		disp.textContent='';
		const beep=async(dur)=>{
			const osc=ctx.createOscillator(),gain=ctx.createGain();
			osc.connect(gain);gain.connect(ctx.destination);
			osc.frequency.value=freq;
			gain.gain.setValueAtTime(0,ctx.currentTime);
			gain.gain.linearRampToValueAtTime(0.5,ctx.currentTime+0.005);
			gain.gain.linearRampToValueAtTime(0.5,ctx.currentTime+dur-0.005);
			gain.gain.linearRampToValueAtTime(0,ctx.currentTime+dur);
			osc.start(ctx.currentTime); osc.stop(ctx.currentTime+dur);
			await new Promise(r=>setTimeout(r,dur*1000));
		};
		const pause=async(dur)=>new Promise(r=>setTimeout(r,dur*1000));
		for(const char of text){
			if(stopReq) break;
			const morse=CODE[char];
			if(!morse) continue;
			disp.textContent+=char;
			if(char===' '){await pause(dot*7);continue;}
			for(const sym of morse){
				if(stopReq) break;
				if(sym==='.') await beep(dot);
				else if(sym==='-') await beep(dot*3);
				await pause(dot);
			}
			await pause(dot*2);
		}
		playing=false;
		if(ctx){ctx.close();ctx=null;}
	});
	document.getElementById('cw-stop').addEventListener('click',()=>{stopReq=true;playing=false;if(ctx){ctx.close();ctx=null;}});
})();

})();


/* ── LÍNEA DE TRANSMISIÓN ── */
rauOn('lt-calc','click',function(){
	var baseLoss=parseFloat(document.getElementById('lt-cable').value)||0.177;
	var freq=parseFloat(document.getElementById('lt-freq').value)||146;
	var len=parseFloat(document.getElementById('lt-long').value)||20;
	var pot=parseFloat(document.getElementById('lt-pot').value)||100;
	var freqFactor=Math.sqrt(freq/100);
	var totalLoss=baseLoss*freqFactor*len;
	var potOut=pot*Math.pow(10,-totalLoss/10);
	var pct=(potOut/pot*100);
	var col=totalLoss<1?'text-emerald-400':totalLoss<3?'text-primary':totalLoss<6?'text-amber-400':'text-rose-400';
	document.getElementById('lt-result').innerHTML='<p class="mb-4 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Resultado</p>'
		+'<div class="space-y-3">'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Pérdida total</span><span class="font-mono font-bold '+col+'">'+totalLoss.toFixed(2)+' dB</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Potencia salida</span><span class="font-mono font-bold text-foreground">'+potOut.toFixed(1)+' W</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Eficiencia</span><span class="font-mono font-bold text-foreground">'+pct.toFixed(1)+'%</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Pérdida por metro</span><span class="font-mono text-sm text-muted-foreground">'+(baseLoss*freqFactor).toFixed(4)+' dB/m</span></div>'
		+'</div>'
		+(totalLoss>3?'<div class="mt-4 rounded-lg border border-amber-400/30 bg-amber-400/5 px-3 py-2 text-xs text-amber-400">Pérdida significativa. Considera un cable de menor pérdida o acortar la línea.</div>':'')
		+(totalLoss<=1?'<div class="mt-4 rounded-lg border border-emerald-400/30 bg-emerald-400/5 px-3 py-2 text-xs text-emerald-400">Excelente. Pérdida bajo 1 dB.</div>':'');
});

/* ── ENLACE REPETIDOR ── */
rauOn('el-calc','click',function(){
	var ptx=parseFloat(document.getElementById('el-ptx').value)||5;
	var gtx=parseFloat(document.getElementById('el-gtx').value)||2.15;
	var ltx=parseFloat(document.getElementById('el-ltx').value)||0.5;
	var grx=parseFloat(document.getElementById('el-grx').value)||7;
	var sens=parseFloat(document.getElementById('el-sens').value)||-120;
	var freq=parseFloat(document.getElementById('el-freq2').value)||146.675;
	var dist=parseFloat(document.getElementById('el-dist').value)||15;
	var fspl=20*Math.log10(dist)+20*Math.log10(freq)+32.44;
	var prx=10*Math.log10(ptx*1000)+gtx-ltx-fspl+grx;
	var margin=prx-sens;
	var ok=margin>0;
	var col=margin>20?'text-emerald-400':margin>10?'text-primary':margin>0?'text-amber-400':'text-rose-400';
	document.getElementById('el-result').innerHTML='<p class="mb-4 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Presupuesto de enlace</p>'
		+'<div class="space-y-3">'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">FSPL (espacio libre)</span><span class="font-mono text-sm text-muted-foreground">'+fspl.toFixed(1)+' dB</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Potencia recibida</span><span class="font-mono font-bold text-foreground">'+prx.toFixed(1)+' dBm</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Sensibilidad requerida</span><span class="font-mono text-sm text-muted-foreground">'+sens+' dBm</span></div>'
		+'<div class="flex justify-between border-t border-border/50 pt-3"><span class="text-sm font-semibold">Margen de enlace</span><span class="font-mono font-bold '+col+'">'+margin.toFixed(1)+' dB</span></div>'
		+'</div>'
		+'<div class="mt-4 rounded-lg border px-4 py-3 text-sm font-semibold '+(ok?'border-emerald-400/30 bg-emerald-400/5 text-emerald-400':'border-rose-400/30 bg-rose-400/5 text-rose-400')+'">'+(ok?'El enlace es viable':'El enlace NO es viable con estos parámetros')+'</div>';
});

/* ── AZIMUTH ── */
function mhCenter(g){
	// Delega en rauGridACoords — esta herramienta exige 6 caracteres, así que se mantiene ese requisito
	g=(g||'').toUpperCase().trim(); if(g.length<6) return null;
	return rauGridACoords(g);
}
rauOn('az-calc','click',function(){
	var o=mhCenter(document.getElementById('az-origen').value);
	var d=mhCenter(document.getElementById('az-destino').value);
	if(!o||!d){document.getElementById('az-result').innerHTML='<p class="text-rose-400 text-sm">Ingresa grid squares completos de 6 caracteres (ej. FJ26ff).</p>';return;}
	var _dr=rauDistanciaRumbo(o.lat,o.lng,d.lat,d.lng);
	var dist=_dr.dist, az=_dr.az;
	var dirs=['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSO','SO','OSO','O','ONO','NO','NNO'];
	var dir=dirs[Math.round(az/22.5)%16];
	document.getElementById('az-result').innerHTML='<p class="mb-4 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Resultado</p>'
		+'<div class="space-y-4">'
		+'<div class="flex justify-between items-center"><span class="text-sm text-muted-foreground">Distancia</span><span class="font-mono text-3xl font-bold text-primary">'+Math.round(dist)+' km</span></div>'
		+'<div class="flex justify-between items-center"><span class="text-sm text-muted-foreground">Azimuth</span><span class="font-mono text-3xl font-bold text-foreground">'+az.toFixed(1)+'°</span></div>'
		+'<div class="flex justify-between items-center"><span class="text-sm text-muted-foreground">Dirección cardinal</span><span class="font-mono text-xl font-bold text-foreground">'+dir+'</span></div>'
		+'</div>';
});
['az-origen','az-destino'].forEach(function(id){document.getElementById(id).addEventListener('input',function(){this.value=this.value.toUpperCase();});});

/* ── COBERTURA DE REPETIDOR (horizonte de radio geométrico) ── */
(function(){
	var selectEl=document.getElementById('cov-repetidor');
	if(!selectEl) return;

	var REPEATERS = <?php echo wp_json_encode( rau_get_repeaters_for_coverage() ); ?>;

	if(!REPEATERS.length){
		document.getElementById('cov-empty').classList.remove('hidden');
		document.getElementById('cov-calc').disabled=true;
	} else {
		selectEl.innerHTML=REPEATERS.map(function(r,i){
			return '<option value="'+i+'">'+r.name+(r.callsign?' — '+r.callsign:'')+'</option>';
		}).join('');
	}

	// Delega en rauGridACoords (definida junto a la herramienta Maidenhead)
	function covGridCenter(g){ return rauGridACoords(g); }

	function covDistanceKm(o,d){ return rauDistanciaRumbo(o.lat,o.lng,d.lat,d.lng).dist; }

	function radioHorizonKm(alturaM){
		return 4.12*Math.sqrt(Math.max(alturaM,0)); // horizonte de RADIO (modelo Tierra 4/3, ITU-R P.452) — no confundir con el óptico (3.57)
	}

	document.getElementById('cov-gps').addEventListener('click',function(){
		if(!navigator.geolocation){alert('Tu navegador no soporta geolocalización.');return;}
		navigator.geolocation.getCurrentPosition(function(pos){
			// Reutiliza latLngToMH si ya existe en la página (pestaña Maidenhead); si no, deja lat/lng crudos.
			if(typeof latLngToMH==='function'){
				document.getElementById('cov-grid').value=latLngToMH(pos.coords.latitude,pos.coords.longitude,6);
			} else {
				document.getElementById('cov-grid').value=pos.coords.latitude.toFixed(4)+','+pos.coords.longitude.toFixed(4);
			}
		},function(){alert('No se pudo obtener la ubicación.');});
	});
	document.getElementById('cov-grid').addEventListener('input',function(){this.value=this.value.toUpperCase();});

	document.getElementById('cov-calc').addEventListener('click',function(){
		if(!REPEATERS.length) return;
		var raw=document.getElementById('cov-grid').value.trim();
		var origen=null;
		if(raw.indexOf(',')>-1){
			var parts=raw.split(',');
			origen={lat:parseFloat(parts[0]),lng:parseFloat(parts[1])};
		} else {
			origen=covGridCenter(raw);
		}
		if(!origen || isNaN(origen.lat)){
			alert('Ingresa un grid square válido (ej. FJ26ff) o usa el botón GPS.');
			return;
		}
		var altura=parseFloat(document.getElementById('cov-altura').value)||10;
		var rep=REPEATERS[parseInt(selectEl.value)||0]; /* 0 legítimo: índice de <select> */

		var dist=covDistanceKm(origen,{lat:rep.lat,lng:rep.lng});
		var h1=radioHorizonKm(altura);
		var h2=radioHorizonKm(rep.altura);
		var horizonte=h1+h2;
		var ok=dist<=horizonte;

		document.getElementById('cov-dist').textContent=dist.toFixed(1)+' km';
		document.getElementById('cov-horizonte').textContent=horizonte.toFixed(1)+' km';
		document.getElementById('cov-h1').textContent=h1.toFixed(1)+' km';
		document.getElementById('cov-h2').textContent=h2.toFixed(1)+' km';
		document.getElementById('cov-altrep').textContent=rep.altura+' m'+(rep.altura===30?' (estimado, sin dato registrado)':'');

		var veredicto=document.getElementById('cov-veredicto');
		if(ok){
			veredicto.className='rounded-lg border px-4 py-3 text-sm font-semibold border-emerald-400/30 bg-emerald-400/5 text-emerald-400';
			veredicto.textContent='✅ Estás dentro del horizonte de radio teórico. Probablemente alcances este repetidor, salvo obstrucción del terreno.';
		} else {
			veredicto.className='rounded-lg border px-4 py-3 text-sm font-semibold border-rose-400/30 bg-rose-400/5 text-rose-400';
			veredicto.textContent='⚠️ Estás fuera del horizonte de radio teórico ('+(dist-horizonte).toFixed(0)+' km de más). Es poco probable que alcances este repetidor desde tu QTH actual.';
		}
		document.getElementById('cov-result').classList.remove('hidden');
	});
})();

/* ── DOPPLER ── */
(function(){
	var freqEl=document.getElementById('dop-freq'), velEl=document.getElementById('dop-vel'),
		velVal=document.getElementById('dop-vel-val'), upEl=document.getElementById('dop-up'),
		downEl=document.getElementById('dop-down'), deltaEl=document.getElementById('dop-delta');
	if(!freqEl) return;
	var C=299792.458; // km/s

	function calc(){
		var f=parseFloat(freqEl.value), v=parseFloat(velEl.value);
		if(isNaN(f)||isNaN(v)) return;
		velVal.textContent=v.toFixed(1);
		var up=f*(1+v/C), down=f*(1-v/C);
		upEl.textContent=up.toFixed(4)+' MHz';
		downEl.textContent=down.toFixed(4)+' MHz';
		deltaEl.textContent='± '+((up-f)*1000).toFixed(2)+' kHz';
	}
	freqEl.addEventListener('input',calc);
	velEl.addEventListener('input',calc);
	calc();
})();

/* ── PREDICCIÓN DE PASADAS (satellite.js + TLE de CelesTrak) ── */
(function(){
	var satSelect=document.getElementById('sp-sat');
	if(!satSelect) return;

	var statusEl=document.getElementById('sp-status');
	var resultsEl=document.getElementById('sp-results');
	var tbody=document.getElementById('sp-tbody');
	var TLES=[];

	var PALABRAS_CLAVE=['SO-50','SAUDISAT','AO-91','RADFXSAT','RS-44','RS44','DOSAAF','AO-92','AO-123','ASRTU','ISS','ZARYA','PO-101','DIWATA',
		'CAS-3H','LILACSAT','IO-86','LAPAN','RS95S','QMR-KWT','TEVEL'];

	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_tle', { cache: 'no-store' })
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success || !json.data || !json.data.length){
				statusEl.textContent='No se pudieron cargar los datos orbitales de CelesTrak. Intenta de nuevo más tarde.';
				return;
			}
			var todos=json.data;
			var filtrados=todos.filter(function(s){
				var n=s.name.toUpperCase();
				return PALABRAS_CLAVE.some(function(k){ return n.indexOf(k)>-1; });
			});
			TLES = filtrados.length ? filtrados : todos.slice(0,60);

			satSelect.innerHTML=TLES.map(function(s,i){ return '<option value="'+i+'">'+s.name+'</option>'; }).join('');
			statusEl.textContent='Listo — '+TLES.length+' satélite(s) cargado(s). Ingresa tu grid y calcula.';
		}).catch(function(e){ rauErr(e);
			statusEl.textContent='No se pudieron cargar los datos orbitales. Revisa tu conexión e intenta de nuevo.';
		});

	// Delega en rauGridACoords (definida junto a la herramienta Maidenhead)
	function gridCenter(g){ return rauGridACoords(g); }

	document.getElementById('sp-gps').addEventListener('click',function(){
		if(!navigator.geolocation){ alert('Tu navegador no soporta geolocalización.'); return; }
		navigator.geolocation.getCurrentPosition(function(pos){
			if(typeof latLngToMH==='function'){
				document.getElementById('sp-grid').value=latLngToMH(pos.coords.latitude,pos.coords.longitude,6);
			} else {
				document.getElementById('sp-grid').value=pos.coords.latitude.toFixed(4)+','+pos.coords.longitude.toFixed(4);
			}
		},function(){ alert('No se pudo obtener la ubicación.'); });
	});
	document.getElementById('sp-grid').addEventListener('input',function(){this.value=this.value.toUpperCase();});

	function compass(az){
		var dirs=['N','NNE','NE','ENE','E','ESE','SE','SSE','S','SSO','SO','OSO','O','ONO','NO','NNO'];
		return dirs[Math.round(az/22.5)%16];
	}
	function fmtHora(d){
		return d.toLocaleDateString('es-CO',{day:'2-digit',month:'short'})+' '+d.toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'});
	}
	function fmtDur(ms){
		var min=Math.floor(ms/60000), seg=Math.round((ms%60000)/1000);
		return min+'m '+seg+'s';
	}

	document.getElementById('sp-calc').addEventListener('click',function(){
		if(typeof satellite==='undefined'){
			alert('La librería de cálculo orbital no cargó. Recarga la página e intenta de nuevo.');
			return;
		}
		if(!TLES.length){ alert('Todavía no hay satélites cargados.'); return; }

		var raw=document.getElementById('sp-grid').value.trim();
		var origen=null;
		if(raw.indexOf(',')>-1){
			var parts=raw.split(',');
			origen={lat:parseFloat(parts[0]),lng:parseFloat(parts[1])};
		} else {
			origen=gridCenter(raw);
		}
		if(!origen || isNaN(origen.lat)){
			alert('Ingresa un grid square válido (ej. FJ26ff) o usa el botón GPS.');
			return;
		}

		var sat=TLES[parseInt(satSelect.value)||0]; /* 0 legítimo: índice de <select> */
		var satrec=satellite.twoline2satrec(sat.line1, sat.line2);

		var observerGd={
			longitude: satellite.degreesToRadians(origen.lng),
			latitude:  satellite.degreesToRadians(origen.lat),
			height: 0.15
		};

		statusEl.textContent='Calculando…';
		resultsEl.classList.add('hidden');
		tbody.innerHTML='';

		// Escanea las próximas 48h en pasos de 30s. Suficiente para no perder
		// pasadas cortas y mantenerse liviano para el navegador.
		var ahora=new Date();
		var pasoMs=30*1000;
		var totalPasos=Math.floor(48*60*60*1000/pasoMs);
		var pasadas=[];
		var enPasada=false, actual=null;

		for(var i=0;i<totalPasos && pasadas.length<8;i++){
			var t=new Date(ahora.getTime()+i*pasoMs);
			var pv=satellite.propagate(satrec, t);
			if(!pv || !pv.position) continue; // satélite decayó / TLE inválido para esta fecha
			var gmst=satellite.gstime(t);
			var posEcf=satellite.eciToEcf(pv.position, gmst);
			var look=satellite.ecfToLookAngles(observerGd, posEcf);
			var elDeg=satellite.radiansToDegrees(look.elevation);
			var azDeg=satellite.radiansToDegrees(look.azimuth);
			if(azDeg<0) azDeg+=360;

			if(elDeg>=0 && !enPasada){
				enPasada=true;
				actual={aos:t, azAos:azDeg, maxEl:elDeg, azMaxEl:azDeg};
			} else if(elDeg>=0 && enPasada){
				if(elDeg>actual.maxEl){ actual.maxEl=elDeg; actual.azMaxEl=azDeg; }
			} else if(elDeg<0 && enPasada){
				enPasada=false;
				actual.los=t; actual.azLos=azDeg;
				pasadas.push(actual);
				actual=null;
			}
		}

		if(!pasadas.length){
			statusEl.textContent='No se encontraron pasadas visibles en las próximas 48h para este satélite desde tu ubicación (o el TLE no es válido para este rango de fechas).';
			return;
		}

		tbody.innerHTML=pasadas.map(function(p){
			return '<tr class="hover:bg-muted/10">'
				+'<td class="px-4 py-3 font-mono">'+fmtHora(p.aos)+'</td>'
				+'<td class="px-4 py-3 font-mono text-xs">'+p.azAos.toFixed(0)+'° '+compass(p.azAos)+'</td>'
				+'<td class="px-4 py-3 font-mono font-semibold text-primary">'+p.maxEl.toFixed(0)+'°</td>'
				+'<td class="px-4 py-3 font-mono">'+fmtHora(p.los)+'</td>'
				+'<td class="px-4 py-3 font-mono text-xs">'+p.azLos.toFixed(0)+'° '+compass(p.azLos)+'</td>'
				+'<td class="px-4 py-3 font-mono text-xs">'+fmtDur(p.los-p.aos)+'</td>'
				+'</tr>';
		}).join('');

		statusEl.textContent=pasadas.length+' pasada(s) encontrada(s) en las próximas 48h · Hora local de tu navegador.';
		resultsEl.classList.remove('hidden');
	});
})();


/* ── DASHBOARD EN VIVO ── */
(function(){
	var clockEl=document.getElementById('dash-utc-time');
	if(!clockEl) return; // el panel no está en esta página

	function pad(n){ return String(n).padStart(2,'0'); }
	function tick(){
		var now=new Date();
		document.getElementById('dash-utc-time').textContent = pad(now.getUTCHours())+':'+pad(now.getUTCMinutes())+':'+pad(now.getUTCSeconds());
		var col=new Date(now.getTime()-5*3600000);
		document.getElementById('dash-local-time').textContent = pad(col.getUTCHours())+':'+pad(col.getUTCMinutes())+':'+pad(col.getUTCSeconds());
		var dias=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'], meses=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
		document.getElementById('dash-date').textContent = dias[now.getUTCDay()]+' '+pad(now.getUTCDate())+' '+meses[now.getUTCMonth()]+' '+now.getUTCFullYear()+' · UTC';
	}
	tick(); setInterval(tick, 1000);

	/* ── Propagación + bandas (reutiliza el mismo proxy que Propagación Solar / Alertas de Banda) ── */
	function bandColorClasses(cond){
		if(/good/i.test(cond)) return 'text-emerald-400';
		if(/fair/i.test(cond)) return 'text-amber-400';
		return 'text-rose-400';
	}
	function loadSolar(){
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_solar', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(!json.success) throw 0;
				var d=json.data;
				document.getElementById('dash-sfi').textContent=d.sfi||'—';
				document.getElementById('dash-a').textContent=d.aindex||'—';
				document.getElementById('dash-k').textContent=d.kindex||'—';
				document.getElementById('dash-xray').textContent=d.xray||'—';
				var k=parseFloat(d.kindex);
				var estado = isNaN(k) ? 'Sin datos' : (k<=1?'Excelente para HF':k<=3?'Buenas condiciones':k<=4?'Perturbada':'Tormenta geomagnética');
				document.getElementById('dash-solar-status').textContent=estado+' · Actualizado '+(d.updated||'—');

				var bandsEl=document.getElementById('dash-bands');
				if(d.bands && d.bands.length){
					bandsEl.innerHTML=d.bands.map(function(b){
						return '<div class="rounded-lg border border-border/50 bg-background/50 px-2 py-2 text-center">'
							+'<p class="font-mono text-[11px] font-bold text-foreground">'+b.name+'</p>'
							+'<p class="mt-1 flex justify-center gap-2 font-mono text-[10px]">'
							+'<span class="'+bandColorClasses(b.day)+'">☀ '+b.day+'</span>'
							+'<span class="'+bandColorClasses(b.night)+'">☾ '+b.night+'</span>'
							+'</p></div>';
					}).join('');
				} else {
					bandsEl.innerHTML='<p class="col-span-2 text-center text-xs text-muted-foreground sm:col-span-4">Sin datos de banda en esta actualización.</p>';
				}
			}).catch(function(e){ rauErr(e);
				document.getElementById('dash-solar-status').textContent='No se pudo cargar la propagación.';
			});
	}
	loadSolar(); setInterval(loadSolar, 5*60*1000);

	/* ── KiwiSDR (reutiliza el mismo proxy que el widget del home) ── */
	function loadSDR(){
		var badge=document.getElementById('dash-sdr-badge');
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_sdr_status', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(json.success){
					badge.innerHTML='<span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span><span class="font-mono text-[10px] text-emerald-400">'+(json.data.libres===0?'Muy ocupado':'En línea'+(json.data.libres!=null?' · '+json.data.libres+' libres':''))+'</span>';
					var det=document.getElementById('dash-sdr-detalle');
					if(det) det.textContent = json.data.libres===0 ? 'Todos los canales en uso; a veces igual se consigue uno.' : (json.data.libres+' de '+json.data.max+' canales libres'+(json.data.snr_hf?' · SNR en HF '+json.data.snr_hf+' dB':''));
				} else {
					badge.innerHTML='<span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span><span class="font-mono text-[10px] text-rose-400">Sin conexión</span>';
					var det=document.getElementById('dash-sdr-detalle'); if(det) det.textContent='El receptor no responde en este momento.';
				}
			}).catch(function(e){ rauErr(e);
				badge.innerHTML='<span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span><span class="font-mono text-[10px] text-rose-400">Sin conexión</span>';
			});
	}
	loadSDR(); setInterval(loadSDR, 2*60*1000);

	/* ── YouTube en vivo (mismo proxy que el badge del header) ── */
	function loadYT(){
		var badge=document.getElementById('dashYtLiveBadge');
		if(!badge) return;
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_yt_live', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(json.success && json.data && json.data.live){
					badge.href=json.data.url;
					badge.classList.remove('hidden');
					badge.classList.add('inline-flex');
				} else {
					badge.classList.add('hidden');
					badge.classList.remove('inline-flex');
				}
			}).catch(function(e){ rauErr(e);});
	}
	loadYT(); setInterval(loadYT, 5*60*1000);

	/* ── Próxima pasada de la ISS sobre Medellín (usa satellite.js, cargado diferido al abrir esta pestaña) ── */
	var RAU_QTH=(function(){
		try {
			var g=JSON.parse(localStorage.getItem('rau_mi_qth')||'null');
			if(g && typeof g.lat==='number' && typeof g.lng==='number') return g;
		} catch(e){}
		return {lat:6.2442,lng:-75.5812};
	})();
	var issCalculado=false;
	function loadISS(){
		if(typeof satellite==='undefined' || issCalculado) return;
		issCalculado=true;
		var el=document.getElementById('dash-iss-content');
		fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_tle', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){
				if(!json.success || !json.data) throw 0;
				var iss=json.data.find(function(s){ return /ISS|ZARYA/i.test(s.name); });
				if(!iss) throw 0;

				var satrec=satellite.twoline2satrec(iss.line1, iss.line2);
				var observerGd={longitude:satellite.degreesToRadians(RAU_QTH.lng), latitude:satellite.degreesToRadians(RAU_QTH.lat), height:0.15};
				var ahora=new Date(), pasoMs=30000, encontrado=null, enPasada=false, actual=null;

				for(var i=0;i<Math.floor(48*60*60*1000/pasoMs) && !encontrado;i++){
					var t=new Date(ahora.getTime()+i*pasoMs);
					var pv=satellite.propagate(satrec, t);
					if(!pv || !pv.position) continue;
					var gmst=satellite.gstime(t);
					var posEcf=satellite.eciToEcf(pv.position, gmst);
					var look=satellite.ecfToLookAngles(observerGd, posEcf);
					var elDeg=satellite.radiansToDegrees(look.elevation);
					if(elDeg>=0 && !enPasada){ enPasada=true; actual={aos:t, maxEl:elDeg}; }
					else if(elDeg>=0 && enPasada){ if(elDeg>actual.maxEl) actual.maxEl=elDeg; }
					else if(elDeg<0 && enPasada){ actual.los=t; encontrado=actual; }
				}

				if(!encontrado){ el.innerHTML='<p class="text-center text-xs text-muted-foreground">Sin pasadas visibles en 48h.</p>'; return; }

				el.innerHTML='<p class="iss-countdown font-mono text-2xl font-bold text-primary text-center">—</p>'
					+'<p class="mt-1 text-center font-mono text-[10px] uppercase tracking-wider text-muted-foreground">hasta el AOS</p>'
					+'<div class="mt-3 flex justify-between font-mono text-xs"><span class="text-muted-foreground">Elev. máx.</span><span class="font-bold text-foreground">'+encontrado.maxEl.toFixed(0)+'°</span></div>'
					+'<div class="mt-1 flex justify-between font-mono text-xs"><span class="text-muted-foreground">Hora AOS</span><span class="font-bold text-foreground">'+encontrado.aos.toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'})+'</span></div>';

				var idIntervaloCountdown=null;
				function actualizarCountdown(){
					var span=el.querySelector('.iss-countdown');
					if(!span) return;
					var ms=encontrado.aos-new Date();
					if(ms<=0){
						if(ms < -20*60*1000){
							// Ya pasaron más de 20 min desde el AOS (ninguna pasada dura tanto) —
							// detener este intervalo (su cierre quedó con datos obsoletos) y recalcular
							// la siguiente pasada, en vez de quedarse en "¡Pasando ahora!" para siempre.
							if(idIntervaloCountdown) clearInterval(idIntervaloCountdown);
							issCalculado=false;
							loadISS();
							return;
						}
						span.textContent='¡Pasando ahora!';
						return;
					}
					var h=Math.floor(ms/3600000), m=Math.floor((ms%3600000)/60000), s=Math.floor((ms%60000)/1000);
					span.textContent=(h>0?h+'h ':'')+m+'m '+s+'s';
				}
				idIntervaloCountdown=setInterval(actualizarCountdown, 1000);
				actualizarCountdown();
			}).catch(function(e){ rauErr(e);
				el.innerHTML='<p class="text-center text-xs text-muted-foreground">No se pudo calcular la próxima pasada.</p>';
			});
	}
	// Si satellite.js ya está listo (por ejemplo, se abrió antes Predicción de Pasadas), calcula de una.
	// Si no, queda a la espera de que la carga diferida avise que ya está lista.
	if(typeof satellite!=='undefined') loadISS();
	var prevSatReady = window.rauOnSatReady;
	window.rauOnSatReady = function(){
		if(typeof prevSatReady==='function') prevSatReady();
		loadISS();
	};
})();

/* ── CERTIFICADO DE PARTICIPACIÓN (canvas, mismo patrón del QSL) ── */
(function(){
	var canvas=document.getElementById('cert-canvas');
	if(!canvas) return;
	var ctx2=canvas.getContext('2d');
	var logo=new Image();
	logo.src='<?php echo esc_url( get_template_directory_uri() . "/assets/images/rau-badge.webp" ); ?>';
	logo.onload=function(){ drawCert(); };

	function drawCert(){
		var nombre=document.getElementById('cert-nombre').value||'Nombre del Participante';
		var indicativo=document.getElementById('cert-indicativo').value.toUpperCase();
		var actividad=document.getElementById('cert-actividad').value||'Actividad de RAU';
		var fecha=document.getElementById('cert-fecha').value;
		var fechaTxt=fecha ? new Date(fecha+'T00:00:00').toLocaleDateString('es-CO',{day:'numeric',month:'long',year:'numeric'}) : new Date().toLocaleDateString('es-CO',{day:'numeric',month:'long',year:'numeric'});

		var W=600,H=424;
		ctx2.setTransform(2,0,0,2,0,0);
		ctx2.clearRect(0,0,W,H);

		var grad=ctx2.createLinearGradient(0,0,W,H);
		grad.addColorStop(0,'#0d1321');grad.addColorStop(1,'#080c16');
		ctx2.fillStyle=grad;ctx2.fillRect(0,0,W,H);

		ctx2.strokeStyle='#1fd5f9';ctx2.lineWidth=3;
		ctx2.strokeRect(14,14,W-28,H-28);
		ctx2.strokeStyle='rgba(31,213,249,0.35)';ctx2.lineWidth=1;
		ctx2.strokeRect(22,22,W-44,H-44);

		ctx2.textAlign='center';
		ctx2.fillStyle='#8899aa';ctx2.font='11px monospace';
		ctx2.fillText('RADIOAFICIONADOS UNIDOS · HK4RAU · MEDELLÍN, COLOMBIA',W/2,55);

		ctx2.fillStyle='#1fd5f9';ctx2.font='bold 30px Georgia, serif';
		ctx2.fillText('CERTIFICADO DE PARTICIPACIÓN',W/2,95);

		ctx2.fillStyle='#8899aa';ctx2.font='italic 13px Georgia, serif';
		ctx2.fillText('Se otorga el presente certificado a',W/2,145);

		ctx2.fillStyle='#ffffff';ctx2.font='bold 34px Georgia, serif';
		ctx2.fillText(nombre,W/2,190);
		if(indicativo){
			ctx2.fillStyle='#1fd5f9';ctx2.font='bold 15px monospace';
			ctx2.fillText(indicativo,W/2,213);
		}

		ctx2.fillStyle='#8899aa';ctx2.font='italic 13px Georgia, serif';
		ctx2.fillText('por su participación en',W/2,250);

		ctx2.fillStyle='#e2e8f0';ctx2.font='bold 20px Georgia, serif';
		var actLines=wrapText(actividad, 44);
		actLines.forEach(function(line,i){ ctx2.fillText(line,W/2,278+i*24); });

		ctx2.fillStyle='#8899aa';ctx2.font='12px monospace';
		ctx2.fillText(fechaTxt,W/2,H-55);

		if(logo.complete && logo.naturalWidth){
			var lw=60,lh=lw*(logo.naturalHeight/logo.naturalWidth);
			ctx2.save();ctx2.globalAlpha=0.9;
			ctx2.drawImage(logo,W/2-lw/2,H-45,lw,lh);
			ctx2.restore();
		}

		document.getElementById('cert-download').classList.remove('hidden');
	}

	function wrapText(texto, maxChars){
		var palabras=texto.split(' '), lineas=[], actual='';
		palabras.forEach(function(p){
			if((actual+' '+p).trim().length>maxChars){ lineas.push(actual.trim()); actual=p; }
			else { actual=(actual+' '+p).trim(); }
		});
		if(actual) lineas.push(actual);
		return lineas.slice(0,2);
	}

	['cert-nombre','cert-indicativo','cert-actividad','cert-fecha'].forEach(function(id){
		document.getElementById(id).addEventListener('input', drawCert);
	});

	document.getElementById('cert-download').addEventListener('click', function(){
		var nombre=document.getElementById('cert-nombre').value||'certificado';
		var a=document.createElement('a');
		a.download='Certificado-RAU-'+nombre.replace(/\s+/g,'-')+'.png';
		a.href=canvas.toDataURL('image/png');
		a.click();
	});

	drawCert();
})();

/* ── REPORTES RST ── */
function updateRST(){
	var r=document.getElementById('rst-r').value;
	var s=document.getElementById('rst-s').value;
	var t=document.getElementById('rst-t').value;
	document.getElementById('rst-fonia').textContent=r+''+s;
	document.getElementById('rst-cw').textContent=r+''+s+''+t;
}
['rst-r','rst-s','rst-t'].forEach(function(id){document.getElementById(id).addEventListener('change',updateRST);});

/* ── CÓDIGO Q ── */
var CODIGOS_Q=[
	['QRG','¿Cuál es mi frecuencia exacta?','Mi frecuencia exacta es… kHz/MHz'],
	['QRK','¿Cuál es la inteligibilidad de mis señales?','La inteligibilidad es 1 (mala) a 5 (excelente)'],
	['QRL','¿Estás ocupado? ¿Está en uso esta frecuencia?','Estoy ocupado / La frecuencia está en uso'],
	['QRM','¿Me causas interferencia?','Me causas interferencia (1 nada – 5 extrema)'],
	['QRN','¿Te molestan las atmosféricas?','Me molestan las atmosféricas (1 nada – 5 extrema)'],
	['QRO','¿Debo aumentar la potencia?','Aumenta tu potencia de transmisión'],
	['QRP','¿Debo reducir la potencia?','Reduce tu potencia / Estación de baja potencia'],
	['QRQ','¿Debo transmitir más rápido?','Transmite más rápido'],
	['QRS','¿Debo transmitir más despacio?','Transmite más despacio'],
	['QRT','¿Debo dejar de transmitir?','Deja de transmitir / Cierro la estación'],
	['QRU','¿Tienes algo para mí?','No tengo nada para ti'],
	['QRV','¿Estás listo?','Estoy listo para recibir'],
	['QRX','¿Cuándo me llamarás de nuevo?','Te llamaré a las … UTC / Espera un momento'],
	['QRZ','¿Quién me llama?','Te llama … / ¿Quién me llamó?'],
	['QSA','¿Cuál es la intensidad de mis señales?','La intensidad es 1 (apenas perceptible) a 5 (muy fuerte)'],
	['QSB','¿Varían mis señales?','Tus señales se desvanecen'],
	['QSL','¿Puedes confirmar recepción?','Confirmo recepción (también: tarjeta QSL)'],
	['QSO','¿Puedes comunicarte con …?','Puedo comunicarme con … / Contacto de radio'],
	['QSP','¿Puedes retransmitir a …?','Retransmitiré a … sin cargo'],
	['QSX','¿Puedes escuchar en …?','Escucho en … kHz/MHz'],
	['QSY','¿Debo cambiar de frecuencia?','Cambia a transmitir en … kHz/MHz'],
	['QTC','¿Tienes mensajes?','Tengo … mensajes para ti'],
	['QTH','¿Cuál es tu ubicación?','Mi ubicación es … (latitud/longitud o ciudad)'],
	['QTR','¿Cuál es la hora exacta?','La hora exacta es … UTC'],
];
(function(){
	var tbody=document.getElementById('cq-tbody');
	function render(filter){
		var html='';
		CODIGOS_Q.forEach(function(row){
			var match=!filter||row[0].toLowerCase().includes(filter)||row[1].toLowerCase().includes(filter)||row[2].toLowerCase().includes(filter);
			if(match) html+='<tr class="border-b border-border/50 last:border-0"><td class="px-3 py-2 font-mono text-sm font-bold text-primary">'+row[0]+'</td><td class="px-3 py-2 text-xs text-muted-foreground">'+row[1]+'</td><td class="px-3 py-2 text-xs text-muted-foreground">'+row[2]+'</td></tr>';
		});
		tbody.innerHTML=html||'<tr><td colspan="3" class="px-3 py-4 text-center text-sm text-muted-foreground">Sin resultados</td></tr>';
	}
	render('');
	document.getElementById('cq-search').addEventListener('input',function(){render(this.value.toLowerCase());});
})();

/* ── POTA Colombia (carga al activar pestaña) ── */
(function(){
	var loaded=false;
	var tabBtn=document.querySelector('[data-tab="pota"]');
	if(!tabBtn)return;
	tabBtn.addEventListener('click',function(){
		if(loaded)return; loaded=true;
		fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>?action=rau_pota_spots',{cache:'no-store'})
		.then(function(r){return r.json();})
		.then(function(json){
			if(!json.success) throw new Error('proxy_error');
			document.getElementById('pota-loading').classList.add('hidden');
			var hk=json.data.filter(function(s){return s.activator&&s.activator.toUpperCase().startsWith('HK');});
			if(!hk.length){document.getElementById('pota-empty').classList.remove('hidden');return;}
			var list=document.getElementById('pota-list');
			list.classList.remove('hidden');
			hk.forEach(function(s){
				var d=document.createElement('div');
				d.className='rounded-xl border border-border/70 bg-background p-4';
				d.innerHTML='<div class="flex flex-wrap items-start justify-between gap-2">'
					+'<div><p class="font-mono font-bold text-primary">'+s.activator+'</p>'
					+'<p class="text-sm text-muted-foreground">'+(s.reference||'')+' · '+(s.name||'')+'</p></div>'
					+'<div class="text-right"><p class="font-mono text-sm font-bold">'+s.frequency+' kHz</p>'
					+'<p class="font-mono text-xs text-muted-foreground">'+(s.mode||'')+'</p>'
					+(window.rauEscuchar && +s.frequency>0 && +s.frequency<30000 ? '<button type="button" class="rau-escuchar mt-1 font-mono text-xs text-primary hover:underline" data-khz="'+(+s.frequency)+'" data-modo="'+String(s.mode||'').replace(/[^A-Za-z0-9]/g,'')+'">🎧 Escuchar</button>' : '')
					+'</div></div>'
					+(s.comments?'<p class="mt-2 text-xs text-muted-foreground">'+s.comments+'</p>':'');
				list.appendChild(d);
			});
		})
		.catch(function(e){ rauErr(e);
			document.getElementById('pota-loading').innerHTML='<p class="text-sm text-muted-foreground">No se pudo cargar la API de POTA. Intenta más tarde.</p>';
		});
	});
})();

/* ── DX SPOTS ──
   El módulo que construía aquí una tabla de spots quedó huérfano: el panel #panel-dx se rehízo
   con un iframe de DXheat y los cuatro ids que este código manipulaba (dx-table-wrap, dx-empty,
   dx-tbody, dx-loading) ya no existen en el HTML. Seguía ejecutándose en cada carga de la página:
   lanzaba una petición a admin-ajax.php que nadie iba a usar y después reventaba al intentar
   pintar sobre elementos inexistentes, dejando rechazos de promesa sin atender en la consola.
   El panel "DX Spots en vivo" de la consola es otro módulo distinto y sigue intacto más abajo. */


/* ── SIMULADOR EXAMEN RABCA ── */
(function(){
	var PREGUNTAS={
		A:[
			{q:'¿Cuál es la frecuencia de llamada FM en VHF?',o:['144.000 MHz','146.520 MHz','146.675 MHz','147.000 MHz'],a:1,e:'146.520 MHz es la frecuencia de llamada simplex FM en VHF reconocida internacionalmente.'},
			{q:'¿Qué significa el código Q "QRZ"?',o:['La frecuencia está ocupada','¿Quién me llama?','Cambia de frecuencia','Detén tu transmisión'],a:1,e:'QRZ significa "¿Quién me llama?" y se usa cuando no se identificó la estación que llamó.'},
			{q:'¿Cuál es el prefijo del indicativo de Colombia?',o:['CO','HK','CL','HC'],a:1,e:'El prefijo HK identifica las estaciones de radioaficionados de Colombia según la UIT.'},
			{q:'¿Qué representa "QTH" en el código Q?',o:['La hora UTC','La frecuencia de operación','La ubicación de la estación','La potencia del transmisor'],a:2,e:'QTH significa cuál es tu ubicación y en respuesta indica el lugar donde está la estación.'},
			{q:'¿Qué significa "73" en radioafición?',o:['Frecuencia de emergencia','Mejores deseos','Fin de transmisión','Cambio de frecuencia'],a:1,e:'"73" es una abreviatura que significa "mejores deseos" y se usa al despedirse.'},
			{q:'¿Cuál es la banda de 40 metros en HF?',o:['3.5 – 4.0 MHz','7.0 – 7.3 MHz','14.0 – 14.35 MHz','21.0 – 21.45 MHz'],a:1,e:'La banda de 40 metros comprende 7.000 a 7.300 MHz y es muy usada para comunicación regional.'},
			{q:'¿Qué es un QSO?',o:['Un tipo de antena','Un contacto de radio','Un modo digital','Una tarjeta de confirmación'],a:1,e:'Un QSO es un contacto o comunicación establecida entre dos o más estaciones de radio.'},
			{q:'Trabajando fonía en 40 metros, ¿qué banda lateral corresponde por convención?',o:['USB','LSB','Indistinta, la que prefiera el operador','AM de doble banda lateral'],a:1,e:'Por convención se usa LSB por debajo de 10 MHz (160m, 80m, 40m) y USB por encima (20m y superiores, más toda VHF/UHF). 40m está en 7 MHz, así que va en LSB.'},
			{q:'¿Qué es una tarjeta QSL?',o:['Un tipo de antena','Confirmación escrita de un contacto','Una licencia de operación','Un modo digital'],a:1,e:'Una tarjeta QSL es la confirmación escrita (postal o electrónica) de un contacto de radio realizado.'},
			{q:'¿Qué departamentos cubre el dígito 4 en un indicativo colombiano (HK4)?',o:['Cundinamarca, Meta y Vichada','Antioquia y Chocó','Atlántico, Bolívar, Córdoba y Sucre','Valle del Cauca y Cauca'],a:1,e:'El artículo 35 del Decreto 963 de 2009, compilado en el 2.2.5.7.1 del Decreto 1078 de 2015, asigna la zona 4 a Antioquia y Chocó. Ojo con confundirla con el Eje Cafetero: Caldas, Risaralda y Quindío están en la zona 6, junto con Tolima y Huila. Las otras opciones son las zonas 3, 1 y 5 respectivamente.'},
		],
		B:[
			{q:'¿Cuál es la fórmula para la longitud de un dipolo λ/2?',o:['L = 300 / f','L = 142.5 / f(MHz)','L = 75 / f(MHz)','L = 234 / f(MHz)'],a:1,e:'La longitud en metros de un dipolo de media onda es ~142.5 dividido entre la frecuencia en MHz.'},
			{q:'¿Qué es el índice K en propagación solar?',o:['El flujo de radio del sol','El índice de actividad geomagnética','La temperatura de la ionosfera','El número de manchas solares'],a:1,e:'El índice K mide la actividad geomagnética en períodos de 3 horas. K=0-1 excelente, K≥5 tormenta.'},
			{q:'¿Qué es el CTCSS?',o:['Un tipo de antena','Un tono subaudible para acceso a repetidoras','Un modo de transmisión digital','Una codificación de voz'],a:1,e:'CTCSS son tonos subaudibles usados para abrir el squelch de repetidoras sin interferencia.'},
			{q:'¿Cuántos dB representa duplicar la potencia?',o:['3 dB','6 dB','10 dB','20 dB'],a:0,e:'Duplicar la potencia equivale exactamente a +3 dB. Multiplicar por 10 es +10 dB.'},
			{q:'¿Qué es el SWR o ROE?',o:['Velocidad de onda de radio','Relación de Ondas Estacionarias','Sistema de radio en emergencias','Señal de radio en el éter'],a:1,e:'SWR mide la adaptación de impedancias entre transmisor y antena. Ideal: 1:1.'},
			{q:'¿Qué frecuencia usa FT8 en 20 metros?',o:['14.070 MHz','14.074 MHz','14.080 MHz','14.100 MHz'],a:1,e:'FT8 en 20m opera en 14.074 MHz por convención internacional del plan de bandas IARU.'},
			{q:'¿Qué es un locator Maidenhead?',o:['El indicativo de la estación','Un sistema de coordenadas geográficas','El nombre del operador','Un tipo de antena'],a:1,e:'El localizador Maidenhead divide el mundo en cuadrículas, muy usado en POTA, concursos y FT8.'},
			{q:'¿Qué significa QRP?',o:['Potencia alta','Potencia reducida / baja','Cambio de frecuencia','Fin de transmisión'],a:1,e:'QRP significa reducir la potencia. Estación QRP: ≤5W en CW, ≤10W en SSB.'},
			{q:'¿Qué es el offset en una repetidora VHF?',o:['La potencia de salida','La diferencia entre frecuencia TX y RX','El tono CTCSS','La sensibilidad del receptor'],a:1,e:'El offset es la diferencia entre frecuencia de TX y RX. En 2m es típicamente ±600 kHz.'},
			{q:'¿Cuál es la repetidora principal de HK4RAU en Medellín?',o:['146.520 MHz','<?php echo esc_js( rau_get_repetidor_principal()['frequency'] ); ?> MHz','147.380 MHz','147.925 MHz'],a:1,e:'La repetidora de Medellín opera en <?php echo esc_js( rau_get_repetidor_principal()['frequency'] ); ?> MHz, offset <?php echo esc_js( (float) rau_get_repetidor_principal()['offset'] * 1000 ); ?> kHz, tono CTCSS <?php echo esc_js( rtrim( rtrim( number_format( (float) rau_get_repetidor_principal()['tone'], 1 ), '0' ), '.' ) ); ?> Hz.'},
			{q:'¿Qué modo digital funciona a señales muy débiles bajo el ruido?',o:['RTTY','PSK31','FT8','SSTV'],a:2,e:'FT8 puede decodificar señales hasta 24 dB bajo el ruido, mucho mejor que cualquier modo de voz.'},
			{q:'¿Qué es APRS?',o:['Un modo de voz digital','Sistema de reporte automático de posición GPS','Un tipo de antena','Protocolo de repetidoras'],a:1,e:'APRS (Automatic Packet Reporting System) reporta posición GPS y telemetría, típicamente en 144.390 MHz.'},
			{q:'¿Qué es un pileup en DX?',o:['Un concurso de CW','Muchas estaciones llamando a la vez a una estación DX','Un tipo de antena','Una interferencia'],a:1,e:'Un pileup ocurre cuando muchas estaciones llaman simultáneamente a una estación DX rara.'},
			{q:'Dos colombianos tienen indicativos HJ3ABC y HK3ABC. ¿Qué los diferencia?',o:['Están en departamentos distintos','HJ es de Segunda categoría o Novicio; HK es de Primera o Avanzada','HJ es para estaciones móviles y HK para fijas','HJ está vencido y HK vigente'],a:1,e:'El artículo 35 del Decreto 963 de 2009, compilado en el 2.2.5.7.1 del Decreto 1078 de 2015, asigna el prefijo HJ a las licencias de Segunda categoría o de Novicio, y HK a las de Primera categoría o de Experto y a las de categoría Avanzada. El dígito indica la zona, así que ambos serían de Cundinamarca, Meta o Vichada.'},
			{q:'Un radioaficionado con licencia de Novicio quiere hacer fonía en 15 metros. ¿Puede?',o:['Sí, la banda completa','No: en 15m el Novicio solo tiene autorizados CW y datos','Sí, pero solo por encima de 21.200 MHz','No, 15m está reservada a la categoría Avanzada'],a:1,e:'El artículo 34 del Decreto 963 de 2009 designa 21000 a 21450 kHz al Novicio únicamente con emisiones de telegrafía y datos (A1A, A2A, PoA, X3E). La fonía (A3E, R3E, J3E, F3E) no aparece para esa banda. Sí puede hacer fonía en 160m, 80m, 40m, 10m y 2m, con recortes de banda en algunas.'},
			{q:'¿Qué zona corresponde al dígito 0 en un indicativo colombiano?',o:['Amazonas y Vaupés','Nariño y Putumayo','El territorio insular y el servicio móvil marítimo','Bogotá y alrededores'],a:2,e:'El dígito 0 se reserva al territorio insular colombiano (San Andrés y Providencia) y al servicio móvil marítimo. Amazonas y Vaupés son zona 9; Nariño y Putumayo, zona 8.'},
			{q:'¿Qué principio rige la potencia de transmisión de un radioaficionado en Colombia?',o:['Usar siempre la potencia máxima permitida por el equipo','Usar la mínima potencia necesaria para lograr la comunicación deseada','La potencia no está regulada','Usar siempre 100 W en cualquier banda'],a:1,e:'El artículo 38 del Decreto 963 de 2009, compilado en el 2.2.5.7.4 del Decreto 1078 de 2015, obliga textualmente a usar la mínima potencia para transmitir la comunicación deseada. Los máximos no dependen de la banda sino del rango y de tu categoría: en VHF son 100 W para Avanzada, 50 W para Experto y 25 W para Novicio. Consulta la tabla completa en la herramienta Bandas de Colombia.'},
			{q:'¿Qué es la propagación troposférica?',o:['Propagación ionosférica','Propagación en VHF/UHF por la troposfera','Propagación por satélite','Propagación terrestre en HF'],a:1,e:'La propagación troposférica usa la capa baja de la atmósfera para VHF/UHF a cientos de km.'},
		],
		C:[
			{q:'¿Cuál es la fórmula de pérdida en espacio libre (FSPL)?',o:['FSPL = 20log(d) + 20log(f) + 32.44','FSPL = d × f / 300','FSPL = 10log(d) + 10log(f)','FSPL = d² × f'],a:0,e:'FSPL(dB) = 20log₁₀(d_km) + 20log₁₀(f_MHz) + 32.44 es la ecuación de Friis.'},
			{q:'¿Qué es el factor de velocidad de un coaxial?',o:['La relación pérdida/longitud','La velocidad de propagación respecto a la luz','La impedancia característica','La potencia máxima admisible'],a:1,e:'El factor de velocidad (VF) es la proporción entre la velocidad en el cable y la velocidad de la luz. RG-58: ~0.66.'},
			{q:'¿Cuál es la impedancia estándar del coaxial en radioafición?',o:['50 Ω','75 Ω','100 Ω','300 Ω'],a:0,e:'50 ohms es el estándar en RF y radioafición. 75 ohms se usa en sistemas de TV por cable.'},
			{q:'¿Qué es la modulación SSB?',o:['AM con doble banda lateral','Banda lateral única sin portadora','Modulación de frecuencia digital','AM con portadora reducida'],a:1,e:'SSB suprime la portadora y una banda lateral, usando solo una. Más eficiente en potencia y ancho de banda.'},
			{q:'¿Cuántos dB de ganancia tiene una Yagi de 5 elementos típica?',o:['2.15 dBi','5 dBi','10 dBi','20 dBi'],a:2,e:'Una Yagi de 5 elementos tiene aproximadamente 10 dBi de ganancia según diseño y espaciado.'},
			{q:'¿Qué es un balun 1:1?',o:['Convierte señal balanceada a no balanceada','Multiplica la impedancia por 4','Filtra armónicas','Adapta 50Ω a 75Ω'],a:0,e:'Un balun 1:1 convierte entre señal balanceada (antena) y no balanceada (coaxial) sin cambiar la impedancia.'},
			{q:'¿Cuál es la impedancia de un dipolo λ/2 en espacio libre?',o:['50 Ω','73 Ω','300 Ω','600 Ω'],a:1,e:'La impedancia de radiación de un dipolo λ/2 es aproximadamente 73 ohms en espacio libre.'},
			{q:'¿Qué es el ruido de figura (NF) de un receptor?',o:['Interferencia ambiental','Degradación de S/N introducida por el receptor','Sensibilidad mínima detectable','Ancho de banda del filtro FI'],a:1,e:'El Noise Figure expresa cuánto ruido adicional añade el receptor. Un NF bajo (0.5-2 dB) es excelente.'},
			{q:'¿Qué es la IMD (Intermodulation Distortion)?',o:['Ruido de fondo','Productos indeseados por amplificación no lineal de múltiples señales','Reflexión de antena','Interferencia de red eléctrica'],a:1,e:'La IMD genera productos de intermodulación cuando dos o más señales se mezclan en un amplificador no lineal.'},
			{q:'¿Cuál es el principio de reciprocidad de las antenas?',o:['Solo puede transmitir','Mismas propiedades en TX y RX','Mayor ganancia en TX que en RX','Las antenas directivas no reciben bien'],a:1,e:'Una antena tiene el mismo diagrama de radiación, ganancia e impedancia tanto para TX como para RX.'},
			{q:'¿Qué es la propagación skywave?',o:['Propagación directa a corta distancia','Reflexión de señales en la ionosfera','Propagación troposférica','Reflexión lunar'],a:1,e:'La propagación skywave usa la reflexión ionosférica para comunicaciones a miles de km en HF.'},
			{q:'¿Cuánto dura aproximadamente el ciclo solar?',o:['1 año','3 años','11 años','27 días'],a:2,e:'El ciclo solar dura ~11 años. En el máximo hay más manchas solares y mejor propagación HF.'},
			{q:'¿Qué es un LNA?',o:['Amplificador de potencia TX','Amplificador de bajo ruido para RX','Filtro de armónicas','Modulador de señal'],a:1,e:'LNA (Low Noise Amplifier) amplifica señales débiles con el mínimo de ruido añadido, mejorando la sensibilidad.'},
			{q:'¿Qué es el ancho de banda de una señal SSB típica?',o:['300 Hz','2.7 kHz','6 kHz','25 kHz'],a:1,e:'Una señal SSB ocupa ~2.7 kHz de ancho de banda (300-3000 Hz de audio).'},
			{q:'¿Qué es EME en radioafición?',o:['Modo de emergencia','Earth-Moon-Earth, comunicación vía luna','European Mobile Equipment','Equipo de Modulación Eléctrica'],a:1,e:'EME o moonbounce usa la luna como reflector pasivo de señales de radio para comunicaciones a gran distancia.'},
			{q:'¿Qué es la zona de Fresnel?',o:['Región elipsoidal alrededor de la trayectoria de RF','Zona de cobertura de un repetidor','Rango de frecuencias de una banda','Zona de propagación ionosférica'],a:0,e:'La primera zona de Fresnel debe estar al 60% despejada de obstáculos para un enlace óptimo de microondas.'},
			{q:'¿Cuál es la ganancia de una antena isótropa?',o:['0 dBi','2.15 dBi','3 dBi','6 dBi'],a:0,e:'Una antena isótropa teórica (irradia igual en todas direcciones) tiene 0 dBi por definición.'},
			{q:'¿Qué es FT4 en relación a FT8?',o:['Idéntico a FT8','Variante más rápida de FT8 para concursos','Un modo de video','Un tipo de modulación FM'],a:1,e:'FT4 usa períodos de 7.5 segundos vs 15 de FT8, permitiendo el doble de QSOs por hora en concursos.'},
			{q:'¿Qué es la onda de tierra (ground wave)?',o:['Propagación ionosférica','Propagación que sigue la curvatura terrestre en frecuencias bajas','Reflexión troposférica','Propagación directa'],a:1,e:'La onda de tierra es efectiva en LF/MF y permite comunicaciones confiables a cientos de km siguiendo la curvatura de la Tierra.'},
			{q:'¿Cuál es el significado de IMD de tercer orden (IP3) en un receptor?',o:['La frecuencia de corte del filtro','El punto de intercepción donde los productos de 3er orden igualan la señal fundamental','La sensibilidad mínima','El rango dinámico máximo'],a:1,e:'El IP3 (Third-Order Intercept Point) es una figura de mérito del receptor que indica qué tan bien maneja señales fuertes sin generar intermodulación.'},
		]
	};

	var currentCat='A',currentIdx=0,correct=0,wrong=0,questions=[],preguntasFalladas=[];
	// Fisher-Yates. El shuffle anterior era sort(function(){return Math.random()-0.5;}), que es un
	// barajado sesgado clásico: el comparador no es consistente y el algoritmo de ordenación tiende
	// a dejar los elementos cerca de donde estaban. Medido sobre 200.000 tiradas, con 4 opciones la
	// respuesta correcta caía en la 2ª o 3ª posición el 76% de las veces y en la última solo el 9.5%
	// (lo uniforme es 25% en cada una). Como en el banco la correcta suele ser la segunda opción del
	// array, se podía aprobar apostando siempre por la de en medio. Además la primera pregunta del
	// banco salía en el 35.6% de los exámenes en vez del 20.8% que le tocaba, y las del final casi nunca.
	function shuffle(arr){
		var r=arr.slice();
		for(var i=r.length-1;i>0;i--){
			var j=Math.floor(Math.random()*(i+1));
			var t=r[i]; r[i]=r[j]; r[j]=t;
		}
		return r;
	}

	function startExam(cat){
		currentCat=cat;currentIdx=0;correct=0;wrong=0;preguntasFalladas=[];
		var n=cat==='A'?10:cat==='B'?15:20;
		questions=shuffle(PREGUNTAS[cat]).slice(0,n);
		document.getElementById('exam-total').textContent=questions.length;
		document.getElementById('exam-correct').textContent=0;
		document.getElementById('exam-wrong').textContent=0;
		document.getElementById('exam-bar').style.width='0%';
		document.getElementById('exam-start').classList.add('hidden');
		document.getElementById('exam-result').classList.add('hidden');
		document.getElementById('exam-container').classList.remove('hidden');
		showQuestion();
	}

	function showQuestion(){
		var q=questions[currentIdx];
		document.getElementById('exam-num').textContent=currentIdx+1;
		document.getElementById('exam-bar').style.width=(currentIdx/questions.length*100)+'%';
		document.getElementById('exam-q-text').textContent=q.q;
		document.getElementById('exam-explain').classList.add('hidden');
		document.getElementById('exam-next').classList.add('hidden');
		var opts=document.getElementById('exam-options');
		opts.innerHTML='';
		shuffle(q.o.map(function(o,i){return{text:o,idx:i};})).forEach(function(opt){
			var btn=document.createElement('button');
			btn.className='w-full text-left rounded-xl border border-border/70 bg-background px-4 py-3 text-sm transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-ring';
			btn.textContent=opt.text;
			btn.addEventListener('click',function(){
				opts.querySelectorAll('button').forEach(function(b){b.disabled=true;});
				var isCorrect=opt.idx===q.a;
				if(isCorrect){correct++;document.getElementById('exam-correct').textContent=correct;}
				else{wrong++;document.getElementById('exam-wrong').textContent=wrong;preguntasFalladas.push(q);}
				btn.className=btn.className.replace('border-border/70 bg-background hover:border-primary/50',isCorrect?'border-emerald-400 bg-emerald-400/10 text-emerald-400':'border-rose-400 bg-rose-400/10 text-rose-400');
				if(!isCorrect){
					opts.querySelectorAll('button').forEach(function(b,i){
						var sopts=Array.from(opts.querySelectorAll('button'));
						// no podemos recuperar el idx aquí, mejor marcar la respuesta correcta en el explain
					});
				}
				var ex=document.getElementById('exam-explain');
				ex.textContent=(isCorrect?'Correcto. ':'Incorrecto. La respuesta correcta es: "'+q.o[q.a]+'". ')+q.e;
				ex.className='mt-4 rounded-xl border px-4 py-3 text-sm '+(isCorrect?'border-emerald-400/30 bg-emerald-400/5 text-emerald-400':'border-rose-400/30 bg-rose-400/5 text-rose-400');
				ex.classList.remove('hidden');
				document.getElementById('exam-next').classList.remove('hidden');
			});
			opts.appendChild(btn);
		});
	}

	document.querySelectorAll('.exam-cat-btn').forEach(function(btn){
		btn.addEventListener('click',function(){startExam(this.dataset.cat);});
	});

	function mostrarEstadisticas(){
		try {
			var stats=JSON.parse(localStorage.getItem('rau_examen_stats')||'{}');
			document.querySelectorAll('[data-exam-stats]').forEach(function(el){
				var cat=el.dataset.examStats;
				var s=stats[cat];
				el.textContent = s ? ('Mejor: '+s.mejorPct+'% · '+s.intentos+' intento'+(s.intentos===1?'':'s')+' · '+s.ultimaFecha) : 'Sin intentos todavía';
			});
		} catch(e){}
	}
	mostrarEstadisticas();
	document.getElementById('exam-next').addEventListener('click',function(){
		currentIdx++;
		if(currentIdx>=questions.length){
			document.getElementById('exam-container').classList.add('hidden');
			var pct=Math.round(correct/questions.length*100);
			var st=document.getElementById('exam-score-text');
			st.textContent=pct+'% — '+correct+' de '+questions.length+' correctas';
			st.style.color=pct>=70?'#22c55e':pct>=50?'#f59e0b':'#ef4444';
			document.getElementById('exam-score-sub').textContent=pct>=70?'¡Aprobado! Estás listo para el examen real.':pct>=50?'Cerca. Repasa los temas donde fallaste.':'Necesitas más práctica. ¡Sigue adelante!';
			document.getElementById('exam-result').classList.remove('hidden');

			// Estadísticas persistentes (localStorage, mismo patrón que Bitácora Rápida)
			try {
				var STATS_KEY='rau_examen_stats';
				var stats=JSON.parse(localStorage.getItem(STATS_KEY)||'{}');
				var previo=stats[currentCat];
				if(!previo || pct>previo.mejorPct) stats[currentCat]={mejorPct:pct, ultimaFecha:new Date().toISOString().slice(0,10), intentos:(previo?previo.intentos:0)+1};
				else { previo.intentos++; previo.ultimaFecha=new Date().toISOString().slice(0,10); }
				localStorage.setItem(STATS_KEY, JSON.stringify(stats));
			} catch(e){}

			// Revisión de errores — solo lo que falló, con su explicación
			var revEl=document.getElementById('exam-revision');
			if(preguntasFalladas.length){
				revEl.innerHTML='<p class="font-mono text-xs uppercase tracking-wider text-muted-foreground mb-2">Repasa esto:</p>'
					+preguntasFalladas.map(function(q){
						return '<div class="rounded-xl border border-rose-400/30 bg-rose-400/5 p-3 text-sm">'
							+'<p class="font-semibold">'+q.q+'</p>'
							+'<p class="mt-1 text-muted-foreground">'+q.e+'</p>'
							+'</div>';
					}).join('');
			} else {
				revEl.innerHTML='<p class="text-center text-emerald-400 text-sm">Ninguna pregunta fallada — excelente.</p>';
			}
		}else showQuestion();
	});
	document.getElementById('exam-restart').addEventListener('click',function(){
		document.getElementById('exam-container').classList.add('hidden');
		document.getElementById('exam-start').classList.remove('hidden');
		mostrarEstadisticas();
	});
	document.getElementById('exam-retry').addEventListener('click',function(){startExam(currentCat);});
})();


/* ── Sub-tabs de Calculadoras ── */
(function(){
	var pestanas=document.querySelectorAll('.calc-sub-tab');
	if(!pestanas.length) return;
	var ACTIVA='border-primary bg-primary/10 text-primary';
	var INACTIVA='border-border/70 bg-card text-muted-foreground hover:border-primary/50';

	function activar(btn){
		var id=btn.dataset.calc;
		document.querySelectorAll('.calc-sub-panel').forEach(function(p){ p.style.display='none'; });
		pestanas.forEach(function(b){
			b.className=b.className.replace(ACTIVA, INACTIVA);
			// El cambio de clases es puramente visual; sin aria-selected y tabindex un lector de
			// pantalla no distingue qué calculadora está abierta ni permite saltar el grupo con Tab.
			b.setAttribute('aria-selected','false');
			b.setAttribute('tabindex','-1');
		});
		var panel=document.getElementById('calc-'+id);
		if(panel) panel.style.display='';
		btn.className=btn.className.replace(INACTIVA, ACTIVA);
		btn.setAttribute('aria-selected','true');
		btn.setAttribute('tabindex','0');
	}

	pestanas.forEach(function(btn, i){
		btn.addEventListener('click', function(){ activar(btn); });
		btn.addEventListener('keydown', function(e){
			var salto = e.key==='ArrowRight' ? 1 : e.key==='ArrowLeft' ? -1
				: e.key==='Home' ? -i : e.key==='End' ? pestanas.length-1-i : null;
			if(salto===null) return;
			e.preventDefault();
			var destino=pestanas[(i+salto+pestanas.length)%pestanas.length];
			activar(destino);
			destino.focus();
		});
	});
})();

/* ── EFHW ── */
rauOn('efhw-calc','click',function(){
	var f=parseFloat(document.getElementById('efhw-freq').value);
	var vf=parseFloat(document.getElementById('efhw-vf').value)||0.97;
	var res=document.getElementById('efhw-result');
	if(!f||f<=0){res.innerHTML='<p class="text-rose-400">Ingresa una frecuencia válida.</p>';return;}
	var c=299.792458;
	var wl=c/f;
	var L=wl*0.5*vf;
	var harmonics=[1,2,3,4];
	var rows=harmonics.map(function(h){
		var fh=f*h;
		var lh=L/h;
		var band=fh<2?'160m':fh<5?'80m':fh<8?'40m':fh<11?'30m':fh<15?'20m':fh<19?'17m':fh<22?'15m':fh<25?'12m':fh<30?'10m':fh<54?'6m':'VHF';
		return '<div class="flex items-center justify-between rounded-lg border border-border/50 bg-background/50 px-3 py-2">'
			+'<div><span class="font-mono text-xs font-bold text-primary">'+h+'ª armónica</span><span class="ml-2 font-mono text-xs text-muted-foreground">'+fh.toFixed(3)+' MHz ('+band+')</span></div>'
			+'<span class="font-mono text-sm font-bold">'+lh.toFixed(3)+' m</span>'
			+'</div>';
	}).join('');
	res.innerHTML='<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Longitud del radiador</p>'
		+'<div class="space-y-2">'+rows+'</div>'
		+'<div class="mt-4 rounded-lg border border-primary/30 bg-primary/5 px-3 py-2 text-xs text-muted-foreground">'
		+'Transformador recomendado: <strong class="text-foreground">49:1 unun</strong> · Alimentación: extremo del alambre via unun + coaxial 50Ω'
		+'</div>';
});

/* ── TRANSFORMADOR Z ── */
rauOn('trafo-calc','click',function(){
	var z1=parseFloat(document.getElementById('trafo-z1').value);
	var z2=parseFloat(document.getElementById('trafo-z2').value);
	var res=document.getElementById('trafo-result');
	if(!z1||!z2||z1<=0||z2<=0){res.innerHTML='<p class="text-rose-400 text-sm">Ingresa ambas impedancias.</p>';return;}
	var ratio=z2/z1;
	var turns=Math.sqrt(ratio);
	var n1=1, n2=turns;
	// Simplificar a enteros aproximados
	var best={n1:1,n2:Math.round(turns),err:Math.abs(turns-Math.round(turns))};
	for(var i=1;i<=20;i++){
		for(var j=1;j<=50;j++){
			var r=j/i;
			var err2=Math.abs(r*r-ratio);
			if(err2<best.err){best={n1:i,n2:j,err:err2};}
		}
	}
	var achieved=z1*Math.pow(best.n2/best.n1,2);
	res.innerHTML='<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Resultado</p>'
		+'<div class="space-y-3">'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Relación de impedancias</span><span class="font-mono font-bold text-primary">1:'+ratio.toFixed(1)+'</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Relación de espiras exacta</span><span class="font-mono font-bold text-foreground">1:'+turns.toFixed(3)+'</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Espiras prácticas (enteros)</span><span class="font-mono font-bold text-primary">'+best.n1+':'+best.n2+'</span></div>'
		+'<div class="flex justify-between"><span class="text-sm text-muted-foreground">Z salida con espiras prácticas</span><span class="font-mono text-sm text-muted-foreground">'+achieved.toFixed(1)+' Ω</span></div>'
		+'</div>'
		+'<div class="mt-3 rounded-lg border border-border/50 bg-background/50 px-3 py-2 text-xs text-muted-foreground">'
		+'Construye el bobinado primario con <strong class="text-foreground">'+best.n1+'</strong> espira(s) y el secundario con <strong class="text-foreground">'+best.n2+'</strong> espira(s) sobre un núcleo toroidal de ferrita.'
		+'</div>';
});

/* ── CONDENSADOR LC ── */
rauOn('lc-calc','click',function(){
	var f=parseFloat(document.getElementById('lc-freq').value);
	var L=parseFloat(document.getElementById('lc-l').value);
	var C=parseFloat(document.getElementById('lc-c').value);
	var res=document.getElementById('lc-result');
	if(!f||f<=0){res.innerHTML='<p class="text-rose-400 text-sm">Ingresa la frecuencia.</p>';return;}
	var w=2*Math.PI*f*1e6;
	if(L>0&&!C){
		var Cpf=1/(w*w*L*1e-6)*1e12;
		res.innerHTML='<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Capacitancia necesaria</p>'
			+'<div class="flex justify-between items-center"><span class="text-sm text-muted-foreground">C requerida</span><span class="font-mono text-3xl font-bold text-primary">'+Cpf.toFixed(1)+' pF</span></div>'
			+'<div class="mt-2 text-xs text-muted-foreground">Para L = '+L+' µH a '+f+' MHz</div>';
	} else if(C>0&&!L){
		var Luh=1/(w*w*C*1e-12)*1e6;
		res.innerHTML='<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Inductancia necesaria</p>'
			+'<div class="flex justify-between items-center"><span class="text-sm text-muted-foreground">L requerida</span><span class="font-mono text-3xl font-bold text-primary">'+Luh.toFixed(3)+' µH</span></div>'
			+'<div class="mt-2 text-xs text-muted-foreground">Para C = '+C+' pF a '+f+' MHz</div>';
	} else if(L>0&&C>0){
		var fres=1/(2*Math.PI*Math.sqrt(L*1e-6*C*1e-12))/1e6;
		res.innerHTML='<p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-muted-foreground">Frecuencia de resonancia</p>'
			+'<div class="flex justify-between items-center"><span class="text-sm text-muted-foreground">f resonante</span><span class="font-mono text-3xl font-bold text-primary">'+fres.toFixed(4)+' MHz</span></div>'
			+'<div class="mt-2 text-xs text-muted-foreground">Con L = '+L+' µH y C = '+C+' pF</div>';
	} else {
		res.innerHTML='<p class="text-rose-400 text-sm">Ingresa al menos uno de los dos valores (L o C).</p>';
	}
});

/* ── ALFABETO FONÉTICO ── */
(function(){
	var synth=window.speechSynthesis;
	document.querySelectorAll('.fon-card').forEach(function(btn){
		btn.addEventListener('click',function(){
			var word=this.dataset.word;
			if(!synth||!word) return;
			synth.cancel();
			var utt=new SpeechSynthesisUtterance(word);
			utt.lang='en-US';
			utt.rate=0.85;
			utt.pitch=1;
			synth.speak(utt);
			// Feedback visual
			var orig=this.className;
			this.style.borderColor='hsl(var(--primary))';
			this.style.background='hsl(var(--primary)/.1)';
			setTimeout(function(){
				btn.style.borderColor='';
				btn.style.background='';
			},600);
		});
	});
})();


/* ── CW Sub-tabs ── */
document.querySelectorAll('.cw-sub-tab').forEach(function(btn){
	btn.addEventListener('click',function(){
		var id=this.dataset.cwtab;
		document.querySelectorAll('.cw-sub-panel').forEach(function(p){p.style.display='none';});
		document.querySelectorAll('.cw-sub-tab').forEach(function(b){
			b.className=b.className.replace('border-primary bg-primary/10 text-primary','border-border/70 bg-card text-muted-foreground hover:border-primary/50');
		});
		var p=document.getElementById('cw-'+id);
		if(p)p.style.display='';
		this.className=this.className.replace('border-border/70 bg-card text-muted-foreground hover:border-primary/50','border-primary bg-primary/10 text-primary');
	});
});

/* Decodificador texto→Morse en tiempo real */
(function(){
	var CODE=MORSE;
	var inp=document.getElementById('cw-text');
	var vis=document.getElementById('cw-morse-visual');
	if(!inp||!vis)return;
	inp.addEventListener('input',function(){
		var out='';
		for(var ch of this.value.toUpperCase()){
			if(CODE[ch]) out+=CODE[ch]+'  ';
			else if(ch===' ') out+='/ ';
		}
		vis.textContent=out.trim()||'—';
	});
})();

/* ── CW Entrenamiento ── */
(function(){
	var CODE=MORSE;
	var WORDS=['CQ','DE','QSL','QTH','RST','73','88','TNX','QSO','DX','HK','MEDELLIN','RADIO','MORSE'];
	var tCorrect=0,tWrong=0,tRacha=0,currentChar='';

	function getChar(mode){
		if(mode==='letters'){var k=Object.keys(CODE).filter(function(c){return isNaN(c)});return k[Math.floor(Math.random()*k.length)];}
		if(mode==='numbers'){var k=Object.keys(CODE).filter(function(c){return !isNaN(c)});return k[Math.floor(Math.random()*k.length)];}
		if(mode==='words')return WORDS[Math.floor(Math.random()*WORDS.length)];
		var k=Object.keys(CODE);return k[Math.floor(Math.random()*k.length)];
	}

	async function playMorse(text,wpm,freqHz){
		var ctx=new(window.AudioContext||window.webkitAudioContext)();
		var dot=1.2/wpm;
		for(var ch of text.toUpperCase()){
			var code=CODE[ch];
			if(!code)continue;
			for(var sym of code){
				var osc=ctx.createOscillator(),gain=ctx.createGain();
				osc.connect(gain);gain.connect(ctx.destination);
				osc.frequency.value=freqHz;
				gain.gain.setValueAtTime(0,ctx.currentTime);
				gain.gain.linearRampToValueAtTime(0.5,ctx.currentTime+0.005);
				var dur=sym==='.'?dot:dot*3;
				gain.gain.linearRampToValueAtTime(0.5,ctx.currentTime+dur-0.005);
				gain.gain.linearRampToValueAtTime(0,ctx.currentTime+dur);
				osc.start(ctx.currentTime);osc.stop(ctx.currentTime+dur);
				await new Promise(function(r){setTimeout(r,dur*1000+dot*1000);});
			}
			await new Promise(function(r){setTimeout(r,dot*2000);});
		}
		ctx.close();
	}

	var playBtn=document.getElementById('train-play');
	var checkBtn=document.getElementById('train-check');
	var inp2=document.getElementById('train-input');
	var fb=document.getElementById('train-feedback');
	var wpmEl=document.getElementById('train-wpm');
	var wpmVal=document.getElementById('train-wpm-val');
	if(!playBtn)return;

	wpmEl.addEventListener('input',function(){wpmVal.textContent=this.value;});

	playBtn.addEventListener('click',function(){
		var mode=document.getElementById('train-mode').value;
		currentChar=getChar(mode);
		inp2.value='';fb.classList.add('hidden');
		playMorse(currentChar,parseInt(wpmEl.value)||10,600);
	});

	checkBtn.addEventListener('click',function(){
		if(!currentChar){fb.classList.remove('hidden');fb.className='mt-2 rounded-xl border border-amber-400/30 bg-amber-400/5 px-4 py-2 text-sm text-amber-400';fb.textContent='Primero haz clic en "Escuchar siguiente".';return;}
		var ans=(inp2.value||'').toUpperCase().trim();
		var ok=ans===currentChar.toUpperCase();
		if(ok){tCorrect++;tRacha++;}else{tWrong++;tRacha=0;}
		document.getElementById('train-racha').textContent=tRacha;
		var total=tCorrect+tWrong;
		document.getElementById('train-correct').textContent=tCorrect;
		document.getElementById('train-wrong').textContent=tWrong;
		document.getElementById('train-pct').textContent=total?Math.round(tCorrect/total*100)+'%':'—';
		fb.classList.remove('hidden');
		fb.className='mt-2 rounded-xl border px-4 py-2 text-sm font-medium '+(ok?'border-emerald-400/30 bg-emerald-400/5 text-emerald-400':'border-rose-400/30 bg-rose-400/5 text-rose-400');
		fb.textContent=ok?'✓ Correcto — era: '+currentChar:'✗ Incorrecto — era: '+currentChar+' (tú escribiste: '+ans+')';
		currentChar='';
	});

	inp2.addEventListener('keydown',function(e){if(e.key==='Enter')checkBtn.click();});

	document.getElementById('train-reset').addEventListener('click',function(){
		tCorrect=0;tWrong=0;tRacha=0;currentChar='';
		document.getElementById('train-correct').textContent=0;
		document.getElementById('train-wrong').textContent=0;
		document.getElementById('train-racha').textContent=0;
		document.getElementById('train-pct').textContent='—';
		fb.classList.add('hidden');inp2.value='';
	});
})();

/* ── Abreviaciones CW ── */
(function(){
	var ABREV=[
		['AGN','De nuevo (again)',''],
		['ANT','Antena',''],
		['AR','Fin de mensaje','Prosign'],
		['AS','Espere un momento','Prosign'],
		['B4','Antes (before)',''],
		['BK','Break / interrupción',''],
		['BN','Sido (been)',''],
		['BTW','Por cierto (by the way)',''],
		['CFM','Confirmo',''],
		['CL','Cerrando (closing)','Prosign'],
		['CQ','Llamada general',''],
		['CU','Nos vemos (see you)',''],
		['CUL','Nos vemos luego',''],
		['CPI','Copio (copy)',''],
		['DE','De (de / from)','Separador de indicativos'],
		['DWN','Abajo (down)',''],
		['DX','Larga distancia',''],
		['ES','Y (and)',''],
		['FB','Excelente (fine business)',''],
		['FER','Para (for)',''],
		['GA','Adelante / buenos tardes',''],
		['GD','Bueno / buen día',''],
		['GE','Buenas noches',''],
		['GL','Buena suerte (good luck)',''],
		['GM','Buenos días',''],
		['GN','Buenas noches (goodbye)',''],
		['GUD','Bueno (good)',''],
		['HI','Risa (laughing)',''],
		['HR','Aquí (here)',''],
		['HW','¿Cómo? (how)',''],
		['K','Cambio (over)',''],
		['KN','Cambio solo para ti',''],
		['LP','Vía largo recorrido (long path)',''],
		['MNI','Muchos (many)',''],
		['NR','Número / cerca (near)',''],
		['NW','Ahora (now)',''],
		['OM','Colega (old man)',''],
		['OP','Operador',''],
		['PSE','Por favor (please)',''],
		['PWR','Potencia (power)',''],
		['R','Roger / recibido',''],
		['RPT','Repita',''],
		['RPRT','Reporte',''],
		['RX','Receptor',''],
		['SK','Fin de contacto','Prosign'],
		['SP','Vía corto recorrido (short path)',''],
		['SRI','Lo siento (sorry)',''],
		['TKS','Gracias (thanks)',''],
		['TNX','Gracias (thanks)',''],
		['TRX','Transceptor',''],
		['TU','Gracias (thank you)',''],
		['TX','Transmisor',''],
		['UFB','Muy bueno (ultra fine business)',''],
		['UR','Tu / tus (your)',''],
		['VY','Muy (very)',''],
		['WX','Clima (weather)',''],
		['XYL','Esposa (ex-young lady)',''],
		['YL','Señorita (young lady)',''],
		['73','Mejores deseos','Clásico'],
		['88','Amor y besos','Solo entre YL/XYL'],
	];

	var tbody=document.getElementById('abrev-tbody');
	var search=document.getElementById('abrev-search');
	if(!tbody)return;

	function render(filter){
		var html='';
		ABREV.forEach(function(row){
			var match=!filter||row[0].toLowerCase().includes(filter)||row[1].toLowerCase().includes(filter);
			if(match) html+='<tr class="border-b border-border/50 last:border-0">'
				+'<td class="px-3 py-2 font-mono text-sm font-bold text-primary">'+row[0]+'</td>'
				+'<td class="px-3 py-2 text-sm text-muted-foreground">'+row[1]+'</td>'
				+'<td class="px-3 py-2 text-xs text-muted-foreground">'+row[2]+'</td></tr>';
		});
		tbody.innerHTML=html||'<tr><td colspan="3" class="px-3 py-4 text-center text-sm text-muted-foreground">Sin resultados</td></tr>';
	}
	render('');
	if(search) search.addEventListener('input',function(){render(this.value.toLowerCase());});
})();

/* ── QSL Digital ── */
(function(){
	var canvas=document.getElementById('qsl-canvas');
	if(!canvas)return;
	var ctx2=canvas.getContext('2d');

	// Logo real de RAU precargado una vez, como marca de agua de la tarjeta
	var logo=new Image();
	logo.src='<?php echo esc_url( get_template_directory_uri() . "/assets/images/rau-badge.webp" ); ?>';
	logo.onload=function(){ drawQSL(); };

	// Estrellas fijas para el fondo "Espacial" (no se regeneran en cada redibujo)
	var stars=[];
	(function(){
		var seed=42;
		function rnd(){ seed=(seed*9301+49297)%233280; return seed/233280; }
		for(var i=0;i<60;i++) stars.push({x:rnd()*600,y:rnd()*400,r:rnd()*1.3+0.3,a:rnd()*0.6+0.2});
	})();

	// Color de acento según la banda detectada por la frecuencia (MHz)
	function bandColor(freq){
		var mhz=parseFloat(String(freq).replace(',','.'));
		if(isNaN(mhz)) return '#1fd5f9';
		if(mhz>=1.8&&mhz<2.1)   return '#a78bfa'; // 160m
		if(mhz>=3.5&&mhz<4.1)   return '#f472b6'; // 80m
		if(mhz>=7&&mhz<7.3)     return '#fb923c'; // 40m
		if(mhz>=10.1&&mhz<10.2) return '#fbbf24'; // 30m
		if(mhz>=14&&mhz<14.4)   return '#4ade80'; // 20m
		if(mhz>=18&&mhz<18.2)   return '#34d399'; // 17m
		if(mhz>=21&&mhz<21.5)   return '#22d3ee'; // 15m
		if(mhz>=24.8&&mhz<25)   return '#38bdf8'; // 12m
		if(mhz>=28&&mhz<29.7)   return '#1fd5f9'; // 10m
		if(mhz>=50&&mhz<54)     return '#818cf8'; // 6m
		if(mhz>=144&&mhz<148)   return '#c084fc'; // 2m
		if(mhz>=430&&mhz<450)   return '#f472b6'; // 70cm
		return '#1fd5f9';
	}

	// Trazo de rectángulo redondeado (por si el navegador no trae roundRect nativo)
	function roundedRectPath(ctx,x,y,w,h,r){
		ctx.beginPath();
		ctx.moveTo(x+r,y);
		ctx.arcTo(x+w,y,x+w,y+h,r);
		ctx.arcTo(x+w,y+h,x,y+h,r);
		ctx.arcTo(x,y+h,x,y,r);
		ctx.arcTo(x,y,x+w,y,r);
		ctx.closePath();
	}

	// La foto se pide la primera vez que se elige su fondo, no al cargar la página: las tres
	// sumaban ~1 MB en cada URL de herramienta, y el fondo por defecto (Clásico) no usa ninguna.
	function loadPhoto(url){
		var img=new Image();
		img.crossOrigin='anonymous';
		img.rauUrl=url;
		img.onload=function(){ drawQSL(); };
		img.onerror=function(){ img.failed=true; drawQSL(); };
		return img;
	}
	// Fotos de uso libre (Pexels, sin atribución requerida)
	var photoColombia=loadPhoto('https://images.pexels.com/photos/28405086/pexels-photo-28405086.jpeg?auto=compress&cs=tinysrgb&w=1200');
	var photoEmblema=loadPhoto('https://images.pexels.com/photos/13767911/pexels-photo-13767911.jpeg?auto=compress&cs=tinysrgb&w=1200');
	var photoRetro=loadPhoto('https://images.pexels.com/photos/30717831/pexels-photo-30717831.jpeg?auto=compress&cs=tinysrgb&w=1200');

	function fondoPlano(W,H){
		var grad=ctx2.createLinearGradient(0,0,W,H);
		grad.addColorStop(0,'#080c16');grad.addColorStop(1,'#0d1321');
		ctx2.fillStyle=grad;ctx2.fillRect(0,0,W,H);
	}

	// Dibuja una foto ajustada tipo "cover" (recorta sin deformar) + degradado oscuro encima para legibilidad
	function drawPhotoBg(img,W,H){
		if(!img.src) img.src=img.rauUrl; // al terminar de cargar, onload vuelve a dibujar
		if(!(img.complete && img.naturalWidth) || img.failed){
			fondoPlano(W,H);
			return false;
		}
		var ir=img.naturalWidth/img.naturalHeight, cr=W/H, sw,sh,sx,sy;
		if(ir>cr){ sh=img.naturalHeight; sw=sh*cr; sx=(img.naturalWidth-sw)/2; sy=0; }
		else { sw=img.naturalWidth; sh=sw/cr; sx=0; sy=(img.naturalHeight-sh)/2; }
		ctx2.drawImage(img,sx,sy,sw,sh,0,0,W,H);
		var scrim=ctx2.createLinearGradient(0,0,0,H);
		scrim.addColorStop(0,'rgba(6,10,18,0.72)');
		scrim.addColorStop(0.45,'rgba(6,10,18,0.4)');
		scrim.addColorStop(1,'rgba(6,10,18,0.8)');
		ctx2.fillStyle=scrim;ctx2.fillRect(0,0,W,H);
		return true;
	}

	function drawFondo(fondo,W,H,acento){
		if(fondo==='minimal'){
			ctx2.fillStyle='#0b0f1a';
			ctx2.fillRect(0,0,W,H);
			return;
		}

		if(fondo==='colombia'){
			drawPhotoBg(photoColombia,W,H);
			return;
		}
		if(fondo==='emblematico'){
			drawPhotoBg(photoEmblema,W,H);
			return;
		}
		if(fondo==='retro'){
			ctx2.save();
			ctx2.filter='sepia(0.65) saturate(1.35) contrast(1.05) brightness(0.82)';
			var ok=drawPhotoBg(photoRetro,W,H);
			ctx2.filter='none';
			ctx2.restore();
			if(ok){
				ctx2.fillStyle='rgba(120,60,20,0.16)';
				ctx2.fillRect(0,0,W,H);
			}
			// Textura halftone vintage encima de la foto
			ctx2.fillStyle='rgba(232,168,84,0.06)';
			for(var hy=6;hy<H;hy+=9){
				for(var hx=(Math.round(hy/9)%2===0)?6:10;hx<W;hx+=9){
					ctx2.beginPath();ctx2.arc(hx,hy,0.9,0,Math.PI*2);ctx2.fill();
				}
			}
			return;
		}

		fondoPlano(W,H);

		if(fondo==='cuadricula'){
			ctx2.strokeStyle='rgba(31,213,249,0.07)';ctx2.lineWidth=1;
			for(var gx=20;gx<W;gx+=20){ctx2.beginPath();ctx2.moveTo(gx,0);ctx2.lineTo(gx,H);ctx2.stroke();}
			for(var gy=20;gy<H;gy+=20){ctx2.beginPath();ctx2.moveTo(0,gy);ctx2.lineTo(W,gy);ctx2.stroke();}

		} else if(fondo==='espacial'){
			stars.forEach(function(s){
				ctx2.fillStyle='rgba(226,232,240,'+s.a+')';
				ctx2.beginPath();ctx2.arc(s.x,s.y,s.r,0,Math.PI*2);ctx2.fill();
			});
			var glow=ctx2.createRadialGradient(W/2,95,10,W/2,95,160);
			glow.addColorStop(0,acento+'26');glow.addColorStop(1,acento+'00');
			ctx2.fillStyle=glow;ctx2.fillRect(0,0,W,H);
		}
	}

	function drawBorde(forma,W,H,acento){
		if(forma==='redondeada'){
			roundedRectPath(ctx2,8,8,W-16,H-16,18);
			ctx2.strokeStyle=acento;ctx2.lineWidth=2;ctx2.stroke();
		} else {
			ctx2.strokeStyle=acento;ctx2.lineWidth=2;
			ctx2.strokeRect(8,8,W-16,H-16);
		}
	}

	// Perforado tipo sello postal: recorta semicírculos a lo largo del borde
	function perforarSello(W,H){
		var step=14,r=4;
		ctx2.save();
		ctx2.globalCompositeOperation='destination-out';
		for(var x=step/2;x<W;x+=step){
			ctx2.beginPath();ctx2.arc(x,0,r,0,Math.PI*2);ctx2.fill();
			ctx2.beginPath();ctx2.arc(x,H,r,0,Math.PI*2);ctx2.fill();
		}
		for(var y=step/2;y<H;y+=step){
			ctx2.beginPath();ctx2.arc(0,y,r,0,Math.PI*2);ctx2.fill();
			ctx2.beginPath();ctx2.arc(W,y,r,0,Math.PI*2);ctx2.fill();
		}
		ctx2.restore();
	}

	function drawQSL(){
		var de=document.getElementById('qsl-de').value.toUpperCase()||'HK4SSI';
		var to=document.getElementById('qsl-to').value.toUpperCase()||'W1AW';
		var fecha=document.getElementById('qsl-fecha').value||new Date().toISOString().slice(0,10);
		var hora=document.getElementById('qsl-hora').value||'00:00';
		var freq=document.getElementById('qsl-freq').value||'14.250 MHz';
		var modo=document.getElementById('qsl-modo').value||'SSB';
		var rst=document.getElementById('qsl-rst').value||'59';
		var nombre=document.getElementById('qsl-nombre').value||'Operador';
		var qthl=document.getElementById('qsl-qthl').value||'Medellín, Colombia';
		var fondoEl=document.getElementById('qsl-fondo');
		var formaEl=document.getElementById('qsl-forma');
		var fondo=fondoEl?fondoEl.value:'clasico';
		var forma=formaEl?formaEl.value:'rect';
		var acento=fondo==='retro' ? '#e8c07a' : bandColor(freq);
		var minimal=(fondo==='minimal');

		// Lienzo lógico 600x400, escalado 2x sobre el canvas físico 1200x800 (exportación nítida).
		// setTransform (no scale) para no acumular escala en llamadas repetidas.
		var W=600,H=400;
		ctx2.setTransform(2,0,0,2,0,0);
		ctx2.clearRect(0,0,W,H);

		drawFondo(fondo,W,H,acento);
		drawBorde(forma,W,H,acento);

		if(!minimal){
			ctx2.fillStyle=acento;ctx2.fillRect(8,8,W-16,3);
		}

		// Indicativo principal
		ctx2.fillStyle=acento;ctx2.font='bold 52px monospace';
		ctx2.textAlign='center';ctx2.fillText(de,W/2,95);

		// Subtítulo
		ctx2.fillStyle='#8899aa';ctx2.font='14px monospace';
		ctx2.fillText('RADIOAFICIONADOS UNIDOS · HK4RAU · MEDELLÍN, COLOMBIA',W/2,118);

		// Línea separadora
		ctx2.strokeStyle=acento+'33';ctx2.lineWidth=1;
		ctx2.beginPath();ctx2.moveTo(30,130);ctx2.lineTo(W-30,130);ctx2.stroke();

		// "Confirma QSO con"
		ctx2.fillStyle='#8899aa';ctx2.font='13px sans-serif';
		ctx2.fillText('CONFIRMA QSO CON',W/2,158);
		ctx2.fillStyle='#ffffff';ctx2.font='bold 36px monospace';
		ctx2.fillText(to,W/2,198);

		// Datos del QSO
		var col1=80,col2=W/2+20;
		ctx2.textAlign='left';
		[
			[col1,240,'FECHA',fecha+' UTC'],
			[col1,275,'HORA',hora+' UTC'],
			[col2,240,'FRECUENCIA',freq],
			[col2,275,'MODO',modo+' · RST '+rst],
		].forEach(function(d){
			ctx2.fillStyle=acento;ctx2.font='10px monospace';ctx2.fillText(d[2],d[0],d[1]-12);
			ctx2.fillStyle='#e2e8f0';ctx2.font='bold 16px monospace';ctx2.fillText(d[3],d[0],d[1]);
		});

		// Firma
		ctx2.textAlign='center';
		ctx2.fillStyle='#8899aa';ctx2.font='12px sans-serif';
		ctx2.fillText('73 DE '+nombre+' · '+qthl,W/2,330);

		// Marca de agua: logo real de RAU (si ya cargó y no es estilo minimal)
		if(!minimal && logo.complete && logo.naturalWidth){
			var lw=130,lh=lw*(logo.naturalHeight/logo.naturalWidth);
			ctx2.save();
			ctx2.globalAlpha=0.16;
			ctx2.drawImage(logo,W/2-lw/2,352-lh/2,lw,lh);
			ctx2.restore();
		} else if(!minimal){
			ctx2.fillStyle=acento+'30';ctx2.font='bold 120px monospace';
			ctx2.fillText('73',W/2,390);
		}

		if(forma==='sello') perforarSello(W,H);

		document.getElementById('qsl-actions').classList.remove('hidden');

		var shareBtn=document.getElementById('qsl-share');
		if(navigator.share && navigator.canShare) shareBtn.classList.remove('hidden');
	}

	document.getElementById('qsl-gen').addEventListener('click', drawQSL);

	['qsl-de','qsl-to','qsl-fecha','qsl-hora','qsl-freq','qsl-modo','qsl-rst','qsl-nombre','qsl-qthl','qsl-fondo','qsl-forma'].forEach(function(id){
		var el = document.getElementById(id);
		if (!el) return;
		el.addEventListener('input', drawQSL);
		el.addEventListener('change', drawQSL);
	});

	var nowBtn=document.getElementById('qsl-now');
	if(nowBtn) nowBtn.addEventListener('click', function(){
		var d=new Date();
		document.getElementById('qsl-fecha').value=d.toISOString().slice(0,10);
		document.getElementById('qsl-hora').value=String(d.getUTCHours()).padStart(2,'0')+':'+String(d.getUTCMinutes()).padStart(2,'0');
		drawQSL();
	});

	function qslFilename(){
		var de=document.getElementById('qsl-de').value||'HK4RAU';
		var to=document.getElementById('qsl-to').value||'QSO';
		var fecha=(document.getElementById('qsl-fecha').value||new Date().toISOString().slice(0,10)).replace(/-/g,'');
		return 'QSL-'+de+'-'+to+'-'+fecha+'.png';
	}

	document.getElementById('qsl-download').addEventListener('click',function(){
		var a=document.createElement('a');
		a.download=qslFilename();
		a.href=canvas.toDataURL('image/png');
		a.click();
	});

	var shareBtn=document.getElementById('qsl-share');
	if(shareBtn) shareBtn.addEventListener('click',function(){
		canvas.toBlob(function(blob){
			if(!blob) return;
			var file=new File([blob], qslFilename(), {type:'image/png'});
			if(navigator.canShare && navigator.canShare({files:[file]})){
				navigator.share({files:[file], title:'QSL Digital RAU'}).catch(function(e){ rauErr(e);});
			}
		}, 'image/png');
	});

	// Preview inicial
	drawQSL();
})();

/* ── Log ADIF ── */
(function(){
	var LS_KEY='rau_log_adif';
	var log=[];
	try { log=JSON.parse(localStorage.getItem(LS_KEY)||'[]'); } catch(e){ log=[]; }
	var tbody=document.getElementById('log-tbody');

	function guardar(){ try { localStorage.setItem(LS_KEY, JSON.stringify(log)); } catch(e){} }

	function pad(n){return String(n).padStart(2,'0');}
	function nowUTC(){var d=new Date();return pad(d.getUTCHours())+':'+pad(d.getUTCMinutes())+':'+pad(d.getUTCSeconds());}
	function dateUTC(){var d=new Date();return d.getUTCFullYear()+''+pad(d.getUTCMonth()+1)+''+pad(d.getUTCDate());}

	function render(){
		if(!log.length){tbody.innerHTML='<tr><td colspan="7" class="px-3 py-6 text-center text-sm text-muted-foreground">No hay QSOs registrados todavía.</td></tr>';return;}
		tbody.innerHTML=log.map(function(q,i){
			return '<tr class="border-b border-border/50 last:border-0">'
				+'<td class="px-3 py-2 font-mono text-xs text-muted-foreground">'+q.time+'</td>'
				+'<td class="px-3 py-2 font-mono text-sm font-bold text-primary">'+q.call+'</td>'
				+'<td class="px-3 py-2 font-mono text-xs">'+q.freq+'</td>'
				+'<td class="px-3 py-2 font-mono text-xs">'+q.mode+'</td>'
				+'<td class="px-3 py-2 font-mono text-xs">'+q.rstS+'/'+q.rstR+'</td>'
				+'<td class="px-3 py-2 text-xs text-muted-foreground">'+q.name+'</td>'
				+'<td class="px-3 py-2"><button onclick="removeQSO('+i+')" class="text-xs text-rose-400 hover:text-rose-300">✕</button></td>'
				+'</tr>';
		}).join('');
	}

	window.removeQSO=function(i){log.splice(i,1); guardar(); render();};

	document.getElementById('log-add').addEventListener('click',function(){
		var call=(document.getElementById('log-call').value||'').toUpperCase().trim();
		if(!call){alert('Ingresa el indicativo DX.');return;}
		log.push({
			call:call,
			time:nowUTC(),
			date:dateUTC(),
			freq:document.getElementById('log-freq').value||'',
			mode:document.getElementById('log-modo2').value,
			rstS:document.getElementById('log-rst-s').value||'59',
			rstR:document.getElementById('log-rst-r').value||'59',
			name:document.getElementById('log-nombre2').value||'',
		});
		guardar();
		document.getElementById('log-call').value='';
		document.getElementById('log-rst-s').value='';
		document.getElementById('log-rst-r').value='';
		document.getElementById('log-nombre2').value='';
		render();
	});

	document.getElementById('log-export').addEventListener('click',function(){
		if(!log.length){alert('No hay QSOs para exportar.');return;}
		var adif='ADIF Export by HK4RAU Herramientas\n<EOH>\n\n';
		log.forEach(function(q){
			adif+='<CALL:'+q.call.length+'>'+q.call;
			adif+=' <QSO_DATE:8>'+q.date;
			adif+=' <TIME_ON:6>'+q.time.replace(/:/g,'')+'00';
			if(q.freq) adif+=' <FREQ:'+q.freq.length+'>'+q.freq;
			adif+=' <MODE:'+q.mode.length+'>'+q.mode;
			adif+=' <RST_SENT:'+q.rstS.length+'>'+q.rstS;
			adif+=' <RST_RCVD:'+q.rstR.length+'>'+q.rstR;
			if(q.name) adif+=' <NAME:'+q.name.length+'>'+q.name;
			adif+=' <EOR>\n';
		});
		var blob=new Blob([adif],{type:'text/plain'});
		var a=document.createElement('a');
		a.href=URL.createObjectURL(blob);
		a.download='log-hk4rau-'+dateUTC()+'.adi';
		a.click();
	});

	document.getElementById('log-clear').addEventListener('click',function(){
		if(confirm('¿Borrar todos los QSOs guardados en este navegador?')){log=[]; guardar(); render();}
	});

	document.getElementById('log-call').addEventListener('keydown',function(e){
		if(e.key==='Enter') document.getElementById('log-add').click();
	});

	/* Importar ADIF — parseo simple de los campos que ya usa este log */
	document.getElementById('log-import-btn').addEventListener('click', function(){
		document.getElementById('log-import-file').click();
	});
	document.getElementById('log-import-file').addEventListener('change', function(e){
		var archivo=e.target.files[0];
		if(!archivo) return;
		var lector=new FileReader();
		lector.onload=function(ev){
			var texto=ev.target.result;
			var registros=texto.split(/<eor>/i).slice(0,-1);
			var importados=0;
			registros.forEach(function(registro){
				function campo(tag){
					var m=registro.match(new RegExp('<'+tag+':(\\d+)>([^<]*)','i'));
					return m ? m[2].substring(0,parseInt(m[1])) : '';
				}
				var call=campo('CALL');
				if(!call) return;
				var fecha=campo('QSO_DATE')||dateUTC();
				var horaRaw=campo('TIME_ON')||'000000';
				log.push({
					call: call.toUpperCase(),
					date: fecha,
					time: horaRaw.substring(0,2)+':'+horaRaw.substring(2,4)+':'+(horaRaw.substring(4,6)||'00'),
					freq: campo('FREQ'),
					mode: campo('MODE')||'SSB',
					rstS: campo('RST_SENT')||'59',
					rstR: campo('RST_RCVD')||'59',
					name: campo('NAME'),
				});
				importados++;
			});
			guardar();
			render();
			alert(importados+' QSO(s) importado(s) correctamente.');
			e.target.value='';
		};
		lector.readAsText(archivo);
	});

	render();
})();

/* ── CONSOLA: BITÁCORA RÁPIDA (localStorage, mismo patrón que Favoritos) ── */
(function(){
	var btnGuardar=document.getElementById('bitacora-guardar');
	if(!btnGuardar) return;
	var LOG_KEY='rau_bitacora_rapida';

	function getLog(){
		try { return JSON.parse(localStorage.getItem(LOG_KEY)||'[]'); } catch(e){ return []; }
	}
	function setLog(arr){
		try { localStorage.setItem(LOG_KEY, JSON.stringify(arr.slice(-100))); } catch(e){}
	}
	function hoyISO(){ return new Date().toISOString().slice(0,10); }

	function render(){
		var log=getLog();
		var hoy=hoyISO();
		var deHoy=log.filter(function(q){ return q.fecha===hoy; });
		document.getElementById('bitacora-contador-hoy').textContent=deHoy.length;

		var listaEl=document.getElementById('bitacora-lista');
		var ultimos=log.slice(-8).reverse();
		if(!ultimos.length){
			listaEl.innerHTML='<p class="text-muted-foreground">Sin contactos registrados todavía.</p>';
			return;
		}
		listaEl.innerHTML=ultimos.map(function(q){
			return '<div class="flex justify-between"><span class="text-foreground">'+q.indicativo+'</span><span>'+q.freq+' '+q.modo+' '+(q.rst||'')+'</span></div>';
		}).join('');
	}

	btnGuardar.addEventListener('click', function(){
		var indicativo=(document.getElementById('bitacora-indicativo').value||'').trim().toUpperCase();
		if(!indicativo){ document.getElementById('bitacora-indicativo').focus(); return; }
		var freq=(document.getElementById('bitacora-freq').value||'').trim();
		var modo=document.getElementById('bitacora-modo').value;
		var rst=(document.getElementById('bitacora-rst').value||'').trim();

		var log=getLog();
		log.push({ indicativo:indicativo, freq:freq, modo:modo, rst:rst, fecha:hoyISO(), hora:new Date().toISOString().slice(11,16) });
		setLog(log);

		var confEl=document.getElementById('bitacora-confirmacion');
		if(confEl){
			confEl.innerHTML='✅ QSO registrado — '+indicativo+(freq?' · '+freq+' MHz':'')+' · '+modo+(rst?' · RST '+rst:'');
			confEl.classList.remove('hidden');
			setTimeout(function(){ confEl.classList.add('hidden'); }, 4000);
		}

		document.getElementById('bitacora-indicativo').value='';
		document.getElementById('bitacora-freq').value='';
		document.getElementById('bitacora-rst').value='';
		document.getElementById('bitacora-indicativo').focus();
		render();
	});
	document.getElementById('bitacora-indicativo').addEventListener('keydown', function(e){ if(e.key==='Enter') btnGuardar.click(); });

	render();
})();



/* ── CONSOLA: COPIAR FRECUENCIA AL PORTAPAPELES ── */
(function(){
	document.querySelectorAll('.rau-freq-copiar').forEach(function(btn){
		btn.addEventListener('click', function(){
			var freq=btn.dataset.freq;
			navigator.clipboard.writeText(freq).then(function(){
				var original=btn.textContent;
				btn.textContent='✅ copiado';
				setTimeout(function(){ btn.textContent=original; }, 1200);
			}).catch(function(e){ rauErr(e);});
			// Conexión Frecuencia → Log: pre-llenar la Bitácora Rápida, sin obligar a escribirla a mano
			var bitacoraFreq=document.getElementById('bitacora-freq');
			if(bitacoraFreq) bitacoraFreq.value=freq;
			// En HF, además, el receptor del club va a esa frecuencia (VHF queda fuera de su alcance)
			if(parseFloat(freq)<30 && window.RauRx) window.RauRx.sintonizar(parseFloat(freq)*1000);
		});
	});
})();

/* ── CONSOLA: NOTAS RÁPIDAS (localStorage, se guarda mientras escribes) ── */
(function(){
	var textarea=document.getElementById('consola-notas');
	if(!textarea) return;
	var NOTAS_KEY='rau_notas_rapidas';
	try { textarea.value=localStorage.getItem(NOTAS_KEY)||''; } catch(e){}
	var guardarPendiente=null;
	textarea.addEventListener('input', function(){
		clearTimeout(guardarPendiente);
		guardarPendiente=setTimeout(function(){
			try { localStorage.setItem(NOTAS_KEY, textarea.value); } catch(e){}
		}, 400);
	});
})();

/* ── CONSOLA: CONTEO REAL DEL LOG ADIF — misma clave de localStorage que la herramienta completa ── */
(function(){
	var el=document.getElementById('consola-logadif-conteo');
	if(!el) return;
	try {
		var log=JSON.parse(localStorage.getItem('rau_log_adif')||'[]');
		if(Array.isArray(log) && log.length>0) el.textContent='('+log.length+' QSO'+(log.length===1?'':'s')+')';
	} catch(e){}
})();

/* ── CONSOLA: D-STAR LAST HEARD — dstarusers.org, actividad global (no filtrada a Colombia, ver justificación en functions.php) ── */
rauCuandoVisible('consola-dstar-lista', function(){
	var el=document.getElementById('consola-dstar-lista');
	if(!el) return;
	fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>?action=rau_dstar_lastheard', {cache:'no-store'})
		.then(function(r){ return r.json(); })
		.then(function(json){
			if(!json.success || !json.data || !json.data.length){
				el.innerHTML='No disponible ahora mismo — <a href="https://dstarusers.org/lastheard.php" target="_blank" rel="noopener" class="text-primary hover:underline">ver en dstarusers.org ↗</a>';
				return;
			}
			el.innerHTML=json.data.map(function(r){
				return '<div class="flex justify-between gap-2"><span class="text-foreground">'+r.indicativo+'</span><span class="text-muted-foreground truncate">'+r.nodo+'</span></div>';
			}).join('');
		}).catch(function(e){ rauErr(e);
			el.innerHTML='No disponible ahora mismo — <a href="https://dstarusers.org/lastheard.php" target="_blank" rel="noopener" class="text-primary hover:underline">ver en dstarusers.org ↗</a>';
		});
});

/* ── CONSOLA: WSPR DESDE COLOMBIA — wspr.live, base de datos pública real, sin autenticación (documentado en https://wspr.live/) ──
 * No pude confirmar la conectividad de este servicio desde el entorno de desarrollo (toda petición, incluso
 * la más simple posible, devolvió 400) — puede ser una particularidad de la herramienta de pruebas, no
 * necesariamente del servicio. Esta pieza necesita confirmación real en producción más que cualquier otra. */
rauCuandoVisible('consola-wspr-lista', function(){
	var el=document.getElementById('consola-wspr-lista');
	if(!el) return;
	var NOMBRES_BANDA={ '-1':'LF', '0':'MF', '1':'160m', '3':'80m', '5':'60m', '7':'40m', '10':'30m', '14':'20m', '18':'17m', '21':'15m', '24':'12m', '28':'10m', '50':'6m', '70':'4m', '144':'2m', '432':'70cm', '1296':'23cm' };
	var consulta="SELECT tx_sign, rx_sign, band, distance, snr FROM wspr.rx WHERE time > now() - INTERVAL 30 MINUTE AND (tx_sign LIKE 'HK%' OR tx_sign LIKE 'HJ%') ORDER BY time DESC LIMIT 8 FORMAT JSONCompact";
	var url='https://db1.wspr.live/?query='+encodeURIComponent(consulta);

	var controlador=new AbortController();
	var tiempoAgotado=setTimeout(function(){ controlador.abort(); }, 12000);

	fetch(url, { signal: controlador.signal })
		.then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
		.then(function(json){
			clearTimeout(tiempoAgotado);
			if(!json.data || json.data.length===0){
				el.innerHTML='Sin actividad WSPR detectada en los últimos 30 min. <a href="https://wspr.live/gui" target="_blank" rel="noopener" class="text-primary hover:underline">Ver en WSPR Live ↗</a>';
				return;
			}
			el.innerHTML=json.data.map(function(fila){
				var tx=fila[0], rx=fila[1], banda=NOMBRES_BANDA[String(fila[2])] || fila[2]+'m', dist=fila[3], snr=fila[4];
				return '<div class="flex justify-between gap-2"><span class="text-foreground">'+tx+' → '+rx+'</span><span class="text-muted-foreground">'+banda+' · '+dist+'km · '+snr+'dB</span></div>';
			}).join('');
		})
		.catch(function(e){ rauErr(e);
			clearTimeout(tiempoAgotado);
			el.innerHTML='No se pudo conectar con WSPR Live ahora mismo. <a href="https://wspr.live/gui" target="_blank" rel="noopener" class="text-primary hover:underline">Ver en WSPR Live ↗</a>';
		});
});

/* ── CONSOLA: BRANDMEISTER LAST HEARD — TG 732 (Colombia Nacional, verificado contra 2 fuentes) ──
 * BrandMeister no tiene una API REST pública para esto — solo WebSocket (Socket.IO) en tiempo real.
 * Se conecta directo desde el navegador del usuario, sin pasar por el servidor de RAU.
 * No pude probar la conexión en vivo desde el entorno de desarrollo (BrandMeister estaba bloqueado
 * ahí), así que el manejo de error/tiempo de espera es especialmente cuidadoso aquí. */
(function(){
	var el=document.getElementById('consola-bm-lastheard');
	if(!el) return;
	var TG_COLOMBIA=732;
	var eventos=[];
	var socket=null;
	var seConectoAlgunaVez=false;

	function renderizar(){
		if(eventos.length===0){
			el.innerHTML = seConectoAlgunaVez
				? 'Conectado — esperando actividad en TG '+TG_COLOMBIA+'…'
				: 'Conectando…';
			return;
		}
		el.innerHTML=eventos.slice(0,6).map(function(ev){
			return '<div class="flex justify-between gap-2"><span class="text-foreground">'+ev.call+(ev.nombre?' · '+ev.nombre:'')+'</span><span class="text-muted-foreground">'+ev.hora+'</span></div>';
		}).join('');
	}

	function cargarSocketIO(cb){
		if(window.io){ cb(); return; }
		var script=document.createElement('script');
		script.src='https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.7.2/socket.io.min.js';
		script.onload=cb;
		script.onerror=function(){
			el.innerHTML='No se pudo cargar la conexión con BrandMeister. <a href="https://brandmeister.network/?page=lh" target="_blank" rel="noopener" class="text-primary hover:underline">Ver en BrandMeister ↗</a>';
		};
		document.head.appendChild(script);
	}

	rauCuandoVisible(el, function(){ cargarSocketIO(function(){
		try {
			socket=io('https://api.brandmeister.network', { path:'/lh/socket.io', transports:['websocket'], reconnectionAttempts:3 });
		} catch(e){
			el.innerHTML='No se pudo conectar con BrandMeister. <a href="https://brandmeister.network/?page=lh" target="_blank" rel="noopener" class="text-primary hover:underline">Ver en BrandMeister ↗</a>';
			return;
		}

		socket.on('connect', function(){ seConectoAlgunaVez=true; renderizar(); });

		socket.on('connect_error', function(){
			if(eventos.length===0) el.innerHTML='No se pudo conectar con BrandMeister ahora mismo. <a href="https://brandmeister.network/?page=lh" target="_blank" rel="noopener" class="text-primary hover:underline">Ver en BrandMeister ↗</a>';
		});

		socket.on('mqtt', function(data){
			var payload;
			try { payload=JSON.parse(data.payload); } catch(e){ return; }
			if(!payload || payload.Event!=='Session-Start') return;
			if(parseInt(payload.DestinationID)!==TG_COLOMBIA) return;
			eventos.unshift({
				call: payload.SourceCall || ('ID '+payload.SourceID),
				nombre: payload.SourceName || '',
				hora: new Date().toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'})
			});
			eventos=eventos.slice(0,6);
			renderizar();
		});

		// Si nunca se conecta ni llega nada en 15s, no dejar "Conectando..." colgado para siempre
		setTimeout(function(){
			if(!seConectoAlgunaVez && eventos.length===0){
				el.innerHTML='No se pudo conectar con BrandMeister ahora mismo. <a href="https://brandmeister.network/?page=lh" target="_blank" rel="noopener" class="text-primary hover:underline">Ver en BrandMeister ↗</a>';
			}
		}, 15000);
	}); });

	// Desconexión limpia al salir de la página — no dejar sockets abiertos innecesariamente
	window.addEventListener('beforeunload', function(){
		if(socket) socket.disconnect();
	});
})();

/* ── CONSOLA: MI PROGRESO (DXCC/WAS) — lee el mismo localStorage que la herramienta completa, sin duplicar datos ── */
(function(){
	var el=document.getElementById('consola-dxcc-was');
	if(!el) return;
	var datos={};
	try { datos=JSON.parse(localStorage.getItem('rau_dxcc_was')||'{}'); } catch(e){}
	var dTrab=parseInt(datos.dxccTrab)||0, dConf=parseInt(datos.dxccConf)||0; /* 0 legítimo: nada guardado */
	var wTrab=parseInt(datos.wasTrab)||0, wConf=parseInt(datos.wasConf)||0; /* 0 legítimo: nada guardado */
	var urlDxcc='<?php echo esc_url( home_url( "/herramientas/dxcc/" ) ); ?>';

	if(dTrab===0 && dConf===0 && wTrab===0 && wConf===0){
		el.innerHTML='<p class="font-mono text-[10px] text-muted-foreground">Aún no has registrado tu progreso. <a href="'+urlDxcc+'" class="text-primary hover:underline">Empezar →</a></p>';
		return;
	}

	function fila(etiqueta, trab, conf, total){
		var pct=Math.round((conf/total)*100);
		return '<div>'
			+'<div class="mb-0.5 flex justify-between font-mono text-[9px] text-muted-foreground"><span>'+etiqueta+'</span><span>'+trab+' trab. · '+conf+' conf. de '+total+'</span></div>'
			+'<div class="h-1.5 overflow-hidden rounded-full bg-border/40"><div class="h-full rounded-full bg-primary" style="width:'+pct+'%"></div></div>'
			+'</div>';
	}
	el.innerHTML=fila('DXCC', dTrab, dConf, 340) + fila('WAS', wTrab, wConf, 50)
		+ '<a href="'+urlDxcc+'" class="mt-1 block font-mono text-[9px] text-primary hover:underline">Editar mi progreso →</a>';
})();

/* ── CENTRO DE MANDO: ESTACIONES EN LA BANDA (cluster DX + POTA) ──
   Reemplaza la lista «DX Spots en vivo» de la columna izquierda. Sigue la frecuencia del receptor
   (evento rau:frecuencia): muestra lo que se reporta en esa banda, con país, distancia y rumbo
   desde Mi QTH, y le pasa las estaciones al receptor para dibujarlas sobre la cascada. */
(function(){
	var lista=document.getElementById('consola-banda-lista');
	if(!lista) return;
	var ajaxUrl='<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';
	var bandasHf=[['160m',1800,2000],['80m',3500,4000],['60m',5330,5410],['40m',7000,7300],['30m',10100,10150],['20m',14000,14350],['17m',18068,18168],['15m',21000,21450],['12m',24890,24990],['10m',28000,29700],['6m',50000,54000]];
	var enConteo=['80m','40m','30m','20m','17m','15m','12m','10m'];
	var inicioBanda={'80m':3573,'40m':7074,'30m':10136,'20m':14074,'17m':18100,'15m':21074,'12m':24915,'10m':28074};
	var datos={dx:[], pota:[]}, filtro='banda', khzActual=null;

	function esc(t){ return String(t==null?'':t).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
	function bandaDe(khz){ for(var i=0;i<bandasHf.length;i++){ if(khz>=bandasHf[i][1] && khz<=bandasHf[i][2]) return bandasHf[i][0]; } return null; }
	function geoDe(indicativo){
		if(!indicativo) return null;
		var raw=String(indicativo).toUpperCase().split('/')[0];
		for(var l=4; l>=1; l--){
			var p=PFX_GEO[raw.slice(0,l)];
			if(p){ var x=p.split('|'); return {nombre:(x[0]||'').replace(/^[^\s]+\s/, ''), lat:parseFloat(x[4]), lng:parseFloat(x[5])}; }
		}
		return null;
	}
	function distRumbo(lat, lng){
		if(isNaN(lat) || isNaN(lng)) return null;
		var q=rauQth(), r=Math.PI/180, f1=q.lat*r, f2=lat*r, dl=(lng-q.lng)*r;
		var d=2*6371*Math.asin(Math.sqrt(Math.pow(Math.sin((f2-f1)/2),2)+Math.cos(f1)*Math.cos(f2)*Math.pow(Math.sin(dl/2),2)));
		var y=Math.sin(dl)*Math.cos(f2), x=Math.cos(f1)*Math.sin(f2)-Math.sin(f1)*Math.cos(f2)*Math.cos(dl);
		return {km:Math.round(d), az:Math.round((Math.atan2(y,x)/r+360)%360)};
	}
	function minutosDesde(utc){
		// El cluster da "HH:MM" UTC; POTA una fecha ISO en UTC sin zona
		var ahora=new Date(), m;
		if(/^\d{2}:\d{2}$/.test(utc||'')){ m=ahora.getUTCHours()*60+ahora.getUTCMinutes()-(parseInt(utc.slice(0,2),10)*60+parseInt(utc.slice(3),10)); if(m<0) m+=1440; return m>180?null:m; }
		var t=Date.parse(String(utc||'').replace(/Z?$/,'Z')); if(isNaN(t)) return null;
		m=Math.round((ahora.getTime()-t)/60000); return m<0||m>180?null:m;
	}
	function modoPista(texto){ var t=String(texto||'').toUpperCase(); var m=t.match(/\b(FT8|FT4|CW|SSB|USB|LSB|RTTY|PSK31|FM|AM|JS8)\b/); return m?m[1]:''; }
	function todas(){
		var l=[];
		if(document.getElementById('consola-banda-dx').checked) datos.dx.forEach(function(s){
			var khz=parseFloat(s.freq)*1000, g=geoDe(s.dx);
			if(!(khz>0)) return;
			l.push({fuente:'dx', dx:String(s.dx||'').toUpperCase(), khz:khz, modo:modoPista(s.info), lugar:g?g.nombre:'', dr:g?distRumbo(g.lat,g.lng):null, min:minutosDesde(s.t), info:s.info||''});
		});
		var chkOido=document.getElementById('consola-banda-oido');
		if(chkOido && chkOido.checked && window.rauOido) window.rauOido.reportes.forEach(function(s){
			var g=s.pais ? null : geoDe(s.c);
			l.push({fuente:'oido', dx:s.c, khz:s.f, modo:s.m, lugar:s.pais||(g?g.nombre:''), dr:distRumbo(s.lat, s.lng), min:Math.max(0, Math.round((Date.now()/1000-s.t)/60)), info:'Oído en HK4RAU con '+s.snr+' dB a '+s.km.toLocaleString('es-CO')+' km del receptor'});
		});
		if(document.getElementById('consola-banda-pota').checked) datos.pota.forEach(function(s){
			var khz=parseFloat(s.frequency);
			if(!(khz>0)) return;
			l.push({fuente:'pota', dx:String(s.activator||'').toUpperCase(), khz:khz, modo:String(s.mode||'').toUpperCase(), lugar:[s.reference, s.locationDesc].filter(Boolean).join(' · '), dr:distRumbo(parseFloat(s.latitude), parseFloat(s.longitude)), min:minutosDesde(s.spotTime), info:s.name||''});
		});
		return l;
	}
	function bandaActual(){ return khzActual ? bandaDe(khzActual) : null; }

	function render(){
		var l=todas(), banda=bandaActual();
		if(window.RauRx && window.RauRx.ponerSpots) window.RauRx.ponerSpots(l);
		// Conteo por banda: dónde hay movimiento ahora
		var cuenta={}; l.forEach(function(s){ var b=bandaDe(s.khz); if(b) cuenta[b]=(cuenta[b]||0)+1; });
		var maximo=Math.max.apply(null, enConteo.map(function(b){ return cuenta[b]||0; }).concat([1]));
		document.getElementById('consola-banda-conteo').innerHTML=enConteo.map(function(b){
			var n=cuenta[b]||0, alto=Math.max(8, Math.round(100*n/maximo)), activa=b===banda;
			return '<button type="button" class="rau-banda-conteo flex flex-col items-center gap-0.5 rounded-md border px-0.5 py-1 font-mono text-[10px] '+(activa?'border-amber-400/70 text-amber-400':'border-border/60 text-muted-foreground hover:border-primary/40')+'" data-banda="'+b+'" aria-label="'+b+': '+n+' estaciones. Sintonizar">'
				+'<span class="flex h-6 w-full items-end"><span class="w-full rounded-sm '+(activa?'bg-amber-400':'bg-primary/60')+'" style="height:'+alto+'%"></span></span>'+b+'<span>'+n+'</span></button>';
		}).join('');
		document.getElementById('consola-banda-nombre').textContent = filtro==='banda' && banda ? banda : 'todas las bandas';
		var vis=l.filter(function(s){ return filtro==='todas' || !banda || bandaDe(s.khz)===banda; })
			.sort(function(a,b){ return (a.min==null?999:a.min)-(b.min==null?999:b.min); }).slice(0,40);
		if(!vis.length){
			lista.innerHTML='<p class="py-2 text-muted-foreground">'+(l.length ? 'Nadie reportado en '+(banda||'esta banda')+' en este momento. Puede estar abierta y desierta: buen momento para llamar CQ. Mira el conteo de arriba para ver dónde hay movimiento.' : 'Sin estaciones reportadas ahora mismo.')+'</p>';
			return;
		}
		lista.innerHTML=vis.map(function(s){
			var color=s.fuente==='pota'?'text-emerald-400':s.fuente==='oido'?'text-sky-400':'text-amber-400';
			var lugar=[s.lugar, s.dr ? s.dr.km.toLocaleString('es-CO')+' km · '+s.dr.az+'°' : '', s.min!=null ? (s.min<1?'ahora':'hace '+s.min+' min') : ''].filter(Boolean).join(' · ');
			var mhz=(s.khz/1000).toFixed(3);
			return '<div class="flex items-center gap-2 border-b border-border/30 py-1 last:border-0">'
				+'<span class="'+color+'" aria-hidden="true">▼</span>'
				+'<div class="min-w-0 flex-1"><div class="flex items-baseline gap-2"><span class="font-bold text-foreground">'+esc(s.dx)+'</span><span class="text-primary">'+mhz+'</span>'+(s.modo?'<span class="text-[10px] text-muted-foreground">'+esc(s.modo)+'</span>':'')+'</div>'
				+'<div class="truncate text-[10px] text-muted-foreground" title="'+esc(s.info)+'">'+esc(lugar)+'</div></div>'
				+'<button type="button" class="rau-escuchar shrink-0 rounded px-1.5 py-1 hover:bg-primary/15" data-khz="'+s.khz+'" data-modo="'+esc(s.modo)+'" title="Sintonizar en el receptor" aria-label="Escuchar '+esc(s.dx)+' en '+mhz+' MHz">🎧</button>'
				+'<button type="button" class="rau-anotar shrink-0 rounded px-1.5 py-1 hover:bg-primary/15" data-dx="'+esc(s.dx)+'" data-mhz="'+mhz+'" data-modo="'+esc(s.modo)+'" title="Pasar a la bitácora" aria-label="Anotar '+esc(s.dx)+' en la bitácora">📝</button>'
				+'<button type="button" class="rau-ubicar shrink-0 rounded px-1.5 py-1 hover:bg-primary/15" data-dx="'+esc(s.dx)+'" title="Ubicar en el mapa" aria-label="Ubicar '+esc(s.dx)+' en el mapa">📏</button>'
				+'</div>';
		}).join('');
	}

	lista.addEventListener('click', function(e){
		var b=e.target.closest('button'); if(!b) return;
		if(b.classList.contains('rau-anotar')){
			var ind=document.getElementById('bitacora-indicativo'), fr=document.getElementById('bitacora-freq'), mo=document.getElementById('bitacora-modo');
			if(ind) ind.value=b.dataset.dx;
			if(fr){ fr.value=b.dataset.mhz; fr.dataset.auto='0'; }
			if(mo && b.dataset.modo){
				var m=b.dataset.modo==='USB'||b.dataset.modo==='LSB' ? 'SSB' : b.dataset.modo;
				if([].some.call(mo.options, function(o){ return o.value===m; })){ mo.value=m; mo.dataset.auto='0'; }
			}
			if(typeof window.rauIrPestana==='function') window.rauIrPestana('operar');
			var rst=document.getElementById('bitacora-rst'); if(rst) rst.focus({preventScroll:true});
			var conf=document.getElementById('bitacora-confirmacion');
			if(conf){ conf.textContent='✏️ '+b.dataset.dx+' listo en la bitácora: completa el RST y registra.'; conf.classList.remove('hidden'); setTimeout(function(){ conf.classList.add('hidden'); }, 4000); }
		} else if(b.classList.contains('rau-ubicar')){
			if(typeof window.rauIrAIndicativo==='function'){
				window.rauIrAIndicativo(b.dataset.dx);
				var mapa=document.getElementById('consola-mapa-bloque'); if(mapa) mapa.scrollIntoView({behavior:'smooth', block:'center'});
			}
		}
	});
	document.getElementById('consola-banda-conteo').addEventListener('click', function(e){
		var b=e.target.closest('.rau-banda-conteo'); if(!b) return;
		filtro='banda'; marcarFiltro();
		if(window.RauRx) window.RauRx.sintonizar(inicioBanda[b.dataset.banda]); else { khzActual=inicioBanda[b.dataset.banda]; render(); }
	});
	function marcarFiltro(){ document.querySelectorAll('.rau-banda-filtro').forEach(function(x){ x.classList.toggle('is-active', x.dataset.filtro===filtro); x.setAttribute('aria-pressed', x.dataset.filtro===filtro); }); }
	document.querySelectorAll('.rau-banda-filtro').forEach(function(x){ x.addEventListener('click', function(){ filtro=x.dataset.filtro; marcarFiltro(); render(); }); });
	['consola-banda-dx','consola-banda-pota','consola-banda-oido'].forEach(function(id){ var el=document.getElementById(id); if(el) el.addEventListener('change', render); });
	document.addEventListener('rau:oido', render);
	document.addEventListener('rau:frecuencia', function(ev){ var b0=bandaActual(); khzActual=ev.detail.khz; if(bandaActual()!==b0) render(); });
	document.addEventListener('rau:estacion', render);
	marcarFiltro();

	function cargarDx(){
		var controlador=new AbortController();
		var tiempoAgotado=setTimeout(function(){ controlador.abort(); }, 15000);
		fetch(ajaxUrl+'?action=rau_dx_spots', {cache:'no-store', signal: controlador.signal})
			.then(function(r){ clearTimeout(tiempoAgotado); return r.json(); })
			.then(function(json){
				datos.dx = ( json.success && json.data ) ? json.data : [];
				window.rauUltimosDxSpots = datos.dx;
				document.dispatchEvent(new CustomEvent('rau:spots'));
				var rnDxEl=document.getElementById('rn-dx');
				if(rnDxEl) rnDxEl.innerHTML = datos.dx.length ? '🌎 DX: <span class="text-primary font-bold">'+datos.dx.length+' spots</span>' : '🌎 DX: sin spots';
				render();
			}).catch(function(e){ rauErr(e);
				clearTimeout(tiempoAgotado);
				var rnDxErr=document.getElementById('rn-dx');
				if(rnDxErr) rnDxErr.innerHTML='🌎 DX: <span class="text-rose-400">sin datos</span>';
				render();
			});
	}
	function cargarPota(){
		fetch(ajaxUrl+'?action=rau_pota_spots', {cache:'no-store'})
			.then(function(r){ return r.json(); })
			.then(function(json){ datos.pota = json.success && Array.isArray(json.data) ? json.data : []; window.rauUltimosPota=datos.pota; render(); document.dispatchEvent(new CustomEvent('rau:spots')); })
			.catch(function(e){ rauErr(e); });
	}
	if(window.RauRx && window.RauRx.estado) khzActual=window.RauRx.estado().khz;
	cargarDx(); setInterval(cargarDx, 2*60*1000);
	rauCuandoVisible(lista, function(){ cargarPota(); setInterval(cargarPota, 3*60*1000); });
})();

/* ── EL RECEPTOR COMO SENSOR DE PROPAGACIÓN (19.47.0) ──
   Lo que oyen los decodificadores FT8 y WSPR del KiwiSDR en los últimos 30 minutos (PSK Reporter +
   wspr.live, leídos por el servidor cada 5 minutos) y, con 30 días de WSPR, a qué hora abre cada
   banda hacia cada región. Publica window.rauOido y el evento rau:oido: lo usan el semáforo de
   bandas, la lista de estaciones, la cascada, el mapa y «Ahora mismo». */
(function(){
	var tarjeta=document.getElementById('consola-oido'), tabla=document.getElementById('ap-tabla');
	if(!tarjeta && !tabla) return;
	var ajaxUrl='<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';
	var regiones={NA:'Norteamérica', EU:'Europa', SA:'Sur y Centroamérica', AF:'África', AS:'Asia', OC:'Oceanía'};
	var orden=['160m','80m','60m','40m','30m','20m','17m','15m','12m','10m','6m'];
	var inicioBanda={'160m':1840,'80m':3573,'40m':7074,'30m':10136,'20m':14074,'17m':18100,'15m':21074,'12m':24915,'10m':28074};
	var region='todas';
	function esc(t){ return String(t==null?'':t).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
	function horaCol(){ return (new Date().getUTCHours()+19)%24; }

	function semaforo(o){
		window.rauOidoSemaforo={};
		var mejor=null;
		o.vigiladas.forEach(function(b){
			var n=o.bandas[b] ? o.bandas[b].n : 0, sem=n>=5?'🟢':n>=1?'🟡':'🔴';
			window.rauOidoSemaforo[b]=sem;
			var el=document.querySelector('#consola-estado-bandas [data-banda="'+b+'"] span:last-child');
			if(el){ el.textContent=sem; el.parentNode.title=b+': medido en HK4RAU ('+n+' estaciones en 30 min)'; }
			if(n>=3 && (!mejor || orden.indexOf(b)>orden.indexOf(mejor))) mejor=b;
		});
		window.rauMejorMedida=mejor;
		var mb=document.getElementById('consola-mejor-banda');
		if(mejor && mb) mb.textContent=mejor+' (medida)';
	}
	function pintarTarjeta(o){
		var el=document.getElementById('consola-oido-bandas');
		if(!el) return;
		var filas=o.vigiladas.slice().sort(function(a,b){ return orden.indexOf(a)-orden.indexOf(b); }).map(function(b){
			var m=o.bandas[b], n=m?m.n:0;
			var col=n>=5?'text-emerald-400':n>=1?'text-amber-400':'text-rose-400';
			var regs=m ? Object.keys(m.regiones).sort(function(x,y){ return m.regiones[y]-m.regiones[x]; }).slice(0,3).map(function(r){ return regiones[r]+' '+m.regiones[r]; }).join(' · ') : 'nadie oído: cerrada o sin actividad';
			return '<button type="button" class="rau-oido-banda flex w-full items-center gap-2 rounded-md px-1.5 py-1 text-left hover:bg-primary/10" data-banda="'+b+'" title="'+(m?'Más lejana: '+esc(m.lejos.c)+' a '+m.lejos.km.toLocaleString('es-CO')+' km · mejor señal '+m.mejor_snr+' dB':'')+'. Toca para sintonizar">'
				+'<span class="w-9 shrink-0 font-bold text-foreground">'+b+'</span><span class="w-8 shrink-0 text-right '+col+'">'+n+'</span>'
				+'<span class="min-w-0 flex-1 truncate text-[10px]">'+regs+'</span>'+(m?'<span class="shrink-0 text-[10px] text-sky-400">'+Math.round(m.lejos.km/1000)+' mil km</span>':'')+'</button>';
		});
		var sin=orden.filter(function(b){ return inicioBanda[b] && o.vigiladas.indexOf(b)<0 && b!=='160m'; });
		el.innerHTML=filas.join('')+(sin.length?'<p class="px-1.5 pt-1 text-[9px] text-muted-foreground">'+sin.join(', ')+': el receptor no las vigila; ahí el semáforo usa el índice solar.</p>':'');
		var min=Math.max(0, Math.round((Date.now()/1000-o.actualizado)/60));
		document.getElementById('consola-oido-nota').textContent='FT8 y WSPR decodificados por el receptor · '+(o.viejo?'sin conexión con las fuentes, último dato de hace '+min+' min':'actualizado hace '+(min<1?'menos de 1':min)+' min');
	}
	function pintarHora(a){
		var el=document.getElementById('consola-oido-hora');
		if(!el || !a) return;
		var h=horaCol(), l=[];
		Object.keys(a.bandas).forEach(function(b){ var c=a.bandas[b][h]||{}; Object.keys(c).forEach(function(r){ if(c[r]>=50) l.push({b:b, r:r, p:c[r]}); }); });
		l.sort(function(x,y){ return y.p-x.p; });
		el.innerHTML = l.length
			? '<span class="text-foreground">A esta hora ('+h+' h) suele abrir:</span> '+l.slice(0,4).map(function(x){ return x.b+' → '+regiones[x.r]+' '+x.p+' %'; }).join(' · ')+' <span class="text-muted-foreground">('+a.dias+' día'+(a.dias===1?'':'s')+' de datos)</span>'
			: '<span class="text-foreground">A esta hora ('+h+' h)</span> ninguna banda abre más de la mitad de los días ('+a.dias+' días de datos).';
		el.classList.remove('hidden');
	}
	function pintarTabla(a){
		if(!tabla) return;
		if(!a){ tabla.textContent='No se pudieron leer las mediciones de WSPR en este momento.'; return; }
		var h=horaCol(), cab='<div class="grid gap-px" style="grid-template-columns:3.5rem repeat(24,minmax(0,1fr))"><span></span>';
		for(var i=0;i<24;i++) cab+='<span class="text-center'+(i===h?' text-amber-400':'')+'">'+i+'</span>';
		var filas=Object.keys(a.bandas).map(function(b){
			var f='<button type="button" class="ap-banda text-left font-bold text-foreground hover:text-primary" data-banda="'+b+'" title="Escuchar '+b+' en el receptor">'+b+' 🎧</button>';
			for(var i=0;i<24;i++){
				var c=a.bandas[b][i]||{}, v=region==='todas' ? Math.max.apply(null, Object.keys(c).map(function(r){ return c[r]; }).concat([0])) : (c[region]||0);
				var detalle=Object.keys(c).sort(function(x,y){ return c[y]-c[x]; }).map(function(r){ return regiones[r]+' '+c[r]+' %'; }).join(', ');
				f+='<span class="h-7 rounded-sm'+(i===h?' ring-1 ring-amber-400':'')+'" style="background:rgb(56 189 248 / '+(v?(0.12+0.88*v/100).toFixed(2):'0.04')+')" title="'+b+' a las '+i+' h: '+(detalle||'nada oído')+'"></span>';
			}
			return f;
		}).join('');
		tabla.innerHTML=cab+filas+'</div>';
		var desde=new Date(a.desde*1000).toLocaleDateString('es-CO',{day:'numeric', month:'long'});
		var nota=document.getElementById('ap-nota');
		if(nota) nota.textContent='Basado en '+a.dias+' día'+(a.dias===1?'':'s')+' de recepción WSPR de HK4RAU (desde el '+desde+'). Con más días el mapa se vuelve más confiable; se actualiza cada 6 horas. Solo aparecen las bandas que el receptor vigila.';
	}
	document.addEventListener('click', function(e){
		var b=e.target.closest('.rau-oido-banda, .ap-banda, .ap-region'); if(!b) return;
		if(b.classList.contains('ap-region')){
			region=b.dataset.r;
			document.querySelectorAll('.ap-region').forEach(function(x){ x.classList.toggle('is-active', x===b); x.setAttribute('aria-pressed', x===b); });
			pintarTabla(window.rauAperturas); return;
		}
		var f=inicioBanda[b.dataset.banda]; if(!f) return;
		if(b.classList.contains('ap-banda')){ if(typeof window.rauEscuchar==='function') window.rauEscuchar(f, 'FT8'); return; }
		if(window.RauRx) window.RauRx.sintonizar(f, 'usb');
		if(typeof window.rauIrPestana==='function') window.rauIrPestana('escuchar');
	});

	function cargar(){
		fetch(ajaxUrl+'?action=rau_sdr_oido', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
			if(!j.success){ var el=document.getElementById('consola-oido-bandas'); if(el) el.textContent='No se pudo leer lo que oye el receptor en este momento.'; return; }
			window.rauOido=j.data;
			semaforo(j.data); pintarTarjeta(j.data);
			document.dispatchEvent(new CustomEvent('rau:oido', {detail:j.data}));
		}).catch(function(e){ rauErr(e); });
	}
	function cargarAperturas(){
		fetch(ajaxUrl+'?action=rau_sdr_aperturas', {cache:'no-store'}).then(function(r){ return r.json(); }).then(function(j){
			window.rauAperturas=j.success ? j.data : null;
			pintarHora(window.rauAperturas); pintarTabla(window.rauAperturas);
		}).catch(function(e){ rauErr(e); pintarTabla(null); });
	}
	var arrancado=false;
	function arrancar(){ if(arrancado) return; arrancado=true; cargar(); setInterval(cargar, 5*60*1000); cargarAperturas(); }
	if(tarjeta) rauCuandoVisible('consola-columnas', arrancar);
	if(tabla) rauCuandoVisible(tabla, arrancar);
})();

/* ── CENTRO DE MANDO: AVÍSAME SI APARECE ──
   Una lista de indicativos, prefijos o bandas que la persona quiere atrapar. Cada vez que llegan
   spots del cluster, de POTA o lo que oye el receptor, se revisa; lo nuevo se avisa una sola vez
   por sesión con un tono y una notificación del navegador. Una banda («10m») avisa cuando el
   receptor del club empieza a oír estaciones en ella, como máximo una vez por hora. */
(function(){
	var card=document.getElementById('consola-avisos');
	if(!card) return;
	var bandas=[['160m',1800,2000],['80m',3500,4000],['40m',7000,7300],['30m',10100,10150],['20m',14000,14350],['17m',18068,18168],['15m',21000,21450],['12m',24890,24990],['10m',28000,29700],['6m',50000,54000]];
	function bandaDe(k){ var b=null; bandas.forEach(function(x){ if(k>=x[1] && k<=x[2]) b=x[0]; }); return b; }
	function esc(t){ return String(t==null?'':t).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
	function leer(){ try { var l=JSON.parse(localStorage.getItem('rau_avisos')||'[]'); return Array.isArray(l)?l:[]; } catch(e){ return []; } }
	function guardar(l){ try { localStorage.setItem('rau_avisos', JSON.stringify(l)); } catch(e){} }
	var vistos={}; try { vistos=JSON.parse(sessionStorage.getItem('rau_avisos_vistos')||'{}')||{}; } catch(e){}
	var hallados=[];

	function pintarLista(){
		var l=leer(), el=document.getElementById('avisos-lista');
		el.innerHTML = l.length ? l.map(function(t, i){ return '<span class="inline-flex items-center gap-1 rounded-md border border-border bg-background px-2 py-0.5 font-mono text-[11px]">'+esc(t)+'<button type="button" class="avisos-quitar text-muted-foreground hover:text-rose-400" data-i="'+i+'" aria-label="Dejar de vigilar '+esc(t)+'">×</button></span>'; }).join('')
			: '<span class="text-[10px] text-muted-foreground">Nada en vigilancia todavía.</span>';
	}
	function pintarHallados(){
		document.getElementById('avisos-hallados').innerHTML=hallados.map(function(h){
			return '<div class="flex items-center justify-between gap-2 rounded-md border border-amber-400/30 bg-amber-400/5 px-2 py-1"><span><span class="font-bold text-amber-400">'+esc(h.titulo)+'</span> <span class="text-muted-foreground">'+esc(h.detalle)+'</span></span>'
				+(h.khz?'<button type="button" class="rau-escuchar shrink-0 rounded px-1 hover:bg-primary/15" data-khz="'+h.khz+'" aria-label="Escuchar '+esc(h.titulo)+'">🎧</button>':'')+'</div>';
		}).join('');
	}
	function estaciones(){
		var l=[];
		(window.rauUltimosDxSpots||[]).forEach(function(s){ var k=parseFloat(s.freq)*1000; if(s.dx && k>0) l.push({dx:String(s.dx).toUpperCase(), khz:k, fuente:'cluster DX'}); });
		(window.rauUltimosPota||[]).forEach(function(s){ var k=parseFloat(s.frequency); if(s.activator && k>0) l.push({dx:String(s.activator).toUpperCase(), khz:k, fuente:'POTA '+(s.reference||'')}); });
		((window.rauOido&&window.rauOido.reportes)||[]).forEach(function(s){ l.push({dx:s.c, khz:s.f, fuente:'oído en HK4RAU '+s.snr+' dB'}); });
		return l;
	}
	function avisar(nuevos){
		try { var ctx=new (window.AudioContext||window.webkitAudioContext)(), o=ctx.createOscillator(), g=ctx.createGain(); o.frequency.value=988; g.gain.value=0.12; o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime+0.25); } catch(e){}
		if(window.Notification && Notification.permission==='granted'){
			try { new Notification('RAU · '+(nuevos.length===1 ? nuevos[0].titulo : nuevos.length+' avisos'), {body: nuevos.map(function(n){ return n.titulo+' '+n.detalle; }).slice(0,4).join('\n')}); } catch(e){}
		}
	}
	function revisar(){
		var l=leer(); if(!l.length) return;
		var nuevos=[], hora=new Date().toISOString().slice(0,13);
		l.forEach(function(t){
			if(/^\d+M$/.test(t)){
				var b=t.toLowerCase(), m=window.rauOido && window.rauOido.bandas[b], clave='banda|'+b+'|'+hora;
				if(m && m.n>0 && !vistos[clave]){ vistos[clave]=1; nuevos.push({titulo:b+' abierta', detalle:'el receptor oye '+m.n+' estaciones (la más lejana, '+m.lejos.c+')', khz:0}); }
				return;
			}
			estaciones().forEach(function(e){
				if(e.dx.indexOf(t)!==0) return;
				var clave=t+'|'+e.dx+'|'+bandaDe(e.khz);
				if(vistos[clave]) return;
				vistos[clave]=1; nuevos.push({titulo:e.dx, detalle:(e.khz/1000).toFixed(3)+' MHz · '+e.fuente, khz:e.khz});
			});
		});
		try { sessionStorage.setItem('rau_avisos_vistos', JSON.stringify(vistos)); } catch(e){}
		if(!nuevos.length) return;
		hallados=nuevos.concat(hallados).slice(0,8); pintarHallados(); avisar(nuevos);
	}
	function agregar(){
		var inp=document.getElementById('avisos-nuevo'), t=String(inp.value||'').toUpperCase().replace(/[^A-Z0-9\/]/g,'');
		if(!t) return;
		var l=leer(); if(l.indexOf(t)<0){ l.push(t); guardar(l.slice(0,20)); }
		inp.value=''; pintarLista();
		if(window.Notification && Notification.permission==='default') Notification.requestPermission().catch(function(){});
		revisar();
	}
	document.getElementById('avisos-agregar').addEventListener('click', agregar);
	document.getElementById('avisos-nuevo').addEventListener('keydown', function(e){ if(e.key==='Enter') agregar(); });
	document.getElementById('avisos-lista').addEventListener('click', function(e){ var b=e.target.closest('.avisos-quitar'); if(!b) return; var l=leer(); l.splice(+b.dataset.i,1); guardar(l); pintarLista(); });
	document.addEventListener('rau:spots', revisar);
	document.addEventListener('rau:oido', revisar);
	pintarLista();
})();

/* ── CENTRO DE MANDO: TODO SIGUE LA FRECUENCIA DEL RECEPTOR ──
   El receptor avisa cada cambio con el evento rau:frecuencia. La bitácora toma la frecuencia y el
   modo (mientras la persona no los haya escrito a mano), las bandas del mapa marcan la actual y el
   banco «Construir antena» calcula para esa frecuencia. Tocar una banda del mapa sintoniza el receptor. */
(function(){
	var inicioBanda={'80m':3573,'40m':7074,'30m':10136,'20m':14074,'17m':18100,'15m':21074,'12m':24915,'10m':28074};
	var bandas=[['80m',3500,4000],['40m',7000,7300],['30m',10100,10150],['20m',14000,14350],['17m',18068,18168],['15m',21000,21450],['12m',24890,24990],['10m',28000,29700]];
	var aBitacora={usb:'SSB', lsb:'SSB', cw:'CW', nbfm:'FM'};
	var fr=document.getElementById('bitacora-freq'), mo=document.getElementById('bitacora-modo'), banco=document.getElementById('banco-freq');
	if(fr){ fr.dataset.auto='1'; fr.addEventListener('input', function(){ fr.dataset.auto = fr.value ? '0' : '1'; }); }
	if(mo){ mo.dataset.auto='1'; mo.addEventListener('change', function(){ mo.dataset.auto='0'; }); }
	var guardar=document.getElementById('bitacora-guardar');
	if(guardar) guardar.addEventListener('click', function(){ setTimeout(function(){ if(fr){ fr.dataset.auto='1'; aplicar(window.RauRx && window.RauRx.estado()); } if(mo) mo.dataset.auto='1'; }, 0); });

	function aplicar(d){
		if(!d) return;
		if(fr && fr.dataset.auto==='1' && document.activeElement!==fr) fr.value=(d.khz/1000).toFixed(3);
		if(mo && mo.dataset.auto==='1' && aBitacora[d.modo]) mo.value=aBitacora[d.modo];
		if(banco && document.activeElement!==banco && d.khz>=1800){ banco.value=(d.khz/1000).toFixed(3); banco.dispatchEvent(new Event('input')); }
		var b=null; bandas.forEach(function(x){ if(d.khz>=x[1] && d.khz<=x[2]) b=x[0]; });
		document.querySelectorAll('.rau-banda-tile').forEach(function(t){ t.classList.toggle('rau-banda-actual', t.dataset.banda===b); });
	}
	document.addEventListener('rau:frecuencia', function(ev){ aplicar(ev.detail); });
	document.querySelectorAll('.rau-banda-tile').forEach(function(t){
		function sintonizar(){ if(window.RauRx && inicioBanda[t.dataset.banda]) window.RauRx.sintonizar(inicioBanda[t.dataset.banda], 'usb'); }
		t.addEventListener('click', sintonizar);
		t.addEventListener('keydown', function(e){ if(e.key==='Enter' || e.key===' ') sintonizar(); });
		t.setAttribute('aria-label', 'Banda '+t.dataset.banda+': ver por qué tiene este estado y sintonizarla en el receptor');
	});
	// «Mejor banda ahora» de la franja superior: un clic la sintoniza
	var mejorBtn=document.getElementById('consola-mejor-banda-btn');
	if(mejorBtn) mejorBtn.addEventListener('click', function(){
		var b=((document.getElementById('consola-mejor-banda').textContent||'').match(/\d+m/)||[''])[0];
		if(!window.RauRx || !inicioBanda[b]) return;
		if(typeof window.rauIrPestana==='function') window.rauIrPestana('escuchar');
		window.RauRx.sintonizar(inicioBanda[b], 'usb');
		var app=document.getElementById('rx-app'); if(app) app.scrollIntoView({block:'start', behavior:'smooth'});
	});
	if(window.RauRx && window.RauRx.estado) aplicar(window.RauRx.estado());
})();

/* ── CENTRO DE MANDO: MI ESTACIÓN ──
   Indicativo, locator, potencia y antena, una sola vez y solo en este navegador. El locator se
   guarda también como Mi QTH (rau_mi_qth), que es lo que ya leen el mapa, «Ahora mismo» y las
   distancias. El indicativo llena los campos «mi indicativo» de la QSL, el certificado, PSK
   Reporter y RBN cuando están vacíos. */
(function(){
	var card=document.getElementById('consola-estacion');
	if(!card) return;
	var campos=['indicativo','locator','potencia','antena'];
	function leer(){ try { return JSON.parse(localStorage.getItem('rau_estacion')||'{}')||{}; } catch(e){ return {}; } }
	function gridACoord(g){
		g=String(g||'').trim(); var A='ABCDEFGHIJKLMNOPQR', a='abcdefghijklmnopqr';
		if(!/^[A-Ra-r]{2}[0-9]{2}([A-Xa-x]{2})?$/.test(g)) return null;
		var lng=A.indexOf(g[0].toUpperCase())*20-180+parseInt(g[2],10)*2, lat=A.indexOf(g[1].toUpperCase())*10-90+parseInt(g[3],10);
		if(g.length>=6){ lng+=a.indexOf(g[4].toLowerCase())*(2/24)+(1/24); lat+=a.indexOf(g[5].toLowerCase())*(1/24)+(1/48); } else { lng+=1; lat+=0.5; }
		return {lat:lat, lng:lng};
	}
	function resumen(){
		var e=leer(), r=document.getElementById('estacion-resumen'), chip=document.getElementById('consola-estacion-chip');
		if(!e.indicativo && !e.locator){ return; }
		r.innerHTML='<span class="font-bold text-foreground">'+String(e.indicativo||'—').replace(/[<>&"]/g,'')+'</span> · '+[e.locator, e.potencia?e.potencia+' W':'', e.antena].filter(Boolean).join(' · ').replace(/[<>&"]/g,'');
		if(chip) chip.textContent='⚙ '+(e.indicativo||'Mi estación');
	}
	function prellenar(){
		var e=leer(); if(!e.indicativo) return;
		['qsl-de','cert-indicativo','psk-indicativo','rbn-indicativo'].forEach(function(id){
			var el=document.getElementById(id);
			if(el && !el.value){ el.value=e.indicativo; el.dispatchEvent(new Event('input', {bubbles:true})); }
		});
	}
	function abrir(on){
		var f=document.getElementById('estacion-form'), b=document.getElementById('estacion-editar');
		f.classList.toggle('hidden', !on); b.setAttribute('aria-expanded', on); b.textContent=on?'Cerrar':'Configurar';
		if(on){ var e=leer(); campos.forEach(function(c){ document.getElementById('estacion-'+c).value=e[c]||''; }); }
	}
	document.getElementById('estacion-editar').addEventListener('click', function(){ abrir(document.getElementById('estacion-form').classList.contains('hidden')); });
	document.getElementById('estacion-guardar').addEventListener('click', function(){
		var e={}, estado=document.getElementById('estacion-estado');
		campos.forEach(function(c){ e[c]=(document.getElementById('estacion-'+c).value||'').trim(); });
		e.indicativo=e.indicativo.toUpperCase(); e.locator=e.locator ? e.locator.slice(0,2).toUpperCase()+e.locator.slice(2,4)+e.locator.slice(4).toLowerCase() : '';
		var coords=null;
		if(e.locator){ coords=gridACoord(e.locator); if(!coords){ estado.textContent='Locator inválido: usa el formato FJ26 o FJ26ff.'; return; } }
		var antes=leer();
		try { localStorage.setItem('rau_estacion', JSON.stringify(e)); if(coords) localStorage.setItem('rau_mi_qth', JSON.stringify(coords)); } catch(err){ estado.textContent='Este navegador no permite guardar datos.'; return; }
		resumen(); prellenar(); abrir(false);
		document.dispatchEvent(new CustomEvent('rau:estacion', {detail:e}));
		var r=document.getElementById('estacion-resumen');
		if(coords && antes.locator!==e.locator){
			// El mapa y los satélites leen el QTH al cargar: se recarga, salvo que el receptor esté sonando
			if(window.RauRx && window.RauRx.encendido()) r.insertAdjacentHTML('beforeend', '<br><span class="text-amber-400">El mapa usará tu locator la próxima vez que abras la página.</span>');
			else { r.insertAdjacentHTML('beforeend', '<br><span class="text-primary">Guardado. Recargando el mapa…</span>'); setTimeout(function(){ location.reload(); }, 900); }
		}
	});
	var chip=document.getElementById('consola-estacion-chip');
	if(chip) chip.addEventListener('click', function(){
		if(typeof window.rauIrPestana==='function') window.rauIrPestana('operar');
		abrir(true); card.scrollIntoView({block:'center', behavior:'smooth'});
	});
	resumen(); prellenar();
})();

/* ── CENTRO DE MANDO: PESTAÑAS EN CELULAR Y ESTADO DEL RECEPTOR EN LA FRANJA SUPERIOR ── */
(function(){
	var cols=document.getElementById('consola-columnas'), nav=document.getElementById('consola-pestanas');
	if(!cols || !nav) return;
	function ir(p){
		if(p==='explorar'){
			var cat=document.querySelector('.consola-modo-btn[data-modo="catalogo"]'); if(cat) cat.click();
			var ex=document.getElementById('rau-explorar'); if(ex) ex.scrollIntoView({block:'start', behavior:'smooth'});
			return;
		}
		var cambia=cols.dataset.pestana!==p;
		cols.dataset.pestana=p;
		nav.querySelectorAll('button').forEach(function(b){ b.classList.toggle('is-active', b.dataset.ir===p); b.setAttribute('aria-pressed', b.dataset.ir===p); });
		// Solo en pantallas angostas las columnas son pestañas; en computador no hace falta moverse
		if(cambia && window.matchMedia('(max-width: 1023px)').matches) cols.scrollIntoView({block:'start', behavior:'smooth'});
	}
	window.rauIrPestana=ir;
	nav.addEventListener('click', function(e){ var b=e.target.closest('button'); if(b) ir(b.dataset.ir); });
	nav.querySelectorAll('button').forEach(function(b){ b.setAttribute('aria-pressed', b.dataset.ir===cols.dataset.pestana); });

	var chip=document.getElementById('consola-rx-chip');
	function pintar(d){
		if(!chip) return;
		if(d===undefined) return;
		chip.textContent = d===null ? '🎧 Receptor: fuera de línea' : d.libres===0 ? '🎧 Receptor: muy ocupado' : '🎧 Receptor: '+d.libres+' libres';
		chip.classList.toggle('text-emerald-400', !!(d && d.libres>0));
		chip.classList.toggle('text-amber-400', !!(d && d.libres===0));
		chip.classList.toggle('text-rose-400', d===null);
	}
	pintar(window.rauRxEstado);
	document.addEventListener('rau:sdr-estado', function(ev){ pintar(ev.detail); });
	if(chip) chip.addEventListener('click', function(){
		if(document.getElementById('rau-tools').classList.contains('rau-vista-herramienta') && typeof window.rauVolverConsola==='function') window.rauVolverConsola();
		ir('escuchar');
		var app=document.getElementById('rx-app'); if(app) setTimeout(function(){ app.scrollIntoView({block:'start', behavior:'smooth'}); }, 50);
	});
})();

/* Última línea del archivo. Si esta marca no se pone, es que la ejecución se detuvo antes: un
   error fatal, un archivo que no cargó o un include que falló. El vigilante de la página lo
   comprueba unos segundos después y lo dice, en vez de dejar los paneles diciendo "Cargando…"
   para siempre — que es lo que pasó dos veces y costó varias rondas de diagnóstico a ciegas. */
window.RAU_JS_COMPLETO = true;
