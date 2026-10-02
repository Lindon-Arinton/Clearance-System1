// Copies third-party runtime assets from node_modules into public/assets/vendor
// so the application works fully offline (no CDN dependency on campus networks).
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const nm = (...p) => path.join(root, 'node_modules', ...p);
const out = (...p) => path.join(root, 'public', 'assets', 'vendor', ...p);

function copy(src, dest) {
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.copyFileSync(src, dest);
}

copy(nm('bootstrap', 'dist', 'js', 'bootstrap.bundle.min.js'), out('bootstrap', 'bootstrap.bundle.min.js'));
copy(nm('bootstrap-icons', 'font', 'bootstrap-icons.min.css'), out('bootstrap-icons', 'bootstrap-icons.min.css'));
for (const f of ['bootstrap-icons.woff', 'bootstrap-icons.woff2']) {
  copy(nm('bootstrap-icons', 'font', 'fonts', f), out('bootstrap-icons', 'fonts', f));
}
for (const w of [400, 500, 600, 700]) {
  const f = `dm-sans-latin-${w}-normal.woff2`;
  copy(nm('@fontsource', 'dm-sans', 'files', f), out('fonts', f));
}
copy(nm('@fontsource', 'dm-serif-display', 'files', 'dm-serif-display-latin-400-normal.woff2'), out('fonts', 'dm-serif-display-latin-400-normal.woff2'));
console.log('Vendor assets copied.');
