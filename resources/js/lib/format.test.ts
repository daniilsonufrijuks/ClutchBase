import { describe, expect, it } from 'vitest';
import { clampUnit, formatTiming, priceLabel } from './format';

describe('format helpers', () => {
    it('formats round timings', () => {
        expect(formatTiming(0)).toBe('00:00');
        expect(formatTiming(15)).toBe('00:15');
        expect(formatTiming(75)).toBe('01:15');
    });

    it('clamps map coordinates into the unit interval', () => {
        expect(clampUnit(-0.2)).toBe(0);
        expect(clampUnit(0.2567894)).toBe(0.256789);
        expect(clampUnit(2)).toBe(1);
    });

    it('does not present a missing price as zero', () => {
        expect(priceLabel(null)).toBe('Price unavailable');
        expect(priceLabel({ display: 'USD 1.00', is_demo: true, is_verified: false })).toBe('Demo USD 1.00');
    });
});
