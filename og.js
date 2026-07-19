// Generates Open Graph images for Theatergruppe Freispiel into public/og
const fs = require('fs');
const path = require('path');

let sharp = null;
let sharpUnavailableReason = '';
try {
  sharp = require('sharp');
} catch (error) {
  sharpUnavailableReason = (error instanceof Error ? error.message : String(error)).split('\n')[0];
}

const OUT_DIR = path.join(process.cwd(), 'public', 'og');
const WIDTH = 1200;
const HEIGHT = 630;
const CARD = { x: 458, y: 64, width: 618, height: 502, radius: 26 };

const BRAND = 'Theatergruppe Freispiel';
const FOOTER_DOMAIN = 'tg-freispiel.de';

const STAGE_IMAGE_PATH = path.join(process.cwd(), 'public', 'images', 'stage-background-mystical.webp');
const NUNITO_FONT_PATH = path.join(process.cwd(), 'assets', 'fonts', 'nunito-variablefontwght.woff2');

const pages = [
  {
    fileName: 'start',
    chip: 'THEATER',
    title: `Theatergruppe Freispiel in Dormagen`,
    subtitle: 'Proben, Termine und Theater zum Mitmachen',
    titleSize: 58,
    subtitleSize: 22,
  },
  {
    fileName: 'proben',
    chip: 'PROBEN',
    title: `Proben & Schauspieltraining`,
    subtitle: `Improvisation, Ensemblearbeit und Probenausschnitte`,
    titleSize: 64,
    subtitleSize: 23,
  },
  {
    fileName: 'termine',
    chip: 'TERMINE',
    title: `Termine & Probestunden`,
    subtitle: 'Aktuelle Termine der Theatergruppe Freispiel',
    titleSize: 62,
    subtitleSize: 23,
  },
  {
    fileName: 'anmeldung',
    chip: 'MITMACHEN',
    title: `Anmeldung zur Probestunde`,
    subtitle: 'Unverbindlich mitmachen und die Bühne erleben',
    titleSize: 60,
    subtitleSize: 22,
  },
  {
    fileName: 'kontakt',
    chip: 'KONTAKT',
    title: 'Kontakt',
    subtitle: `Fragen, Zusammenarbeit und Nachrichten an Freispiel`,
    titleSize: 68,
    subtitleSize: 23,
  },
  {
    fileName: 'datenschutz',
    chip: 'RECHTLICH',
    title: 'Datenschutz',
    subtitle: `Informationen zum Umgang mit personenbezogenen Daten`,
    titleSize: 66,
    subtitleSize: 22,
  },
];

const themes = {
  start: { gold: '#e3b24a', accent: '#7c0d16', glow: '#f7d99b' },
  proben: { gold: '#e1b762', accent: '#6f1022', glow: '#f6d9a0' },
  termine: { gold: '#eed27a', accent: '#83131d', glow: '#f9dda0' },
  anmeldung: { gold: '#f0cb78', accent: '#95131f', glow: '#ffe2aa' },
  kontakt: { gold: '#f1cf86', accent: '#6e1222', glow: '#f8deaa' },
  datenschutz: { gold: '#d5d7e1', accent: '#505a6b', glow: '#eef1f7' },
};

let stageImageBuffer = null;
try {
  stageImageBuffer = fs.readFileSync(STAGE_IMAGE_PATH);
} catch (error) {
  console.warn('Stage background not found at public/images/stage-background-mystical.webp — OG images will fall back to a dark theater gradient.');
}

let nunitoFontFace = '';
try {
  const nunito = fs.readFileSync(NUNITO_FONT_PATH);
  nunitoFontFace = `@font-face{font-family:"Nunito";font-style:normal;font-weight:200 900;font-display:swap;src:url(data:font/woff2;base64,${nunito.toString('base64')}) format('woff2');}`;
} catch (error) {
  // no-op
}

function escapeXml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function toDataUri(buffer, mimeType) {
  return `data:${mimeType};base64,${buffer.toString('base64')}`;
}

function wrapLines(text, maxChars) {
  const paragraphs = String(text || '').split(/\n+/);
  const lines = [];

  for (const paragraph of paragraphs) {
    const words = paragraph.split(/\s+/).filter(Boolean);
    let line = '';

    for (const word of words) {
      const next = (line ? `${line} ${word}` : word).trim();
      if (next.length > maxChars && line) {
        lines.push(line);
        line = word;
      } else {
        line = next;
      }
    }

    if (line) lines.push(line);
  }

  return lines;
}

function computeTextLayout(text, availableWidth, { initialSize, minSize, factor, minChars }) {
  const safeText = String(text || '').trim() || BRAND;
  let fontSize = initialSize;
  let maxChars = Math.max(minChars, Math.floor(availableWidth / (fontSize * factor)));
  let lines = wrapLines(safeText, maxChars);

  while (fontSize > minSize) {
    const longest = Math.max(1, ...lines.map((line) => line.length));
    if (longest * fontSize * factor <= availableWidth) {
      break;
    }

    fontSize -= 2;
    maxChars = Math.max(minChars, Math.floor(availableWidth / (fontSize * factor)));
    lines = wrapLines(safeText, maxChars);
  }

  return {
    fontSize,
    lineHeight: Math.round(fontSize * 1.08),
    lines,
  };
}

async function buildBackgroundData(stageBuffer) {
  if (!stageBuffer) {
    return null;
  }

  if (!sharp) {
    return toDataUri(stageBuffer, 'image/webp');
  }

  const image = sharp(stageBuffer);
  const metadata = await image.metadata();

  if (!metadata.width || !metadata.height) {
    const fallbackBuffer = await sharp(stageBuffer).resize(WIDTH, HEIGHT, { fit: 'cover', position: 'centre' }).modulate({ brightness: 0.98, saturation: 1.08 }).sharpen(1.05).jpeg({ quality: 90, progressive: true }).toBuffer();

    return toDataUri(fallbackBuffer, 'image/jpeg');
  }

  const scale = Math.max(WIDTH / metadata.width, HEIGHT / metadata.height);
  const resizedWidth = Math.round(metadata.width * scale);
  const resizedHeight = Math.round(metadata.height * scale);
  const cropLeft = Math.max(0, Math.round((resizedWidth - WIDTH) / 2));
  const cropTop = Math.max(0, Math.min(resizedHeight - HEIGHT, Math.round((resizedHeight - HEIGHT) * 0.3)));

  const backgroundBuffer = await sharp(stageBuffer).resize(resizedWidth, resizedHeight).extract({ left: cropLeft, top: cropTop, width: WIDTH, height: HEIGHT }).modulate({ brightness: 0.98, saturation: 1.08 }).sharpen(1.05).jpeg({ quality: 90, progressive: true }).toBuffer();

  return toDataUri(backgroundBuffer, 'image/jpeg');
}

function makeSvg(page, theme, backgroundDataUrl) {
  const contentX = CARD.x + 46;
  const titleWidth = CARD.width - 92;

  const titleLayout = computeTextLayout(page.title, titleWidth, {
    initialSize: page.titleSize || 72,
    minSize: 48,
    factor: 0.515,
    minChars: 10,
  });

  const subtitleLayout = computeTextLayout(page.subtitle, titleWidth, {
    initialSize: page.subtitleSize || 24,
    minSize: 19,
    factor: 0.49,
    minChars: 18,
  });

  const titleY = CARD.y + 146;
  const titleGap = 34;
  const titleBottom = titleY + titleLayout.fontSize + (titleLayout.lines.length - 1) * titleLayout.lineHeight;
  const subtitleY = titleBottom + titleGap;

  const titleTspans = titleLayout.lines.map((line, index) => `<tspan x="${contentX}" dy="${index === 0 ? 0 : titleLayout.lineHeight}">${escapeXml(line)}</tspan>`).join('');
  const subtitleTspans = subtitleLayout.lines.map((line, index) => `<tspan x="${contentX}" dy="${index === 0 ? 0 : subtitleLayout.lineHeight}">${escapeXml(line)}</tspan>`).join('');

  const background = backgroundDataUrl ? `<image href="${backgroundDataUrl}" x="0" y="0" width="${WIDTH}" height="${HEIGHT}" preserveAspectRatio="xMidYMax slice" />` : `<rect width="100%" height="100%" fill="#0d0d0d" />`;

  return `
<svg width="${WIDTH}" height="${HEIGHT}" viewBox="0 0 ${WIDTH} ${HEIGHT}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="rightShade" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#060303" stop-opacity="0.00" />
      <stop offset="58%" stop-color="#060303" stop-opacity="0.10" />
      <stop offset="100%" stop-color="#060303" stop-opacity="0.26" />
    </linearGradient>
    <linearGradient id="cardFill" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="rgba(16,12,12,0.42)" />
      <stop offset="52%" stop-color="rgba(12,10,10,0.36)" />
      <stop offset="100%" stop-color="rgba(10,8,8,0.50)" />
    </linearGradient>
    <radialGradient id="cardGlow" cx="0.12" cy="0.10" r="1.08">
      <stop offset="0%" stop-color="${theme.gold}" stop-opacity="0.08" />
      <stop offset="50%" stop-color="${theme.gold}" stop-opacity="0.02" />
      <stop offset="100%" stop-color="${theme.gold}" stop-opacity="0.00" />
    </radialGradient>
    <radialGradient id="maskReveal" cx="16%" cy="18%" r="28%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.10" />
      <stop offset="44%" stop-color="#ffffff" stop-opacity="0.03" />
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0.00" />
    </radialGradient>
    <radialGradient id="vignette" cx="50%" cy="50%" r="76%">
      <stop offset="60%" stop-color="#000000" stop-opacity="0.00" />
      <stop offset="100%" stop-color="#000000" stop-opacity="0.42" />
    </radialGradient>
    <filter id="cardShadow" x="-20%" y="-20%" width="160%" height="170%">
      <feDropShadow dx="0" dy="18" stdDeviation="24" flood-color="#000000" flood-opacity="0.28" />
    </filter>
    <filter id="textShadow" x="-20%" y="-20%" width="140%" height="160%">
      <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#000000" flood-opacity="0.24" />
    </filter>
    <style><![CDATA[
      ${nunitoFontFace}
      .brand { font-family: "Nunito", Arial, sans-serif; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; }
      .title { font-family: "Nunito", Arial, sans-serif; font-weight: 900; }
      .subtitle { font-family: "Nunito", Arial, sans-serif; font-weight: 700; }
      .micro { font-family: "Nunito", Arial, sans-serif; font-weight: 700; letter-spacing: 0.04em; }
      .chip { font-family: "Nunito", Arial, sans-serif; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; }
    ]]></style>
  </defs>

  ${background}
  <rect width="100%" height="100%" fill="url(#maskReveal)" />
  <rect width="100%" height="100%" fill="url(#rightShade)" />
  <rect width="100%" height="100%" fill="url(#vignette)" />

  <g filter="url(#cardShadow)">
    <rect x="${CARD.x}" y="${CARD.y}" width="${CARD.width}" height="${CARD.height}" rx="${CARD.radius}" ry="${CARD.radius}" fill="url(#cardFill)" />
    <rect x="${CARD.x}" y="${CARD.y}" width="${CARD.width}" height="${CARD.height}" rx="${CARD.radius}" ry="${CARD.radius}" fill="url(#cardGlow)" />
    <rect x="${CARD.x + 46}" y="${CARD.y + 52}" width="92" height="2" rx="1" fill="${theme.gold}" fill-opacity="0.92" />
  </g>

  <g filter="url(#textShadow)">
    <text x="${contentX}" y="${CARD.y + 40}" class="brand" font-size="17" fill="${theme.gold}">${escapeXml(BRAND)}</text>

    <g>
      <rect x="${CARD.x + CARD.width - 148}" y="${CARD.y + 22}" width="108" height="36" rx="18" ry="18" fill="rgba(11,9,9,0.18)" />
      <text x="${CARD.x + CARD.width - 94}" y="${CARD.y + 45}" class="chip" font-size="15" text-anchor="middle" fill="${theme.gold}">${escapeXml(page.chip)}</text>
    </g>

    <text x="${contentX}" y="${titleY}" class="title" font-size="${titleLayout.fontSize}" fill="#fff7e6">${titleTspans}</text>
    <text x="${contentX}" y="${subtitleY}" class="subtitle" font-size="${subtitleLayout.fontSize}" fill="rgba(245,236,219,0.90)">${subtitleTspans}</text>

    <text x="${contentX}" y="${CARD.y + CARD.height - 38}" class="micro" font-size="23" fill="${theme.gold}">${escapeXml(FOOTER_DOMAIN)}</text>
  </g>
</svg>`.trim();
}

function logSharpFallbackNotice() {
  if (sharp) return;
  console.warn('sharp could not be loaded. Generating SVG files only. To also create JPG files in this Yarn project, reinstall dependencies for the current platform, e.g. yarn install --force or yarn add --dev sharp');
  if (sharpUnavailableReason) console.warn(`Reason: ${sharpUnavailableReason}`);
}

async function ensureOutDir() {
  await fs.promises.mkdir(OUT_DIR, { recursive: true });
}

async function generateOne(page, backgroundDataUrl) {
  const theme = themes[page.fileName] || themes.start;
  const svg = makeSvg(page, theme, backgroundDataUrl);
  const svgFile = path.join(OUT_DIR, `${page.fileName}.svg`);
  const jpgFile = path.join(OUT_DIR, `${page.fileName}.jpg`);

  await fs.promises.writeFile(svgFile, svg, 'utf8');

  if (!sharp) {
    process.stdout.write(`✓ ${path.relative(process.cwd(), svgFile)} (SVG only)\n`);
    return;
  }

  await sharp(Buffer.from(svg)).jpeg({ quality: 92, progressive: true, chromaSubsampling: '4:4:4' }).toFile(jpgFile);
  process.stdout.write(`✓ ${path.relative(process.cwd(), jpgFile)} (and svg)\n`);
}

(async function main() {
  await ensureOutDir();
  logSharpFallbackNotice();

  const backgroundDataUrl = await buildBackgroundData(stageImageBuffer);

  for (const page of pages) {
    await generateOne(page, backgroundDataUrl);
  }
})().catch((error) => {
  console.error('OG generation failed:', error);
  process.exit(1);
});
