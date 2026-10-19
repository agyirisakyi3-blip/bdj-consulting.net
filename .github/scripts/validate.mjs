import { readFileSync, readdirSync, existsSync } from 'fs';

const files = readdirSync('.').filter((f) => f.endsWith('.html'));
let blocks = 0;
let bad = 0;

for (const f of files) {
  const s = readFileSync(f, 'utf8');
  const re = /<script type="application\/ld\+json">([\s\S]*?)<\/script>/g;
  let m;
  while ((m = re.exec(s))) {
    blocks++;
    try {
      JSON.parse(m[1]);
    } catch (e) {
      bad++;
      console.log('BAD', f, e.message);
    }
  }
}

let sitemap;
try {
  sitemap = readFileSync('sitemap.xml', 'utf8');
} catch (e) {
  bad++;
  console.log('BAD sitemap.xml introuvable');
  sitemap = '';
}

if (sitemap && !/<\/urlset>/.test(sitemap)) {
  bad++;
  console.log('BAD', 'sitemap.xml', 'balise de fermeture absente');
}

const urls = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1]);
for (const u of urls) {
  const path = u.replace(/^https:\/\/(?:www\.)?bdj-consulting\.net\//, '').split('#')[0];
  const local = path === '' ? 'index.html' : path;
  if (!existsSync(local)) {
    bad++;
    console.log('BAD sitemap cible manquante:', local);
  }
}

console.log(`jsonld blocks=${blocks} bad=${bad}`);
if (bad > 0) process.exit(1);