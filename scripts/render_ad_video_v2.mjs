import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import ffmpegPath from 'ffmpeg-static';

const root = process.cwd();
const scenesDir = path.join(root, 'storage', 'app', 'publicite', 'scenes-v2');
const output = path.join(root, 'storage', 'app', 'publicite', 'sortie', 'sysdental-publicite-cinematique-v2.mp4');
const files = Array.from({ length: 10 }, (_, i) => path.join(scenesDir, `${String(i + 1).padStart(2, '0')}.png`));
files.forEach((file) => { if (!fs.existsSync(file)) throw new Error(`Scène absente : ${file}`); });

const seconds = 3.3;
const transitionDuration = 0.35;
const frames = 99;
const args = [];
files.forEach((file) => args.push('-loop', '1', '-t', String(seconds), '-i', file));
args.push('-f', 'lavfi', '-t', '29.85', '-i', 'aevalsrc=0.020*sin(2*PI*55*t)*(0.65+0.35*sin(2*PI*0.23*t))+0.012*sin(2*PI*220*t)+0.010*sin(2*PI*277.18*t)+0.008*sin(2*PI*329.63*t):s=48000');

const filters = [];
for (let i = 0; i < files.length; i++) {
    const pan = i % 2 === 0 ? "8*sin(on/17)" : "-8*sin(on/17)";
    filters.push(`[${i}:v]scale=1080:1920,zoompan=z='1+0.026*on/${frames - 1}':x='iw/2-(iw/zoom/2)+${pan}':y='ih/2-(ih/zoom/2)':d=${frames}:s=1080x1920:fps=30,setsar=1[v${i}]`);
}
const transitions = ['fadeblack', 'smoothleft', 'circleopen', 'zoomin', 'fadeblack', 'smoothup', 'wipeleft', 'zoomin', 'fadeblack'];
let previous = 'v0';
for (let i = 1; i < files.length; i++) {
    const next = `x${i}`;
    const offset = i * (seconds - transitionDuration);
    filters.push(`[${previous}][v${i}]xfade=transition=${transitions[i - 1]}:duration=${transitionDuration}:offset=${offset.toFixed(2)}[${next}]`);
    previous = next;
}
filters.push('[10:a]lowpass=f=1800,acompressor=threshold=0.08:ratio=3:attack=20:release=200,volume=0.52,afade=t=in:st=0:d=0.8,afade=t=out:st=28.2:d=1.5[a]');

args.push('-filter_complex', filters.join(';'), '-map', `[${previous}]`, '-map', '[a]', '-t', '29.85', '-r', '30', '-c:v', 'libx264', '-preset', 'medium', '-crf', '18', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '160k', '-movflags', '+faststart', '-y', output);
const result = spawnSync(ffmpegPath, args, { stdio: 'inherit' });
if (result.status !== 0) process.exit(result.status ?? 1);
console.log(output);
