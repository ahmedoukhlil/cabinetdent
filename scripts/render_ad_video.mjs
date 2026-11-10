import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import ffmpegPath from 'ffmpeg-static';

const root = process.cwd();
const scenesDir = path.join(root, 'storage', 'app', 'publicite', 'scenes');
const output = path.join(root, 'storage', 'app', 'publicite', 'sortie', 'sysdental-publicite-verticale.mp4');
const sceneFiles = Array.from({ length: 8 }, (_, i) => path.join(scenesDir, `${String(i + 1).padStart(2, '0')}.png`));
for (const file of sceneFiles) {
    if (!fs.existsSync(file)) throw new Error(`Scène absente : ${file}`);
}

const args = [];
for (const file of sceneFiles) args.push('-loop', '1', '-t', '4', '-i', file);
args.push(
    '-f', 'lavfi', '-t', '28.5', '-i',
    'aevalsrc=0.018*sin(2*PI*220*t)+0.014*sin(2*PI*277.18*t)+0.012*sin(2*PI*329.63*t):s=48000',
);

const filters = [];
for (let i = 0; i < sceneFiles.length; i++) {
    filters.push(`[${i}:v]scale=1080:1920,zoompan=z='min(zoom+0.00022,1.026)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=120:s=1080x1920:fps=30,setsar=1[v${i}]`);
}
let previous = 'v0';
const transitions = ['fade', 'smoothleft', 'fade', 'circleopen', 'fade', 'smoothleft', 'fade'];
for (let i = 1; i < sceneFiles.length; i++) {
    const next = `x${i}`;
    filters.push(`[${previous}][v${i}]xfade=transition=${transitions[i - 1]}:duration=0.5:offset=${(i * 3.5).toFixed(1)}[${next}]`);
    previous = next;
}
filters.push('[8:a]lowpass=f=1400,volume=0.38,afade=t=in:st=0:d=1,afade=t=out:st=27:d=1.5[a]');

args.push(
    '-filter_complex', filters.join(';'),
    '-map', `[${previous}]`, '-map', '[a]',
    '-t', '28.5', '-r', '30',
    '-c:v', 'libx264', '-preset', 'medium', '-crf', '18', '-pix_fmt', 'yuv420p',
    '-c:a', 'aac', '-b:a', '160k', '-movflags', '+faststart', '-y', output,
);

const result = spawnSync(ffmpegPath, args, { stdio: 'inherit' });
if (result.status !== 0) process.exit(result.status ?? 1);
console.log(output);
