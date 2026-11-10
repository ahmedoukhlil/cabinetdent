import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import ffmpegPath from 'ffmpeg-static';

const root = process.cwd();
const scenesDir = path.join(root, 'storage', 'app', 'publicite', 'scenes-v2');
const capturesDir = path.join(root, 'storage', 'app', 'publicite', 'captures');
const output = path.join(root, 'storage', 'app', 'publicite', 'sortie', 'sysdental-publicite-captures-animees-v3.mp4');
const seconds = 3.3;
const transitionDuration = 0.35;
const frames = 99;
const duration = 29.85;

// Coordinates mirror the laptop and phone masks generated in create_ad_scenes_v2.mjs.
const scenes = [
  {},
  { desktop: '01-accueil.png' },
  { desktop: '02-agenda.png' },
  { desktop: '03-attente.png' },
  { desktop: '05-dossier.png', reverse: true },
  { desktop: '06-facturation.png' },
  { desktop: '07-statistiques.png', reverse: true },
  { desktop: '01-accueil.png', reverse: true },
  { mobile: 'mobile-accueil.png' },
  { desktop: '05-dossier.png', mobile: 'mobile-dossier.png', dual: true },
];

const args = ['-hide_banner', '-loglevel', 'error'];
const inputs = [];
for (let i = 0; i < scenes.length; i++) {
  const base = path.join(scenesDir, `${String(i + 1).padStart(2, '0')}.png`);
  if (!fs.existsSync(base)) throw new Error(`Scène absente : ${base}`);
  args.push('-loop', '1', '-framerate', '30', '-t', String(seconds), '-i', base);
  const entry = { base: inputs.length };
  inputs.push(base);
  for (const type of ['desktop', 'mobile']) {
    if (!scenes[i][type]) continue;
    const capture = path.join(capturesDir, scenes[i][type]);
    if (!fs.existsSync(capture)) throw new Error(`Capture absente : ${capture}`);
    args.push('-loop', '1', '-framerate', '30', '-t', String(seconds), '-i', capture);
    entry[type] = inputs.length;
    inputs.push(capture);
  }
  scenes[i].inputs = entry;
}

const audioInput = inputs.length;
args.push('-f', 'lavfi', '-t', String(duration), '-i', 'aevalsrc=0.020*sin(2*PI*55*t)*(0.65+0.35*sin(2*PI*0.23*t))+0.012*sin(2*PI*220*t)+0.010*sin(2*PI*277.18*t)+0.008*sin(2*PI*329.63*t):s=48000');

const filters = [];
for (let i = 0; i < scenes.length; i++) {
  const scene = scenes[i];
  const pan = i % 2 === 0 ? '7*sin(on/18)' : '-7*sin(on/18)';
  filters.push(`[${scene.inputs.base}:v]scale=1080:1920,zoompan=z='1+0.018*on/${frames - 1}':x='iw/2-(iw/zoom/2)+${pan}':y='ih/2-(ih/zoom/2)':d=${frames}:s=1080x1920:fps=30,setsar=1[bg${i}]`);

  let current = `bg${i}`;
  if (scene.desktop) {
    const size = scene.dual ? '759x474' : '960x600';
    const x = scene.dual ? 58 : 60;
    const y = scene.dual ? 804 : 639;
    const progress = scene.reverse ? `1-on/${frames - 1}` : `on/${frames - 1}`;
    filters.push(`[${scene.inputs.desktop}:v]scale=1440:900,zoompan=z='1.03+0.12*on/${frames - 1}':x='(iw-iw/zoom)*(${progress})':y='(ih-ih/zoom)*(0.18+0.22*on/${frames - 1})':d=${frames}:s=${size}:fps=30,eq=saturation=1.06:contrast=1.025,setsar=1[desk${i}]`);
    filters.push(`[${current}][desk${i}]overlay=x=${x}:y=${y}:shortest=1:format=auto[deskout${i}]`);
    current = `deskout${i}`;
  }
  if (scene.mobile) {
    const size = scene.dual ? '309x720' : '454x1020';
    const x = scene.dual ? 688 : 313;
    const y = scene.dual ? 841 : 701;
    filters.push(`[${scene.inputs.mobile}:v]scale=780:1688,zoompan=z='1.025+0.10*on/${frames - 1}':x='(iw-iw/zoom)/2':y='(ih-ih/zoom)*(0.10+0.68*on/${frames - 1})':d=${frames}:s=${size}:fps=30,eq=saturation=1.06:contrast=1.025,setsar=1[mob${i}]`);
    filters.push(`[${current}][mob${i}]overlay=x=${x}:y=${y}:shortest=1:format=auto[mobout${i}]`);
    current = `mobout${i}`;
  }
  filters.push(`[${current}]trim=duration=${seconds},setpts=PTS-STARTPTS[v${i}]`);
}

const transitions = ['fadeblack', 'smoothleft', 'circleopen', 'zoomin', 'fadeblack', 'smoothup', 'wipeleft', 'zoomin', 'fadeblack'];
let previous = 'v0';
for (let i = 1; i < scenes.length; i++) {
  const next = `x${i}`;
  const offset = i * (seconds - transitionDuration);
  filters.push(`[${previous}][v${i}]xfade=transition=${transitions[i - 1]}:duration=${transitionDuration}:offset=${offset.toFixed(2)}[${next}]`);
  previous = next;
}
filters.push(`[${audioInput}:a]lowpass=f=1800,acompressor=threshold=0.08:ratio=3:attack=20:release=200,volume=0.52,afade=t=in:st=0:d=0.8,afade=t=out:st=28.2:d=1.5[a]`);

args.push('-filter_complex', filters.join(';'), '-map', `[${previous}]`, '-map', '[a]', '-t', String(duration), '-r', '30', '-c:v', 'libx264', '-preset', 'medium', '-crf', '18', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '160k', '-movflags', '+faststart', '-y', output);
const result = spawnSync(ffmpegPath, args, { stdio: 'inherit' });
if (result.status !== 0) process.exit(result.status ?? 1);
console.log(output);
