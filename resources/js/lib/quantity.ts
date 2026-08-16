/**
 * Formatação de quantidades no padrão brasileiro (vírgula decimal).
 * Use formatQty* só para exibição. Inputs HTML number continuam com ponto via formatQtyInput.
 */

export type QuantityUnit = 'm3' | 'ton';

export function formatQty(
    value: string | number | null | undefined,
    fractionDigits = 3,
): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const number = typeof value === 'number' ? value : Number(value);

    if (Number.isNaN(number)) {
        return '—';
    }

    const rounded =
        Math.round(number * 10 ** fractionDigits) / 10 ** fractionDigits;

    return rounded.toLocaleString('pt-BR', {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });
}

/** Valor para <input type="number"> / envio ao backend (ponto decimal). */
export function formatQtyInput(value: number, fractionDigits = 3): string {
    const rounded =
        Math.round(value * 10 ** fractionDigits) / 10 ** fractionDigits;

    return rounded.toFixed(fractionDigits);
}

export function unitLabel(unit: QuantityUnit | string | null | undefined): string {
    if (unit === 'm3') {
        return 'm³';
    }

    if (unit === 'ton' || unit === 't') {
        return 't';
    }

    return unit ? String(unit) : '';
}

export function formatQtyWithUnit(
    value: string | number | null | undefined,
    unit: QuantityUnit | string | null | undefined,
    fractionDigits = 3,
): string {
    const qty = formatQty(value, fractionDigits);
    const label = unitLabel(unit);

    return label ? `${qty} ${label}` : qty;
}

export function toDisplayUnit(
    quantityInProductUnit: number,
    productUnit: QuantityUnit,
    density: number,
    displayUnit: QuantityUnit,
): number {
    const safeDensity = density > 0 ? density : 1.45;

    if (displayUnit === 'm3') {
        return productUnit === 'm3'
            ? quantityInProductUnit
            : quantityInProductUnit / safeDensity;
    }

    return productUnit === 'ton'
        ? quantityInProductUnit
        : quantityInProductUnit * safeDensity;
}
