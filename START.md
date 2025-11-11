# 🎭 Theatergruppe Freispiel - Setup Complete!

## ✅ Was wurde implementiert:

### 1. Windows Vista Aero Glass Effekt
- **Glasmorphismus-Design** mit backdrop-filter blur
- **Reflektierende Kanten** mit hellen Borders oben/links
- **Semi-transparente Hintergründe** mit Farbverläufen
- **Hover-Animationen** für alle interaktiven Elemente
- **3D-Effekte** für Buttons mit Glanz-Animation

**Angewendet auf:**
- ✨ Karten (.card)
- ✨ Navigation (Navbar) 
- ✨ Footer
- ✨ Formulare (Inputs)
- ✨ Buttons
- ✨ Alerts

### 2. Bühnenbild als Hintergrund
- **Bild-Pfad:** `assets/images/stage-background.png`
- **Verarbeitung:** Wird durch Webpack Encore verarbeitet und optimiert
- **Output:** `public/build/images/stage-background.[hash].png`
- **CSS:** Relativer Pfad `url('../images/stage-background.png')`
- **Effekt:** Semi-transparent (opacity: 0.2) mit fixed background

### 3. Development Script (develop.sh)
- **Startet automatisch:**
  - Webpack Encore im Watch-Mode
  - Symfony Development Server (Port 8000)
- **Features:**
  - Automatische Dependency-Installation
  - Cache-Clearing
  - Initial Asset Build
  - Beide Server parallel
  - Ein Ctrl+C beendet beide

---

## 🚀 Wie starte ich die Anwendung?

### Option 1: Mit develop.sh (Empfohlen)
```bash
cd tg-freispiel.de
./develop.sh
```

Das Script startet automatisch:
- 📦 Webpack auf http://localhost:8080
- 🚀 Symfony auf http://localhost:8000

**Mit Ctrl+C werden beide Server beendet.**

### Option 2: Manuell

**Terminal 1 - Assets Watch:**
```bash
cd tg-freispiel.de
yarn watch
```

**Terminal 2 - Symfony Server:**
```bash
cd tg-freispiel.de
symfony serve --no-tls --port=8000
```

---

## 🎨 Glas-Effekt anpassen

Bearbeite `assets/styles/app.css`:

### Blur-Stärke ändern:
```css
backdrop-filter: blur(12px);  /* Standard */
backdrop-filter: blur(16px);  /* Stärker */
backdrop-filter: blur(8px);   /* Schwächer */
```

### Transparenz ändern:
```css
background: rgba(13, 13, 13, 0.85);  /* Standard */
background: rgba(13, 13, 13, 0.95);  /* Weniger transparent */
background: rgba(13, 13, 13, 0.70);  /* Mehr transparent */
```

### Reflektionen anpassen:
```css
border-top: 1px solid rgba(255, 255, 255, 0.25);   /* Standard */
border-top: 2px solid rgba(255, 255, 255, 0.40);   /* Stärker */
border-top: 1px solid rgba(255, 255, 255, 0.15);   /* Schwächer */
```

**Nach Änderungen:**
```bash
yarn encore dev  # oder yarn watch läuft bereits
```

---

## 🖼️ Hintergrundbild ändern

1. Ersetze `assets/images/stage-background.png` mit deinem Bild
2. Baue Assets neu:
   ```bash
   yarn encore dev
   ```
3. Das Bild wird automatisch optimiert und mit Hash versehen

---

## 📁 Wichtige Dateien

```
tg-freispiel.de/
├── develop.sh                    # Development Script
├── assets/
│   ├── images/
│   │   └── stage-background.png  # Bühnenbild (Original)
│   ├── styles/
│   │   └── app.css               # CSS mit Glas-Effekt
│   └── app.js                    # JavaScript Entry
├── templates/
│   ├── base.html.twig            # Base Template
│   ├── _partials/
│   │   ├── navbar.html.twig      # Navigation mit Glas-Effekt
│   │   ├── footer.html.twig      # Footer mit Glas-Effekt
│   │   └── flash_messages.html.twig
│   └── home/
│       ├── index.html.twig       # Homepage
│       └── contact.html.twig     # Kontakt
└── public/build/                 # Kompilierte Assets
    ├── images/
    │   └── stage-background.[hash].png
    ├── app.css
    └── app.js
```

---

## 🔧 Nützliche Commands

### Assets
```bash
yarn encore dev          # Development Build
yarn watch               # Watch Mode (automatisch)
yarn build               # Production Build
```

### Symfony
```bash
symfony serve                        # Server starten
php bin/console cache:clear          # Cache leeren
composer install                     # PHP Dependencies
```

### Yarn
```bash
yarn install             # Node Dependencies installieren
```

---

## ✅ Checkliste zum Testen

Nach dem Start mit `./develop.sh` überprüfe:

- [ ] Seite lädt auf http://localhost:8000
- [ ] Hintergrundbild (Bühne) ist sichtbar
- [ ] Navigation hat Glas-Effekt (durchsichtig mit Blur)
- [ ] Karten haben Glas-Effekt mit Reflexionen
- [ ] Karten heben sich beim Hover leicht an
- [ ] Buttons haben Glanz-Animation beim Hover
- [ ] Form-Felder haben Glas-Effekt
- [ ] Footer hat Glas-Effekt
- [ ] Goldene Farbe (#d4af37) für Überschriften
- [ ] Dunkles Theme ist durchgängig

---

## 🎭 Farb-Palette

```css
--theater-dark: #1a1a1a;           /* Haupthintergrund */
--theater-darker: #0d0d0d;         /* Dunklerer Hintergrund */
--theater-accent: #8b0000;         /* Dunkelrot (Akzent) */
--theater-accent-light: #b22222;   /* Helleres Rot */
--theater-gold: #d4af37;           /* Gold (Überschriften) */
--theater-text: #e0e0e0;           /* Helle Textfarbe */
--theater-text-muted: #a0a0a0;     /* Gedämpfter Text */
```

---

## 🐛 Problembehebung

### Hintergrundbild wird nicht angezeigt
```bash
# Prüfe ob Bild existiert
ls -l assets/images/stage-background.png

# Assets neu bauen
yarn encore dev

# Cache leeren
php bin/console cache:clear
```

### Glas-Effekt wird nicht angezeigt
Der `backdrop-filter` wird möglicherweise nicht unterstützt:
- ✅ Chrome 76+
- ✅ Safari 9+
- ✅ Firefox 103+
- ❌ Ältere Browser: Graceful degradation (funktioniert, aber ohne Blur)

### develop.sh startet nicht
```bash
# Berechtigungen prüfen/setzen
chmod +x develop.sh

# Manuell testen
bash develop.sh
```

---

## 📚 Weitere Dokumentation

- `DESIGN.md` - Ausführliche Design-Dokumentation
- `CHANGELOG-GLASS.md` - Änderungen im Glas-Effekt Update
- `README.md` - Allgemeine Projekt-Dokumentation

---

## 🎉 Viel Erfolg!

Die Seite ist jetzt bereit mit einem professionellen, theatralischen Design! 🎭

Bei Fragen oder Problemen:
- Überprüfe die Browser-Konsole (F12)
- Schaue in `var/log/dev.log` für Symfony-Fehler
- Webpack-Fehler werden im Terminal angezeigt

**Viel Spaß beim Entwickeln!**
