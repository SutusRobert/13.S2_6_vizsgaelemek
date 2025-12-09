-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Gép: localhost
-- Létrehozás ideje: 2025. Dec 09. 11:16
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
  `meal_id` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `hu_title` text COLLATE utf8mb4_general_ci,
  `hu_instructions` longtext COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `api_recipe_translations`
--

INSERT INTO `api_recipe_translations` (`id`, `meal_id`, `hu_title`, `hu_instructions`, `created_at`) VALUES
(1, '52795', 'Csirke handi', 'Vegyen egy nagy edényt vagy wokot, amely elég nagy ahhoz, hogy megfőzze az összes csirkét, és melegítse fel benne az olajat. Miután az olaj felforrósodott, adjon hozzá szeletelt hagymát, és süsse mély aranybarnára. Ezután vegye ki őket egy tányéron, és tegye félre.\r\nUgyanahhoz az edényhez adja hozzá az apróra vágott fokhagymát, és párolja egy percig. Ezután adja hozzá az apróra vágott paradicsomot, és főzze addig, amíg a paradicsom megpuhul. Ez körülbelül 5 percet vesz igénybe.\n\nEzután tegye vissza a sült hagymát a fazékba, és keverje meg. Adja hozzá a gyömbérkrémet, és alaposan pirítsa meg.\r\nMost adja hozzá a köménymagot, a koriandermag felét és az apróra vágott zöld chilipaprikát. Adj nekik egy gyors keverőt.\r\nEzután jön a fűszerek – kurkuma por és piros chili por. Pár percig jól párolja a fűszereket.\n\nAdja hozzá a csirkedarabokat a wokhoz, ízlés szerint fűszerezze sóval, és főzze a csirkét közepesen alacsony lángon, amíg a csirke majdnem átsül. Ez körülbelül 15 percet vesz igénybe. A csirke lassú párolása fokozza az ízt, ezért ne gyorsítsa fel ezt a lépést azzal, hogy magas hőfokra helyezi.\n\nAmikor az olaj elválik a fűszerektől, adja hozzá a megvert joghurtot, és tartsa a hőt a legalacsonyabban, hogy a joghurt ne hasadjon szét. Szórja meg a megmaradt koriandermagokat, és adja hozzá a szárított görögszéna levelek felét. Keverje jól össze.\r\nVégül adja hozzá a krémet, és adjon hozzá egy végső keveréket, hogy mindent jól kombináljon.\r\nSzórja meg a maradék kasuri methit és garam masalát, és tálalja a csirke handit forrón naannal vagy rotisszal. Jó étvágyat!', '2025-12-09 11:05:37'),
(2, '53358', 'Csirke mandi', '1. Tisztítsa meg és vágja fel a csirkét; rövid ideig pácolja sóval, kurkumával és egy kis olajjal.\r\n2. Öblítse le és áztassa a basmati rizst 20–30 percig.\r\n3. Egy nagy edényben melegítse fel a ghee-t/olajat. Fry apróra vágott hagymát, amíg arany. Adjon hozzá darált fokhagymát és zöld chilit, és süsse meg 1–2 percig.\r\n4. Adjon hozzá egész fűszereket (kardamom, szegfűszeg, fahéj, babérlevél) és őrölt fűszereket (koriander, kömény). Keverje addig, amíg illatos lesz.\r\n5.\n\nAdjon hozzá csirkehúsdarabokat, enyhén barnítsa meg őket, és adjon hozzá elegendő vizet/csirkehúst a Párolja addig, amíg a csirke majdnem megfő.\r\n6. Távolítsa el a csirkét; mérje meg a maradék folyadékot, és adjon hozzá áztatott riz Forralja fel, majd csökkentse a hőt, fedje le és főzze a rizst, amíg majdnem kész.\r\n7. Tegye vissza a csirkét a tetején lévő rizsfazékba, fedje le szorosan, és gőzölje alacsonyra 10–15 percig, hogy az ízek összeolvadjanak.\r\n8.\n\n(Választható) Az autentikus füstös aromához: melegítsen egy kis faszenet, amíg vörösen felforrósodik, helyezze egy kis fóliacsészére az edény közepén, adjon hozzá egy teáskanál vajat/olajat a szénhez, majd azonnal fedje le, hogy 5–10 percig elzárja a füstöt. Távolítsa el a szenet.\r\n9. Díszítse sült hagymával, apróra vágott korianderrel, és tálalja chutney-val vagy raitával.', '2025-12-09 11:06:50'),
(3, '52854', 'Palacsinták', 'Tegye a lisztet, a tojást, a tejet, 1 evőkanál olajat és egy csipet sót egy tálba vagy egy nagy kancsóba, majd habverővel egyenletes tésztára. Szánjon 30 percet pihenésre, ha van ideje, vagy azonnal kezdjen el főzni.\r\nHelyezzen egy közepes serpenyőt vagy krepp serpenyőt közepes hőre, és óvatosan törölje át olajozott konyhai papírral.\n\nAmikor forró, főzze a palacsintákat 1 percig mindkét oldalon, amíg aranyszínű nem lesz, miközben melegen tartja őket egy alacsony sütőben.\r\nCitromkarikákkal és cukorral, vagy kedvenc töltelékével tálaljuk. Miután kihűlt, rétegezheti a palacsintákat a sütőpapír között, majd ragasztófóliába csomagolhatja és akár 2 hónapig is fagyaszthatja.', '2025-12-09 11:10:51'),
(4, '53214', 'Thai zöld csirkeleves', '1. lépés\r\nMelegítse fel az olajat a legnagyobb serpenyőben, adja hozzá a hagymát, és süsse 3 percig, hogy lágyuljon. Adja hozzá a csirkét és a fokhagymát, és főzze addig, amíg a csirke színe meg nem változik.\r\n\r\n2. lépés\r\nAdja hozzá a currys pasztát, a kókusztejet, az alapanyagot, a citromlevelet és a halszószt, majd párolja 12 percig. Adja hozzá az apróra vágott hagyma tetejét, a zöldbabot és a bambuszrügyet, és főzze 4-6 percig, amíg a bab csak puha nem lesz.\n\n3. lépés\r\nKözben tegye a lime juice-t és a bazsalikomot egy keskeny kancsóba, és keverje össze egy kézi turmixgéppel, hogy sima zöld pasztát készítsen. Öntse a levesbe a szeletelt újhagymát, és melegítse át. A könnyű ebédhez vagy vacsorához, illetve előételként meszes ékekkel tálaljuk.', '2025-12-09 11:12:00');

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
(4, 4, 'Kiss Gábor háztartása', '2025-12-09 11:12:59');

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
(5, 4, 1, 'tag', '2025-12-09 11:13:15');

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
(2, 1, 'csirkemájas', '2025-11-28 12:42:45');

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
(3, 2, 'csirke');

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
(4, 'Kiss Gábor', 'gaborvagyok@gmail.com', '$2y$10$dA7Yh0CAJp3bYeDXQiwrM.C5VldZUuPsN1Q/kNmcA8ddQVX1MVRR.', '2025-12-09 11:09:48');

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
-- A tábla indexei `household_members`
--
ALTER TABLE `household_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`),
  ADD KEY `member_id` (`member_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT a táblához `households`
--
ALTER TABLE `households`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT a táblához `household_members`
--
ALTER TABLE `household_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT a táblához `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT a táblához `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `households`
--
ALTER TABLE `households`
  ADD CONSTRAINT `households_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `household_members`
--
ALTER TABLE `household_members`
  ADD CONSTRAINT `household_members_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `household_members_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
