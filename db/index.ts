import { getDatabase } from '@netlify/database';
import { drizzle } from 'drizzle-orm/netlify-db';
import * as schema from './schema.ts';

let database: ReturnType<typeof connect>;

function connect() {
  return drizzle({ client: getDatabase(), schema });
}

export function getDb() {
  return database ??= connect();
}
