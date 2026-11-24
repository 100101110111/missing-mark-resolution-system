import { copyFile, mkdir, rm } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { build } from 'esbuild';

const output = new URL('../dist/', import.meta.url);
const styles = new URL('../Missing-marks-system_4.4/assets/css/style.css', import.meta.url);
const browser = new URL('../web/portal.mjs', import.meta.url);

await rm(output, { recursive: true, force: true });
await mkdir(new URL('assets/', output), { recursive: true });
await copyFile(styles, new URL('assets/style.css', output));
await build({
  entryPoints: [fileURLToPath(browser)],
  outfile: fileURLToPath(new URL('assets/portal.js', output)),
  platform: 'browser',
  format: 'esm',
  target: 'es2022',
  bundle: true,
  minify: true,
});
