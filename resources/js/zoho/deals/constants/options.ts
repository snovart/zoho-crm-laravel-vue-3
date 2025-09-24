// Static select options for deal sources
export const SOURCE_OPTIONS = [
  'Source 1',
  'Source 2',
  'Source 3',
  'Source 4',
  'Source 5',
] as const;

export type SourceOption = typeof SOURCE_OPTIONS[number];
