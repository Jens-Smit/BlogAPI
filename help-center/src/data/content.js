window.GASTRO_OS_HELP = (function () {
  'use strict';

  const contact = {
    email: 'support@visiongastro.de',
    phone: '+49 (0) 000 000 000 0',
    hours: 'Mo–Fr, 9:00–17:00 Uhr',
    responseTime: 'Antwort in der Regel innerhalb von 24 Stunden'
  };

  const categories = [
    {
      id: 'first-steps',
      title: 'Erste Schritte',
      icon: '🚀',
      description: 'Account erstellen, Betrieb einrichten und das Team einladen.',
      articles: [
        {
          id: 'account-erstellen',
          title: 'Account erstellen',
          steps: [
            'Registrieren Sie Ihren Betrieb unter app.visiongastro.com.',
            'Bestätigen Sie Ihre E-Mail-Adresse über den Aktivierungslink.',
            'Richten Sie die grundlegenden Räume und Öffnungszeiten ein.'
          ]
        },
        {
          id: 'team-einladen',
          title: 'Team einladen und Rollen vergeben',
          steps: [
            'Öffnen Sie den Bereich „Mitarbeiter“ im Dashboard.',
            'Fügen Sie Mitarbeiter per E-Mail-Einladung hinzu.',
            'Weisen Sie individuelle Rollen und Rechte zu (Admin, Schichtleiter, Mitarbeiter).'
          ]
        },
        {
          id: 'erste-dienstplan',
          title: 'Ersten Dienstplan erstellen',
          steps: [
            'Wechseln Sie zum Modul „Dienstplan“.',
            'Erstellen Sie den Plan per Drag & Drop oder über die KI-Zuweisung.',
            'Veröffentlichen Sie den Plan – das Team wird automatisch benachrichtigt.'
          ]
        }
      ]
    },
    {
      id: 'dienstplanung',
      title: 'Dienstplanung',
      icon: '🗓️',
      description: 'Intelligente Planung, Konfliktprüfung und automatische Zuweisung.',
      articles: [
        {
          id: 'ki-zuweisung',
          title: 'KI-Zuweisung nutzen',
          steps: [
            'Öffnen Sie den Dienstplan und klicken Sie auf „KI-Zuweisung“.',
            'Prüfen Sie die vorgeschlagenen Zuweisungen und Konfliktprüfungen.',
            'Bestätigen Sie den Vorschlag oder passen Sie einzelne Schichten manuell an.'
          ]
        },
        {
          id: 'konflikte',
          title: 'Konflikte im Dienstplan lösen',
          steps: [
            'Konfliktwarnungen werden im Dienstplan farblich markiert.',
            'Öffnen Sie die betroffene Schicht und wählen Sie eine Alternative.',
            'Speichern Sie den Plan – Konflikte werden erneut geprüft.'
          ]
        }
      ]
    },
    {
      id: 'zeiterfassung',
      title: 'Zeiterfassung',
      icon: '⏱️',
      description: 'Arbeits- und Pausenzeiten live via App erfassen.',
      articles: [
        {
          id: 'einstempeln',
          title: 'Arbeitszeiten erfassen (Einstempeln)',
          steps: [
            'Mitarbeiter öffnen die Mobile App und melden sich an.',
            'Per „Einstempeln“ startet die Zeiterfassung automatisch.',
            'Pausen werden separat erfasst und dem Arbeitstag zugeordnet.'
          ]
        },
        {
          id: 'urlaub-beantragen',
          title: 'Urlaub beantragen',
          steps: [
            'In der App unter „Urlaub“ den Zeitraum auswählen.',
            'Antrag absenden – die Schichtleitung erhält eine Benachrichtigung.',
            'Der Status des Antrags ist jederzeit in der App sichtbar.'
          ]
        }
      ]
    },
    {
      id: 'reservierung',
      title: 'Reservierung & Gäste',
      icon: '🍽️',
      description: 'Tischreservierungen mit interaktiver 3D-Raumplanung.',
      articles: [
        {
          id: '3d-raumplanung',
          title: '3D-Raumplanung einrichten',
          steps: [
            'Öffnen Sie das Reservierungs-Modul und wählen Sie „Raumplanung“.',
            'Legen Sie Tische, Bereiche und Gehwege in der 3D-Ansicht fest.',
            'Speichern Sie die Konfiguration – sie steht sofort für Buchungen bereit.'
          ]
        },
        {
          id: 'events',
          title: 'Geschlossene Gesellschaften planen',
          steps: [
            'Erstellen Sie ein Event mit spezieller Raumkonfiguration.',
            'Hinterlegen Sie Menüs, Pflegepersonal und Zeitfenster.',
            'Teilen Sie den Buchungslink direkt mit Ihren Gästen.'
          ]
        }
      ]
    },
    {
      id: 'controlling',
      title: 'Controlling & DATEV',
      icon: '📊',
      description: 'Echtzeit-Auswertungen und DATEV-Export für den Steuerberater.',
      articles: [
        {
          id: 'datev-export',
          title: 'DATEV-Export durchführen',
          steps: [
            'Öffnen Sie den Bereich „Berichte“ und wählen Sie den Zeitraum.',
            'Klicken Sie auf „DATEV-Export“ – die Lohndaten werden validiert.',
            'Laden Sie die CSV-Datei herunter; Ihr Steuerberater kann sie direkt in DATEV Lohn und Gehalt einspielen.'
          ]
        }
      ]
    },
    {
      id: 'account-verwaltung',
      title: 'Account & Sicherheit',
      icon: '🔐',
      description: 'Passwort, DSGVO und Zugriff verwalten.',
      articles: [
        {
          id: 'passwort-zuruecksetzen',
          title: 'Passwort zurücksetzen',
          steps: [
            'Klicken Sie auf der Anmeldeseite auf „Passwort vergessen“.',
            'Folgen Sie dem Link in der E-Mail und setzen Sie ein neues Passwort.',
            'Alle aktiven Sitzungen werden aus Sicherheitsgründen abgemeldet.'
          ]
        },
        {
          id: 'dsgvo',
          title: 'DSGVO und Datenlöschung',
          steps: [
            'Alle personenbezogenen Daten werden DSGVO-konform in Deutschland verarbeitet.',
            'Anfragen zur Datenlöschung richten Sie an den Support.',
            'Betriebsdaten können im Dashboard jederzeit exportiert werden.'
          ]
        }
      ]
    }
  ];

  const faq = [
    {
      question: 'Wann startet GastroOS offiziell?',
      answer: 'Der vollständige Public Launch ist für das 1. Quartal 2027 geplant. Early-Access-Partner erhalten deutlich früher Zugang.'
    },
    {
      question: 'Gibt es eine Mobile App?',
      answer: 'Ja, parallel entwickeln wir eine App für iOS und Android zum Einstempeln, Dienstpläne einsehen und Urlaub beantragen.'
    },
    {
      question: 'Wie funktioniert die DATEV-Schnittstelle?',
      answer: 'Am Monatsende exportieren Sie mit einem Klick validierte Lohndaten (inkl. Feiertags- und Nachtzuschlägen) als CSV-Datei im DATEV-Format.'
    },
    {
      question: 'Wo werden meine Daten gespeichert?',
      answer: 'Alle Daten werden DSGVO-konform auf Servern in Deutschland verarbeitet und gespeichert.'
    },
    {
      question: 'Kann ich mehrere Betriebe verwalten?',
      answer: 'Ja, im Dashboard können Sie beliebig viele Betriebe anlegen und zwischen ihnen wechseln.'
    },
    {
      question: 'Wie erreiche ich den Support?',
      answer: 'Per E-Mail an support@visiongastro.de oder über das Kontaktformular auf dieser Seite – Antwort in der Regel innerhalb von 24 Stunden.'
    }
  ];

  return { contact, categories, faq };
})();
