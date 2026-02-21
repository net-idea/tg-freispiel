// Generates Open Graph images for Theatergruppe Freispiel into public/og
const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const OUT_DIR = path.join(process.cwd(), 'public', 'og');
// Use common OG size (recommended by FB/Twitter): 1200x630
const WIDTH = 1200;
const HEIGHT = 630;

const PAD_LEFT = 80;
const PAD_TOP = 70;
const PAD_RIGHT = 40;
const LOGO_PADDING_Y = 40;

const BRAND = 'Theatergruppe Freispiel';
const BRAND_SUBLINE = 'Unique Surfing';
const FOOTER_DOMAIN = 'Theatergruppe Freispiel.de';

// IMPORTANT: fileName must match the expected basename from content/_pages.php (e.g. /assets/og/home.jpg)
const pages = [
  { fileName: 'start', title: 'Theatergruppe Freispiel', subtitle: 'Professionelles Web Hosting & Managed IT' },
  { fileName: 'home', title: 'Theatergruppe Freispiel', subtitle: 'Professionelles Web Hosting & Managed IT' },
  { fileName: 'services', title: 'Services', subtitle: 'Webentwicklung, Hosting & digitale Lösungen' },
  { fileName: 'entwicklung', title: 'Entwicklung', subtitle: 'Internetseiten, Webshops & Plattformen' },
  { fileName: 'hosting', title: 'Hosting', subtitle: 'Sicher, zuverlässig & individuell betreut' },
  { fileName: 'kontakt', title: 'Kontakt', subtitle: 'Beratung, Support & pragmatische Lösungen' },
  { fileName: 'impressum', title: 'Impressum', subtitle: 'Rechtliche Angaben & Kontakt' },
  { fileName: 'datenschutz', title: 'Datenschutz', subtitle: 'Informationen zur Verarbeitung personenbezogener Daten' },
];

// Palettes aligned to Theatergruppe Freispiel brand (greens + supporting contrasts)
const palettes = {
  start: ['#008000', '#66b366', '#85c285', '#064a2c'],
  home: ['#008000', '#66b366', '#85c285', '#064a2c'],
  services: ['#66b366', '#4CCBFF', '#1476FF', '#072244'],
  entwicklung: ['#85c285', '#4CCBFF', '#1D66C0', '#0E3450'],
  hosting: ['#66b366', '#00A0C6', '#0E6B8A', '#062C3A'],
  kontakt: ['#66b366', '#00B39F', '#0E7460', '#053532'],
  impressum: ['#D5E7F3', '#94BBD3', '#38617D', '#1F3A4C'],
  datenschutz: ['#B8FFF1', '#58DCCF', '#0E768A', '#064754'],
};

// Use the site PNG logo instead of favicon.svg; fallback to no logo if not present
const LOGO_IMG_PATH = path.join(process.cwd(), 'assets', 'images', 'Theatergruppe Freispiel-logo.png');
let logoBuffer = null;
try {
  logoBuffer = fs.readFileSync(LOGO_IMG_PATH);
} catch (e) {
  console.warn('Logo image not found at assets/images/Theatergruppe Freispiel-logo.png — continuing without embedded logo.');
}

// Prefer a local font if present; otherwise fall back to system fonts.
// (We keep this optional so CI doesn't break if the font isn't in the repo)
const NUNITO_FONT_PATH = path.join(process.cwd(), 'assets', 'fonts', 'nunito-variablefontwght.woff2');
let nunitoFontFace = '';
try {
  const nunito = fs.readFileSync(NUNITO_FONT_PATH);
  nunitoFontFace = `@font-face{font-family:"Nunito";font-style:normal;font-weight:200 900;font-display:swap;src:url(data:font/woff2;base64,${nunito.toString('base64')}) format('woff2');}`;
} catch (e) {
  // no-op
}

function wrapLines(text, maxChars) {
  const words = (text || '').toString().split(/\s+/).filter(Boolean);
  const lines = [];
  let line = '';
  for (const w of words) {
    if ((line + ' ' + w).trim().length > maxChars) {
      if (line) lines.push(line);
      line = w;
    } else {
      line = (line ? line + ' ' : '') + w;
    }
  }
  if (line) lines.push(line);
  return lines;
}

function escapeXml(str) {
  return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// Estimate char width factor for Nunito bold (rough average)
const CHAR_WIDTH_FACTOR = 0.55; // width ≈ factor * fontSize

function computeDynamicTitleLayout(title, availableWidth) {
  const safeTitle = (title || '').toString().trim() || BRAND;

  let fontSize = 82;

  let maxChars = Math.max(8, Math.floor(availableWidth / (fontSize * CHAR_WIDTH_FACTOR)));
  let lines = wrapLines(safeTitle, maxChars);
  let longest = Math.max(1, ...lines.map((l) => l.length));

  let neededScale = availableWidth / (longest * fontSize * CHAR_WIDTH_FACTOR);
  if (neededScale < 1) fontSize = Math.max(54, Math.round(fontSize * neededScale));

  maxChars = Math.max(8, Math.floor(availableWidth / (fontSize * CHAR_WIDTH_FACTOR)));
  lines = wrapLines(safeTitle, maxChars);
  longest = Math.max(1, ...lines.map((l) => l.length));

  neededScale = availableWidth / (longest * fontSize * CHAR_WIDTH_FACTOR);
  if (neededScale < 1) fontSize = Math.max(50, Math.floor(fontSize * neededScale));

  return { fontSize, lines };
}

function buildGradientStops(colors) {
  if (!colors || colors.length === 0) colors = ['#5EE7FF', '#0066B2'];
  if (colors.length < 2) colors = [colors[0], colors[0]];

  const stops = [];
  const lastIndex = colors.length - 1;
  colors.forEach((col, i) => {
    const offset = ((i / lastIndex) * 100).toFixed(1);
    stops.push(`<stop offset="${offset}%" stop-color="${col}"/>`);
  });
  return stops.join('');
}

function makeSVG({ title, subtitle, brand, brandSubline, colors }) {
  const padLeft = PAD_LEFT,
    padTop = PAD_TOP,
    padRight = PAD_RIGHT;

  const logoPaddingY = LOGO_PADDING_Y;
  const logoHeight = HEIGHT - logoPaddingY * 2;
  const logoWidth = logoHeight * 1.05;
  const logoX = WIDTH - logoWidth - padRight;
  const logoY = logoPaddingY;

  const contentRight = logoX - 50;
  const availableWidth = contentRight - padLeft;

  // Dynamic title sizing & wrapping
  const { fontSize: titleFontSize, lines: titleLines } = computeDynamicTitleLayout(title, availableWidth);
  const titleLineGap = Math.round(titleFontSize * 1.05);

  // Subtitle sizing: relate to title size but capped
  const subtitleFontSize = Math.min(40, Math.max(28, Math.round(titleFontSize * 0.48)));
  const subtitleLineGap = Math.round(subtitleFontSize * 1.15);
  const subtitleMaxChars = Math.max(10, Math.floor(availableWidth / (subtitleFontSize * 0.52)));
  const subtitleLines = wrapLines(subtitle, subtitleMaxChars);

  // Vertical layout: center title block a bit above center
  const titleBlockHeight = titleFontSize + (titleLines.length - 1) * titleLineGap;
  const titleCenterRef = 260;
  const titleYStart = Math.max(padTop + titleFontSize + 40, Math.round(titleCenterRef - titleBlockHeight / 2));
  const subtitleYStart = titleYStart + titleBlockHeight + Math.round(subtitleFontSize * 0.35);

  const titleTspans = titleLines.map((l, i) => `<tspan x="${padLeft}" dy="${i === 0 ? 0 : titleLineGap}">${escapeXml(l)}</tspan>`).join('');

  const subtitleTspans = subtitleLines.map((l, i) => `<tspan x="${padLeft}" dy="${i === 0 ? 0 : subtitleLineGap}">${escapeXml(l)}</tspan>`).join('');

  const lines = [];
  lines.push(`<svg width="${WIDTH}" height="${HEIGHT}" viewBox="0 0 ${WIDTH} ${HEIGHT}" xmlns="http://www.w3.org/2000/svg">`);
  lines.push('<defs>');
  lines.push(`<linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">${buildGradientStops(colors)}</linearGradient>`);
  lines.push(`<linearGradient id="glow" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="rgba(255,255,255,0.28)"/><stop offset="100%" stop-color="rgba(255,255,255,0)"/></linearGradient>`);
  lines.push(
    `<filter id="dropshadow" height="140%"><feGaussianBlur in="SourceAlpha" stdDeviation="4"/><feOffset dx="0" dy="2" result="o"/><feComponentTransfer><feFuncA type="linear" slope="0.30"/></feComponentTransfer><feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge></filter>`
  );

  // Softer outline: smaller width + partial opacity
  const outlineClass = '.outline{paint-order:stroke fill;stroke:#01324e;stroke-opacity:.40;stroke-width:1.3;stroke-linejoin:round;}';
  if (nunitoFontFace) lines.push(`<style><![CDATA[${nunitoFontFace} text, tspan { font-kerning:normal; } ${outlineClass}]]></style>`);
  else lines.push(`<style><![CDATA[${outlineClass}]]></style>`);

  lines.push('</defs>');

  lines.push(`<rect width="100%" height="100%" fill="url(#bg)"/>`);
  lines.push(`<ellipse cx="${WIDTH * 0.72}" cy="${HEIGHT * 0.18}" rx="${WIDTH * 0.6}" ry="${HEIGHT * 0.6}" fill="url(#glow)" />`);

  lines.push(`<text x="${padLeft}" y="${padTop}" fill="#FFFFFF" class="outline" font-family="Nunito, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif" font-size="32" font-weight="800" letter-spacing="0.5" filter="url(#dropshadow)">${escapeXml(brand)}</text>`);
  if (brandSubline)
    lines.push(
      `<text x="${padLeft}" y="${padTop + 42}" fill="rgba(255,255,255,0.93)" class="outline" font-family="Nunito, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif" font-size="24" font-weight="700" filter="url(#dropshadow)">${escapeXml(brandSubline)}</text>`
    );

  lines.push(
    `<text x="${padLeft}" y="${titleYStart}" fill="#FFFFFF" class="outline" font-family="Nunito, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif" font-size="${titleFontSize}" font-weight="900" letter-spacing="0.5" filter="url(#dropshadow)">${titleTspans}</text>`
  );

  lines.push(
    `<text x="${padLeft}" y="${subtitleYStart}" fill="rgba(255,255,255,0.96)" class="outline" font-family="Nunito, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif" font-size="${subtitleFontSize}" font-weight="700" filter="url(#dropshadow)">${subtitleTspans}</text>`
  );

  // Note: Logo compositing is handled via Sharp after rasterizing the SVG (avoids SVG <image> compatibility issues)

  lines.push(`<text x="${padLeft}" y="${HEIGHT - 40}" fill="rgba(255,255,255,0.80)" class="outline" font-family="Nunito, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif" font-size="22" font-weight="700" filter="url(#dropshadow)">${escapeXml(FOOTER_DOMAIN)}</text>`);

  lines.push('</svg>');
  return { svg: lines.join('\n'), logoBox: { x: Math.round(logoX), y: Math.round(logoY), width: Math.round(logoWidth), height: Math.round(logoHeight) } };
}

async function ensureOutDir() {
  await fs.promises.mkdir(OUT_DIR, { recursive: true });
}

async function generateOne({ fileName, title, subtitle }) {
  const colors = palettes[fileName] || ['#008000', '#66b366'];
  const { svg, logoBox } = makeSVG({ title, subtitle, brand: BRAND, brandSubline: BRAND_SUBLINE, colors });

  const svgFile = path.join(OUT_DIR, `${fileName}.svg`);
  const jpgFile = path.join(OUT_DIR, `${fileName}.jpg`);

  await fs.promises.writeFile(svgFile, svg, 'utf8');

  // Rasterize the SVG to a PNG buffer first
  let baseImage = sharp(Buffer.from(svg)).png();

  // If we have a logo buffer, composite it on top at the calculated position
  if (logoBuffer) {
    const resizedLogo = await sharp(logoBuffer).resize(logoBox.width, logoBox.height, { fit: 'contain' }).toBuffer();
    baseImage = baseImage.composite([
      {
        input: resizedLogo,
        left: logoBox.x,
        top: logoBox.y,
      },
    ]);
  }

  // Write final JPG
  await baseImage.jpeg({ quality: 90, progressive: true, chromaSubsampling: '4:4:4' }).toFile(jpgFile);
  process.stdout.write(`✓ ${path.relative(process.cwd(), jpgFile)} (and svg)\n`);
}

(async function main() {
  await ensureOutDir();
  for (const p of pages) {
    await generateOne({
      fileName: p.fileName,
      title: p.title || BRAND,
      subtitle: p.subtitle || 'Webentwicklung, Hosting und digitale Lösungen',
    });
  }
})().catch((err) => {
  console.error('OG generation failed:', err);
  process.exit(1);
});
