// Domain types (shared across UI/composables/API)

// deal sources
export type Source =
    | 'Source 1'
    | 'Source 2'
    | 'Source 3'
    | 'Source 4'
    | 'Source 5';

// customer payload (frontend)
export interface CustomerIn {
    id?: number;
    first_name?: string;
    last_name?: string;
    email?: string;
}

// deal payload (frontend)
export interface DealIn {
    name: string;
    source: Source | '';
}

// full form shape (frontend)
export interface FormState {
    customer: CustomerIn;
    deal: DealIn;
}
