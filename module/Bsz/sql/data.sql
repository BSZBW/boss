/*
 * Copyright 2023 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

INSERT INTO libraries.authentications (id, name) VALUES(1, 'adis');
INSERT INTO libraries.authentications (id, name) VALUES(6, 'alephinoh');
INSERT INTO libraries.authentications (id, name) VALUES(7, 'bibliotheca');
INSERT INTO libraries.authentications (id, name) VALUES(9, 'koha');
INSERT INTO libraries.authentications (id, name) VALUES(10, 'kohaauth');
INSERT INTO libraries.authentications (id, name) VALUES(2, 'libero');
INSERT INTO libraries.authentications (id, name) VALUES(8, 'lmscloud');
INSERT INTO libraries.authentications (id, name) VALUES(5, 'shibboleth');
INSERT INTO libraries.authentications (id, name) VALUES(4, 'slnp');
INSERT INTO libraries.authentications (id, name) VALUES(3, 'tan');

INSERT INTO libraries.countries (id, name, shortcut) VALUES(1, 'Baden-Württemberg', 'BaWue');
INSERT INTO libraries.countries (id, name, shortcut) VALUES(2, 'Saarland', 'Saarland');
INSERT INTO libraries.countries (id, name, shortcut) VALUES(3, 'Sachsen', 'Sachsen');

INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(1, 'DE-Rav1', 'Campus Ravensburg', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(2, 'DE-Rav1-a', 'Campus Friedrichshafen', '', 1, 0);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(7, 'DE-14', 'Zentralbibliothek', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(8, 'DE-14', 'ZwB Forst', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(9, 'DE-14', 'ZwB Medizin', '', 1, 3);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(13, 'DE-16', 'Ausleihe Altstadt', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(14, 'DE-16', 'Ausleihe Neuenheim.', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(15, 'DE-Fn1', 'Campus Furtwangen', '', 1, 0);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(16, 'DE-Fn1-VS', 'Campus Schwenningen', '', 1, 0);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(17, 'DE-Fn1-TUT', 'Campus Tuttlingen', '', 1, 0);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(22, 'DE-941', 'Campus Bad Mergentheim', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(23, 'DE-941', 'Campus Mosbach', '', 1, 0);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(24, 'DE-Zi4', 'Zittau', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(25, 'DE-Zi4', 'Görlitz', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(26, 'DE-Stg117', 'EHZ S-Möhringen', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(27, 'DE-Stg117', 'OKR, eigener Arbeitsplatz', '', 1, 3);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(31, 'DE-Stg117', 'Postversand', '', 1, 4);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(32, 'DE-520', 'Zentralbibliothek', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(33, 'DE-520', 'Zweigstelle Pillnitz', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(34, 'DE-Stg257', 'EHZ S-Birkach', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(35, 'DE-Stg257', 'Postversand', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(36, 'DE-Zwi2', 'Hauptbibliothek', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(37, 'DE-Zwi2', 'Markneukirchen', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(38, 'DE-Zwi2', 'Scheffelstraße', '', 1, 4);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(39, 'DE-Zwi2', 'Reichenbach', '', 1, 3);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(40, 'DE-Zwi2', 'Schneeberg', '', 1, 5);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(44, 'DE-950', 'Nürtingen, Campus Innenstadt', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(45, 'DE-1090', 'Campus Geislingen', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(46, 'DE-950-1', 'Nürtingen, Campus Braike', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(49, 'DE-753', 'Hauspost (nur für Mitarbeitende)', 'Hauspost', 0, 3);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(50, 'DE-753', 'Vor Ort (Informationen auf der Bibliothekshomepage)', 'Vor-Ort', 0, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(51, 'DE-753', 'Postversand (aktuelle Adresse im Feld Bemerkungen)', 'Postversand', 0, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(52, 'DE-972', 'Hauspost (nur für Mitarbeitende)', 'Hauspost', 0, 3);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(53, 'DE-972', 'Vor Ort (Informationen auf der Bibliothekshomepage)', 'Vor-Ort', 0, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(54, 'DE-972', 'Postversand (aktuelle Adresse im Feld Bemerkungen)', 'Postversand', 0, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(55, 'DE-972', 'Bücherabholung (Informationen auf der Bibliothekshomepage)', 'Abholung', 0, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(56, 'DE-753', 'Bücherabholung (Informationen auf der Bibliothekshomepage)', 'Abholung', 0, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(57, 'DE-31', 'Leihstelle', 'LEIH', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(58, 'DE-100', 'Zentralbibliothek', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(59, 'DE-972', 'ES-Flandernstraße', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(60, 'DE-972', 'Standort Göppingen', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(61, 'DE-753', 'ES-Flandernstraße', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(62, 'DE-753', 'Standort Göppingen', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(63, 'DE-Rt3', 'Theke', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(64, 'DE-991', 'Campus Sigmaringen', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(65, 'DE-991a', 'Campus Albstadt', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(66, 'DE-Kon4', 'HTWG Konstanz', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(67, 'DE-944', 'Hochschule Aalen Bibliothek', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(68, 'DE-Vil2', 'Duale Hochschule Villingen-Schwenningen Bibliothek', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(69, 'DE-752', 'PH Bibliothek', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(70, 'DE-Stg117', 'Mitarbeitende Haus Birkach', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(71, 'DE-93', 'UB Stadtmitte', '', 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(72, 'DE-93', 'UB Vaihingen', '', 1, 2);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(73, 'DE-991', 'Campus Albstadt', NULL, 1, 1);
INSERT INTO libraries.places (id, library, name, code, active, sort) VALUES(74, 'DE-991a', 'Campus Sigmaringen', NULL, 1, 1);


