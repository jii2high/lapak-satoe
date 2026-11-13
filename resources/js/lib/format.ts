const rupiah = new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
});

const shortMonth = new Intl.DateTimeFormat('id-ID', { month: 'short' });
const dayMonth = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
});
const fullDate = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});
const shortDate = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

export function formatRupiah(value: number): string {
    return `Rp${rupiah.format(value)}`;
}

/** Label sumbu chart: kunci `Y-m` jadi "Nov 26", kunci `Y-m-d` jadi "13 Nov". */
export function formatBucketLabel(key: string): string {
    const date = new Date(`${key}${key.length === 7 ? '-01' : ''}T00:00:00`);

    return key.length === 7
        ? `${shortMonth.format(date)} ${String(date.getFullYear()).slice(2)}`
        : dayMonth.format(date);
}

/** Label tooltip chart: tanggal penuh. */
export function formatBucketFull(key: string): string {
    return fullDate.format(
        new Date(`${key}${key.length === 7 ? '-01' : ''}T00:00:00`),
    );
}

/** Tanggal ringkas untuk tabel: "13 Nov 2026". */
export function formatDate(value: string): string {
    return shortDate.format(new Date(value));
}

/** Angka rupiah ringkas untuk tick sumbu: 1.250.000 jadi "1,3jt". */
export function compactRupiah(value: number): string {
    if (value >= 1_000_000) {
        return `${(value / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })}jt`;
    }

    if (value >= 1_000) {
        return `${(value / 1_000).toLocaleString('id-ID', { maximumFractionDigits: 0 })}rb`;
    }

    return String(value);
}
