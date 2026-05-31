<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ShowLesenModuleController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Modules/Lesen', [
            'module' => [
                'contextLabel' => 'B2 · Allgemein · Lesen',
                'title' => 'Prüfungsvorbereitung: Lesen',
                'subtitle' => 'Kombinieren Sie die Texte mit den entsprechenden Situationen.',
                'timer' => '12:45 verbleibend',
                'startedLockLabel' => 'Teil-Wechsel gesperrt, sobald die erste Antwort gewählt wurde.',
            ],
            'parts' => [
                ['key' => 'teil1', 'label' => 'Teil 1', 'available' => true],
                ['key' => 'teil2', 'label' => 'Teil 2', 'available' => true],
                ['key' => 'teil3', 'label' => 'Teil 3', 'available' => true],
            ],
            'task' => $this->partOneTask(),
        ]);
    }

    /**
     * @return array{
     *     instructions: string,
     *     prompt: string,
     *     situations: array<int, array{id: string, number: int, text: string}>,
     *     texts: array<int, array{id: string, label: string, title: string, body: string}>,
     *     extra_answer: array{id: string, label: string, text: string},
     *     correct: array<string, string>,
     *     explanation: array<string, array<string, string>>
     * }
     */
    private function partOneTask(): array
    {
        return [
            'instructions' => 'Lesen Sie die Überschriften und ordnen Sie dann jedem Eintrag den passenden Text zu. Jeder Text wird nur einmal vergeben.',
            'prompt' => 'Überschriften zuordnen',
            'situations' => [
                ['id' => 'situation_1', 'number' => 1, 'label' => 'A', 'text' => 'Schaden an Kreuzfahrtschiff verhindert Weiterfahrt'],
                ['id' => 'situation_2', 'number' => 2, 'label' => 'B', 'text' => 'Bäder, Seen und Natur – im hessischen Paradies'],
                ['id' => 'situation_3', 'number' => 3, 'label' => 'C', 'text' => 'Freiheit und Natur – nach sechs Wochen harter Arbeit'],
                ['id' => 'situation_4', 'number' => 4, 'label' => 'D', 'text' => 'Jugendliche arbeiten für Jugendliche'],
                ['id' => 'situation_5', 'number' => 5, 'label' => 'E', 'text' => 'Aus der Stadt an die See – sichere Strände für Urlauber'],
                ['id' => 'situation_6', 'number' => 6, 'label' => 'F', 'text' => 'Urlaub an deutschen Seen immer gefährlicher'],
                ['id' => 'situation_7', 'number' => 7, 'label' => 'G', 'text' => 'Kinderarbeit in Deutschland: Jugendliche werden zur Arbeit gezwungen'],
                ['id' => 'situation_8', 'number' => 8, 'label' => 'H', 'text' => 'Nach harter Arbeit durch nordische Gewässer'],
                ['id' => 'situation_9', 'number' => 9, 'label' => 'I', 'text' => 'Zu Gast bei den Fürsten'],
                ['id' => 'situation_10', 'number' => 10, 'label' => 'J', 'text' => 'Wegen Niedrigwasser: vom Fluss auf die Straße'],
            ],
            'texts' => [
                [
                    'id' => 'text_a',
                    'label' => '1',
                    'title' => '',
                    'body' => 'Entdecken Sie interessante Städte und Regionen. Im Herzen Deutschlands liegen wunderbare Landschaften, mit einem für deutsche Verhältnisse sehr milden Klima – und keine typischen „Touristenziele“. Von der netten Stadt Gießen ausgehend kann man in den hessischen Kreisen Bergstraße und Waldeck-Frankenberg noch viele Orte entdecken, die noch ein Geheimtipp sind. Vor allem gilt dies für den Kreis Waldeck-Frankenberg. Wer nicht gerade in Hessen wohnt, wird kaum eine Ahnung haben, wo diese Region eigentlich liegt. Es ist ein herrliches Stück Deutschland ohne besonders große Städte, eine Gegend, die Natur pur bietet. Daher wundert es nicht, dass man hier einige Kurorte findet wie Bad Arolsen oder Bad Wildungen oder den Luftkurort Edertal-Kleinern. Apropos Edertal: Der zwölf Quadratkilometer große Edersee gehört zu den vier schönen „blauen Augen“ des Kreises. Der Landkreis Waldeck-Frankenberg ist Hessens attraktivstes Umland. In der Region der Berge und Seen spürt man auch heute noch einen Hauch von Fürstlichkeit: Majestätisch erhebt sich über dem Edersee das Schloss Waldeck. Auch in Bad Arolsen, einer ehemaligen Residenzstadt, ist vieles noch vom früheren Adel geprägt. Unbedingt besuchen sollte man darüber hinaus das 1000-jährige Korbach wie auch die Fachwerkstadt Frankenberg mit ihren vielen romantischen Ecken.',
                ],
                [
                    'id' => 'text_b',
                    'label' => '2',
                    'title' => '',
                    'body' => 'Pferde waren schon immer Melanie Schilles große Leidenschaft. „Und jetzt kann ich Hobby und Beruf miteinander verbinden“, freut sich die junge Beamtin aus Hannover. In diesem Jahr verstärkt sie die Strandwache an der Nordseeküste. Ihr Arbeitsplatz ist der Strand: Mit „Magnus“, einem 11-jährigen Pferd, patrouilliert sie dort, wo die Kleinen Sandburgen bauen, Urlauber bei einem Buch entspannen oder sich wagemutig in die kühlen Fluten stürzen. Melanie Schille und ihr brauner Hannoveraner sind zweifellos eine Attraktion in dem Ferienort. Immer wieder wollen Gäste das Tier streicheln, von der Polizistin wissen, was sie hier macht. „Wir sorgen für mehr Sicherheit am Strand“, erklären Melanie Schille und Rüdiger Teichmann (42). Sie suchen im Watt nach vermissten Kindern, klären über Gefahren auf, verhindern Diebstähle und Sachbeschädigungen. Nachweislich gingen die Delikte zurück, seit es die Streife hoch zu Ross gibt. Die Polizisten: „Wir sind in dem unwegsamen Gelände oft schneller am Einsatzort als die Kollegen per Fahrrad oder mit dem Auto. Außerdem schonen wir die Natur.“ Für sich persönlich sieht Melanie Schille noch einen großen Vorteil: „Es ist schön, mal keine Demonstration sichern zu müssen, stattdessen genieße ich die frische Luft mit fröhlichen Urlaubern.“ Nur eins vermisst die 22-Jährige, die mit Polizeipferd „Magnus“ auf einem Bauernhof Quartier bezogen hat, während ihres sechs-wöchigen Einsatzes: Freund Robert (23). Er fährt als Polizist in Hannover Streife – und wartet auf sie.',
                ],
                [
                    'id' => 'text_c',
                    'label' => '3',
                    'title' => '',
                    'body' => 'Ein neuer Urlaubstrend setzt sich durch: Statt faul am Strand zu liegen, wird man aktiv. Besonders beliebt als Ziel ist Schweden am Ufer des Flusses Klarälven in der Provinz Värmland. In drei bis sechs Stunden baut man hier selbst ein Floß und macht anschließend darauf Urlaub. „Das ist Abenteuerurlaub pur“, schwärmt Urlauber Johan Bengtson (37), der mit seiner Frau Kari (38) und den drei Kindern Martin (13), Elfrida (11) und Peter (8) zum zweiten Mal Floßferien macht: „Wir fühlen uns wie Huckleberry Finn und Tom Sawyer. Sich auf dem Fluss treiben lassen und in der Wildnis leben – dieses Gefühl ist nicht zu überbieten!“ Seit zehn Jahren veranstaltet Marie Junler (35) von der Agentur Vildmark i Värmland die Holzfloßtrips: „In der ersten Saison kamen 200 Gäste, darunter 40 Deutsche.“ In der letzten Saison waren es schon 1700, darunter 500 Deutsche, die diesen unvergleichlichen Natururlaub für einen Tag oder eine ganze Woche buchten. Wir haben die Bengtsons an ihrem ersten Urlaubstag begleitet, auch dabei: Veranstalterin Marie Junler, die der Familie hilft, das Floß zu bauen. Es ist ein herrlicher Sonnentag. In einer sanften Kurve des 270 Kilometer langen Flusses Klarälven nahe dem Dorf Branäs in Mittelschweden steht Marie bis zu den Hüften im tiefblauen Wasser. Mit fingerdicken grünen Seilen schnürt sie Holzstämme zusammen. Laut schallen ihre Kommandos zu Johan und seiner Familie hinüber: „Einer hält den Stamm, der andere knotet – den Seemannsknoten, wie wir ihn vorhin an Land geübt haben.“ Ohne einen Nagel werden 96 Baumstämme verzurrt – im Wasser, sonst wäre das Holz zu schwer. Mindestens zwei Erwachsene sind nötig, um ein Floß zu bauen – einer allein packt’s nicht. Nach drei Stunden ist es geschafft: Das Urlaubsparadies der Bengtsons – es misst übrigens 6 mal 3 Meter und wiegt stattliche 2 Tonnen – treibt am Ufer. Noch schnell das Sonnenzelt befestigen, darunter Vorratskasten, Frischwassertank, Chemie-Klo, Küchenausrüstung, Zelt, Rettungsring, Schwimmwesten, Notruf-Telefonnummer und das Paddel zum Steuern und Manövrieren verstauen – und ab geht’s.',
                ],
                [
                    'id' => 'text_d',
                    'label' => '4',
                    'title' => '',
                    'body' => 'Von 9 bis 15 Uhr arbeitet Sebastian Keller (18) in einem Altenwohnheim in Hamburg-Altona: Er kümmert sich um die Essensausgabe, putzt anschließend die Küche und dann ist noch Zeit, um den Älteren etwas vorzulesen oder mit ihnen Karten zu spielen. Zur gleichen Zeit putzen Rebecca (12) und Christiane (13) den Eingang des Hamburger „Michels“, der wohl bekanntesten Kirche der Stadt, und Friderike (17) füttert schon früh morgens Kühe, Schweine und Hühner auf einem Bio-Bauernhof bei Wedel. „Endlich mal ein sinnvoller Job“, sagen die fünf übereinstimmend. Sie stehen stellvertretend für etwa 100.000 Jugendliche, die beim „Sozialen Tag“ mitgemacht haben. Hut ab! Und was mindestens ebenso beeindruckend ist: Der Verein „Hamburgs Schüler helfen“ (HSH) wurde von den Jugendlichen selbst im Jahr 2004 gegründet – und seitdem findet jedes Jahr im August der „Soziale Tag“ statt. Mit Behörden und Firmen haben Schüler aus Hamburg Verträge für einen Tag abgeschlossen. Die Schülerinnen und Schüler verdienen dann am „Sozialen Tag“ zwischen 6 und 8 Euro pro Stunde – aber nicht für sich selbst, sondern für andere. Denn der Verdienst wird jedes Jahr gespendet. Die Jugendlichen selbst wählen ein Projekt aus, an das sie die Gelder spenden wollen. Einzige Bedingung: Es muss ein Projekt sein, von dem Jugendliche profitieren. Im letzten Jahr zum Beispiel wurde die Gesamtsumme von 1,2 Millionen Euro an das Projekt „Frieden für alle“ gespendet. Ziel des Projekts ist es, Jugendliche in Kriegs- und Krisenregionen zu unterstützen, den Dialog unter Jugendlichen aus verschiedenen Ländern zu fördern und auch das Kennenlernen anderer Kulturen zu ermöglichen. So konnte von dem Geld, das der Verein HSH gespendet hat, eine internationale Online-Zeitschrift hergestellt werden, in der Jugendliche ihre Länder, kulturelle Besonderheiten oder auch ihre Sprache vorstellen konnten. Für Ralf Waldner (20) vom HSH steht fest: „Wir können und werden anderen auch in Zukunft helfen, das Engagement der Schülerinnen und Schüler in Hamburg ist in den letzten Jahren schließlich immer weiter gestiegen.“',
                ],
                [
                    'id' => 'text_e',
                    'label' => '5',
                    'title' => '',
                    'body' => 'Die Windjacken waren schon eingepackt, die Koffer geschlossen. Thomas Meurer (64) und Wiebke Fuchs (62) aus Hannover freuten sich auf ihre Flusskreuzfahrt mit der „MS Eurostar“ von Potsdam nach Prag. Stattliche 2500 Euro kostete die Reise pro Person, und beide hatten lange gespart, um sich das leisten zu können. Doch aus der Kreuzfahrt wurde eine Bustour. Meurer berichtet, was er erlebt hat: „Wir waren am Abend auf das Schiff gegangen und hatten unsere Kabinen bezogen. Am nächsten Morgen ging es los. Aber schon bald machte das Schiff wieder fest und alle Gäste mussten von Bord.“ Wiebke Fuchs ergänzt: „Der Fluss hatte einfach zu wenig Wasser, da konnten wir mit dem großen Kreuzfahrtschiff nicht weiterfahren!“ Per Bus ging es nach Prag. Beide wollen nun einen Teil des Reisepreises zurück, aber der Veranstalter Hapag-Lloyd wehrt ab: „Das war höhere Gewalt, da kann man nichts machen.“',
                ],
            ],
            'extra_answer' => [
                'id' => 'text_x',
                'label' => 'X',
                'text' => 'Kein passender Text',
            ],
            'correct' => [
                'situation_2' => 'text_a',
                'situation_4' => 'text_d',
                'situation_5' => 'text_b',
                'situation_8' => 'text_c',
                'situation_10' => 'text_e',
            ],
            'explanation' => [
                'situation_2' => [
                    'correct_answer' => 'text_a',
                    'reason' => 'Nur Text 1 beschreibt genau eine hessische Region mit Bädern, Seen und Natur.',
                    'evidence' => 'Zentrale Hinweise sind „Waldeck-Frankenberg“, „Kurorte“ und „Edersee“.',
                    'wrong_answer_reason' => 'Die anderen Texte behandeln Strandwache, Floßurlaub, Jugendprojekte oder eine gescheiterte Flusskreuzfahrt.',
                    'strategy_hint' => 'Achten Sie auf die Kombination aus Region, Landschaft und konkreten Ortsnamen.',
                ],
                'situation_5' => [
                    'correct_answer' => 'text_b',
                    'reason' => 'Text 2 spielt an der Nordseeküste und betont Sicherheit am Strand.',
                    'evidence' => 'Wichtig sind „Strandwache“, „mehr Sicherheit am Strand“ und der Einsatz an der See.',
                    'wrong_answer_reason' => 'Die anderen Texte führen in Binnenregionen, nach Schweden oder auf eine Flussreise ohne Strandkontext.',
                    'strategy_hint' => 'Suchen Sie nach den Wörtern Strand, Küste und Sicherheit.',
                ],
                'situation_8' => [
                    'correct_answer' => 'text_c',
                    'reason' => 'Text 3 beschreibt Natururlaub nach harter Arbeit beim Floßbau in Schweden.',
                    'evidence' => 'Die Reise beginnt mit dem Bauen des Floßes und führt anschließend durch nordische Gewässer.',
                    'wrong_answer_reason' => 'Die anderen Texte spielen an Land, an der deutschen Nordsee oder auf einer gescheiterten Flusskreuzfahrt.',
                    'strategy_hint' => 'Achten Sie darauf, wo Arbeit und Naturerlebnis direkt miteinander verbunden werden.',
                ],
                'situation_4' => [
                    'correct_answer' => 'text_d',
                    'reason' => 'Text 4 stellt Jugendliche vor, die gezielt für andere Jugendliche arbeiten und spenden.',
                    'evidence' => 'Entscheidend sind „Sozialer Tag“, „Hamburgs Schüler helfen“ und die Unterstützung anderer Jugendlicher.',
                    'wrong_answer_reason' => 'Die anderen Texte haben Reise- oder Urlaubsthemen und keinen sozialen Schwerpunkt.',
                    'strategy_hint' => 'Suchen Sie nach Hinweisen auf Engagement, Spenden und Jugendliche als Zielgruppe.',
                ],
                'situation_10' => [
                    'correct_answer' => 'text_e',
                    'reason' => 'Text 5 beschreibt klar den Wechsel vom Schiff auf den Bus wegen Niedrigwasser.',
                    'evidence' => 'Die Schlüsselaussagen sind „zu wenig Wasser“ und „Per Bus ging es nach Prag“.',
                    'wrong_answer_reason' => 'In keinem anderen Text scheitert eine Reise an einem niedrigen Wasserstand.',
                    'strategy_hint' => 'Achten Sie auf den Grund der Reiseänderung.',
                ],
            ],
        ];
    }
}
