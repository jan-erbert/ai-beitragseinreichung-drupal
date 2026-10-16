// Dev build: supply an extracted lucide-static package and a resolvable sharp install.
const fs = require('node:fs');
const path = require('node:path');
const zlib = require('node:zlib');
const sharp = require('sharp');

async function build() {
  const [source, destination] = process.argv.slice(2);
  if (!source || !destination) {
    throw new Error('Usage: node scripts/build-lucide-catalog.cjs /path/to/package/icons output.json.gz');
  }
  const catalog = {};
  const names = fs.readdirSync(source).filter(name => /^[a-z][a-z0-9-]*\.svg$/.test(name)).sort();
  for (let index = 0; index < names.length; index += 12) {
    await Promise.all(names.slice(index, index + 12).map(async name => {
      const svg = fs.readFileSync(path.join(source, name), 'utf8');
      const png = await sharp(Buffer.from(svg.replaceAll('currentColor', '#337b92'))).resize(48, 48).png().toBuffer();
      catalog[name.slice(0, -4)] = { svg, png: png.toString('base64') };
    }));
  }
  const sorted = Object.fromEntries(Object.keys(catalog).sort().map(name => [name, catalog[name]]));
  fs.writeFileSync(destination, zlib.gzipSync(JSON.stringify(sorted), { level: 9 }));
  process.stdout.write(`${names.length} icons bundled.\n`);
}
build().catch(error => { process.stderr.write(error.message + '\n'); process.exitCode = 1; });
