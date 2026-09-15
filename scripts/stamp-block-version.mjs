#!/usr/bin/env node
/**
 * Stamp the current build timestamp into blocks/search/block.json "version".
 *
 * WordPress 7.x uses the block.json `version` field as the version query
 * argument for block asset URLs (styles/scripts) unless SCRIPT_DEBUG is on.
 * A per-build timestamp busts the browser cache every time the block is
 * rebuilt, without any external enqueue logic.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const blockJsonPath = path.join(root, 'blocks', 'search', 'block.json');

const blockJson = JSON.parse(readFileSync(blockJsonPath, 'utf8'));
blockJson.version = String(Math.floor(Date.now() / 1000));

writeFileSync(blockJsonPath, JSON.stringify(blockJson, null, 4) + '\n');