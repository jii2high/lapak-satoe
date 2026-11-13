import { useForm } from '@inertiajs/react';
import { ImagePlus, ShieldAlert, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    store as storeProduct,
    update as updateProduct,
} from '@/routes/seller/products';

type Category = {
    id: number;
    name: string;
};

type ProductImage = {
    id: number;
    url: string;
};

export type ProductDetail = {
    id: number;
    name: string;
    description: string | null;
    category_id: number;
    price: number;
    stock: number;
    reserved: number;
    is_active: boolean;
    is_suspended: boolean;
    suspension_note?: string | null;
    images: ProductImage[];
};

type ProductFormData = {
    name: string;
    description: string;
    category_id: string;
    price: string;
    stock: string;
    is_active: boolean;
    images: File[];
    removed_image_ids: number[];
};

const maxImages = 5;

export function ProductForm({
    categories,
    product = null,
}: {
    categories: Category[];
    product?: ProductDetail | null;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [fileError, setFileError] = useState<string | null>(null);

    const form = useForm<ProductFormData>({
        name: product?.name ?? '',
        description: product?.description ?? '',
        category_id: product === null ? '' : String(product.category_id),
        price: product === null ? '' : String(product.price),
        stock: product === null ? '' : String(product.stock),
        is_active: product?.is_active ?? true,
        images: [],
        removed_image_ids: [],
    });

    const previews = useMemo(
        () =>
            form.data.images.map((file) => ({
                file,
                url: URL.createObjectURL(file),
            })),
        [form.data.images],
    );

    useEffect(
        () => () =>
            previews.forEach((preview) => URL.revokeObjectURL(preview.url)),
        [previews],
    );

    const keptImages =
        product === null
            ? []
            : product.images.filter(
                  (image) => !form.data.removed_image_ids.includes(image.id),
              );
    const totalImages = keptImages.length + form.data.images.length;

    const addImages = (list: FileList | null) => {
        if (list === null) {
            return;
        }

        const incoming = Array.from(list);
        const slots = maxImages - totalImages;

        if (incoming.some((file) => file.size > 3 * 1024 * 1024)) {
            setFileError('Ukuran tiap foto maksimal 3 MB.');

            return;
        }

        if (incoming.length > slots) {
            setFileError(`Maksimal ${maxImages} foto produk.`);

            return;
        }

        setFileError(null);
        form.setData('images', [...form.data.images, ...incoming]);
    };

    const removeImage = (id: number) => {
        form.setData('removed_image_ids', [...form.data.removed_image_ids, id]);
    };

    const submit = () => {
        const options = { forceFormData: true, preserveScroll: true };

        if (product === null) {
            form.post(storeProduct.url(), options);
        } else {
            form.patch(updateProduct.url(product.id), options);
        }
    };

    return (
        <form
            className="grid max-w-2xl gap-5"
            onSubmit={(event) => {
                event.preventDefault();
                submit();
            }}
        >
            {product?.is_suspended === true && (
                <div className="flex items-start gap-3 border border-l-4 border-border border-l-destructive bg-destructive/10 px-4 py-3">
                    <ShieldAlert
                        aria-hidden
                        className="mt-0.5 size-5 shrink-0 text-destructive"
                    />
                    <p className="text-sm text-foreground">
                        Produk ini dinonaktifkan admin. Produk tidak tampil di
                        katalog dan tidak bisa diaktifkan sendiri.
                        {product?.suspension_note != null &&
                            ` Catatan admin: ${product.suspension_note}`}
                    </p>
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="product-name">Nama produk</Label>
                <Input
                    id="product-name"
                    value={form.data.name}
                    maxLength={120}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                />
                <InputError message={form.errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="product-category">Kategori</Label>
                <Select
                    value={form.data.category_id}
                    onValueChange={(value) =>
                        form.setData('category_id', value)
                    }
                >
                    <SelectTrigger id="product-category" className="w-full">
                        <SelectValue placeholder="Pilih kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map((category) => (
                            <SelectItem
                                key={category.id}
                                value={String(category.id)}
                            >
                                {category.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={form.errors.category_id} />
            </div>

            <div className="grid gap-5 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="product-price">Harga</Label>
                    <Input
                        id="product-price"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        value={form.data.price}
                        onChange={(event) =>
                            form.setData('price', event.target.value)
                        }
                    />
                    <InputError message={form.errors.price} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="product-stock">Stok</Label>
                    <Input
                        id="product-stock"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        value={form.data.stock}
                        onChange={(event) =>
                            form.setData('stock', event.target.value)
                        }
                    />
                    {product !== null && product.reserved > 0 && (
                        <p className="text-xs text-muted-foreground">
                            {product.reserved} sedang dipesan. Stok tidak boleh
                            lebih kecil dari angka ini.
                        </p>
                    )}
                    <InputError message={form.errors.stock} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="product-description">Deskripsi</Label>
                <Textarea
                    id="product-description"
                    value={form.data.description}
                    maxLength={2000}
                    onChange={(event) =>
                        form.setData('description', event.target.value)
                    }
                />
                <InputError message={form.errors.description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="product-images">Foto produk</Label>

                <div className="flex flex-wrap gap-3">
                    {keptImages.map((image) => (
                        <div key={image.id} className="relative">
                            <img
                                src={image.url}
                                alt=""
                                className="size-24 border border-border object-cover"
                            />
                            <button
                                type="button"
                                aria-label="Hapus foto"
                                onClick={() => removeImage(image.id)}
                                className="absolute -top-2 -right-2 flex size-6 items-center justify-center border border-border bg-card text-destructive before:absolute before:-inset-2.5 before:content-[''] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <Trash2 aria-hidden className="size-3.5" />
                            </button>
                        </div>
                    ))}

                    {previews.map((preview) => (
                        <div key={preview.url} className="relative">
                            <img
                                src={preview.url}
                                alt={preview.file.name}
                                className="size-24 border border-border object-cover"
                            />
                            <button
                                type="button"
                                aria-label={`Hapus ${preview.file.name}`}
                                onClick={() => {
                                    setFileError(null);
                                    form.setData(
                                        'images',
                                        form.data.images.filter(
                                            (file) => file !== preview.file,
                                        ),
                                    );
                                }}
                                className="absolute -top-2 -right-2 flex size-6 items-center justify-center border border-border bg-card text-destructive before:absolute before:-inset-2.5 before:content-[''] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <Trash2 aria-hidden className="size-3.5" />
                            </button>
                        </div>
                    ))}

                    {totalImages < maxImages && (
                        <button
                            type="button"
                            onClick={() => inputRef.current?.click()}
                            className="flex size-24 flex-col items-center justify-center gap-1 border border-border bg-card text-muted-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            <ImagePlus aria-hidden className="size-5" />
                            <span className="text-xs">Tambah foto</span>
                        </button>
                    )}
                </div>

                <input
                    ref={inputRef}
                    id="product-images"
                    type="file"
                    accept="image/jpeg,image/png"
                    multiple
                    className="sr-only"
                    onChange={(event) => {
                        addImages(event.target.files);
                        event.target.value = '';
                    }}
                />

                <p className="text-xs text-muted-foreground">
                    {totalImages} dari {maxImages} foto. JPG atau PNG, maks 3 MB
                    per foto.
                </p>
                <InputError message={fileError ?? undefined} />
                <InputError message={form.errors.images} />
            </div>

            {product !== null && product.reserved > 0 && (
                <p className="text-xs text-muted-foreground">
                    Stok tersedia saat ini:{' '}
                    {Math.max(product.stock - product.reserved, 0)}.
                </p>
            )}

            <div className="flex items-start gap-3">
                <Checkbox
                    id="product-active"
                    checked={form.data.is_active}
                    disabled={product?.is_suspended === true}
                    onCheckedChange={(checked) =>
                        form.setData('is_active', checked === true)
                    }
                />
                <div className="grid gap-1">
                    <Label
                        htmlFor="product-active"
                        className="leading-snug font-normal"
                    >
                        Tampilkan produk di katalog
                    </Label>
                    <p className="text-xs text-muted-foreground">
                        Produk nonaktif tetap tersimpan tetapi tidak bisa
                        dipesan.
                    </p>
                    <InputError message={form.errors.is_active} />
                </div>
            </div>

            <Button
                type="submit"
                disabled={form.processing || totalImages < 1}
                className="self-start"
            >
                {form.processing && <Spinner />}
                {product === null ? 'Tambah produk' : 'Simpan perubahan'}
            </Button>

            {totalImages < 1 && (
                <p className="text-sm text-muted-foreground">
                    Unggah minimal satu foto produk.
                </p>
            )}
        </form>
    );
}
