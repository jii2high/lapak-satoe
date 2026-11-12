const rupiah = new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
});

export function formatRupiah(value: number): string {
    return `Rp${rupiah.format(value)}`;
}
