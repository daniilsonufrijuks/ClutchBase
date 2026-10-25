export function formatTiming(seconds: number): string {
    const safe = Math.max(0, Math.floor(seconds));
    const minutes = Math.floor(safe / 60);
    const remain = safe % 60;
    return `${String(minutes).padStart(2, '0')}:${String(remain).padStart(2, '0')}`;
}

export function clampUnit(value: number): number {
    if (Number.isNaN(value)) {
        return 0;
    }
    return Math.min(1, Math.max(0, Math.round(value * 1_000_000) / 1_000_000));
}

export function priceLabel(price: { display: string | null; is_demo: boolean; is_verified: boolean } | null): string {
    if (!price || !price.display) {
        return 'Price unavailable';
    }
    if (price.is_demo) {
        return `Demo ${price.display}`;
    }
    if (!price.is_verified) {
        return `Unverified ${price.display}`;
    }
    return price.display;
}
