-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Gép: localhost
-- Létrehozás ideje: 2026. Feb 11. 10:13
-- Kiszolgáló verziója: 8.0.45
-- PHP verzió: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `magicfridge`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `api_recipe_translations`
--

CREATE TABLE `api_recipe_translations` (
  `id` int NOT NULL,
  `meal_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hu_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hu_instructions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `api_recipe_translations`
--

INSERT INTO `api_recipe_translations` (`id`, `meal_id`, `hu_title`, `hu_instructions`, `created_at`) VALUES
(1, '52795', 'Csirke handi', 'Vegyen egy nagy edényt vagy wokot, amely elég nagy ahhoz, hogy megfőzze az összes csirkét, és melegítse fel benne az olajat. Miután az olaj felforrósodott, adjon hozzá szeletelt hagymát, és süsse mély aranybarnára. Ezután vegye ki őket egy tányéron, és tegye félre.\r\nUgyanahhoz az edényhez adja hozzá az apróra vágott fokhagymát, és párolja egy percig. Ezután adja hozzá az apróra vágott paradicsomot, és főzze addig, amíg a paradicsom megpuhul. Ez körülbelül 5 percet vesz igénybe.\n\nEzután tegye vissza a sült hagymát a fazékba, és keverje meg. Adja hozzá a gyömbérkrémet, és alaposan pirítsa meg.\r\nMost adja hozzá a köménymagot, a koriandermag felét és az apróra vágott zöld chilipaprikát. Adj nekik egy gyors keverőt.\r\nEzután jön a fűszerek – kurkuma por és piros chili por. Pár percig jól párolja a fűszereket.\n\nAdja hozzá a csirkedarabokat a wokhoz, ízlés szerint fűszerezze sóval, és főzze a csirkét közepesen alacsony lángon, amíg a csirke majdnem átsül. Ez körülbelül 15 percet vesz igénybe. A csirke lassú párolása fokozza az ízt, ezért ne gyorsítsa fel ezt a lépést azzal, hogy magas hőfokra helyezi.\n\nAmikor az olaj elválik a fűszerektől, adja hozzá a megvert joghurtot, és tartsa a hőt a legalacsonyabban, hogy a joghurt ne hasadjon szét. Szórja meg a megmaradt koriandermagokat, és adja hozzá a szárított görögszéna levelek felét. Keverje jól össze.\r\nVégül adja hozzá a krémet, és adjon hozzá egy végső keveréket, hogy mindent jól kombináljon.\r\nSzórja meg a maradék kasuri methit és garam masalát, és tálalja a csirke handit forrón naannal vagy rotisszal. Jó étvágyat!', '2025-12-09 11:05:37'),
(2, '53358', 'Csirke mandi', '1. Tisztítsa meg és vágja fel a csirkét; rövid ideig pácolja sóval, kurkumával és egy kis olajjal.\r\n2. Öblítse le és áztassa a basmati rizst 20–30 percig.\r\n3. Egy nagy edényben melegítse fel a ghee-t/olajat. Fry apróra vágott hagymát, amíg arany. Adjon hozzá darált fokhagymát és zöld chilit, és süsse meg 1–2 percig.\r\n4. Adjon hozzá egész fűszereket (kardamom, szegfűszeg, fahéj, babérlevél) és őrölt fűszereket (koriander, kömény). Keverje addig, amíg illatos lesz.\r\n5.\n\nAdjon hozzá csirkehúsdarabokat, enyhén barnítsa meg őket, és adjon hozzá elegendő vizet/csirkehúst a Párolja addig, amíg a csirke majdnem megfő.\r\n6. Távolítsa el a csirkét; mérje meg a maradék folyadékot, és adjon hozzá áztatott riz Forralja fel, majd csökkentse a hőt, fedje le és főzze a rizst, amíg majdnem kész.\r\n7. Tegye vissza a csirkét a tetején lévő rizsfazékba, fedje le szorosan, és gőzölje alacsonyra 10–15 percig, hogy az ízek összeolvadjanak.\r\n8.\n\n(Választható) Az autentikus füstös aromához: melegítsen egy kis faszenet, amíg vörösen felforrósodik, helyezze egy kis fóliacsészére az edény közepén, adjon hozzá egy teáskanál vajat/olajat a szénhez, majd azonnal fedje le, hogy 5–10 percig elzárja a füstöt. Távolítsa el a szenet.\r\n9. Díszítse sült hagymával, apróra vágott korianderrel, és tálalja chutney-val vagy raitával.', '2025-12-09 11:06:50'),
(3, '52854', 'Palacsinták', 'Tegye a lisztet, a tojást, a tejet, 1 evőkanál olajat és egy csipet sót egy tálba vagy egy nagy kancsóba, majd habverővel egyenletes tésztára. Szánjon 30 percet pihenésre, ha van ideje, vagy azonnal kezdjen el főzni.\r\nHelyezzen egy közepes serpenyőt vagy krepp serpenyőt közepes hőre, és óvatosan törölje át olajozott konyhai papírral.\n\nAmikor forró, főzze a palacsintákat 1 percig mindkét oldalon, amíg aranyszínű nem lesz, miközben melegen tartja őket egy alacsony sütőben.\r\nCitromkarikákkal és cukorral, vagy kedvenc töltelékével tálaljuk. Miután kihűlt, rétegezheti a palacsintákat a sütőpapír között, majd ragasztófóliába csomagolhatja és akár 2 hónapig is fagyaszthatja.', '2025-12-09 11:10:51'),
(4, '53214', 'Thai zöld csirkeleves', '1. lépés\r\nMelegítse fel az olajat a legnagyobb serpenyőben, adja hozzá a hagymát, és süsse 3 percig, hogy lágyuljon. Adja hozzá a csirkét és a fokhagymát, és főzze addig, amíg a csirke színe meg nem változik.\r\n\r\n2. lépés\r\nAdja hozzá a currys pasztát, a kókusztejet, az alapanyagot, a citromlevelet és a halszószt, majd párolja 12 percig. Adja hozzá az apróra vágott hagyma tetejét, a zöldbabot és a bambuszrügyet, és főzze 4-6 percig, amíg a bab csak puha nem lesz.\n\n3. lépés\r\nKözben tegye a lime juice-t és a bazsalikomot egy keskeny kancsóba, és keverje össze egy kézi turmixgéppel, hogy sima zöld pasztát készítsen. Öntse a levesbe a szeletelt újhagymát, és melegítse át. A könnyű ebédhez vagy vacsorához, illetve előételként meszes ékekkel tálaljuk.', '2025-12-09 11:12:00'),
(5, '53359', 'Marhahús mandi', '1. Mossa meg a marhahúst, és vágja nagy darabokra. Enyhén fűszerezze sóval és kurkumával.\r\n2. Melegítse fel a ghee-t/olajat egy nagy edényben. Adjon hozzá szeletelt hagymát, és pirítsa világos aranysárga színig.\r\n3. Adjon hozzá fokhagymát, zöld chilit és paradicsomot; főzze puhára.\r\n4. Adja hozzá a mandi fűszerkeveréket: koriander, kömény, fekete bors, fahéj, kardamom, szegfűszeg és babérlevél.\r\n5.\n\nAdjon hozzá marhahúsdarabokat, és közepes lángon keverje, amíg a hús jól be nem van vonva fűszerekkel.\r\n6. Öntse vízbe vagy marhahúslevesbe. Fedje le és párolja, amíg a marhahús megpuhul (kb. 1,5-2 óra a vágástól függően).\r\n7. Óvatosan távolítsa el a marhahúst, és tegye félre Feszítse meg és mérje meg a levest.\r\n8. Adjon mosott, áztatott basmati rizst a húsleveshez (általában 1 csésze rizs = 1,5-2 csésze folyadék). Állítsa be a fűszerezést, és forralja fel.\r\n9.\n\nCsökkentse a hőt, fedje le és főzze a rizst, amíg bolyhos nem lesz\r\n10. Helyezze a marhahúsdarabokat a rizsre, és gőzölje alacsony lángon 10 percig, hogy az ízek összekeveredjenek.\r\n11. Opcionális: A füstös íz érdekében tegyen egy kis forró faszenet a fóliára az edényben, adjon hozzá 1 teáskanál vajat/olajat, és azonnal fedje le 5 percig. Tálalás előtt távolítsa el a szenet\r\n12. Bolyhosítsa fel a rizst, és tálalja a marhahúsos mandit salátával vagy chutney', '2025-12-12 07:24:03'),
(6, '53110', 'Ragacsos csirke', '1. lépés\r\nTegyen 3 vágást mindegyik dobverőre. Keverje össze a szóját, a mézet, az olajat, a paradicsompürét és a mustárt. Öntse ezt a keveréket alaposan a csirkére és a kabátra. Hagyja pácolni 30 percig szobahőmérsékleten vagy egy éjszakán át a hűtőben. Melegítse fel a sütőt 200C-ra/180C ventilátorra/gázra 6.\r\n\r\n2. lépés\r\nFordítsa a csirkét egy sekély tepsibe, és főzze 35 percig, időnként megforgatva, amíg a csirke meg nem puhul és meg nem csillog a pácban.', '2025-12-12 07:34:20'),
(7, '52777', 'Mediterrán tésztasaláta', 'Forraljon fel egy nagy serpenyőnyi sós vizet\r\nAdja hozzá a tésztát, keverje össze egyszer, és főzze kb. 10 percig, vagy a csomag utasításai szerint.\r\nKözben mossa meg a paradicsomot, és vágja negyedekre. Szeletelje fel az olajbogyót. Mossa meg a bazsalikom\r\nTedd a paradicsomot egy salátástálba, és tépd szét a bazsalikom leveleit. Adjon hozzá egy evőkanál olívaolajat, és keverje össze.\n\nAmikor a tészta elkészült, engedje le egy szűrőedénybe, és öntsön rá hideg vizet, hogy gyorsan lehűljön.\r\nDobja a tésztát a salátástálba a paradicsommal és a bazsalikommal.\r\nAdja hozzá a szeletelt olajbogyót, a lecsapolt mozzarellagolyókat és a tonhalat. Keverje jól össze, és hagyja a salátát legalább fél órán át pihenni, hogy az ízek összekeveredjenek.\n\nSzórja meg a tésztát bőségesen őrölt fekete borssal, és tálalás előtt szórja meg a maradék olívaolajjal.', '2025-12-12 07:37:00'),
(8, '53080', 'Blini palacsinta', 'Egy nagy tálban keverje össze 1/2 csésze hajdina lisztet, 2/3 csésze univerzális lisztet, 1/2 teáskanál sót és 1 teáskanál élesztőt.\r\n\r\nKészítsen egy kutat a közepén, és öntsön 1 csésze meleg tejet, addig habosítva, amíg a tészta sima nem lesz.\r\n\r\nFedje le a tálat, és hagyja, hogy a tészta felemelkedjen, amíg megduplázódik, kb. 1 óra.\r\n\r\nDúsítsa és pihentesse az akkumulátort\r\nKeverjen bele 2 evőkanál olvasztott vajat és 1 tojássárgáját a tésztába.\n\nKülön tálban habosítson fel 1 tojásfehérjét, amíg meg nem keményedik, de ne szárítsa meg.\r\n\r\nHajtsa a felvert tojásfehérjét a tésztába.\r\n\r\nFedje le a tálat, és hagyja állni a tésztát 20 percig.\r\n\r\nPan-Fry the Blini\r\nMelegítse fel a vajat egy nagy tapadásmentes serpenyőben közepes hőfokon.\r\n\r\nDobja a negyed méretű tésztát a serpenyőbe, ügyelve arra, hogy ne zsúfolja túl a serpenyőt. Főzze kb. 1 percig, vagy amíg buborékok képződnek.\n\nForduljon meg és főzze kb. 30 másodpercig.\r\n\r\nTávolítsa el a kész blinit egy tányérra, és fedje le egy tiszta konyharuhával, hogy melegen tartsa. Adjon még vajat a serpenyőbe, és ismételje meg a sütési folyamatot a maradék tésztával.', '2026-01-20 08:33:53'),
(9, '53316', 'Céklás palacsinta', '1. lépés\r\nHelyezze a céklát egy kancsóba a tejjel, és keverje össze egy botmixerrel, amíg sima nem lesz. Öntse egy tálba a palacsinta többi hozzávalójával együtt, és addig habosítsa, amíg sima és élénk lila nem lesz.\r\n\r\n2. lépés\r\nTegyen egy kis vajgombot egy nagy tapadásmentes serpenyőbe, és melegítse közepesen alacsony hőfokon, amíg megolvad és habzik. Most készítsen 3 vagy 4 palacsintát egyenként 2 evőkanálnyi tésztából.\n\nFőzze 2-3 percig, majd fordítsa meg, és főzze még egy percig, amíg át nem főzi. Ismételje meg a fennmaradó tésztával. Melegítse a sütőt a legalacsonyabb beállításig, és tartsa melegen a palacsintákat, amíg szükséges.\r\n\r\n3. lépés\r\nTálalja kedvenc palacsintaönteteivel, vagy készítsen egy egyszerű kompótot fagyasztott bogyók főzésével 1 evőkanál feketeribizli lekvárral, amíg buborékos és szirupos nem lesz (kb. 5-10 perc).\n\nEgy kis tálban keverje össze a maradék lekvárt és a joghurtot. Rakja egymásra a főtt palacsintát a joghurttal, és öntse a meleg bogyós kompótot a tetejére.', '2026-01-20 08:34:15'),
(10, '52934', 'Chicken Basquaise', 'Melegítse elő a sütőt 180°C-ra/Gázjelölés 4. Készítse elő a csirkeízületeket főzésre. Melegítse fel a vajat és 3 evőkanál olívaolajat tűzálló edényben vagy nagy serpenyőben. Pirítsa meg a csirke darabokat kötegekben mindkét oldalon, és fűszerezze meg őket sóval és borssal menet közben. Ne zsúfolja össze a serpenyőt - kis tételekben süsse meg a csirkét, és vegye ki a darabokat a konyhai papírból.\n\nAdjon hozzá egy kicsit több olívaolajat a raguhoz, és süsse a hagymát közepes lángon 10 percig, gyakran kevergetve, amíg megpuhul, de nem pirul meg. Adja hozzá a maradék olajat, majd a paprikát, és főzze további 5 percig.\r\n\r\nAdja hozzá a chorizót, a napon szárított paradicsomot és a fokhagymát, és főzze 2-3 percig. Adja hozzá a rizst, kevergetve, hogy megbizonyosodjon arról, hogy jól be van vonva az olajjal.\n\nKeverje bele a paradicsompürét, a paprikát, a babérlevelet és az apróra vágott kakukkfüvet. Öntse bele a készletet és a bort. Amikor a folyadék buborékolni kezd, tekerje lejjebb a hőt egy enyhe párolásra. Nyomja bele a rizst a folyadékba, ha még nincs alámerülve, és helyezze a csirkét a tetejére. Helyezze a citromos éket és az olajbogyót a csirke köré.\r\n\r\nFedje le és főzze a sütőben 50 percig.\n\nA rizst meg kell főzni, de még mindig van benne egy kis harapás, és a csirkének olyan gyümölcslevekkel kell rendelkeznie, amelyek a legvastagabb részbe késsel átszúrva tiszták. Ha nem, főzze további 5 percig, és ellenőrizze újra.', '2026-01-21 07:40:58'),
(11, '52831', 'Csirke karaage', 'Adja hozzá a gyömbért, a fokhagymát, a szójaszószt, a szakét és a cukrot egy tálhoz, és habverővel keverje össze. Adja hozzá a csirkét, majd keverje el egyenletesen a bevonathoz. Fedjük le és hűtsük legalább 1 órán keresztül.\r\n\r\nAdjon 1 hüvelyknyi növényi olajat egy nehéz aljú edényhez, és melegítse fel, amíg az olaj el nem éri a 360 fokot. Soroljon fel egy dróttartót 2 papírtörlővel, és vegye ki a fogóit.\n\nTegye a burgonyakeményítőt egy tálba\r\n\r\nAdjon egy marék csirkét a burgonyakeményítőhöz, és dobja el, hogy minden egyes darabot egyenletesen bevonjon.\r\n\r\nSüsse meg a karaage-t adagokban, amíg a külseje közepesen barna nem lesz, és a csirkét átsütik. Helyezze át a sült csirkét a papírtörlővel bélelt állványra.\n\nHa azt szeretné, hogy a karaage hosszabb ideig ropogós maradjon, másodszor is megsütheti a csirkét, amíg sötétebb színű nem lesz, miután egyszer lehűlt. Citromkarikákkal tálaljuk.', '2026-01-21 10:41:47'),
(12, '52956', 'Kongói csirke', '1. LÉPÉS - A CSIRKE PÁCOLÁSA\r\nEgy tálban adjon hozzá csirkét, sót, fehér borsot, gyömbérlevet, majd jól keverje össze.\r\nTegye félre a csirkét.\r\n2. LÉPÉS - ÖBLÍTSE LE A FEHÉR RIZST\r\nÖblítse ki a rizst néhányszor egy fémedényben vagy fazékban, majd engedje le a vizet.\r\n2. LÉPÉS - A FEHÉR RIZS FORRALÁSA\r\nEzután adjon hozzá 8 csésze vizet, majd állítsa a tűzhelyet magas hőfokra, amíg fel nem forr.\n\nMiután a rizskása forrni kezd, állítsa a tűzhelyet alacsony hőfokra, majd 8-10 percenként egyszer keverje kb. 20-25 percig.\r\n25 perc elteltével ez opcionális, de hozzáadhat egy kicsit több vizet a rizskása elkészítéséhez, hogy kevésbé sűrű legyen, vagy tetszése szerint.\r\nEzután adja hozzá a pácolt csirkét a rizskásához, és hagyja a tűzhelyet még 10 percig alacsony hőmérsékleten.\n\nTovábbi 10 perc elteltével adja hozzá a zöldhagymát, a szeletelt gyömbért, 1 csipet sót, 1 csipet fehér borsot, és keverje 10 másodpercig.\r\nA rizskása tálban tálalva\r\nOpcionális: adjon koriandert a rizskása tetejére.', '2026-01-21 10:45:15'),
(13, '52920', 'Csirke Marengo', 'Melegítse fel az olajat egy nagy lángbiztos edényben, és addig süsse a gombákat, amíg meg nem lágyulnak. Adja hozzá a csirkelábakat, és főzze rövid ideig mindkét oldalon, hogy egy kicsit színezze őket.\r\nÖntsük bele a passatát, morzsoljuk bele az alapkockába, és keverjük bele az olajbogyót. Fűszerezze fekete borssal – nincs szüksége sóra. Fedje le és főzze 40 percig, amíg a csirke megpuhul.\n\nSzórja meg petrezselyemmel, és tálalja tésztával és salátával, vagy pürével és zöld zöld zöldséggel, ha úgy tetszik.', '2026-01-21 10:45:38');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `households`
--

CREATE TABLE `households` (
  `id` int NOT NULL,
  `owner_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `households`
--

INSERT INTO `households` (`id`, `owner_id`, `name`, `created_at`) VALUES
(1, 1, 'Udvari Dominik háztartása', '2025-11-28 11:15:01'),
(2, 2, 'Zsigó Róbert háztartása', '2025-11-28 11:16:08'),
(3, 3, 'Sutús Róbert háztartása', '2025-12-09 10:02:51'),
(4, 4, 'Kiss Gábor háztartása', '2025-12-09 11:12:59'),
(5, 5, 'Nagy Tamás háztartása', '2025-12-12 07:25:36'),
(6, 6, 'Kis Márk háztartása', '2025-12-12 07:41:08'),
(7, 7, 'Anyám háztartása', '2026-01-06 07:26:42'),
(8, 8, 'asd123 háztartása', '2026-01-20 08:09:18'),
(9, 10, 'asd1234 háztartása', '2026-01-20 11:01:03'),
(10, 12, 'alma háztartása', '2026-01-21 08:32:35'),
(11, 14, 'aaa2 háztartása', '2026-02-06 10:49:35'),
(12, 13, 'aaa1 háztartása', '2026-02-06 10:52:32'),
(13, 15, 'asd22 háztartása', '2026-02-06 13:03:05'),
(14, 16, 'qwe1 háztartása', '2026-02-10 10:47:08'),
(15, 14, 'aaa2 háztartása', '2026-02-10 12:11:05');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `household_invites`
--

CREATE TABLE `household_invites` (
  `id` int NOT NULL,
  `household_id` int NOT NULL,
  `invited_user_id` int NOT NULL,
  `invited_by_user_id` int NOT NULL,
  `status` enum('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `household_invites`
--

INSERT INTO `household_invites` (`id`, `household_id`, `invited_user_id`, `invited_by_user_id`, `status`, `created_at`, `responded_at`) VALUES
(1, 9, 8, 10, 'accepted', '2026-01-20 11:01:14', '2026-01-20 11:01:27'),
(2, 8, 10, 8, 'accepted', '2026-01-20 11:06:31', '2026-01-20 11:06:42'),
(3, 8, 1, 8, 'pending', '2026-01-21 07:42:01', NULL),
(4, 11, 13, 14, 'accepted', '2026-02-06 10:52:21', NULL),
(5, 14, 14, 16, 'accepted', '2026-02-10 11:16:30', NULL);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `household_members`
--

CREATE TABLE `household_members` (
  `id` int NOT NULL,
  `household_id` int NOT NULL,
  `member_id` int NOT NULL,
  `role` enum('tag','alap felhasználó') DEFAULT 'tag',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `household_members`
--

INSERT INTO `household_members` (`id`, `household_id`, `member_id`, `role`, `created_at`) VALUES
(1, 2, 1, 'tag', '2025-11-28 11:16:15'),
(2, 1, 2, 'alap felhasználó', '2025-11-28 11:16:58'),
(3, 3, 1, 'alap felhasználó', '2025-12-09 10:02:58'),
(4, 3, 2, 'alap felhasználó', '2025-12-09 10:19:02'),
(5, 4, 1, 'tag', '2025-12-09 11:13:15'),
(6, 5, 3, 'tag', '2025-12-12 07:25:52'),
(7, 6, 3, 'alap felhasználó', '2025-12-12 07:41:57'),
(8, 8, 3, 'alap felhasználó', '2026-01-20 08:19:39'),
(9, 9, 8, 'alap felhasználó', '2026-01-20 11:01:27'),
(10, 8, 10, 'alap felhasználó', '2026-01-20 11:06:42'),
(11, 12, 13, 'tag', '2026-02-06 10:52:32'),
(12, 11, 13, 'tag', '2026-02-06 12:02:10'),
(13, 13, 15, 'tag', '2026-02-06 13:03:05'),
(14, 14, 16, 'tag', '2026-02-10 10:47:08'),
(15, 15, 14, 'tag', '2026-02-10 12:11:05'),
(16, 14, 14, 'tag', '2026-02-11 07:24:11');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int NOT NULL,
  `household_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` enum('fridge','pantry','freezer') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pantry',
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expired_notified` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `household_id`, `name`, `category`, `location`, `quantity`, `unit`, `expires_at`, `note`, `created_at`, `updated_at`, `expired_notified`) VALUES
(50, 8, 'Garam masala', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Csirke handi', '2026-01-21 10:44:09', '2026-01-21 10:44:09', 0),
(51, 8, 'Csirkecomb', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Ragacsos csirke', '2026-01-21 10:44:09', '2026-01-21 10:44:09', 0),
(52, 8, 'szójaszósz', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Ragacsos csirke', '2026-01-21 10:44:09', '2026-01-21 10:44:09', 0),
(53, 8, 'Méz', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Ragacsos csirke', '2026-01-21 10:44:09', '2026-01-21 10:44:09', 0),
(54, 8, 'paradicsompüré', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Ragacsos csirke', '2026-01-21 10:44:09', '2026-01-21 10:44:09', 0),
(56, 8, 'Mustármag', NULL, 'pantry', 6.00, NULL, NULL, 'Recept: Ragacsos csirke', '2026-01-21 11:08:59', '2026-01-21 11:09:06', 0),
(57, 8, 'Petrezselyem', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Csirke Marengo', '2026-01-21 11:09:00', '2026-01-21 11:09:00', 0),
(58, 8, 'Csirkeállomány kocka', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Csirke Marengo', '2026-01-21 11:09:01', '2026-01-21 11:09:01', 0),
(59, 8, 'Passata', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Csirke Marengo', '2026-01-21 11:09:02', '2026-01-21 11:09:02', 0),
(60, 8, 'Ehető gomba', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Csirke Marengo', '2026-01-21 11:09:02', '2026-01-21 11:09:02', 0),
(61, 8, 'Újhagyma', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Kongói csirke', '2026-01-21 11:09:02', '2026-01-21 11:09:02', 0),
(62, 8, 'Gyömbéres szíverősítő', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Kongói csirke', '2026-01-21 11:09:02', '2026-01-21 11:09:02', 0),
(63, 8, 'Citrom', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:03', '2026-01-21 11:09:03', 0),
(64, 8, 'Fehérbor', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:03', '2026-01-21 11:09:03', 0),
(65, 8, 'Csirkeállomány', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:03', '2026-01-21 11:09:03', 0),
(66, 8, 'Kakukkfű', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:03', '2026-01-21 11:09:03', 0),
(67, 8, 'Babérlevél', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:03', '2026-01-21 11:09:03', 0),
(68, 8, 'Paprika', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:03', '2026-01-21 11:09:03', 0),
(69, 8, 'Chorizo ///Mi lesz a következő? Abált szalonna? Rántott velő?///', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:04', '2026-01-21 11:09:04', 0),
(70, 8, 'Pirospaprika', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:04', '2026-01-21 11:09:04', 0),
(71, 8, 'lila hagyma', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:04', '2026-01-21 11:09:04', 0),
(72, 8, 'Vaj', NULL, 'pantry', 1.00, NULL, NULL, 'Recept: Chicken Basquaise', '2026-01-21 11:09:04', '2026-01-21 11:09:04', 0),
(78, 1, 'kenyér', '1231', 'pantry', 1.00, NULL, '2026-02-05', NULL, '2026-02-06 12:24:28', '2026-02-06 12:24:28', 0),
(79, 1, 'asdasdadaad', NULL, 'pantry', 1.00, NULL, '2026-02-05', NULL, '2026-02-06 12:33:19', '2026-02-06 12:33:19', 0),
(80, 1, 'asd123oöipk', NULL, 'pantry', 1.00, NULL, '2026-02-06', NULL, '2026-02-06 12:41:21', '2026-02-06 12:45:17', 0),
(81, 14, 'Kenyér', 'pékárú', 'pantry', 2.00, 'kg', '2026-02-09', NULL, '2026-02-10 09:48:08', '2026-02-11 07:24:12', 1),
(82, 15, 'Kenyér', 'Pékárú', 'pantry', 1.00, '2', '2026-02-09', NULL, '2026-02-10 11:23:12', '2026-02-11 06:26:21', 1),
(83, 15, 'Kenyér', 'Pékárú', 'pantry', 1.00, '2', '2026-02-09', NULL, '2026-02-10 11:25:51', '2026-02-11 06:26:21', 1),
(84, 15, 'Kenyér', 'Pékárú', 'pantry', 1.00, '2', '2026-02-09', NULL, '2026-02-10 11:26:17', '2026-02-11 06:26:21', 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `household_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `type` enum('info','warning','ok','danger') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `link_url` varchar(512) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `messages`
--

INSERT INTO `messages` (`id`, `household_id`, `user_id`, `type`, `title`, `body`, `link_url`, `is_read`, `created_at`) VALUES
(1, 1, NULL, 'warning', 'Lejárat közeleg', 'A tej 2 napon belül lejár.', NULL, 0, '2026-01-20 10:28:29'),
(2, 1, NULL, 'info', 'Rendszer üzenet', 'Új funkciók érkeznek hamarosan.', NULL, 0, '2026-01-20 10:28:29'),
(3, NULL, 1, 'ok', 'Sikeres művelet', 'A készlet frissítése megtörtént.', NULL, 1, '2026-01-20 10:28:29'),
(4, 1, NULL, 'warning', 'TESZT – lejárat', 'Ez egy teszt üzenet a háztartásnak.', NULL, 0, '2026-01-20 10:31:43'),
(5, NULL, 1, 'info', 'TESZT – felhasználó', 'Ez egy teszt üzenet csak neked.', NULL, 1, '2026-01-20 10:31:43'),
(6, NULL, 3, 'info', 'TESZT – user', 'Ha ezt látod, a user üzenetek mennek.', NULL, 0, '2026-01-20 10:39:15'),
(7, 2, NULL, 'warning', 'TESZT – household', 'Ha ezt látod, a háztartás üzenetek mennek.', NULL, 1, '2026-01-20 10:39:15'),
(8, NULL, 9, 'info', 'TESZT – user 9', 'Ha ezt látod, a user_id=9 üzenetei működnek.', NULL, 0, '2026-01-20 10:46:23'),
(9, NULL, 8, 'info', 'TESZT – user 9', 'Ha ezt látod, a user_id=9 üzenetei működnek.', NULL, 1, '2026-01-20 10:46:51'),
(10, NULL, 8, 'info', 'Háztartás meghívó', 'asd1234 meghívott a(z) \"asd1234 háztartása\" háztartásba.', 'invite:1', 1, '2026-01-20 11:01:14'),
(11, NULL, 10, 'info', 'Háztartás meghívó', 'asd123 meghívott a(z) \"asd123 háztartása\" háztartásba.', 'invite:2', 1, '2026-01-20 11:06:31'),
(12, 8, 8, 'danger', 'Lejárt termék a raktárban', 'Lejárt: Tej (lejárat: 2026-01-20). Nézd meg a raktárban.', 'inventory.php', 1, '2026-01-21 07:38:49'),
(13, NULL, 1, 'info', 'Háztartás meghívó', 'asd123 meghívott a(z) \"asd123 háztartása\" háztartásba.', 'invite:3', 0, '2026-01-21 07:42:01'),
(14, 8, 8, 'danger', 'Lejárt termék a raktárban', 'Lejárt: zsír (lejárat: 2026-01-20). Nézd meg a raktárban.', 'inventory.php', 1, '2026-01-21 08:31:09'),
(15, 8, 8, 'danger', 'Lejárt termék a raktárban', 'Lejárt: kifli (lejárat: 2026-01-20). Nézd meg a raktárban.', 'inventory.php', 1, '2026-01-21 08:31:09'),
(16, 8, 8, 'danger', 'Lejárt termék a raktárban', 'Lejárt: kenyér (lejárat: 2026-01-20). Nézd meg a raktárban.', 'inventory.php', 1, '2026-01-21 08:31:09'),
(17, NULL, 13, 'info', 'Háztartás meghívó', 'aaa2 meghívott a(z) \"aaa2 háztartása\" háztartásba.', 'invite:4', 1, '2026-02-06 10:52:21'),
(18, NULL, 14, 'info', 'Háztartás meghívó', 'qwe1 meghívott a(z) \"qwe1 háztartása\" háztartásba.', 'invite:5', 1, '2026-02-10 11:16:30'),
(19, NULL, 14, 'info', 'Lejárat', 'Lejár/lejárt: Kenyér (dátum: 2026-02-09).', 'inventory:15', 0, '2026-02-11 05:26:21'),
(20, NULL, 14, 'info', 'Lejárat', 'Lejár/lejárt: Kenyér (dátum: 2026-02-09).', 'inventory:15', 1, '2026-02-11 05:26:21'),
(21, NULL, 14, 'info', 'Lejárat', 'Lejár/lejárt: Kenyér (dátum: 2026-02-09).', 'inventory:15', 1, '2026-02-11 05:26:21'),
(22, NULL, 14, 'info', 'Lejárat', 'Lejár/lejárt: Kenyér (dátum: 2026-02-09).', 'inventory:14', 1, '2026-02-11 06:24:12');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `recipes`
--

CREATE TABLE `recipes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `title`, `created_at`) VALUES
(2, 1, 'csirkemájas', '2025-11-28 12:42:45'),
(7, 5, 'tejbegríz', '2025-12-12 07:25:08'),
(8, 6, 'Tejbegríz', '2025-12-12 07:38:56'),
(9, 7, 'faszpaprikás', '2026-01-06 07:26:06'),
(10, 8, 'akármi', '2026-01-20 08:29:28');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `recipe_ingredients`
--

CREATE TABLE `recipe_ingredients` (
  `id` int NOT NULL,
  `recipe_id` int NOT NULL,
  `ingredient` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`id`, `recipe_id`, `ingredient`) VALUES
(3, 2, 'csirke'),
(21, 7, 'tej'),
(22, 7, 'rízs'),
(23, 8, 'tej'),
(24, 8, 'búzadara'),
(25, 8, 'cukor'),
(26, 9, 'fasz'),
(27, 9, 'paprika'),
(28, 10, 'krumpli'),
(29, 10, 'viz'),
(30, 10, 'so'),
(31, 10, 'paprika'),
(32, 10, 'hagyma'),
(33, 10, 'kolbász');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `shopping_list_items`
--

CREATE TABLE `shopping_list_items` (
  `id` int NOT NULL,
  `household_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_bought` tinyint(1) NOT NULL DEFAULT '0',
  `bought_at` datetime DEFAULT NULL,
  `bought_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `location` enum('fridge','freezer','pantry') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pantry'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `shopping_list_items`
--

INSERT INTO `shopping_list_items` (`id`, `household_id`, `name`, `quantity`, `unit`, `note`, `is_bought`, `bought_at`, `bought_by`, `created_by`, `created_at`, `updated_at`, `location`) VALUES
(58, 8, 'Vaj', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:04', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:04', 'pantry'),
(59, 8, 'lila hagyma', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:04', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:04', 'pantry'),
(60, 8, 'Pirospaprika', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:04', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:04', 'pantry'),
(61, 8, 'Chorizo ///Mi lesz a következő? Abált szalonna? Rántott velő?///', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:04', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:04', 'pantry'),
(62, 8, 'Paprika', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:03', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:03', 'pantry'),
(63, 8, 'Babérlevél', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:03', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:03', 'pantry'),
(64, 8, 'Kakukkfű', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:03', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:03', 'pantry'),
(65, 8, 'Csirkeállomány', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:03', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:03', 'pantry'),
(66, 8, 'Fehérbor', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:03', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:03', 'pantry'),
(67, 8, 'Citrom', 1.00, NULL, 'Recept: Chicken Basquaise', 1, '2026-01-21 12:09:03', 8, 8, '2026-01-21 10:45:05', '2026-01-21 11:09:03', 'pantry'),
(68, 8, 'Gyömbéres szíverősítő', 1.00, NULL, 'Recept: Kongói csirke', 1, '2026-01-21 12:09:02', 8, 8, '2026-01-21 10:45:21', '2026-01-21 11:09:02', 'pantry'),
(69, 8, 'Újhagyma', 1.00, NULL, 'Recept: Kongói csirke', 1, '2026-01-21 12:09:02', 8, 8, '2026-01-21 10:45:21', '2026-01-21 11:09:02', 'pantry'),
(70, 8, 'Ehető gomba', 1.00, NULL, 'Recept: Csirke Marengo', 1, '2026-01-21 12:09:02', 8, 8, '2026-01-21 10:45:42', '2026-01-21 11:09:02', 'pantry'),
(71, 8, 'Passata', 1.00, NULL, 'Recept: Csirke Marengo', 1, '2026-01-21 12:09:02', 8, 8, '2026-01-21 10:45:42', '2026-01-21 11:09:02', 'pantry'),
(72, 8, 'Csirkeállomány kocka', 1.00, NULL, 'Recept: Csirke Marengo', 1, '2026-01-21 12:09:01', 8, 8, '2026-01-21 10:45:42', '2026-01-21 11:09:01', 'pantry'),
(73, 8, 'Petrezselyem', 1.00, NULL, 'Recept: Csirke Marengo', 1, '2026-01-21 12:09:00', 8, 8, '2026-01-21 10:45:42', '2026-01-21 11:09:00', 'pantry'),
(75, 9, 'Hagymás', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(76, 9, 'Paradicsom', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(77, 9, 'Fokhagyma', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(78, 9, 'Gyömbérpaszta', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(79, 9, 'Növényi olaj', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(80, 9, 'Köménymag', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(81, 9, 'Koriandermag', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(82, 9, 'Kurkuma por', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(83, 9, 'Csilipor', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(84, 9, 'Zöld chili', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(85, 9, 'Joghurt', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'fridge'),
(86, 9, 'Krémszínű', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(87, 9, 'görögszéna', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(88, 9, 'Garam masala', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(89, 9, 'Só', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 8, '2026-01-21 11:13:38', '2026-01-21 11:13:38', 'pantry'),
(90, 15, 'Házityúk', 1.20, 'kg', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(91, 15, 'Hagymás', 5.00, 'thinly', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(92, 15, 'Paradicsom', 2.00, 'finely', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(93, 15, 'Fokhagyma', 8.00, 'cloves', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(94, 15, 'Gyömbérpaszta', 1.00, 'tbsp', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(95, 15, 'Növényi olaj', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(96, 15, 'Köménymag', 2.00, 'tsp', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(97, 15, 'Koriandermag', 3.00, 'tsp', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(98, 15, 'Kurkuma por', 1.00, 'tsp', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(99, 15, 'Csilipor', 1.00, 'tsp', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(100, 15, 'Zöld chili', 2.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(101, 15, 'Joghurt', 1.00, 'cup', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(102, 15, 'Krémszínű', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(103, 15, 'görögszéna', 3.00, 'tsp', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(104, 15, 'Garam masala', 1.00, 'tsp', 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry'),
(105, 15, 'Só', 1.00, NULL, 'Recept: Csirke handi', 0, NULL, NULL, 14, '2026-02-11 08:26:21', '2026-02-11 08:26:21', 'pantry');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `translations_cache`
--

CREATE TABLE `translations_cache` (
  `id` int NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `target` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `translations_cache`
--

INSERT INTO `translations_cache` (`id`, `source`, `target`, `created_at`) VALUES
(1, 'Fish pie', 'Halas pite', '2026-02-11 08:28:22'),
(2, 'Fish fofos', 'Fish fofos', '2026-02-11 08:28:23'),
(3, 'Three Fish Pie', 'Három halas pite', '2026-02-11 08:28:23'),
(4, 'Escovitch Fish', 'Escovitch Fish', '2026-02-11 08:28:24'),
(5, 'Fish Soup (Ukha)', 'Halleves (Ukha)', '2026-02-11 08:28:24'),
(6, 'Saltfish and Ackee', 'Sóhal és ackee', '2026-02-11 08:28:25'),
(7, 'Recheado Masala Fish', 'Recheado Masala Fish', '2026-02-11 08:28:25'),
(8, 'Callaloo and SaltFish', 'Callaloo és SaltFish', '2026-02-11 08:28:25'),
(9, 'Fish Stew with Rouille', 'Rouille-i halpörkölt', '2026-02-11 08:28:26'),
(10, 'Cajun spiced fish tacos', 'Cajun fűszeres halas taco', '2026-02-11 08:28:26'),
(11, 'Thai-style steamed fish', 'Thai stílusú párolt hal', '2026-02-11 08:28:26'),
(12, 'Thai-style fish broth with greens', 'Thai stílusú halleves zöldségekkel', '2026-02-11 08:28:27'),
(13, 'Fiskesuppe (Creamy Norwegian Fish Soup)', 'Fiskesuppe (krémes norvég halleves)', '2026-02-11 08:28:27'),
(14, 'Portuguese fish stew (Caldeirada de peixe)', 'Portugál halpörkölt (Caldeirada de peixe)', '2026-02-11 08:28:27'),
(15, 'Chicken Handi', 'Csirke handi', '2026-02-11 08:28:45'),
(16, 'Chicken Mandi', 'Csirke mandi', '2026-02-11 08:28:46'),
(17, 'Sticky Chicken', 'Ragacsos csirke', '2026-02-11 08:28:47'),
(18, 'Chicken Congee', 'Kongói csirke', '2026-02-11 08:28:48'),
(19, 'Chicken Karaage', 'Csirke karaage', '2026-02-11 08:28:49'),
(20, 'Chicken Marengo', 'Csirke Marengo', '2026-02-11 08:28:49'),
(21, 'Spanish Chicken', 'Spanyol csirke', '2026-02-11 08:28:50'),
(22, 'Tandoori chicken', 'Tandoori csirke', '2026-02-11 08:28:51'),
(23, 'Chicken Couscous', 'Csirke kuszkusz', '2026-02-11 08:28:52'),
(24, 'Kung Pao Chicken', 'Kung Pao csirke', '2026-02-11 08:28:52'),
(25, 'Chicken Basquaise', 'Chicken Basquaise', '2026-02-11 08:28:53'),
(26, 'Chicken Fried Rice', 'Csirke sült rizs', '2026-02-11 08:28:53'),
(27, 'Chicken Parmentier', 'Chicken Parmentier', '2026-02-11 08:28:54'),
(28, 'Brown Stew Chicken', 'Barna pörkölt csirke', '2026-02-11 08:28:54'),
(29, 'Spanish chicken pie', 'Spanyol csirkés pite', '2026-02-11 08:28:55'),
(30, 'Katsu Chicken curry', 'Katsu csirke curry', '2026-02-11 08:28:55'),
(31, 'Nutty Chicken Curry', 'Currys csirkemell', '2026-02-11 08:28:55'),
(32, 'Easy Spanish chicken', 'Könnyű spanyol csirke', '2026-02-11 08:28:55'),
(33, 'General Tsos Chicken', 'General Tsos csirke', '2026-02-11 08:28:56'),
(34, 'Smoky chicken skewers', 'Füstös csirke nyársak', '2026-02-11 08:28:56'),
(35, 'Sweet and Sour Chicken', 'Édes-savanyú csirke', '2026-02-11 08:28:56'),
(36, 'Kentucky Fried Chicken', 'sült csirke', '2026-02-11 08:28:57'),
(37, 'Chinese Orange Chicken', 'Kínai narancsos csirke', '2026-02-11 08:28:57'),
(38, 'Thai green chicken soup', 'Thai zöld csirkeleves', '2026-02-11 08:28:57'),
(39, 'Red curry chicken kebabs', 'Vörös curry csirke kebab', '2026-02-11 08:28:58'),
(40, 'Beef pho', 'Marhahús pho', '2026-02-11 08:28:59'),
(41, 'Beef Mandi', 'Marhahús mandi', '2026-02-11 08:29:00'),
(42, 'Beef Asado', 'Marhahúsos aszado', '2026-02-11 08:29:01'),
(43, 'Beef Lo Mein', 'Marhahús Lo Mein', '2026-02-11 08:29:01'),
(44, 'Beef Rendang', 'Marhahús rendang', '2026-02-11 08:29:02'),
(45, 'Beef Mechado', 'Marhahúsos Mechado', '2026-02-11 08:29:03'),
(46, 'Szechuan Beef', 'Szecsuáni marhahús', '2026-02-11 08:29:03'),
(47, 'Beef Caldereta', 'Caldereta marhahús', '2026-02-11 08:29:04'),
(48, 'Beef Empanadas', 'Marhahúsos empanada', '2026-02-11 08:29:05'),
(49, 'Beef Wellington', 'Wellington marhahús', '2026-02-11 08:29:05'),
(50, 'Beef stroganoff', 'Marhahús stroganoff', '2026-02-11 08:29:06'),
(51, 'Minced Beef Pie', 'Darált marhahús', '2026-02-11 08:29:06'),
(52, 'Beef Bourguignon', 'Marhahús Bourguignon', '2026-02-11 08:29:07'),
(53, 'Corned Beef Hash', 'besózott marhahús', '2026-02-11 08:29:07'),
(54, 'Beef Sunday Roast', 'Marhahús vasárnapi sült', '2026-02-11 08:29:07'),
(55, 'Kenyan Beef Curry', 'Kenyai marhahúsos curry', '2026-02-11 08:29:07'),
(56, 'Beef Dumpling Stew', 'Marhagombóc pörkölt', '2026-02-11 08:29:08'),
(57, 'Thai beef stir-fry', 'Thai marhahús sült', '2026-02-11 08:29:08'),
(58, 'Braised Beef Chilli', 'Párolt marhahúsos chili', '2026-02-11 08:29:08'),
(59, 'Massaman Beef curry', 'Massaman marhahús curry', '2026-02-11 08:29:09'),
(60, 'Beef and Oyster pie', 'Marhahúsos osztrigás pite', '2026-02-11 08:29:09'),
(61, 'Beef and Mustard Pie', 'Marhahúsos-mustáros pite', '2026-02-11 08:29:09'),
(62, 'Jamaican Beef Patties', 'Jamaikai marhahúsos pogácsák', '2026-02-11 08:29:10'),
(63, 'Mini chilli beef pies', 'Mini chili marhahúsos pite', '2026-02-11 08:29:10'),
(64, 'Beef Brisket Pot Roast', 'Marhahúsos szegyfazék Sült', '2026-02-11 08:29:10'),
(65, 'Mediterranean Pasta Salad', 'Mediterrán tésztasaláta', '2026-02-11 08:29:38'),
(66, 'Noodle bowl salad', 'Tészta tál saláta', '2026-02-11 08:29:42'),
(67, 'Pomegranate salad', 'Gránátalma saláta', '2026-02-11 08:29:43'),
(68, 'Sweet potato salad', 'Édesburgonya saláta', '2026-02-11 08:29:44'),
(69, 'Salmon Avocado Salad', 'Lazac avokádó saláta', '2026-02-11 08:29:44'),
(70, 'Vietnamese pork salad', 'Vietnami sertéssaláta', '2026-02-11 08:29:45'),
(71, 'Bang bang prawn salad', 'Bang bang garnélasaláta', '2026-02-11 08:29:45'),
(72, 'Sesame Cucumber Salad', 'Szezámos uborkasaláta', '2026-02-11 08:29:45'),
(73, 'Chorizo & tomato salad', 'Chorizo és paradicsom saláta', '2026-02-11 08:29:45'),
(74, 'Thai rice noodle salad', 'Thai rizstészta saláta', '2026-02-11 08:29:46'),
(75, 'Cucumber & fennel salad', 'Uborka és édeskömény saláta', '2026-02-11 08:29:46'),
(76, 'Vietnamese chicken salad', 'Vietnami csirkesaláta', '2026-02-11 08:29:46'),
(77, 'Aubergine couscous salad', 'Padlizsánkuszkusz saláta', '2026-02-11 08:29:47'),
(78, 'Chicken Quinoa Greek Salad', 'Csirke quinoa görög saláta', '2026-02-11 08:29:47'),
(79, 'Warm roast asparagus salad', 'Meleg sült spárga saláta', '2026-02-11 08:29:47'),
(80, 'Potato Salad (Olivier Salad)', 'Burgonyasaláta (Olivier saláta)', '2026-02-11 08:29:48'),
(81, 'Squid, chickpea & chorizo salad', 'Tintahal, csicseriborsó és chorizo saláta', '2026-02-11 08:29:48'),
(82, 'Chorizo & soft-boiled egg salad', 'Chorizo és lágy tojás saláta', '2026-02-11 08:29:48'),
(83, 'Steak & Vietnamese noodle salad', 'Steak és vietnami tésztasaláta', '2026-02-11 08:29:48'),
(84, 'Spicy North African Potato Salad', 'Fűszeres észak-afrikai burgonyasaláta', '2026-02-11 08:29:49'),
(85, 'Tangy carrot, cabbage & onion salad', 'Sárgarépa, káposzta és hagyma saláta', '2026-02-11 08:29:49'),
(86, 'Algerian Flafla (Bell Pepper Salad)', 'Algériai flafla (kaliforniai paprika saláta)', '2026-02-11 08:29:49'),
(87, 'Prawn & noodle salad with crispy shallots', 'Garnéla és tésztasaláta ropogós mogyoróhagymával', '2026-02-11 08:29:50'),
(88, 'Chicken', 'Házityúk', '2026-02-11 09:26:14'),
(89, 'Onion', 'Hagymás', '2026-02-11 09:26:15'),
(90, 'Tomatoes', 'Paradicsom', '2026-02-11 09:26:15'),
(91, 'Garlic', 'Fokhagyma', '2026-02-11 09:26:15'),
(92, 'Ginger paste', 'Gyömbérpaszta', '2026-02-11 09:26:16'),
(93, 'Vegetable oil', 'Növényi olaj', '2026-02-11 09:26:17'),
(94, 'Cumin seeds', 'Köménymag', '2026-02-11 09:26:17'),
(95, 'Coriander seeds', 'Koriandermag', '2026-02-11 09:26:18'),
(96, 'Turmeric powder', 'Kurkuma por', '2026-02-11 09:26:18'),
(97, 'Chilli powder', 'Csilipor', '2026-02-11 09:26:19'),
(98, 'Green chilli', 'Zöld chili', '2026-02-11 09:26:19'),
(99, 'Yogurt', 'Joghurt', '2026-02-11 09:26:20'),
(100, 'Cream', 'Krémszínű', '2026-02-11 09:26:20'),
(101, 'fenugreek', 'görögszéna', '2026-02-11 09:26:20'),
(102, 'Garam masala', 'Garam masala', '2026-02-11 09:26:20'),
(103, 'Salt', 'Só', '2026-02-11 09:26:21');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `created_at`) VALUES
(1, 'Udvari Dominik', 'udvaridominik77@gmail.com', '$2y$10$I58NBJaKTf1.HwkbPjyAtea3AiG1p0kgn5DnfXZNQhJ7xoHcS0wkG', '2025-11-28 11:14:35'),
(2, 'Zsigó Róbert', 'zsigivagyok@gmail.com', '$2y$10$HUT6uf7yDCywraoj56R5f.IzTwbTRgX6pxu2mDyBWgfunggYKCQCK', '2025-11-28 11:15:53'),
(3, 'Sutús Róbert', 'sutivagyok@gmail.com', '$2y$10$3Wx0FYZmBKADPpT7XBxyVegshLz0Hh0xIx.u0V/ydi8/zKERBAOka', '2025-12-09 10:01:36'),
(4, 'Kiss Gábor', 'gaborvagyok@gmail.com', '$2y$10$dA7Yh0CAJp3bYeDXQiwrM.C5VldZUuPsN1Q/kNmcA8ddQVX1MVRR.', '2025-12-09 11:09:48'),
(5, 'Nagy Tamás', 'nagytamas123@gmail.com', '$2y$10$LI/GBNdi3PA4PIM2lN7sm.D/AfBYQ5Zqc/uQxk2ZZFWB557B9c86.', '2025-12-12 07:23:20'),
(6, 'Kis Márk', 'kismark44@gmail.com', '$2y$10$WGvPWSrfTLTeOp6o19mO6e21Ew6fvS27iiM49BfFgGnWk6MIMMTNK', '2025-12-12 07:33:05'),
(7, 'Anyám', 'anyam@gmail.com', '$2y$10$rWA7fqZE3WCDZo5CqoXtvu2EUMLE2sxroFC/3iNRKCuPMyu6535ie', '2026-01-06 07:25:23'),
(8, 'asd123', 'asd123@gmail.com', '$2y$10$Xdimi90z5CIFKn7RcqFRfuJsV1NoXiOmMnCrxW/zON8kHaCgmRGKS', '2026-01-20 07:42:58'),
(9, 'Sutus Robert', 'sutusrobert@gmail.com', '$2y$10$UgG1iPbSXRKv8Orbq5lJPusSydppiEjvNCYC.yp6KUOECWliNr4Za', '2026-01-20 08:27:03'),
(10, 'asd1234', 'asd1234@gmail.com', '$2y$10$jb4cAq5P7xbHGrFFo0yGMeM3uC2nIvMzc1ffTIFlg.QVfVSxZAtXu', '2026-01-20 11:00:54'),
(11, 'asd123123', 'asd123asd123@gmail.com', '$2y$10$vljzsYmY/Ea8jSoBUPwblu1IAxFlOTj8wsrU5lneL0uzur63Gwqny', '2026-01-21 06:30:40'),
(12, 'alma', 'alma@gmail.com', '$2y$10$T9aMUf50EBNBiM0rsr8XsOq59vWiPTRJw02GWL/vp/ljZ2wYHje7m', '2026-01-21 08:32:23'),
(13, 'aaa1', 'aaa1@gmail.com', '$2y$12$LK8QXzVTkTxvCPFzMNWXTePZmTltCUL6JstShJTN9tmrjxoYhzk1m', '2026-02-06 09:30:06'),
(14, 'aaa2', 'aaa2@gmail.com', '$2y$12$osgMRrjwfb1KvRbFgWvezeCQrFmaZdCbsQaZ3aChwEDHN8Op5S6vi', '2026-02-06 10:13:44'),
(15, 'asd22', 'asd22@gmail.com', '$2y$12$NdYf/Wo28ijGlT7238yJyesAQLDVxCM9HQ0TiBTs0qFmAEa7diOGy', '2026-02-06 12:45:58'),
(16, 'qwe1', 'qwe1@gmail.com', '$2y$12$jiMR1hlbXywR9Gru8/8nS.9TghWPgKhKsj3TzjbSwuICwQPHMtm8a', '2026-02-10 10:02:46');

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `api_recipe_translations`
--
ALTER TABLE `api_recipe_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_meal` (`meal_id`);

--
-- A tábla indexei `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- A tábla indexei `household_invites`
--
ALTER TABLE `household_invites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_household_invited` (`household_id`,`invited_user_id`),
  ADD KEY `idx_invited_user` (`invited_user_id`),
  ADD KEY `fk_inv_inviter` (`invited_by_user_id`);

--
-- A tábla indexei `household_members`
--
ALTER TABLE `household_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`),
  ADD KEY `member_id` (`member_id`);

--
-- A tábla indexei `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`),
  ADD KEY `name` (`name`),
  ADD KEY `expires_at` (`expires_at`);

--
-- A tábla indexei `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_household` (`household_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- A tábla indexei `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- A tábla indexei `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- A tábla indexei `shopping_list_items`
--
ALTER TABLE `shopping_list_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`),
  ADD KEY `is_bought` (`is_bought`),
  ADD KEY `name` (`name`);

--
-- A tábla indexei `translations_cache`
--
ALTER TABLE `translations_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_source` (`source`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `api_recipe_translations`
--
ALTER TABLE `api_recipe_translations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT a táblához `households`
--
ALTER TABLE `households`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT a táblához `household_invites`
--
ALTER TABLE `household_invites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT a táblához `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT a táblához `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT a táblához `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT a táblához `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT a táblához `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT a táblához `shopping_list_items`
--
ALTER TABLE `shopping_list_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT a táblához `translations_cache`
--
ALTER TABLE `translations_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `households`
--
ALTER TABLE `households`
  ADD CONSTRAINT `households_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `household_invites`
--
ALTER TABLE `household_invites`
  ADD CONSTRAINT `fk_inv_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_invited_user` FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_inviter` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `household_members`
--
ALTER TABLE `household_members`
  ADD CONSTRAINT `household_members_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `household_members_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
