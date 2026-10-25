export interface SeoMeta {
    title: string;
    description: string;
    canonical: string;
    robots: string;
    jsonLd?: Record<string, unknown>[] | null;
    image?: string | null;
}

export interface RarityInfo {
    name: string;
    slug: string;
    color: string;
    verified: boolean;
}

export interface PriceInfo {
    amount: string;
    display: string | null;
    currency: string;
    is_demo: boolean;
    is_verified: boolean;
    stale: boolean;
    observed_at: string | null;
    marketplace: string | null;
    exterior: string | null;
    variant: string;
}

export interface ItemCard {
    id: number;
    name: string;
    slug: string;
    href: string;
    type: string;
    image: string;
    image_alt: string;
    weapon: string | null;
    weapon_slug: string | null;
    rarity: RarityInfo | null;
    collection: string | null;
    collection_slug?: string | null;
    attribute_status: string;
    price: PriceInfo | null;
    stattrak: boolean | null;
    souvenir: boolean | null;
}

export interface Paginator<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: string;
    premium: boolean;
    currency: string;
}

export interface SharedProps {
    auth: { user: AuthUser | null };
    flash: { success: string | null; error: string | null; share_url: string | null; opening: OpeningResult | null };
    appName: string;
}

export interface OpeningResult {
    result: ItemCard;
    probabilities_known: boolean;
    drop_rates: string;
    disclaimer: string;
    selection: string;
}

export interface MapPoint {
    x: number;
    y: number;
}

export interface StrategyPlayer {
    slot: number;
    nickname: string;
    role: string;
    start_x: number | null;
    start_y: number | null;
    responsibilities: string | null;
    contingency: string | null;
}

export interface StrategyMarker {
    kind: string;
    player_slot: number | null;
    x: number;
    y: number;
    label: string | null;
    step_number: number | null;
}

export interface StrategyPath {
    player_slot: number;
    label: string | null;
    points: MapPoint[];
}

export interface UtilityAction {
    player_slot: number;
    utility_type: string;
    throw_x: number | null;
    throw_y: number | null;
    land_x: number | null;
    land_y: number | null;
    timing_seconds: number;
    step_number: number | null;
    notes: string | null;
    lineup_reference: string | null;
}

export interface TimelineStep {
    seconds: number;
    title: string;
    instructions: string | null;
}

export interface StrategyGraph {
    id: number;
    revision: number;
    title: string;
    slug: string;
    side: 't' | 'ct';
    objective: string;
    summary: string | null;
    visibility: 'private' | 'team' | 'link';
    status: string;
    is_template: boolean;
    folder_id: number | null;
    team_id: number | null;
    updated_at: string | null;
    creator: string | null;
    editor: string | null;
    map: { id: number; name: string; slug: string; image: string; license: string };
    players: StrategyPlayer[];
    markers: StrategyMarker[];
    paths: StrategyPath[];
    utility: UtilityAction[];
    timeline: TimelineStep[];
    disclaimer: string;
}
