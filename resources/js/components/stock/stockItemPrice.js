export function uncommafy(txt) {
    return String(txt ?? '').split(',').join('').trim();
}

export function toNumber(value, fallback = 0) {
    const n = Number(uncommafy(value));
    return Number.isFinite(n) ? n : fallback;
}

export function explainPiecePrice({
    weight,
    marketMetalPrice,
    minimumPercent,
    metalPrice,
    metalName,
    goldPrice,
    formula,
    formatters,
    totalWageLabel,
}) {
    const w = Number(weight || 0);
    const fee1 = Number(formula.fee1 || 0);
    const fee2 = Number(formula.fee2 || 0);
    const fee3 = Number(formula.fee3 || 0);
    const feePercent = Number(formula.feePercent || 0);
    const profitPercent = Number(formula.profitPercent || 0);
    const taxPercent = Number(formula.taxPercent || 0);
    const addon = Number(formula.addon || 0);

    if (!w || w <= 0 || !marketMetalPrice || !metalPrice) {
        return null;
    }

    const profitRate = profitPercent / 100;
    const taxRate = taxPercent / 100;
    const p = metalPrice;
    const n1 = p + (p * (feePercent / 100));
    const n2 = (n1 + (n1 * profitRate) - p);
    const n3 = (n2 * taxRate) + n2;
    const complete = (n3 + p) * w;
    const rounded = Math.floor(complete / 1000) * 1000;
    const final = rounded + addon;

    return {
        final,
        steps: [
            {
                label: `نرخ روز ${metalName}` + (formula.metalType !== 'silver' && formula.karat !== 18 ? ` (عیار ${formula.karat} - ضریب ${formula.karatCoefficient})` : ''),
                math: formula.metalType === 'silver'
                    ? `${metalName} / گرم`
                    : (formula.karat !== 18
                        ? `${formatters.formatPlain(toNumber(goldPrice))} × (${formula.karatCoefficient} / 750)`
                        : `${metalName} / گرم`),
                value: formatters.formatPrice(marketMetalPrice),
            },
            {
                label: `حداقل درصد سود ${formatters.formatPercent(minimumPercent)}`,
                math: `${formatters.formatPlain(marketMetalPrice)} × ${formatters.formatPercent(minimumPercent)}`,
                value: formatters.formatPrice(p),
            },
            {
                label: (fee2 > 0 || fee3 > 0)
                    ? `${totalWageLabel} ${formatters.formatPercent(feePercent)}`
                    : `اجرت ${formatters.formatPercent(feePercent)}`,
                math: (fee2 > 0 || fee3 > 0)
                    ? `(${formatters.formatPercent(fee1)} + ${formatters.formatPercent(fee2)} + ${formatters.formatPercent(fee3)}) ${formatters.formatPlain(p)} + (${formatters.formatPlain(p)} × ${formatters.formatPercent(feePercent)})`
                    : `${formatters.formatPlain(p)} + (${formatters.formatPlain(p)} × ${formatters.formatPercent(feePercent)})`,
                value: formatters.formatPrice(Math.round(n1)),
            },
            {
                label: `سود ${formatters.formatPercent(profitPercent)} روی اجرت`,
                math: `(${formatters.formatPlain(Math.round(n1))} × ${formatters.formatPercent(100 + profitPercent)}) - ${formatters.formatPlain(p)}`,
                value: formatters.formatPrice(Math.round(n2)),
            },
            {
                label: `مالیات ${formatters.formatPercent(taxPercent)} روی اجرت+سود`,
                math: `${formatters.formatPlain(Math.round(n2))} × ${formatters.formatPercent(100 + taxPercent)}`,
                value: formatters.formatPrice(Math.round(n3)),
            },
            {
                label: `ضرب در وزن ${formatters.formatWeight(w)} گرم`,
                math: `(${formatters.formatPlain(Math.round(n3))} + ${formatters.formatPlain(p)}) × ${formatters.formatWeight(w)}`,
                value: formatters.formatPrice(Math.round(complete)),
            },
            {
                label: 'رند به پایین تا هزار تومان',
                math: `floor(${formatters.formatPlain(Math.round(complete))} / 1000) × 1000`,
                value: formatters.formatPrice(rounded),
            },
            {
                label: 'اضافه کردن اقلام اضافه',
                math: `${formatters.formatPlain(rounded)} + ${formatters.formatPlain(addon)}`,
                value: formatters.formatPrice(final),
            },
        ],
    };
}
