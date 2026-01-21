-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Gép: localhost
-- Létrehozás ideje: 2026. Jan 21. 09:15
-- Kiszolgáló verziója: 8.0.42
-- PHP verzió: 8.2.29

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
(10, '52934', 'Chicken Basquaise', 'Melegítse elő a sütőt 180°C-ra/Gázjelölés 4. Készítse elő a csirkeízületeket főzésre. Melegítse fel a vajat és 3 evőkanál olívaolajat tűzálló edényben vagy nagy serpenyőben. Pirítsa meg a csirke darabokat kötegekben mindkét oldalon, és fűszerezze meg őket sóval és borssal menet közben. Ne zsúfolja össze a serpenyőt - kis tételekben süsse meg a csirkét, és vegye ki a darabokat a konyhai papírból.\n\nAdjon hozzá egy kicsit több olívaolajat a raguhoz, és süsse a hagymát közepes lángon 10 percig, gyakran kevergetve, amíg megpuhul, de nem pirul meg. Adja hozzá a maradék olajat, majd a paprikát, és főzze további 5 percig.\r\n\r\nAdja hozzá a chorizót, a napon szárított paradicsomot és a fokhagymát, és főzze 2-3 percig. Adja hozzá a rizst, kevergetve, hogy megbizonyosodjon arról, hogy jól be van vonva az olajjal.\n\nKeverje bele a paradicsompürét, a paprikát, a babérlevelet és az apróra vágott kakukkfüvet. Öntse bele a készletet és a bort. Amikor a folyadék buborékolni kezd, tekerje lejjebb a hőt egy enyhe párolásra. Nyomja bele a rizst a folyadékba, ha még nincs alámerülve, és helyezze a csirkét a tetejére. Helyezze a citromos éket és az olajbogyót a csirke köré.\r\n\r\nFedje le és főzze a sütőben 50 percig.\n\nA rizst meg kell főzni, de még mindig van benne egy kis harapás, és a csirkének olyan gyümölcslevekkel kell rendelkeznie, amelyek a legvastagabb részbe késsel átszúrva tiszták. Ha nem, főzze további 5 percig, és ellenőrizze újra.', '2026-01-21 07:40:58');

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
(10, 12, 'alma háztartása', '2026-01-21 08:32:35');

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
(3, 8, 1, 8, 'pending', '2026-01-21 07:42:01', NULL);

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
(10, 8, 10, 'alap felhasználó', '2026-01-20 11:06:42');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int NOT NULL,
  `household_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` enum('fridge','pantry','freezer') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pantry',
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expired_notified` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `household_id`, `name`, `category`, `location`, `quantity`, `unit`, `expires_at`, `note`, `created_at`, `updated_at`, `expired_notified`) VALUES
(3, 8, 'zsír', NULL, 'pantry', 3.00, 'kg', '2026-01-20', NULL, '2026-01-21 08:22:57', '2026-01-21 08:31:09', 1),
(5, 8, 'krumpli', NULL, 'freezer', 2.00, 'kg', '2026-01-22', NULL, '2026-01-21 08:29:06', '2026-01-21 08:31:01', 0),
(6, 8, 'kifli', NULL, 'pantry', 3.00, 'db', '2026-01-20', NULL, '2026-01-21 08:29:08', '2026-01-21 08:31:09', 1),
(8, 8, 'vaj', NULL, 'pantry', 1.00, 'db', '2026-01-22', NULL, '2026-01-21 08:29:10', '2026-01-21 08:30:36', 0),
(9, 9, 'banán', NULL, 'pantry', 1.00, NULL, '2026-01-25', NULL, '2026-01-21 08:34:04', '2026-01-21 08:34:22', 0),
(10, 9, 'alma', NULL, 'pantry', 1.00, NULL, '2026-01-25', NULL, '2026-01-21 08:34:05', '2026-01-21 08:34:20', 0),
(11, 9, 'kenyér', NULL, 'pantry', 1.00, NULL, '2026-01-25', NULL, '2026-01-21 08:34:06', '2026-01-21 08:34:17', 0);

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
(16, 8, 8, 'danger', 'Lejárt termék a raktárban', 'Lejárt: kenyér (lejárat: 2026-01-20). Nézd meg a raktárban.', 'inventory.php', 1, '2026-01-21 08:31:09');

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
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_general_ci,
  `is_bought` tinyint(1) NOT NULL DEFAULT '0',
  `bought_at` datetime DEFAULT NULL,
  `bought_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `location` enum('fridge','freezer','pantry') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pantry'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(12, 'alma', 'alma@gmail.com', '$2y$10$T9aMUf50EBNBiM0rsr8XsOq59vWiPTRJw02GWL/vp/ljZ2wYHje7m', '2026-01-21 08:32:23');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT a táblához `households`
--
ALTER TABLE `households`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT a táblához `household_invites`
--
ALTER TABLE `household_invites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT a táblához `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT a táblához `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
