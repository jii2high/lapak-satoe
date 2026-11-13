import { Check, ChevronsUpDown, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type Category = {
    id: number;
    name: string;
};

type Props = {
    categories: Category[];
    value: number[];
    onChange: (ids: number[]) => void;
    invalid?: boolean;
};

export function CategoryCombobox({
    categories,
    value,
    onChange,
    invalid,
}: Props) {
    const [open, setOpen] = useState(false);
    const selected = categories.filter((category) =>
        value.includes(category.id),
    );

    const toggle = (id: number) => {
        onChange(
            value.includes(id)
                ? value.filter((item) => item !== id)
                : [...value, id],
        );
    };

    return (
        <div className="grid gap-2">
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        type="button"
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        aria-invalid={invalid}
                        className="w-full justify-between font-normal"
                    >
                        <span className="truncate">
                            {selected.length === 0
                                ? 'Pilih kategori'
                                : `${selected.length} kategori dipilih`}
                        </span>
                        <ChevronsUpDown aria-hidden className="opacity-50" />
                    </Button>
                </PopoverTrigger>

                <PopoverContent
                    align="start"
                    className="w-[var(--radix-popover-trigger-width)] p-0"
                >
                    <Command>
                        <CommandInput placeholder="Cari kategori" />
                        <CommandList>
                            <CommandEmpty>
                                Tidak ada kategori cocok.
                            </CommandEmpty>
                            <CommandGroup>
                                {categories.map((category) => (
                                    <CommandItem
                                        key={category.id}
                                        value={category.name}
                                        onSelect={() => toggle(category.id)}
                                    >
                                        <Check
                                            aria-hidden
                                            className={cn(
                                                'size-4',
                                                value.includes(category.id)
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                        {category.name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {selected.length > 0 && (
                <ul className="flex flex-wrap gap-2">
                    {selected.map((category) => (
                        <li
                            key={category.id}
                            className="inline-flex items-center gap-1 rounded-md bg-secondary py-1 pr-1 pl-2 text-xs font-medium text-secondary-foreground"
                        >
                            {category.name}
                            <button
                                type="button"
                                aria-label={`Hapus ${category.name}`}
                                onClick={() => toggle(category.id)}
                                className="relative rounded-sm p-0.5 before:absolute before:-inset-3 before:content-[''] hover:bg-[#CFE0D4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring dark:hover:bg-secondary/80"
                            >
                                <X aria-hidden className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
