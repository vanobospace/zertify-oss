<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Support\ListeningTeilOneSegmentedContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZertifySeeder extends Seeder
{
    public function run(): void
    {
        $exam = $this->syncExam();

        $teil1 = $exam->modules()->updateOrCreate(
            ['slug' => 'sprachbausteine-teil-1'],
            [
                'name' => 'Sprachbausteine Teil 1',
                'type' => 'gap_fill',
                'default_points' => 1.5,
            ]
        );

        $this->createTeil1Questions($teil1);

        $teil2 = $exam->modules()->updateOrCreate(
            ['slug' => 'sprachbausteine-teil-2'],
            [
                'name' => 'Sprachbausteine Teil 2',
                'type' => 'gap_fill',
                'default_points' => 1.5,
            ]
        );

        $this->createTeil2Questions($teil2);

        $lesenTeil1 = $exam->modules()->updateOrCreate(
            ['slug' => 'lesen-teil-1'],
            [
                'name' => 'Lesen Teil 1',
                'type' => 'reading',
                'default_points' => 1.5,
            ]
        );

        $this->createLesenTeil1Questions($lesenTeil1);

        $lesenTeil2 = $exam->modules()->updateOrCreate(
            ['slug' => 'lesen-teil-2'],
            [
                'name' => 'Lesen Teil 2',
                'type' => 'reading',
                'default_points' => 1.5,
            ]
        );

        $this->createLesenTeil2Questions($lesenTeil2);

        $lesenTeil3 = $exam->modules()->updateOrCreate(
            ['slug' => 'lesen-teil-3'],
            [
                'name' => 'Lesen Teil 3',
                'type' => 'reading',
                'default_points' => 1.5,
            ]
        );

        $this->createLesenTeil3Questions($lesenTeil3);

        $hoerenTeil1 = $exam->modules()->updateOrCreate(
            ['slug' => 'hoeren-teil-1'],
            [
                'name' => 'Hören Teil 1',
                'type' => 'listening',
                'default_points' => 1.5,
            ]
        );

        $this->createHoerenTeil1Questions($hoerenTeil1);

        $hoerenTeil2 = $exam->modules()->updateOrCreate(
            ['slug' => 'hoeren-teil-2'],
            [
                'name' => 'Hören Teil 2',
                'type' => 'listening',
                'default_points' => 1.5,
            ]
        );

        $this->createHoerenTeil2Questions($hoerenTeil2);

        $hoerenTeil3 = $exam->modules()->updateOrCreate(
            ['slug' => 'hoeren-teil-3'],
            [
                'name' => 'Hören Teil 3',
                'type' => 'listening',
                'default_points' => 1.5,
            ]
        );

        $this->createHoerenTeil3Questions($hoerenTeil3);
    }

    public function syncListeningSeeds(): void
    {
        $exam = $this->syncExam();

        $hoerenTeil1 = $exam->modules()->updateOrCreate(
            ['slug' => 'hoeren-teil-1'],
            [
                'name' => 'Hören Teil 1',
                'type' => 'listening',
                'default_points' => 1.5,
            ]
        );

        $this->createHoerenTeil1Questions($hoerenTeil1);

        $hoerenTeil2 = $exam->modules()->updateOrCreate(
            ['slug' => 'hoeren-teil-2'],
            [
                'name' => 'Hören Teil 2',
                'type' => 'listening',
                'default_points' => 1.5,
            ]
        );

        $this->createHoerenTeil2Questions($hoerenTeil2);

        $hoerenTeil3 = $exam->modules()->updateOrCreate(
            ['slug' => 'hoeren-teil-3'],
            [
                'name' => 'Hören Teil 3',
                'type' => 'listening',
                'default_points' => 1.5,
            ]
        );

        $this->createHoerenTeil3Questions($hoerenTeil3);
    }

    private function syncExam(): Exam
    {
        Exam::where('slug', 'goethe-b2')->update(['slug' => 'telc-b2', 'name' => 'B2 Allgemein']);

        return Exam::updateOrCreate(
            ['slug' => 'telc-b2'],
            [
                'name' => 'B2 Allgemein',
                'level' => 'B2',
            ]
        );
    }

    /** @param Module $module */
    private function createTeil1Questions($module): void
    {
        $module->questions()->delete();

        $tasks = [
            [
                'topic' => 'Beschwerde: Mangelhafter Online-Sprachkurs',
                'content' => [
                    'text' => "Sehr geehrte Damen und Herren,\n\nhiermit wende ich mich an Sie, um mich über erhebliche Mängel des Online-Sprachkurses Deutsch B2 zu beschweren, den ich vor zwei Monaten bei Ihrem Sprachinstitut {{gap_1}} habe. Bei meiner Anmeldung war ich von der ausführlichen Kursbeschreibung überzeugt, in der ausdrücklich {{gap_2}}, dass alle Teilnehmer täglich persönliche Rückmeldungen von qualifizierten Lehrkräften erhalten würden.\n\nZu meinem großen {{gap_3}} musste ich jedoch gleich in der ersten Kurswoche feststellen, dass meine schriftlichen Aufgaben tagelang unkorrigiert blieben. Auf insgesamt vier E-Mails an den Kundendienst habe ich bis heute keine einzige Antwort {{gap_4}}. Darüber hinaus brach die Lernplattform mehrmals täglich zusammen, {{gap_5}} ich wichtige Unterrichtseinheiten verpasste und im Lernfortschritt deutlich zurückblieb. Bei diesen Problemen handelt es sich keinesfalls {{gap_6}} Einzelfälle, sondern um ein durchgehendes technisches Versagen Ihres Systems.\n\nDa ich {{gap_7}} diesen Kurs mehrere Hundert Euro bezahlt habe, halte ich eine Entschädigung für mehr als gerechtfertigt. Ich fordere Sie {{gap_8}} auf, mir bis zum Ende des laufenden Monats entweder eine anteilige Rückerstattung oder einen kostenfreien Ersatzkurs anzubieten. Ich gehe davon {{gap_9}}, dass Sie meinem berechtigten Anliegen nachkommen werden.\n\nSollte ich bis dahin keine zufriedenstellende Antwort erhalten, behalte ich mir ausdrücklich vor, die Verbraucherzentrale zu {{gap_10}} und entsprechende Bewertungen in einschlägigen Portalen zu veröffentlichen.\n\nMit freundlichen Grüßen\nKatharina Richter",
                    'options' => [
                        'gap_1' => ['gebucht', 'gemacht', 'begonnen'],
                        'gap_2' => ['hieß es', 'stand es', 'schrieb es'],
                        'gap_3' => ['Bedauern', 'Glück', 'Erstaunen'],
                        'gap_4' => ['erhalten', 'geschickt', 'geschrieben'],
                        'gap_5' => ['sodass', 'obwohl', 'denn'],
                        'gap_6' => ['um', 'von', 'über'],
                        'gap_7' => ['für', 'von', 'wegen'],
                        'gap_8' => ['daher', 'trotzdem', 'zwar'],
                        'gap_9' => ['aus', 'ein', 'ab'],
                        'gap_10' => ['informieren', 'kontaktieren', 'anrufen'],
                    ],
                    'correct' => [
                        'gap_1' => 'gebucht',
                        'gap_2' => 'hieß es',
                        'gap_3' => 'Bedauern',
                        'gap_4' => 'erhalten',
                        'gap_5' => 'sodass',
                        'gap_6' => 'um',
                        'gap_7' => 'für',
                        'gap_8' => 'daher',
                        'gap_9' => 'aus',
                        'gap_10' => 'informieren',
                    ],
                    'explanation' => [
                        'gap_1' => 'gebucht — Einen Kurs buchen: feste Kollokation. „machen" ist umgangssprachlich.',
                        'gap_2' => 'hieß es — Es hieß, dass: feste Redewendung für schriftliche und mündliche Aussagen.',
                        'gap_3' => 'Bedauern — Zu meinem Bedauern: B2-Formulierung in Beschwerdebriefen.',
                        'gap_4' => 'erhalten — Eine Antwort erhalten: formeller als „bekommen". „geschickt" wäre falsches Passiv.',
                        'gap_5' => 'sodass — Folgekonjunktion: drückt direkte Konsequenz aus. Verbletztstellung im Nebensatz.',
                        'gap_6' => 'um — Sich handeln um (Akkusativ): festes Verb-Präposition-Paar.',
                        'gap_7' => 'für — Für etwas bezahlen: Präposition „für" + Akkusativ.',
                        'gap_8' => 'daher — Kausaladverb: logische Schlussfolgerung, Verbzweitstellung.',
                        'gap_9' => 'aus — Davon ausgehen: trennbares Verb (gehe...aus), feste Redewendung.',
                        'gap_10' => 'informieren — Jemanden informieren: formeller als „kontaktieren" oder „anrufen".',
                    ],
                ],
            ],
            [
                'topic' => 'Anfrage: Praktikum in Marketingagentur',
                'content' => [
                    'text' => "Sehr geehrte Frau Müller,\n\nmit großem Interesse habe ich Ihre Stellenanzeige {{gap_1}} ein Praktikum im Bereich Marketing auf Ihrer Website entdeckt. Da ich mich zurzeit im vierten Semester meines Bachelorstudiums der Betriebswirtschaftslehre an der Universität Mannheim befinde, bin ich aktiv auf der Suche nach einer Möglichkeit, praktische Erfahrungen in einem innovativen Unternehmen wie Ihrem zu sammeln.\n\nIch interessiere mich {{gap_2}} besonders für den Bereich des digitalen Marketings, da ich in diesem Feld sowohl meine Bachelorarbeit als auch meinen späteren Berufseinstieg plane. Im Laufe meines Studiums habe ich umfassende theoretische Kenntnisse über Kampagnenplanung, Marktforschung und Social-Media-Strategien aufgebaut. Darüber hinaus verfüge ich bereits {{gap_3}} erste praktische Erfahrungen, die ich während eines Nebenjobs in einer kleinen Werbeagentur {{gap_4}} habe. Dort war ich unter anderem für die Pflege von Social-Media-Kanälen und die Analyse von Nutzerdaten {{gap_5}}.\n\nDarf ich Sie noch um einige Informationen bitten? Mich würde interessieren, wie lange ein Praktikum bei Ihnen {{gap_6}} dauern muss und {{gap_7}} eine finanzielle Vergütung vorgesehen ist. Da ich meinen Lebensunterhalt selbst finanziere, wäre dies ein wichtiger {{gap_8}} bei meiner Entscheidung.\n\nIm Anhang finden Sie meinen Lebenslauf {{gap_9}} aktuelle Arbeitszeugnisse. Über eine positive Rückmeldung {{gap_10}} ich mich sehr freuen.\n\nMit freundlichen Grüßen\nLaura Weber",
                    'options' => [
                        'gap_1' => ['für', 'über', 'zu'],
                        'gap_2' => ['daher', 'jedoch', 'zwar'],
                        'gap_3' => ['über', 'von', 'an'],
                        'gap_4' => ['gesammelt', 'gemacht', 'gewonnen'],
                        'gap_5' => ['zuständig', 'beschäftigt', 'tätig'],
                        'gap_6' => ['mindestens', 'höchstens', 'ungefähr'],
                        'gap_7' => ['ob', 'dass', 'weil'],
                        'gap_8' => ['Faktor', 'Punkt', 'Aspekt'],
                        'gap_9' => ['sowie', 'oder', 'aber'],
                        'gap_10' => ['würde', 'werde', 'hätte'],
                    ],
                    'correct' => [
                        'gap_1' => 'für',
                        'gap_2' => 'daher',
                        'gap_3' => 'über',
                        'gap_4' => 'gesammelt',
                        'gap_5' => 'zuständig',
                        'gap_6' => 'mindestens',
                        'gap_7' => 'ob',
                        'gap_8' => 'Faktor',
                        'gap_9' => 'sowie',
                        'gap_10' => 'würde',
                    ],
                    'explanation' => [
                        'gap_1' => 'für — Stellenanzeige für ein Praktikum: Präposition „für" + Akkusativ.',
                        'gap_2' => 'daher — Kausaladverb: logische Folge — weil ich BWL studiere, interessiere ich mich daher für Marketing.',
                        'gap_3' => 'über — Verfügen über (Akkusativ): festes Verb-Präposition-Paar.',
                        'gap_4' => 'gesammelt — Erfahrungen sammeln: feste Kollokation. „machen" ist umgangssprachlich.',
                        'gap_5' => 'zuständig — Zuständig sein für: Adjektiv mit fester Präposition „für".',
                        'gap_6' => 'mindestens — Untere Grenze. „höchstens" wäre die obere Grenze.',
                        'gap_7' => 'ob — Indirekter Fragesatz (Ja/Nein-Frage): „ob eine Vergütung vorgesehen ist".',
                        'gap_8' => 'Faktor — Ein wichtiger Faktor: Nomen in fester Kollokation.',
                        'gap_9' => 'sowie — Formelle additive Konjunktion. „oder" wäre exklusiv.',
                        'gap_10' => 'würde — Konjunktiv II für höfliche Aussagen und Wünsche: würde + Infinitiv.',
                    ],
                ],
            ],
            [
                'topic' => 'Kündigung: Fitnessstudio-Mitgliedschaft',
                'content' => [
                    'text' => "Sehr geehrte Damen und Herren,\n\nhiermit kündige ich meine Mitgliedschaft in Ihrem Fitnessstudio mit der Mitgliedsnummer 47821 {{gap_1}} zum nächstmöglichen Termin. Ich bitte Sie, mir die Kündigung schriftlich zu {{gap_2}} und mir den genauen letzten Gültigkeitstag meiner Mitgliedschaft mitzuteilen.\n\nDer Hauptgrund für meine Entscheidung liegt in der zunehmend schlechten Qualität der angebotenen Dienstleistungen. Trotz der hohen monatlichen Beiträge, die ich seit über drei Jahren pünktlich entrichtet habe, entsprechen die Bedingungen im Studio schon lange nicht mehr {{gap_3}} dem Standard, den ich zu Beginn meiner Mitgliedschaft vorgefunden habe. Mehrere Trainingsgeräte sind seit Wochen defekt und wurden bisher nicht {{gap_4}}. Das Personal zeigt sich {{gap_5}} Beschwerden gegenüber leider wenig hilfsbereit, was die Situation für die Mitglieder noch unangenehmer macht.\n\nZusätzlich haben die Gruppentrainingskurse, die ursprünglich ein zentrales Merkmal des Angebots waren, in letzter Zeit stark {{gap_6}}. Viele Kurse wurden ersatzlos gestrichen, {{gap_7}} wir als Mitglieder nicht rechtzeitig informiert wurden. Dies stellt aus meiner Sicht eine wesentliche Verschlechterung des vereinbarten Leistungsumfangs dar.\n\nIch {{gap_8}} Sie außerdem darauf hin, dass ich für den Zeitraum der nachgewiesenen Mängel eine anteilige Rückerstattung meiner Beiträge für angemessen halte. Über eine kulante Regelung {{gap_9}} ich mich freuen. Sollte keine Einigung {{gap_10}} kommen, werde ich rechtliche Schritte prüfen.\n\nMit freundlichen Grüßen\nMarkus Sommer",
                    'options' => [
                        'gap_1' => ['fristgerecht', 'sofort', 'umgehend'],
                        'gap_2' => ['bestätigen', 'senden', 'mitteilen'],
                        'gap_3' => ['annähernd', 'völlig', 'kaum'],
                        'gap_4' => ['repariert', 'erneuert', 'geprüft'],
                        'gap_5' => ['berechtigten', 'häufigen', 'schriftlichen'],
                        'gap_6' => ['abgenommen', 'reduziert', 'verändert'],
                        'gap_7' => ['wobei', 'obwohl', 'weil'],
                        'gap_8' => ['weise', 'bitte', 'fordere'],
                        'gap_9' => ['würde', 'werde', 'wollte'],
                        'gap_10' => ['zustande', 'heraus', 'infrage'],
                    ],
                    'correct' => [
                        'gap_1' => 'fristgerecht',
                        'gap_2' => 'bestätigen',
                        'gap_3' => 'annähernd',
                        'gap_4' => 'repariert',
                        'gap_5' => 'berechtigten',
                        'gap_6' => 'abgenommen',
                        'gap_7' => 'wobei',
                        'gap_8' => 'weise',
                        'gap_9' => 'würde',
                        'gap_10' => 'zustande',
                    ],
                    'explanation' => [
                        'gap_1' => 'fristgerecht — Fristgerecht kündigen: Adverb. „sofort" wäre außerordentliche Kündigung.',
                        'gap_2' => 'bestätigen — Etwas bestätigen: feste Kollokation mit Kündigung. „mitteilen" würde einen anderen Satzbau erfordern.',
                        'gap_3' => 'annähernd — Nicht annähernd: Gradpartikel in Negation, verstärkt den Kontrast.',
                        'gap_4' => 'repariert — Defekte Geräte reparieren: logische Kollokation.',
                        'gap_5' => 'berechtigten — Berechtigte Beschwerden: Adjektiv. „häufige" passt inhaltlich nicht als Komplement.',
                        'gap_6' => 'abgenommen — Stark abnehmen: trennbares Verb. „reduziert" wäre Partizip, nicht finit.',
                        'gap_7' => 'wobei — Relativadverb: drückt Begleitumstand aus. „obwohl" wäre Konzession.',
                        'gap_8' => 'weise — Hinweisen auf: trennbares Verb (ich weise...hin), feste Wendung in formellen Briefen.',
                        'gap_9' => 'würde — Konjunktiv II für höfliche Wünsche: würde + Infinitiv. „wollte" ist weniger formell.',
                        'gap_10' => 'zustande — Zustande kommen: trennbares Verb in fester Redewendung. „heraus" und „infrage" passen nicht.',
                    ],
                ],
            ],
            [
                'topic' => 'Anfrage: Weiterbildungsseminar Buchhaltung',
                'content' => [
                    'text' => "Sehr geehrter Herr Dr. Fischer,\n\nüber das Weiterbildungsportal Ihrer Handelskammer bin ich {{gap_1}} auf Ihr zweitägiges Seminar \"Buchhaltung und Steuern für Selbstständige\" aufmerksam geworden. Da ich seit Kurzem als freiberuflicher Grafiker tätig bin und über keine kaufmännische Ausbildung verfüge, bin ich {{gap_2}} auf der Suche nach einer soliden Grundlage in diesen Bereichen.\n\nBevor ich mich {{gap_3}} anmelde, hätte ich einige Fragen, die mir bei meiner Entscheidung helfen würden. Zunächst würde mich interessieren, {{gap_4}} Vorkenntnisse für die Teilnahme am Seminar vorausgesetzt werden. Sind Grundkenntnisse in der doppelten Buchführung bereits notwendig, oder richtet sich das Seminar ausdrücklich auch {{gap_5}} absolute Anfänger? Außerdem wäre es hilfreich zu wissen, ob die Seminarunterlagen {{gap_6}} als digitale Dateien zur Verfügung gestellt werden, da ich keine Möglichkeit habe, umfangreiche Druckmaterialien aufzubewahren.\n\nEin weiterer Punkt betrifft die zeitliche {{gap_7}}: Das Seminar findet laut Ihrer Ankündigung am 14. und 15. Mai statt. Gibt es alternativ auch Termine {{gap_8}} Juni oder Juli, falls ich an den genannten Tagen verhindert sein sollte? Ich würde das Seminar {{gap_9}} sehr gerne im ersten Halbjahr absolvieren, da ich ab Herbst mit einer deutlich höheren Auftragslage rechne.\n\nÜber eine baldige Rückmeldung {{gap_10}} ich mich sehr freuen. Für eventuelle Rückfragen stehe ich Ihnen telefonisch jederzeit zur Verfügung.\n\nMit freundlichen Grüßen\nFabian Krause",
                    'options' => [
                        'gap_1' => ['zufällig', 'absichtlich', 'gezielt'],
                        'gap_2' => ['dringend', 'kaum', 'selten'],
                        'gap_3' => ['verbindlich', 'vorläufig', 'offiziell'],
                        'gap_4' => ['welche', 'was', 'wie'],
                        'gap_5' => ['an', 'für', 'bei'],
                        'gap_6' => ['ausschließlich', 'ebenfalls', 'normalerweise'],
                        'gap_7' => ['Planung', 'Gestaltung', 'Einteilung'],
                        'gap_8' => ['im', 'in', 'für'],
                        'gap_9' => ['daher', 'trotzdem', 'dennoch'],
                        'gap_10' => ['würde', 'werde', 'hätte'],
                    ],
                    'correct' => [
                        'gap_1' => 'zufällig',
                        'gap_2' => 'dringend',
                        'gap_3' => 'verbindlich',
                        'gap_4' => 'welche',
                        'gap_5' => 'an',
                        'gap_6' => 'ausschließlich',
                        'gap_7' => 'Planung',
                        'gap_8' => 'im',
                        'gap_9' => 'daher',
                        'gap_10' => 'würde',
                    ],
                    'explanation' => [
                        'gap_1' => 'zufällig — Zufällig auf etwas aufmerksam werden: Adverb der Art und Weise.',
                        'gap_2' => 'dringend — Dringend auf der Suche sein: Gradadverb. „kaum" wäre Negation.',
                        'gap_3' => 'verbindlich — Sich verbindlich anmelden: Adverb. „vorläufig" bedeutet provisorisch.',
                        'gap_4' => 'welche — Welche Vorkenntnisse: Fragepronomen für Plural-Nomen im Nominativ.',
                        'gap_5' => 'an — Sich richten an: festes Verb-Präposition-Paar (richten an + Akkusativ).',
                        'gap_6' => 'ausschließlich — Adverb der Einschränkung. „ebenfalls" würde Zusatz bedeuten.',
                        'gap_7' => 'Planung — Zeitliche Planung: Nomen in fester Kollokation mit „zeitlich".',
                        'gap_8' => 'im — Im Juni/Juli: Präposition „im" vor Monatsnamen (im + Dativ).',
                        'gap_9' => 'daher — Kausaladverb: logische Konsequenz — weil ich ab Herbst ausgelastet bin, möchte ich es daher im ersten Halbjahr.',
                        'gap_10' => 'würde — Konjunktiv II für höfliche Wünsche und Anfragen: würde + Infinitiv.',
                    ],
                ],
            ],
            [
                'topic' => 'Beschwerde: Verspätete Onlinebestellung',
                'content' => [
                    'text' => "Sehr geehrte Damen und Herren,\n\nüber Ihren Onlineshop habe ich am 3. März ein Paket mit Büromaterialien im Gesamtwert von 148 Euro bestellt. Laut der Auftragsbestätigung, die ich per E-Mail erhalten habe, sollte die Lieferung spätestens {{gap_1}} fünf Werktagen bei mir eingehen. Heute ist der 20. März, und das Paket ist bis {{gap_2}} nicht angekommen.\n\nIch habe Ihre Kundenhotline bereits dreimal angerufen und jedes Mal eine andere Auskunft erhalten. Zunächst wurde mir mitgeteilt, das Paket sei {{gap_3}} beim Versanddienstleister. Beim zweiten Anruf hieß es, das Paket sei verloren gegangen und werde neu verschickt. Beim dritten Gespräch {{gap_4}} man mir, ich solle noch einige Tage warten. Diese widersprüchlichen Informationen sind für mich absolut {{gap_5}} und zeugen von mangelnder Professionalität.\n\nDa ich die bestellten Materialien dringend für mein Büro benötige, fordere ich Sie {{gap_6}} auf, die Situation unverzüglich zu klären und mir das Paket innerhalb von 48 Stunden zuzustellen. Falls dies nicht {{gap_7}} ist, erwarte ich eine sofortige vollständige Rückerstattung des Kaufpreises auf mein Konto. Ich behalte mir {{gap_8}}, bei ausbleibender Reaktion eine Beschwerde bei der zuständigen Schlichtungsstelle einzureichen.\n\nBitte teilen Sie mir {{gap_9}} schriftlich mit, wie Sie in dieser Angelegenheit weiter vorgehen werden. Ich bitte um eine Antwort bis {{gap_10}} übermorgen.\n\nMit freundlichen Grüßen\nAnna Bergmann",
                    'options' => [
                        'gap_1' => ['innerhalb', 'binnen', 'nach'],
                        'gap_2' => ['dato', 'heute', 'jetzt'],
                        'gap_3' => ['unterwegs', 'verschickt', 'verloren'],
                        'gap_4' => ['riet', 'sagte', 'erklärte'],
                        'gap_5' => ['inakzeptabel', 'unangenehm', 'seltsam'],
                        'gap_6' => ['daher', 'trotzdem', 'jedoch'],
                        'gap_7' => ['möglich', 'machbar', 'umsetzbar'],
                        'gap_8' => ['vor', 'ein', 'an'],
                        'gap_9' => ['umgehend', 'baldmöglichst', 'schriftlich'],
                        'gap_10' => ['spätestens', 'frühestens', 'ungefähr'],
                    ],
                    'correct' => [
                        'gap_1' => 'innerhalb',
                        'gap_2' => 'dato',
                        'gap_3' => 'unterwegs',
                        'gap_4' => 'riet',
                        'gap_5' => 'inakzeptabel',
                        'gap_6' => 'daher',
                        'gap_7' => 'möglich',
                        'gap_8' => 'vor',
                        'gap_9' => 'umgehend',
                        'gap_10' => 'spätestens',
                    ],
                    'explanation' => [
                        'gap_1' => 'innerhalb — Innerhalb von fünf Werktagen: Präposition + Genitiv/Dativ. „binnen" ist veraltet.',
                        'gap_2' => 'dato — Bis dato: feste lateinische Redewendung in der Geschäftssprache.',
                        'gap_3' => 'unterwegs — Unterwegs sein: Adverb. „verschickt" wäre Partizip und passt syntaktisch nicht.',
                        'gap_4' => 'riet — Jemandem raten: Verb mit Dativ. „sagte" würde „dass" erfordern.',
                        'gap_5' => 'inakzeptabel — Stärker als „unangenehm", passt zum formellen Beschwerdebrief.',
                        'gap_6' => 'daher — Kausaladverb: logische Konsequenz — weil das Paket fehlt, fordere ich daher...',
                        'gap_7' => 'möglich — Möglich sein: Adjektiv als Prädikativ. „machbar" ist umgangssprachlicher.',
                        'gap_8' => 'vor — Sich etwas vorbehalten: trennbares Verb (behalte...vor), feste Wendung.',
                        'gap_9' => 'umgehend — Formelles Adverb in der Geschäftskorrespondenz.',
                        'gap_10' => 'spätestens — Spätestens bis: Gradpartikel. „frühestens" wäre frühest möglicher Zeitpunkt.',
                    ],
                ],
            ],
        ];

        foreach ($tasks as $index => $task) {
            $module->questions()->create([
                'topic' => $task['topic'],
                'content' => $this->normalizeSeedContent($task['content']),
                'format' => 'per_gap',
                'status' => 'published',
                'generation_mode' => 'manual',
                'source_label' => 'Internal Zertify seed set',
                'order' => ($index + 1) * 10,
                'is_active' => true,
                'difficulty' => 'medium',
                'points' => 1.0,
            ]);
        }
    }

    /** @param Module $module */
    private function createTeil2Questions($module): void
    {
        $module->questions()->delete();

        $tasks = [
            [
                'topic' => 'Gesundheit: Schlaf und Erholung',
                'content' => [
                    'format' => 'shared_pool',
                    // correct: jedoch(1) dass(2) weil(3) damit(4) ebenfalls(5) wodurch(6) sodass(7) daher(8) wenn(9) zudem(10)
                    // distractors: obwohl denn ob wobei trotzdem
                    'text' => "Schlaf gilt als eine der wichtigsten Voraussetzungen für ein gesundes Leben – {{gap_1}} unterschätzen viele Menschen seine Bedeutung erheblich. Mediziner sind sich einig, {{gap_2}} ausreichender Schlaf eine entscheidende Rolle für die körperliche und geistige Regeneration spielt. Während des Tiefschlafs werden Wachstumshormone ausgeschüttet, das Immunsystem gestärkt und Gedächtnisinhalte gefestigt. Wer dauerhaft zu wenig schläft, riskiert langfristige Schäden am Immunsystem, {{gap_3}} Schlaf maßgeblich an der Reparatur körpereigener Zellen beteiligt ist. Experten empfehlen für Erwachsene sieben bis neun Stunden Schlaf pro Nacht, {{gap_4}} sich Gehirn und Körper vollständig erholen können. Kinder und Jugendliche benötigen sogar noch deutlich mehr Schlaf, da ihr Organismus sich intensiv in der Wachstumsphase befindet.\n\n{{gap_5}} zeigen aktuelle Untersuchungen deutlich, dass Schlafmangel unmittelbare Auswirkungen auf die kognitive Leistungsfähigkeit hat. Konzentrationsfähigkeit und Reaktionsvermögen nehmen bereits nach einer einzigen schlafarmen Nacht messbar ab, {{gap_6}} die Unfallgefahr im Straßenverkehr erheblich steigt. Besonders problematisch ist die zunehmende Schlafstörung durch digitale Medien: Das blaue Licht von Bildschirmen hemmt die Ausschüttung des Schlafhormons Melatonin, {{gap_7}} Einschlafen und Durchschlafen erheblich erschwert werden. Wer abends regelmäßig soziale Medien nutzt oder Serien schaut, schläft im Schnitt über eine Stunde weniger als Menschen ohne diese Gewohnheiten. Schlafexperten raten {{gap_8}}, alle Bildschirme mindestens eine Stunde vor dem Schlafen abzuschalten und stattdessen entspannende Aktivitäten wie Lesen oder leichte Dehnübungen zu bevorzugen.\n\nAuch die Schlafumgebung beeinflusst die Schlafqualität maßgeblich. Ein kühles, dunkles und ruhiges Schlafzimmer fördert tiefen und erholsamen Schlaf. Die Raumtemperatur sollte idealerweise zwischen 16 und 18 Grad Celsius liegen, da es bei Wärme schwerer fällt, in den Tiefschlaf zu gleiten. {{gap_9}} man auf einen regelmäßigen Schlaf-Wach-Rhythmus achtet, kann der Körper seinen natürlichen Biorhythmus stabilisieren. Kurze Mittagsschläfchen von zwanzig bis dreißig Minuten können {{gap_10}} die Leistungsfähigkeit am Nachmittag steigern, ohne den Nachtschlaf zu beeinträchtigen. Länger sollte man tagsüber jedoch nicht schlafen, da sonst der Tiefschlafbedarf in der Nacht sinkt.",
                    'options_pool' => ['jedoch', 'dass', 'weil', 'damit', 'ebenfalls', 'wodurch', 'sodass', 'daher', 'wenn', 'zudem', 'obwohl', 'denn', 'ob', 'wobei', 'trotzdem'],
                    'correct' => [
                        'gap_1' => 'jedoch',
                        'gap_2' => 'dass',
                        'gap_3' => 'weil',
                        'gap_4' => 'damit',
                        'gap_5' => 'ebenfalls',
                        'gap_6' => 'wodurch',
                        'gap_7' => 'sodass',
                        'gap_8' => 'daher',
                        'gap_9' => 'wenn',
                        'gap_10' => 'zudem',
                    ],
                    'explanation' => [
                        'gap_1' => 'jedoch — Adversatives Adverb mit Verbzweitstellung: leitet Gegensatz zum Vorherigen ein.',
                        'gap_2' => 'dass — Einig sein, dass: Konjunktion für Aussagesatz als Nebensatz, Verbletztstellung.',
                        'gap_3' => 'weil — Kausalkonjunktion: gibt den Grund an, Verbletztstellung. „denn" würde Hauptsatz einleiten.',
                        'gap_4' => 'damit — Finalkonjunktion: drückt den Zweck/die Absicht aus. „sodass" wäre Folge, nicht Zweck.',
                        'gap_5' => 'ebenfalls — Additives Adverb: leitet weiteren Beleg ein.',
                        'gap_6' => 'wodurch — Relativadverb: bezieht sich auf den gesamten Vordersatz.',
                        'gap_7' => 'sodass — Konsekutivkonjunktion: drückt direkte Folge aus.',
                        'gap_8' => 'daher — Kausaladverb: logische Schlussfolgerung aus den Erkenntnissen.',
                        'gap_9' => 'wenn — Konditionalsatz: Bedingung für den Hauptsatz.',
                        'gap_10' => 'zudem — Additives Adverb: ergänzt weiteren Vorteil.',
                    ],
                ],
            ],
            [
                'topic' => 'Klimawandel: Individuelle und kollektive Verantwortung',
                'content' => [
                    'format' => 'shared_pool',
                    // correct: darin(1) was(2) obwohl(3) ob(4) da(5) jedoch(6) bewusst(7) zudem(8) weil(9) daher(10)
                    // distractors: darüber wobei denn wenn sodass
                    'text' => "Der Klimawandel stellt die größte globale Herausforderung unserer Zeit dar – {{gap_1}} sind sich nahezu alle Wissenschaftler einig. Die globale Durchschnittstemperatur ist seit der Industrialisierung um mehr als ein Grad Celsius gestiegen, {{gap_2}} gravierende Folgen wie häufigere Extremwetterereignisse, schmelzende Gletscher und steigende Meeresspiegel nach sich zieht. Bereits heute sind Küstenregionen in verschiedenen Teilen der Welt durch den Anstieg des Meeresspiegels akut bedroht. Regierungen weltweit haben sich im Pariser Klimaabkommen verpflichtet, die Erderwärmung auf deutlich unter zwei Grad zu begrenzen und die Treibhausgasemissionen drastisch zu reduzieren. {{gap_3}} die politischen Absichtserklärungen ambitioniert klingen, bleibt die praktische Umsetzung in vielen Ländern weit hinter den Erwartungen zurück.\n\nEine zentrale Frage ist, {{gap_4}} Einzelpersonen tatsächlich einen nennenswerten Beitrag zur Bekämpfung des Klimawandels leisten können. Kritiker argumentieren, dass die Hauptverantwortung bei der Industrie liegt, {{gap_5}} diese für den Großteil der weltweiten Treibhausgasemissionen verantwortlich ist. {{gap_6}} betonen andere Experten, dass kollektive Veränderungen im Konsumverhalten durchaus eine bedeutende Wirkung entfalten können. Wer {{gap_7}} auf Flugreisen verzichtet, weniger Fleisch konsumiert und öffentliche Verkehrsmittel nutzt, kann seinen persönlichen CO₂-Fußabdruck erheblich reduzieren. Studien zeigen, dass eine pflanzliche Ernährung den Treibhausgasausstoß einer Person um bis zu 73 Prozent senken kann.\n\n{{gap_8}} ist die soziale Dimension des Klimawandels nicht zu vernachlässigen: Ärmere Bevölkerungsschichten sind von den Folgen besonders stark betroffen, {{gap_9}} sie kaum Mittel haben, sich vor Extremwetterereignissen zu schützen oder in klimafreundlichere Technologien zu investieren. Besonders in Regionen, die von Landwirtschaft und Fischerei abhängig sind, hat der Klimawandel bereits jetzt katastrophale Folgen für die lokale Wirtschaft und die Lebensgrundlage der Menschen. Eine gerechte Klimapolitik muss {{gap_10}} soziale Ungleichheiten berücksichtigen und tragbare Lösungen für alle Bevölkerungsgruppen entwickeln. Nur durch eine Kombination aus technologischen Innovationen, politischen Maßnahmen und einem veränderten Konsumverhalten kann der Klimawandel langfristig wirksam eingedämmt werden. Die Verantwortung dafür liegt bei Regierungen, Unternehmen und jedem Einzelnen gleichermaßen.",
                    'options_pool' => ['darin', 'was', 'obwohl', 'ob', 'da', 'jedoch', 'bewusst', 'zudem', 'weil', 'daher', 'darüber', 'wobei', 'denn', 'wenn', 'sodass'],
                    'correct' => [
                        'gap_1' => 'darin',
                        'gap_2' => 'was',
                        'gap_3' => 'obwohl',
                        'gap_4' => 'ob',
                        'gap_5' => 'da',
                        'gap_6' => 'jedoch',
                        'gap_7' => 'bewusst',
                        'gap_8' => 'zudem',
                        'gap_9' => 'weil',
                        'gap_10' => 'daher',
                    ],
                    'explanation' => [
                        'gap_1' => 'darin — Darin sind sich alle einig: Pronominaladverb als Vorausverweis auf folgende Aussage.',
                        'gap_2' => 'was — Relativpronomen: bezieht sich auf den gesamten vorangegangenen Hauptsatz.',
                        'gap_3' => 'obwohl — Konzessive Konjunktion: Einräumung mit Gegenaussage im Hauptsatz.',
                        'gap_4' => 'ob — Leitet indirekten Fragesatz ein: Ja/Nein-Frage.',
                        'gap_5' => 'da — Kausal: weniger betont als „weil", häufig in der Schriftsprache.',
                        'gap_6' => 'jedoch — Adversatives Adverb: leitet Gegenposition ein.',
                        'gap_7' => 'bewusst — Bewusst auf etwas verzichten: Adverb der Absicht/Vorsätzlichkeit.',
                        'gap_8' => 'zudem — Additives Adverb: fügt weiteren Aspekt hinzu.',
                        'gap_9' => 'weil — Kausalkonjunktion mit Verbletztstellung: gibt den Grund an.',
                        'gap_10' => 'daher — Kausaladverb: logische Konsequenz aus dem Vorherigen.',
                    ],
                ],
            ],
            [
                'topic' => 'Digitalisierung im Bildungswesen',
                'content' => [
                    'format' => 'shared_pool',
                    // correct: der(1) dass(2) jedoch(3) weil(4) daher(5) die(6) obwohl(7) wie(8) was(9) zudem(10)
                    // distractors: denn wobei wenn damit sodass
                    'text' => "Die Digitalisierung verändert das Bildungswesen grundlegend – eine Entwicklung, {{gap_1}} sich kein Schulsystem entziehen kann. Moderne Technologien bieten enorme Möglichkeiten: Lernplattformen ermöglichen individuell angepasstes Lernen, digitale Lehrmittel machen Unterricht anschaulicher, und Videokonferenzen erlauben eine ortsunabhängige Wissensvermittlung. Schülerinnen und Schüler können Lerninhalte in ihrem eigenen Tempo bearbeiten und sofort Feedback zu ihren Leistungen erhalten. Experten sind überzeugt, {{gap_2}} digitale Kompetenzen in der heutigen Berufswelt unverzichtbar sind und bereits in der Grundschule gezielt gefördert werden sollten. {{gap_3}} stellt der ungleiche Zugang zu digitalen Geräten und schnellem Internet eine erhebliche Herausforderung dar, {{gap_4}} er soziale Ungleichheiten im Bildungsbereich verschärfen kann. Schulen in einkommensschwachen Regionen haben oft weder ausreichende Geräte noch stabile Internetverbindungen.\n\nEin weiteres zentrales Problem ist die Qualität der digitalen Bildungsinhalte. Nicht jede App und nicht jede Plattform leistet tatsächlich einen pädagogischen Mehrwert. Lehrkräfte müssen {{gap_5}} in der Lage sein, digitale Werkzeuge kritisch zu beurteilen und sinnvoll in den Unterricht zu integrieren. Dies erfordert eine umfassende Ausbildung und regelmäßige Fortbildungsmaßnahmen, {{gap_6}} viele Schulen bisher noch nicht ausreichend anbieten. {{gap_7}} digitale Medien den Unterricht ergänzen, sollten grundlegende Fähigkeiten wie Lesen, Schreiben und kritisches Denken nicht vernachlässigt werden. Zahlreiche Pädagogen betonen, dass Technologie niemals den persönlichen Kontakt zwischen Lehrenden und Lernenden vollständig ersetzen kann.\n\nGleichzeitig birgt die Digitalisierung Risiken {{gap_8}} Datenschutz und digitale Sicherheit. Schülerdaten werden von kommerziellen Plattformen gesammelt und ausgewertet, {{gap_9}} erhebliche ethische Fragen aufwirft. Eltern und Schulbehörden sind zunehmend besorgt darüber, welche persönlichen Informationen Technologieunternehmen über minderjährige Nutzer speichern und verwenden dürfen. Untersuchungen zeigen, dass viele Lernplattformen Nutzerdaten an Dritte weitergeben, ohne dass Eltern oder Schüler davon wissen. {{gap_10}} stehen Bildungspolitiker vor der Aufgabe, klare gesetzliche Rahmenbedingungen zu schaffen, die Datenschutz und digitale Chancen gleichermaßen sicherstellen. Die Herausforderung besteht darin, den pädagogischen Nutzen digitaler Technologien voll auszuschöpfen, ohne dabei die Privatsphäre junger Nutzerinnen und Nutzer zu gefährden.",
                    'options_pool' => ['der', 'dass', 'jedoch', 'weil', 'daher', 'die', 'obwohl', 'wie', 'was', 'zudem', 'denn', 'wobei', 'wenn', 'damit', 'sodass'],
                    'correct' => [
                        'gap_1' => 'der',
                        'gap_2' => 'dass',
                        'gap_3' => 'jedoch',
                        'gap_4' => 'weil',
                        'gap_5' => 'daher',
                        'gap_6' => 'die',
                        'gap_7' => 'obwohl',
                        'gap_8' => 'wie',
                        'gap_9' => 'was',
                        'gap_10' => 'zudem',
                    ],
                    'explanation' => [
                        'gap_1' => 'der — Relativpronomen: eine Entwicklung, der (Dativ Feminin). „sich entziehen" regiert den Dativ.',
                        'gap_2' => 'dass — Überzeugt sein, dass: Konjunktion für eingebetteten Aussagesatz.',
                        'gap_3' => 'jedoch — Adversatives Adverb: leitet Einschränkung nach positiver Aussage ein.',
                        'gap_4' => 'weil — Kausalkonjunktion: gibt den Grund für die Herausforderung an.',
                        'gap_5' => 'daher — Kausaladverb: Schlussfolgerung aus dem Vorherigen — wegen des Qualitätsproblems müssen Lehrkräfte daher...',
                        'gap_6' => 'die — Relativpronomen: Fortbildungsmaßnahmen, die (Nominativ Plural, Subjekt des Relativsatzes).',
                        'gap_7' => 'obwohl — Konzessiv: auch wenn Medien helfen, sollte Grundlegendes nicht vernachlässigt werden.',
                        'gap_8' => 'wie — Vergleichspartikel: leitet Aufzählung von Beispielen ein.',
                        'gap_9' => 'was — Relativpronomen: bezieht sich auf den gesamten vorangegangenen Hauptsatz.',
                        'gap_10' => 'zudem — Additives Adverb: führt weiteren Aspekt ein.',
                    ],
                ],
            ],
            [
                'topic' => 'Urbanisierung und Wohnungsnot',
                'content' => [
                    'format' => 'shared_pool',
                    // correct: das(1) weil(2) dass(3) bereits(4) daher(5) jedoch(6) sodass(7) zunehmend(8) damit(9) darum(10)
                    // distractors: sowohl wobei wenn obwohl was
                    'text' => "Die Urbanisierung schreitet weltweit in einem Tempo voran, {{gap_1}} Stadtplaner und Politiker vor enorme Herausforderungen stellt. Immer mehr Menschen zieht es in die Städte, {{gap_2}} dort bessere Arbeitsmöglichkeiten, Bildungsangebote und Infrastruktur vorhanden sind. Laut aktuellen Prognosen werden bis 2050 rund siebzig Prozent der Weltbevölkerung in städtischen Gebieten leben – in Entwicklungsländern wird dieser Anteil sogar noch deutlich höher ausfallen. Dies führt zwangsläufig dazu, {{gap_3}} die Nachfrage nach bezahlbarem Wohnraum in Ballungsgebieten das Angebot bei Weitem übersteigt. Besonders in Großstädten wie Berlin, München oder Hamburg ist die Wohnungsnot {{gap_4}} ein dringendes gesellschaftliches Problem geworden, das breite Bevölkerungsschichten betrifft.\n\nDie Ursachen für die Wohnungskrise sind vielschichtig. Jahrzehntelange Vernachlässigung des sozialen Wohnungsbaus, steigende Bodenpreise und Spekulation mit Immobilien haben die Lage erheblich verschärft. In einigen deutschen Großstädten hat sich der Mietpreis in den letzten zehn Jahren nahezu verdoppelt. Hinzu kommt, dass viele Neubauprojekte aufgrund langwieriger Genehmigungsverfahren und gestiegener Baukosten scheitern oder stark verzögert werden. {{gap_5}} gehen Experten davon aus, dass ohne entschlossenes politisches Handeln keine nachhaltige Verbesserung zu erwarten ist. Mögliche Lösungsansätze umfassen eine stärkere staatliche Regulierung des Mietmarktes sowie gezielte Förderung des Neubaus von Sozialwohnungen. {{gap_6}} greifen Stadtentwicklung und Wohnungspolitik tief in bestehende Eigentumsrechte und Marktmechanismen ein, {{gap_7}} politische Mehrheiten für tiefgreifende Reformen schwer zu finden sind.\n\nNeben dem Wohnungsmarkt stellt die wachsende Bevölkerungsdichte {{gap_8}} die Verkehrsinfrastruktur vor ernste Probleme. Überlastete Straßen, Lärm und schlechte Luftqualität beeinträchtigen die Lebensqualität in dichten Stadtvierteln erheblich. Der öffentliche Nahverkehr muss massiv ausgebaut werden, {{gap_9}} Staus und Luftverschmutzung langfristig reduziert werden können. Stadtplaner setzen {{gap_10}} auf das Konzept der kompakten Stadt, die kurze Wege, gemischte Nutzung und hohe Lebensqualität bei geringem Flächenverbrauch vereint. Viele Experten sind der Meinung, dass auch die gezielte Entwicklung von Mittelstädten helfen könnte, den Druck auf Großstädte zu mindern und eine ausgewogenere regionale Bevölkerungsverteilung zu fördern.",
                    'options_pool' => ['das', 'weil', 'dass', 'bereits', 'daher', 'jedoch', 'sodass', 'zunehmend', 'damit', 'darum', 'sowohl', 'wobei', 'wenn', 'obwohl', 'was'],
                    'correct' => [
                        'gap_1' => 'das',
                        'gap_2' => 'weil',
                        'gap_3' => 'dass',
                        'gap_4' => 'bereits',
                        'gap_5' => 'daher',
                        'gap_6' => 'jedoch',
                        'gap_7' => 'sodass',
                        'gap_8' => 'zunehmend',
                        'gap_9' => 'damit',
                        'gap_10' => 'darum',
                    ],
                    'explanation' => [
                        'gap_1' => 'das — Relativpronomen: ein Tempo, das (Nominativ Neutrum, Subjekt des Relativsatzes).',
                        'gap_2' => 'weil — Kausalkonjunktion mit Verbletztstellung: erklärt den Grund des Zuzugs.',
                        'gap_3' => 'dass — Dazu führen, dass: feste Konstruktion mit Objektsatz.',
                        'gap_4' => 'bereits — Zeitliches Adverb: betont, dass die Wohnungsnot schon jetzt besteht.',
                        'gap_5' => 'daher — Kausaladverb: Konsequenz aus der Vielschichtigkeit der Ursachen.',
                        'gap_6' => 'jedoch — Adversatives Adverb: leitet Einschränkung zu den Lösungsansätzen ein.',
                        'gap_7' => 'sodass — Konsekutivkonjunktion: Folge aus den beschriebenen Eingriffen.',
                        'gap_8' => 'zunehmend — Gradpartikel: beschreibt eine steigende Tendenz.',
                        'gap_9' => 'damit — Finalkonjunktion: drückt den Zweck des Ausbaus aus.',
                        'gap_10' => 'darum — Kausaladverb: Schlussfolgerung aus allen vorherigen Argumenten.',
                    ],
                ],
            ],
            [
                'topic' => 'Arbeitswelt im Wandel: Homeoffice und neue Modelle',
                'content' => [
                    'format' => 'shared_pool',
                    // correct: der(1) die(2) wobei(3) jedoch(4) sodass(5) weshalb(6) muss(7) wie(8) daher(9) daran(10)
                    // distractors: was wenn denn damit obwohl
                    'text' => "Die Arbeitswelt befindet sich in einem tiefgreifenden Wandel, {{gap_1}} durch die Digitalisierung und die Erfahrungen der Pandemie-Jahre stark beschleunigt wurde. Homeoffice, flexible Arbeitszeiten und ortsunabhängiges Arbeiten sind für viele Beschäftigte heute selbstverständlich – eine Entwicklung, {{gap_2}} noch vor einem Jahrzehnt kaum vorstellbar schien. Eine Umfrage aus dem Jahr 2023 ergab, dass mehr als sechzig Prozent der Büroangestellten mindestens zwei Tage pro Woche von zu Hause arbeiten möchten. Unternehmen, die flexiblere Arbeitsmodelle anbieten, haben laut aktuellen Studien deutliche Vorteile bei der Gewinnung qualifizierter Fachkräfte. Arbeitnehmer schätzen die bessere Vereinbarkeit von Beruf und Privatleben, {{gap_3}} Produktivität und Arbeitszufriedenheit nachweislich gesteigert werden können.\n\n{{gap_4}} bringt die neue Arbeitswelt auch erhebliche Herausforderungen mit sich. Die Grenzen zwischen Arbeit und Freizeit verschwimmen zunehmend, {{gap_5}} viele Beschäftigte Schwierigkeiten haben, nach Feierabend wirklich abzuschalten. Psychologen warnen vor einem Anstieg von Burnout und chronischen Erschöpfungszuständen, {{gap_6}} Unternehmen ihrer Fürsorgepflicht stärker nachkommen sollten. Ein weiteres Problem ist die soziale Isolation: Wer dauerhaft von zu Hause aus arbeitet, {{gap_7}} auf den informellen Austausch mit Kollegen verzichten, der für Kreativität, Innovation und Teamzusammenhalt unerlässlich ist. Regelmäßige gemeinsame Präsenztage können helfen, den Teamgeist zu stärken und das Gemeinschaftsgefühl zu fördern.\n\nAuch strukturelle Fragen sind noch nicht abschließend geklärt. {{gap_8}} sollen die Kosten für heimische Arbeitsplätze aufgeteilt werden? Wer trägt die Verantwortung für Arbeitssicherheit im Homeoffice? Darf ein Arbeitgeber Mitarbeiter verpflichten, ins Büro zu kommen, oder haben Arbeitnehmer ein Recht auf Homeoffice? In einigen Ländern, wie etwa Portugal und Spanien, wurden bereits Gesetze verabschiedet, die das Recht auf Nicht-Erreichbarkeit nach Feierabend regeln. Arbeitgeber und Gesetzgeber sind {{gap_9}} gefragt, klare und faire Regelungen zu schaffen. Gleichzeitig sollten Arbeitnehmer {{gap_10}} arbeiten, ihre eigenen Grenzen klar zu kommunizieren und eine gesunde Work-Life-Balance aktiv einzufordern. Die Zukunft der Arbeit wird letztlich davon abhängen, wie gut Flexibilität und Menschlichkeit miteinander in Einklang gebracht werden können.",
                    'options_pool' => ['der', 'was', 'die', 'jedoch', 'wobei', 'sodass', 'weshalb', 'daher', 'muss', 'wie', 'wenn', 'denn', 'damit', 'obwohl', 'daran'],
                    'correct' => [
                        'gap_1' => 'der',
                        'gap_2' => 'die',
                        'gap_3' => 'wobei',
                        'gap_4' => 'jedoch',
                        'gap_5' => 'sodass',
                        'gap_6' => 'weshalb',
                        'gap_7' => 'muss',
                        'gap_8' => 'wie',
                        'gap_9' => 'daher',
                        'gap_10' => 'daran',
                    ],
                    'explanation' => [
                        'gap_1' => 'der — Relativpronomen: ein Wandel, der (Nominativ Maskulin, Subjekt des Relativsatzes).',
                        'gap_2' => 'die — Relativpronomen: eine Entwicklung, die (Nominativ Feminin, Subjekt des Relativsatzes).',
                        'gap_3' => 'wobei — Relativadverb: drückt Begleitumstand aus.',
                        'gap_4' => 'jedoch — Adversatives Adverb: leitet Gegenperspektive nach positiver Aussage ein.',
                        'gap_5' => 'sodass — Konsekutivkonjunktion: Konsequenz aus dem Verschwimmen der Grenzen.',
                        'gap_6' => 'weshalb — Kausal-konsekutives Relativadverb: bezieht sich auf den gesamten Vordersatz.',
                        'gap_7' => 'muss — Modalverb: drückt Notwendigkeit/Unvermeidbarkeit aus.',
                        'gap_8' => 'wie — Leitet indirekten Fragesatz ein: wie sollen... aufgeteilt werden?',
                        'gap_9' => 'daher — Kausaladverb: logische Konsequenz aus den ungeklärten Fragen.',
                        'gap_10' => 'daran — Daran arbeiten, etwas zu tun: Pronominaladverb, feste Konstruktion.',
                    ],
                ],
            ],
        ];

        foreach ($tasks as $index => $task) {
            $module->questions()->create([
                'topic' => $task['topic'],
                'content' => $this->normalizeSeedContent($task['content']),
                'format' => 'shared_pool',
                'status' => 'published',
                'generation_mode' => 'manual',
                'source_label' => 'Internal Zertify seed set',
                'order' => ($index + 1) * 10,
                'is_active' => true,
                'difficulty' => 'medium',
                'points' => 1.0,
            ]);
        }
    }

    /** @param Module $module */
    private function createLesenTeil1Questions($module): void
    {
        $module->questions()->delete();

        $tasks = $this->lesenTeil1Tasks();

        $this->seedStructuredReferenceTasks(
            $module,
            $tasks,
            'reading_matching_headlines',
            true,
        );
    }

    /** @param Module $module */
    private function createLesenTeil2Questions($module): void
    {
        $module->questions()->delete();

        $tasks = $this->lesenTeil2Tasks();

        $this->seedStructuredReferenceTasks(
            $module,
            $tasks,
            'reading_article_mc',
            true,
        );
    }

    /** @param Module $module */
    private function createLesenTeil3Questions($module): void
    {
        $module->questions()->delete();

        $tasks = $this->lesenTeil3Tasks();

        $this->seedStructuredReferenceTasks(
            $module,
            $tasks,
            'reading_situations_matching',
            true,
        );
    }

    /** @param Module $module */
    private function createHoerenTeil1Questions($module): void
    {
        $this->syncListeningReferenceTasks(
            $module,
            $this->hoerenTeil1Tasks(),
            $module->slug,
            'listening_segmented_true_false',
        );
    }

    /** @param Module $module */
    private function createHoerenTeil2Questions($module): void
    {
        $this->syncListeningReferenceTasks(
            $module,
            $this->hoerenTeil2Tasks(),
            $module->slug,
            'listening_long_true_false',
        );
    }

    /** @param Module $module */
    private function createHoerenTeil3Questions($module): void
    {
        $this->syncListeningReferenceTasks(
            $module,
            $this->hoerenTeil3Tasks(),
            $module->slug,
            'listening_short_true_false',
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     */
    private function seedStructuredReferenceTasks(Module $module, array $tasks, string $format, bool $published): void
    {
        foreach ($tasks as $index => $task) {
            $content = $task['content'];
            $content['format'] = $format;
            $content['source'] = [
                'label' => $task['source_label'] ?? 'Internal Zertify seed set',
                'url' => (string) ($task['source_url'] ?? ''),
                'notes' => (string) ($task['source_notes'] ?? ''),
            ];

            $hasSeededAudioAsset = is_array($task['seed_audio_asset'] ?? null);
            $questionPublished = $published || $hasSeededAudioAsset;
            $audioAsset = $hasSeededAudioAsset
                ? $this->syncQuestionAudioAsset($task['seed_audio_asset'])
                : null;

            $module->questions()->create([
                'topic' => $task['topic'],
                'content' => $this->normalizeSeedContent($content),
                'format' => $format,
                'status' => $questionPublished ? Question::STATUS_PUBLISHED : Question::STATUS_DRAFT,
                'generation_mode' => 'manual',
                'source_label' => $task['source_label'] ?? 'Internal Zertify seed set',
                'source_url' => $task['source_url'] ?? null,
                'source_notes' => $task['source_notes'] ?? null,
                'audio_source_type' => $audioAsset !== null ? Question::AUDIO_SOURCE_ASSET : null,
                'question_audio_asset_id' => $audioAsset?->id,
                'order' => ($index + 1) * 10,
                'is_active' => $questionPublished,
                'difficulty' => 'medium',
                'points' => 1.0,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     */
    private function syncListeningReferenceTasks(Module $module, array $tasks, string $moduleSlug, string $format): void
    {
        $desiredSeedKeys = [];

        foreach ($tasks as $index => $task) {
            $order = ($index + 1) * 10;
            $seedKey = (string) ($task['seed_key'] ?? "{$moduleSlug}.reference.".($index + 1));
            $desiredSeedKeys[] = $seedKey;

            $content = $task['content'];
            $content['format'] = $format;
            $content['source'] = [
                'label' => $task['source_label'] ?? 'Internal Zertify seed set',
                'url' => (string) ($task['source_url'] ?? ''),
                'notes' => (string) ($task['source_notes'] ?? ''),
            ];

            $normalizedContent = $this->normalizeSeedContent($content);
            $transcriptHash = $this->contentTranscriptHash($normalizedContent);
            $hasSeededAudioAsset = is_array($task['seed_audio_asset'] ?? null);
            $questionPublished = $hasSeededAudioAsset;
            $audioAsset = $hasSeededAudioAsset
                ? $this->syncQuestionAudioAsset(
                    $task['seed_audio_asset'],
                    $transcriptHash,
                    ['source' => 'seed_sync', 'question_format' => $format],
                )
                : null;

            $payload = [
                'module_id' => $module->id,
                'seed_key' => $seedKey,
                'topic' => $task['topic'],
                'content' => $normalizedContent,
                'format' => $format,
                'status' => $questionPublished ? Question::STATUS_PUBLISHED : Question::STATUS_DRAFT,
                'generation_mode' => Question::GENERATION_MODE_MANUAL,
                'source_label' => $task['source_label'] ?? 'Internal Zertify seed set',
                'source_url' => $task['source_url'] ?? null,
                'source_notes' => $task['source_notes'] ?? null,
                'audio_source_type' => $audioAsset !== null ? Question::AUDIO_SOURCE_ASSET : null,
                'question_audio_asset_id' => $audioAsset?->id,
                'audio_external_url' => null,
                'order' => $order,
                'is_active' => $questionPublished,
                'difficulty' => 'medium',
                'points' => 1.0,
            ];

            $question = $this->findListeningSeedQuestion($seedKey);

            if ($question === null) {
                Question::query()->create($payload);

                continue;
            }

            $question->forceFill($payload)->save();
        }

        Question::query()
            ->where('module_id', $module->id)
            ->whereNotNull('seed_key')
            ->where('seed_key', 'like', $moduleSlug.'.reference.%')
            ->whereNotIn('seed_key', $desiredSeedKeys)
            ->delete();
    }

    /**
     * @param  array{label: string, path: string, original_name?: string, duration_seconds?: int, is_active?: bool}  $assetData
     */
    private function syncQuestionAudioAsset(array $assetData, ?string $transcriptHash = null, array $generationMetadata = []): QuestionAudioAsset
    {
        return QuestionAudioAsset::query()->updateOrCreate(
            ['path' => $assetData['path']],
            [
                'label' => $assetData['label'],
                'disk' => 'public',
                'original_name' => $assetData['original_name'] ?? basename($assetData['path']),
                'transcript_hash' => $transcriptHash,
                'generation_metadata' => $generationMetadata === [] ? null : $generationMetadata,
                'generated_at' => now(),
                'duration_seconds' => $assetData['duration_seconds'] ?? null,
                'is_active' => $assetData['is_active'] ?? true,
            ],
        );
    }

    private function findListeningSeedQuestion(string $seedKey): ?Question
    {
        return Question::query()
            ->where('seed_key', $seedKey)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function contentTranscriptHash(array $content): ?string
    {
        $transcript = trim((string) ($content['transcript'] ?? ''));

        return $transcript !== '' ? hash('sha256', $transcript) : null;
    }

    /**
     * @return array<string, string>
     */
    private function buildComprehensionExplanation(
        string $correctAnswer,
        string $reason,
        string $evidence,
        string $wrongAnswerReason,
        string $strategyHint = '',
    ): array {
        return [
            'correct_answer' => $correctAnswer,
            'reason' => $reason,
            'evidence' => $evidence,
            'wrong_answer_reason' => $wrongAnswerReason,
            'strategy_hint' => $strategyHint,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lesenTeil1Tasks(): array
    {
        return [
            [
                'topic' => 'Lesen 1: Kultur, Freizeit und Medienpraxis',
                'source_url' => null,
                'source_label' => 'Internal Zertify seed set',
                'source_notes' => 'AI-generated B2 Allgemein seed task.',
                'content' => [
                    'instructions' => 'Lesen Sie zuerst die zehn Überschriften. Lesen Sie dann die fünf Texte und entscheiden Sie, welche Überschrift am besten zu welchem Text passt.',
                    'headings' => [
                        ['id' => 'heading_a', 'label' => 'A', 'text' => 'Ehrenamt als Basis für das Sportangebot'],
                        ['id' => 'heading_b', 'label' => 'B', 'text' => 'Fotografieren lernen: Den Blick für Motive schärfen'],
                        ['id' => 'heading_c', 'label' => 'C', 'text' => 'Bibliotheken wandeln sich zum Ort der Begegnung'],
                        ['id' => 'heading_d', 'label' => 'D', 'text' => 'Verletzungsrisiken im Freizeitsport minimieren'],
                        ['id' => 'heading_e', 'label' => 'E', 'text' => 'Digitale Bilderflut macht die Archivierung zur Qual'],
                        ['id' => 'heading_f', 'label' => 'F', 'text' => 'Vereinsleben bietet mehr als nur sportliche Betätigung'],
                        ['id' => 'heading_g', 'label' => 'G', 'text' => 'Smartphone-Boom steigert Nachfrage nach Fotokursen'],
                        ['id' => 'heading_h', 'label' => 'H', 'text' => 'Öffentliche Bibliotheken modernisieren ihr digitales Angebot'],
                        ['id' => 'heading_i', 'label' => 'I', 'text' => 'Sicherheitsregeln als Voraussetzung für den Trampolinspaß'],
                        ['id' => 'heading_j', 'label' => 'J', 'text' => 'Der neue Wert alter Fotoalben im digitalen Zeitalter'],
                    ],
                    'texts' => [
                        ['id' => 'text_1', 'title' => 'Text 1', 'body' => 'Kleine Sportvereine in ländlichen Regionen stehen vor einer gewaltigen Herausforderung, die den Kern ihres Angebots bedroht. Während das Interesse an Kursen und Wettkämpfen stabil bleibt, schwindet die Zahl der Menschen, die bereit sind, unentgeltlich Verantwortung zu übernehmen. Ohne engagierte Trainer lassen sich viele Jugendmannschaften kaum noch aufrechterhalten, da die pädagogische Betreuung neben dem eigentlichen Training viel Zeit kostet. Auch die Organisation von einem traditionellen Vereinsfest, das früher als gesellschaftlicher Höhepunkt galt, scheitert immer öfter am Mangel an helfenden Händen. Viele Vorstände berichten, dass Mitglieder zwar gerne die Infrastruktur nutzen, sich aber seltener für feste Ämter verpflichten. Diese Entwicklung führt dazu, dass beliebte Sparten geschlossen werden müssen, obwohl die Nachfrage der Sportbegeisterten ungebrochen ist. Langfristig gerät so die soziale Funktion des Sports in Gefahr, da die Last der Arbeit auf immer weniger Schultern verteilt wird.'],
                        ['id' => 'text_2', 'title' => 'Text 2', 'body' => 'Die technische Qualität moderner Smartphonekameras hat in den letzten Jahren ein Niveau erreicht, das früher nur Profiausrüstungen vorbehalten war. Doch trotz hochauflösender Sensoren und intelligenter Software stellen viele Nutzer fest, dass teure Technik allein noch kein ästhetisch ansprechendes Bild garantiert. Dieser Umstand hat einen regelrechten Boom bei spezialisierten Fotoworkshops ausgelöst, in denen Amateure die Grundlagen der Bildkomposition von Grund auf erlernen. In diesen Kursen geht es weniger um technische Details der Gerätebedienung, sondern vielmehr darum, den Blick für Lichtstimmungen und Perspektiven zu schärfen. Die Teilnehmer möchten verstehen, wie sie aus einem schnellen Schnappschuss eine bewusste Aufnahme machen können, die eine Geschichte erzählt. Oft wird erst im praktischen Training deutlich, dass ein gutes Foto im Kopf des Fotografen entsteht und nicht durch die Anzahl der Megapixel. So verwandelt die allgegenwärtige Verfügbarkeit von Kameras das Hobby Fotografie von einer rein technischen Spielerei in eine kreative Ausdrucksform, für die systematisches Lernen wieder an Bedeutung gewinnt.'],
                        ['id' => 'text_3', 'title' => 'Text 3', 'body' => 'In fast jeder größeren Stadt finden sich mittlerweile moderne Trampolinhallen, die besonders bei Jugendlichen als attraktives Ziel für die Freizeitgestaltung gelten. Die Kombination aus sportlicher Betätigung und dem Reiz des Fliegens sorgt für hohe Besucherzahlen, birgt jedoch bei unsachgemäßer Nutzung ein erhebliches Verletzungsrisiko. Um Unfälle zu vermeiden, setzen die Betreiber verstärkt auf ein strenges Regelwerk und geschultes Personal, das die Sprungflächen permanent überwacht. Eine lückenlose Aufsicht ist unerlässlich, da viele Gäste ihre eigenen körperlichen Fähigkeiten überschätzen oder riskante Sprünge ohne Vorbereitung ausprobieren. Vor dem Betreten der Halle müssen Besucher oft Sicherheitseinweisungen absolvieren, in denen das richtige Verhalten auf den Tüchern erklärt wird. Nur wenn diese Vorgaben konsequent eingehalten werden, lässt sich der Betrieb wirtschaftlich und sicher führen. Trotz der lockeren Atmosphäre bleibt der Aufenthalt in der Halle an strikte Bedingungen geknüpft, da die Sicherheit der Springer oberste Priorität hat und über den langfristigen Erfolg des gesamten Konzepts entscheidet.'],
                        ['id' => 'text_4', 'title' => 'Text 4', 'body' => 'Öffentliche Bibliotheken durchlaufen einen fundamentalen Wandel, der ihr traditionelles Image als staubige Orte der Stille grundlegend verändert. Während sie früher primär als Archive für gedruckte Bücher dienten, entwickeln sie sich heute zu lebendigen Zentren der Begegnung für alle Generationen. Neben klassischen Regalen finden sich vermehrt gemütliche Cafés, moderne Lernräume für Gruppenarbeiten und Flächen für kulturelle Veranstaltungen wie eine öffentliche Lesung oder Diskussionsabende. Auch digitale Angebote spielen eine zentrale Rolle, da der Zugang zu Wissen längst nicht mehr nur über Papier erfolgt. Diese Neuausrichtung macht die Institution zu einem wichtigen sozialen Raum, in dem der Austausch zwischen Menschen ebenso wichtig ist wie die stille Lektüre. Besonders in städtischen Quartieren fungieren die Häuser als kostenlose Treffpunkte, die den gesellschaftlichen Zusammenhalt fördern. Damit reagieren die Bibliotheken auf die Bedürfnisse einer vernetzten Gesellschaft, die Orte sucht, an denen Bildung und soziale Kontakte barrierefrei miteinander verknüpft werden können.'],
                        ['id' => 'text_5', 'title' => 'Text 5', 'body' => 'Dank der digitalen Fotografie werden heute in wenigen Sekunden mehr Aufnahmen gemacht als früher in einem ganzen Jahr. Die ständige Verfügbarkeit von Smartphonekameras führt dazu, dass fast jeder Moment des Alltags festgehalten wird, was zu einer unüberschaubaren Menge an Dateien führt. Diese enorme Bilderflut stellt viele Nutzer vor das Problem, die wirklich bedeutsamen Aufnahmen in den digitalen Speichern wiederzufinden. Während ein physisches Fotoalbum durch seine begrenzte Kapazität zur Auswahl zwang, verleitet der unbegrenzte Speicherplatz dazu, alles wahllos aufzubewahren. Dadurch verlieren einzelne Bilder oft ihren besonderen Wert, da sie in der Masse der belanglosen Schnappschüsse untergehen. Die Herausforderung besteht heute weniger im Festhalten der Erinnerung, sondern vielmehr in der mühsamen Sichtung und Archivierung der Datenbestände. Wer seine persönlichen Erlebnisse langfristig bewahren möchte, muss lernen, regelmäßig zu löschen und sich auf die wesentlichen Motive zu konzentrieren, um die Übersicht über das eigene digitale Leben nicht vollständig zu verlieren.'],
                    ],
                    'correct' => [
                        'text_1' => 'heading_a',
                        'text_2' => 'heading_g',
                        'text_3' => 'heading_i',
                        'text_4' => 'heading_c',
                        'text_5' => 'heading_e',
                    ],
                    'explanation' => [
                        'text_1' => $this->buildComprehensionExplanation('heading_a', 'Abhängigkeit des Vereinsangebots vom ehrenamtlichen Engagement.', 'Die Überschrift bringt auf den Punkt, dass das sportliche Angebot ohne freiwillige Helfer und Trainer nicht aufrechterhalten werden kann.', 'Überschrift F ist zwar allgemein wahr, verfehlt aber den Schwerpunkt des Textes, der die Problematik des Helfermangels betont.'),
                        'text_2' => $this->buildComprehensionExplanation('heading_g', 'Zusammenhang zwischen Technikverbreitung und dem Wunsch nach Weiterbildung.', 'Der Text erklärt, warum die ständige Verfügbarkeit von Kameras zu einem neuen Bedarf an professionellen Gestaltungskursen führt.', 'Überschrift B beschreibt lediglich einen inhaltlichen Teilaspekt der Kurse, erfasst aber nicht die Ursache für das gestiegene Interesse.'),
                        'text_3' => $this->buildComprehensionExplanation('heading_i', 'Notwendigkeit strenger Regeln für einen sicheren Betrieb von Trampolinhallen.', 'Die Überschrift verknüpft das Freizeitvergnügen direkt mit den erforderlichen Sicherheitsvorkehrungen, die im Text zentral sind.', 'Überschrift D ist zu weit gefasst und bezieht sich auf den gesamten Freizeitsport, während der Text spezifisch Trampolinhallen behandelt.'),
                        'text_4' => $this->buildComprehensionExplanation('heading_c', 'Transformation der Bibliothek vom Archiv zum sozialen Treffpunkt.', 'Die Überschrift spiegelt den im Text beschriebenen Wandel hin zu einem Ort für Austausch und Begegnung wider.', 'Die Digitalisierung (Überschrift H) wird im Text zwar erwähnt, ist aber nur ein Baustein der Neuausrichtung und nicht das Hauptthema.'),
                        'text_5' => $this->buildComprehensionExplanation('heading_e', 'Probleme bei der Verwaltung und Auswahl großer digitaler Bildmengen.', 'Die Überschrift fasst die im Text beschriebene Überforderung durch die Masse an Dateien und die Schwierigkeit der Archivierung zusammen.', 'Der Text warnt vor dem Verlust der Übersicht in der Gegenwart, statt eine Wertsteigerung alter Alben (Überschrift J) zu thematisieren.'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lesenTeil2Tasks(): array
    {
        return [
            [
                'topic' => 'Lesen 2: Familienleben zwischen Nähe und Konflikt',
                'source_url' => null,
                'source_label' => 'Internal Zertify seed set',
                'source_notes' => 'AI-generated B2 Allgemein seed task.',
                'content' => [
                    'instructions' => 'Lesen Sie den Text und die Aufgaben 6 bis 10. Entscheiden Sie, welche Lösung (a, b oder c) richtig ist.',
                    'article' => [
                        'title' => 'Mehrgenerationenhäuser: Eine alte Idee neu entdeckt',
                        'body' => "Das Zusammenleben mehrerer Generationen unter einem Dach war über Jahrhunderte hinweg die Norm in vielen Gesellschaften. Großfamilien lebten gemeinsam, unterstützten sich gegenseitig im Alltag und teilten Ressourcen. Diese traditionelle Wohnform geriet im Zuge der Industrialisierung und Urbanisierung in den Hintergrund. Die Kernfamilie wurde zum vorherrschenden Modell, während ältere Menschen zunehmend isoliert lebten oder in Pflegeeinrichtungen zogen. Doch in den letzten Jahren ist ein bemerkenswerter Wandel zu beobachten, der die Vorteile des Mehrgenerationenwohnens wieder in den Fokus rückt.\n\nEin wesentlicher Treiber dieser Entwicklung ist die demografische Veränderung. Mit einer immer älter werdenden Gesellschaft und gleichzeitig steigenden Kosten für Pflege und Wohnraum suchen viele Familien nach alternativen Lösungen. Mehrgenerationenhäuser bieten hier eine attraktive Perspektive: Sie ermöglichen es, Pflegeaufgaben innerhalb der Familie zu verteilen und gleichzeitig die finanzielle Belastung zu mindern. Eine aktuelle Beobachtung zeigt, dass nicht nur Großeltern und Enkel profitieren, sondern auch die mittlere Generation Entlastung erfährt, indem sie zum Beispiel bei der Kindererziehung Unterstützung erhält oder im Alter selbst versorgt wird.\n\nAllerdings sind Mehrgenerationenprojekte nicht immer nur von Harmonie geprägt. Das Zusammenführen unterschiedlicher Lebensstile und Erwartungen kann zu Reibungen führen, besonders wenn klare Absprachen fehlen. Jüngere Generationen schätzen oft ihre Unabhängigkeit und Privatsphäre, während ältere Generationen möglicherweise mehr Wert auf gemeinsame Aktivitäten legen. Solche Konflikte zeigen sich häufig im Alltag, etwa bei der Nutzung gemeinsamer Räume oder der Aufteilung von Verantwortlichkeiten.\n\nIn vielen erfolgreichen Mehrgenerationenhaushalten hat sich die Einrichtung eines regelmäßigen „Familienrats” bewährt. Bei diesen Treffen werden nicht nur praktische Fragen des Zusammenlebens besprochen, sondern auch persönliche Anliegen und eventuelle Spannungen offen angesprochen. Die Erfahrung zeigt, dass die Möglichkeit, Meinungsverschiedenheiten in einem strukturierten Rahmen zu klären, die Bindung stärkt und Missverständnisse verhindert.\n\nDie Vorteile des Mehrgenerationenwohnens reichen über die rein praktische Unterstützung hinaus. Studien belegen, dass Kinder, die in einem Mehrgenerationenhaushalt aufwachsen, oft ein stärkeres soziales Empfinden entwickeln. Für Senioren bedeutet das Leben im Kreise der Familie eine Verringerung des Gefühls der Einsamkeit. Diese positiven Effekte treten jedoch nur dann vollständig ein, wenn das Zusammenleben auf Freiwilligkeit basiert und die individuellen Bedürfnisse jedes Einzelnen respektiert werden. Ein Mehrgenerationenhaus ist kein Allheilmittel, sondern eine Wohnform, die bewusste Gestaltung erfordert.\n\nEs zeigt sich, dass ein erfolgreiches Mehrgenerationenprojekt nicht primär von der Größe des Hauses oder dem Reichtum der Familie abhängt, sondern vielmehr von der Bereitschaft der Bewohner, sich aufeinander einzulassen und gemeinsame Lösungen zu finden. Die Zukunft des Familienlebens könnte somit in einer modernen Interpretation alter Traditionen liegen, die sowohl Unabhängigkeit als auch Verbundenheit ermöglicht.",
                    ],
                    'questions' => [
                        ['id' => 'question_1', 'prompt' => 'Welchen Vorteil bietet das Mehrgenerationenwohnen der mittleren Generation?', 'options' => [['id' => 'q1_a', 'label' => 'a', 'text' => 'Sie kann ihre Unabhängigkeit besser wahren, da die Großeltern die Hauptverantwortung tragen.'], ['id' => 'q1_b', 'label' => 'b', 'text' => 'Sie wird entlastet, weil sie Hilfe bei der Kindererziehung oder der Versorgung im Alter bekommt.'], ['id' => 'q1_c', 'label' => 'c', 'text' => 'Sie profitiert finanziell durch die Übernahme aller Pflegekosten durch die ältere Generation.']]],
                        ['id' => 'question_2', 'prompt' => 'Wodurch können im Mehrgenerationenhaus Reibungen entstehen?', 'options' => [['id' => 'q2_a', 'label' => 'a', 'text' => 'Durch die Notwendigkeit, Kompromisse einzugehen und persönliche Anliegen anzusprechen.'], ['id' => 'q2_b', 'label' => 'b', 'text' => 'Durch die Aufteilung von Verantwortlichkeiten im Alltag.'], ['id' => 'q2_c', 'label' => 'c', 'text' => 'Durch das Zusammentreffen verschiedener Lebensstile und mangelnde Absprachen.']]],
                        ['id' => 'question_3', 'prompt' => 'Was ist eine Folge der Einrichtung eines regelmäßigen Familienrats?', 'options' => [['id' => 'q3_a', 'label' => 'a', 'text' => 'Die familiäre Bindung wird gestärkt und Missverständnisse lassen sich vermeiden.'], ['id' => 'q3_b', 'label' => 'b', 'text' => 'Die Verteilung von Pflegeaufgaben innerhalb der Familie wird effizienter.'], ['id' => 'q3_c', 'label' => 'c', 'text' => 'Alle Generationen entwickeln ein stärkeres soziales Empfinden.']]],
                        ['id' => 'question_4', 'prompt' => 'Unter welcher Voraussetzung entfalten sich die positiven Effekte des Mehrgenerationenwohnens vollständig?', 'options' => [['id' => 'q4_a', 'label' => 'a', 'text' => 'Wenn die Bewohner bereit sind, Kompromisse einzugehen und offen über ihre Bedürfnisse sprechen.'], ['id' => 'q4_b', 'label' => 'b', 'text' => 'Wenn ein regelmäßiger Familienrat eingerichtet wird, um Konflikte zu klären.'], ['id' => 'q4_c', 'label' => 'c', 'text' => 'Wenn es auf Freiwilligkeit beruht und die individuellen Bedürfnisse aller Bewohner respektiert werden.']]],
                        ['id' => 'question_5', 'prompt' => 'Wovon hängt der Erfolg eines Mehrgenerationenprojekts maßgeblich ab?', 'options' => [['id' => 'q5_a', 'label' => 'a', 'text' => 'Von der Bereitschaft der Bewohner, sich aufeinander einzulassen und gemeinsame Lösungen zu finden.'], ['id' => 'q5_b', 'label' => 'b', 'text' => 'Von der klaren Festlegung fester Regeln für die Nutzung von Gemeinschaftsflächen.'], ['id' => 'q5_c', 'label' => 'c', 'text' => 'Von der Möglichkeit, Meinungsverschiedenheiten in einem strukturierten Rahmen zu klären.']]],
                    ],
                    'correct' => [
                        'question_1' => 'q1_b',
                        'question_2' => 'q2_c',
                        'question_3' => 'q3_a',
                        'question_4' => 'q4_c',
                        'question_5' => 'q5_a',
                    ],
                    'explanation' => [
                        'question_1' => $this->buildComprehensionExplanation('q1_b', 'Die mittlere Generation wird entlastet durch Unterstützung bei Kindererziehung oder Versorgung im Alter.', 'Der Text nennt: die mittlere Generation erfährt Entlastung durch Kindererziehungshilfe und Altersversorgung.', 'Option c ist eine Übertreibung: Der Text erwähnt, dass Kosten gemindert werden, nicht dass die ältere Generation alle Pflegekosten übernimmt.'),
                        'question_2' => $this->buildComprehensionExplanation('q2_c', 'Reibungen entstehen durch unterschiedliche Lebensstile und fehlende klare Absprachen.', 'Das Zusammenführen unterschiedlicher Lebensstile und Erwartungen kann zu Reibungen führen, besonders wenn klare Absprachen fehlen.', 'Option b nennt einen Bereich, in dem sich Konflikte zeigen können, aber nicht die eigentliche Ursache dafür.'),
                        'question_3' => $this->buildComprehensionExplanation('q3_a', 'Die Bindung der Familienmitglieder wird gestärkt und Missverständnisse werden verhindert.', 'Die Möglichkeit, Meinungsverschiedenheiten in einem strukturierten Rahmen zu klären, stärkt die Bindung und verhindert Missverständnisse.', 'Option b nennt einen allgemeinen Vorteil des Mehrgenerationenwohnens, nicht die direkte Folge des Familienrats.'),
                        'question_4' => $this->buildComprehensionExplanation('q4_c', 'Die positiven Effekte treten vollständig ein, wenn das Zusammenleben auf Freiwilligkeit basiert und individuelle Bedürfnisse respektiert werden.', 'Diese positiven Effekte treten nur dann vollständig ein, wenn das Zusammenleben auf Freiwilligkeit basiert und die individuellen Bedürfnisse jedes Einzelnen respektiert werden.', 'Option a nennt Bedingungen für ein funktionierendes Zusammenleben, aber nicht die genaue Voraussetzung für das vollständige Eintreten der positiven Effekte.'),
                        'question_5' => $this->buildComprehensionExplanation('q5_a', 'Ein erfolgreiches Mehrgenerationenprojekt hängt von der Bereitschaft der Bewohner ab, sich aufeinander einzulassen und gemeinsame Lösungen zu finden.', 'Ein erfolgreiches Mehrgenerationenprojekt hängt nicht primär von der Größe des Hauses ab, sondern von der Bereitschaft der Bewohner, sich aufeinander einzulassen und gemeinsame Lösungen zu finden.', 'Option b nennt eine Konsequenz der Bereitschaft, aber nicht die maßgebliche Voraussetzung selbst.'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lesenTeil3Tasks(): array
    {
        return [
            [
                'topic' => 'Lesen 3: Mobilität, Stadtangebote und Zugang',
                'source_url' => null,
                'source_label' => 'Internal Zertify seed set',
                'source_notes' => 'AI-generated B2 Allgemein seed task.',
                'content' => [
                    'instructions' => 'Lesen Sie die Situationen 1 bis 10 und die Anzeigen a bis l. Welche Anzeige passt zu welcher Situation? Für zwei Situationen gibt es keine passende Anzeige. Markieren Sie dann x.',
                    'situations' => [
                        ['id' => 'situation_1', 'number' => 1, 'text' => 'Sie möchten für eine Woche ein E-Lastenfahrrad mieten, um größere Einkäufe und Ihre Kinder zu transportieren. Die Abholung sollte zentral in der Innenstadt möglich sein.'],
                        ['id' => 'situation_2', 'number' => 2, 'text' => 'Als Rollstuhlfahrer suchen Sie eine barrierefreie Möglichkeit, um vom Hauptbahnhof direkt zum Botanischen Garten zu gelangen.'],
                        ['id' => 'situation_3', 'number' => 3, 'text' => 'Sie suchen eine flexible Mitfahrgelegenheit für regelmäßige Fahrten zwischen zwei Stadtteilen, idealerweise ohne feste Zeiten oder Vorab-Buchung.'],
                        ['id' => 'situation_4', 'number' => 4, 'text' => 'Ihre Familie (zwei Erwachsene, zwei Kinder unter 12) möchte einen Tag lang das Stadtzentrum erkunden und dabei öffentliche Verkehrsmittel nutzen. Sie suchen ein günstiges Tagesticket.'],
                        ['id' => 'situation_5', 'number' => 5, 'text' => 'Sie möchten Ihr eigenes Fahrrad sicher und kostengünstig am Hauptbahnhof für mehrere Tage parken, da Sie verreisen.'],
                        ['id' => 'situation_6', 'number' => 6, 'text' => 'Sie sind neu in der Stadt und möchten sich über die verschiedenen Sharing-Angebote für E-Scooter und Fahrräder informieren, bevor Sie sich anmelden.'],
                        ['id' => 'situation_7', 'number' => 7, 'text' => 'Sie benötigen einen Transportservice, der größere Möbelstücke innerhalb der Stadt liefern kann und auch beim Tragen hilft.'],
                        ['id' => 'situation_8', 'number' => 8, 'text' => 'Sie suchen einen Fahrdienst für ältere Menschen, der auch medizinische Termine außerhalb der Stadt abdeckt.'],
                        ['id' => 'situation_9', 'number' => 9, 'text' => 'Als Tourist möchten Sie eine geführte Stadtrundfahrt mit dem Bus erleben, die die wichtigsten Sehenswürdigkeiten der Innenstadt abdeckt.'],
                        ['id' => 'situation_10', 'number' => 10, 'text' => 'Sie planen einen Wochenendausflug mit Freunden und möchten ein größeres Fahrzeug mieten, das Platz für 7 Personen bietet.'],
                    ],
                    'texts' => [
                        ['id' => 'text_a', 'label' => 'A', 'title' => 'Familien- und Lastenradverleih \'Rad & Roll\'', 'body' => 'Mieten Sie unsere modernen E-Lastenfahrräder für flexible Zeiträume – stundenweise, täglich oder wochenweise. Perfekt für den Transport von Einkäufen, Kindern oder kleineren Möbeln. Unsere zentrale Station am Marktplatz ist täglich von 9-18 Uhr geöffnet. Reservierung online oder direkt vor Ort. Kindersitze sind inklusive.'],
                        ['id' => 'text_b', 'label' => 'B', 'title' => 'Barrierefreier Nahverkehr – Ihr Weg durch die Stadt', 'body' => 'Unsere speziell ausgestatteten Niederflurbusse und rollstuhlgerechten Straßenbahnen bringen Sie bequem zu allen wichtigen Zielen. Eine direkte Linie (Linie 7) verbindet den Hauptbahnhof mit dem Botanischen Garten und dem Stadtpark. Informationen zu barrierefreien Haltestellen finden Sie auf unserer Webseite.'],
                        ['id' => 'text_c', 'label' => 'C', 'title' => 'City-Shuttle: Der flexible Fahrdienst', 'body' => 'Unser City-Shuttle bietet Fahrdienste innerhalb des Stadtgebiets an. Buchen Sie Ihre Fahrt bequem per App oder Telefon. Ideal für Einkaufsfahrten, Arztbesuche oder den Weg zur Arbeit. Nur nach Vorab-Buchung. Nicht für regelmäßige Pendlerfahrten ohne feste Route konzipiert.'],
                        ['id' => 'text_d', 'label' => 'D', 'title' => 'Familienticket \'Stadtabenteuer\'', 'body' => 'Erkunden Sie unsere Stadt einen ganzen Tag lang mit dem \'Stadtabenteuer\'-Ticket! Gültig für bis zu fünf Personen (maximal zwei Erwachsene, drei Kinder unter 15 Jahren) in allen Zonen des Stadtgebiets. Preis: 15,00 Euro. Erhältlich an allen Automaten und Verkaufsstellen.'],
                        ['id' => 'text_e', 'label' => 'E', 'title' => 'Sicher parken am Hauptbahnhof – Die Fahrradgarage', 'body' => 'Unsere moderne Fahrradgarage am Hauptbahnhof bietet sichere Stellplätze für Ihr Fahrrad. Kurzzeit- und Langzeitparken möglich. Tageskarte 1,50 Euro, Wochenkarte 7,00 Euro. Videoüberwacht und wettergeschützt. Ideal für Pendler und Reisende, die ihr Fahrrad sicher abstellen möchten. Zugang 24/7 mit Ihrer Kundenkarte.'],
                        ['id' => 'text_f', 'label' => 'F', 'title' => 'Möbel-Taxi: Ihr Umzugshelfer in der Stadt', 'body' => 'Brauchen Sie Hilfe beim Transport großer oder sperriger Gegenstände? Unser Möbel-Taxi liefert innerhalb des Stadtgebiets. Wir helfen Ihnen nicht nur beim Transport, sondern auch beim Ein- und Ausladen. Buchen Sie unseren Service stundenweise. Ideal für Umzüge, Möbelkäufe oder Entrümpelungen.'],
                        ['id' => 'text_g', 'label' => 'G', 'title' => 'Stadtführungen mit \'Panorama Express\'', 'body' => 'Entdecken Sie die Schönheit unserer Stadt mit unseren komfortablen Panorama-Bussen. Unsere erfahrenen Guides zeigen Ihnen die historischen Höhepunkte und versteckten Gassen im Zentrum. Die Tour dauert 2 Stunden und endet am Ausgangspunkt. Ideal für Erstbesucher.'],
                        ['id' => 'text_h', 'label' => 'H', 'title' => 'Fahrrad- und E-Scooter-Sharing: Die Anbieter im Vergleich', 'body' => 'Dieser Online-Ratgeber bietet Ihnen einen umfassenden Überblick über alle in unserer Stadt verfügbaren Sharing-Angebote für Fahrräder und E-Scooter. Erfahren Sie mehr über Registrierung, Kostenmodelle und Nutzungsbedingungen der verschiedenen Anbieter. Ideal für alle, die sich vor der ersten Fahrt informieren möchten.'],
                        ['id' => 'text_i', 'label' => 'I', 'title' => 'Senioren-Fahrdienst \'Mobil im Alter\'', 'body' => 'Unser Fahrdienst ist speziell auf die Bedürfnisse älterer Menschen zugeschnitten. Wir bieten Fahrten zu Arztterminen, Einkaufsmöglichkeiten und Freizeitaktivitäten – ausschließlich innerhalb der Stadtgrenzen. Unsere Fahrer sind geschult und hilfsbereit.'],
                        ['id' => 'text_j', 'label' => 'J', 'title' => 'Großraum-Vans für Gruppenreisen', 'body' => 'Sie planen einen Ausflug mit Freunden oder Familie? Mieten Sie unsere komfortablen Großraum-Vans mit Platz für 7 bis 9 Personen. Ideal für Wochenendtrips, Vereinsfahrten oder längere Reisen. Flexible Mietdauer und günstige Tarife.'],
                        ['id' => 'text_k', 'label' => 'K', 'title' => 'Intercity Busreisen – Entdecken Sie die Region', 'body' => 'Unsere Buslinien verbinden die Stadt mit den umliegenden Gemeinden und ländlichen Regionen. Perfekt für Tagesausflüge oder Pendler, die außerhalb der Stadt leben. Wir bieten keine geführten Touren an, sondern regulären Linienverkehr.'],
                        ['id' => 'text_l', 'label' => 'L', 'title' => 'Fahrradreparatur-Werkstatt \'Pedal Power\'', 'body' => 'Ihr Fahrrad braucht eine Reparatur oder Wartung? Bei \'Pedal Power\' sind Sie richtig! Wir reparieren alle Arten von Fahrrädern, von Citybikes bis E-Bikes. Schneller Service und faire Preise. Kein Fahrradverleih.'],
                    ],
                    'extra_answer' => ['id' => 'x', 'label' => 'X', 'text' => 'Keine passende Information'],
                    'correct' => [
                        'situation_1' => 'text_a',
                        'situation_2' => 'text_b',
                        'situation_3' => 'x',
                        'situation_4' => 'text_d',
                        'situation_5' => 'text_e',
                        'situation_6' => 'text_h',
                        'situation_7' => 'text_f',
                        'situation_8' => 'x',
                        'situation_9' => 'text_g',
                        'situation_10' => 'text_j',
                    ],
                    'explanation' => [
                        'situation_1' => $this->buildComprehensionExplanation('text_a', 'Text A bietet E-Lastenfahrräder zum wochenweisen Verleih an, geeignet für Transport von Kindern und Einkäufen.', 'Die zentrale Station am Marktplatz erfüllt die Anforderung der zentralen Abholung.', 'Text L ist eine Fahrradreparaturwerkstatt und bietet keinen Verleih von Fahrrädern an.'),
                        'situation_2' => $this->buildComprehensionExplanation('text_b', 'Text B beschreibt barrierefreie öffentliche Verkehrsmittel mit einer direkten Linie vom Hauptbahnhof zum Botanischen Garten.', 'Niederflurbusse und rollstuhlgerechte Straßenbahnen werden explizit genannt.', 'Text C bietet einen Fahrdienst an, erwähnt aber keine spezielle Barrierefreiheit für Rollstuhlfahrer.'),
                        'situation_3' => $this->buildComprehensionExplanation('x', 'Kein Text bietet eine flexible Mitfahrgelegenheit für regelmäßige Fahrten ohne feste Zeiten oder Vorab-Buchung.', 'Text C erfordert eine Vorab-Buchung und ist nicht für regelmäßige Pendlerfahrten ohne feste Route konzipiert.', 'Alle anderen Fahrangebote haben ähnliche Einschränkungen.'),
                        'situation_4' => $this->buildComprehensionExplanation('text_d', 'Text D bietet ein Tagesticket für bis zu fünf Personen (max. zwei Erwachsene, drei Kinder unter 15) zu einem günstigen Preis.', 'Das Ticket gilt einen ganzen Tag in allen Zonen des Stadtgebiets.', 'Text B beschreibt den barrierefreien Nahverkehr, bietet aber kein spezifisches Familientagesticket an.'),
                        'situation_5' => $this->buildComprehensionExplanation('text_e', 'Text E bietet eine sichere, videoüberwachte Fahrradgarage am Hauptbahnhof mit Wochenkarte für Langzeitparken.', 'Der günstige Preis von 7,00 Euro pro Woche und 24/7-Zugang erfüllen die Anforderungen.', 'Text A ist ein Fahrradverleih, kein Parkplatz für das eigene Fahrrad.'),
                        'situation_6' => $this->buildComprehensionExplanation('text_h', 'Text H ist ein Online-Ratgeber mit Überblick über alle Sharing-Angebote für Fahrräder und E-Scooter vor der Anmeldung.', 'Registrierung, Kostenmodelle und Nutzungsbedingungen verschiedener Anbieter werden beschrieben.', 'Text A ist ein Fahrradverleih, kein Informationsportal über verschiedene Sharing-Anbieter.'),
                        'situation_7' => $this->buildComprehensionExplanation('text_f', 'Text F bietet einen Möbel-Taxi-Service für große und sperrige Gegenstände innerhalb des Stadtgebiets an.', 'Inklusive Hilfe beim Ein- und Ausladen (Tragehilfe) wird ausdrücklich erwähnt.', 'Text A ist ein Lastenradverleih, der keine Tragehilfe anbietet und für größere Möbel nicht ausreicht.'),
                        'situation_8' => $this->buildComprehensionExplanation('x', 'Kein Text bietet einen Fahrdienst für ältere Menschen an, der medizinische Termine außerhalb der Stadt abdeckt.', 'Text I bietet einen Senioren-Fahrdienst für Arzttermine an, jedoch ausschließlich innerhalb der Stadtgrenzen.', 'Die Bedingung „außerhalb der Stadt” erfüllt keiner der Texte.'),
                        'situation_9' => $this->buildComprehensionExplanation('text_g', 'Text G bietet geführte Stadtrundfahrten mit Panorama-Bussen an, die historische Höhepunkte im Zentrum zeigen.', 'Ideal für Erstbesucher und Touristen, die die wichtigsten Sehenswürdigkeiten sehen möchten.', 'Text K bietet regulären Linienverkehr, aber keine geführten Touren.'),
                        'situation_10' => $this->buildComprehensionExplanation('text_j', 'Text J bietet Großraum-Vans mit Platz für 7 bis 9 Personen an, ideal für Wochenendtrips.', 'Flexible Mietdauer und günstige Tarife passen zur Anforderung.', 'Text A vermietet E-Lastenfahrräder, die nicht für 7 Personen geeignet sind.'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hoerenTeil1Tasks(): array
    {
        return [
            [
                'topic' => 'Hören 1: Nachrichten am Mittag',
                'source_url' => null,
                'source_label' => 'Internal Zertify seed set',
                'source_notes' => 'AI-generated B2 Allgemein seed task.',
                'content' => [
                    'instructions' => 'Sie hören nun eine Nachrichtensendung. Dazu sollen Sie fünf Aufgaben lösen. Sie hören die Nachrichtensendung nur einmal. Entscheiden Sie beim Hören, ob die Aussagen richtig oder falsch sind.',
                    'audio' => [
                        'title' => 'Nachrichten am Mittag',
                        'audio_notes' => 'Professioneller Nachrichtensprecher, klares Sprechtempo, sechs Meldungen, fünf davon werden geprüft.',
                    ],
                    'intro' => [
                        'text' => 'Hier sind die Nachrichten.',
                        'voice_profile' => 'anchor_main',
                    ],
                    'segments' => [
                        [
                            'id' => 'segment_1',
                            'number' => 1,
                            'voice_profile' => 'news_main',
                            'segment_text' => 'Der Deutsche Wetterdienst hat eine offizielle Unwetterwarnung für die gesamte Nordseeküste herausgegeben. Ab heute Abend ist mit schweren Sturmböen der Stärke neun bis zehn zu rechnen. Besonders betroffen sind die ostfriesischen Inseln sowie die Küstengebiete in Schleswig-Holstein. Experten erwarten den Beginn des Unwetters gegen achtzehn Uhr. Anwohner werden gebeten, lose Gegenstände zu sichern und den Aufenthalt im Freien während der Nachtstunden nach Möglichkeit zu vermeiden.',
                            'statement_id' => 'statement_1',
                            'statement_text' => 'Die ersten Sturmböen an der Küste werden für die Abendstunden des heutigen Tages erwartet.',
                            'correct_answer' => 'true',
                            'reason' => 'Die Experten rechnen mit dem Beginn des Unwetters gegen achtzehn Uhr, also am Abend.',
                            'evidence' => 'Experten erwarten den Beginn des Unwetters gegen achtzehn Uhr.',
                            'wrong_answer_reason' => '',
                            'strategy_hint' => '',
                        ],
                        [
                            'id' => 'segment_2',
                            'number' => 2,
                            'voice_profile' => 'news_main',
                            'segment_text' => 'In Berlin verschiebt sich der Auftakt der internationalen Theaterwochen kurzfristig. Entgegen der ursprünglichen Planung wird das Festival nicht wie vorgesehen am Freitagabend eröffnet. Die Veranstalter teilten heute mit, dass technische Probleme beim Bühnenaufbau im Hauptgebäude eine Verzögerung verursachen. Daher beginnen die ersten Vorstellungen nun erst am kommenden Samstag. Insgesamt werden über dreißig Ensembles aus fünfzehn verschiedenen Ländern erwartet.',
                            'statement_id' => 'statement_2',
                            'statement_text' => 'Das internationale Theaterfestival in Berlin wird wie ursprünglich geplant am Freitagabend eröffnet.',
                            'correct_answer' => 'false',
                            'reason' => 'Wegen technischer Probleme verschiebt sich der Auftakt von Freitag auf Samstag.',
                            'evidence' => 'Entgegen der ursprünglichen Planung wird das Festival nicht wie vorgesehen am Freitagabend eröffnet.',
                            'wrong_answer_reason' => '',
                            'strategy_hint' => '',
                        ],
                        [
                            'id' => 'segment_3',
                            'number' => 3,
                            'voice_profile' => 'news_main',
                            'segment_text' => 'Das Bildungsministerium hat heute ein neues Förderprogramm zur Digitalisierung an Grundschulen angekündigt. Insgesamt werden landesweit rund 15 Millionen Euro zusätzlich bereitgestellt, um die technische Ausstattung in den Klassenzimmern zu verbessern. Mit diesen Geldern sollen vor allem moderne Tablets für die Schülerinnen und Schüler angeschafft werden. Die Auszahlung der Mittel soll bereits zum Start des nächsten Schuljahres beginnen.',
                            'statement_id' => 'statement_3',
                            'statement_text' => 'Das Bildungsministerium stellt finanzielle Mittel für den Kauf von Tablets an Grundschulen zur Verfügung.',
                            'correct_answer' => 'true',
                            'reason' => 'Es werden 15 Millionen Euro zusätzlich bereitgestellt, um Tablets für Schüler anzuschaffen.',
                            'evidence' => 'Mit diesen Geldern sollen vor allem moderne Tablets für die Schülerinnen und Schüler angeschafft werden.',
                            'wrong_answer_reason' => '',
                            'strategy_hint' => '',
                        ],
                        [
                            'id' => 'segment_4',
                            'number' => 4,
                            'voice_profile' => 'news_main',
                            'segment_text' => 'Die Landeshauptstadt Hannover führt ab dem kommenden Montag eine neue Bürger-Service-App ein, um Behördengänge zu digitalisieren. Über die Anwendung können Termine im Bürgeramt reserviert und Dokumente online beantragt werden. Die Stadtverwaltung weist jedoch ausdrücklich darauf hin, dass die Nutzung ausschließlich Personen mit gemeldetem Erstwohnsitz im Stadtgebiet vorbehalten bleibt. Pendler aus dem Umland können den digitalen Dienst vorerst nicht in Anspruch nehmen.',
                            'statement_id' => 'statement_4',
                            'statement_text' => 'Die neue Bürger-Service-App der Stadt Hannover kann auch von Personen genutzt werden, die außerhalb des Stadtgebiets wohnen.',
                            'correct_answer' => 'false',
                            'reason' => 'Die Nutzung bleibt ausdrücklich Personen mit Erstwohnsitz im Stadtgebiet vorbehalten; Pendler aus dem Umland sind ausgeschlossen.',
                            'evidence' => 'die Nutzung ausschließlich Personen mit gemeldetem Erstwohnsitz im Stadtgebiet vorbehalten bleibt.',
                            'wrong_answer_reason' => '',
                            'strategy_hint' => '',
                        ],
                        [
                            'id' => 'segment_5',
                            'number' => 5,
                            'voice_profile' => 'news_main',
                            'segment_text' => 'Wissenschaftler der Universität Bremen haben in Zusammenarbeit mit der europäischen Weltraumorganisation ESA bahnbrechende Daten veröffentlicht. Durch die Analyse von Radarmessungen konnten in einer Tiefe von etwa zwei Kilometern unter der Marsoberfläche riesige Mengen an gefrorenem Wasser nachgewiesen werden. Diese Entdeckung in den tiefen Gesteinsschichten liefert neue Erkenntnisse über die geologische Geschichte des Roten Planeten.',
                            'statement_id' => 'statement_5',
                            'statement_text' => 'In tieferen Schichten des Planeten Mars wurde laut neuesten Untersuchungen gefrorenes Wasser entdeckt.',
                            'correct_answer' => 'true',
                            'reason' => 'Radarmessungen haben in zwei Kilometern Tiefe unter der Oberfläche gefrorenes Wasser nachgewiesen.',
                            'evidence' => 'in einer Tiefe von etwa zwei Kilometern unter der Marsoberfläche riesige Mengen an gefrorenem Wasser nachgewiesen werden.',
                            'wrong_answer_reason' => '',
                            'strategy_hint' => '',
                        ],
                    ],
                    'statements' => [
                        ['id' => 'statement_1', 'number' => 1, 'text' => 'Die ersten Sturmböen an der Küste werden für die Abendstunden des heutigen Tages erwartet.'],
                        ['id' => 'statement_2', 'number' => 2, 'text' => 'Das internationale Theaterfestival in Berlin wird wie ursprünglich geplant am Freitagabend eröffnet.'],
                        ['id' => 'statement_3', 'number' => 3, 'text' => 'Das Bildungsministerium stellt finanzielle Mittel für den Kauf von Tablets an Grundschulen zur Verfügung.'],
                        ['id' => 'statement_4', 'number' => 4, 'text' => 'Die neue Bürger-Service-App der Stadt Hannover kann auch von Personen genutzt werden, die außerhalb des Stadtgebiets wohnen.'],
                        ['id' => 'statement_5', 'number' => 5, 'text' => 'In tieferen Schichten des Planeten Mars wurde laut neuesten Untersuchungen gefrorenes Wasser entdeckt.'],
                    ],
                    'correct' => [
                        'statement_1' => 'true',
                        'statement_2' => 'false',
                        'statement_3' => 'true',
                        'statement_4' => 'false',
                        'statement_5' => 'true',
                    ],
                    'explanation' => [
                        'statement_1' => $this->buildComprehensionExplanation('true', 'Die Experten rechnen mit dem Beginn des Unwetters gegen achtzehn Uhr, also am Abend.', 'Experten erwarten den Beginn des Unwetters gegen achtzehn Uhr.', 'Die Aussage gibt diesen Zeitpunkt korrekt wieder.'),
                        'statement_2' => $this->buildComprehensionExplanation('false', 'Wegen technischer Probleme verschiebt sich der Auftakt von Freitag auf Samstag.', 'Das Festival wird nicht wie vorgesehen am Freitagabend eröffnet.', 'Die Aussage behauptet das Gegenteil und ist daher falsch.'),
                        'statement_3' => $this->buildComprehensionExplanation('true', 'Das Bildungsministerium stellt 15 Millionen Euro für Tablets an Grundschulen bereit.', 'Mit diesen Geldern sollen moderne Tablets angeschafft werden.', 'Die Aussage fasst die Information korrekt zusammen.'),
                        'statement_4' => $this->buildComprehensionExplanation('false', 'Die App ist ausschließlich für Personen mit Erstwohnsitz im Stadtgebiet; Pendler sind ausgeschlossen.', 'Die Nutzung bleibt ausdrücklich Personen mit gemeldetem Erstwohnsitz vorbehalten.', 'Die Aussage behauptet das Gegenteil und ist daher falsch.'),
                        'statement_5' => $this->buildComprehensionExplanation('true', 'Radarmessungen haben in zwei Kilometern Tiefe unter der Marsoberfläche gefrorenes Wasser nachgewiesen.', 'In einer Tiefe von etwa zwei Kilometern konnten riesige Mengen an gefrorenem Wasser nachgewiesen werden.', 'Die Aussage gibt den Befund korrekt wieder.'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hoerenTeil2Tasks(): array
    {
        return [
            [
                'topic' => 'Hören 2: Digitalisierung im Handwerk',
                'source_url' => null,
                'source_label' => 'Internal Zertify seed set',
                'source_notes' => 'AI-generated B2 Allgemein seed task.',
                'content' => [
                    'instructions' => 'Sie hören ein Rundfunkinterview. Dazu sollen Sie zehn Aufgaben lösen. Sie hören dieses Interview nur einmal. Entscheiden Sie beim Hören, ob die Aussagen richtig oder falsch sind.',
                    'audio' => [
                        'title' => 'Radiointerview: Digitalisierung im Handwerk',
                        'audio_notes' => 'Interview zwischen Moderator und Sam Jordan (Gründer einer Software-Firma für Handwerksbetriebe), informatives Gespräch über Praxiserfahrungen.',
                    ],
                    'transcript' => "INTERVIEWER: Herzlich willkommen in unserem Magazin, Sam Jordan. Sie haben eine App entwickelt, die den Büroalltag auf dem Bau revolutionieren soll. Wie kam es eigentlich zu der Entscheidung, diesen Schritt in die Selbstständigkeit zu wagen?\nGUEST: Danke für die Einladung. Die Idee entstand eigentlich aus dem Frust heraus, den ich bei Praktika im Handwerk erlebt habe. Ich wollte das Projekt aber nicht im Alleingang durchziehen. Deshalb habe ich mich mit einem ehemaligen Studienkollegen zusammengesetzt und wir haben die Firma dann gemeinsam gegründet, da wir uns fachlich perfekt ergänzen.\nINTERVIEWER: Ein starkes Team ist oft die halbe Miete. Wenn man sich die Software nun ansieht: Für welche Art von Betrieben ist Ihre Lösung denn eigentlich gedacht?\nGUEST: Das ist ein wichtiger Punkt. Große Baukonzerne haben meist eigene IT-Abteilungen und teure Systeme. Unsere App ist hingegen ganz gezielt für die Bedürfnisse der Kleinen konzipiert, also explizit für Betriebe mit weniger als zehn Mitarbeitern. Dort fehlt oft die Zeit für komplexe Verwaltung, und genau da setzen wir an.\nINTERVIEWER: Baustellen sind ja oft Funklöcher. Wie geht die Technik damit um, wenn das Netz mal wieder komplett weg ist?\nGUEST: Das war eine Grundvoraussetzung bei der Entwicklung. Unsere App verfügt über eine spezielle Offline-Funktion, damit alle Daten auch im tiefsten Keller ohne Empfang problemlos gespeichert werden können. Sobald das Smartphone wieder eine Internetverbindung hat, werden die Einträge automatisch mit dem Server im Büro synchronisiert.\nINTERVIEWER: Wie hat denn die Belegschaft auf die Umstellung reagiert?\nGUEST: Das war tatsächlich ein Prozess. Besonders die langjährigen Mitarbeiter äußerten zu Beginn große Skepsis. Da gab es Ängste, dass alles komplizierter wird. Aber wir konnten diese Bedenken meist schnell zerstreuen, da das System sehr intuitiv ist. Eine Einführungsschulung dauert laut unserer Erfahrung lediglich einen Nachmittag, dann haben alle die Grundlagen verstanden.\nINTERVIEWER: Wie stellen Sie sicher, dass auch wirklich jeder mit der Oberfläche zurechtkommt?\nGUEST: Das Geheimnis liegt in der Reduktion. Um die Bedienung so barrierefrei wie möglich zu gestalten, setzt das Design unserer App primär auf selbsterklärende Icons statt auf komplizierte Textanweisungen. Ein Hammer-Symbol steht für die Baustellendokumentation, eine Uhr für die Zeiterfassung.\nGUEST: Wir haben durch das Feedback der Handwerker gelernt, dass Farben eine große Rolle spielen. Jetzt ist alles noch klarer strukturiert, und die Akzeptanz im Team ist mittlerweile enorm hoch, weil sie merken, wie viel Zeit sie am Feierabend sparen.\nINTERVIEWER: Hat sich die Investition für die Betriebe gelohnt? Sind zum Beispiel die Materialausgaben gestiegen?\nGUEST: Tatsächlich befürchten viele erst einmal höhere Ausgaben. Aber das Gegenteil ist der Fall: Durch die präzise digitale Erfassung und die bessere Planung konnte der Materialverschnitt und somit die Kosten deutlich gesenkt werden.\nINTERVIEWER: Wie wirkt sich das System auf die Kommunikation mit den Kunden aus?\nGUEST: Die Transparenz ist heute viel höher. Kunden erhalten nun Fotos vom Baufortschritt und detaillierte Statusberichte in Echtzeit direkt auf ihr Smartphone. Das schafft ein enormes Vertrauen.\nINTERVIEWER: Planen Sie, Ihre Softwarelösungen auch im Ausland anzubieten?\nGUEST: Das Interesse ist zwar groß, aber wir wollen nichts überstürzen. Momentan konzentrieren wir uns voll auf den deutschsprachigen Markt. Eine Expansion in das europäische Ausland ist ein festes Ziel, aber das ist erst in drei Jahren vorgesehen.\nINTERVIEWER: Was würden Sie Handwerksbetrieben raten, die noch am Anfang der Digitalisierung stehen?\nGUEST: Mein wichtigster Rat ist: Überfordern Sie sich und Ihre Belegschaft nicht. Man sollte auf keinen Fall versuchen, alle Prozesse gleichzeitig zu digitalisieren. Stattdessen rät unsere Firma dazu, mit der Zeiterfassung zu beginnen. Das ist ein überschaubarer erster Schritt, der sofort für Transparenz sorgt.",
                    'context' => ['speaker' => 'Interviewer und Sam Jordan (Softwaregründer)', 'replay_limit' => 1],
                    'statements' => [
                        ['id' => 'statement_1', 'number' => 1, 'text' => 'Sam Jordan hat das Unternehmen ohne Partner ins Leben gerufen.'],
                        ['id' => 'statement_2', 'number' => 2, 'text' => 'Die Anwendung wurde speziell für Betriebe mit einer geringen Mitarbeiterzahl entwickelt.'],
                        ['id' => 'statement_3', 'number' => 3, 'text' => 'Die App ermöglicht die Dateneingabe auch an Orten ohne Internetverbindung.'],
                        ['id' => 'statement_4', 'number' => 4, 'text' => 'Die erfahrenen Mitarbeiter im Team begrüßten die digitale Umstellung von Anfang an.'],
                        ['id' => 'statement_5', 'number' => 5, 'text' => 'Das Erlernen der App-Bedienung nimmt mehrere Arbeitstage in Anspruch.'],
                        ['id' => 'statement_6', 'number' => 6, 'text' => 'Die Benutzeroberfläche arbeitet zur Vereinfachung hauptsächlich mit Symbolen.'],
                        ['id' => 'statement_7', 'number' => 7, 'text' => 'Der Einsatz der Software führte zu einer Erhöhung der Materialkosten.'],
                        ['id' => 'statement_8', 'number' => 8, 'text' => 'Die Auftraggeber können den Fortschritt der Arbeiten zeitnah digital mitverfolgen.'],
                        ['id' => 'statement_9', 'number' => 9, 'text' => 'Die Firma plant, ihr Angebot noch in diesem Jahr auf ganz Europa auszuweiten.'],
                        ['id' => 'statement_10', 'number' => 10, 'text' => 'Sam Jordan empfiehlt, die Digitalisierung schrittweise mit der Erfassung der Arbeitszeit zu beginnen.'],
                    ],
                    'correct' => [
                        'statement_1' => 'false',
                        'statement_2' => 'true',
                        'statement_3' => 'true',
                        'statement_4' => 'false',
                        'statement_5' => 'false',
                        'statement_6' => 'true',
                        'statement_7' => 'false',
                        'statement_8' => 'true',
                        'statement_9' => 'false',
                        'statement_10' => 'true',
                    ],
                    'explanation' => [
                        'statement_1' => $this->buildComprehensionExplanation('false', 'Das Unternehmen wurde gemeinsam mit einem ehemaligen Studienkollegen gegründet.', 'Ich wollte das Projekt nicht im Alleingang durchziehen. Wir haben die Firma gemeinsam gegründet.', 'Die Aussage behauptet das Gegenteil und ist falsch.'),
                        'statement_2' => $this->buildComprehensionExplanation('true', 'Jordan bestätigt, dass die Software für kleine Handwerksbetriebe mit weniger als zehn Angestellten gemacht ist.', 'Unsere App ist ganz gezielt für Betriebe mit weniger als zehn Mitarbeitern konzipiert.', 'Die Aussage gibt die Zielgruppe korrekt wieder.'),
                        'statement_3' => $this->buildComprehensionExplanation('true', 'Die App funktioniert dank einer Offline-Funktion auch dort, wo es kein Internet gibt.', 'alle Daten auch im tiefsten Keller ohne Empfang problemlos gespeichert werden können.', 'Die Aussage ist daher richtig.'),
                        'statement_4' => $this->buildComprehensionExplanation('false', 'Langjährige Mitarbeiter waren anfangs skeptisch und nicht sofort begeistert.', 'Besonders die langjährigen Mitarbeiter äußerten zu Beginn große Skepsis.', 'Die Aussage behauptet das Gegenteil und ist falsch.'),
                        'statement_5' => $this->buildComprehensionExplanation('false', 'Die Schulung dauert nur einen Nachmittag, nicht mehrere Tage.', 'Eine Einführungsschulung dauert laut unserer Erfahrung lediglich einen Nachmittag.', 'Die Aussage übertreibt die Dauer und ist daher falsch.'),
                        'statement_6' => $this->buildComprehensionExplanation('true', 'Die App nutzt Symbole statt Text, um die Bedienung zu erleichtern.', 'das Design unserer App primär auf selbsterklärende Icons statt auf komplizierte Textanweisungen.', 'Die Aussage ist daher richtig.'),
                        'statement_7' => $this->buildComprehensionExplanation('false', 'Die Kosten sanken durch die App, anstatt zu steigen.', 'konnte der Materialverschnitt und somit die Kosten deutlich gesenkt werden.', 'Die Aussage ist das Gegenteil der Wahrheit und daher falsch.'),
                        'statement_8' => $this->buildComprehensionExplanation('true', 'Kunden können den Status ihrer Baustelle live über ihr Handy verfolgen.', 'Kunden erhalten nun Fotos vom Baufortschritt und detaillierte Statusberichte in Echtzeit.', 'Die Aussage stimmt mit der Information überein.'),
                        'statement_9' => $this->buildComprehensionExplanation('false', 'Die Expansion ist erst für in drei Jahren geplant, nicht für das laufende Jahr.', 'Eine Expansion in das europäische Ausland ist erst in drei Jahren vorgesehen.', 'Die Aussage nennt einen falschen Zeitraum und ist daher falsch.'),
                        'statement_10' => $this->buildComprehensionExplanation('true', 'Jordan empfiehlt ausdrücklich, die Zeiterfassung als ersten Schritt der Digitalisierung zu wählen.', 'Stattdessen rät unsere Firma dazu, mit der Zeiterfassung zu beginnen.', 'Die Aussage gibt den Rat korrekt wieder.'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hoerenTeil3Tasks(): array
    {
        return [
            [
                'topic' => 'Hören 3: Servicehinweise und Ankündigungen',
                'source_url' => null,
                'source_label' => 'Internal Zertify seed set',
                'source_notes' => 'AI-generated B2 Allgemein seed task.',
                'content' => [
                    'instructions' => 'Sie hören fünf kurze Durchsagen und Nachrichten. Entscheiden Sie jeweils: richtig oder falsch.',
                    'audio' => [
                        'title' => 'Servicehinweise und Ankündigungen',
                        'audio_notes' => 'Fünf kurze Durchsagen und Mitteilungen aus unterschiedlichen Kontexten.',
                    ],
                    'transcript' => 'Sehr geehrte Fahrgäste im ICE 782 nach Hamburg-Altona. Aufgrund eines technischen Defekts an der Klimaanlage mussten kurzfristig drei Wagen ausgetauscht werden. Bitte beachten Sie, dass sich dadurch die Wagenreihung geändert hat und alle Sitzplatzreservierungen für diesen Zug ihre Gültigkeit verlieren. Fahrgäste mit einer Reservierung wenden sich bitte nach der Ankunft am Zielort oder direkt hier am Bahnhof an den Service-Point, um eine Erstattung der Gebühren zu beantragen. — Willkommen bei Elbe-Touristik. Bitte beachten Sie, dass aufgrund der aktuell sehr hohen Nachfrage ein Ticketverkauf direkt am Anleger leider nicht mehr möglich ist. Reservierungen und Zahlungen müssen vorab ausschließlich über unser Online-Portal getätigt werden. — Hallo zusammen, hier ist euer Freizeit-Update für das kommende Wochenende! Bitte beachten Sie unbedingt: Da die Wege teilweise uneben sind, ist festes Schuhwerk sowie eine eigene, funktionstüchtige Taschenlampe für jeden Teilnehmer zwingend erforderlich. — Herzlich willkommen beim Fit-and-Fun Studio! Bitte beachten Sie jedoch: Dieses spezifische Spezial-Angebot gilt ausschließlich für ordentlich eingeschriebene Studierende, die das 26. Lebensjahr noch nicht vollendet haben. — Hallo, hier ist Katrin. Denk bitte daran, dass du deine Dateien bis spätestens Freitagmittag auf den gemeinsamen Server hochladen musst.',
                    'statements' => [
                        ['id' => 'statement_1', 'number' => 1, 'text' => 'Fahrgäste erhalten am Service-Point Informationen zur Erstattung ihrer Reservierungsgebühren.'],
                        ['id' => 'statement_2', 'number' => 2, 'text' => 'Tickets für die Hafenrundfahrt sind vor Ort am Anleger erhältlich.'],
                        ['id' => 'statement_3', 'number' => 3, 'text' => 'Die Teilnehmer der Nachtwanderung sind verpflichtet, eine eigene Taschenlampe mitzuführen.'],
                        ['id' => 'statement_4', 'number' => 4, 'text' => 'Alle Neukunden erhalten bei einer Anmeldung den Rabatt auf die Anmeldegebühr.'],
                        ['id' => 'statement_5', 'number' => 5, 'text' => 'Die Unterlagen für das Projekt müssen spätestens am Freitagmittag vorliegen.'],
                    ],
                    'correct' => [
                        'statement_1' => 'true',
                        'statement_2' => 'false',
                        'statement_3' => 'true',
                        'statement_4' => 'false',
                        'statement_5' => 'true',
                    ],
                    'explanation' => [
                        'statement_1' => $this->buildComprehensionExplanation('true', 'Die Durchsage weist explizit darauf hin, dass Fahrgäste sich am Service-Point zwecks Gebührenerstattung melden sollen.', 'Fahrgäste wenden sich bitte an den Service-Point, um eine Erstattung der Gebühren zu beantragen.', 'Die Aussage gibt diesen Hinweis korrekt wieder.'),
                        'statement_2' => $this->buildComprehensionExplanation('false', 'Die Durchsage stellt klar, dass ein Ticketverkauf am Anleger nicht mehr möglich ist.', 'ein Ticketverkauf direkt am Anleger leider nicht mehr möglich ist.', 'Die Aussage behauptet das Gegenteil und ist daher falsch.'),
                        'statement_3' => $this->buildComprehensionExplanation('true', 'Es wird betont, dass eine eigene Taschenlampe für jeden Teilnehmer zwingend erforderlich ist.', 'eine eigene, funktionstüchtige Taschenlampe für jeden Teilnehmer zwingend erforderlich.', 'Die Aussage gibt diese Anforderung korrekt wieder.'),
                        'statement_4' => $this->buildComprehensionExplanation('false', 'Das Angebot gilt laut Text ausschließlich für Studierende unter 26 Jahren, nicht für alle Neukunden.', 'Dieses Angebot gilt ausschließlich für ordentlich eingeschriebene Studierende, die das 26. Lebensjahr noch nicht vollendet haben.', 'Die Aussage verallgemeinert zu weit und ist daher falsch.'),
                        'statement_5' => $this->buildComprehensionExplanation('true', 'Die Kollegin nennt Freitagmittag als den spätesten Zeitpunkt für den Upload der Dateien.', 'du deine Dateien bis spätestens Freitagmittag auf den gemeinsamen Server hochladen musst.', 'Die Aussage gibt die Deadline korrekt wieder.'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function normalizeSeedContent(array $content): array
    {
        if (($content['format'] ?? null) === ListeningTeilOneSegmentedContent::FORMAT) {
            $content = ListeningTeilOneSegmentedContent::normalize($content);
        }

        $explanations = $content['explanation'] ?? null;
        $correctAnswers = $content['correct'] ?? [];

        if (! is_array($explanations) || ! is_array($correctAnswers)) {
            return $content;
        }

        $content['explanation'] = collect($explanations)
            ->map(function (mixed $explanation, string $gapId) use ($correctAnswers): mixed {
                if (is_array($explanation)) {
                    return $explanation;
                }

                return $this->buildStructuredExplanation(
                    (string) ($correctAnswers[$gapId] ?? ''),
                    (string) $explanation,
                );
            })
            ->all();

        return $content;
    }

    /**
     * @return array<string, string>
     */
    private function buildStructuredExplanation(string $answer, string $legacyExplanation): array
    {
        $normalized = trim($legacyExplanation);
        $answerPrefix = $answer !== '' ? "{$answer} — " : '';

        if ($answerPrefix !== '' && Str::startsWith($normalized, $answerPrefix)) {
            $normalized = trim(Str::after($normalized, $answerPrefix));
        }

        $pattern = '';

        if (str_contains($normalized, ':')) {
            [$possiblePattern, $remainder] = explode(':', $normalized, 2);
            $possiblePattern = trim($possiblePattern);

            if ($possiblePattern !== '' && mb_strlen($possiblePattern) <= 80) {
                $pattern = $possiblePattern;
                $normalized = trim($remainder);
            }
        }

        [$reason, $contrast] = $this->splitReasonAndContrast($normalized);

        return [
            'answer' => $answer,
            'rule_type' => $this->detectRuleType($legacyExplanation),
            'reason' => $reason,
            'pattern' => $pattern,
            'contrast' => $contrast,
            'example' => '',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitReasonAndContrast(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($sentences) <= 1) {
            return [trim($text), ''];
        }

        $lastSentence = trim((string) end($sentences));

        if (! $this->looksLikeContrastSentence($lastSentence)) {
            return [trim($text), ''];
        }

        array_pop($sentences);

        return [
            trim(implode(' ', $sentences)),
            $lastSentence,
        ];
    }

    private function looksLikeContrastSentence(string $sentence): bool
    {
        $needle = mb_strtolower($sentence);

        foreach ([
            'passt nicht',
            'wäre',
            'würde',
            'umgangssprachlich',
            'veraltet',
            'falsch',
            'nicht',
            'exklusiv',
            'obere grenze',
            'untere grenze',
        ] as $keyword) {
            if (str_contains($needle, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function detectRuleType(string $legacyExplanation): string
    {
        $text = mb_strtolower($legacyExplanation);

        return match (true) {
            str_contains($text, 'verb-präposition-paar'), str_contains($text, 'verb-praposition-paar') => 'Verb mit Präposition',
            str_contains($text, 'pronominaladverb') => 'Pronominaladverb',
            str_contains($text, 'relativpronomen') => 'Relativpronomen',
            str_contains($text, 'relativadverb') => 'Relativadverb',
            str_contains($text, 'fragepronomen') => 'Fragepronomen',
            str_contains($text, 'konjunktiv ii') => 'Konjunktiv II',
            str_contains($text, 'modalverb') => 'Modalverb',
            str_contains($text, 'kausalkonjunktion'), str_contains($text, 'konzessive konjunktion'), str_contains($text, 'konsekutivkonjunktion'), str_contains($text, 'finalkonjunktion'), str_contains($text, 'konjunktion') => 'Konjunktion',
            str_contains($text, 'kausaladverb'), str_contains($text, 'adversatives adverb'), str_contains($text, 'additives adverb'), str_contains($text, 'gradpartikel'), str_contains($text, 'adverb') => 'Adverb',
            str_contains($text, 'präposition'), str_contains($text, 'praposition') => 'Präposition',
            str_contains($text, 'redewendung') => 'Feste Wendung',
            str_contains($text, 'kollokation') => 'Feste Verbindung',
            str_contains($text, 'nomen') => 'Nomenverbindung',
            default => 'Grammatik',
        };
    }
}
