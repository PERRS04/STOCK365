const CURRENCY = {
    code: 'USD',
    symbol: '$',
    locale: 'en-US',
};

export function formatCurrency(amount) {
    return new Intl.NumberFormat(CURRENCY.locale, {
        style: 'currency',
        currency: CURRENCY.code,
    }).format(amount || 0);
}

window.formatCurrency = formatCurrency;
