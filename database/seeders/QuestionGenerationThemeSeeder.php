<?php

namespace Database\Seeders;

use App\Models\QuestionGenerationTheme;
use Illuminate\Database\Seeder;

class QuestionGenerationThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teil1Example1 = <<<'EXAMPLE'
Sehr geehrte Damen und Herren,

hiermit wende ich mich an Sie, um mich über erhebliche Mängel des Online-Sprachkurses Deutsch B2 zu beschweren, den ich vor zwei Monaten bei Ihrem Sprachinstitut {{gap_1}} habe. Bei meiner Anmeldung war ich von der ausführlichen Kursbeschreibung überzeugt, in der ausdrücklich {{gap_2}}, dass alle Teilnehmer täglich persönliche Rückmeldungen von qualifizierten Lehrkräften erhalten würden.

Zu meinem großen {{gap_3}} musste ich jedoch gleich in der ersten Kurswoche feststellen, dass meine schriftlichen Aufgaben tagelang unkorrigiert blieben. Auf insgesamt vier E-Mails an den Kundendienst habe ich bis heute keine einzige Antwort {{gap_4}}. Darüber hinaus brach die Lernplattform mehrmals täglich zusammen, {{gap_5}} ich wichtige Unterrichtseinheiten verpasste und im Lernfortschritt deutlich zurückblieb. Bei diesen Problemen handelt es sich keinesfalls {{gap_6}} Einzelfälle, sondern um ein durchgehendes technisches Versagen Ihres Systems.

Da ich {{gap_7}} diesen Kurs mehrere Hundert Euro bezahlt habe, halte ich eine Entschädigung für mehr als gerechtfertigt. Ich fordere Sie {{gap_8}} auf, mir bis zum Ende des laufenden Monats entweder eine anteilige Rückerstattung oder einen kostenfreien Ersatzkurs anzubieten. Ich gehe davon {{gap_9}}, dass Sie meinem berechtigten Anliegen nachkommen werden.

Sollte ich bis dahin keine zufriedenstellende Antwort erhalten, behalte ich mir ausdrücklich vor, die Verbraucherzentrale zu {{gap_10}} und entsprechende Bewertungen in einschlägigen Portalen zu veröffentlichen.

Mit freundlichen Grüßen
Katharina Richter
EXAMPLE;

        $teil1Example2 = <<<'EXAMPLE'
Sehr geehrter Herr Dr. Fischer,

über das Weiterbildungsportal Ihrer Handelskammer bin ich {{gap_1}} auf Ihr zweitägiges Seminar "Buchhaltung und Steuern für Selbstständige" aufmerksam geworden. Da ich seit Kurzem als freiberuflicher Grafiker tätig bin und über keine kaufmännische Ausbildung verfüge, bin ich {{gap_2}} auf der Suche nach einer soliden Grundlage in diesen Bereichen.

Bevor ich mich {{gap_3}} anmelde, hätte ich einige Fragen, die mir bei meiner Entscheidung helfen würden. Zunächst würde mich interessieren, {{gap_4}} Vorkenntnisse für die Teilnahme am Seminar vorausgesetzt werden. Sind Grundkenntnisse in der doppelten Buchführung bereits notwendig, oder richtet sich das Seminar ausdrücklich auch {{gap_5}} absolute Anfänger? Außerdem wäre es hilfreich zu wissen, ob die Seminarunterlagen {{gap_6}} als digitale Dateien zur Verfügung gestellt werden, da ich keine Möglichkeit habe, umfangreiche Druckmaterialien aufzubewahren.

Ein weiterer Punkt betrifft die zeitliche {{gap_7}}: Das Seminar findet laut Ihrer Ankündigung am 14. und 15. Mai statt. Gibt es alternativ auch Termine {{gap_8}} Juni oder Juli, falls ich an den genannten Tagen verhindert sein sollte? Ich würde das Seminar {{gap_9}} sehr gerne im ersten Halbjahr absolvieren, da ich ab Herbst mit einer deutlich höheren Auftragslage rechne.

Über eine baldige Rückmeldung {{gap_10}} ich mich sehr freuen. Für eventuelle Rückfragen stehe ich Ihnen telefonisch jederzeit zur Verfügung.

Mit freundlichen Grüßen
Fabian Krause
EXAMPLE;

        $teil1Example3 = <<<'EXAMPLE'
Sehr geehrte Damen und Herren,

über Ihren Onlineshop habe ich am 3. März ein Paket mit Büromaterialien im Gesamtwert von 148 Euro bestellt. Laut der Auftragsbestätigung, die ich per E-Mail erhalten habe, sollte die Lieferung spätestens {{gap_1}} fünf Werktagen bei mir eingehen. Heute ist der 20. März, und das Paket ist bis {{gap_2}} nicht angekommen.

Ich habe Ihre Kundenhotline bereits dreimal angerufen und jedes Mal eine andere Auskunft erhalten. Zunächst wurde mir mitgeteilt, das Paket sei {{gap_3}} beim Versanddienstleister. Beim zweiten Anruf hieß es, das Paket sei verloren gegangen und werde neu verschickt. Beim dritten Gespräch {{gap_4}} man mir, ich solle noch einige Tage warten. Diese widersprüchlichen Informationen sind für mich absolut {{gap_5}} und zeugen von mangelnder Professionalität.

Da ich die bestellten Materialien dringend für mein Büro benötige, fordere ich Sie {{gap_6}} auf, die Situation unverzüglich zu klären und mir das Paket innerhalb von 48 Stunden zuzustellen. Falls dies nicht {{gap_7}} ist, erwarte ich eine sofortige vollständige Rückerstattung des Kaufpreises auf mein Konto. Ich behalte mir {{gap_8}}, bei ausbleibender Reaktion eine Beschwerde bei der zuständigen Schlichtungsstelle einzureichen.

Bitte teilen Sie mir {{gap_9}} schriftlich mit, wie Sie in dieser Angelegenheit weiter vorgehen werden. Ich bitte um eine Antwort bis {{gap_10}} übermorgen.

Mit freundlichen Grüßen
Anna Bergmann
EXAMPLE;

        $teil1Example4 = <<<'EXAMPLE'
Sehr geehrte Frau Müller,

mit großem Interesse habe ich Ihre Stellenanzeige {{gap_1}} ein Praktikum im Bereich Marketing auf Ihrer Website entdeckt. Da ich mich zurzeit im vierten Semester meines Bachelorstudiums der Betriebswirtschaftslehre an der Universität Mannheim befinde, bin ich aktiv auf der Suche nach einer Möglichkeit, praktische Erfahrungen in einem innovativen Unternehmen wie Ihrem zu sammeln.

Ich interessiere mich {{gap_2}} besonders für den Bereich des digitalen Marketings, da ich in diesem Feld sowohl meine Bachelorarbeit als auch meinen späteren Berufseinstieg plane. Im Laufe meines Studiums habe ich umfassende theoretische Kenntnisse über Kampagnenplanung, Marktforschung und Social-Media-Strategien aufgebaut. Darüber hinaus verfüge ich bereits {{gap_3}} erste praktische Erfahrungen, die ich während eines Nebenjobs in einer kleinen Werbeagentur {{gap_4}} habe. Dort war ich unter anderem für die Pflege von Social-Media-Kanälen und die Analyse von Nutzerdaten {{gap_5}}.

Darf ich Sie noch um einige Informationen bitten? Mich würde interessieren, wie lange ein Praktikum bei Ihnen {{gap_6}} dauern muss und {{gap_7}} eine finanzielle Vergütung vorgesehen ist. Da ich meinen Lebensunterhalt selbst finanziere, wäre dies ein wichtiger {{gap_8}} bei meiner Entscheidung.

Im Anhang finden Sie meinen Lebenslauf {{gap_9}} aktuelle Arbeitszeugnisse. Über eine positive Rückmeldung {{gap_10}} ich mich sehr freuen.

Mit freundlichen Grüßen
Laura Weber
EXAMPLE;

        $teil2Example1 = <<<'EXAMPLE'
Schlaf gilt als eine der wichtigsten Voraussetzungen für ein gesundes Leben – {{gap_1}} unterschätzen viele Menschen seine Bedeutung erheblich. Mediziner sind sich einig, {{gap_2}} ausreichender Schlaf eine entscheidende Rolle für die körperliche und geistige Regeneration spielt. Während des Tiefschlafs werden Wachstumshormone ausgeschüttet, das Immunsystem gestärkt und Gedächtnisinhalte gefestigt. Wer dauerhaft zu wenig schläft, riskiert langfristige Schäden am Immunsystem, {{gap_3}} Schlaf maßgeblich an der Reparatur körpereigener Zellen beteiligt ist. Experten empfehlen für Erwachsene sieben bis neun Stunden Schlaf pro Nacht, {{gap_4}} sich Gehirn und Körper vollständig erholen können. Kinder und Jugendliche benötigen sogar noch deutlich mehr Schlaf, da ihr Organismus sich intensiv in der Wachstumsphase befindet.

{{gap_5}} zeigen aktuelle Untersuchungen deutlich, dass Schlafmangel unmittelbare Auswirkungen auf die kognitive Leistungsfähigkeit hat. Konzentrationsfähigkeit und Reaktionsvermögen nehmen bereits nach einer einzigen schlafarmen Nacht messbar ab, {{gap_6}} die Unfallgefahr im Straßenverkehr erheblich steigt. Besonders problematisch ist die zunehmende Schlafstörung durch digitale Medien: Das blaue Licht von Bildschirmen hemmt die Ausschüttung des Schlafhormons Melatonin, {{gap_7}} Einschlafen und Durchschlafen erheblich erschwert werden. Wer abends regelmäßig soziale Medien nutzt oder Serien schaut, schläft im Schnitt über eine Stunde weniger als Menschen ohne diese Gewohnheiten. Schlafexperten raten {{gap_8}}, alle Bildschirme mindestens eine Stunde vor dem Schlafen abzuschalten und stattdessen entspannende Aktivitäten wie Lesen oder leichte Dehnübungen zu bevorzugen.

Auch die Schlafumgebung beeinflusst die Schlafqualität maßgeblich. Ein kühles, dunkles und ruhiges Schlafzimmer fördert tiefen und erholsamen Schlaf. Die Raumtemperatur sollte idealerweise zwischen 16 und 18 Grad Celsius liegen, da es bei Wärme schwerer fällt, in den Tiefschlaf zu gleiten. {{gap_9}} man auf einen regelmäßigen Schlaf-Wach-Rhythmus achtet, kann der Körper seinen natürlichen Biorhythmus stabilisieren. Kurze Mittagsschläfchen von zwanzig bis dreißig Minuten können {{gap_10}} die Leistungsfähigkeit am Nachmittag steigern, ohne den Nachtschlaf zu beeinträchtigen. Länger sollte man tagsüber jedoch nicht schlafen, da sonst der Tiefschlafbedarf in der Nacht sinkt.
EXAMPLE;

        $teil2Example2 = <<<'EXAMPLE'
Die Arbeitswelt befindet sich in einem tiefgreifenden Wandel, {{gap_1}} durch die Digitalisierung und die Erfahrungen der Pandemie-Jahre stark beschleunigt wurde. Homeoffice, flexible Arbeitszeiten und ortsunabhängiges Arbeiten sind für viele Beschäftigte heute selbstverständlich – eine Entwicklung, {{gap_2}} noch vor einem Jahrzehnt kaum vorstellbar schien. Eine Umfrage aus dem Jahr 2023 ergab, dass mehr als sechzig Prozent der Büroangestellten mindestens zwei Tage pro Woche von zu Hause arbeiten möchten. Unternehmen, die flexiblere Arbeitsmodelle anbieten, haben laut aktuellen Studien deutliche Vorteile bei der Gewinnung qualifizierter Fachkräfte. Arbeitnehmer schätzen die bessere Vereinbarkeit von Beruf und Privatleben, {{gap_3}} Produktivität und Arbeitszufriedenheit nachweislich gesteigert werden können.

{{gap_4}} bringt die neue Arbeitswelt auch erhebliche Herausforderungen mit sich. Die Grenzen zwischen Arbeit und Freizeit verschwimmen zunehmend, {{gap_5}} viele Beschäftigte Schwierigkeiten haben, nach Feierabend wirklich abzuschalten. Psychologen warnen vor einem Anstieg von Burnout und chronischen Erschöpfungszuständen, {{gap_6}} Unternehmen ihrer Fürsorgepflicht stärker nachkommen sollten. Ein weiteres Problem ist die soziale Isolation: Wer dauerhaft von zu Hause aus arbeitet, {{gap_7}} auf den informellen Austausch mit Kollegen verzichten, der für Kreativität, Innovation und Teamzusammenhalt unerlässlich ist. Regelmäßige gemeinsame Präsenztage können helfen, den Teamgeist zu stärken und das Gemeinschaftsgefühl zu fördern.

Auch strukturelle Fragen sind noch nicht abschließend geklärt. {{gap_8}} sollen die Kosten für heimische Arbeitsplätze aufgeteilt werden? Wer trägt die Verantwortung für Arbeitssicherheit im Homeoffice? Darf ein Arbeitgeber Mitarbeiter verpflichten, ins Büro zu kommen, oder haben Arbeitnehmer ein Recht auf Homeoffice? In einigen Ländern, wie etwa Portugal und Spanien, wurden bereits Gesetze verabschiedet, die das Recht auf Nicht-Erreichbarkeit nach Feierabend regeln. Arbeitgeber und Gesetzgeber sind {{gap_9}} gefragt, klare und faire Regelungen zu schaffen. Gleichzeitig sollten Arbeitnehmer {{gap_10}} arbeiten, ihre eigenen Grenzen klar zu kommunizieren und eine gesunde Work-Life-Balance aktiv einzufordern.
EXAMPLE;

        $teil2Example3 = <<<'EXAMPLE'
Der Klimawandel stellt die größte globale Herausforderung unserer Zeit dar – {{gap_1}} sind sich nahezu alle Wissenschaftler einig. Die globale Durchschnittstemperatur ist seit der Industrialisierung um mehr als ein Grad Celsius gestiegen, {{gap_2}} gravierende Folgen wie häufigere Extremwetterereignisse, schmelzende Gletscher und steigende Meeresspiegel nach sich zieht. Bereits heute sind Küstenregionen in verschiedenen Teilen der Welt durch den Anstieg des Meeresspiegels akut bedroht. Regierungen weltweit haben sich im Pariser Klimaabkommen verpflichtet, die Erderwärmung auf deutlich unter zwei Grad zu begrenzen und die Treibhausgasemissionen drastisch zu reduzieren. {{gap_3}} die politischen Absichtserklärungen ambitioniert klingen, bleibt die praktische Umsetzung in vielen Ländern weit hinter den Erwartungen zurück.

Eine zentrale Frage ist, {{gap_4}} Einzelpersonen tatsächlich einen nennenswerten Beitrag zur Bekämpfung des Klimawandels leisten können. Kritiker argumentieren, dass die Hauptverantwortung bei der Industrie liegt, {{gap_5}} diese für den Großteil der weltweiten Treibhausgasemissionen verantwortlich ist. {{gap_6}} betonen andere Experten, dass kollektive Veränderungen im Konsumverhalten durchaus eine bedeutende Wirkung entfalten können. Wer {{gap_7}} auf Flugreisen verzichtet, weniger Fleisch konsumiert und öffentliche Verkehrsmittel nutzt, kann seinen persönlichen CO₂-Fußabdruck erheblich reduzieren.

{{gap_8}} ist die soziale Dimension des Klimawandels nicht zu vernachlässigen: Ärmere Bevölkerungsschichten sind von den Folgen besonders stark betroffen, {{gap_9}} sie kaum Mittel haben, sich vor Extremwetterereignissen zu schützen oder in klimafreundlichere Technologien zu investieren. Eine gerechte Klimapolitik muss {{gap_10}} soziale Ungleichheiten berücksichtigen und tragbare Lösungen für alle Bevölkerungsgruppen entwickeln.
EXAMPLE;

        $teil2Example4 = <<<'EXAMPLE'
Die Digitalisierung verändert das Bildungswesen grundlegend – eine Entwicklung, {{gap_1}} sich kein Schulsystem entziehen kann. Moderne Technologien bieten enorme Möglichkeiten: Lernplattformen ermöglichen individuell angepasstes Lernen, digitale Lehrmittel machen Unterricht anschaulicher, und Videokonferenzen erlauben eine ortsunabhängige Wissensvermittlung. Schülerinnen und Schüler können Lerninhalte in ihrem eigenen Tempo bearbeiten und sofort Feedback zu ihren Leistungen erhalten. Experten sind überzeugt, {{gap_2}} digitale Kompetenzen in der heutigen Berufswelt unverzichtbar sind und bereits in der Grundschule gezielt gefördert werden sollten. {{gap_3}} stellt der ungleiche Zugang zu digitalen Geräten und schnellem Internet eine erhebliche Herausforderung dar, {{gap_4}} er soziale Ungleichheiten im Bildungsbereich verschärfen kann.

Ein weiteres zentrales Problem ist die Qualität der digitalen Bildungsinhalte. Nicht jede App und nicht jede Plattform leistet tatsächlich einen pädagogischen Mehrwert. Lehrkräfte müssen {{gap_5}} in der Lage sein, digitale Werkzeuge kritisch zu beurteilen und sinnvoll in den Unterricht zu integrieren. Dies erfordert eine umfassende Ausbildung und regelmäßige Fortbildungsmaßnahmen, {{gap_6}} viele Schulen bisher noch nicht ausreichend anbieten. {{gap_7}} digitale Medien den Unterricht ergänzen, sollten grundlegende Fähigkeiten wie Lesen, Schreiben und kritisches Denken nicht vernachlässigt werden.

Gleichzeitig birgt die Digitalisierung Risiken {{gap_8}} Datenschutz und digitale Sicherheit. Schülerdaten werden von kommerziellen Plattformen gesammelt und ausgewertet, {{gap_9}} erhebliche ethische Fragen aufwirft. {{gap_10}} stehen Bildungspolitiker vor der Aufgabe, klare gesetzliche Rahmenbedingungen zu schaffen, die Datenschutz und digitale Chancen gleichermaßen sicherstellen.
EXAMPLE;

        $hoerenTeil1Example1 = <<<'EXAMPLE'
Guten Tag. Hier sind die Nachrichten aus den Regionen. Sie hören jetzt fünf Meldungen aus Stadtleben, Verkehr, Gesundheit, Forschung und Veranstaltungen.

Berlin. In mehreren Bezirken startet heute ein neues Programm zur Begrünung von Schulhöfen. Nach Angaben der Senatsverwaltung sollen bis zum Sommer zusätzliche Bäume gepflanzt, schattige Sitzbereiche geschaffen und versiegelte Flächen teilweise geöffnet werden. Die Arbeiten beginnen zunächst an acht Schulen.

Bonn. Wegen umfangreicher Gleisarbeiten kommt es auf der Strecke zwischen Bonn und Köln noch bis Freitag zu erheblichen Einschränkungen. Mehrere Regionalzüge fallen in den frühen Morgenstunden aus, außerdem müssen Fahrgäste mit zusätzlichen Umstiegen rechnen. Die Bahn empfiehlt, vor der Abfahrt die aktuellen Verbindungen zu prüfen.

Freiburg. Das städtische Gesundheitsamt beginnt kommende Woche mit einer neuen Informationskampagne zum besseren Schutz vor Zecken. In Kitas, Schulen und Sportvereinen sollen Eltern und Kinder erfahren, wie man nach Ausflügen in Parks oder Wäldern den Körper richtig kontrolliert. Zusätzlich werden kostenlose Merkblätter verteilt.

München. Ein Forschungsteam der Universität hat ein neues Sensorsystem für Fahrradwege vorgestellt. Die Technik soll Schäden auf dem Belag schneller erkennen und den zuständigen Stellen automatisch melden. Die Stadt will das System zunächst auf zwei stark genutzten Strecken testen.

Dresden. Für das Kulturfestival am ersten Maiwochenende sind deutlich mehr Veranstaltungen geplant als im Vorjahr. Neben Konzerten und Lesungen werden auch kostenlose Führungen durch mehrere Museen angeboten. Wer an den Führungen teilnehmen möchte, muss sich jedoch vorab online anmelden.
EXAMPLE;

        $hoerenTeil1Example2 = <<<'EXAMPLE'
Guten Abend. Sie hören eine Nachrichtensendung aus Stadt und Region mit fünf Meldungen zu Verkehr, Service und öffentlichen Angeboten.

Leipzig. Die Stadtbibliothek erweitert ab kommenden Monat ihre Öffnungszeiten und ist künftig auch an mehreren Sonntagen im Monat geöffnet. Besucher können bestellte Medien weiterhin an den Selbstverbuchungsstationen abholen. Für die Lernräume bleibt jedoch eine vorherige Online-Reservierung nötig.

Mainz. Wegen dringender Bauarbeiten wird die Zufahrt zur Rheinbrücke am Wochenende nur einspurig befahrbar sein. Die Stadt rechnet vor allem am Samstagvormittag mit Verzögerungen und empfiehlt Autofahrern, nach Möglichkeit auf Bus und Bahn umzusteigen.

Kassel. Im Bürgerzentrum startet nächste Woche eine neue Reihe kostenloser Energieberatungen für Mieter und Eigentümer. Fachleute informieren dort über sparsames Heizen, Dämmung und mögliche Förderprogramme. Für die Teilnahme ist eine Anmeldung erforderlich.

Ulm. Das städtische Klinikum testet derzeit einen digitalen Dienst, mit dem Patientinnen und Patienten Untersuchungstermine einfacher verschieben können. Nach Angaben der Klinik soll das neue System die telefonischen Wartezeiten deutlich verkürzen.

Bremen. Das Frühlingsfestival im Stadtpark wird in diesem Jahr um zusätzliche Musik- und Familienangebote erweitert. Mehrere Bereiche bleiben frei zugänglich, für Workshops mit begrenzter Teilnehmerzahl müssen Interessierte sich aber vorab registrieren.
EXAMPLE;

        $themes = [
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-1',
                'title' => 'Beschwerde ueber einen mangelhaften Sprachkurs',
                'prompt_seed' => 'Halbformelle E-Mail mit einer konkreten Beschwerde, sachlichem Ton, Bitte um Loesung und Bezug auf Alltag oder Weiterbildung.',
                'golden_example' => $teil1Example1,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 10,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-1',
                'title' => 'Anfrage zu einem Weiterbildungsseminar',
                'prompt_seed' => 'Halbformelle Anfrage per E-Mail zu Organisation, Teilnahmebedingungen, Kosten und Nutzen eines Seminars oder Kurses.',
                'golden_example' => $teil1Example2,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 20,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-1',
                'title' => 'Reklamation einer verspaeteten Onlinebestellung',
                'prompt_seed' => 'Realistische Reklamationsmail zu Lieferung, Kundenservice, Rueckerstattung oder Ersatz in einem alltagsnahen Kontext.',
                'golden_example' => $teil1Example3,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 30,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-1',
                'title' => 'Terminverschiebung fuer ein Bewerbungsgespraech',
                'prompt_seed' => 'Halbformelle E-Mail mit Begruendung fuer eine Terminveraenderung, Hoeflichkeit, Alternativvorschlaegen und Bitte um Bestaetigung.',
                'golden_example' => $teil1Example4,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 40,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-1',
                'title' => 'Rueckfrage zu einer Wohnungsausschreibung',
                'prompt_seed' => 'Nachricht an Vermieter oder Hausverwaltung mit Rueckfragen zu Lage, Mietbedingungen, Besichtigung und persoenlicher Situation.',
                'golden_example' => $teil1Example2,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 50,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-1',
                'title' => 'Feedback zu einem Freiwilligenprojekt',
                'prompt_seed' => 'Persoenliche, aber strukturierte E-Mail mit Rueckmeldung zu Organisation, Erfahrungen, Verbesserungsvorschlaegen und Motivation.',
                'golden_example' => $teil1Example4,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 60,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-2',
                'title' => 'Digitalisierung im Alltag',
                'prompt_seed' => 'Sachtext von allgemeinem Interesse ueber Chancen und Probleme digitaler Gewohnheiten im Alltag, mit mehreren Perspektiven.',
                'golden_example' => $teil2Example4,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 10,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-2',
                'title' => 'Homeoffice und neue Arbeitsmodelle',
                'prompt_seed' => 'Magazinartiger Sachtext ueber Veraenderungen der Arbeitswelt, Vor- und Nachteile flexibler Modelle und gesellschaftliche Folgen.',
                'golden_example' => $teil2Example2,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 20,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-2',
                'title' => 'Klimaschutz im privaten Leben',
                'prompt_seed' => 'Sachtext ueber individuelle und gesellschaftliche Verantwortung beim Klimaschutz mit konkreten Beispielen und Gegenpositionen.',
                'golden_example' => $teil2Example3,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 30,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-2',
                'title' => 'Gesundes Leben in der Stadt',
                'prompt_seed' => 'Allgemeinverstaendlicher Text ueber Bewegung, Stress, Erholung und Rahmenbedingungen fuer Gesundheit in urbanen Raeumen.',
                'golden_example' => $teil2Example1,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 40,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-2',
                'title' => 'Soziale Medien und oeffentliche Meinung',
                'prompt_seed' => 'Sachtext ueber Einfluss sozialer Medien auf Information, Diskussionen, Meinungsbildung und Verantwortung der Nutzer.',
                'golden_example' => $teil2Example4,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 50,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'sprachbausteine-teil-2',
                'title' => 'Lebenslanges Lernen',
                'prompt_seed' => 'Sachtext ueber Weiterbildung, berufliche Veraenderung, Motivation Erwachsener und Bedeutung neuer Kompetenzen.',
                'golden_example' => $teil2Example2,
                'source_label' => 'Curated B2 Allgemein theme catalog',
                'sort_order' => 60,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'hoeren-teil-1',
                'title' => 'Meldungen aus Stadt und Alltag',
                'prompt_seed' => 'Neutral gelesene Nachrichtensendung mit fuenf klar getrennten Meldungen aus Stadtleben, Service oder Kultur. Keine Dialoge, nur eine Stimme.',
                'golden_example' => $hoerenTeil1Example1,
                'source_label' => 'Curated B2 Allgemein listening catalog',
                'sort_order' => 10,
            ],
            [
                'exam_slug' => 'telc-b2',
                'module_slug' => 'hoeren-teil-1',
                'title' => 'Regionalnachrichten',
                'prompt_seed' => 'Sachliche Regionalnachrichtensendung mit genau fuenf klar getrennten Informationspunkten und einer neutralen Sprecherstimme.',
                'golden_example' => $hoerenTeil1Example2,
                'source_label' => 'Curated B2 Allgemein listening catalog',
                'sort_order' => 20,
            ],
        ];

        foreach ($themes as $theme) {
            QuestionGenerationTheme::query()->updateOrCreate(
                [
                    'exam_slug' => $theme['exam_slug'],
                    'module_slug' => $theme['module_slug'],
                    'title' => $theme['title'],
                ],
                [
                    'prompt_seed' => $theme['prompt_seed'],
                    'golden_example' => $theme['golden_example'],
                    'source_label' => $theme['source_label'],
                    'source_url' => null,
                    'notes' => null,
                    'status' => QuestionGenerationTheme::STATUS_APPROVED,
                    'last_preview_payload' => null,
                    'last_previewed_at' => null,
                    'is_active' => true,
                    'sort_order' => $theme['sort_order'],
                ],
            );
        }

        QuestionGenerationTheme::query()
            ->where('exam_slug', 'telc-b2')
            ->where('module_slug', 'hoeren-teil-1')
            ->whereIn('title', [
                'Kurze Meldungen aus Stadt und Alltag',
                'Regionalnachrichten kompakt',
            ])
            ->delete();
    }
}
